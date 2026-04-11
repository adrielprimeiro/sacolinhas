<?php

namespace App\Domains\Clube\Services;

use App\Domains\Clube\Repositories\ClubeIndicadoresRepository;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClubeIndicadoresService
{
    public function __construct(
        private ClubeIndicadoresRepository $repository
    ) {}

    public function recalcularParaTodos(): void
    {
        // Pega todos os clientes (role='client')
        $userIds = User::where('role', 'client')
            ->pluck('id')
            ->chunk(50); // Processa em lotes de 50
        
        foreach ($userIds as $chunk) {
            foreach ($chunk as $userId) {
                $this->recalcularParaUsuario($userId);
            }
        }
    }

    public function recalcularParaUsuario(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            // 1) Pedidos + taxa (6 meses)
            $this->repository->upsertPedidosETaxa($userId);
            
            // 2) Mensalidade status
            $this->repository->atualizarMensalidadeStatus($userId);
            
            // 3) Total mensalidades pagas
            $this->repository->atualizarMensalidadesTotal($userId);
            
            // 4) Sequência (streak) - regra B (em dia)
            $this->repository->atualizarMensalidadesSequencia($userId);
        });
    }
}