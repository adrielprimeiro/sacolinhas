<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('lives', function (Blueprint $table) {
            $table->boolean('ativo')->default(false)->after('plataformas');
            $table->timestamp('encerrada_em')->nullable()->after('ativo');
        });
    }

    public function down()
    {
        Schema::table('lives', function (Blueprint $table) {
            $table->dropColumn(['ativo', 'encerrada_em']);
        });
    }
};