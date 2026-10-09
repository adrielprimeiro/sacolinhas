<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use App\Models\LiveItem;
use Illuminate\Support\Facades\DB;

class DebugCutsLiveCommand extends Command
{
    protected $signature = 'app:debug-cuts-live {live_id=349}';
    protected $description = 'Debug cuts for live items';

    public function handle()
    {
        $liveId = $this->argument('live_id');
        $live = Live::find($liveId);
        if (!$live) {
            $this->error("Live {$liveId} não encontrada.");
            return Command::FAILURE;
        }

        $this->info("Live #{$live->id} - Recording path: " . $live->recording_path);

        $items = DB::table('live_items')
            ->where('live_id', $liveId)
            ->orderByRaw('CAST(codigo_live AS UNSIGNED) ASC')
            ->get();

        $sentences = json_decode($live->transcription_raw, true) ?: [];
        $this->info("Total frases transcritas: " . count($sentences));

        if (!empty($sentences)) {
            $first = reset($sentences);
            $last = end($sentences);
            $this->line("Primeira frase: Start " . ($first['start'] ?? 0) . "s - " . ($first['text'] ?? ''));
            $this->line("Última frase: End " . ($last['end'] ?? 0) . "s - " . ($last['text'] ?? ''));

            // Procura frases com códigos 54, 55, 56, 90, 91
            $this->info("\n--- BUSCANDO CÓDIGOS NA TRANSCRIÇÃO ---");
            foreach ($sentences as $s) {
                $text = $s['text'] ?? '';
                $st = $s['start'] ?? 0;
                $en = $s['end'] ?? 0;
                $formattedTime = gmdate($st >= 3600 ? 'H:i:s' : 'i:s', (int)$st);

                if (preg_match('/\b(?:c[oó]digo|pe[cç]a|n[uú]mero|item)?\s*(54|55|56|57|58|59|60|90|91|92)\b/i', $text, $m)) {
                    $this->line("[{$formattedTime} - {$st}s] Encontrado #{$m[1]}: \"{$text}\"");
                }
            }
        }


        return Command::SUCCESS;
    }
}
