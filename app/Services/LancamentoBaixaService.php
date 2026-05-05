<?php

namespace App\Services;

use App\Models\Lancamento;
use App\Models\Movimentacao;
use Illuminate\Support\Facades\DB;

class LancamentoBaixaService
{
    /**
     * Realiza a baixa (liquidação parcial ou total) de um lançamento.
     *
     * @param  Lancamento  $lancamento  O lançamento a ser liquidado.
     * @param  string      $dataPagamento  Data do pagamento (Y-m-d).
     * @param  float       $valorPago  Valor efetivamente pago.
     * @param  int         $contaBancariaId  ID da conta bancária que recebeu/debitou.
     * @param  string      $formaPagamento  Forma de pagamento (pix, boleto, etc.).
     * @return Movimentacao  A movimentação criada.
     *
     * @throws \Exception  Se o lançamento estiver cancelado ou já pago integralmente.
     */
    public function baixar(
        Lancamento $lancamento,
        string $dataPagamento,
        float $valorPago,
        int $contaBancariaId,
        string $formaPagamento
    ): Movimentacao {
        if ($lancamento->status === 'cancelado') {
            throw new \Exception('Não é possível baixar um lançamento cancelado.');
        }

        if ($lancamento->status === 'pago') {
            throw new \Exception('Este lançamento já foi integralmente liquidado.');
        }

        return DB::transaction(function () use ($lancamento, $dataPagamento, $valorPago, $contaBancariaId, $formaPagamento) {
            // 1. Registrar a movimentação de caixa
            $movimentacao = Movimentacao::create([
                'lancamento_id'    => $lancamento->id,
                'conta_bancaria_id' => $contaBancariaId,
                'data_pagamento'   => $dataPagamento,
                'valor_pago'       => $valorPago,
                'forma_pagamento'  => $formaPagamento,
            ]);

            // 2. Recalcular total já pago (incluindo a nova movimentação)
            $totalPago = $lancamento->movimentacoes()->sum('valor_pago');

            // 3. Determinar novo status com margem de tolerância de R$ 0,01
            $novoStatus = ($totalPago >= ($lancamento->valor_total - 0.01))
                ? 'pago'
                : 'pago_parcial';

            $lancamento->update(['status' => $novoStatus]);

            return $movimentacao;
        });
    }

    /**
     * Cancela um lançamento (somente se estiver pendente ou parcial).
     *
     * @param  Lancamento  $lancamento
     * @return bool
     *
     * @throws \Exception
     */
    public function cancelar(Lancamento $lancamento): bool
    {
        if ($lancamento->status === 'pago') {
            throw new \Exception('Não é possível cancelar um lançamento já pago. Estorne as movimentações primeiro.');
        }

        return $lancamento->update(['status' => 'cancelado']);
    }
}
