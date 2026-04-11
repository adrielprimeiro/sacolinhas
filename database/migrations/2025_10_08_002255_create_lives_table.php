<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lives', function (Blueprint $table) {
            $table->id();
            $table->date('data');
            $table->enum('tipo_live', ['loja-aberta', 'leilao', 'precinho']);
            $table->string('plataformas'); // Armazenar como string separada por vírgula
            $table->timestamps();
            
            // Índices
            $table->index('data');
            $table->index('tipo_live');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lives');
    }
};