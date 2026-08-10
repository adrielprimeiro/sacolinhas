<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CancelarPedidosPortalExpiradosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal:cancelar-expirados {--hours=24 : Número de horas limite para pagamento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancela pedidos abertos pelo portal/site não pagos após 24h e retorna os itens para a sacolinha do cliente.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $limitDate = Carbon::now()->subHours($hours);

        $this->info("Buscando pedidos do portal/site criados até {$limitDate->toDateTimeString()} ({$hours}h) pendentes de pagamento...");

        $pedidosExpirados = Pedido::whereIn('origem_pedido', ['portal', 'site'])
            ->where('status_pagamento', '!=', 'aprovado')
            ->whereNotIn('status_pedido', ['cancelado', 'pago', 'entregue', 'concluido'])
            ->where(function ($q) use ($limitDate) {
                $q->where('data_pedido', '<=', $limitDate)
                  ->orWhere(function ($q2) use ($limitDate) {
                      $q2->whereNull('data_pedido')
                        ->where('created_at', '<=', $limitDate);
                  });
            })
            ->get();

        if ($pedidosExpirados->isEmpty()) {
            $this->info("Nenhum pedido expirado encontrado.");
            return 0;
        }

        $this->info("Encontrados {$pedidosExpirados->count()} pedidos expirados para cancelar.");

        $countCancelados = 0;
        $totalItensDevolvidos = 0;

        foreach ($pedidosExpirados as $pedido) {
            DB::transaction(function () use ($pedido, &$countCancelados, &$totalItensDevolvidos) {
                $itensPedido = DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->get();

                $itemIds = [];

                foreach ($itensPedido as $ip) {
                    $itemIds[] = $ip->item_id;

                    // Tenta encontrar registro da sacolinha vinculado
                    $sacola = DB::table('sacolinhas')
                        ->where('user_id', $pedido->user_id)
                        ->where('item_id', $ip->item_id)
                        ->orderByDesc('id')
                        ->first();

                    if ($sacola) {
                        DB::table('sacolinhas')
                            ->where('id', $sacola->id)
                            ->update([
                                'status'     => 'sacolinha',
                                'obs'        => null,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('sacolinhas')->insert([
                            'user_id'    => $pedido->user_id,
                            'item_id'    => $ip->item_id,
                            'quantity'   => $ip->quantidade ?? 1,
                            'price'      => $ip->preco_unitario,
                            'live_id'    => 1,
                            'status'     => 'sacolinha',
                            'add_at'     => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $totalItensDevolvidos++;
                }

                if (!empty($itemIds)) {
                    // Atualiza status do produto para 'sacolinha'
                    Item::whereIn('id', $itemIds)->update([
                        'status'     => 'sacolinha',
                        'updated_at' => now(),
                    ]);
                }

                // Atualiza status dos itens no pedido
                DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->update([
                        'status_item' => 'cancelado',
                        'updated_at'  => now(),
                    ]);

                // Atualiza o pedido para cancelado (dispara o PedidoObserver para limpar débitos/lançamentos e recalcular carteira)
                $pedido->status_pedido   = 'cancelado';
                $pedido->status_pagamento = 'rejeitado';
                $pedido->save();

                $countCancelados++;
                $this->line("Pedido #{$pedido->id} ({$pedido->numero_pedido}) do Usuário #{$pedido->user_id} cancelado com sucesso. " . count($itemIds) . " item(ns) devolvido(s) para a sacolinha.");
                Log::info("PortalCancelamento24h: Pedido #{$pedido->id} cancelado por falta de pagamento em 24h. " . count($itemIds) . " itens retornados para a sacolinha.");
            });
        }

        $this->info("Concluído! {$countCancelados} pedidos cancelados e {$totalItensDevolvidos} itens devolvidos às sacolinhas.");

        return 0;
    }
}
