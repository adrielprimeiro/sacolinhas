<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verificar se as colunas já existem antes de adicionar
            if (!Schema::hasColumn('users', 'codigo_cliente')) {
                $table->integer('codigo_cliente')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'data_cadastro')) {
                $table->timestamp('data_cadastro')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'nome_cliente')) {
                $table->string('nome_cliente')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'apelido')) {
                $table->string('apelido')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'rg')) {
                $table->string('rg')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'endereco')) {
                $table->string('endereco')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'numero_endereco')) {
                $table->string('numero_endereco')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'complemento')) {
                $table->string('complemento')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'bairro')) {
                $table->string('bairro')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'cidade')) {
                $table->string('cidade')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'estado')) {
                $table->string('estado', 2)->nullable();
            }
            
            if (!Schema::hasColumn('users', 'cep')) {
                $table->string('cep', 10)->nullable();
            }
            
            if (!Schema::hasColumn('users', 'pais')) {
                $table->string('pais')->default('Brasil')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'telefone_principal')) {
                $table->string('telefone_principal')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'telefone_2')) {
                $table->string('telefone_2')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'ultima_compra')) {
                $table->timestamp('ultima_compra')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'ultima_visita')) {
                $table->timestamp('ultima_visita')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'total_pedidos')) {
                $table->integer('total_pedidos')->default(0);
            }
            
            if (!Schema::hasColumn('users', 'observacao_cliente')) {
                $table->text('observacao_cliente')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'tipo_cliente')) {
                $table->string('tipo_cliente')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'data_nascimento')) {
                $table->date('data_nascimento')->nullable();
            }
            
            if (!Schema::hasColumn('users', 'bloqueado')) {
                $table->boolean('bloqueado')->default(false);
            }
            
            if (!Schema::hasColumn('users', 'sexo')) {
                $table->enum('sexo', ['M', 'F', 'Outro'])->nullable();
            }
        });

        // Adicionar índices
        Schema::table('users', function (Blueprint $table) {
            $table->index('codigo_cliente');
            $table->index('data_cadastro');
            $table->index('ultima_compra');
            $table->index('ultima_visita');
            $table->index('tipo_cliente');
            $table->index('bloqueado');
            $table->index(['cidade', 'estado']);
        });

        // Adicionar constraint unique para CPF
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cpf')) {
                $table->unique('cpf', 'users_cpf_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_cpf_unique');
            
            $table->dropIndex(['codigo_cliente']);
            $table->dropIndex(['data_cadastro']);
            $table->dropIndex(['ultima_compra']);
            $table->dropIndex(['ultima_visita']);
            $table->dropIndex(['tipo_cliente']);
            $table->dropIndex(['bloqueado']);
            $table->dropIndex(['cidade', 'estado']);
        });

        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
                'codigo_cliente', 'data_cadastro', 'nome_cliente', 'apelido',
                'rg', 'cpf', 'endereco', 'numero_endereco', 'complemento',
                'bairro', 'cidade', 'estado', 'cep', 'pais',
                'telefone_principal', 'telefone_2', 'ultima_compra',
                'ultima_visita', 'total_pedidos', 'observacao_cliente',
                'tipo_cliente', 'data_nascimento', 'bloqueado', 'sexo',
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};