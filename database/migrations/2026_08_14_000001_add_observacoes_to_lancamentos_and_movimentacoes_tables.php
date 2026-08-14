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
        if (!Schema::hasColumn('lancamentos', 'observacoes')) {
            Schema::table('lancamentos', function (Blueprint $table) {
                $table->text('observacoes')->nullable()->after('descricao');
            });
        }

        if (!Schema::hasColumn('movimentacoes', 'observacoes')) {
            Schema::table('movimentacoes', function (Blueprint $table) {
                $table->text('observacoes')->nullable()->after('forma_pagamento');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('lancamentos', 'observacoes')) {
            Schema::table('lancamentos', function (Blueprint $table) {
                $table->dropColumn('observacoes');
            });
        }

        if (Schema::hasColumn('movimentacoes', 'observacoes')) {
            Schema::table('movimentacoes', function (Blueprint $table) {
                $table->dropColumn('observacoes');
            });
        }
    }
};
