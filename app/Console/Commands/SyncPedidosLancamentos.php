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
        $count = 0;

        foreach ($pedidos as $pedido) {
            if ($pedido->valor_total <= 0) continue;

            $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                ->where('referencia_id', $pedido->id)
                ->first();

            if (!$lancamento) {
                $this->info("Gerando lançamento para Pedido #{$pedido->numero_pedido}");
                
                $user = $pedido->user;
                if (!$user) {
                    $this->warn("Pedido #{$pedido->id} não possui usuário vinculado.");
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

                $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'Venda na Live')
                    ->orWhere('nome', 'Venda Live')
                    ->first();
                $classificacaoId = $classificacao ? $classificacao->id : 2; // Fallback para 2

                Lancamento::create([
                    'tipo'                        => 'receita',
                    'status'                      => $pedido->status_pagamento === 'aprovado' ? 'pago' : 'pendente',
                    'pessoa_id'                   => $pessoa->id,
                    'classificacao_financeira_id' => $classificacaoId,
                    'data_emissao'                => $pedido->data_pedido ?? $pedido->created_at,
                    'data_vencimento'             => $pedido->data_pedido ?? $pedido->created_at,
                    'valor_total'                 => $pedido->valor_total,
                    'descricao'                   => "Pedido " . $pedido->numero_pedido,
                    'referencia_tipo'             => 'pedido',
                    'referencia_id'               => $pedido->id,
                ]);
                $count++;
            }
        }

        $this->info("Sincronização concluída. {$count} novos lançamentos gerados.");
    }
}
