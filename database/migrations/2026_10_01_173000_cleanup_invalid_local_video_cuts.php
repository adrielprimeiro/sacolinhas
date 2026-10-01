<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemMedia;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpa registros de item_media que apontam para caminhos locais Windows (.mkv ou C:/...)
        DB::table('item_media')
            ->where('media_type', 'video')
            ->where(function ($query) {
                $query->where('url', 'like', 'C:/%')
                    ->orWhere('url', 'like', 'C:\%')
                    ->orWhere('url', 'like', '%.mkv')
                    ->orWhere('url', 'not like', '%storage/%');
            })
            ->delete();

        // 2. Limpa live_items que continham esses caminhos inválidos de testes locais
        DB::table('live_items')
            ->where(function ($query) {
                $query->where('video_cut_path', 'like', 'C:/%')
                    ->orWhere('video_cut_path', 'like', 'C:\%')
                    ->orWhere('video_cut_path', 'like', '%.mkv');
            })
            ->update([
                'video_cut_path' => null,
                'video_cut_status' => null,
            ]);

        // 3. Verifica integridade física dos vídeos restantes no storage público
        $validVideos = DB::table('item_media')
            ->where('media_type', 'video')
            ->get();

        foreach ($validVideos as $media) {
            $relativePath = str_replace('/storage/', '', $media->url);
            if (!Storage::disk('public')->exists($relativePath)) {
                DB::table('item_media')->where('id', $media->id)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Não reversível
    }
};
