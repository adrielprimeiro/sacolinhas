<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lancamento_id')
                  ->constrained('lancamentos')
                  ->cascadeOnDelete();
            $table->foreignId('conta_bancaria_id')
                  ->constrained('contas_bancarias')
                  ->restrictOnDelete();
            $table->date('data_pagamento');
            $table->decimal('valor_pago', 15, 2);
            $table->enum('forma_pagamento', ['pix', 'boleto', 'cartao_credito', 'dinheiro', 'transferencia']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
