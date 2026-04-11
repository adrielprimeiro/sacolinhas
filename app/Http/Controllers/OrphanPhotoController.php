<?php

namespace App\Http\Controllers;

use App\Models\ItemMedia;
use Illuminate\Http\Request;

class OrphanPhotoController extends Controller
{
    public function index()
    {
        $photos = ItemMedia::whereNull('item_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'count' => $photos->count(),
            'photos' => $photos->map(function ($photo) {
                // metadata já é array (cast do model)
                return [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'thumbnail_url' => $photo->thumbnail_url,
                    'metadata' => $photo->metadata, // já é array
                ];
            }),
        ]);
    }

    public function destroy($id)
    {
        $photo = ItemMedia::whereNull('item_id')->findOrFail($id);
        
        \Storage::disk('public')->delete($photo->url);
        if ($photo->thumbnail_url) {
            \Storage::disk('public')->delete($photo->thumbnail_url);
        }
        
        $photo->delete();

        return response()->json(['success' => true]);
    }
}