<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Live;
use App\Models\ContaCorrente;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminSacolinhaAutorizacaoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_cannot_close_sacolinha_with_insufficient_balance_if_unauthorized()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        // Create a dummy live
        $live = Live::create([
            'data' => now(),
            'tipo_live' => 'loja-aberta',
            'plataformas' => 'instagram',
            'ativo' => true,
        ]);

        // Create an item in the sacolinha
        $item = Item::create([
            'codigo' => 'TST-' . uniqid(),
            'nome_do_produto' => 'Test Item',
            'preco' => 100.00,
            'status' => 'disponivel',
        ]);

        DB::table('sacolinhas')->insert([
            'user_id' => $client->id,
            'item_id' => $item->id,
            'live_id' => $live->id,
            'price' => 100.00,
            'quantity' => 1,
            'status' => 'live',
            'add_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Client wallet has R$ 0 balance
        // Try to close sacolinha
        $response = $this->actingAs($admin)
            ->postJson(route('admin.sacolinha.fechar'), [
                'user_id' => $client->id,
                'valor_frete' => 20.00,
                'itens' => [
                    DB::table('sacolinhas')->where('user_id', $client->id)->first()->id
                ]
            ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Saldo insuficiente na carteira', $response->json('message'));
    }

    public function test_can_authorize_and_revoke_sacolinha_closing()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        // Authorize
        $response = $this->actingAs($admin)
            ->postJson(route('admin.sacolinha.autorizar', $client->id), [
                'autorizado_por' => 'Gerente Maria',
                'observacoes' => 'Autorizado frete pendente para cliente VIP',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $client->refresh();
        $this->assertEquals('Gerente Maria', $client->sacolinha_autorizada_por);
        $this->assertEquals('Autorizado frete pendente para cliente VIP', $client->sacolinha_autorizada_obs);

        // Revoke
        $response = $this->actingAs($admin)
            ->postJson(route('admin.sacolinha.revogar', $client->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $client->refresh();
        $this->assertNull($client->sacolinha_autorizada_por);
        $this->assertNull($client->sacolinha_autorizada_obs);
    }

    public function test_can_close_sacolinha_when_authorized_leaving_negative_balance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);

        // Set client initial balance to R$ 30
        ContaCorrente::create([
            'user_id' => $client->id,
            'tipo_movimentacao' => 'credito',
            'valor' => 30.00,
            'descricao' => 'Deposito Inicial',
            'classificacao_id' => 14,
            'data_movimentacao' => now(),
            'saldo_anterior' => 0,
            'saldo_atual' => 30.00,
        ]);

        // Create a dummy live
        $live = Live::create([
            'data' => now(),
            'tipo_live' => 'loja-aberta',
            'plataformas' => 'instagram',
            'ativo' => true,
        ]);

        $item = Item::create([
            'codigo' => 'TST-' . uniqid(),
            'nome_do_produto' => 'Test Item',
            'preco' => 100.00,
            'status' => 'disponivel',
        ]);

        DB::table('sacolinhas')->insert([
            'user_id' => $client->id,
            'item_id' => $item->id,
            'live_id' => $live->id,
            'price' => 100.00,
            'quantity' => 1,
            'status' => 'live',
            'add_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sacolinhaId = DB::table('sacolinhas')->where('user_id', $client->id)->first()->id;

        // Authorize
        $client->update([
            'sacolinha_autorizada_por' => 'Gerente Maria',
            'sacolinha_autorizada_obs' => 'Autorizado frete pendente',
        ]);

        // Close sacolinha with freight R$ 20. Total order: R$ 120. Missing: R$ 90.
        $response = $this->actingAs($admin)
            ->postJson(route('admin.sacolinha.fechar'), [
                'user_id' => $client->id,
                'valor_frete' => 20.00,
                'itens' => [$sacolinhaId]
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check user's authorization is cleared
        $client->refresh();
        $this->assertNull($client->sacolinha_autorizada_por);
        $this->assertNull($client->sacolinha_autorizada_obs);

        // Check that tolerance credit and debit logs were created
        $creditoTolerancia = ContaCorrente::where('user_id', $client->id)
            ->where('tipo_movimentacao', 'credito')
            ->where('descricao', 'like', '%Crédito de Tolerância%')
            ->first();

        $debitoTolerancia = ContaCorrente::where('user_id', $client->id)
            ->where('tipo_movimentacao', 'debito')
            ->where('descricao', 'like', '%Débito de Tolerância%')
            ->first();

        $this->assertNotNull($creditoTolerancia);
        $this->assertNotNull($debitoTolerancia);
        $this->assertEquals(90.00, (float) $creditoTolerancia->valor);
        $this->assertEquals(90.00, (float) $debitoTolerancia->valor);
        $this->assertStringContainsString('Gerente Maria', $creditoTolerancia->observacoes);
        $this->assertStringContainsString('Autorizado frete pendente', $creditoTolerancia->observacoes);

        // Check reference links
        $pedidoCreated = Pedido::where('user_id', $client->id)->latest('id')->first();
        $this->assertNotNull($pedidoCreated);
        $this->assertEquals($pedidoCreated->id, $creditoTolerancia->referencia_id);
        $this->assertEquals('tolerancia', $creditoTolerancia->referencia_tipo);
    }
}
