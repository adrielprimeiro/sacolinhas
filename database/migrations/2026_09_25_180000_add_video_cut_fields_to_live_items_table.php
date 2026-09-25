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
                if (!Schema::hasColumn('live_items', 'video_cut_path')) {
                    $table->string('video_cut_path', 500)->nullable()->after('live_message_id');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_filename')) {
                    $table->string('video_cut_filename', 255)->nullable()->after('video_cut_path');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_duration')) {
                    $table->integer('video_cut_duration')->nullable()->after('video_cut_filename');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_status')) {
                    $table->string('video_cut_status', 50)->nullable()->after('video_cut_duration');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_started_at')) {
                    $table->timestamp('video_cut_started_at')->nullable()->after('video_cut_status');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_finished_at')) {
                    $table->timestamp('video_cut_finished_at')->nullable()->after('video_cut_started_at');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_trigger')) {
                    $table->string('video_cut_trigger', 50)->nullable()->after('video_cut_finished_at');
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
                $columns = [
                    'video_cut_path',
                    'video_cut_filename',
                    'video_cut_duration',
                    'video_cut_status',
                    'video_cut_started_at',
                    'video_cut_finished_at',
                    'video_cut_trigger'
                ];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('live_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
