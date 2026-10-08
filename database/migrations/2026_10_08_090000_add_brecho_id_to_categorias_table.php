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
        if (Schema::hasTable('categorias') && !Schema::hasColumn('categorias', 'brecho_id')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->unsignedBigInteger('brecho_id')->nullable()->after('id');
                $table->foreign('brecho_id')->references('id')->on('brechos')->nullOnDelete();
                $table->index('brecho_id');
            });

            // 1. As categorias que já existiam antes de implementar o Taco Balaio (IDs 1 até 140) permanecem como padrão (NULL)
            DB::table('categorias')->where('id', '<=', 140)->update(['brecho_id' => null]);

            // 2. As categorias criadas pelo Taco Balaio (a partir do ID 141) são vinculadas ao Taco Balaio (brecho_id = 2)
            DB::table('categorias')->where('id', '>=', 141)->update(['brecho_id' => 2]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('categorias') && Schema::hasColumn('categorias', 'brecho_id')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->dropForeign(['brecho_id']);
                $table->dropColumn('brecho_id');
            });
        }
    }
};
