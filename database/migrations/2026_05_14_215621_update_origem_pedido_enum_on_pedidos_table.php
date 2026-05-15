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
        Schema::table('pedidos', function (Blueprint $table) {
            DB::statement("ALTER TABLE pedidos MODIFY COLUMN origem_pedido ENUM('live', 'site', 'whatsapp', 'instagram', 'admin', 'portal') NOT NULL DEFAULT 'live'");
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            DB::statement("ALTER TABLE pedidos MODIFY COLUMN origem_pedido ENUM('live', 'site', 'whatsapp', 'instagram', 'admin') NOT NULL DEFAULT 'live'");
        });
    }
};
