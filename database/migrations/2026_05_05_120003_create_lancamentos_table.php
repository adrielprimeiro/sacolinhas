<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['receita', 'despesa']);
            $table->enum('status', ['pendente', 'pago_parcial', 'pago', 'cancelado'])
                  ->default('pendente');
            $table->foreignId('pessoa_id')
                  ->constrained('pessoas')
                  ->restrictOnDelete();
            $table->unsignedBigInteger('classificacao_financeira_id');
            $table->foreign('classificacao_financeira_id')
                  ->references('id')
                  ->on('classificacao_financeira')
                  ->restrictOnDelete();
            $table->date('data_emissao');
            $table->date('data_vencimento');
            $table->decimal('valor_total', 15, 2);
            $table->string('descricao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
