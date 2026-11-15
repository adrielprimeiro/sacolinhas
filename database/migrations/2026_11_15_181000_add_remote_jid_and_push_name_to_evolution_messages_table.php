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
        Schema::table('evolution_messages', function (Blueprint $table) {
            $table->string('remote_jid', 100)->nullable()->after('instance_name')->index();
            $table->string('push_name', 150)->nullable()->after('remote_jid');
        });

        // Backfill existing records from raw_payload
        $messages = DB::table('evolution_messages')->whereNotNull('raw_payload')->get();
        foreach ($messages as $msg) {
            $payload = json_decode($msg->raw_payload, true);
            if (!is_array($payload)) {
                continue;
            }

            $remoteJid = $payload['key']['remoteJid'] ?? null;
            $pushName = $payload['pushName'] ?? null;

            if ($remoteJid || $pushName) {
                DB::table('evolution_messages')
                    ->where('id', $msg->id)
                    ->update([
                        'remote_jid' => $remoteJid,
                        'push_name' => $pushName,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolution_messages', function (Blueprint $table) {
            $table->dropColumn(['remote_jid', 'push_name']);
        });
    }
};
