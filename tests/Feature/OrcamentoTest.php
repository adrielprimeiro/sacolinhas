<?php

namespace Tests\Feature;

use App\Models\ClassificacaoFinanceira;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrcamentoTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_replicate_budget_values_to_next_month()
    {
        // 1. Criar um usuário administrador e autenticar
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        
        // 2. Buscar uma classificação financeira existente
        $classificacao = ClassificacaoFinanceira::first();
        $this->assertNotNull($classificacao, 'Precisa existir ao menos uma classificação financeira no banco para rodar o teste.');

        // 3. Criar ou atualizar orçamento para o mês de origem (ex: 2026-06)
        $orcamento = Orcamento::updateOrCreate(
            [
                'classificacao_financeira_id' => $classificacao->id,
                'periodo' => '2026-06-01',
            ],
            [
                'valor_previsto' => 1250.50,
            ]
        );

        // 4. Executar o POST para replicar o orçamento para o próximo mês (2026-07)
        $response = $this->actingAs($user)
            ->post(route('financeiro.orcamento.replicar'), [
                'periodo_origem' => '2026-06',
            ]);

        // 5. Verificar o redirecionamento com sucesso
        $response->assertRedirect(route('financeiro.orcamento.index', ['periodo' => '2026-07']));
        $response->assertSessionHas('success');

        // 6. Verificar se o novo orçamento foi criado no mês de destino (2026-07-01) com o mesmo valor previsto
        $this->assertDatabaseHas('orcamentos', [
            'classificacao_financeira_id' => $classificacao->id,
            'periodo' => '2026-07-01',
            'valor_previsto' => 1250.50,
        ]);
    }
}
