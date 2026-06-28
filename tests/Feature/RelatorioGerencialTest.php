<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RelatorioGerencialTest extends TestCase
{
    use DatabaseTransactions;

    public function test_relatorio_gerencial_page_loads_successfully_for_admin()
    {
        // 1. Criar um usuário administrador e autenticar
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        // 2. Executar a requisição para a rota do relatório gerencial
        $response = $this->actingAs($user)
            ->get(route('financeiro.relatoriogerencial'));

        // 3. Verificar se o status é 200 OK e se as variáveis estão na view
        $response->assertStatus(200);
        $response->assertViewHas([
            'periodo',
            'pedidosCount',
            'faturamentoBruto',
            'saldoUtilizado',
            'liquidoDireto',
            'creditosAporte',
            'creditosAvaliacao',
            'creditosDevolucao',
            'totalCreditosGerados',
            'custoFornecedorReal',
            'custoDesapegoVirtual',
            'investimentoTotalEstoque',
            'margemBruta',
            'lucratividadePercentual',
        ]);
    }
}
