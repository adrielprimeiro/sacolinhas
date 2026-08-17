<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('raw_prompt');
            $table->string('category')->nullable()->index();
            $table->string('size')->nullable()->index();
            $table->decimal('max_price', 10, 2)->nullable()->index();
            $table->json('parsed_attributes')->nullable();
            $table->enum('status', ['active', 'matched', 'expired', 'fulfilled'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_wishes');
    }
};
