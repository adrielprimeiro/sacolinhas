<?php

namespace App\Jobs;

use App\Services\Ai\SeverinoWhatsappAgentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSeverinoLiveClosingQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(public int $liveId)
    {
        $this->onQueue('whatsapp');
    }

    public function handle(SeverinoWhatsappAgentService $service): void
    {
        try {
            $service->processNextInClosingQueue($this->liveId);
        } catch (\Throwable $e) {
            Log::error('Erro no ProcessSeverinoLiveClosingQueueJob', [
                'live_id' => $this->liveId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
