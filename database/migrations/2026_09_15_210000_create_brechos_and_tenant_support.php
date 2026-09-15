<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabela de Brechós (Lojas / Parceiros)
        if (!Schema::hasTable('brechos')) {
            Schema::create('brechos', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('slug')->unique();
                $table->string('documento')->nullable();
                $table->string('telefone')->nullable();
                $table->string('whatsapp')->nullable();
                $table->string('chave_pix')->nullable();
                $table->string('tipo_chave_pix')->nullable();
                $table->boolean('ativo')->default(true);
                $table->json('configuracoes')->nullable();
                $table->timestamps();
            });

            // Insere o brechó principal default (ID 1)
            DB::table('brechos')->insert([
                'id' => 1,
                'nome' => 'Brechó Matriz',
                'slug' => 'matriz',
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Adiciona brecho_id na tabela users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'brecho_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('role');
                $table->foreign('brecho_id')->references('id')->on('brechos')->nullOnDelete();
            });
        }

        // 3. Adiciona brecho_id na tabela items
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'brecho_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('id');
                $table->foreign('brecho_id')->references('id')->on('brechos')->cascadeOnDelete();
                $table->index('brecho_id');
            });
            // Vincula todos os itens existentes ao Brechó Matriz
            DB::table('items')->whereNull('brecho_id')->update(['brecho_id' => 1]);
        }

        // 4. Adiciona brecho_id na tabela lives
        if (Schema::hasTable('lives') && !Schema::hasColumn('lives', 'brecho_id')) {
            Schema::table('lives', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('id');
                $table->foreign('brecho_id')->references('id')->on('brechos')->cascadeOnDelete();
                $table->index('brecho_id');
            });
            DB::table('lives')->whereNull('brecho_id')->update(['brecho_id' => 1]);
        }

        // 5. Adiciona brecho_id na tabela sacolinhas
        if (Schema::hasTable('sacolinhas') && !Schema::hasColumn('sacolinhas', 'brecho_id')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('id');
                $table->foreign('brecho_id')->references('id')->on('brechos')->cascadeOnDelete();
                $table->index('brecho_id');
            });
            DB::table('sacolinhas')->whereNull('brecho_id')->update(['brecho_id' => 1]);
        }

        // 6. Adiciona brecho_id na tabela pedidos
        if (Schema::hasTable('pedidos') && !Schema::hasColumn('pedidos', 'brecho_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('id');
                $table->foreign('brecho_id')->references('id')->on('brechos')->cascadeOnDelete();
                $table->index('brecho_id');
            });
            DB::table('pedidos')->whereNull('brecho_id')->update(['brecho_id' => 1]);
        }

        // 7. Tabela de relacionamento Brechó - Clientes
        if (!Schema::hasTable('brecho_clientes')) {
            Schema::create('brecho_clientes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('brecho_id');
                $table->unsignedBigInteger('user_id');
                $table->string('origem')->nullable()->default('live');
                $table->timestamps();

                $table->foreign('brecho_id')->references('id')->on('brechos')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['brecho_id', 'user_id']);
            });

            // Popula automaticamente com os clientes existentes vinculando à Matriz
            $existingClients = DB::table('users')->where('role', 'client')->pluck('id');
            $now = now();
            $clientInserts = [];
            foreach ($existingClients as $clientId) {
                $clientInserts[] = [
                    'brecho_id' => 1,
                    'user_id' => $clientId,
                    'origem' => 'legado',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($clientInserts)) {
                foreach (array_chunk($clientInserts, 500) as $chunk) {
                    DB::table('brecho_clientes')->insertOrIgnore($chunk);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brecho_clientes');

        if (Schema::hasTable('pedidos') && Schema::hasColumn('pedidos', 'brecho_id')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }

        if (Schema::hasTable('sacolinhas') && Schema::hasColumn('sacolinhas', 'brecho_id')) {
            Schema::table('sacolinhas', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }

        if (Schema::hasTable('lives') && Schema::hasColumn('lives', 'brecho_id')) {
            Schema::table('lives', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }

        if (Schema::hasTable('items') && Schema::hasColumn('items', 'brecho_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'brecho_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }

        Schema::dropIfExists('brechos');
    }
};
