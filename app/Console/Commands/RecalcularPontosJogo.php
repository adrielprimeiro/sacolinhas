<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecalcularPontosJogo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:recalculate-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula retroativamente os pontos do jogo para todos os clientes com base nos pedidos com pagamento aprovado';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Iniciando recalculo de pontos do jogo...");

        DB::transaction(function () {
            // 1. Zerar a coluna pontos_itens de todas as pontuações de clientes e grupos
            $this->info("Zerando pontos de itens existentes...");
            DB::table('pontuacoes_clientes')->update(['pontos_itens' => 0]);
            DB::table('pontuacoes_grupos')->update(['pontos_itens' => 0]);

            // 2. Marcar todos os pedidos sem pagamento aprovado como pontos_creditados = false
            DB::table('pedidos')
                ->where('status_pagamento', '!=', 'aprovado')
                ->update(['pontos_creditados' => false]);

            // 3. Buscar todos os pedidos com pagamento aprovado
            $pedidos = DB::table('pedidos')
                ->where('status_pagamento', 'aprovado')
                ->get();

            $this->info("Processando " . $pedidos->count() . " pedidos com pagamento aprovado...");

            $processedCount = 0;

            foreach ($pedidos as $pedido) {
                // Calcular pontos do pedido
                $valorItens = DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->where('status_item', 'ativo')
                    ->sum(DB::raw('preco_unitario * quantidade'));

                $pontos = ceil($valorItens / 10);

                if ($pontos > 0) {
                    $mesAno = Carbon::parse($pedido->data_pedido ?? $pedido->created_at)->format('Y-m');

                    // Incrementar pontos do cliente
                    DB::table('pontuacoes_clientes')->updateOrInsert(
                        ['user_id' => $pedido->user_id, 'mes_ano' => $mesAno],
                        ['pontos_itens' => DB::raw("COALESCE(pontos_itens, 0) + $pontos")]
                    );

                    // Incrementar pontos do grupo se o membro pertencer a um
                    $grupoId = DB::table('grupo_membros')
                        ->where('user_id', $pedido->user_id)
                        ->value('grupo_id');

                    if ($grupoId) {
                        DB::table('pontuacoes_grupos')->updateOrInsert(
                            ['grupo_id' => $grupoId, 'mes_ano' => $mesAno],
                            ['pontos_itens' => DB::raw("COALESCE(pontos_itens, 0) + $pontos")]
                        );
                    }

                    // Marcar pedido como creditado
                    DB::table('pedidos')
                        ->where('id', $pedido->id)
                        ->update(['pontos_creditados' => true]);

                    $processedCount++;
                }
            }

            $this->info("Recalculando totais de usuarios e grupos...");

            // 4. Rodar as procedures de atualização para todos os registros afetados
            $pontuacoes = DB::table('pontuacoes_clientes')->get();
            foreach ($pontuacoes as $p) {
                DB::unprepared("CALL atualizar_pontuacoes_user({$p->user_id}, '{$p->mes_ano}')");
            }

            $pontuacoesGrupos = DB::table('pontuacoes_grupos')->get();
            foreach ($pontuacoesGrupos as $pg) {
                DB::unprepared("CALL atualizar_pontuacoes_grupo({$pg->grupo_id}, '{$pg->mes_ano}')");
            }

            $this->info("Sucesso! {$processedCount} pedidos processados e pontuacoes recalibradas.");
        });

        return Command::SUCCESS;
    }
}
