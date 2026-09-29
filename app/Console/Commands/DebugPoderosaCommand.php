<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPoderosaCommand extends Command
{
    protected $signature = 'app:debug-poderosa';
    protected $description = 'Debug poderosa';

    public function handle()
    {
        $this->info("=== TODOS OS ITENS DA LIVE 335 ===");
        $items = DB::table('live_items')
            ->leftJoin('items', 'items.id', '=', 'live_items.item_id')
            ->where('live_items.live_id', 335)
            ->get([
                'live_items.id as live_item_id',
                'live_items.item_id',
                'live_items.codigo_live',
                'items.codigo as sku',
                'items.nome_do_produto',
                'live_items.buyer_username',
                'live_items.buyer_name',
                'live_items.created_at'
            ]);
        $this->line(json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("=== TESTE BUSCA USUÁRIOS COM 'ana' ===");
        $users = \App\Models\Cliente::clientes()->buscar('ana')->limit(5)->get(['id', 'name', 'apelido', 'instagram', 'whatsapp', 'cpf']);
        $this->line(json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
