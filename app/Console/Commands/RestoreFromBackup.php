<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Jobs\RecalcularSaldosJob;

class RestoreFromBackup extends Command
{
    protected $signature = 'financeiro:restore-from-backup {--backup=backup_db_180626.sql : O arquivo de backup no root do projeto} {--dry-run : Apenas simula as alterações}';
    protected $description = 'Restaura a tabela conta_corrente do backup e reaplica transações e devoluções recentes';

    public function handle()
    {
        $backupFile = $this->option('backup');
        $dryRun = $this->option('dry-run');
        
        $backupPath = base_path($backupFile);
        if (!file_exists($backupPath)) {
            $this->error("Arquivo de backup não encontrado em: {$backupPath}");
            return 1;
        }

        $cutoffDate = '2026-06-18 13:23:03';
        $this->info("Iniciando processo de recuperação com data de corte: {$cutoffDate}");

        if ($dryRun) {
            $this->warn("[MODO SIMULAÇÃO] Nenhuma alteração será gravada no banco de dados.");
        }

        // 1. Capturar movimentações manuais e de ajuste criadas após a data de corte para não perdê-las
        $this->info("Capturando ajustes e movimentações manuais inseridas após a data de corte...");
        $manualEntries = DB::table('conta_corrente')
            ->where('created_at', '>=', $cutoffDate)
            ->where(function($q) {
                $q->whereNotIn('referencia_tipo', ['pedido', 'desconto', 'movimentacao'])
                  ->orWhereNull('referencia_tipo');
            })
            ->get();
            
        $this->info("Total de movimentações manuais/ajustes capturados pós-corte: " . $manualEntries->count());
        foreach ($manualEntries as $m) {
            $this->line(" - ID original: {$m->id} | User: {$m->user_id} | Val: R$ {$m->valor} | Desc: {$m->descricao} | Tipo: {$m->tipo_movimentacao}");
        }

        // 2. Extrair SQL da conta_corrente do backup
        $this->info("Extraindo SQL da tabela conta_corrente a partir do backup...");
        $file = fopen($backupPath, 'r');
        $capturing = false;
        $sqlCommands = [];
        $currentQuery = "";
        
        while (($line = fgets($file)) !== false) {
            if (str_contains($line, 'DROP TABLE IF EXISTS `conta_corrente`')) {
                $capturing = true;
            }
            
            if ($capturing) {
                $currentQuery .= $line;
                
                // Se a linha termina com um ponto e vírgula e uma quebra de linha, executa/salva a query
                if (preg_match('/;\s*$/', $line)) {
                    $sqlCommands[] = $currentQuery;
                    $currentQuery = "";
                }
                
                // Interrompe captura ao atingir a próxima tabela
                if (str_contains($line, 'DROP TABLE IF EXISTS') && !str_contains($line, '`conta_corrente`')) {
                    // Descarta a última query que pertence à próxima tabela
                    array_pop($sqlCommands);
                    break;
                }
            }
        }
        fclose($file);

        if (empty($sqlCommands)) {
            $this->error("Não foi possível encontrar a tabela conta_corrente no arquivo de backup.");
            return 1;
        }

        $this->info("SQL extraído com sucesso. Total de blocos de comando: " . count($sqlCommands));

        // 3. Restaurar a tabela no banco
        if (!$dryRun) {
            $this->info("Executando restauração da tabela conta_corrente...");
            // Desativar chaves estrangeiras temporariamente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($sqlCommands as $cmd) {
                DB::statement($cmd);
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info("Tabela conta_corrente restaurada com sucesso para o estado de {$cutoffDate}!");
        } else {
            $this->info("Simulação: Tabela conta_corrente seria restaurada para o estado de {$cutoffDate}.");
        }

        // 4. Inserir de volta os registros manuais capturados
        $usersAffected = [];
        if ($manualEntries->isNotEmpty() && !$dryRun) {
            $this->info("Re-inserindo movimentações manuais pós-corte...");
            foreach ($manualEntries as $m) {
                $usersAffected[$m->user_id] = true;
                
                // Insere limpando o ID para gerar novo auto-increment
                $data = (array)$m;
                unset($data['id']);
                DB::table('conta_corrente')->insert($data);
            }
            $this->info("Movimentações manuais restauradas.");
        }

        // 5. Buscar e aplicar pedidos criados pós-corte
        $this->info("Verificando pedidos criados após a data de corte...");
        $pedidos = DB::table('pedidos')
            ->where('created_at', '>=', $cutoffDate)
            ->get();
            
        $this->info("Pedidos criados pós-corte encontrados: " . $pedidos->count());
        foreach ($pedidos as $pedido) {
            if ($pedido->valor_total <= 0) continue;
            $usersAffected[$pedido->user_id] = true;

            $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);
            
            // Calcula o valor original (antes de devoluções)
            $devolvidosVal = DB::table('items_pedido')
                ->where('pedido_id', $pedido->id)
                ->where('status_item', 'devolvido')
                ->sum('valor_total');
            $valorOriginal = (float)$pedido->valor_total + (float)$devolvidosVal;
            $valorNetDebito = max(0.00, $valorOriginal - $saldoUtilizado);

            $this->line(" -> Processando Pedido: {$pedido->numero_pedido} | User: {$pedido->user_id} | Bruto Original: R$ {$valorOriginal} | Net Débito: R$ {$valorNetDebito}");

            if (!$dryRun) {
                if ($valorNetDebito > 0) {
                    DB::table('conta_corrente')->updateOrInsert(
                        [
                            'referencia_tipo' => 'pedido',
                            'referencia_id' => $pedido->id,
                            'tipo_movimentacao' => 'debito'
                        ],
                        [
                            'user_id' => $pedido->user_id,
                            'valor' => $valorNetDebito,
                            'descricao' => "Compra: Pedido {$pedido->numero_pedido}",
                            'classificacao_id' => 1,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                            'created_at' => $pedido->created_at,
                            'updated_at' => $pedido->created_at,
                        ]
                    );
                }

                if ($saldoUtilizado > 0) {
                    DB::table('conta_corrente')->updateOrInsert(
                        [
                            'referencia_tipo' => 'desconto',
                            'referencia_id' => $pedido->id
                        ],
                        [
                            'user_id' => $pedido->user_id,
                            'tipo_movimentacao' => 'debito',
                            'valor' => $saldoUtilizado,
                            'descricao' => "Desconto Carteira: Pedido {$pedido->numero_pedido}",
                            'classificacao_id' => 1,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                            'created_at' => $pedido->created_at,
                            'updated_at' => $pedido->created_at,
                        ]
                    );
                }
            }
        }

