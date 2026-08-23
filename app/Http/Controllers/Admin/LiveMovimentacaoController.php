<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Live;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiveMovimentacaoController extends Controller
{
    public function scanner(Request $request)
    {
        $lives = Live::orderBy('data', 'desc')->orderBy('id', 'desc')->take(10)->get();
        $liveId = $request->input('live_id');
        
        $liveAtual = null;
        $itensEnviados = collect();
        $itensRetornados = collect();
        $itensVendidos = collect();
        $itensPerdidos = collect();

        if ($liveId) {
            $liveAtual = Live::find($liveId);
            
            if ($liveAtual) {
                // Pega todos os registros dessa live na tabela live_items com join na tabela items
                $liveItems = DB::table('live_items')
                    ->join('items', 'live_items.item_id', '=', 'items.id')
                    ->where('live_items.live_id', $liveId)
                    ->select('live_items.*', 'items.codigo', 'items.nome_do_produto', 'items.localizacao', 'items.status')
                    ->get();
                    
                foreach ($liveItems as $li) {
                    if ($li->status_movimentacao === 'retornado') {
                        $itensRetornados->push($li);
                    } else {
                        // Foi enviado, precisamos ver o estado atual
                        if (strtolower($li->localizacao) === 'live') {
                            $itensPerdidos->push($li); // Ainda estão pendentes de volta
                        } else if (strtolower($li->localizacao) === 'sacolinha' || strtolower($li->status) === 'vendido') {
                            $itensVendidos->push($li);
                        } else {
                            // Mudou de lugar de outra forma
                            $itensRetornados->push($li);
                        }
                    }
                    $itensEnviados->push($li);
                }
            }
        }

        return view('admin.live.scanner', compact(
            'lives', 
            'liveAtual', 
            'itensEnviados', 
            'itensRetornados', 
            'itensVendidos', 
            'itensPerdidos'
        ));
    }

    public function processarIda(Request $request)
    {
        $request->validate([
            'live_id'   => 'required|exists:lives,id',
            'codigos'   => 'required|array|min:1',
            'codigos.*' => 'required|string',
        ]);

        $liveId = $request->live_id;
        $codigos = array_unique(array_filter($request->codigos));
        $atualizados = 0;
        $naoEncontrados = [];

        foreach ($codigos as $rawCodigo) {
            $codigo = trim($rawCodigo);

            if (filter_var($codigo, FILTER_VALIDATE_URL)) {
                $path = parse_url($codigo, PHP_URL_PATH);
                $parts = array_filter(explode('/', (string)$path));
                if (!empty($parts)) {
                    $codigo = end($parts);
                }
            }

            $item = Item::where('codigo', $codigo)
                ->orWhere('codigo', mb_strtoupper($codigo, 'UTF-8'))
                ->orWhere('codigo', mb_strtolower($codigo, 'UTF-8'))
                ->first();

            if (!$item) {
                $naoEncontrados[] = $codigo;
                continue;
            }

            if (strtolower($item->localizacao ?? '') !== 'live') {
                $origem = $item->localizacao;
                
                // Insere ou atualiza na tabela pivô
                DB::table('live_items')->updateOrInsert(
                    ['live_id' => $liveId, 'item_id' => $item->id],
                    [
                        'localizacao_origem' => $origem,
                        'status_movimentacao' => 'enviado',
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );

                $item->localizacao = 'Live';
                $item->save();
                $atualizados++;
            }
        }

        $msg = "✅ {$atualizados} item(ns) enviado(s) para a Live.";
        if (count($naoEncontrados)) {
            $msg .= " | ⚠️ Não encontrados: " . implode(', ', $naoEncontrados);
        }

        return redirect()->route('live.scanner', ['live_id' => $liveId])->with('success', $msg);
    }

    public function processarVolta(Request $request)
    {
        $request->validate([
            'live_id'      => 'required|exists:lives,id',
            'codigos'      => 'required|array|min:1',
            'codigos.*'    => 'required|string',
            'local_destino'=> 'nullable|string',
        ]);

        $liveId = $request->live_id;
        $codigos = array_unique(array_filter($request->codigos));
        $destinoManual = $request->local_destino;
        
        $atualizados = 0;
        $naoEncontrados = [];

        foreach ($codigos as $rawCodigo) {
            $codigo = trim($rawCodigo);

            if (filter_var($codigo, FILTER_VALIDATE_URL)) {
                $path = parse_url($codigo, PHP_URL_PATH);
                $parts = array_filter(explode('/', (string)$path));
                if (!empty($parts)) {
                    $codigo = end($parts);
                }
            }

            $item = Item::where('codigo', $codigo)
                ->orWhere('codigo', mb_strtoupper($codigo, 'UTF-8'))
                ->orWhere('codigo', mb_strtolower($codigo, 'UTF-8'))
                ->first();

            if (!$item) {
                $naoEncontrados[] = $codigo;
                continue;
            }

            $liveItem = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('item_id', $item->id)
                ->first();

            $destinoFinal = $destinoManual ?: ($liveItem->localizacao_origem ?? 'Estoque');

            // Marca como retornado
            DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('item_id', $item->id)
                ->update([
                    'status_movimentacao' => 'retornado',
                    'updated_at' => now()
                ]);

            $item->localizacao = $destinoFinal;
            $item->save();
            
            $atualizados++;
        }

        $msg = "✅ {$atualizados} item(ns) retornado(s) com sucesso.";
        if (count($naoEncontrados)) {
            $msg .= " | ⚠️ Não encontrados: " . implode(', ', $naoEncontrados);
        }

        return redirect()->route('live.scanner', ['live_id' => $liveId])->with('success', $msg);
    }
}
