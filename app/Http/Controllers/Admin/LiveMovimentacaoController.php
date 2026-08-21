<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class LiveMovimentacaoController extends Controller
{
    public function scanner()
    {
        $itensNaLive = Item::where('localizacao', 'Live')->get();

        $itensVendidosOuPerdidos = Item::whereNotNull('localizacao_anterior')
            ->where('localizacao', '!=', 'Live')
            ->get();

        return view('admin.live.scanner', compact('itensNaLive', 'itensVendidosOuPerdidos'));
    }

    public function processarIda(Request $request)
    {
        $request->validate([
            'codigos'   => 'required|array|min:1',
            'codigos.*' => 'required|string',
        ]);

        $codigos = array_unique(array_filter($request->codigos));
        $atualizados = 0;
        $naoEncontrados = [];

        foreach ($codigos as $codigo) {
            $item = Item::where('codigo', $codigo)->first();
            if (!$item) {
                $naoEncontrados[] = $codigo;
                continue;
            }

            if (strtolower($item->localizacao ?? '') !== 'live') {
                $item->localizacao_anterior = $item->localizacao;
                $item->localizacao = 'Live';
                $item->save();
                $atualizados++;
            }
        }

        $msg = "✅ {$atualizados} item(ns) movido(s) para a Live.";
        if (count($naoEncontrados)) {
            $msg .= " | ⚠️ Não encontrados: " . implode(', ', $naoEncontrados);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function processarVolta(Request $request)
    {
        $request->validate([
            'codigos'      => 'required|array|min:1',
            'codigos.*'    => 'required|string',
            'local_destino'=> 'nullable|string',
        ]);

        $codigos = array_unique(array_filter($request->codigos));
        $destinoManual = $request->local_destino;
        
        $atualizados = 0;
        $naoEncontrados = [];

        foreach ($codigos as $codigo) {
            $item = Item::where('codigo', $codigo)->first();
            if (!$item) {
                $naoEncontrados[] = $codigo;
                continue;
            }

            $destinoFinal = $destinoManual ?: ($item->localizacao_anterior ?: 'Estoque');

            $item->localizacao = $destinoFinal;
            $item->localizacao_anterior = null;
            $item->save();
            
            $atualizados++;
        }

        $msg = "✅ {$atualizados} item(ns) retornado(s) com sucesso.";
        if (count($naoEncontrados)) {
            $msg .= " | ⚠️ Não encontrados: " . implode(', ', $naoEncontrados);
        }

        return redirect()->back()->with('success', $msg);
    }
}
