<?php

namespace App\Console\Commands;

use App\Models\ImageGroup;
use App\Models\ItemMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiGroupOrphans extends Command
{
    protected $signature = 'ai:group-orphans
        {--limit=30 : Quantidade de imagens por lote}
        {--min=2 : Mínimo de imagens por grupo}
        {--max=6 : Máximo de imagens por grupo}
        {--model=models/gemini-2.5-flash : Modelo Gemini}
        {--dry-run : Não grava no banco}
    ';

    protected $description = 'Agrupa imagens órfãs e gera catálogo via IA (uma chamada)';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        $min = (int) $this->option('min');
        $max = (int) $this->option('max');
        $model = (string) $this->option('model');
        $dryRun = (bool) $this->option('dry-run');

        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            $this->error('GEMINI_API_KEY não configurada.');
            return 1;
        }

        // Buscar imagens órfãs
        $medias = ItemMedia::whereNull('item_id')
            ->whereNull('group_id')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get(['id', 'url', 'created_at']);

        if ($medias->isEmpty()) {
            $this->info('Nenhuma imagem órfã encontrada.');
            return 0;
        }

        $this->info("Processando {$medias->count()} imagens...");

        // Montar payload com imagens
        $parts = [];
        $parts[] = ['text' => $this->buildPrompt($min, $max)];

        foreach ($medias as $media) {
            $path = Storage::disk('public')->path($media->url);
            
            if (!file_exists($path)) {
                $this->warn("Arquivo não encontrado: {$media->url}");
                continue;
            }

            $mime = mime_content_type($path) ?: 'image/jpeg';
            $b64 = base64_encode(file_get_contents($path));

            // Texto com ID antes da imagem
            $parts[] = ['text' => "media_id: {$media->id}\n"];
            
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => $b64,
                ],
            ];
        }

        $modelPath = str_starts_with($model, 'models/') ? $model : "models/{$model}";
        $url = "https://generativelanguage.googleapis.com/v1beta/{$modelPath}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [[
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'temperature' => 0.1,
            ],
        ];

        $this->info('Enviando para IA...');

        try {
            $resp = Http::timeout(300)->post($url, $payload);
        } catch (\Throwable $e) {
            $this->error('Erro na requisição: ' . $e->getMessage());
            return 1;
        }

        if (!$resp->successful()) {
            $this->error("HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 300));
            return 1;
        }

        $text = $resp->json()['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if (!$text) {
            $this->error('Resposta sem texto.');
            return 1;
        }

        // Extrair JSON
        $result = $this->extractJson($text);
        
        if (!$result) {
            $this->error('Não consegui extrair JSON válido.');
            $this->line('Resposta recebida:');
            $this->line(mb_substr($text, 0, 500));
            return 1;
        }

        // Parsear resultado
        $groups = $result['groups'] ?? [];
        $ungrouped = $result['ungrouped'] ?? [];
        $catalogByGroup = $result['catalog_by_group'] ?? [];

        // Criar mapa de catálogo por group_key
        $catalogMap = [];
        foreach ($catalogByGroup as $cat) {
            if (!isset($cat['group_key'])) continue;
            $catalogMap[$cat['group_key']] = $cat;
        }

        // Mostrar resultado
        $this->info("\nResultado do agrupamento (IA):");

        $groupsCreated = 0;
        $imagesUpdated = 0;

        foreach ($groups as $g) {
            $groupKey = $g['group_key'] ?? null;
            $mediaIds = $g['media_ids'] ?? [];
            
            if (empty($mediaIds)) continue;

            $this->line(" - Grupo IA #{$groupKey}: " . implode(',', $mediaIds));

            if ($dryRun) continue;

			// Criar grupo no BD
			$catalog = $catalogMap[$groupKey] ?? null;
			$catalogData = $catalog['data'] ?? null;
			$mediaRef = $catalog['media_id_referencia'] ?? null;
			$mediaTag = $catalog['media_id_tag'] ?? null; // NOVO

			$group = ImageGroup::create([
				'grouping_method' => 'ai',
				'status' => 'pending',
				'confidence_score' => 0.90,
				'metadata' => [
					'ai_catalog' => [
						'processed_at' => now()->toIso8601String(),
						'media_id_referencia' => $mediaRef,
						'media_id_tag' => $mediaTag, // NOVO
						'payload' => $catalogData,
					],
				],
			]);

            $groupsCreated++;

            // Atualizar imagens
            foreach ($mediaIds as $mid) {
                $m = ItemMedia::find($mid);
                if ($m) {
                    $m->group_id = $group->id;
                    $m->save();
                    $imagesUpdated++;
                }
            }

            // Mostrar nome do produto se tiver
            if ($catalogData && isset($catalogData['nome_do_produto'])) {
                $this->line("   → {$catalogData['nome_do_produto']}");
            }
        }

        // Mostrar ungrouped
        if (!empty($ungrouped)) {
            $this->line(" - Ungrouped: " . implode(',', $ungrouped));
        } else {
            $this->line(" - Ungrouped: (vazio)");
        }

        if ($dryRun) {
            $this->info("\ndry-run: nada foi gravado no banco.");
            return 0;
        }

        $this->info("\nGravado no BD: grupos criados={$groupsCreated}, imagens atualizadas={$imagesUpdated}, ungrouped=" . count($ungrouped));

        return 0;
    }

	private function buildPrompt(int $min, int $max): string
	{
		return <<<PROMPT
			Você é um especialista em catalogação de produtos para e-commerce.

			Você receberá várias imagens. Cada imagem vem precedida de um texto "media_id: NNN".
			Tarefas:
			1) Agrupar as imagens que pertencem ao MESMO produto.
			2) Para CADA grupo, gerar os dados de cadastro do produto (use como referência a melhor imagem do grupo).
			3) Se houver foto de etiqueta/tag no grupo, identificar qual imagem é a etiqueta.

			Regras de agrupamento:
			- Cada grupo deve ter de {$min} a {$max} imagens.
			- Não misture produtos diferentes no mesmo grupo.
			- Se não tiver confiança para agrupar uma imagem, coloque em "ungrouped".

			Regras de catalogação:
			- Para cada grupo, escolha um "media_id_referencia" (uma imagem do grupo) e baseie a descrição nela.
			- Se não tiver certeza em algum campo, use null (não invente).
			- "ocr_textos": liste textos legíveis presentes no produto/etiquetas.
			- "nome_do_produto" deve ser curto e descritivo (máximo 50 caracteres).
			- "descricao" deve ser objetiva e útil para cadastro (máximo 350 caracteres).

			Regras extras (TAG/ETIQUETA):
			- Dentro de cada grupo, pode existir uma foto da etiqueta/tag do produto (ex.: QR Code e/ou código impresso).
			- Se houver uma imagem que parece ser a etiqueta/tag, preencha "media_id_tag" com o media_id dessa imagem.
			- Se não houver imagem de etiqueta, use null.
			- Se houver QR Code visível, marque "qrcode_presente": true. Caso contrário, false.
			- Se houver um código do produto legível na tag/etiqueta (ex.: SKU, REF, código alfanumérico), preencha "codigo_produto_tag".
			- Em "tag_textos", liste apenas textos/códigos que parecem vir da etiqueta (não do fundo).

			Retorne SOMENTE JSON válido (sem texto fora do JSON) exatamente neste formato:

			{
			  "groups": [
				{ "group_key": "G1", "media_ids": [123,124] }
			  ],
			  "ungrouped": [125],
			  "catalog_by_group": [
				{
				  "group_key": "G1",
				  "media_id_referencia": 123,
				  "media_id_tag": 124,
				  "data": {
					"categoria_sugerida": "string",
					"tipo_produto": "string",
					"cor_dominante": "string",
					"marca": "string ou null",
					"tamanho": "string ou null",
					"ocr_textos": ["string"],
					"atributos_visuais": ["string"],
					"nome_do_produto": "string",
					"descricao": "string",
					"preco_estimado": "number ou null",
					"qrcode_presente": "boolean",
					"codigo_produto_tag": "string ou null",
					"tag_textos": ["string"]
				  }
				}
			  ]
			}
		PROMPT;
	}

    private function extractJson(string $text): ?array
    {
        if (!preg_match('/\{[\s\S]*\}/', $text, $m)) {
            return null;
        }
        $decoded = json_decode($m[0], true);
        return is_array($decoded) ? $decoded : null;
    }
}