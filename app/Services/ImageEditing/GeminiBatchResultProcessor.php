<?php

namespace App\Services\ImageEditing;

use App\Models\ImageEditBatch;
use App\Models\ImageEditBatchItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiBatchResultProcessor
{
    public function __construct(
        private GeminiBatchApiClient $apiClient,
    ) {
    }

    public function process(int $batchId): array
    {
        try {
            /** @var ImageEditBatch|null $batch */
            $batch = ImageEditBatch::with('items')->find($batchId);

            if (!$batch) {
                return [
                    'success' => false,
                    'error' => 'Lote não encontrado.',
                ];
            }

            if (in_array($batch->status, ['partially_succeeded'], true)) {
                return [
                    'success' => true,
                    'batch_id' => $batch->id,
                    'status' => $batch->status,
                    'message' => 'Lote já processado anteriormente.',
                ];
            }

            if (!$batch->provider_output_file_name) {
                return [
                    'success' => false,
                    'error' => 'O lote não possui provider_output_file_name definido.',
                ];
            }

            $download = $this->apiClient->downloadBatchOutput($batch->provider_output_file_name);

            if (!$download['success']) {
                $batch->update([
                    'error_message' => $download['error'] ?? 'Falha ao baixar output do batch.',
                ]);

                Log::error('Gemini batch output download failed', [
                    'batch_id' => $batch->id,
                    'provider_output_file_name' => $batch->provider_output_file_name,
                    'error' => $download['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'batch_id' => $batch->id,
                    'error' => $download['error'] ?? 'Falha ao baixar output do batch.',
                ];
            }

            $results = $this->parseJsonlFile($download['output_full_path']);
            $itemsByCustomId = $batch->items->keyBy('custom_id');

            DB::beginTransaction();

            $batch->update([
                'local_output_file_path' => $download['output_relative_path'],
                'status' => 'result_processing',
                'error_message' => null,
            ]);

            foreach ($results as $row) {
                $this->processResultLine($batch, $itemsByCustomId->get($row['custom_id'] ?? $row['customId'] ?? ''), $row);
            }

            $processedItems = $batch->items()->where('status', 'processed')->count();
            $failedItems = $batch->items()->where('status', 'failed')->count();
            $totalItems = $batch->items()->count();

            $batchStatus = $this->resolveFinalBatchStatus(
                totalItems: $totalItems,
                processedItems: $processedItems,
                failedItems: $failedItems
            );

            $batch->update([
                'processed_items' => $processedItems,
                'status' => $batchStatus,
                'finished_at' => now(),
                'error_message' => $failedItems > 0 ? 'Alguns itens falharam no processamento do resultado.' : null,
            ]);

            DB::commit();

            Log::info('Gemini batch result processed', [
                'batch_id' => $batch->id,
                'status' => $batchStatus,
                'processed_items' => $processedItems,
                'failed_items' => $failedItems,
                'total_items' => $totalItems,
            ]);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'status' => $batchStatus,
                'processed_items' => $processedItems,
                'failed_items' => $failedItems,
                'total_items' => $totalItems,
                'local_output_file' => $download['output_relative_path'],
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Gemini batch result process exception', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function parseJsonlFile(string $fullPath): array
    {
        if (!is_file($fullPath)) {
            throw new Exception('Arquivo JSONL de saída não encontrado.');
        }

        $lines = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $results = [];

        foreach ($lines as $lineNumber => $line) {
            $decoded = json_decode($line, true);

            if (!is_array($decoded)) {
                throw new Exception('Linha inválida no JSONL de saída na posição ' . ($lineNumber + 1));
            }

            $results[] = $decoded;
        }

        return $results;
    }

    private function processResultLine(ImageEditBatch $batch, ?ImageEditBatchItem $item, array $row): void
    {
        $customId = $row['custom_id'] ?? $row['customId'] ?? null;

        if (!$customId) {
            Log::warning('Gemini batch result line without custom_id', [
                'batch_id' => $batch->id,
            ]);
            return;
        }

        if (!$item) {
            Log::warning('Gemini batch result item not found by custom_id', [
                'batch_id' => $batch->id,
                'custom_id' => $customId,
            ]);
            return;
        }

        if ($item->status === 'processed' && !empty($item->edited_path)) {
            return;
        }

        $responseBody = $row['response']['body'] ?? $row['body'] ?? [];
        $base64Image = $this->extractInlineImageBase64($responseBody);

        $item->increment('attempt_count');

        if (!$base64Image) {
            $item->update([
                'status' => 'failed',
                'provider_status' => 'no_image_returned',
                'provider_response' => $this->truncateJson($row),
                'error_message' => 'Nenhuma imagem encontrada no item retornado pelo batch.',
            ]);

            Log::warning('Gemini batch item failed - no image returned', [
                'batch_id' => $batch->id,
                'custom_id' => $customId,
            ]);

            return;
        }

        $editedPath = $this->saveBatchImageAsPng($item, $base64Image);

        $item->update([
            'edited_path' => $editedPath,
            'status' => 'processed',
            'provider_status' => 'image_extracted',
            'provider_response' => $this->truncateJson($row),
            'processed_at' => now(),
            'error_message' => null,
        ]);

        Log::info('Gemini batch item processed', [
            'batch_id' => $batch->id,
            'custom_id' => $customId,
            'edited_path' => $editedPath,
        ]);
    }

    private function extractInlineImageBase64(array $data): ?string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? [];

        foreach ($parts as $part) {
            if (isset($part['inline_data']['data'])) {
                return $part['inline_data']['data'];
            }

            if (isset($part['inlineData']['data'])) {
                return $part['inlineData']['data'];
            }
        }

        return null;
    }

    private function saveBatchImageAsPng(ImageEditBatchItem $item, string $base64Image): string
    {
        $decoded = base64_decode($base64Image, true);

        if ($decoded === false) {
            throw new Exception("Falha ao decodificar base64 do item {$item->custom_id}");
        }

        $filename = $this->generateEditedFilename($item);
        $relativePath = 'gemini/edited/' . $filename;

        Storage::disk('public')->put($relativePath, $decoded);

        return $relativePath;
    }

    private function generateEditedFilename(ImageEditBatchItem $item): string
    {
        $safeCustomId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->custom_id) ?: 'image';

        return $safeCustomId . '_edited_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
    }

    private function resolveFinalBatchStatus(int $totalItems, int $processedItems, int $failedItems): string
    {
        if ($totalItems === 0) {
            return 'failed';
        }

        if ($processedItems === $totalItems) {
            return 'succeeded';
        }

        if ($processedItems > 0 && $failedItems > 0) {
            return 'partially_succeeded';
        }

        if ($processedItems === 0 && $failedItems > 0) {
            return 'failed';
        }

        return 'result_processing';
    }

    private function truncateJson(array $row): string
    {
        $json = json_encode($row, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return '{}';
        }

        return mb_substr($json, 0, 65000);
    }
}