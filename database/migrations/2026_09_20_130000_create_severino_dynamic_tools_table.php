<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('severino_dynamic_tools', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->text('descricao');
            $table->string('modulo_area', 50)->default('geral');
            $table->json('parametros')->nullable();
            $table->text('sql_template');
            $table->string('created_by', 50)->default('severino');
            $table->boolean('ativo')->default(true);
            $table->text('exemplos_uso')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('severino_dynamic_tools');
    }
};
