<?php

namespace App\Observers;

use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Pessoa;
use Illuminate\Support\Facades\Log;

class PedidoObserver
{
    /**
     * Handle the Pedido "saved" event.
     */
    public function saved(Pedido $pedido): void
    {
        // O valor_total do pedido no banco já representa o valor bruto total (itens + frete - descontos)
        // recalculado pelo trigger após inserção dos itens_pedido.
        $valorBruto = (float) $pedido->valor_total_original;

        // valor_saldo_utilizado:
        //   > 0 → saldo positivo abatido do pedido (reduz valor a pagar)
        //   <= 0 → sem saldo envolvido (legacy negative debt is ignored here to prevent ledger/revenue inflation)
        $saldoUtilizado = max(0.00, (float) $pedido->valor_saldo_utilizado);

        // Valor líquido que entra no caixa da empresa (exclui o saldo de carteira usado)
        $valorLiquido = max(0.00, $valorBruto - $saldoUtilizado);

        // Valor a debitar na carteira do cliente (o que ele deve ao total)
        // Quando há dívida embutida, valor_total já inclui ela; usamos valor_total como débito.
        $valorDebitoCarteira = $valorBruto; // valor_total já é o bruto dos itens após trigger

        // Só gera registros se houver valor faturado
        if ($valorDebitoCarteira <= 0) {
            return;
        }

        try {
            $user = $pedido->user;
            if (!$user) return;

            // 1. Garantir que o usuário tenha um perfil financeiro (Pessoa)
            $pessoa = $user->perfilFinanceiro;
            if (!$pessoa) {
                $pessoa = Pessoa::create([
                    'user_id'   => $user->id,
                    'nome'      => $user->name,
                    'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                    'tipo'      => 'cliente_circular',
                ]);
            }

            // Buscar a classificação financeira com base na origem do pedido (Portal/Site -> Venda no Site, senão Venda na Live)
            if (in_array($pedido->origem_pedido, ['portal', 'site'])) {
                $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'Venda no Site')
                    ->orWhere('nome', 'Venda Site')
                    ->first();
                $classificacaoId = $classificacao ? $classificacao->id : 17; // Fallback para Venda no Site
            } else {
                $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'Venda na Live')
                    ->orWhere('nome', 'Venda Live')
                    ->first();
                $classificacaoId = $classificacao ? $classificacao->id : 15; // Fallback para Venda na Live
            }

            // 2. Criar ou atualizar o lançamento de receita com o valor bruto do pedido
            if ($valorBruto > 0) {
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();

                $dadosLancamento = [
                    'tipo'                        => 'receita',
                    'status'                      => $pedido->status_pagamento === 'aprovado' ? 'pago' : 'pendente',
                    'description'                 => "Pedido " . $pedido->numero_pedido,
                    'pessoa_id'                   => $pessoa->id,
                    'classificacao_financeira_id' => $classificacaoId,
                    'data_emissao'                => $pedido->data_pedido ?? $pedido->created_at,
                    'data_vencimento'             => $pedido->data_pedido ?? $pedido->created_at,
                    'valor_total'                 => $valorBruto, // Valor bruto do pedido
                    'descricao'                   => "Pedido " . $pedido->numero_pedido,
                    'referencia_tipo'             => 'pedido',
                    'referencia_id'               => $pedido->id,
                ];

                if ($lancamento) {
                    $lancamento->update($dadosLancamento);
                } else {
                    $lancamento = Lancamento::create($dadosLancamento);
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
                    \App\Models\Movimentacao::where('lancamento_id', $lancamento->id)
                        ->where('forma_pagamento', 'saldo_carteira')
                        ->delete();
                }
            } else {
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();
                if ($lancamento) {
                    $lancamento->movimentacoes()->delete();
                    $lancamento->delete();
                }
            }

            // Sincronizar Débito e Crédito da Compra na Conta Corrente do Cliente (Ledger)
            if ($pessoa->user_id) {
                if (!str_starts_with($pedido->numero_pedido, 'REC-')) {
                    $valorNetDebito = max(0.00, $valorBruto - $saldoUtilizado);

                    // 1. Débito da compra pelo valor LÍQUIDO do pedido (apenas se for maior que 0)
                    if ($valorNetDebito > 0) {
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
                    } else {
                        \App\Models\ContaCorrente::where('referencia_tipo', 'pedido')
                            ->where('referencia_id', $pedido->id)
                            ->where('tipo_movimentacao', 'debito')
                            ->delete();
                    }

                    // 2. Se houver saldo utilizado (desconto), cria/atualiza o lançamento correspondente
                    if ($saldoUtilizado > 0) {
                        \App\Models\ContaCorrente::updateOrCreate(
                            [
                                'referencia_tipo' => 'desconto',
                                'referencia_id' => $pedido->id,
                            ],
                            [
                                'user_id' => $pessoa->user_id,
                                'tipo_movimentacao' => 'debito',
                                'valor' => $saldoUtilizado,
                                'descricao' => "Desconto Carteira: Pedido {$pedido->numero_pedido}",
                                'classificacao_id' => $classificacaoId,
                                'data_movimentacao' => $pedido->data_pedido ?? $pedido->created_at,
                            ]
                        );
                    } else {
                        \App\Models\ContaCorrente::where('referencia_tipo', 'desconto')
                            ->where('referencia_id', $pedido->id)
                            ->delete();
                    }

                    // Limpar qualquer registro antigo de crédito de uso de saldo para este pedido na ContaCorrente (preservando devoluções!)
                    \App\Models\ContaCorrente::where('referencia_tipo', 'pedido')
                        ->where('referencia_id', $pedido->id)
                        ->where('tipo_movimentacao', 'credito')
                        ->where('classificacao_id', '!=', 81)
                        ->where('descricao', 'not like', '%devolu%')
                        ->delete();

                    // Disparar recálculo de saldo da carteira da cliente
                    if (class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
                        $dataParaRecalculo = $pedido->data_pedido ? \Carbon\Carbon::parse($pedido->data_pedido) : $pedido->created_at;
                        \App\Jobs\RecalcularSaldosJob::dispatch($pessoa->user_id, $dataParaRecalculo->toDateString());
                    }
                }
            }

