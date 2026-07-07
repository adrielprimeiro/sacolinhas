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
        Schema::create('avaliacao_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avaliacao_id')->constrained('avaliacoes')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->string('nome');
            $table->string('marca')->default('sem_marca'); // 'sem_marca', 'de_marca', 'farm'
            $table->integer('estado')->default(10);
            $table->integer('nota_curadoria')->default(10);
            $table->decimal('taxa_curadoria', 10, 2)->default(0.00);
            $table->decimal('preco_base', 10, 2)->default(0.00);
            $table->decimal('preco_venda', 10, 2)->default(0.00);
            $table->decimal('payout_credito', 10, 2)->default(0.00);
            $table->decimal('payout_dinheiro', 10, 2)->default(0.00);
            $table->string('cor')->nullable();
            $table->string('tamanho')->nullable();
            $table->foreignId('item_id')->nullable()->constrained('items')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacao_items');
    }
};
