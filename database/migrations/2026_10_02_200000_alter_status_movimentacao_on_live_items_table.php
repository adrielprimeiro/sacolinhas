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
        if (Schema::hasTable('live_items')) {
            try {
                DB::statement("ALTER TABLE `live_items` MODIFY COLUMN `status_movimentacao` VARCHAR(50) NOT NULL DEFAULT 'enviado'");
            } catch (\Exception $e) {
                // Fallback via Schema builder
                Schema::table('live_items', function (Blueprint $table) {
                    $table->string('status_movimentacao', 50)->default('enviado')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_items')) {
            try {
                DB::statement("ALTER TABLE `live_items` MODIFY COLUMN `status_movimentacao` ENUM('enviado', 'retornado') NOT NULL DEFAULT 'enviado'");
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
};
