<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Pedido;
use App\Models\Item;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Models\ContaBancaria;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BancoInterPixTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Testa se ao confirmar o checkout com Pix ele gera os dados no Banco Inter,
     * atualiza o status de pagamento para pix e redireciona para a tela do Pix.
     */
    public function test_checkout_routes_to_inter_pix_when_pix_selected()
    {
        $user = User::factory()->create();

        // Criar um pedido pendente
        $pedido = Pedido::create([
            'numero_pedido' => 'PED-' . uniqid(),
            'user_id' => $user->id,
            'status_pedido' => 'pendente',
            'data_pedido' => now(),
            'valor_total' => 120.00,
            'valor_frete' => 20.00,
            'valor_desconto' => 0.00,
            'valor_saldo_utilizado' => 0.00,
            'status_pagamento' => 'pendente',
            'origem_pedido' => 'portal',
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('portal.checkout.confirmar', $pedido->id), [
                'shipping_id' => 'pac',
                'shipping_price' => 20.00,
                'shipping_name' => 'Correios PAC',
                'payment_method' => 'pix'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'redirect']);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('/inter/' . $pedido->id . '/checkout', $data['redirect']);

        // Atualizar o pedido da base
        $pedido->refresh();

        $this->assertEquals('pix', $pedido->forma_pagamento);
        $this->assertNotEmpty($pedido->inter_txid);

        // Verifica se a sessão contém os dados do Pix gerado
        $this->assertTrue(session()->has("inter_pix_{$pedido->id}"));
        $sessionData = session("inter_pix_{$pedido->id}");
        $this->assertEquals($pedido->inter_txid, $sessionData['txid']);
        $this->assertNotEmpty($sessionData['pixCopiaECola']);
    }

    /**
     * Testa se o webhook do Banco Inter aprova o pedido, dá baixa no estoque
     * e cria a movimentação financeira correta no caixa.
     */
    public function test_webhook_confirms_payment_and_reduces_stock_and_creates_movimentacao()
    {
        $user = User::factory()->create();

        // Criar a conta bancária do Banco Inter caso não exista
        $contaInter = ContaBancaria::firstOrCreate(
            ['nome' => 'Banco Inter'],
            [
                'agencia' => '0001',
                'numero_conta' => '123456-7',
                'status' => 'ativo',
                'saldo_inicial' => 0.00
            ]
        );

        // Criar um item disponível no estoque
        $item = Item::create([
            'codigo' => 'SKU-' . uniqid(),
            'nome_do_produto' => 'Melissa Flox',
            'descricao' => 'Sandália preta',
            'preco' => 100.00,
            'custo' => 50.00,
            'category' => 'Sandália',
            'status' => 'disponivel',
        ]);

        // Criar um pedido pendente
        $pedido = Pedido::create([
            'numero_pedido' => 'PED-' . uniqid(),
            'user_id' => $user->id,
            'status_pedido' => 'pendente',
            'data_pedido' => now(),
            'valor_total' => 100.00,
            'valor_frete' => 0.00,
            'valor_desconto' => 0.00,
            'valor_saldo_utilizado' => 0.00,
            'status_pagamento' => 'pendente',
            'forma_pagamento' => 'pix',
            'inter_txid' => 'TXIDTEST1234567890abcdef',
            'origem_pedido' => 'portal',
        ]);

        // Adicionar o item ao pedido
        DB::table('items_pedido')->insert([
            'pedido_id' => $pedido->id,
            'item_id' => $item->id,
            'quantidade' => 1,
            'preco_unitario' => 100.00,
            'status_item' => 'ativo',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // O PedidoObserver deve ter criado o lançamento ao salvarmos o pedido
        $lancamento = Lancamento::where('referencia_tipo', 'pedido')
            ->where('referencia_id', $pedido->id)
            ->first();

        $this->assertNotNull($lancamento, 'Lançamento financeiro deveria ter sido criado automaticamente pelo PedidoObserver.');
        $this->assertEquals('pendente', $lancamento->status);

        // Chamar o webhook de confirmação
        $payload = [
            'pix' => [
                [
                    'txid' => 'TXIDTEST1234567890abcdef',
                    'valor' => '100.00',
                    'horario' => '2026-06-11T12:00:00.000Z',
                    'endToEndId' => 'E1234567890'
                ]
            ]
        ];

        $response = $this->postJson('/api/webhooks/inter', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'OK']);

        // Recarregar os modelos do banco
        $pedido->refresh();
        $item->refresh();
        $lancamento->refresh();

        // 1. Verificar alteração dos status do pedido
        $this->assertEquals('aprovado', $pedido->status_pagamento);
        $this->assertEquals('pago', $pedido->status_pedido);

        // 2. Verificar se o estoque foi baixado
        $this->assertEquals('vendido', $item->status);

        // 3. Verificar se o lançamento foi alterado para pago
        $this->assertEquals('pago', $lancamento->status);

        // 4. Verificar se a movimentação financeira correspondente foi gravada na conta do Banco Inter
        $movimentacao = Movimentacao::where('lancamento_id', $lancamento->id)->first();
        $this->assertNotNull($movimentacao);
        $this->assertEquals(100.00, $movimentacao->valor_pago);
        $this->assertEquals($contaInter->id, $movimentacao->conta_bancaria_id);
        $this->assertEquals('pix', $movimentacao->forma_pagamento);
    }
}
