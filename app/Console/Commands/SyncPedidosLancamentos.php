<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Pessoa;
use Illuminate\Support\Facades\Log;

class SyncPedidosLancamentos extends Command
{
    protected $signature = 'financeiro:sync-pedidos';
    protected $description = 'Gera lançamentos financeiros para pedidos que ainda não possuem';

    public function handle()
    {
        $pedidos = Pedido::all();
        $newCount = 0;
        $updatedCount = 0;

        $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'Venda na Live')
            ->orWhere('nome', 'Venda Live')
            ->first();
        $classificacaoId = $classificacao ? $classificacao->id : 2; // Fallback para 2

        $this->info("Usando classificação ID {$classificacaoId} ('" . ($classificacao ? $classificacao->nome : 'Venda Live') . "') para os pedidos.");

        foreach ($pedidos as $pedido) {
            if ($pedido->valor_total <= 0) continue;

            $user = $pedido->user;
            if (!$user) {
                $this->warn("Pedido #{$pedido->numero_pedido} não possui usuário vinculado.");
                continue;
            }

            $pessoa = $user->perfilFinanceiro;
            if (!$pessoa) {
                $pessoa = Pessoa::create([
                    'user_id'   => $user->id,
                    'nome'      => $user->name,
                    'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                    'tipo'      => 'cliente_circular',
                ]);
            }

            $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);
            $valorBruto = (float) $pedido->valor_total;

            $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();

            if ($valorBruto > 0) {
                $dadosLancamento = [
                    'tipo'                        => 'receita',
                    'status'                      => $pedido->status_pagamento === 'aprovado' ? 'pago' : 'pendente',
                    'description'                 => "Pedido " . $pedido->numero_pedido,
                    'pessoa_id'                   => $pessoa->id,
                    'classificacao_financeira_id' => $classificacaoId,
                    'data_emissao'                => $pedido->data_pedido ?? $pedido->created_at,
                    'data_vencimento'             => $pedido->data_pedido ?? $pedido->created_at,
                    'valor_total'                 => $valorBruto,
                    'descricao'                   => "Pedido " . $pedido->numero_pedido,
                    'referencia_tipo'             => 'pedido',
                    'referencia_id'               => $pedido->id,
                ];

                if (!$lancamento) {
                    $this->info("Gerando lançamento para Pedido #{$pedido->numero_pedido} (Valor Bruto: R$ {$valorBruto})");
                    $lancamento = Lancamento::create($dadosLancamento);
                    $newCount++;
                } else {
                    $this->info("Atualizando lançamento do Pedido #{$pedido->numero_pedido} (Valor Bruto: R$ {$valorBruto})");
                    $lancamento->update($dadosLancamento);
                    
                    // Sincronizar cada movimentação para atualizar o livro razão (ContaCorrente)
                    foreach ($lancamento->movimentacoes as $mov) {
                        $mov->unsetRelation('lancamento');
                        $mov->sincronizarCarteira();
                    }
                    
                    $updatedCount++;
                }

                if ($saldoUtilizado > 0) {
                    $contaCarteira = \App\Models\ContaBancaria::where('nome', 'like', '%carteira%')->first();
                    $contaBancariaId = $contaCarteira ? $contaCarteira->id : 3;

                    \App\Models\Movimentacao::updateOrCreate(
                        [
                            'lancamento_id' => $lancamento->id,
                            'forma_pagamento' => 'saldo_carteira',
                        ],
                        [
                            'conta_bancaria_id' => $contaBancariaId,
                            'data_pagamento' => $pedido->data_pedido ?? $pedido->created_at,
                            'valor_pago' => $saldoUtilizado,
                        ]
                    );
                } else {
                    $lancamento->movimentacoes()->where('forma_pagamento', 'saldo_carteira')->delete();
                }
            } else {
                if ($lancamento) {
                    $this->info("Excluindo lançamento financeiro do Pedido #{$pedido->numero_pedido} (Sem valor)");
                    $lancamento->movimentacoes()->delete();
                    $lancamento->delete();
                    $updatedCount++;
                }
            }

            // Sincronizar Débito e Crédito da Compra na Conta Corrente do Cliente (Ledger)
            if ($pessoa->user_id) {
                if (!str_starts_with($pedido->numero_pedido, 'REC-')) {
                    $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);
                    $valorNetDebito = max(0.00, (float)$pedido->valor_total - $saldoUtilizado);

                    // Débito da compra pelo valor LÍQUIDO do pedido
                    \App\Models\ContaCorrente::updateOrCreate(
                        [
                            'referencia_tipo' => 'pedido',
                            'referencia_id' => $pedido->id,
                            'tipo_movimentacao' => 'debito',
                        ],
                        [
                            'user_id' => $pessoa->user_id,
                            'valor' => $valorNetDebito,
                            'descricao' => "Compra: Pedido {$pedido->numero_pedido}",
                            'classificacao_id' => $classificacaoId,
                            'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                        ]
                    );

                    // Se houver saldo utilizado, cria/atualiza o lançamento correspondente
                    if ($saldoUtilizado != 0) {
                        $tipoMovSaldo = $saldoUtilizado > 0 ? 'debito' : 'credito';
                        $valorMovSaldo = abs($saldoUtilizado);
                        $descMovSaldo = $saldoUtilizado > 0 
                            ? "Desconto Carteira: Pedido {$pedido->numero_pedido}" 
                            : "Ajuste Dívida Embutida: Pedido {$pedido->numero_pedido}";

                        \App\Models\ContaCorrente::updateOrCreate(
                            [
                                'referencia_tipo' => 'desconto',
                                'referencia_id' => $pedido->id,
                            ],
                            [
                                'user_id' => $pessoa->user_id,
                                'tipo_movimentacao' => $tipoMovSaldo,
                                'valor' => $valorMovSaldo,
                                'descricao' => $descMovSaldo,
                                'classificacao_id' => $classificacaoId,
                                'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                            ]
                        );
                    } else {
                        \App\Models\ContaCorrente::where('referencia_tipo', 'desconto')
                            ->where('referencia_id', $pedido->id)
                            ->delete();
                    }
                }
            }
        }

        $this->info("Sincronização concluída. {$newCount} novos lançamentos gerados, {$updatedCount} lançamentos existentes atualizados.");
    }
}
