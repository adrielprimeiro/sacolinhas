<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar coluna em pontuacoes_clientes
        Schema::table('pontuacoes_clientes', function (Blueprint $table) {
            $table->decimal('pontos_retirados', 10, 2)->default(0.00)->after('pontos_itens');
        });

        // 2. Adicionar coluna em pontuacoes_grupos
        Schema::table('pontuacoes_grupos', function (Blueprint $table) {
            $table->decimal('pontos_retirados', 10, 2)->default(0.00)->after('pontos_itens');
        });

        // 3. Atualizar colunas geradas (MySQL não permite mudar a expressão via Blueprint facilmente)
        DB::statement("ALTER TABLE pontuacoes_clientes DROP COLUMN total");
        DB::statement("ALTER TABLE pontuacoes_clientes ADD COLUMN total DECIMAL(10,2) GENERATED ALWAYS AS (pontos_mensalidade + pontos_itens + pontos_desafios + pontos_bonus_grupo + pontos_retirados) STORED");

        DB::statement("ALTER TABLE pontuacoes_grupos DROP COLUMN total");
        DB::statement("ALTER TABLE pontuacoes_grupos ADD COLUMN total DECIMAL(10,2) GENERATED ALWAYS AS (pontos_mensalidades + pontos_itens + pontos_retirados) STORED");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pontuacoes_clientes DROP COLUMN total");
        DB::statement("ALTER TABLE pontuacoes_clientes ADD COLUMN total DECIMAL(10,2) GENERATED ALWAYS AS (pontos_mensalidade + pontos_itens + pontos_desafios + pontos_bonus_grupo) STORED");

        DB::statement("ALTER TABLE pontuacoes_grupos DROP COLUMN total");
        DB::statement("ALTER TABLE pontuacoes_grupos ADD COLUMN total DECIMAL(10,2) GENERATED ALWAYS AS (pontos_mensalidades + pontos_itens) STORED");

        Schema::table('pontuacoes_clientes', function (Blueprint $table) {
            $table->dropColumn('pontos_retirados');
        });

        Schema::table('pontuacoes_grupos', function (Blueprint $table) {
            $table->dropColumn('pontos_retirados');
        });
    }
};
