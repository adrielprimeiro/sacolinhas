<?php

namespace App\Http\Controllers;

use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BatchUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'photos' => 'required|array|min:1|max:1',
            'photos.*' => 'image|max:51200',
        ]);

        $file = $request->file('photos')[0];

        try {
            // Processar imagem
            $result = $this->processImageSimple($file);

            if (!$result) {
                throw new \Exception('Falha ao processar imagem');
            }

            // Criar mídia SEM item_id (órfã)
            $media = ItemMedia::create([
                'item_id' => null, // Sem item vinculado
                'media_type' => 'image',
                'url' => $result['url'],
                'thumbnail_url' => $result['thumbnail_url'],
                'metadata' => $result['metadata'],
            ]);

            return response()->json([
                'success' => true,
                'uploaded' => 1,
                'media_id' => $media->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'uploaded' => 0,
                'errors' => [[
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]],
            ], 500);
        }
    }

    private function processImageSimple($file)
    {
        try {
            // Pasta temporária para fotos órfãs
            $dir = 'orphan_photos';
            Storage::disk('public')->makeDirectory($dir);

            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = Str::uuid() . '.' . $extension;
            $path = $file->storeAs($dir, $filename, 'public');

            $thumbPath = "{$dir}/thumb_{$filename}";
            Storage::disk('public')->copy($path, $thumbPath);

            return [
                'url' => $path,
                'thumbnail_url' => $thumbPath,
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                ],
            ];

        } catch (\Exception $e) {
            \Log::error('Image processing error', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}