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
        if (Schema::hasTable('live_messages') && !Schema::hasColumn('live_messages', 'host_account')) {
            Schema::table('live_messages', function (Blueprint $table) {
                $table->string('host_account', 100)->nullable()->after('plataforma')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_messages') && Schema::hasColumn('live_messages', 'host_account')) {
            Schema::table('live_messages', function (Blueprint $table) {
                $table->dropColumn('host_account');
            });
        }
    }
};
