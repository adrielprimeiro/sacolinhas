<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brechos', function (Blueprint $table) {
            $table->string('fornecedor_id', 50)->nullable()->after('comissao_porcentagem');
            $table->string('fornecedor_nome', 255)->nullable()->after('fornecedor_id');
        });
    }

    public function down(): void
    {
        Schema::table('brechos', function (Blueprint $table) {
            $table->dropColumn(['fornecedor_id', 'fornecedor_nome']);
        });
    }
};
