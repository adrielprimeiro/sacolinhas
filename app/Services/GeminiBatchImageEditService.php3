<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiBatchImageEditService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    private string $uploadUrl = 'https://generativelanguage.googleapis.com/upload/v1beta/files';

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        // O modelo recomendado para edição de imagens em lote
        $this->model = (string) config('services.gemini.model', 'gemini-3-pro-image-preview'); 
    }

    /**
     * PASSO 1: Inicia o processo em lote para várias imagens.
     */
    public function startBatchJob(array $imagePaths, string $prompt = null): array
    {
        try {
            $prompt = $prompt ?: $this->getDefaultPrompt();
            
            $jsonlPath = $this->createJsonlFile($imagePaths, $prompt);
            $uploadedFile = $this->uploadFile($jsonlPath);
            $jobName = $this->createBatch($uploadedFile['name']);
            
            Storage::disk('local')->delete($jsonlPath);
            
            return ['success' => true, 'job_name' => $jobName];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * PASSO 2: Verifica o status do Job. Se concluído, baixa e salva as imagens.
     */
	public function checkAndProcessResults(string $jobName): array
    {
        try {
            $statusUrl = "{$this->baseUrl}/{$jobName}?key={$this->apiKey}";
            $response = Http::get($statusUrl);
            
            if (!$response->successful()) {
                throw new Exception('Erro ao verificar status: ' . $response->body());
            }
            
            $data = $response->json();
            
            // Lendo o status do local correto retornado pelo Google
            $rawState = $data['metadata']['state'] ?? 'UNKNOWN';
            
            // Adaptando para o padrão que o nosso Command já espera
            $state = $rawState;
            if ($rawState === 'BATCH_STATE_SUCCEEDED') $state = 'JOB_STATE_SUCCEEDED';
            if ($rawState === 'BATCH_STATE_FAILED') $state = 'JOB_STATE_FAILED';
            
            if ($state === 'JOB_STATE_SUCCEEDED') {
                // Pegando o nome do arquivo com as imagens prontas
                $resultFile = $data['response']['responsesFile'] ?? null;
                
                if (!$resultFile) {
                    throw new Exception('Arquivo de resultado não encontrado no job.');
                }
                
                $savedImages = $this->downloadAndSaveResults($resultFile);
                return ['success' => true, 'state' => $state, 'images' => $savedImages];
            }
            
            return ['success' => true, 'state' => $state];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }





    private function createJsonlFile(array $imagePaths, string $prompt): string
    {
        $jsonlContent = '';
        
        foreach ($imagePaths as $index => $imagePath) {
            $fullPath = Storage::disk('public')->path($imagePath);
            if (!is_file($fullPath)) continue;
            
            $imageBinary = file_get_contents($fullPath);
            $base64Image = base64_encode($imageBinary);
            $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
            
            $request = [
                'request' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $jsonlContent .= json_encode($request) . "\n";
        }

        if (empty($jsonlContent)) {
            throw new Exception("Nenhuma imagem válida encontrada nos caminhos fornecidos. Verifique se os arquivos existem no storage.");
        }
        
        $fileName = 'temp_batch_' . time() . '_' . bin2hex(random_bytes(4)) . '.jsonl';
        Storage::disk('local')->put($fileName, $jsonlContent);
        
        return $fileName;
    }

    private function uploadFile(string $jsonlPath): array
    {
        $fullPath = Storage::disk('local')->path($jsonlPath);
        $mimeType = 'application/jsonl';
        $fileSize = filesize($fullPath);
        
        // 1. PASSO 1: Iniciar a sessão de upload (Avisar o Google que vamos mandar um arquivo grande)
        $initUrl = "{$this->uploadUrl}?uploadType=resumable&key={$this->apiKey}";
        
        $initResponse = Http::withHeaders([
            'X-Goog-Upload-Protocol' => 'resumable',
            'X-Goog-Upload-Command' => 'start',
            'X-Goog-Upload-Header-Content-Length' => $fileSize,
            'X-Goog-Upload-Header-Content-Type' => $mimeType,
            'Content-Type' => 'application/json',
        ])->post($initUrl, [
            'file' => [
                'display_name' => 'batch_upload_' . time()
            ]
        ]);
        
        if (!$initResponse->successful()) {
            throw new Exception('Falha ao iniciar upload no Google: ' . $initResponse->body());
        }
        
        // O Google nos devolve uma URL única e temporária só para este upload
        $uploadUrl = $initResponse->header('X-Goog-Upload-URL');
        
        if (!$uploadUrl) {
            throw new Exception('URL de upload não retornada pelo Google.');
        }
        
        // 2. PASSO 2: Enviar os bytes do arquivo de fato para a URL temporária
        // Colocamos timeout de 600 segundos (10 minutos) para garantir que arquivos grandes subam tranquilos
        $uploadResponse = Http::timeout(600)->withHeaders([
            'Content-Length' => $fileSize,
            'X-Goog-Upload-Offset' => '0',
            'X-Goog-Upload-Command' => 'upload, finalize',
        ])->withBody(file_get_contents($fullPath), $mimeType)
        ->post($uploadUrl);
        
        if (!$uploadResponse->successful()) {
            throw new Exception('Falha ao enviar os bytes do arquivo: ' . $uploadResponse->body());
        }
        
        return $uploadResponse->json()['file'];
    }

    private function createBatch(string $fileUri): string
    {
        $url = "{$this->baseUrl}/models/{$this->model}:batchGenerateContent?key={$this->apiKey}";
        
        $payload = [
            'batch' => [
                'display_name' => 'batch-image-edit-' . time(),
                'input_config' => [
                    'file_name' => $fileUri
                ]
            ]
        ];
        
        $response = Http::post($url, $payload);
        
        if (!$response->successful()) {
            throw new Exception('Falha na criação do job em lote: ' . $response->body());
        }
        
        return $response->json()['name'];
    }

    private function downloadAndSaveResults(string $resultFileName): array
    {
        // O resultFileName já vem no formato "files/nome-do-arquivo"

		$downloadUrl = "https://generativelanguage.googleapis.com/v1/{$resultFileName}?alt=media&key={$this->apiKey}";
        
        $response = Http::timeout(120)->get($downloadUrl);
        
        if (!$response->successful()) {
            throw new Exception('Falha ao baixar resultados: ' . $response->body());
        }
        
        $fileContent = $response->body();
        $lines = explode("\n", trim($fileContent));
        $savedPaths = [];
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            $parsed = json_decode($line, true);
            $parts = $parsed['response']['candidates'][0]['content']['parts'] ?? [];
            
            foreach ($parts as $part) {
                $inlineData = $part['inlineData'] ?? $part['inline_data'] ?? null;
                
                if ($inlineData && isset($inlineData['data'])) {
                    $savedPaths[] = $this->saveEditedImageAsPng($inlineData['data']);
                }
            }
        }
        
        return $savedPaths;
    }

    private function saveEditedImageAsPng(string $base64Image): string
    {
        $newFilename = "batch_edited_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . ".png";
        
        // Salva na pasta uploads para manter o padrão do seu sistema
        $path = "uploads/" . $newFilename;
        Storage::disk('public')->put($path, base64_decode($base64Image));
        
        return $path;
    }

    private function getDefaultPrompt(): string
    {
        return "Aja como um editor de imagens profissional especializado em e-commerce, com o objetivo principal de preparar a imagem fornecida para uso em e-commerce. **Instruções Detalhadas:** 1. **Limpeza e Retoque:** * Remova quaisquer dobras, amassados ou imperfeições visíveis no produto, de forma que ele pareça impecável, como se estivesse perfeitamente passado. * Não remova cabides; em vez disso, nivele as peças para que fiquem na pose padrão, sem distorções, preservando a textura e os detalhes originais do material. 2. **Ajustes de Cor e Luz:** * Assegure que as cores do produto sejam o mais fiéis possível à realidade, evitando saturação excessiva ou desbotamento. * Ajuste o brilho e o contraste para realçar as características do produto e torná-lo visualmente atraente. * Equilibre a exposição para eliminar áreas superexpostas ('estouradas') e subexpostas (muito escuras). * Adicione uma sombra de estúdio sutil e suave (em fundo branco puro) para conferir profundidade, mantendo a conformidade com os padrões de marketplace. 3. **Corte e Composição:** * Recorte a imagem de modo que o produto ocupe aproximadamente 70-80% da área total do quadro. * Mantenha a perspectiva e as proporções originais do produto. 4. **Fundo Branco Profissional:** * Crie um fundo perfeitamente branco (RGB: 255, 255, 255 | Hex: #FFFFFF). * Elimine completamente quaisquer sombras residuais, reflexos indesejados ou vestígios do fundo original no produto. * As bordas do produto devem estar nítidas e claramente definidas contra o fundo branco. 5. **Resolução e Formato:** * Salve a imagem em alta resolução (mínimo 150 DPI, idealmente 300 DPI). * Exporte no formato mais comum para e-commerce (JPEG ou PNG), a menos que instruído de outra forma. * O tamanho do arquivo deve ser otimizado para carregamento rápido em websites, minimizando a perda de qualidade. **Diretrizes Adicionais:** * Preserve a identidade visual e as características únicas do produto. * O resultado final deve apresentar um visual limpo, profissional e altamente atraente para potenciais compradores. * Em caso de dúvida sobre a cor original ou algum detalhe específico, priorize a máxima fidelidade ao original. **Entregável:** Uma imagem de produto editada profissionalmente, com fundo branco puro e todos os retoques aplicados, pronta para publicação em plataformas de e-commerce.";
    }
}