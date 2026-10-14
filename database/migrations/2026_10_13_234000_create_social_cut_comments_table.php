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
        if (Schema::hasTable('youtube_comments') && !Schema::hasTable('social_cut_comments')) {
            // Renomeia a tabela existente para acomodar todas as redes sociais (YouTube, Instagram, TikTok)
            Schema::rename('youtube_comments', 'social_cut_comments');

            Schema::table('social_cut_comments', function (Blueprint $table) {
                if (!Schema::hasColumn('social_cut_comments', 'platform')) {
                    $table->string('platform', 50)->default('youtube')->after('id')->index();
                }
            });

            // Ajustar índice único para comportar múltiplas plataformas
            try {
                Schema::table('social_cut_comments', function (Blueprint $table) {
                    $table->dropUnique(['comment_id']);
                });
            } catch (\Throwable $e) {
                // Se o índice não existir ou tiver outro nome, segue adiante
            }

            try {
                Schema::table('social_cut_comments', function (Blueprint $table) {
                    $table->unique(['platform', 'comment_id']);
                });
            } catch (\Throwable $e) {
                // Já existe
            }
        } elseif (!Schema::hasTable('social_cut_comments')) {
            Schema::create('social_cut_comments', function (Blueprint $table) {
                $table->id();
                $table->string('platform', 50)->default('youtube')->index();
                $table->string('comment_id', 150);
                $table->string('video_id', 150)->index();
                $table->unsignedBigInteger('live_item_id')->nullable()->index();
                $table->unsignedBigInteger('live_id')->nullable()->index();
                $table->string('author_name', 150);
                $table->string('author_channel_id', 150)->nullable();
                $table->string('author_profile_image', 500)->nullable();
                $table->text('comment_text');
                $table->boolean('is_purchase_intent')->default(false)->index();
                $table->string('detected_keyword', 100)->nullable();
                $table->boolean('reply_sent')->default(false)->index();
                $table->text('reply_text')->nullable();
                $table->timestamp('reply_sent_at')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['platform', 'comment_id']);
            });

            // Se ainda existir dados em youtube_comments, copiar para a nova tabela unificada
            if (Schema::hasTable('youtube_comments')) {
                DB::statement("
                    INSERT IGNORE INTO social_cut_comments (platform, comment_id, video_id, live_item_id, live_id, author_name, author_channel_id, author_profile_image, comment_text, is_purchase_intent, detected_keyword, reply_sent, reply_text, reply_sent_at, published_at, created_at, updated_at)
                    SELECT 'youtube', comment_id, video_id, live_item_id, live_id, author_name, author_channel_id, author_profile_image, comment_text, is_purchase_intent, detected_keyword, reply_sent, reply_text, reply_sent_at, published_at, created_at, updated_at
                    FROM youtube_comments
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Preserva a tabela para não perder comentários gravados
    }
};
