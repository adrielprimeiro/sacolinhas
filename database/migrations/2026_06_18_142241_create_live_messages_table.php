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
        Schema::create('live_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained('lives')->onDelete('cascade');
            $table->enum('plataforma', ['instagram', 'tiktok']);
            $table->string('username');
            $table->text('message');
            $table->dateTime('captured_at')->nullable();
            $table->timestamps();

            // Index
            $table->index('live_id');
            $table->index('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_messages');
    }
};
