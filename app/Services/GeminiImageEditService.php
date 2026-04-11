<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Exception;

class GeminiImageEditService
{
    public function __construct()
    {
        // Construtor mantido para compatibilidade, mas instâncias de configuração da API foram removidas.
    }

    public function editImage(string $imagePath, string $prompt = null): array
    {
        try {
            $originalFullPath = Storage::disk('public')->path($imagePath);
            
            if (!is_file($originalFullPath)) {
                return ['success' => false, 'error' => 'Arquivo não encontrado.'];
            }

            // Define o novo caminho e nome do arquivo de saída (JPEG)
            $pathInfo = pathinfo($imagePath);
            $dir = $pathInfo['dirname'] ?? '';
            $filename = $pathInfo['filename'] ?? 'image';
            
            $newFilename = "{$filename}_edited_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . ".jpg";
            $newRelativePath = ($dir && $dir !== '.') ? "{$dir}/{$newFilename}" : $newFilename;
            $newFullPath = Storage::disk('public')->path($newRelativePath);

            // Garante que o diretório de destino existe na estrutura do Laravel
            $destinationDir = dirname($newFullPath);
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
            }

            // Caminho para o script Python
            $pythonScriptPath = base_path('scripts/image_processor.py');

            // Constrói o comando blindado contra exploração de shell escape
            $command = sprintf(
                'python %s %s %s 2>&1',
                escapeshellarg($pythonScriptPath),
                escapeshellarg($originalFullPath),
                escapeshellarg($newFullPath)
            );

            // Executa o comando e captura qualquer saída para debug
            $output = shell_exec($command);

            // Verifica de forma segura se a imagem nova foi de fato ejetada com sucesso pelo Python
            if (!is_file($newFullPath)) {
                return [
                    'success' => false, 
                    'error' => "Falha ao processar a imagem localmente. Saída do Python: " . ($output ?: 'Sem retorno.')
                ];
            }

            // Retorna sucesso e o novo caminho relativo como esperado pelo sistema original
            return ['success' => true, 'path' => $newRelativePath];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}