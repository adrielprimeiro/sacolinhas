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
        Schema::create('evolution_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('instance_name', 100)->default('principal')->index();
            $table->enum('direction', ['inbound', 'outbound'])->index();
            $table->string('status', 50)->default('sent')->index(); // sent, delivered, read, received, failed
            $table->string('message_id', 150)->nullable()->unique();
            $table->string('from', 50)->nullable();
            $table->string('to', 50)->nullable();
            $table->text('body')->nullable();
            $table->string('media_type', 50)->nullable(); // image, video, audio, document
            $table->text('media_url')->nullable();
            $table->string('failed_reason', 255)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_messages');
    }
};
