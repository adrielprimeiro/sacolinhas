<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
	public function up()
	{
		Schema::create('sacolinhas', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK para tabela users
			$table->foreignId('item_id')->constrained('items')->onDelete('cascade'); // FK para tabela items
			$table->foreignId('live_id')->constrained('lives')->onDelete('cascade'); // FK para tabela live
			$table->dateTime('add_at'); // Data e hora
			$table->integer('tray')->nullable(); // Numérico
			$table->string('status'); // Texto curto
			$table->text('obs')->nullable(); // Texto longo
			$table->timestamps(); // created_at e updated_at
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sacolinhas');
    }
};
