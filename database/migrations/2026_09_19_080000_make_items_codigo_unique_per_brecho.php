<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasCodigoUnique = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_codigo_unique'"))->isNotEmpty();
        if ($hasCodigoUnique) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropUnique('items_codigo_unique');
            });
        }

        $hasBrechoCodigoUnique = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_brecho_codigo_unique'"))->isNotEmpty();
        if (!$hasBrechoCodigoUnique) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique(['brecho_id', 'codigo'], 'items_brecho_codigo_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasBrechoCodigoUnique = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_brecho_codigo_unique'"))->isNotEmpty();
        if ($hasBrechoCodigoUnique) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropUnique('items_brecho_codigo_unique');
            });
        }

        $hasCodigoUnique = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_codigo_unique'"))->isNotEmpty();
        if (!$hasCodigoUnique) {
            Schema::table('items', function (Blueprint $table) {
                $table->unique('codigo', 'items_codigo_unique');
            });
        }
    }
};
