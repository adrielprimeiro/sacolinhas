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
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->decimal('porcentagem_valor', 8, 2)->default(100.00); // Ex: 100.00, 350.00, 400.00
            $table->timestamps();
        });

        // 1. Inserir padrões com valores de mercado já estabelecidos
        $standards = [
            'Sem Marca' => 100.00,
            'De Marca'  => 140.00, // Equivale a R$ 35,00 em peças de base 25,00 (1.4x)
            'Farm'      => 180.00, // Equivale a R$ 45,00 em peças de base 25,00 (1.8x)
            'Melissa'   => 400.00, // 400% como solicitado pelo cliente (4.0x)
        ];

        $inserted = [];
        foreach ($standards as $name => $percentage) {
            DB::table('marcas')->insert([
                'nome' => $name,
                'porcentagem_valor' => $percentage,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $inserted[] = strtolower(trim($name));
        }

        // 2. Selecionar todas as marcas distintas da tabela items (estoque atual)
        if (Schema::hasTable('items')) {
            $existingBrands = DB::table('items')
                ->whereNotNull('marca')
                ->where('marca', '!=', '')
                ->distinct()
                ->pluck('marca')
                ->toArray();

            foreach ($existingBrands as $brand) {
                $trimmed = trim($brand);
                $lower = strtolower($trimmed);

                // Normalizações básicas para evitar duplicados com os padrões acima
                if ($lower === 'sem_marca' || $lower === 'sem marca') continue;
                if ($lower === 'de_marca' || $lower === 'de marca') continue;
                if ($lower === 'farm') continue;
                if ($lower === 'melissa') continue;

                if (!in_array($lower, $inserted)) {
                    DB::table('marcas')->insert([
                        'nome' => $trimmed,
                        'porcentagem_valor' => 100.00, // Default 100%
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $inserted[] = $lower;
                }
            }
        }

        // 3. Adicionar coluna marca_id na tabela avaliacao_items
        Schema::table('avaliacao_items', function (Blueprint $table) {
            $table->foreignId('marca_id')->nullable()->after('categoria_id')->constrained('marcas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaliacao_items', function (Blueprint $table) {
            $table->dropForeign(['marca_id']);
            $table->dropColumn('marca_id');
        });

        Schema::dropIfExists('marcas');
    }
};
