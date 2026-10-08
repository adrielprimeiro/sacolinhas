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
        DB::statement("ALTER TABLE lives MODIFY COLUMN tipo_live VARCHAR(50) NOT NULL DEFAULT 'loja-aberta'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE lives MODIFY COLUMN tipo_live ENUM('loja-aberta','leilao','precinho') NOT NULL DEFAULT 'loja-aberta'");
    }
};
