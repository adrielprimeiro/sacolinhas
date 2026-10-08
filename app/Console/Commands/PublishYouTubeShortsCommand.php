<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use App\Models\SocialChannelAccount;
use App\Services\YouTube\YouTubeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishYouTubeShortsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:publish-youtube-shorts {--live_id= : ID da live para publicar os cortes} {--item_id= : ID específico de um live_item} {--privacy=public : Visibilidade (public, unlisted, private)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publica cortes de peças da live no YouTube Shorts automaticamente em segundo plano.';

    /**
     * Execute the console command.
     */
    public function handle(YouTubeService $youtubeService)
    {
        $liveId = $this->option('live_id');
        $itemId = $this->option('item_id');
        $privacy = $this->option('privacy') ?: 'public';

        $account = SocialChannelAccount::getActiveAccount('youtube');
        if (!$account) {
            $this->error('Canal do YouTube não conectado.');
            return Command::FAILURE;
        }

        $query = DB::table('live_items')
            ->join('items', 'live_items.item_id', '=', 'items.id')
            ->leftJoin('users', 'live_items.user_id', '=', 'users.id')
            ->select([
                'live_items.*',
                'items.nome_do_produto as item_nome',
                'items.descricao as item_descricao',
                'items.preco as item_price',
                'items.marca',
                'items.tamanho',
                'items.cor',
                'users.name as user_full_name'
            ]);

        if ($itemId) {
            $query->where('live_items.id', $itemId);
        } elseif ($liveId) {
            $query->where('live_items.live_id', $liveId)
                ->where(function ($q) {
                    $q->whereNull('live_items.user_id')
                      ->where(function ($sub) {
                          $sub->whereNull('live_items.buyer_name')
                              ->orWhere('live_items.buyer_name', '')
                              ->orWhere('live_items.buyer_name', '0');
                      });
                })
                ->where(function ($q) {
                    $q->whereNotNull('live_items.video_cut_path')
                      ->orWhere('live_items.video_cut_status', 'recorded');
                })
                ->where(function ($q) {
                    $q->whereNull('live_items.youtube_video_id')
                      ->orWhere('live_items.youtube_status', 'error');
                })
                ->orderByRaw('CAST(live_items.codigo_live AS UNSIGNED) ASC');
        } else {
            $this->error('Especifique --live_id ou --item_id.');
            return Command::FAILURE;
        }

        $items = $query->get();
        $total = $items->count();

        if ($total === 0) {
            $this->info('Nenhum item pendente de publicação no YouTube.');
            return Command::SUCCESS;
        }

        $this->info("Iniciando publicação de {$total} peças no YouTube Shorts (Canal: {$account->channel_name})...");

        $successCount = 0;
        foreach ($items as $index => $item) {
            $current = $index + 1;
            $code = $item->codigo_live ?: $item->id;
            $this->line("[{$current}/{$total}] Enviando peça #{$code} ({$item->item_nome})...");

            $res = $youtubeService->uploadShort($item, ['privacy' => $privacy]);

            if ($res['success']) {
                $successCount++;
                $this->info("  ✅ Publicado: {$res['url']}");
            } else {
                $this->warn("  ⚠️ Falha: {$res['message']}");
            }

            // Pausa de 3 segundos entre uploads para respeitar limites da API
            if ($current < $total) {
                sleep(3);
            }
        }

        $this->info("Concluído! {$successCount} de {$total} peças publicadas no YouTube Shorts.");
        return Command::SUCCESS;
    }
}
