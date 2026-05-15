<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE movimentacoes MODIFY COLUMN forma_pagamento ENUM('pix', 'boleto', 'cartao_credito', 'dinheiro', 'transferencia', 'saldo_carteira') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE movimentacoes MODIFY COLUMN forma_pagamento ENUM('pix', 'boleto', 'cartao_credito', 'dinheiro', 'transferencia') NOT NULL");
    }
};
