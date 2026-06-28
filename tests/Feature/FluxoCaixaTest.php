<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FluxoCaixaTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fluxo_de_caixa_page_loads_successfully_for_admin()
    {
        // 1. Criar um usuário administrador e autenticar
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // 2. Executar a requisição para a rota de fluxo de caixa
        $response = $this->actingAs($user)
            ->get(route('financeiro.fluxodecaixa'));

        // 3. Verificar se o status é 200 OK e se as variáveis estão na view
        $response->assertStatus(200);
        $response->assertViewHas([
            'contas',
            'contasSelecionadas',
            'totalEntradas',
            'totalSaidas',
            'saldoLiquido',
            'treeReceitas',
            'treeDespesas',
            'chartData',
            'periodo',
        ]);
    }
}
