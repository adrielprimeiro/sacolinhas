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
        // 1. Campos na tabela live_items para associar o comprador e a mensagem
        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                if (!Schema::hasColumn('live_items', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('codigo_live');
                }
                if (!Schema::hasColumn('live_items', 'buyer_username')) {
                    $table->string('buyer_username', 100)->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('live_items', 'buyer_name')) {
                    $table->string('buyer_name', 150)->nullable()->after('buyer_username');
                }
                if (!Schema::hasColumn('live_items', 'live_message_id')) {
                    $table->unsignedBigInteger('live_message_id')->nullable()->after('buyer_name');
                }
            });
        }

        // 2. Campos na tabela live_messages para exibir o código/item vinculado
        if (Schema::hasTable('live_messages')) {
            Schema::table('live_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('live_messages', 'linked_item_id')) {
                    $table->unsignedBigInteger('linked_item_id')->nullable()->after('is_marked');
                }
                if (!Schema::hasColumn('live_messages', 'linked_code')) {
                    $table->string('linked_code', 100)->nullable()->after('linked_item_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('live_items')) {
            Schema::table('live_items', function (Blueprint $table) {
                $columns = ['user_id', 'buyer_username', 'buyer_name', 'live_message_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('live_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('live_messages')) {
            Schema::table('live_messages', function (Blueprint $table) {
                $columns = ['linked_item_id', 'linked_code'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('live_messages', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
