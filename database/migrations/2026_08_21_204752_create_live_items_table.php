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
        Schema::create('live_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained('lives')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('localizacao_origem')->nullable();
            $table->enum('status_movimentacao', ['enviado', 'retornado'])->default('enviado');
            $table->timestamps();
            
            $table->unique(['live_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_items');
    }
};
