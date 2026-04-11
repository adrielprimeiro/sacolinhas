<?php

namespace App\Services\ImageEditing;

use App\Models\ImageEditBatch;
use App\Models\ImageEditBatchItem;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeminiBatchImageEditService
{
    public function __construct(
        private GeminiBatchManifestBuilder $manifestBuilder,
        private GeminiBatchApiClient $apiClient,
    ) {
    }

    public function submitBatch(array $items, ?int $createdBy = null): array
    {
        if (empty($items)) {
            return [
                'success' => false,
                'error' => 'Nenhum item informado para submissão do lote.',
            ];
        }

        DB::beginTransaction();

        try {
            $batch = ImageEditBatch::create([
                'provider' => 'gemini',
                'mode' => 'batch',
                'status' => 'pending',
                'model' => (string) config('services.gemini.batch_model', 'gemini-2.0-flash'),
                'total_items' => count($items),
                'processed_items' => 0,
                'started_at' => now(),
                'created_by' => $createdBy,
            ]);

            $manifest = $this->manifestBuilder->build($items);

            if (!$manifest['success']) {
                DB::rollBack();

                return [
                    'success' => false,
                    'error' => $manifest['error'] ?? 'Falha ao gerar manifesto.',
                ];
            }

            $batch->update([
                'manifest_path' => $manifest['manifest_relative_path'],
            ]);

            foreach ($manifest['items'] as $preparedItem) {
                ImageEditBatchItem::create([
                    'batch_id' => $batch->id,
                    'custom_id' => $preparedItem['custom_id'],
                    'original_path' => $preparedItem['image_path'],
                    'prompt' => $preparedItem['prompt'],
                    'mime_type' => $preparedItem['mime_type'],
                    'status' => 'pending',
                    'content_hash' => $preparedItem['content_hash'] ?? null,
                ]);
            }

            $upload = $this->apiClient->uploadBatchFile($manifest['manifest_full_path']);

            if (!$upload['success']) {
                $batch->update([
                    'status' => 'failed',
                    'error_message' => $upload['error'] ?? 'Falha no upload do manifesto.',
                    'finished_at' => now(),
                ]);

                DB::commit();

                Log::error('Gemini batch upload failed', [
                    'batch_id' => $batch->id,
                    'error' => $upload['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'batch_id' => $batch->id,
                    'error' => $upload['error'] ?? 'Falha no upload do manifesto.',
                ];
            }

            $batch->update([
                'provider_input_file_name' => $upload['file_name'],
            ]);

            $batchCreate = $this->apiClient->createBatchJob(
                $upload['file_name'],
                'image-edit-batch-' . $batch->id
            );

            if (!$batchCreate['success']) {
                $batch->update([
                    'status' => 'failed',
                    'error_message' => $batchCreate['error'] ?? 'Falha ao criar batch job.',
                    'finished_at' => now(),
                ]);

                DB::commit();

                Log::error('Gemini batch creation failed', [
                    'batch_id' => $batch->id,
                    'provider_input_file_name' => $batch->provider_input_file_name,
                    'error' => $batchCreate['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'batch_id' => $batch->id,
                    'error' => $batchCreate['error'] ?? 'Falha ao criar batch job.',
                ];
            }

            $batch->update([
                'batch_name' => $batchCreate['batch_name'],
                'status' => $this->mapProviderBatchStatus($batchCreate['state'] ?? null),
                'error_message' => null,
            ]);

            $batch->items()->update([
                'status' => 'submitted',
                'provider_status' => $batchCreate['state'] ?? 'submitted',
            ]);

            DB::commit();

            Log::info('Gemini batch submitted', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'total_items' => $batch->total_items,
                'model' => $batch->model,
            ]);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'total_items' => $batch->total_items,
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Gemini batch submit exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncBatchStatus(int $batchId): array
    {
        try {
            $batch = ImageEditBatch::find($batchId);

            if (!$batch) {
                return [
                    'success' => false,
                    'error' => 'Lote não encontrado.',
                ];
            }

            if (!$batch->batch_name) {
                return [
                    'success' => false,
                    'error' => 'O lote ainda não possui batch_name vinculado.',
                ];
            }

            if (in_array($batch->status, ['result_processing', 'partially_succeeded', 'failed', 'cancelled'], true)) {
                return [
                    'success' => true,
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'status' => $batch->status,
                    'provider_state' => null,
                    'provider_output_file_name' => $batch->provider_output_file_name,
                ];
            }

            $status = $this->apiClient->getBatchStatus($batch->batch_name);

            $batch->update([
                'last_polled_at' => now(),
            ]);

            if (!$status['success']) {
                $batch->update([
                    'error_message' => $status['error'] ?? 'Falha ao consultar status do lote.',
                ]);

                Log::warning('Gemini batch status sync failed', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'error' => $status['error'] ?? null,
                ]);

                return [
                    'success' => false,
                    'batch_id' => $batch->id,
                    'error' => $status['error'] ?? 'Falha ao consultar status do lote.',
                ];
            }

            $mappedStatus = $this->mapProviderBatchStatus($status['state'] ?? null);

            $updateData = [
                'status' => $mappedStatus,
                'error_message' => null,
            ];

            $outputFileName = $this->apiClient->extractOutputFileName($status['raw'] ?? []);
            if ($outputFileName) {
                $updateData['provider_output_file_name'] = $outputFileName;
            }

            if (in_array($mappedStatus, ['failed', 'cancelled'], true)) {
                $updateData['finished_at'] = now();
            }

            $batch->update($updateData);

            $batch->items()->whereIn('status', ['pending', 'submitted'])->update([
                'provider_status' => $status['state'] ?? null,
            ]);

            Log::info('Gemini batch status synced', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'provider_state' => $status['state'] ?? null,
                'provider_output_file_name' => $batch->provider_output_file_name,
            ]);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'provider_state' => $status['state'] ?? null,
                'provider_output_file_name' => $batch->provider_output_file_name,
                'raw' => $status['raw'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Gemini batch status sync exception', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getBatchWithItems(int $batchId): array
    {
        try {
            $batch = ImageEditBatch::with('items')->find($batchId);

            if (!$batch) {
                return [
                    'success' => false,
                    'error' => 'Lote não encontrado.',
                ];
            }

            return [
                'success' => true,
                'batch' => $batch,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function mapProviderBatchStatus(?string $providerState): string
    {
        return match ($providerState) {
            'JOB_STATE_PENDING', 'JOB_STATE_QUEUED', 'JOB_STATE_UNSPECIFIED', null => 'submitted',
            'JOB_STATE_RUNNING' => 'running',
            'JOB_STATE_SUCCEEDED', 'SUCCEEDED' => 'succeeded',
            'JOB_STATE_CANCELLED', 'CANCELLED' => 'cancelled',
            'JOB_STATE_FAILED', 'FAILED' => 'failed',
            default => 'submitted',
        };
    }
}