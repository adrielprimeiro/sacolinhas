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
        Schema::table('sacolinhas', function (Blueprint $table) {
            if (!Schema::hasColumn('sacolinhas', 'pago_a_vista')) {
                $table->boolean('pago_a_vista')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sacolinhas', 'pago_a_vista_at')) {
                $table->timestamp('pago_a_vista_at')->nullable()->after('pago_a_vista');
            }
            if (!Schema::hasColumn('sacolinhas', 'pago_a_vista_por')) {
                $table->string('pago_a_vista_por')->nullable()->after('pago_a_vista_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sacolinhas', function (Blueprint $table) {
            if (Schema::hasColumn('sacolinhas', 'pago_a_vista_por')) {
                $table->dropColumn('pago_a_vista_por');
            }
            if (Schema::hasColumn('sacolinhas', 'pago_a_vista_at')) {
                $table->dropColumn('pago_a_vista_at');
            }
            if (Schema::hasColumn('sacolinhas', 'pago_a_vista')) {
                $table->dropColumn('pago_a_vista');
            }
        });
    }
};
