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
}
