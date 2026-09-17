<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            // Expande o enum da coluna 'role' para incluir 'brecho_admin'
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('client', 'admin', 'admin_master', 'brecho_admin') NOT NULL DEFAULT 'client'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('client', 'admin', 'admin_master') NOT NULL DEFAULT 'client'");
        }
    }
};
