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
        // 1. Campos na tabela lives para gravação bruta e transcrição
        if (Schema::hasTable('lives')) {
            Schema::table('lives', function (Blueprint $table) {
                if (!Schema::hasColumn('lives', 'recording_path')) {
                    $table->string('recording_path', 500)->nullable()->after('encerrada_em');
                }
                if (!Schema::hasColumn('lives', 'recording_filename')) {
                    $table->string('recording_filename', 255)->nullable()->after('recording_path');
                }
                if (!Schema::hasColumn('lives', 'recording_duration')) {
                    $table->integer('recording_duration')->nullable()->after('recording_filename');
                }
                if (!Schema::hasColumn('lives', 'transcription_raw')) {
                    $table->longText('transcription_raw')->nullable()->after('recording_duration');
                }
                if (!Schema::hasColumn('lives', 'transcription_status')) {
                    $table->string('transcription_status', 50)->default('none')->after('transcription_raw');
                }
            });
        }

        // 2. Campos na tabela live_items para minutagem de início/fim e corte
        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                if (!Schema::hasColumn('live_items', 'cut_start_sec')) {
                    $table->double('cut_start_sec', 8, 2)->nullable()->after('video_cut_trigger');
                }
                if (!Schema::hasColumn('live_items', 'cut_end_sec')) {
                    $table->double('cut_end_sec', 8, 2)->nullable()->after('cut_start_sec');
                }
                if (!Schema::hasColumn('live_items', 'transcription_snippet')) {
                    $table->text('transcription_snippet')->nullable()->after('cut_end_sec');
                }
                if (!Schema::hasColumn('live_items', 'video_cut_url')) {
                    $table->string('video_cut_url', 500)->nullable()->after('transcription_snippet');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lives')) {
            Schema::table('lives', function (Blueprint $table) {
                $cols = ['recording_path', 'recording_filename', 'recording_duration', 'transcription_raw', 'transcription_status'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('lives', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }

        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                $cols = ['cut_start_sec', 'cut_end_sec', 'transcription_snippet', 'video_cut_url'];
                foreach ($cols as $c) {
                    if (Schema::hasColumn('live_items', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
