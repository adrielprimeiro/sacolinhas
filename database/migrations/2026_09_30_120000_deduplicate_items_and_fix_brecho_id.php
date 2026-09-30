<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Identifica e resolve itens duplicados para (brecho_id IS NULL OR brecho_id = 1)
        $duplicateCodes = DB::table('items')
            ->select('codigo', DB::raw('count(*) as total'))
            ->whereNull('brecho_id')
            ->orWhere('brecho_id', 1)
            ->groupBy('codigo')
            ->having('total', '>', 1)
            ->pluck('codigo');

        foreach ($duplicateCodes as $codigo) {
            $items = DB::table('items')
                ->where('codigo', $codigo)
                ->where(function ($q) {
                    $q->whereNull('brecho_id')->orWhere('brecho_id', 1);
                })
                ->orderBy('id', 'asc')
                ->get();

            if ($items->count() <= 1) {
                continue;
            }

            // Descobre o item canônico com base em uso real
            $canonical = null;

            // Critério 1: Item que está em sacolinha ou pedido ou tem status ativo/vendido ou está em Live
            foreach ($items as $it) {
                $hasSacolinha = DB::table('sacolinhas')->where('item_id', $it->id)->exists();
                $hasPedido = DB::table('items_pedido')->where('item_id', $it->id)->exists();
                $hasLiveItem = DB::table('live_items')->where('item_id', $it->id)->exists();
                $isLive = ($it->localizacao === 'Live');

                if ($hasSacolinha || $hasPedido || $hasLiveItem || $isLive) {
                    $canonical = $it;
                    break;
                }
            }

            // Critério 2: Item vinculado a avaliacao_items
            if (!$canonical) {
                foreach ($items as $it) {
                    $hasAvaliacao = DB::table('avaliacao_items')->where('item_id', $it->id)->exists();
                    if ($hasAvaliacao) {
                        $canonical = $it;
                        break;
                    }
                }
            }

            // Critério 3: O primeiro registro criado
            if (!$canonical) {
                $canonical = $items->first();
            }

            // Reatribui referências e remove os duplicados
            foreach ($items as $it) {
                if ($it->id === $canonical->id) {
                    continue;
                }

                // Relink foreign keys
                DB::table('avaliacao_items')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);
                DB::table('live_items')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);
                DB::table('sacolinhas')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);
                DB::table('items_pedido')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);
                DB::table('live_code_requests')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);
                DB::table('item_media')->where('item_id', $it->id)->update(['item_id' => $canonical->id]);

                // Limpa pivot categoria
                DB::table('categoria_item')->where('item_id', $it->id)->delete();

                // Deleta o registro duplicado
                DB::table('items')->where('id', $it->id)->delete();
            }

            // Garante que o canônico tem brecho_id = 1
            DB::table('items')->where('id', $canonical->id)->update(['brecho_id' => 1]);
        }

        // 2. Todos os itens restantes com brecho_id nulo recebem brecho_id = 1 (Matriz)
        DB::table('items')->whereNull('brecho_id')->update(['brecho_id' => 1]);

        // 3. Garante que brecho_id na tabela items tem default 1 e é NOT NULL
        try {
            DB::statement("ALTER TABLE `items` MODIFY `brecho_id` BIGINT UNSIGNED NOT NULL DEFAULT 1");
        } catch (\Exception $e) {
            // Ignora se não for suportado diretamente pela versão do banco
        }

        // 4. Garante que o índice UNIQUE(brecho_id, codigo) existe
        $hasBrechoCodigoUnique = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_brecho_codigo_unique'"))->isNotEmpty();
        if (!$hasBrechoCodigoUnique) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique(['brecho_id', 'codigo'], 'items_brecho_codigo_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reverte deduplicação de dados corrompidos
    }
};
