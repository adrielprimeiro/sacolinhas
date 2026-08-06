<?php

namespace App\Services;

use App\Models\ContaCorrente;
use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletAutoPayService
{
    protected LancamentoBaixaService $baixaService;

    public function __construct(LancamentoBaixaService $baixaService)
    {
        $this->baixaService = $baixaService;
    }

    /**
     * Processa o saldo da carteira do usuário para abater pedidos pendentes.
     *
     * @param int $userId
     * @return int Número de pedidos abatidos
     */
    public function process(int $userId): int
    {
        return DB::transaction(function () use ($userId) {
            // Pega o saldo final atual do usuário
            $ultimaMov = ContaCorrente::where('user_id', $userId)
                                      ->orderByDesc('data_movimentacao')
                                      ->orderByDesc('id')
                                      ->lockForUpdate() // Evita concorrência
                                      ->first();

            if (!$ultimaMov) {
                return 0;
            }

            $saldoDisponivelReal = $ultimaMov->saldo_atual;
            $pedidosAbatidos = 0;

            // Busca pedidos pendentes ou parciais do usuário (mais antigos primeiro)
            $pedidosPendentes = Pedido::where('user_id', $userId)
                                      ->whereIn('status_pagamento', ['pendente', 'parcial'])
                                      ->orderBy('created_at', 'asc')
                                      ->get();

            if ($pedidosPendentes->isEmpty()) {
                return 0;
            }

            // Calcula o total em lançamentos pendentes (receitas) desses pedidos
            $totalPendentes = 0;
            $totalPago = 0;
            
            $lancamentosProcessar = collect();

            foreach ($pedidosPendentes as $pedido) {
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                                        ->where('referencia_id', $pedido->id)
                                        ->where('tipo', 'receita')
                                        ->whereIn('status', ['pendente', 'pago_parcial'])
                                        ->first();
                if ($lancamento) {
                    $totalPendentes += $lancamento->valor_total;
                    $jaPago = $lancamento->movimentacoes()->sum('valor_pago');
                    $totalPago += $jaPago;
                    
                    $lancamentosProcessar->push([
                        'pedido' => $pedido,
                        'lancamento' => $lancamento,
                        'valor_restante' => max(0, $lancamento->valor_total - $jaPago)
                    ]);
                }
            }

            // A mágica matemática: Calcula o saldo virtual disponível apenas para os pedidos, 
            // abatendo automaticamente qualquer dívida paralela que a carteira possua.
            $saldoDisponivel = ($totalPendentes + $saldoDisponivelReal) - $totalPago;

            if ($saldoDisponivel <= 0.01) {
                return 0; // Não há saldo virtual suficiente para abater pedidos
            }

            foreach ($lancamentosProcessar as $item) {
                if ($saldoDisponivel <= 0.01) {
                    break;
                }

                $pedido = $item['pedido'];
                $lancamento = $item['lancamento'];
                $valorRestante = $item['valor_restante'];

                if (!$lancamento) {
                    continue;
                }

                if ($valorRestante <= 0.01) {
                    continue; // Já foi pago de alguma forma
                }

                $valorParaAbater = min($saldoDisponivel, $valorRestante);
                
                try {
                    // Baixar no sistema financeiro usando a Conta 3 (Carteira Cliente)
                    $this->baixaService->baixar(
                        $lancamento,
                        now()->toDateString(),
                        $valorParaAbater,
                        3, // Conta ID 3 = Carteira Cliente
                        'saldo_carteira'
                    );

                    // Não precisamos criar um débito na ContaCorrente aqui!
                    // O PedidoObserver já criou o débito referente à compra no momento em que o Pedido foi criado.
                    // E o MovimentacaoObserver não cria crédito quando a forma de pagamento é 'saldo_carteira'.
                    // Portanto, o saldo da carteira do cliente não sofre impacto nesta operação (ele já reflete a dívida ou o pagamento real).

                    $saldoDisponivel -= $valorParaAbater;
                    $pedidosAbatidos++;

                    // Atualizar status do pedido
                    $lancamento->refresh();
                    if ($lancamento->status === 'pago') {
                        $pedido->status_pagamento = 'aprovado';
                        // Apenas atualiza o status_pedido se ainda estiver pendente/aguardando
                        if (in_array(strtolower($pedido->status_pedido), ['pendente', 'aguardando_pagamento', 'novo'])) {
                            $pedido->status_pedido = 'pago';
                        }
                    } else {
                        $pedido->status_pagamento = 'parcial';
                    }
                    $pedido->save();

                    Log::info("WalletAutoPay: Pedido {$pedido->numero_pedido} abatido em R$ {$valorParaAbater}. Saldo restante carteira: {$saldoDisponivel}");
                } catch (\Exception $e) {
                    Log::error("WalletAutoPay erro ao baixar pedido {$pedido->numero_pedido}: " . $e->getMessage());
                }
            }

            return $pedidosAbatidos;
        });
    }
}
