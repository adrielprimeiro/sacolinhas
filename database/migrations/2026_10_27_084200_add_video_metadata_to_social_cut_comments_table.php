<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('social_cut_comments')) {
            Schema::table('social_cut_comments', function (Blueprint $table) {
                if (!Schema::hasColumn('social_cut_comments', 'video_url')) {
                    $table->string('video_url', 500)->nullable()->after('video_id');
                }
                if (!Schema::hasColumn('social_cut_comments', 'video_title')) {
                    $table->string('video_title', 500)->nullable()->after('video_url');
                }
                if (!Schema::hasColumn('social_cut_comments', 'video_thumbnail')) {
                    $table->text('video_thumbnail')->nullable()->after('video_title');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('social_cut_comments')) {
            Schema::table('social_cut_comments', function (Blueprint $table) {
                $cols = [];
                foreach (['video_url', 'video_title', 'video_thumbnail'] as $col) {
                    if (Schema::hasColumn('social_cut_comments', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
