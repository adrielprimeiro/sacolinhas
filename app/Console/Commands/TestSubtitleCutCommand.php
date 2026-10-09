<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Live;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Admin\LiveVideoCutsController;

class TestSubtitleCutCommand extends Command
{
    protected $signature = 'test:cut {live_id=349} {codigo=1}';
    protected $description = 'Test video cut with subtitles';

    public function handle()
    {
        $liveId = $this->argument('live_id');
        $codigo = $this->argument('codigo');

        $live = Live::find($liveId);
        if (!$live) {
            $this->error("Live $liveId not found");
            return 1;
        }

        $item = DB::table('live_items')
            ->where('live_id', $liveId)
            ->where('codigo_live', $codigo)
            ->first();

        if (!$item) {
            $this->error("Item $codigo not found in Live $liveId");
            return 1;
        }

        $this->info("Item ID: {$item->id}, Code: {$item->codigo_live}, Start: {$item->cut_start_sec}, End: {$item->cut_end_sec}");
        $this->info("Recording path: {$live->recording_path}");

        $controller = app(LiveVideoCutsController::class);
        $request = new \Illuminate\Http\Request();

        $response = $controller->generateSingleClip($request, $liveId, $item->id);
        $data = $response->getData(true);

        $this->info("Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return 0;
    }
}
