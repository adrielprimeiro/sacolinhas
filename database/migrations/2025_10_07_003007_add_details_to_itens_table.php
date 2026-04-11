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
        Schema::table('items', function (Blueprint $table) {
            // Renomear colunas existentes
            $table->renameColumn('name', 'nome_do_produto');
            $table->renameColumn('description', 'descricao');
            $table->renameColumn('price', 'preco');
            $table->renameColumn('category', 'codigo_da_categoria');

            // Adicionar as novas colunas
            $table->string('codigo')->unique()->after('id');
            $table->decimal('custo', 8, 2)->nullable()->after('descricao');
            $table->string('marca')->nullable()->after('codigo_da_categoria');
            $table->string('modelo')->nullable()->after('marca');
            $table->string('estado')->default('novo')->after('modelo');
            $table->string('cor')->nullable()->after('estado');
            $table->string('tamanho')->nullable()->after('cor');
            $table->string('pedido')->nullable()->after('preco');
            
            // Alterar status para novos valores
            $table->string('status')->default('disponivel')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            // Remover as colunas adicionadas
            $table->dropColumn([
                'codigo',
                'custo',
                'marca',
                'modelo',
                'estado',
                'cor',
                'tamanho',
                'pedido',
            ]);

            // Renomear colunas de volta
            $table->renameColumn('nome_do_produto', 'name');
            $table->renameColumn('descricao', 'description');
            $table->renameColumn('preco', 'price');
            $table->renameColumn('codigo_da_categoria', 'category');
        });
    }
};