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
                if (!Schema::hasColumn('live_items', 'tiktok_publish_id')) {
                    $table->string('tiktok_publish_id', 150)->nullable()->after('youtube_error');
                }
                if (!Schema::hasColumn('live_items', 'tiktok_url')) {
                    $table->string('tiktok_url', 500)->nullable()->after('tiktok_publish_id');
                }
                if (!Schema::hasColumn('live_items', 'tiktok_status')) {
                    $table->string('tiktok_status', 50)->default('none')->after('tiktok_url'); // 'none', 'queued', 'uploading', 'published', 'error'
                }
                if (!Schema::hasColumn('live_items', 'tiktok_published_at')) {
                    $table->timestamp('tiktok_published_at')->nullable()->after('tiktok_status');
                }
                if (!Schema::hasColumn('live_items', 'tiktok_error')) {
                    $table->text('tiktok_error')->nullable()->after('tiktok_published_at');
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
                $cols = ['tiktok_publish_id', 'tiktok_url', 'tiktok_status', 'tiktok_published_at', 'tiktok_error'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('live_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
