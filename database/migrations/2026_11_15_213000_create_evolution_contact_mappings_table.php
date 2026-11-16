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
        Schema::create('evolution_contact_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('lid', 100)->unique()->index();
            $table->string('phone', 50)->index();
            $table->string('remote_jid', 100)->index();
            $table->string('name', 150)->nullable();
            $table->timestamps();
        });

        // Seed known mappings
        DB::table('evolution_contact_mappings')->insertOrIgnore([
            [
                'lid' => '210526462324913@lid',
                'phone' => '5521996733703',
                'remote_jid' => '5521996733703@s.whatsapp.net',
                'name' => 'Grasi da Mania',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'lid' => '78782652272782@lid',
                'phone' => '554896368721',
                'remote_jid' => '554896368721@s.whatsapp.net',
                'name' => 'Manoel Borba',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolution_contact_mappings');
    }
};
