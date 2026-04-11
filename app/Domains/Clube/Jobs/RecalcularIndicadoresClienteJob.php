<?php
namespace App\Domains\Clube\Jobs;

use App\Domains\Clube\Services\ClubeIndicadoresService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalcularIndicadoresClienteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $userId
    ) {}

    public function handle(ClubeIndicadoresService $service): void
    {
        $service->recalcularParaUsuario($this->userId);
    }
}