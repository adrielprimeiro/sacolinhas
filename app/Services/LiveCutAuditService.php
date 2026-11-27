<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemMedia;
use App\Models\Live;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LiveCutAuditService
{
    /**
     * Grupos semânticos de categorias de vestuário para detectar troca de peça no áudio.
     */
    protected array $categoryGroups = [
        'vestido' => ['vestido', 'chemise', 'tubinho'],
        'calca'   => ['calca', 'jeans', 'pantalona', 'pantacourt', 'legging', 'jogger', 'flare', 'wide leg', 'mom jeans'],
        'blusa'   => ['blusa', 'camisa', 'camiseta', 't-shirt', 'tshirt', 'regata', 'cropped', 'top', 'body', 'trico', 'tricot', 'sueter', 'bata', 'polo'],
        'saia'    => ['saia', 'minissaia'],
        'short'   => ['short', 'shorts', 'bermuda'],
        'casaco'  => ['casaco', 'jaqueta', 'blazer', 'cardigan', 'cardiga', 'moletom', 'colete', 'sobretudo', 'parka', 'trench'],
        'macacao' => ['macacao', 'macaquinho', 'jardineira'],
        'calcado' => ['melissa', 'tenis', 'sandalia', 'bota', 'sapato', 'chinelo', 'rasteira', 'rasteirinha', 'scarpin', 'tamanco', 'mocassim', 'sapatilha', 'coturno', 'mule', 'papete', 'plataforma', 'salto'],
        'bolsa'   => ['bolsa', 'mochila', 'carteira', 'pochete', 'clutch', 'necessaire'],
        'praia'   => ['biquini', 'maio', 'saida de praia'],
        'conjunto'=> ['conjunto', 'conjuntinho'],
    ];

    /**
     * Audita um único item da live (regras determinísticas + opcionalmente Visão IA / Texto IA).
     */
    public function auditLiveItem(object $row, ?Live $live = null, bool $useVision = false): array
    {
        $expectedCode = $this->extractNumericCode($row->codigo_live ?? $row->item_codigo ?? '');
        $itemName = trim((string) ($row->item_nome ?? $row->nome_do_produto ?? ''));
        $itemDesc = trim((string) ($row->item_descricao ?? $row->descricao ?? ''));
        $itemBrand = trim((string) ($row->item_marca ?? $row->marca ?? ''));
        $itemColor = trim((string) ($row->item_cor ?? $row->cor ?? ''));
        $itemSize = trim((string) ($row->item_tamanho ?? $row->tamanho ?? ''));
        $itemPrice = (float) ($row->item_price ?? $row->preco ?? 0);

        $snippet = trim((string) ($row->transcription_snippet ?? ''));
        if ($snippet === '' && $live && !empty($live->transcription_raw) && $row->cut_start_sec !== null && $row->cut_end_sec !== null) {
            $snippet = $this->extractSnippetFromRaw($live->transcription_raw, (float) $row->cut_start_sec, (float) $row->cut_end_sec);
        }

        // 1) Checagem determinística rápida (código falado, preço, tipo de peça, marca, tamanho)
        $ruleResult = $this->runDeterministicAudit([
            'expected_code' => $expectedCode,
            'name'          => $itemName,
            'description'   => $itemDesc,
            'brand'         => $itemBrand,
            'color'         => $itemColor,
            'size'          => $itemSize,
            'price'         => $itemPrice,
            'snippet'       => $snippet,
            'has_cut'       => !empty($row->video_cut_path) || !empty($row->video_cut_url) || ($row->cut_start_sec !== null && $row->cut_end_sec !== null),
        ]);

        // 2) Se solicitado modo IA (Visão na thumbnail + transcrição), executa análise multimodal com Gemini
        if ($useVision) {
            $thumbAbsolutePath = $this->resolveThumbnailAbsolutePath($row);
            $aiResult = $this->runGeminiAudit([
                'expected_code' => $expectedCode,
                'name'          => $itemName,
                'description'   => $itemDesc,
                'brand'         => $itemBrand,
                'color'         => $itemColor,
                'size'          => $itemSize,
                'price'         => $itemPrice,
                'snippet'       => $snippet,
                'thumb_path'    => $thumbAbsolutePath,
            ]);

            if ($aiResult !== null) {
                // Combina sinal forte de código falado errado com o julgamento visual do Gemini
                $finalStatus = $aiResult['status'];
                $finalScore = $aiResult['score'];
                $reasons = [];

                if (!empty($aiResult['reason'])) {
                    $reasons[] = $aiResult['reason'];
                }
                if (!empty($aiResult['visual_description'])) {
                    $reasons[] = 'Imagem: ' . $aiResult['visual_description'];
                }

                // Se a regra determinística detectou código explicitamente trocado e a IA não percebeu, rebaixa
                if ($ruleResult['status'] === 'mismatch' && !empty($ruleResult['detected_code']) && $finalStatus === 'ok') {
                    $finalStatus = 'warning';
                    $finalScore = min($finalScore, 55);
                    $reasons[] = $ruleResult['reason'];
                }

                return [
                    'status'        => $finalStatus,
                    'score'         => max(0, min(100, (int) $finalScore)),
                    'reason'        => implode(' | ', array_filter($reasons)),
                    'detected_code' => $aiResult['detected_code'] ?: $ruleResult['detected_code'],
                    'method'        => $thumbAbsolutePath ? 'ai_vision' : 'ai_text',
                ];
            }
        }

        return $ruleResult;
    }

    /**
     * Salva o resultado da auditoria em live_items (sem sobrescrever itens aprovados manualmente se preserveApproved=true).
     */
    public function persistAuditResult(int $liveItemId, array $result): void
    {
        if (!Schema::hasColumn('live_items', 'audit_status')) {
            return;
        }

        DB::table('live_items')
            ->where('id', $liveItemId)
            ->update([
                'audit_status'        => $result['status'] ?? 'warning',
                'audit_score'         => (int) ($result['score'] ?? 50),
                'audit_reason'        => mb_substr((string) ($result['reason'] ?? ''), 0, 1000),
                'audit_detected_code' => $result['detected_code'] ? mb_substr((string) $result['detected_code'], 0, 32) : null,
                'audit_method'        => $result['method'] ?? 'rules',
                'audited_at'          => now(),
                'updated_at'          => now(),
            ]);
    }

    /**
     * Detecta descompasso em cascata (efeito dominó, ex.: "a partir do #311 todos errados").
     */
    public function detectCascadeDrift(Collection $items): array
    {
        $ordered = $items->sortBy(function ($item) {
            $code = is_array($item) ? ($item['codigo_live'] ?? '') : ($item->codigo_live ?? '');
            $num = (int) preg_replace('/\D+/', '', (string) $code);
            return $num > 0 ? $num : 999999;
        })->values();

        $mismatchCount = 0;
        $warningCount = 0;
        $okCount = 0;
        $approvedCount = 0;
        $pendingCount = 0;

        $firstCascadeCode = null;
        $consecutiveBad = 0;
        $runStartCode = null;
        $mismatchCodes = [];

        foreach ($ordered as $item) {
            $status = is_array($item) ? ($item['audit_status'] ?? null) : ($item->audit_status ?? null);
            $code = is_array($item) ? ($item['codigo_live'] ?? '') : ($item->codigo_live ?? '');

            if (in_array($status, ['mismatch', 'rejected'], true)) {
                $mismatchCount++;
                $mismatchCodes[] = $code;
                if ($consecutiveBad === 0) {
                    $runStartCode = $code;
                }
                $consecutiveBad++;
                if ($consecutiveBad >= 2 && $firstCascadeCode === null) {
                    $firstCascadeCode = $runStartCode;
                }
            } else {
                $consecutiveBad = 0;
                $runStartCode = null;
                if ($status === 'warning') {
                    $warningCount++;
                } elseif ($status === 'approved') {
                    $approvedCount++;
                } elseif ($status === 'ok') {
                    $okCount++;
                } else {
                    $pendingCount++;
                }
            }
        }

        $affectedFromCascade = 0;
        if ($firstCascadeCode !== null) {
            $firstNum = (int) preg_replace('/\D+/', '', (string) $firstCascadeCode);
            $affectedFromCascade = $ordered->filter(function ($item) use ($firstNum) {
                $code = is_array($item) ? ($item['codigo_live'] ?? '') : ($item->codigo_live ?? '');
                $status = is_array($item) ? ($item['audit_status'] ?? null) : ($item->audit_status ?? null);
                $num = (int) preg_replace('/\D+/', '', (string) $code);
                return $num >= $firstNum && in_array($status, ['mismatch', 'rejected', 'warning'], true);
            })->count();
        }

        return [
            'has_cascade_drift'     => $firstCascadeCode !== null,
            'first_bad_code'        => $firstCascadeCode,
            'affected_from_cascade' => $affectedFromCascade,
            'mismatch_count'        => $mismatchCount,
            'warning_count'         => $warningCount,
            'ok_count'              => $okCount,
            'approved_count'        => $approvedCount,
            'pending_count'         => $pendingCount,
            'summary'               => $firstCascadeCode !== null
                ? "Descompasso em cascata detectado a partir do código #{$firstCascadeCode} ({$affectedFromCascade} itens afetados)."
                : ($mismatchCount > 0
                    ? "{$mismatchCount} corte(s) incompatível(is) detectado(s) para revisão."
                    : 'Nenhum descompasso em cascata detectado.'),
        ];
    }

    /**
     * Reprova um corte e remove vídeo/thumbnail de live_cuts/ do cadastro do item para não aparecer errado na loja.
     * Preserva fotos reais de estúdio se o item já tinha fotos próprias.
     */
    public function unlinkCutMediaFromItem(int $liveItemId, ?string $reason = null): array
    {
        $liveItem = DB::table('live_items')->where('id', $liveItemId)->first();
        if (!$liveItem) {
            return ['success' => false, 'message' => 'Item da live não encontrado.'];
        }

        $removedMediaCount = 0;
        $clearedImage = false;

        if (!empty($liveItem->item_id)) {
            // Remove apenas vídeos vinculados da live (preserva fotos reais do produto!)
            $removedMediaCount = ItemMedia::where('item_id', $liveItem->item_id)
                ->where('media_type', 'video')
                ->where(function ($q) use ($liveItem) {
                    $q->where('url', 'like', '%live_cuts/%');
                    if (!empty($liveItem->video_cut_path)) {
                        $q->orWhere('url', $liveItem->video_cut_path);
                    }
                })
                ->delete();

            // Se a capa principal do item apontava para live_cuts/, limpa ou restaura a 1ª foto de estúdio real
            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && !empty($itemObj->image) && str_contains($itemObj->image, 'live_cuts/')) {
                $studioPhoto = ItemMedia::where('item_id', $itemObj->id)
                    ->where('media_type', 'image')
                    ->where('url', 'not like', '%live_cuts/%')
                    ->orderByDesc('is_cover')
                    ->orderBy('position')
                    ->orderBy('id')
                    ->first();

                $itemObj->image = $studioPhoto ? ($studioPhoto->thumbnail_url ?: $studioPhoto->url) : null;
                $itemObj->save();
                $clearedImage = true;
            }
        }

        $update = [
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('live_items', 'is_reviewed')) {
            $update['is_reviewed'] = false;
        }
        if (Schema::hasColumn('live_items', 'audit_status')) {
            $update['audit_status'] = 'rejected';
            $update['audit_method'] = 'human';
            $update['audited_at'] = now();
            if ($reason) {
                $update['audit_reason'] = mb_substr($reason, 0, 1000);
            }
        }
        DB::table('live_items')->where('id', $liveItemId)->update($update);

        Cache::forget('loja_popular_sizes_v2');
        Cache::forget('loja_quick_stats_v2');
        Cache::forget('loja_sitemap_xml_v1');

        return [
            'success'             => true,
            'removed_media_count' => $removedMediaCount,
            'cleared_image'       => $clearedImage,
        ];
    }

    /**
     * Aprova manualmente um corte e garante que vídeo + thumbnail estejam vinculados ao item para a loja.
     */
    public function approveCutForStore(int $liveItemId): array
    {
        $liveItem = DB::table('live_items')->where('id', $liveItemId)->first();
        if (!$liveItem) {
            return ['success' => false, 'message' => 'Item da live não encontrado.'];
        }

        $primaryThumbPath = $this->resolveThumbnailRelativePath($liveItem);

        // Garante que o vídeo e a thumbnail estejam vinculados no ItemMedia se o corte já existir
        if (!empty($liveItem->item_id) && !empty($liveItem->video_cut_path)) {
            ItemMedia::updateOrCreate(
                [
                    'item_id'    => $liveItem->item_id,
                    'media_type' => 'video',
                ],
                [
                    'url'           => $liveItem->video_cut_path,
                    'thumbnail_url' => $primaryThumbPath,
                    'position'      => 99,
                    'is_cover'      => false,
                    'alt_text'      => 'Vídeo do produto na Live',
                ]
            );

            $itemObj = Item::find($liveItem->item_id);
            if ($itemObj && empty($itemObj->image) && $primaryThumbPath) {
                $itemObj->image = $primaryThumbPath;
                $itemObj->save();
            }
        }

        $update = [
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('live_items', 'is_reviewed')) {
            $update['is_reviewed'] = true;
        }
        if (Schema::hasColumn('live_items', 'audit_status')) {
            $update['audit_status'] = 'approved';
            $update['audit_score'] = 100;
            $update['audit_reason'] = 'Conferido e aprovado manualmente para a Loja.';
            $update['audit_method'] = 'human';
            $update['audited_at'] = now();
        }
        DB::table('live_items')->where('id', $liveItemId)->update($update);

        Cache::forget('loja_popular_sizes_v2');
        Cache::forget('loja_quick_stats_v2');
        Cache::forget('loja_sitemap_xml_v1');

        return [
            'success'         => true,
            'has_thumbnail'   => !empty($primaryThumbPath),
        ];
    }

    /**
     * Auditoria determinística de texto (código falado, preço, categoria de roupa, marca, tamanho).
     */
    protected function runDeterministicAudit(array $ctx): array
    {
        if (empty($ctx['has_cut'])) {
            return [
                'status'        => 'warning',
                'score'         => 0,
                'reason'        => 'Item ainda não possui minutagem ou corte de vídeo gerado.',
                'detected_code' => null,
                'method'        => 'rules',
            ];
        }

        $snippetRaw = (string) ($ctx['snippet'] ?? '');
        if (trim($snippetRaw) === '') {
            return [
                'status'        => 'warning',
                'score'         => 45,
                'reason'        => 'Sem trecho de transcrição neste corte — confira visualmente a thumbnail.',
                'detected_code' => null,
                'method'        => 'rules',
            ];
        }

        $normSnippet = $this->normalizeText($snippetRaw);
        $expectedCode = $ctx['expected_code'];
        $itemPrice = (float) ($ctx['price'] ?? 0);
        $itemSize = $this->normalizeText((string) ($ctx['size'] ?? ''));
        $itemBrand = $this->normalizeText((string) ($ctx['brand'] ?? ''));
        $itemFullText = $this->normalizeText(trim(($ctx['name'] ?? '') . ' ' . ($ctx['description'] ?? '')));

        $score = 50;
        $positiveReasons = [];
        $issues = [];
        $detectedWrongCode = null;
        $hardMismatch = false;

        // 1) Código explicitamente mencionado no áudio
        $spokenCodes = $this->extractSpokenCodes($normSnippet, $itemPrice, $itemSize);
        $expectedCodeFound = false;

        if ($expectedCode !== '') {
            $expectedInt = (int) $expectedCode;
            if (in_array($expectedInt, $spokenCodes['explicit'], true)
                || in_array($expectedInt, $spokenCodes['any_number'], true)
                || $this->containsSpokenNumber($normSnippet, $expectedInt)) {
                $expectedCodeFound = true;
                $score += 35;
                $positiveReasons[] = "Código #{$expectedCode} confirmado na fala";
            } elseif (!empty($spokenCodes['explicit'])) {
                // Apresentadora falou explicitamente "código X" e NÃO falou o código esperado!
                $wrong = $spokenCodes['explicit'][0];
                $detectedWrongCode = (string) $wrong;
                $hardMismatch = true;
                $score -= 45;
                $issues[] = "Código falado na live foi #{$wrong}, mas o item cadastrado é #{$expectedCode}";
            } else {
                // Nenhum código explícito detectado — verifica se aparece número muito próximo (ex: ±25, fora preço/tamanho)
                $nearOther = null;
                foreach ($spokenCodes['any_number'] as $n) {
                    if ($n !== $expectedInt && $n >= 10 && abs($n - $expectedInt) <= 30 && abs($n - $expectedInt) >= 1) {
                        if ($itemPrice > 0 && abs($n - round($itemPrice)) <= 1) {
                            continue;
                        }
                        if ($itemSize !== '' && is_numeric($itemSize) && (int) $itemSize === $n) {
                            continue;
                        }
                        $nearOther = $n;
                        break;
                    }
                }
                if ($nearOther !== null) {
                    $detectedWrongCode = (string) $nearOther;
                    $score -= 25;
                    $issues[] = "Fala cita número #{$nearOther} sem citar #{$expectedCode}";
                } else {
                    $score -= 10;
                    $issues[] = "Código #{$expectedCode} não apareceu na transcrição deste trecho";
                }
            }
        }

        // 2) Preço falado vs Preço cadastrado
        if ($itemPrice > 0) {
            $spokenPrices = $this->extractSpokenPrices($normSnippet);
            if (!empty($spokenPrices)) {
                $priceMatched = false;
                foreach ($spokenPrices as $sp) {
                    if (abs($sp - $itemPrice) <= 1.5) {
                        $priceMatched = true;
                        break;
                    }
                }
                if ($priceMatched) {
                    $score += 15;
                    $positiveReasons[] = 'Preço bate (R$ ' . number_format($itemPrice, 2, ',', '.') . ')';
                } else {
                    $firstSpoken = $spokenPrices[0];
                    $score -= 22;
                    $issues[] = 'Preço falado (R$ ' . number_format($firstSpoken, 2, ',', '.') . ') difere do cadastro (R$ ' . number_format($itemPrice, 2, ',', '.') . ')';
                }
            }
        }

        // 3) Grupo de categoria da peça (vestido vs calça vs calçado etc.)
        $expectedGroups = $this->detectCategoryGroups($itemFullText);
        $spokenGroups = $this->detectCategoryGroups($normSnippet);

        if (!empty($expectedGroups) && !empty($spokenGroups)) {
            $overlap = array_intersect($expectedGroups, $spokenGroups);
            if (!empty($overlap)) {
                $score += 15;
                $positiveReasons[] = 'Tipo de peça compatível (' . implode('/', $overlap) . ')';
            } else {
                $score -= 32;
                $hardMismatch = $hardMismatch || !$expectedCodeFound;
                $issues[] = 'Peça falada (' . implode('/', $spokenGroups) . ') difere do cadastro (' . implode('/', $expectedGroups) . ')';
            }
        }

        // 4) Marca mencionada na fala
        if (strlen($itemBrand) >= 3 && str_contains($normSnippet, $itemBrand)) {
            $score += 15;
            $positiveReasons[] = 'Marca confirmada na fala';
        }

        // 5) Tamanho mencionado na fala
        if ($itemSize !== '' && preg_match('/\b' . preg_quote($itemSize, '/') . '\b/u', $normSnippet)) {
            $score += 10;
            $positiveReasons[] = 'Tamanho confirmado';
        }

        $score = max(0, min(100, $score));

        if ($hardMismatch || $score < 40) {
            $status = 'mismatch';
        } elseif ($score >= 75 && empty($issues)) {
            $status = 'ok';
        } elseif ($score >= 70 && $expectedCodeFound) {
            $status = 'ok';
        } else {
            $status = 'warning';
        }

        $reasonParts = !empty($issues) ? $issues : $positiveReasons;
        if (empty($reasonParts)) {
            $reasonParts[] = 'Transcrição inconclusiva — recomendamos conferência visual.';
        }

        return [
            'status'        => $status,
            'score'         => $score,
            'reason'        => implode(' • ', $reasonParts),
            'detected_code' => $detectedWrongCode ?: ($expectedCodeFound ? $expectedCode : null),
            'method'        => 'rules',
        ];
    }

    /**
     * Chama Gemini Multimodal (Foto da thumbnail + Transcrição vs Cadastro do item).
     */
    protected function runGeminiAudit(array $ctx): ?array
    {
        $geminiKey = config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));
        if (empty($geminiKey)) {
            return null;
        }

        $priceFmt = $ctx['price'] > 0 ? ('R$ ' . number_format((float) $ctx['price'], 2, ',', '.')) : 'Não informado';
        $prompt = "Você é um auditor de qualidade de brechó online (Minha Mania).\n"
            . "Compare o ITEM CADASTRADO abaixo com a IMAGEM (thumbnail do vídeo da live) e o TRECHO FALADO na live.\n"
            . "Seu objetivo é descobrir se o vídeo/foto realmente corresponde ao item cadastrado ou se houve descompasso de minutagem (vídeo de outra peça).\n\n"
            . "=== ITEM CADASTRADO ===\n"
            . "- Código da Live: #{$ctx['expected_code']}\n"
            . "- Produto: {$ctx['name']}\n"
            . "- Marca: " . ($ctx['brand'] ?: 'Não informada') . "\n"
            . "- Cor: " . ($ctx['color'] ?: 'Não informada') . "\n"
            . "- Tamanho: " . ($ctx['size'] ?: 'Não informado') . "\n"
            . "- Preço: {$priceFmt}\n"
            . ($ctx['description'] ? "- Descrição: " . mb_substr($ctx['description'], 0, 220) . "\n" : '')
            . "\n=== TRECHO FALADO NO CORTE ===\n"
            . ($ctx['snippet'] ? $ctx['snippet'] : '(Sem transcrição disponível — julgue pela imagem)') . "\n\n"
            . "REGRAS DE JULGAMENTO:\n"
            . "1. Se a imagem mostra um tipo de peça/cor totalmente diferente do cadastro (ex: cadastro diz 'Vestido Preto' e a foto mostra uma calça jeans ou sapato) -> status='mismatch'.\n"
            . "2. Se no texto falado a apresentadora anuncia claramente OUTRO código (ex: 'código 314' quando o item é #311) ou outro produto/preço -> status='mismatch'.\n"
            . "3. Se imagem e fala batem com o item cadastrado -> status='ok'.\n"
            . "4. Se a imagem estiver borrada/sem mostrar a peça direito e o texto for vago -> status='warning'.\n\n"
            . "Responda SOMENTE em JSON válido neste formato exato:\n"
            . '{"status":"ok|warning|mismatch","score":0,"detected_code":null,"visual_description":"descrição curta da peça vista na foto","reason":"explicação direta em português de até 140 caracteres"}';

        $parts = [['text' => $prompt]];

        $thumbPath = $ctx['thumb_path'] ?? null;
        if ($thumbPath && file_exists($thumbPath) && filesize($thumbPath) > 500 && filesize($thumbPath) < 4 * 1024 * 1024) {
            $ext = strtolower(pathinfo($thumbPath, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'png'  => 'image/png',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data'      => base64_encode(file_get_contents($thumbPath)),
                ],
            ];
        }

        $models = ['gemini-2.5-flash', 'gemini-2.0-flash'];
        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$geminiKey}";
                $resp = Http::timeout(25)->post($url, [
                    'contents' => [
                        ['role' => 'user', 'parts' => $parts],
                    ],
                    'generationConfig' => [
                        'temperature'      => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

                if (!$resp->successful()) {
                    Log::warning("[LiveCutAudit] Gemini {$model} falhou ({$resp->status()}): " . mb_substr($resp->body(), 0, 300));
                    continue;
                }

                $raw = $resp->json('candidates.0.content.parts.0.text');
                if (!$raw) {
                    continue;
                }

                $clean = trim($raw);
                if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $clean, $m)) {
                    $clean = trim($m[1]);
                }

                $parsed = json_decode($clean, true);
                if (is_array($parsed) && isset($parsed['status'])) {
                    $st = in_array($parsed['status'], ['ok', 'warning', 'mismatch'], true) ? $parsed['status'] : 'warning';
                    return [
                        'status'             => $st,
                        'score'              => max(0, min(100, (int) ($parsed['score'] ?? ($st === 'ok' ? 90 : ($st === 'mismatch' ? 15 : 55))))),
                        'detected_code'      => !empty($parsed['detected_code']) ? preg_replace('/\D+/', '', (string) $parsed['detected_code']) : null,
                        'visual_description' => trim((string) ($parsed['visual_description'] ?? '')),
                        'reason'             => trim((string) ($parsed['reason'] ?? '')),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning("[LiveCutAudit] Exceção Gemini {$model}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Resolve o caminho relativo da thumbnail de um live_item (preferindo candidatas do corte).
     */
    public function resolveThumbnailRelativePath(object $row): ?string
    {
        if (!empty($row->thumbnail_candidates)) {
            $cands = is_string($row->thumbnail_candidates) ? json_decode($row->thumbnail_candidates, true) : $row->thumbnail_candidates;
            if (is_array($cands) && !empty($cands)) {
                $chosen = $cands[1] ?? $cands[0];
                $path = is_array($chosen) ? ($chosen['path'] ?? null) : (is_string($chosen) ? $chosen : null);
                if (!empty($path)) {
                    return ltrim(str_replace('/storage/', '', $path), '/');
                }
            }
        }

        if (!empty($row->item_id)) {
            $videoMedia = ItemMedia::where('item_id', $row->item_id)
                ->where('media_type', 'video')
                ->whereNotNull('thumbnail_url')
                ->where('thumbnail_url', '!=', '')
                ->first();
            if ($videoMedia && !empty($videoMedia->thumbnail_url)) {
                return ltrim(str_replace('/storage/', '', $videoMedia->thumbnail_url), '/');
            }
        }

        $rawImg = $row->item_image ?? $row->image ?? null;
        if (!empty($rawImg) && !str_starts_with($rawImg, 'http')) {
            return ltrim(str_replace('/storage/', '', $rawImg), '/');
        }

        return null;
    }

    protected function resolveThumbnailAbsolutePath(object $row): ?string
    {
        $rel = $this->resolveThumbnailRelativePath($row);
        if (!$rel) {
            return null;
        }
        $abs = Storage::disk('public')->path($rel);
        return file_exists($abs) ? $abs : null;
    }

    protected function extractSnippetFromRaw(string $rawJson, float $start, float $end): string
    {
        $sentences = json_decode($rawJson, true);
        if (!is_array($sentences)) {
            return '';
        }
        $parts = [];
        foreach ($sentences as $s) {
            $sStart = (float) ($s['start'] ?? 0);
            $sEnd = (float) ($s['end'] ?? $sStart);
            if ($sEnd >= ($start - 0.5) && $sStart <= ($end + 0.5)) {
                $txt = trim((string) ($s['text'] ?? ''));
                if ($txt !== '') {
                    $parts[] = $txt;
                }
            }
        }
        return implode(' ', $parts);
    }

    protected function extractNumericCode(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    protected function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];
        $text = strtr($text, $map);
        return preg_replace('/\s+/', ' ', trim($text));
    }

    protected function extractSpokenCodes(string $normSnippet, float $itemPrice, string $itemSize): array
    {
        $explicit = [];
        $anyNumber = [];

        // Padrões explícitos: "codigo 314", "numero 314", "peca 314", "cod 314", "#314"
        if (preg_match_all('/(?:codigo|cod\.?|numero|num\.?|peca|#)\s*[:\-]?\s*(\d{1,4})\b/u', $normSnippet, $m)) {
            foreach ($m[1] as $num) {
                $explicit[] = (int) $num;
            }
        }

        if (preg_match_all('/\b(\d{1,4})\b/u', $normSnippet, $all)) {
            foreach ($all[1] as $num) {
                $anyNumber[] = (int) $num;
            }
        }

        return [
            'explicit'   => array_values(array_unique($explicit)),
            'any_number' => array_values(array_unique($anyNumber)),
        ];
    }

    protected function extractSpokenPrices(string $normSnippet): array
    {
        $prices = [];
        if (preg_match_all('/(?:r\$\s*|por\s+|apenas\s+)?(\d{1,4})(?:[.,](\d{2}))?\s*(?:reais|real)\b/u', $normSnippet, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $val = (float) ($match[1] . '.' . ($match[2] ?? '00'));
                if ($val >= 5 && $val <= 2000) {
                    $prices[] = $val;
                }
            }
        }
        return array_values(array_unique($prices));
    }

    protected function containsSpokenNumber(string $normSnippet, int $num): bool
    {
        if (preg_match('/\b' . $num . '\b/', $normSnippet)) {
            return true;
        }
        // Também reconhece dezenas/centenas faladas por extenso (ex: 311 -> "trezentos e onze")
        $words = $this->intToPortugueseWords($num);
        if ($words && str_contains($normSnippet, $words)) {
            return true;
        }
        return false;
    }

    protected function intToPortugueseWords(int $num): ?string
    {
        if ($num <= 0 || $num > 999) {
            return null;
        }
        $units = ['', 'um', 'dois', 'tres', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $tens = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $hundreds = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        if ($num === 100) {
            return 'cem';
        }
        $parts = [];
        $h = intdiv($num, 100);
        $rem = $num % 100;
        if ($h > 0) {
            $parts[] = $hundreds[$h];
        }
        if ($rem > 0) {
            if ($rem < 20) {
                $parts[] = $units[$rem];
            } else {
                $t = intdiv($rem, 10);
                $u = $rem % 10;
                $parts[] = $tens[$t];
                if ($u > 0) {
                    $parts[] = $units[$u];
                }
            }
        }
        return implode(' e ', $parts);
    }

    protected function detectCategoryGroups(string $normText): array
    {
        $found = [];
        foreach ($this->categoryGroups as $group => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . 's?\b/u', $normText)) {
                    $found[] = $group;
                    break;
                }
            }
        }
        return array_values(array_unique($found));
    }
}
