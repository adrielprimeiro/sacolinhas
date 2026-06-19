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
        Schema::create('live_code_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained('lives')->onDelete('cascade');
            $table->foreignId('live_message_id')->constrained('live_messages')->onDelete('cascade');
            $table->string('username');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('codigo');
            $table->text('message_text');
            $table->enum('status', ['pending', 'added', 'ignored'])->default('pending');
            $table->timestamps();

            // Índices
            $table->index('live_id');
            $table->index('status');
            $table->index(['live_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_code_requests');
    }
};
