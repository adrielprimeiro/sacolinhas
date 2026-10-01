<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ItemMedia;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $cuts = DB::table('live_items')
            ->whereNotNull('item_id')
            ->whereNotNull('video_cut_path')
            ->where('video_cut_status', 'recorded')
            ->get();

        foreach ($cuts as $cut) {
            // Verifica se o item ainda existe
            $itemExists = DB::table('items')->where('id', $cut->item_id)->exists();
            if (!$itemExists) {
                continue;
            }

            ItemMedia::updateOrCreate(
                [
                    'item_id' => $cut->item_id,
                    'media_type' => 'video'
                ],
                [
                    'url' => $cut->video_cut_path,
                    'position' => 99,
                    'is_cover' => false,
                    'alt_text' => 'Vídeo do produto na Live'
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não remove
    }
};
