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
        Schema::create('conferencias_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('localizacao');
            $table->string('status_aplicado')->nullable();
            $table->string('cor_aplicada')->nullable();
            $table->integer('total_esperado')->default(0);
            $table->integer('total_lido')->default(0);
            $table->integer('total_encontrados')->default(0);
            $table->integer('total_faltantes')->default(0);
            $table->integer('total_sobrando')->default(0);
            $table->decimal('acuracia_percentual', 5, 2)->default(0.00);
            $table->json('detalhes_json')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conferencias_inventario');
    }
};
