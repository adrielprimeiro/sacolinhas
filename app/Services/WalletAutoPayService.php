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

            if (!$ultimaMov || $ultimaMov->saldo_atual <= 0) {
                return 0;
            }

            $saldoDisponivel = $ultimaMov->saldo_atual;
            $pedidosAbatidos = 0;

            // Busca pedidos pendentes ou parciais do usuário (mais antigos primeiro)
            $pedidosPendentes = Pedido::where('user_id', $userId)
                                      ->whereIn('status_pagamento', ['pendente', 'parcial'])
                                      ->orderBy('created_at', 'asc')
                                      ->get();

            foreach ($pedidosPendentes as $pedido) {
                if ($saldoDisponivel <= 0.01) {
                    break;
                }

                // Encontra o lançamento de receita correspondente ao pedido
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                                        ->where('referencia_id', $pedido->id)
                                        ->where('tipo', 'receita')
                                        ->whereIn('status', ['pendente', 'pago_parcial'])
                                        ->first();

                if (!$lancamento) {
                    continue;
                }

                $valorJaPago = $lancamento->movimentacoes()->sum('valor_pago');
                $valorRestante = max(0, $lancamento->valor_total - $valorJaPago);

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
                        'carteira_cliente'
                    );

                    // Descontar da carteira do cliente
                    $novoSaldo = $saldoDisponivel - $valorParaAbater;
                    
                    ContaCorrente::create([
                        'user_id' => $userId,
                        'tipo_movimentacao' => 'debito',
                        'valor' => $valorParaAbater,
                        'descricao' => "Pagamento Automático do Pedido {$pedido->numero_pedido}",
                        'referencia_tipo' => 'pedido',
                        'referencia_id' => $pedido->id,
                        'classificacao_id' => 17, // Assumindo ID 17 = Vendas / ou alguma classificação adequada.
                        'saldo_anterior' => $saldoDisponivel,
                        'saldo_atual' => $novoSaldo,
                        'data_movimentacao' => now(),
                    ]);

                    $saldoDisponivel = $novoSaldo;
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