        // 6. Buscar e aplicar pagamentos (movimentações) criados pós-corte
        $this->info("Verificando pagamentos recebidos pós-corte...");
        $movs = \App\Models\Movimentacao::where('created_at', '>=', $cutoffDate)->get();
        $this->info("Pagamentos encontrados: " . $movs->count());
        
        foreach ($movs as $mov) {
            $lancamento = $mov->lancamento;
            if (!$lancamento || !$lancamento->pessoa_id) continue;
            
            $pessoa = $lancamento->pessoa;
            if (!$pessoa->user_id) continue;
            
            $usersAffected[$pessoa->user_id] = true;
            $this->line(" -> Processando Pagamento: ID: {$mov->id} | User: {$pessoa->user_id} | Valor: R$ {$mov->valor_pago}");
            
            if (!$dryRun) {
                $mov->sincronizarCarteira();
            }
        }

        // 7. Buscar e aplicar devoluções registradas pós-corte
        $this->info("Verificando devoluções efetuadas pós-corte...");
        $devolvidos = DB::table('items_pedido')
            ->join('pedidos', 'items_pedido.pedido_id', '=', 'pedidos.id')
            ->where('items_pedido.status_item', 'devolvido')
            ->where('items_pedido.updated_at', '>=', $cutoffDate)
            ->select([
                'items_pedido.id as item_id',
                'items_pedido.pedido_id',
                'items_pedido.valor_total',
                'items_pedido.updated_at',
                'pedidos.user_id',
                'pedidos.numero_pedido',
                'pedidos.live_id'
            ])
            ->get();

        $groups = $devolvidos->groupBy(function ($item) {
            return $item->pedido_id . '_' . $item->updated_at;
        });

        $this->info("Total de devoluções pós-corte encontradas: " . $devolvidos->count() . " (em " . $groups->count() . " lotes)");
        
        foreach ($groups as $key => $items) {
            $first = $items->first();
            $pedidoId = $first->pedido_id;
            $userId = $first->user_id;
            $numeroPedido = $first->numero_pedido;
            $liveId = $first->live_id;
            $updatedAt = $first->updated_at;

            $valorTotalDevolvido = $items->sum(function ($i) {
                return (float) ($i->valor_total ?? 0);
            });
            $idsValidos = $items->pluck('item_id')->toArray();

            $usersAffected[$userId] = true;
            $this->line(" -> Processando Devolução: Pedido: {$numeroPedido} | User: {$userId} | Valor: R$ " . number_format($valorTotalDevolvido, 2));

            if (!$dryRun) {
                DB::table('conta_corrente')->updateOrInsert(
                    [
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $pedidoId,
                        'tipo_movimentacao' => 'credito',
                        'data_movimentacao' => $updatedAt,
                    ],
                    [
                        'user_id' => $userId,
                        'valor' => $valorTotalDevolvido,
                        'descricao' => 'Crédito por devolução de itens do pedido ' . ($numeroPedido ?? $pedidoId),
                        'live_id' => $liveId,
                        'saldo_anterior' => 0.00,
                        'saldo_atual' => 0.00,
                        'observacoes' => 'Itens devolvidos: ' . implode(',', $idsValidos),
                        'classificacao_id' => 81,
                        'created_at' => $updatedAt,
                        'updated_at' => $updatedAt,
                    ]
                );
            }
        }

        // 8. Recalcular saldos para todos os usuários afetados
        if (!empty($usersAffected) && !$dryRun) {
            $this->info("\nRecalculando saldos dos usuários afetados desde a data de corte...");
            $bar = $this->output->createProgressBar(count($usersAffected));
            foreach (array_keys($usersAffected) as $userId) {
                // Começamos o recálculo a partir de 2026-06-18 para acelerar o processo e manter o histórico intacto
                $job = new RecalcularSaldosJob($userId, '2026-06-18');
                $job->handle();
                $bar->advance();
            }
            $bar->finish();
            $this->info("\nSaldos recalculados com sucesso!");
        }

        $this->info("Recuperação concluída com sucesso!");
    }
}
