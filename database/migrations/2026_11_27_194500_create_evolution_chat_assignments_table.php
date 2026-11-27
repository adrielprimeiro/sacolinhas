<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evolution_chat_assignments')) {
            Schema::create('evolution_chat_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('remote_jid', 120)->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->enum('assigned_type', ['none', 'admin', 'severino'])->default('none')->index();
                $table->unsignedBigInteger('assigned_admin_id')->nullable()->index();
                $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_chat_assignments');
    }
};
