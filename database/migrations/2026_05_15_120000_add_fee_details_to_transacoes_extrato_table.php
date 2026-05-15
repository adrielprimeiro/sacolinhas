<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transacoes_extrato', function (Blueprint $table) {
            $table->decimal('valor_bruto', 15, 2)->nullable()->after('descricao');
            $table->decimal('valor_taxa', 15, 2)->nullable()->after('valor_bruto');
            $table->decimal('valor_liquido', 15, 2)->nullable()->after('valor_taxa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transacoes_extrato', function (Blueprint $table) {
            $table->dropColumn(['valor_bruto', 'valor_taxa', 'valor_liquido']);
        });
    }
};
