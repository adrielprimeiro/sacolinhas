<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use App\Http\Controllers\Admin\LiveVideoCutsController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AutoDetectLiveCutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-detect-live-cuts {--live_id= : ID da live a minutar} {--start_code= : Código inicial opcional} {--only_unreviewed=0 : Se deve processar apenas não revisados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa a detecção inteligente de minutagens dos cortes da live em segundo plano com Severino IA.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $liveId = (int) $this->option('live_id');
        $startCode = $this->option('start_code') ? (int) $this->option('start_code') : null;
        $onlyUnreviewed = (bool) $this->option('only_unreviewed');

        $live = Live::find($liveId);
        if (!$live) {
            $this->error("Live #{$liveId} não encontrada.");
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'error',
                'progress' => 0,
                'message' => 'Live não encontrada no banco de dados.'
            ], 3600);
            return Command::FAILURE;
        }

        $this->info("Iniciando minutagem inteligente com Severino IA para Live #{$liveId}...");

        Cache::put("live_transcription_status_{$liveId}", [
            'status' => 'processing',
            'progress' => 10,
            'message' => 'Severino IA preparando a transcrição e catálogo de peças...'
        ], 3600);

        try {
            $controller = new LiveVideoCutsController();
            $progressCallback = function ($pct, $msg) use ($liveId) {
                Cache::put("live_transcription_status_{$liveId}", [
                    'status' => 'processing',
                    'progress' => $pct,
                    'message' => $msg
                ], 3600);
            };

            $results = $controller->performAutoDetection($live, $startCode, $onlyUnreviewed, $progressCallback);

            $count = count($results);
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => "Minutagem concluída com sucesso! ({$count} peças minutadas com IA)",
                'items_count' => $count
            ], 3600);

            $this->info("✅ Minutagem concluída com sucesso: {$count} peças processadas.");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            Log::error("[AutoDetectCommand] Erro na minutagem da Live #{$liveId}: " . $e->getMessage(), [
                'exception' => $e
            ]);
            Cache::put("live_transcription_status_{$liveId}", [
                'status' => 'error',
                'progress' => 0,
                'message' => 'Erro durante a minutagem com IA: ' . $e->getMessage()
            ], 3600);
            return Command::FAILURE;
        }
    }
}
