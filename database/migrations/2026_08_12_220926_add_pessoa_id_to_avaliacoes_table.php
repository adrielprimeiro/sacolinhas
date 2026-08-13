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
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->unsignedBigInteger('pessoa_id')->nullable()->after('user_id');
            $table->foreign('pessoa_id')->references('id')->on('pessoas')->nullOnDelete();
            
            // Allow user_id to be null
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaliacoes', function (Blueprint $table) {
            $table->dropForeign(['pessoa_id']);
            $table->dropColumn('pessoa_id');
            
            // Revert user_id to non-nullable (might fail if there are nulls)
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};
