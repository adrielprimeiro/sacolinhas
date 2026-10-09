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

        $controller = new \App\Http\Controllers\Admin\LiveVideoCutsController();
        $class = new \ReflectionClass($controller);
        $method = $class->getMethod('performChronologicalHeuristicDetection');
        $method->setAccessible(true);

        $results = $method->invoke($controller, $live, $sentences, $items, 0.0);
        $this->info("\n--- RESULTADO DA DETECÇÃO PROGRESSIVA (Total: " . count($results) . ") ---");

        foreach ($results as $res) {
            $code = (int) $res['codigo_live'];
            if ($code >= 50 && $code <= 105) {
                $st = $res['cut_start_sec'];
                $en = $res['cut_end_sec'];
                $formattedSt = gmdate($st >= 3600 ? 'H:i:s' : 'i:s', (int)$st);
                $formattedEn = gmdate($en >= 3600 ? 'H:i:s' : 'i:s', (int)$en);
                $this->line("Peça #{$code} -> [{$formattedSt} ({$st}s) até {$formattedEn} ({$en}s)]");
            }
        }



        return Command::SUCCESS;
    }
}
