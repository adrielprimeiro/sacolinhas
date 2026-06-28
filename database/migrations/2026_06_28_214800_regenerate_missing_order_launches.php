<?php

use App\Models\Pedido;
use App\Observers\PedidoObserver;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Encontrar todos os pedidos que não estão cancelados, possuem valor positivo e não têm lançamento associado
        $pedidos = Pedido::where('status_pedido', '!=', 'cancelado')
            ->where('valor_total', '>', 0)
            ->whereDoesntHave('lancamento')
            ->get();

        $observer = new PedidoObserver();
        
        foreach ($pedidos as $p) {
            try {
                $observer->saved($p);
            } catch (\Throwable $e) {
                // Silenciar erros de registros específicos para não travar a migração
                Log::error("Erro ao regenerar lançamento para pedido {$p->id} na migração: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Sem operação de reversão crítica necessária
    }
};
