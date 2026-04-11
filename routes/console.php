<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Domains\Clube\Console\Commands\RecalcularIndicadoresClubeCommand;
use App\Jobs\PollGeminiBatchStatusJob;
use Illuminate\Support\Facades\Schedule;

// Verifica os jobs a cada 5 minutos
Schedule::command('gemini:check-batches')->everyFiveMinutes();

//Schedule::job(new PollGeminiBatchStatusJob())->everyMinute();

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

