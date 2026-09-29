<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPoderosaCommand extends Command
{
    protected $signature = 'app:debug-poderosa';
    protected $description = 'Debug poderosa and live items';

    public function handle()
    {
        $this->info("=== ITEMS COM PODEROSA ===");
        $items = DB::table('items')
            ->where('nome_do_produto', 'like', '%poderosa%')
            ->orWhere('descricao', 'like', '%poderosa%')
            ->orWhere('codigo', 'like', '%poderosa%')
            ->get(['id', 'codigo', 'nome_do_produto', 'descricao', 'status']);
        $this->line(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("=== LIVES RECENTES ===");
        $lives = DB::table('lives')->orderBy('id', 'desc')->limit(5)->get(['id', 'data_live', 'titulo', 'status']);
        $this->line(json_encode($lives, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("=== LIVE ITEMS DA LIVE 334 ===");
        $liveItems = DB::table('live_items')
            ->leftJoin('items', 'items.id', '=', 'live_items.item_id')
            ->where('live_items.live_id', 334)
            ->get(['live_items.id', 'live_items.live_id', 'live_items.item_id', 'live_items.codigo_live', 'items.codigo', 'items.nome_do_produto', 'live_items.cut_start_sec', 'live_items.cut_end_sec', 'live_items.transcription_snippet']);
        $this->line(json_encode($liveItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("=== TODAS AS LIVE_ITEMS COM PODEROSA ===");
        $allPoderosa = DB::table('live_items')
            ->leftJoin('items', 'items.id', '=', 'live_items.item_id')
            ->where('live_items.codigo_live', 'like', '%poderosa%')
            ->orWhere('live_items.transcription_snippet', 'like', '%poderosa%')
            ->get(['live_items.id', 'live_items.live_id', 'live_items.item_id', 'live_items.codigo_live', 'items.codigo', 'items.nome_do_produto']);
        $this->line(json_encode($allPoderosa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
