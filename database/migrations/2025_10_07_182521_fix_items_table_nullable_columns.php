<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Tornar as colunas nullable
            $table->string('codigo_da_categoria')->nullable()->change();
            $table->string('marca')->nullable()->change();
            $table->string('modelo')->nullable()->change();
            $table->string('cor')->nullable()->change();
            $table->string('tamanho')->nullable()->change();
            $table->string('pedido')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            // Reverter para NOT NULL (se necessário)
            $table->string('codigo_da_categoria')->nullable(false)->change();
            $table->string('marca')->nullable(false)->change();
            $table->string('modelo')->nullable(false)->change();
            $table->string('cor')->nullable(false)->change();
            $table->string('tamanho')->nullable(false)->change();
            $table->string('pedido')->nullable(false)->change();
        });
    }
};