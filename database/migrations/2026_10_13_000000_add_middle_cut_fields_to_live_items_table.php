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
        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                if (!Schema::hasColumn('live_items', 'middle_cut_start_sec')) {
                    $table->double('middle_cut_start_sec', 8, 2)->nullable()->after('cut_end_sec');
                }
                if (!Schema::hasColumn('live_items', 'middle_cut_end_sec')) {
                    $table->double('middle_cut_end_sec', 8, 2)->nullable()->after('middle_cut_start_sec');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                if (Schema::hasColumn('live_items', 'middle_cut_start_sec')) {
                    $table->dropColumn('middle_cut_start_sec');
                }
                if (Schema::hasColumn('live_items', 'middle_cut_end_sec')) {
                    $table->dropColumn('middle_cut_end_sec');
                }
            });
        }
    }
};
