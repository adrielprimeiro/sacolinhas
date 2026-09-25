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
        if (Schema::hasTable('live_items') && !Schema::hasColumn('live_items', 'codigo_live')) {
            Schema::table('live_items', function (Blueprint $table) {
                $table->string('codigo_live', 100)->nullable()->after('item_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_items') && Schema::hasColumn('live_items', 'codigo_live')) {
            Schema::table('live_items', function (Blueprint $table) {
                $table->dropColumn('codigo_live');
            });
        }
    }
};
