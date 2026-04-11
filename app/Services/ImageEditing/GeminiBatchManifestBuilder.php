<?php

namespace App\Services\ImageEditing;

use Exception;
use Illuminate\Support\Facades\Storage;

class GeminiBatchManifestBuilder
{
    private string $model;

    public function __construct()
    {
        $this->model = (string) config('services.gemini.batch_model', 'gemini-2.0-flash');
    }

    public function build(array $items): array
    {
        if (empty($items)) {
            return ['success' => false, 'error' => 'Nenhum item informado.'];
        }

        try {
            $lines = [];
            $preparedItems = [];

            foreach ($items as $index => $item) {
                $prepared = $this->prepareItem($item, $index);
                $preparedItems[] = $prepared;

                // Formato OpenAI Chat Completions exigido pelo Gemini Batch
                $lines[] = json_encode([
                    'custom_id' => $prepared['custom_id'],
                    'method' => 'POST',
                    'url' => '/v1/chat/completions',
                    'body' => [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    ['type' => 'text', 'text' => $prepared['prompt']],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => "data:{$prepared['mime_type']};base64,{$prepared['base64_image']}"
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $relativePath = 'gemini/batch/input/gemini_batch_' . now()->format('Ymd_His') . '.jsonl';
            Storage::disk('local')->put($relativePath, implode("\n", $lines));

            return [
                'success' => true,
                'manifest_full_path' => Storage::disk('local')->path($relativePath),
                'total_items' => count($preparedItems),
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function prepareItem(array $item, int $index): array
    {
        $fullPath = Storage::disk('public')->path($item['image_path']);
        if (!is_file($fullPath)) throw new Exception("Arquivo não encontrado: {$item['image_path']}");

        return [
            'custom_id' => $item['custom_id'] ?? 'img_' . $index,
            'prompt' => $item['prompt'] ?? 'Remova o fundo da imagem.',
            'mime_type' => mime_content_type($fullPath) ?: 'image/jpeg',
            'base64_image' => base64_encode(file_get_contents($fullPath)),
        ];
    }
}