<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pedido;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Models\ContaBancaria;
use App\Services\LancamentoBaixaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InterWebhookController extends Controller
{
    protected $baixaService;

    public function __construct(LancamentoBaixaService $baixaService)
    {
        $this->baixaService = $baixaService;
    }

    /**
     * Recebe e processa as notificações de pagamento Pix do Banco Inter.
     */
    public function handle(Request $request)
    {
        Log::info('Webhook Pix Banco Inter recebido', [
            'payload' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $pixList = $request->input('pix');

        if (!is_array($pixList)) {
            Log::warning('Payload de webhook Banco Inter inválido ou vazio (sem chave pix)');
            return response()->json(['message' => 'Nenhum pix para processar'], 200);
        }

        foreach ($pixList as $pixItem) {
            $txid = $pixItem['txid'] ?? null;
            $valorPago = (float) ($pixItem['valor'] ?? 0);
            $dataPagamento = isset($pixItem['horario']) 
                ? Carbon::parse($pixItem['horario'])->toDateString() 
                : now()->toDateString();

            if (!$txid) {
                Log::warning('Pix sem txid recebido no webhook Banco Inter');
                continue;
            }

            // Busca o pedido correspondente ao txid
            $pedido = Pedido::where('inter_txid', $txid)->first();

            if (!$pedido) {
                // Tenta buscar nos lançamentos (recargas diretas)
                $lancamento = Lancamento::where('inter_txid', $txid)->first();
                if ($lancamento) {
                    if ($lancamento->status === 'pago') {
                        Log::info("Lançamento #{$lancamento->id} já está pago. Ignorando webhook.");
                        continue;
                    }

                    DB::beginTransaction();
                    try {
                        // 1. Atualizar o lançamento para pago
                        $lancamento->status = 'pago';
                        $lancamento->save();

                        // 2. Localizar a conta do Banco Inter
                        $contaInter = ContaBancaria::where('nome', 'like', '%Inter%')->first();
                        $contaBancariaId = $contaInter ? $contaInter->id : 1;

                        // 3. Criar a movimentação
                        Movimentacao::updateOrCreate(
                            [
                                'lancamento_id'    => $lancamento->id,
                                'forma_pagamento'  => 'pix',
                            ],
                            [
                                'conta_bancaria_id' => $contaBancariaId,
                                'data_pagamento'   => $dataPagamento,
                                'valor_pago'       => $valorPago,
                            ]
                        );

                        DB::commit();
                        Log::info("Lançamento #{$lancamento->id} processado com sucesso via webhook Banco Inter.");
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Log::error("Erro ao processar webhook Pix Banco Inter para Lançamento #{$lancamento->id}: " . $e->getMessage());
                    }
                    continue;
                }

                Log::warning("Pedido ou Lançamento não encontrado para o inter_txid: {$txid}");
                continue;
            }

            if ($pedido->status_pagamento === 'aprovado') {
                Log::info("Pedido #{$pedido->id} já está aprovado/pago. Ignorando webhook.");
                continue;
            }

            DB::beginTransaction();
            try {
                // 1. Atualizar o pedido para aprovado/pago
                $pedido->status_pagamento = 'aprovado';
                $pedido->status_pedido = 'pago';
                $pedido->forma_pagamento = 'pix';
                $pedido->save(); // Isso dispara o PedidoObserver que cria/atualiza o Lançamento

                // 2. Dar baixa no estoque dos itens
                $this->darBaixaEstoque($pedido);

                // 3. Obter o Lançamento correspondente
                $lancamento = Lancamento::where('referencia_tipo', 'pedido')
                    ->where('referencia_id', $pedido->id)
                    ->first();

                if ($lancamento) {
                    // Localiza a conta do Banco Inter pelo nome ou ID
                    $contaInter = ContaBancaria::where('nome', 'like', '%Inter%')->first();
                    $contaBancariaId = $contaInter ? $contaInter->id : 1;

                    // Cria a movimentação financeira diretamente
                    Movimentacao::updateOrCreate(
                        [
                            'lancamento_id'    => $lancamento->id,
                            'forma_pagamento'  => 'pix',
                        ],
                        [
                            'conta_bancaria_id' => $contaBancariaId,
                            'data_pagamento'   => $dataPagamento,
                            'valor_pago'       => $valorPago,
                        ]
                    );

                    Log::info("Baixa de faturamento registrada via webhook Banco Inter para o Pedido #{$pedido->id}");
                } else {
                    Log::error("Lançamento financeiro não encontrado para o Pedido #{$pedido->id} após salvamento.");
                }

                DB::commit();
                Log::info("Pedido #{$pedido->id} processado com sucesso via webhook Banco Inter.");

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Erro ao processar webhook Pix Banco Inter para o Pedido #{$pedido->id}: " . $e->getMessage(), [
                    'exception' => $e
                ]);
            }
        }

        return response()->json(['status' => 'OK'], 200);
    }

    /**
     * Altera o status dos itens vinculados ao pedido para 'vendido'.
     */
    private function darBaixaEstoque(Pedido $pedido)
    {
        if (str_starts_with($pedido->numero_pedido, 'REC-')) {
            return;
        }

        $itemIds = DB::table('items_pedido')
            ->where('pedido_id', $pedido->id)
            ->pluck('item_id');

        if ($itemIds->count() > 0) {
            DB::table('items')
                ->whereIn('id', $itemIds)
                ->update([
                    'status' => 'vendido',
                    'updated_at' => now()
                ]);

            Log::info("Estoque baixado via webhook Banco Inter para o pedido #{$pedido->id}", ['items' => $itemIds]);
        }
    }
}
