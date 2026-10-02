<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LiveVideoAutoProcessorService;

class AutoProcessLiveVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:auto-process-live-video {--live_id= : ID da live a processar} {--username=de_minha_mania : @ do Instagram} {--url= : URL direta do vídeo da live (Instagram/TikTok/Reel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica novo vídeo publicado no Instagram, baixa, transcreve com IA e gera os cortes das peças automaticamente.';

    /**
     * Execute the console command.
     */
    public function handle(LiveVideoAutoProcessorService $processor)
    {
        $liveId = $this->option('live_id');
        $username = $this->option('username') ?: 'de_minha_mania';
        $directUrl = $this->option('url');

        $this->info("Iniciando processamento automático de vídeo da live...");
        $this->line("Live ID: " . ($liveId ?: 'Última ativa'));
        $this->line("Instagram: @{$username}");
        if ($directUrl) {
            $this->line("URL Direta: {$directUrl}");
        }

        $result = $processor->processLiveVideo($liveId, $username, $directUrl, function($pct, $msg) {
            $this->line("[{$pct}%] {$msg}");
        });

        if (!empty($result['success'])) {
            $this->info("✅ " . $result['message']);
            return Command::SUCCESS;
        }

        $this->warn("⚠️ " . ($result['message'] ?? 'Nenhum vídeo processado.'));
        return Command::FAILURE;
    }
}
