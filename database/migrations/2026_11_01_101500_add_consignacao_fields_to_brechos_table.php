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
        Schema::table('brechos', function (Blueprint $table) {
            $table->string('prefixo_codigo', 10)->nullable()->after('slug');
            $table->decimal('taxa_cadastro_peca', 8, 2)->default(1.80)->after('prefixo_codigo');
            $table->decimal('comissao_porcentagem', 5, 2)->default(20.00)->after('taxa_cadastro_peca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brechos', function (Blueprint $table) {
            $table->dropColumn(['prefixo_codigo', 'taxa_cadastro_peca', 'comissao_porcentagem']);
        });
    }
};
