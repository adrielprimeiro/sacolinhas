<?php

    namespace App\Jobs;

    use App\Models\ContaCorrente;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Foundation\Bus\Dispatchable;
    use Illuminate\Queue\InteractsWithQueue;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log; // Para logar erros ou o progresso

    class RecalcularSaldosJob implements ShouldQueue
    {
        use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

        protected $userId;
        protected $dataInicial;

        /**
         * Create a new job instance.
         *
         * @param int $userId O ID do usuário cujos saldos serão recalculados.
         * @param string $dataInicial A data a partir da qual o recálculo deve começar.
         * @return void
         */
        public function __construct(int $userId, string $dataInicial)
        {
            $this->userId = $userId;
            $this->dataInicial = $dataInicial;
        }

        /**
         * Execute the job.
         *
         * @return void
         */
        public function handle()
        {
            Log::info("Iniciando recálculo de saldos para user_id: {$this->userId} a partir de: {$this->dataInicial}");

            DB::transaction(function () {
                // Pega todas as movimentações do usuário a partir da data inicial, ordenadas cronologicamente
                $movimentacoes = ContaCorrente::where('user_id', $this->userId)
                                            ->where('data_movimentacao', '>=', $this->dataInicial)
                                            ->orderBy('data_movimentacao')
                                            ->orderBy('id') // Para garantir ordem consistente
                                            ->get();

                // Pega o saldo final da última movimentação ANTES da data inicial
                $saldoAnteriorTotal = ContaCorrente::where('user_id', $this->userId)
                                                  ->where('data_movimentacao', '<', $this->dataInicial)
                                                  ->orderBy('data_movimentacao', 'desc')
                                                  ->orderBy('id', 'desc')
                                                  ->first()
                                                  ->saldo_atual ?? 0;

                foreach ($movimentacoes as $movimentacao) {
                    $movimentacao->saldo_anterior = $saldoAnteriorTotal;

                    if ($movimentacao->tipo_movimentacao === 'credito') {
                        $movimentacao->saldo_atual = $saldoAnteriorTotal + $movimentacao->valor;
                    } else { // debito
                        $movimentacao->saldo_atual = $saldoAnteriorTotal - $movimentacao->valor;
                    }

                    $movimentacao->save();
                    $saldoAnteriorTotal = $movimentacao->saldo_atual;
                }
            });

            Log::info("Recálculo de saldos concluído para user_id: {$this->userId}");
        }
    }