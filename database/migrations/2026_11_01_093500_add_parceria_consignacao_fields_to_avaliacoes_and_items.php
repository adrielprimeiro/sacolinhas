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
        // 1. Adicionar campos na tabela de avaliacoes
        Schema::table('avaliacoes', function (Blueprint $table) {
            if (!Schema::hasColumn('avaliacoes', 'prefixo_codigo')) {
                $table->string('prefixo_codigo', 10)->nullable()->after('tipo_compra');
            }
            if (!Schema::hasColumn('avaliacoes', 'parceiro_id')) {
                $table->unsignedBigInteger('parceiro_id')->nullable()->after('prefixo_codigo');
            }
            if (!Schema::hasColumn('avaliacoes', 'taxa_cadastro_peca')) {
                $table->decimal('taxa_cadastro_peca', 8, 2)->default(0.00)->after('parceiro_id');
            }
            if (!Schema::hasColumn('avaliacoes', 'comissao_parceiro_porcentagem')) {
                $table->decimal('comissao_parceiro_porcentagem', 5, 2)->default(20.00)->after('taxa_cadastro_peca');
            }
        });

        // 2. Adicionar parceiro_id na tabela items para rastreabilidade direta do estoque
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'parceiro_id')) {
                $table->unsignedBigInteger('parceiro_id')->nullable()->after('brecho_id');
            }
            if (!Schema::hasColumn('items', 'origem_parceiro')) {
                $table->string('origem_parceiro', 100)->nullable()->after('parceiro_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->dropColumn(['prefixo_codigo', 'parceiro_id', 'taxa_cadastro_peca', 'comissao_parceiro_porcentagem']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['parceiro_id', 'origem_parceiro']);
        });
    }
};
