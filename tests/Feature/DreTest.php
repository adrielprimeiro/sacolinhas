<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DreTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dre_page_loads_successfully_for_admin()
    {
        // 1. Criar um usuário administrador e autenticar
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // 2. Executar a requisição para a rota da DRE
        $response = $this->actingAs($user)
            ->get(route('financeiro.dre'));

        // 3. Verificar se o status é 200 OK e se as variáveis estão na view
        $response->assertStatus(200);
        $response->assertViewHas([
            'periodo',
            'receitaVendas',
            'outrasReceitas',
            'receitaBruta',
            'descontosConcedidos',
            'devolucoesVendas',
            'totalDeducoes',
            'receitaLiquida',
            'cmv',
            'lucroBruto',
            'despesasPorCategoria',
            'totalDespesasOperacionais',
            'lucroLiquido',
            'margemBrutaPercentual',
            'margemLiquidaPercentual',
        ]);
    }

    public function test_devolucao_does_not_decrease_order_total_and_creates_devolucao_lancamento()
    {
        // 1. Criar um administrador
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // 2. Criar um pedido com um item
        $pedido = \App\Models\Pedido::create([
            'user_id' => $user->id,
            'numero_pedido' => 'PED-999',
            'status_pedido' => 'pendente',
            'status_pagamento' => 'pendente',
            'valor_total' => 100.00,
            'valor_frete' => 0.00,
            'valor_desconto' => 0.00,
        ]);

        $item = \App\Models\Item::create([
            'codigo' => 'TST-001',
            'nome_do_produto' => 'Item Teste',
            'custo' => 50.00,
            'preco' => 100.00,
        ]);

        $itemPedidoId = \Illuminate\Support\Facades\DB::table('items_pedido')->insertGetId([
            'pedido_id' => $pedido->id,
            'item_id' => $item->id,
            'quantidade' => 1,
            'preco_unitario' => 100.00,
            'status_item' => 'ativo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Recalcular total do pedido (para simular a trigger de insert)
        $pedido->refresh();
        $this->assertEquals(100.00, (float)$pedido->valor_total);

        // 3. Simular a devolução do item via rota
        $response = $this->actingAs($user)
            ->post(route('admin.pedido.devolucao', $pedido->id), [
                'itens_devolver' => [$itemPedidoId],
            ]);

        $response->assertRedirect();
        
        // 4. Verificar se o total do pedido não diminuiu no modelo completo
        $pedido->refresh();
        $this->assertEquals(100.00, (float)$pedido->valor_total);

        // 5. Verificar se foi criado o lançamento de despesa de devolução (categoria 81)
        $this->assertDatabaseHas('lancamentos', [
            'tipo' => 'despesa',
            'classificacao_financeira_id' => 81,
            'valor_total' => 100.00,
            'referencia_tipo' => 'pedido_devolucao',
            'referencia_id' => $pedido->id,
        ]);

        // 6. Verificar se o crédito foi gerado na carteira do cliente
        $this->assertDatabaseHas('conta_corrente', [
            'user_id' => $user->id,
            'tipo_movimentacao' => 'credito',
            'valor' => 100.00,
            'classificacao_id' => 81,
        ]);
    }

    public function test_update_pedido_form_submits_successfully()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $pedido = \App\Models\Pedido::create([
            'user_id' => $user->id,
            'numero_pedido' => 'PED-TEST-123',
            'status_pedido' => 'pendente',
            'status_pagamento' => 'pendente',
            'valor_total' => 100.00,
            'valor_frete' => 0.00,
            'valor_desconto' => 0.00,
            'data_pedido' => now(),
            'origem_pedido' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->put(route('admin.pedido.update', $pedido->id), [
                'user_id' => $user->id,
                'numero_pedido' => 'PED-TEST-123',
                'status_pedido' => 'confirmado',
                'status_pagamento' => 'aprovado',
                'valor_total' => 100.00,
                'valor_frete' => 10.00,
                'valor_desconto' => 5.00,
                'data_pedido' => now()->format('Y-m-d\TH:i'),
                'origem_pedido' => 'admin',
                'valor_saldo_utilizado' => 0.00,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.pedido.index'));
    }
}
