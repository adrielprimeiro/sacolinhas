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
        if (!Schema::hasTable('social_channel_accounts')) {
            Schema::create('social_channel_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->default('youtube')->index(); // 'youtube', 'instagram', 'tiktok'
                $table->string('channel_id', 150)->nullable();
                $table->string('channel_name', 255)->nullable();
                $table->string('channel_avatar', 500)->nullable();
                $table->text('access_token')->nullable();
                $table->text('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->json('settings')->nullable(); // Configurações personalizadas (ex: auto_mark_sold, playlist_id, default_privacy)
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                if (!Schema::hasColumn('live_items', 'youtube_video_id')) {
                    $table->string('youtube_video_id', 100)->nullable()->after('video_cut_status');
                }
                if (!Schema::hasColumn('live_items', 'youtube_url')) {
                    $table->string('youtube_url', 500)->nullable()->after('youtube_video_id');
                }
                if (!Schema::hasColumn('live_items', 'youtube_status')) {
                    $table->string('youtube_status', 50)->default('none')->after('youtube_url'); // 'none', 'queued', 'uploading', 'published', 'sold_updated', 'error'
                }
                if (!Schema::hasColumn('live_items', 'youtube_published_at')) {
                    $table->timestamp('youtube_published_at')->nullable()->after('youtube_status');
                }
                if (!Schema::hasColumn('live_items', 'youtube_error')) {
                    $table->text('youtube_error')->nullable()->after('youtube_published_at');
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
                $cols = ['youtube_video_id', 'youtube_url', 'youtube_status', 'youtube_published_at', 'youtube_error'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('live_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('social_channel_accounts');
    }
};
