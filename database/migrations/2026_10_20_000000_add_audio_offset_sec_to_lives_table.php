<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lives', function (Blueprint $table) {
            if (!Schema::hasColumn('lives', 'audio_offset_sec')) {
                $table->decimal('audio_offset_sec', 5, 2)->default(0.00)->nullable()->after('recording_duration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lives', function (Blueprint $table) {
            if (Schema::hasColumn('lives', 'audio_offset_sec')) {
                $table->dropColumn('audio_offset_sec');
            }
        });
    }
};
