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
        Schema::create('categorias', function (Blueprint $col) {
            $col->id();
            $col->string('name');
            $col->string('slug')->unique();
            $col->foreignId('parent_id')->nullable()->constrained('categorias')->onDelete('cascade');
            
            // Campos de desconto
            $col->decimal('valor_desconto', 10, 2)->default(0);
            $col->enum('tipo_desconto', ['porcentagem', 'fixo'])->default('porcentagem');
            
            $col->timestamps();
        });

        Schema::create('categoria_item', function (Blueprint $col) {
            $col->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $col->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $col->primary(['categoria_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_item');
        Schema::dropIfExists('categorias');
    }
};
