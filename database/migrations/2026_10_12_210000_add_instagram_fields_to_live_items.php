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
                if (!Schema::hasColumn('live_items', 'instagram_media_id')) {
                    $table->string('instagram_media_id', 150)->nullable()->after('tiktok_error');
                }
                if (!Schema::hasColumn('live_items', 'instagram_url')) {
                    $table->string('instagram_url', 500)->nullable()->after('instagram_media_id');
                }
                if (!Schema::hasColumn('live_items', 'instagram_status')) {
                    $table->string('instagram_status', 50)->default('none')->after('instagram_url'); // 'none', 'queued', 'uploading', 'published', 'error'
                }
                if (!Schema::hasColumn('live_items', 'instagram_published_at')) {
                    $table->timestamp('instagram_published_at')->nullable()->after('instagram_status');
                }
                if (!Schema::hasColumn('live_items', 'instagram_error')) {
                    $table->text('instagram_error')->nullable()->after('instagram_published_at');
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
                $cols = ['instagram_media_id', 'instagram_url', 'instagram_status', 'instagram_published_at', 'instagram_error'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('live_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
