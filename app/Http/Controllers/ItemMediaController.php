<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemMediaController extends Controller
{
    public function reorder(Request $request, Item $item)
    {
        try {
            $data = $request->validate([
                'ordered_ids' => ['required', 'array', 'min:1'],
                'ordered_ids.*' => ['integer'],
            ]);

            DB::transaction(function () use ($data, $item) {
                $validIds = ItemMedia::query()
                    ->where('item_id', $item->id)
                    ->whereIn('id', $data['ordered_ids'])
                    ->pluck('id')
                    ->all();

                $validIdSet = array_flip($validIds);
                $order = 1;

                foreach ($data['ordered_ids'] as $id) {
                    if (!isset($validIdSet[$id])) continue;

                    ItemMedia::where('id', $id)
                        ->where('item_id', $item->id)
                        ->update(['position' => $order]);
                    
                    $order++;
                }
            });

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false, 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}