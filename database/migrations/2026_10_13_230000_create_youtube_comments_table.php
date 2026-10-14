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
        if (!Schema::hasTable('youtube_comments')) {
            Schema::create('youtube_comments', function (Blueprint $table) {
                $table->id();
                $table->string('comment_id', 100)->unique();
                $table->string('video_id', 100)->index();
                $table->unsignedBigInteger('live_item_id')->nullable()->index();
                $table->unsignedBigInteger('live_id')->nullable()->index();
                $table->string('author_name', 255)->default('');
                $table->string('author_channel_id', 100)->nullable();
                $table->string('author_profile_image', 500)->nullable();
                $table->text('comment_text');
                $table->boolean('is_purchase_intent')->default(false)->index();
                $table->string('detected_keyword', 100)->nullable();
                $table->boolean('reply_sent')->default(false);
                $table->text('reply_text')->nullable();
                $table->timestamp('reply_sent_at')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_comments');
    }
};
