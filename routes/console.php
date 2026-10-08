<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Domains\Clube\Console\Commands\RecalcularIndicadoresClubeCommand;
use App\Jobs\PollGeminiBatchStatusJob;
use Illuminate\Support\Facades\Schedule;

// Verifica os jobs a cada 5 minutos
// Schedule::command('gemini:check-batches')->everyFiveMinutes();

// Schedule::job(new PollGeminiBatchStatusJob())->everyMinute();

// Processamento automático de cortes e vídeos do Instagram após o término da live (a cada 5 min)
Schedule::command('app:auto-process-live-video --username=de_minha_mania')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// REGISTRO DO COMANDO DO CLUBE
Artisan::command('clube:recalcular-indicadores {--user-id= : Recalcular apenas um usuário}', function () {
    $userId = $this->option('user-id');
    
    if ($userId) {
        $this->info("Recalculando indicadores para usuário {$userId}...");
        app(\App\Domains\Clube\Services\ClubeIndicadoresService::class)->recalcularParaUsuario($userId);
        $this->info("✅ Concluído.");
    } else {
        $this->info("Recalculando indicadores para todos os clientes...");
        app(\App\Domains\Clube\Services\ClubeIndicadoresService::class)->recalcularParaTodos();
        $this->info("✅ Concluído.");
    }
})->purpose('Recalcula indicadores do clube para clientes');

Artisan::command('ai:group-orphans {--limit=30} {--model=models/gemini-2.5-flash} {--min=2} {--max=6} {--dry-run}', function () {
    $this->call(\App\Console\Commands\AiGroupOrphans::class, [
        '--limit' => $this->option('limit'),
        '--model' => $this->option('model'),
        '--min' => $this->option('min'),
        '--max' => $this->option('max'),
        '--dry-run' => $this->option('dry-run'),
    ]);
})->purpose('Agrupa imagens órfãs com IA (lote único) e grava group_id');

Artisan::command('live:transfer-video {from_id} {to_id}', function () {
    $fromId = $this->argument('from_id');
    $toId = $this->argument('to_id');
    $fromLive = \App\Models\Live::findOrFail($fromId);
    $toLive = \App\Models\Live::findOrFail($toId);

    $this->info("Transferindo gravação da Live #{$fromId} para Live #{$toId}...");
    $toLive->recording_path = $fromLive->recording_path;
    $toLive->transcription_raw = $fromLive->transcription_raw;
    $toLive->transcription_status = $fromLive->transcription_status;
    $toLive->save();

    // Limpa a live de origem
    $fromLive->recording_path = null;
    $fromLive->transcription_raw = null;
    $fromLive->transcription_status = null;
    $fromLive->save();

    $this->info("✅ Gravação transferida com sucesso para Live #{$toId}!");
})->purpose('Transfere gravação e transcrição de uma live para outra');

