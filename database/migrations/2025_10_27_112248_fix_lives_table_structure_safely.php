<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Primeiro, dropar foreign key da tabela sacolinhas
        if (Schema::hasTable('sacolinhas')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                try {
                    $table->dropForeign(['live_id']);
                } catch (Exception $e) {
                    // Ignorar se não existir
                }
            });
        }

        // Modificar tabela lives
        Schema::table('lives', function (Blueprint $table) {
            // Remover colunas desnecessárias se existirem
            $columns_to_drop = [];
            if (Schema::hasColumn('lives', 'nome')) $columns_to_drop[] = 'nome';
            if (Schema::hasColumn('lives', 'descricao')) $columns_to_drop[] = 'descricao';
            if (Schema::hasColumn('lives', 'preco')) $columns_to_drop[] = 'preco';
            if (Schema::hasColumn('lives', 'quantidade')) $columns_to_drop[] = 'quantidade';
            if (Schema::hasColumn('lives', 'ativo')) $columns_to_drop[] = 'ativo';

            if (!empty($columns_to_drop)) {
                $table->dropColumn($columns_to_drop);
            }

            // Adicionar colunas corretas se não existirem
            if (!Schema::hasColumn('lives', 'tipo_live')) {
                $table->enum('tipo_live', ['loja-aberta', 'leilao', 'precinho'])
                      ->after('data')
                      ->comment('Tipo da transmissão');
            }

            if (!Schema::hasColumn('lives', 'plataformas')) {
                $table->text('plataformas')
                      ->after('tipo_live')
                      ->comment('Plataformas separadas por vírgula');
            }
        });

        // Recriar foreign key na tabela sacolinhas
        if (Schema::hasTable('sacolinhas') && Schema::hasColumn('sacolinhas', 'live_id')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                $table->foreign('live_id')->references('id')->on('lives')->onDelete('cascade');
            });
        }

        // Adicionar índices - verificar se já existe
        $indexes = DB::select("SHOW INDEXES FROM lives WHERE Key_name = 'lives_tipo_live_index'");
        if (empty($indexes)) {
            DB::statement('ALTER TABLE lives ADD INDEX lives_tipo_live_index (tipo_live)');
        }
    }

    public function down()
    {
        // Dropar foreign key
        if (Schema::hasTable('sacolinhas')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                try {
                    $table->dropForeign(['live_id']);
                } catch (Exception $e) {
                    // Ignorar se não existir
                }
            });
        }

        // Reverter mudanças na tabela lives
        Schema::table('lives', function (Blueprint $table) {
            $table->dropColumn(['tipo_live', 'plataformas']);

            // Readicionar colunas antigas
            $table->string('nome')->nullable();
            $table->text('descricao')->nullable();
            $table->decimal('preco', 8, 2)->nullable();
            $table->integer('quantidade')->default(0);
            $table->boolean('ativo')->default(true);
        });

        // Recriar foreign key
        if (Schema::hasTable('sacolinhas') && Schema::hasColumn('sacolinhas', 'live_id')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                $table->foreign('live_id')->references('id')->on('lives')->onDelete('cascade');
            });
        }
    }
};
