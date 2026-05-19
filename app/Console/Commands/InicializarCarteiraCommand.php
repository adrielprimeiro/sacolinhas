<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContaBancaria;
use App\Models\ContaCorrente;
use App\Models\User;

class InicializarCarteiraCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financeiro:inicializar-carteira';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Calcula o saldo consolidado de todos os clientes e inicializa a conta bancária Carteira Cliente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("   Inicializando Conta Bancária: Carteira Cliente  ");
        $this->info("==================================================");

        // 1. Encontrar ou criar a conta bancária "Carteira Cliente"
        $conta = ContaBancaria::where('nome', 'like', '%Carteira Cliente%')
            ->orWhere('nome', 'like', '%Carteira de Cliente%')
            ->first();

        if (!$conta) {
            $this->comment("Conta bancária 'Carteira Cliente' não encontrada. Criando...");
            $conta = ContaBancaria::create([
                'nome' => 'Carteira Cliente',
                'tipo' => 'caixa',
                'saldo_inicial' => 0.00,
            ]);
            $this->info("Conta 'Carteira Cliente' criada com sucesso (ID: {$conta->id}).");
        } else {
            $this->info("Conta encontrada: '{$conta->nome}' (ID: {$conta->id})");
        }

        // 2. Calcular os saldos dos clientes
        $this->comment("Calculando saldos atuais de todos os clientes no sistema...");
        
        $saldos = [];
        $users = User::all();
        $totalUsers = count($users);
        $bar = $this->output->createProgressBar($totalUsers);
        $bar->start();

        foreach ($users as $user) {
            $last = ContaCorrente::where('user_id', $user->id)
                ->orderByDesc('data_movimentacao')
                ->orderByDesc('id')
                ->first();
            
            if ($last) {
                $saldos[$user->id] = (float)$last->saldo_atual;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->println("");

        $positivo = array_filter($saldos, fn($v) => $v > 0);
        $negativo = array_filter($saldos, fn($v) => $v < 0);
        $netBalance = array_sum($saldos);

        // 3. Exibir Resumo no Terminal
        $this->println("");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de Clientes no Sistema', $totalUsers],
                ['Clientes com Histórico de Saldo', count($saldos)],
                ['Soma de Vales/Créditos (Positivos)', 'R$ ' . number_format(array_sum($positivo), 2, ',', '.')],
                ['Soma de Pendências/Débitos (Negativos)', 'R$ ' . number_format(array_sum($negativo), 2, ',', '.')],
                ['Saldo Líquido Consolidado (Net)', 'R$ ' . number_format($netBalance, 2, ',', '.')]
            ]
        );

        // 4. Atualizar o saldo_inicial da conta bancária
        $this->comment("Atualizando saldo_inicial da conta bancária...");
        
        $saldoAnterior = $conta->saldo_inicial;
        $conta->saldo_inicial = $netBalance;
        $conta->save();

        $this->info("Sucesso! Saldo inicial da conta '{$conta->nome}' atualizado de R$ " . number_format($saldoAnterior, 2, ',', '.') . " para R$ " . number_format($netBalance, 2, ',', '.'));
        $this->info("==================================================");
    }

    /**
     * Helper para imprimir quebra de linha
     */
    private function println(string $text)
    {
        $this->line($text);
    }
}
