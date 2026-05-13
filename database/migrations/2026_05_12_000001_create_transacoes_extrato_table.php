<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes_extrato', function (Blueprint $table) {
            $table->id();
            $table->string('fitid')->unique(); // ID original da transação (OFX ou MP)
            $table->date('data');
            $table->string('descricao');
            $table->decimal('valor', 15, 2);
            $table->enum('tipo', ['entrada', 'saida']);
            $table->enum('status', ['pendente', 'conciliado', 'ignorado'])->default('pendente');
            $table->string('origem'); // 'banco', 'mercadopago'
            $table->foreignId('conta_bancaria_id')->nullable()->constrained('contas_bancarias')->nullOnDelete();
            $table->foreignId('movimentacao_id')->nullable()->constrained('movimentacoes')->nullOnDelete();
            $table->json('payload_original')->nullable(); // Guardar o JSON bruto para auditoria
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes_extrato');
    }
};
