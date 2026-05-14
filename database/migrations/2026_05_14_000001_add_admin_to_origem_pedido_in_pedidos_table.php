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
        \DB::statement("ALTER TABLE pedidos MODIFY COLUMN origem_pedido ENUM('live','site','whatsapp','instagram','admin') NOT NULL DEFAULT 'live'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE pedidos MODIFY COLUMN origem_pedido ENUM('live','site','whatsapp','instagram') NOT NULL DEFAULT 'live'");
    }
};