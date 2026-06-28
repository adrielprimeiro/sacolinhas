<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Obter os IDs das movimentações vinculadas a recargas classificadas incorretamente
        $ccMovIds = DB::table('conta_corrente')
            ->where('referencia_tipo', 'movimentacao')
            ->where('classificacao_id', 15)
            ->pluck('referencia_id')
            ->filter()
            ->toArray();

        if (!empty($ccMovIds)) {
            // 2. Obter os IDs dos lançamentos correspondentes
            $lancIds = DB::table('movimentacoes')
                ->whereIn('id', $ccMovIds)
                ->pluck('lancamento_id')
                ->filter()
                ->unique()
                ->toArray();

            if (!empty($lancIds)) {
                // 3. Atualizar classificação financeira do Lançamento para 84 (Recarga de Carteira)
                DB::table('lancamentos')
                    ->whereIn('id', $lancIds)
                    ->where('classificacao_financeira_id', 15)
                    ->update(['classificacao_financeira_id' => 84]);
            }
        }

        // 4. Atualizar classificação na conta corrente do cliente
        DB::table('conta_corrente')
            ->where('referencia_tipo', 'movimentacao')
            ->where('classificacao_id', 15)
            ->update(['classificacao_id' => 84]);
    }

    public function down(): void
    {
        $ccMovIds = DB::table('conta_corrente')
            ->where('referencia_tipo', 'movimentacao')
            ->where('classificacao_id', 84)
            ->pluck('referencia_id')
            ->filter()
            ->toArray();

        if (!empty($ccMovIds)) {
            $lancIds = DB::table('movimentacoes')
                ->whereIn('id', $ccMovIds)
                ->pluck('lancamento_id')
                ->filter()
                ->unique()
                ->toArray();

            if (!empty($lancIds)) {
                DB::table('lancamentos')
                    ->whereIn('id', $lancIds)
                    ->where('classificacao_financeira_id' , 84)
                    ->update(['classificacao_financeira_id' => 15]);
            }
        }

        DB::table('conta_corrente')
            ->where('referencia_tipo', 'movimentacao')
            ->where('classificacao_id', 84)
            ->update(['classificacao_id' => 15]);
    }
};
