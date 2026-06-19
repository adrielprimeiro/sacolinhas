<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDates extends Command
{
    protected $signature = 'financeiro:check-dates';
    protected $description = 'Verifica as datas dos itens devolvidos e os lançamentos recentes na conta_corrente';

    public function handle()
    {
        $nullCount = DB::table('items_pedido')
            ->where('status_item', 'devolvido')
            ->whereNull('updated_at')
            ->count();

        $totalCount = DB::table('items_pedido')
            ->where('status_item', 'devolvido')
            ->count();

        $this->info("=== Items Pedido (status_item = 'devolvido') ===");
        $this->info("Total returned items: {$totalCount}");
        $this->info("Items with NULL updated_at: {$nullCount}");

        $minDate = DB::table('items_pedido')->where('status_item', 'devolvido')->min('updated_at');
        $maxDate = DB::table('items_pedido')->where('status_item', 'devolvido')->max('updated_at');
        $this->info("Date range of updated_at: [{$minDate}] to [{$maxDate}]");

        $this->info("\n=== Recentes em conta_corrente (tipo_movimentacao = 'credito') ===");
        $recentes = DB::table('conta_corrente')
            ->where('tipo_movimentacao', 'credito')
            ->where('referencia_tipo', 'pedido')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        foreach ($recentes as $r) {
            $this->line("ID: {$r->id} | User: {$r->user_id} | Val: R$ {$r->valor} | Data Mov: {$r->data_movimentacao} | Created At: {$r->created_at} | Desc: {$r->descricao}");
        }
    }
}
