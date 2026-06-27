<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyPrecinhoDiscount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:apply-precinho-discount 
                            {live_id? : O ID da live a ser atualizada}
                            {--force : Executar sem confirmação interativa}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica desconto de 50% nos itens da sacola de uma live, exceto itens que já foram manualmente editados.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $liveId = $this->argument('live_id');

        if (!$liveId) {
            // Encontra a última live que possui itens na sacola
            $liveIdsWithItems = DB::table('sacolinhas')
                ->select('live_id')
                ->distinct()
                ->get()
                ->pluck('live_id');

            $live = DB::table('lives')
                ->whereIn('id', $liveIdsWithItems)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$live) {
                $this->error('Nenhuma live com itens na sacola foi encontrada.');
                return Command::FAILURE;
            }

            $liveId = $live->id;
        } else {
            $live = DB::table('lives')->where('id', $liveId)->first();
            if (!$live) {
                $this->error("Live com ID {$liveId} não encontrada.");
                return Command::FAILURE;
            }
        }

        $itemCount = DB::table('sacolinhas')->where('live_id', $liveId)->count();

        $this->info("=== APLICAÇÃO DE DESCONTO DE 50% (LIVE DO PRECINHO) ===");
        $this->info("Live Selecionada: ID {$live->id}");
        $this->info("Tipo da Live: {$live->tipo_live}");
        $this->info("Criada em: {$live->created_at}");
        $this->info("Total de itens na sacola: {$itemCount}");
        $this->newLine();

        if ($itemCount === 0) {
            $this->comment("Esta live não possui itens na sacola para atualizar.");
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Deseja realmente aplicar o desconto de 50% aos itens não editados desta live?')) {
            $this->comment('Operação cancelada pelo usuário.');
            return Command::SUCCESS;
        }

        // Buscar itens na sacola desta live
        $sacolinhas = DB::table('sacolinhas as s')
            ->join('items as i', 's.item_id', '=', 'i.id')
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->where('s.live_id', $liveId)
            ->select('s.id as sacolinha_id', 's.price as sacolinha_price', 'i.nome_do_produto', 'i.preco as item_original_price', 'u.name as user_name')
            ->get();

        $updatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($sacolinhas, &$updatedCount, &$skippedCount) {
            foreach ($sacolinhas as $s) {
                $sacolinhaPrice = (float) $s->sacolinha_price;
                $originalPrice = (float) $s->item_original_price;
                
                // Se a diferença for muito pequena (ex: menos de 0.01), consideramos igual (não editado)
                $isEdited = abs($sacolinhaPrice - $originalPrice) > 0.01;
                
                if ($isEdited) {
                    $skippedCount++;
                    $this->line("<comment>[PULADO]</comment> Cliente: {$s->user_name} | Item: {$s->nome_do_produto} | Preço sacola: R$ " . number_format($sacolinhaPrice, 2, ',', '.') . " | Original: R$ " . number_format($originalPrice, 2, ',', '.') . " (já editado)");
                } else {
                    $updatedCount++;
                    $newPrice = $originalPrice * 0.5;
                    
                    DB::table('sacolinhas')
                        ->where('id', $s->sacolinha_id)
                        ->update([
                            'price' => $newPrice,
                            'updated_at' => now()
                        ]);
                        
                    $this->line("<info>[ATUALIZADO]</info> Cliente: {$s->user_name} | Item: {$s->nome_do_produto} | R$ " . number_format($originalPrice, 2, ',', '.') . " -> R$ " . number_format($newPrice, 2, ',', '.'));
                }
            }
        });

        $this->newLine();
        $this->info("=== PROCESSO CONCLUÍDO ===");
        $this->info("Itens atualizados (desconto de 50% aplicado): {$updatedCount}");
        $this->info("Itens pulados (já editados anteriormente): {$skippedCount}");
        $this->info("Os limites dos clientes foram atualizados automaticamente via triggers do banco de dados.");

        return Command::SUCCESS;
    }
}
