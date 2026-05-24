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
        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'valor_frete_real')) {
                $table->decimal('valor_frete_real', 10, 2)->nullable()->after('valor_frete');
            }
        });

        // Atualizar ENUM para conter os novos status
        DB::statement("ALTER TABLE pedidos MODIFY COLUMN status_pedido ENUM('pendente', 'confirmado', 'processando', 'embalado', 'enviado', 'entregue', 'pago', 'concluido', 'cancelado') NOT NULL DEFAULT 'pendente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos', 'valor_frete_real')) {
                $table->dropColumn('valor_frete_real');
            }
        });

        // Tentar reverter (risco de perder status 'embalado', mas para safety mantemos uma versão limpa)
        DB::statement("ALTER TABLE pedidos MODIFY COLUMN status_pedido ENUM('pendente', 'confirmado', 'processando', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente'");
    }
};
