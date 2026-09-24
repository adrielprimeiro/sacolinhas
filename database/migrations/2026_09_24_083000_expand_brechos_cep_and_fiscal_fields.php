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
        if (Schema::hasTable('brechos')) {
            Schema::table('brechos', function (Blueprint $table) {
                // Altera a coluna cep para permitir até 12 caracteres (com ou sem hífen)
                $table->string('cep', 12)->nullable()->change();
                $table->string('documento', 25)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('brechos')) {
            Schema::table('brechos', function (Blueprint $table) {
                $table->string('cep', 8)->nullable()->change();
                $table->string('documento')->nullable()->change();
            });
        }
    }
};
