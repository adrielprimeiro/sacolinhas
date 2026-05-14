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
        \DB::statement("ALTER TABLE conta_corrente MODIFY COLUMN referencia_tipo ENUM('sacolinha','pagamento','pedido','ajuste','desconto', 'movimentacao')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE conta_corrente MODIFY COLUMN referencia_tipo ENUM('sacolinha','pagamento','pedido','ajuste','desconto')");
    }
};
