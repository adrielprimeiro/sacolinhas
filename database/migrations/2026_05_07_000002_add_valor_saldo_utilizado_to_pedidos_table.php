<?php
/*
 * (c) Adriel Primeiro
 */

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
        Schema::table('pedidos', function (Blueprint $column) {
            $column->decimal('valor_saldo_utilizado', 10, 2)->default(0.00)->after('valor_desconto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $column) {
            $column->dropColumn('valor_saldo_utilizado');
        });
    }
};
