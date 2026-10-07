<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use Illuminate\Support\Facades\DB;

class DebugItemCutCommand extends Command
{
    protected $signature = 'debug:item-cut {live_id} {code=all} {--restore}';
    protected $description = 'Debug live item timestamps and transcription sentences';

    public function handle()
    {
        $liveId = (int) $this->argument('live_id');
        $code = (string) $this->argument('code');
        $shouldRestore = $this->option('restore');

        if ($shouldRestore) {
            $feedbacks = DB::table('live_cut_feedbacks')->where('live_id', $liveId)->get();
            $count = 0;
            foreach ($feedbacks as $fb) {
                DB::table('live_items')
                    ->where('live_id', $liveId)
                    ->where('codigo_live', $fb->codigo_live)
                    ->update([
                        'cut_start_sec' => $fb->cut_start_sec,
                        'cut_end_sec' => $fb->cut_end_sec,
                        'transcription_snippet' => $fb->full_transcription_snippet ?: DB::raw('transcription_snippet'),
                        'is_reviewed' => 1,
                        'review_quality' => $fb->feedback_type === 'human_approved' ? 'gold' : 'human_adjusted'
                    ]);
                $count++;
            }
            $this->info("Restaurados com sucesso {$count} itens revisados para a Live #{$liveId} a partir da memória do Severino!");
            return 0;
        }

        if ($code === 'all') {
            $items = DB::table('live_items')->where('live_id', $liveId)->orderBy('id')->get();
            $feedbacks = DB::table('live_cut_feedbacks')->where('live_id', $liveId)->get()->keyBy('codigo_live');
            
            $this->info("Total items: " . $items->count());
            $this->info("Total feedbacks/reviewed in memory: " . $feedbacks->count());
            
            foreach ($items->take(25) as $it) {
                $isRev = $it->is_reviewed ?? 0;
                $q = $it->review_quality ?? 'none';
                $fb = $feedbacks->get($it->codigo_live);
                $fbInfo = $fb ? " [MEMÓRIA: {$fb->cut_start_sec}s - {$fb->cut_end_sec}s ({$fb->feedback_type})]" : "";
                $this->line("#{$it->codigo_live} (ID {$it->id}): Start={$it->cut_start_sec}s End={$it->cut_end_sec}s | Rev={$isRev} Q={$q}{$fbInfo}");
            }
            return 0;
        }

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

        $startRange = max(0, ($item->cut_start_sec ?? 0) - 20);
        $endRange = ($item->cut_end_sec ?? 0) + 20;

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