            // 3. Sincronizar pontos do jogo de Melissa (1 ponto a cada R$10,00 nos itens do pedido)
            // Os pontos são creditados quando o pagamento do pedido estiver aprovado.
            $isPagamentoAprovado = $pedido->status_pagamento === 'aprovado';

            if ($isPagamentoAprovado && !$pedido->pontos_creditados) {
                // Calcular pontos a ganhar (contabiliza itens ativos e devolvidos)
                $valorItens = \DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->whereIn('status_item', ['ativo', 'devolvido'])
                    ->sum(\DB::raw('preco_unitario * quantidade'));

                $pontosGanhar = ceil($valorItens / 10);

                if ($pontosGanhar > 0) {
                    \App\Services\PontuacoesService::updateItemPoints($pedido->user_id, $pontosGanhar);
                    
                    // Atualiza a coluna diretamente no banco de dados para evitar re-gatilho do observer
                    \DB::table('pedidos')->where('id', $pedido->id)->update(['pontos_creditados' => true]);
                    $pedido->setAttribute('pontos_creditados', true);

                    Log::info("✅ Pontos do jogo creditados para o usuário {$pedido->user_id}: {$pontosGanhar} pontos para o Pedido #{$pedido->id} (R$ {$valorItens} em itens)");
                }
            } elseif (!$isPagamentoAprovado && $pedido->pontos_creditados) {
                // Se o pagamento deixou de ser aprovado, removemos os pontos (contabiliza itens ativos e devolvidos)
                $valorItens = \DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->whereIn('status_item', ['ativo', 'devolvido'])
                    ->sum(\DB::raw('preco_unitario * quantidade'));

                $pontosDeduzir = ceil($valorItens / 10);

                if ($pontosDeduzir > 0) {
                    \App\Services\PontuacoesService::updateItemPoints($pedido->user_id, -$pontosDeduzir);
                    
                    \DB::table('pedidos')->where('id', $pedido->id)->update(['pontos_creditados' => false]);
                    $pedido->setAttribute('pontos_creditados', false);

                    Log::info("⚠️ Pontos do jogo removidos para o usuário {$pedido->user_id}: -{$pontosDeduzir} pontos pois o Pedido #{$pedido->id} mudou o status de pagamento para '{$pedido->status_pagamento}'");
                }
            }

        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao processar lançamento/carteira/pontos: " . $e->getMessage());
        }
    }

    /**
     * Handle the Pedido "deleting" event.
     */
    public function deleting(Pedido $pedido): void
    {
        try {
            // Remover pontos do jogo se o pedido deletado tinha pontos creditados
            if ($pedido->pontos_creditados) {
                // Contabiliza itens ativos e devolvidos ao remover pontos por exclusão do pedido
                $valorItens = \DB::table('items_pedido')
                    ->where('pedido_id', $pedido->id)
                    ->whereIn('status_item', ['ativo', 'devolvido'])
                    ->sum(\DB::raw('preco_unitario * quantidade'));

                $pontosDeduzir = ceil($valorItens / 10);

                if ($pontosDeduzir > 0) {
                    \App\Services\PontuacoesService::updateItemPoints($pedido->user_id, -$pontosDeduzir);
                    Log::info("⚠️ Pontos do jogo removidos para o usuário {$pedido->user_id}: -{$pontosDeduzir} pontos porque o Pedido #{$pedido->id} está sendo excluído");
                }
            }
        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao processar deleting: " . $e->getMessage());
        }
    }

    /**
     * Handle the Pedido "deleted" event.
     */
    public function deleted(Pedido $pedido): void
    {
        try {
            $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();
            if ($lancamento) {
                $lancamento->movimentacoes()->delete();
                $lancamento->delete();
            }

            \App\Models\ContaCorrente::whereIn('referencia_tipo', ['pedido', 'desconto', 'tolerancia'])
                ->where('referencia_id', $pedido->id)
                ->delete();

            if ($pedido->user_id && class_exists(\App\Jobs\RecalcularSaldosJob::class)) {
                $dataParaRecalculo = $pedido->data_pedido ? \Carbon\Carbon::parse($pedido->data_pedido) : $pedido->created_at;
                \App\Jobs\RecalcularSaldosJob::dispatch($pedido->user_id, $dataParaRecalculo->toDateString());
            }
        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao deletar registros: " . $e->getMessage());
        }
    }
}
