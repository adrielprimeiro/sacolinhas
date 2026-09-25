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
        Schema::create('obs_relay_commands', function (Blueprint $table) {
            $table->id();
            $table->string('command_type');          // StartRecord | StopRecord
            $table->json('payload')->nullable();     // dados extras (item_id, live_id etc)
            $table->string('status')->default('pending'); // pending | processing | done | error
            $table->string('agent_id')->nullable();  // identificador do agente no PC A
            $table->text('result')->nullable();      // resposta do OBS (outputPath etc)
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obs_relay_commands');
    }
};
