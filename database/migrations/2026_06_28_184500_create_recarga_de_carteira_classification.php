<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('classificacao_financeira')->insertOrIgnore([
            'id' => 84,
            'user_id' => 2,
            'nome' => 'Recarga de Carteira',
            'codigo_contabil' => '1.04',
            'tipo_natureza' => 'receita',
            'nivel' => 'analitico',
            'id_pai' => 1, // Receitas
            'area_finalidade' => 'vendas',
            'frequencia' => 'regular',
            'descricao' => 'Aportes e recargas de saldo dos clientes na carteira virtual',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        DB::table('classificacao_financeira')->where('id', 84)->delete();
    }
};
