<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classificacao_financeira_id');
            $table->foreign('classificacao_financeira_id')
                  ->references('id')
                  ->on('classificacao_financeira')
                  ->cascadeOnDelete();
            $table->date('periodo'); // sempre dia 01 do mês
            $table->decimal('valor_previsto', 15, 2);
            $table->timestamps();

            // Constraint: apenas um orçamento por categoria por mês
            $table->unique(['classificacao_financeira_id', 'periodo'], 'orcamento_categoria_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
