<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sacolinhas', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('live_id');
            $table->decimal('price', 10, 2)->default(0)->after('quantity');
        });
    }

    public function down()
    {
        Schema::table('sacolinhas', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'price']);
        });
    }
};