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
        // Só gera lançamento se houver valor
        if ($pedido->valor_total <= 0) {
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

            // 2. Buscar ou criar o lançamento vinculado a este pedido
            $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();

            $dadosLancamento = [
                'tipo'                        => 'receita',
                'status'                      => $pedido->status_pagamento === 'aprovado' ? 'pago' : 'pendente',
                'pessoa_id'                   => $pessoa->id,
                'classificacao_financeira_id' => 1, // Venda
                'data_emissao'                => $pedido->data_pedido ?? $pedido->created_at,
                'data_vencimento'             => $pedido->data_pedido ?? $pedido->created_at,
                'valor_total'                 => $pedido->valor_total,
                'descricao'                   => "Pedido " . $pedido->numero_pedido,
                'referencia_tipo'             => 'pedido',
                'referencia_id'               => $pedido->id,
            ];

            if ($lancamento) {
                // Só atualiza se o valor ou status mudou para evitar recursão infinita se houver outros observers
                $lancamento->update($dadosLancamento);
            } else {
                Lancamento::create($dadosLancamento);
            }

        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao gerar lançamento: " . $e->getMessage());
        }
    }

    /**
     * Handle the Pedido "deleted" event.
     */
    public function deleted(Pedido $pedido): void
    {
        try {
            Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->delete();
        } catch (\Throwable $e) {
            Log::error("Erro no PedidoObserver ao deletar lançamento: " . $e->getMessage());
        }
    }
}
