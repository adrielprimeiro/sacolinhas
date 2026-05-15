<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement("ALTER TABLE pedidos MODIFY COLUMN forma_pagamento ENUM('pix','cartao_credito','cartao_debito','boleto','dinheiro','transferencia','saldo_carteira') DEFAULT NULL");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE pedidos MODIFY COLUMN forma_pagamento ENUM('pix','cartao_credito','cartao_debito','boleto','dinheiro','transferencia') DEFAULT NULL");
    }
};