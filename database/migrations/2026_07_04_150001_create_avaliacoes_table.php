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
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo_compra')->default('avaliados'); // 'avaliados', 'direta'
            $table->string('tipo_cliente')->default('fora_clube'); // 'clube', 'fora_clube'
            $table->decimal('frete', 10, 2)->default(0.00);
            $table->string('pagamento_escolhido')->default('pendente'); // 'credito', 'dinheiro', 'pendente'
            $table->decimal('total_venda', 10, 2)->default(0.00);
            $table->decimal('total_payout', 10, 2)->default(0.00);
            $table->string('status')->default('rascunho'); // 'rascunho', 'finalizada', 'cancelada'
            $table->dateTime('data_avaliacao');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes');
    }
};
