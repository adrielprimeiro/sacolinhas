<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Facades\DB;

class MarkYouTubeSoldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-youtube-sold {--item_id= : ID da live_item vendida}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o vídeo do YouTube Shorts com o carimbo de VENDIDO / ESGOTADO.';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeService $youtubeService)
    {
        $itemId = (int) $this->option('item_id');
        if (!$itemId) {
            $this->error('Especifique --item_id.');
            return Command::FAILURE;
        }

        $liveItem = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->where('live_items.id', $itemId)
            ->select([
                'live_items.*',
                'items.nome_do_produto as item_nome',
                'items.descricao as item_descricao',
                'items.preco as item_price',
                'items.marca',
                'items.tamanho',
                'items.cor',
                'users.name as user_full_name'
            ])
            ->first();

        if (!$liveItem) {
            $this->error("LiveItem #{$itemId} não encontrado.");
            return Command::FAILURE;
        }

        if (empty($liveItem->youtube_video_id)) {
            $this->warn("LiveItem #{$itemId} não possui vídeo publicado no YouTube.");
            return Command::SUCCESS;
        }

        $this->info("Marcando vídeo do YouTube {$liveItem->youtube_video_id} como VENDIDO...");
        $res = $youtubeService->markVideoAsSold($liveItem);

        if ($res['success']) {
            $this->info("✅ " . $res['message']);
            return Command::SUCCESS;
        }

        $this->error("❌ " . $res['message']);
        return Command::FAILURE;
    }
}
