<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use Illuminate\Support\Facades\DB;

class DebugItemCutCommand extends Command
{
    protected $signature = 'debug:item-cut {live_id} {code}';
    protected $description = 'Debug live item timestamps and transcription sentences';

    public function handle()
    {
        $liveId = (int) $this->argument('live_id');
        $code = (string) $this->argument('code');

        $item = DB::table('live_items')->where('live_id', $liveId)->where('codigo_live', $code)->first();
        if (!$item) {
            $this->error("Item #{$code} not found.");
            return 1;
        }

        $this->info("Item {$code}: Start={$item->cut_start_sec} End={$item->cut_end_sec}");
        $this->info("Snippet: {$item->transcription_snippet}");
        $this->newLine();

        $live = Live::find($liveId);
        $sentences = json_decode($live->transcription_raw, true) ?: [];

        $startRange = max(0, $item->cut_start_sec - 20);
        $endRange = $item->cut_end_sec + 20;

        $this->info("Sentences around {$startRange}s to {$endRange}s:");
        foreach ($sentences as $idx => $s) {
            $sStart = (float) ($s['start'] ?? 0);
            $sEnd = (float) ($s['end'] ?? 0);
            if ($sStart >= $startRange && $sStart <= $endRange) {
                $this->line(sprintf("[%d] [%02d:%02d / %.1fs - %02d:%02d / %.1fs] %s", 
                    $idx,
                    floor($sStart / 60), fmod($sStart, 60), $sStart,
                    floor($sEnd / 60), fmod($sEnd, 60), $sEnd,
                    $s['text'] ?? ''
                ));
            }
        }

        return 0;
    }
}
