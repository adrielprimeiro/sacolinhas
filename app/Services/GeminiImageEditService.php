<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiImageEditService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct()
    {
        //$this->apiKey = (string) config('services.gemini.api_key');
		$this->apiKey = (string) config('services.gemini.paid_api_key'); 
        $this->model  = (string) config('services.gemini.model', 'gemini-3.1-flash-image-preview');
    }

    public function editImage(string $imagePath, string $prompt = null): array
    {
        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            if (!is_file($fullPath)) return ['success' => false, 'error' => 'Arquivo não encontrado.'];

            $imageBinary = file_get_contents($fullPath);
            $base64Image = base64_encode($imageBinary);
            $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';

            $gemini = $this->callGemini($base64Image, $mimeType, $prompt ?: $this->getDefaultPrompt());
            
            if (!$gemini['success']) return ['success' => false, 'error' => $gemini['error']];

            $editedPath = $this->saveEditedImageAsPng($imagePath, $gemini['image_data']);

            return ['success' => true, 'path' => $editedPath];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function callGemini(string $base64Image, string $mimeType, string $prompt): array
    {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
        $payload = ['contents' => [['parts' => [['text' => $prompt], ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Image]]]]]];

        $response = Http::timeout(120)->post($url, $payload);
        if (!$response->successful()) return ['success' => false, 'error' => $response->body()];

        $data = $response->json();
        $imageBase64 = $this->extractInlineImageBase64($data);

        if (!$imageBase64) return ['success' => false, 'error' => 'Nenhuma imagem retornada.'];

        return ['success' => true, 'image_data' => $imageBase64];
    }

    private function extractInlineImageBase64(array $data): ?string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (isset($part['inline_data']['data'])) return $part['inline_data']['data'];
            if (isset($part['inlineData']['data'])) return $part['inlineData']['data'];
        }
        return null;
    }

    private function saveEditedImageAsPng(string $originalPath, string $base64Image): string
    {
        $pathInfo = pathinfo($originalPath);
        $dir = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? 'image';

        // Nome único para evitar sobrescrever
        $newFilename = "{$filename}_edited_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . ".png";
        $newPath = ($dir && $dir !== '.') ? "{$dir}/{$newFilename}" : $newFilename;

        Storage::disk('public')->put($newPath, base64_decode($base64Image));

        return $newPath;
    }

	private function getDefaultPrompt(): string
	{
		return "Aja como um editor de imagens profissional especializado em e-commerce, com o objetivo principal de preparar a imagem fornecida para uso em e-commerce. 
		
		**Instruções Detalhadas:** 
		
		1. **Limpeza e Retoque:** 
		* Remova quaisquer dobras, amassados ou imperfeições visíveis no produto, de forma que ele pareça impecável, como se estivesse perfeitamente passado. 
		* Não remova cabides; em vez disso, nivele as peças para que fiquem na pose padrão, sem distorções, preservando a textura e os detalhes originais do material. 
		
		2. **Ajustes de Cor e Luz:** 
		* Assegure que as cores do produto sejam o mais fiéis possível à realidade, evitando saturação excessiva ou desbotamento. 
		* Ajuste o brilho e o contraste para realçar as características do produto e torná-lo visualmente atraente. 
		* Equilibre a exposição para eliminar áreas superexpostas ('estouradas') e subexpostas (muito escuras). 
		* Adicione uma sombra de estúdio sutil e suave (em fundo branco puro) para conferir profundidade, mantendo a conformidade com os padrões de marketplace. 
		
		3. **Corte e Composição:** 
		* Recorte a imagem de modo que o produto ocupe aproximadamente 70-80% da área total do quadro. 
		* Mantenha a perspectiva e as proporções originais do produto. 
		
		4. **Fundo Branco Profissional:** 
		* Crie um fundo perfeitamente branco (RGB: 255, 255, 255 | Hex: #FFFFFF). 
		* Elimine completamente quaisquer sombras residuais, reflexos indesejados ou vestígios do fundo original no produto. 
		* As bordas do produto devem estar nítidas e claramente definidas contra o fundo branco. 
		
		5. **Resolução e Formato:** 
		* Salve a imagem em alta resolução (mínimo 150 DPI, idealmente 300 DPI). 
		* Exporte no formato mais comum para e-commerce (JPEG ou PNG), a menos que instruído de outra forma. 
		* O tamanho do arquivo deve ser otimizado para carregamento rápido em websites, minimizando a perda de qualidade. 
		
		**Diretrizes Adicionais:** 
		* Preserve a identidade visual e as características únicas do produto. 
		* O resultado final deve apresentar um visual limpo, profissional e altamente atraente para potenciais compradores. 
		* Em caso de dúvida sobre a cor original ou algum detalhe específico, priorize a máxima fidelidade ao original. 
		
		**Entregável:** 
		Uma imagem de produto editada profissionalmente, com fundo branco puro e todos os retoques aplicados, pronta para publicação em plataformas de e-commerce.";
	}

}