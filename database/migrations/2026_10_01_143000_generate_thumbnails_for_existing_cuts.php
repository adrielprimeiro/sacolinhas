<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemMedia;
use App\Models\Item;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cuts = DB::table('live_items')
            ->join('lives', 'live_items.live_id', '=', 'lives.id')
            ->whereNotNull('live_items.item_id')
            ->whereNotNull('live_items.video_cut_path')
            ->where('live_items.video_cut_status', 'recorded')
            ->select(
                'live_items.id as live_item_id',
                'live_items.live_id',
                'live_items.item_id',
                'live_items.codigo_live',
                'live_items.cut_start_sec',
                'live_items.cut_end_sec',
                'live_items.video_cut_path',
                'lives.recording_path'
            )
            ->get();

        foreach ($cuts as $cut) {
            if (!$cut->recording_path) {
                continue;
            }

            $inputPath = Storage::disk('public')->path($cut->recording_path);
            if (!file_exists($inputPath) && file_exists($cut->recording_path)) {
                $inputPath = $cut->recording_path;
            }

            if (!file_exists($inputPath)) {
                continue;
            }

            $outputDir = storage_path('app/public/live_cuts/live_' . $cut->live_id);
            if (!file_exists($outputDir)) {
                @mkdir($outputDir, 0777, true);
            }

            $codeClean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cut->codigo_live ?: 'item_' . $cut->item_id);
            $thumbFilename = 'thumb_' . $codeClean . '_' . time() . '.jpg';
            $thumbPath = $outputDir . '/' . $thumbFilename;
            $relativeThumbPath = 'live_cuts/live_' . $cut->live_id . '/' . $thumbFilename;

            $start = (float) ($cut->cut_start_sec ?? 0);
            $end = (float) ($cut->cut_end_sec ?? ($start + 10));
            $duration = max(1, $end - $start);
            $optimalOffset = min(3.0, max(0.5, round($duration * 0.35, 2)));
            $thumbTimestamp = round($start + $optimalOffset, 2);

            $cmd = sprintf(
                'ffmpeg -ss %s -i %s -vframes 1 -q:v 2 -y %s 2>&1',
                escapeshellarg($thumbTimestamp),
                escapeshellarg($inputPath),
                escapeshellarg($thumbPath)
            );
            exec($cmd);

            if (file_exists($thumbPath) && filesize($thumbPath) > 1000) {
                ItemMedia::updateOrCreate(
                    [
                        'item_id' => $cut->item_id,
                        'media_type' => 'video'
                    ],
                    [
                        'url' => $cut->video_cut_path,
                        'thumbnail_url' => $relativeThumbPath,
                        'position' => 99,
                        'is_cover' => false,
                        'alt_text' => 'Vídeo do produto na Live'
                    ]
                );

                $item = Item::find($cut->item_id);
                if ($item && empty($item->image)) {
                    $item->image = $relativeThumbPath;
                    $item->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
