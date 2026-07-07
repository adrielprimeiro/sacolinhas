<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Adicionar a coluna total_registros na tabela marcas
        Schema::table('marcas', function (Blueprint $table) {
            $table->integer('total_registros')->default(0)->after('porcentagem_valor');
        });

        // 2. Valores percentuais estimados de mercado (baseando-se em Sem Marca = 100%)
        // Marcas premium e importadas agregam de 1.5x a 4x o valor base de revenda.
        $brandValues = [
            'melissa'         => 400.00,
            'mini melissa'    => 350.00,
            'farm'            => 350.00,
            'refarm'          => 250.00,
            'fabula'          => 280.00,
            'zara'            => 180.00,
            'colcci'          => 200.00,
            'lança perfume'   => 250.00,
            'dudalina'        => 300.00,
            'tommy'           => 250.00,
            'adidas'          => 220.00,
            'gap'             => 160.00,
            'cantão'          => 180.00,
            'hering'          => 110.00,
            'marisa'          => 100.00,
            'shein'           => 90.00,
            'blue steel'      => 110.00,
            'bluesteel'       => 110.00,
            'marfinno'        => 110.00,
            'marfino'         => 110.00,
            'cortelle'        => 120.00,
            'clock house'     => 100.00,
            'yessica'         => 100.00,
            'pool'            => 100.00,
            'fuzarka'         => 100.00,
            'alphabeto'       => 140.00,
            'bibi'            => 180.00,
            'puket'           => 140.00,
            'brae'            => 150.00,
            'brae stages'     => 150.00,
            'malwee'          => 100.00,
            'sawary'          => 120.00,
            'patricia foster' => 100.00,
            'disney'          => 120.00,
            'lenir'           => 100.00,
            'glam'            => 100.00,
            'magia mix'       => 100.00,
        ];

        // 3. Contar registros em items e atualizar total_registros e porcentagem_valor
        if (Schema::hasTable('items')) {
            $counts = DB::table('items')
                ->select('marca', DB::raw('count(*) as total'))
                ->whereNotNull('marca')
                ->where('marca', '!=', '')
                ->groupBy('marca')
                ->pluck('total', 'marca')
                ->toArray();

            // Transformar chaves do array de contagem para minúsculas
            $countsLower = [];
            foreach ($counts as $mName => $total) {
                $countsLower[strtolower(trim($mName))] = $total;
            }

            // Obter todas as marcas cadastradas
            $marcas = DB::table('marcas')->get();

            foreach ($marcas as $m) {
                $key = strtolower(trim($m->nome));
                $totalReg = $countsLower[$key] ?? 0;

                // Se for uma das marcas mapeadas na pesquisa, atualiza seu percentual
                $newPct = $m->porcentagem_valor;
                if (isset($brandValues[$key])) {
                    $newPct = $brandValues[$key];
                }

                DB::table('marcas')
                    ->where('id', $m->id)
                    ->update([
                        'total_registros' => $totalReg,
                        'porcentagem_valor' => $newPct,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marcas', function (Blueprint $table) {
            $table->dropColumn('total_registros');
        });
    }
};
