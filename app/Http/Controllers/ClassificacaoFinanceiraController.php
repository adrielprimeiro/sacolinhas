<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassificacaoFinanceiraRequest;
use App\Models\ClassificacaoFinanceira;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassificacaoFinanceiraController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClassificacaoFinanceira::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('tipo_natureza')) {
            $query->where('tipo_natureza', $request->string('tipo_natureza'));
        }

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->string('nivel'));
        }

        $classificacoes = $query
            ->with('pai')
            ->orderBy('codigo_contabil')
            ->paginate(20)
            ->withQueryString();

        return view('classificacao_financeira.index', compact('classificacoes'));
    }

    public function create(): View
    {
        $pais = ClassificacaoFinanceira::where('nivel', 'sintetico')
            ->orderBy('codigo_contabil')
            ->get(['id','nome','codigo_contabil','user_id','tipo_natureza']);

        return view('classificacao_financeira.create', compact('pais'));
    }

    public function store(ClassificacaoFinanceiraRequest $request): RedirectResponse
    {
        ClassificacaoFinanceira::create($request->validated());

        return redirect()
            ->route('classificacao_financeira.index')
            ->with('success', 'Classificação criada com sucesso.');
    }

    public function show(ClassificacaoFinanceira $classificacao_financeira): View
    {
        $classificacao_financeira->load(['pai','filhos']);

        return view('classificacao_financeira.show', compact('classificacao_financeira'));
    }

    public function edit(ClassificacaoFinanceira $classificacao_financeira): View
    {
        $pais = ClassificacaoFinanceira::where('nivel', 'sintetico')
            ->where('id', '!=', $classificacao_financeira->id)
            ->orderBy('codigo_contabil')
            ->get(['id','nome','codigo_contabil','user_id','tipo_natureza']);

        return view('classificacao_financeira.edit', compact('classificacao_financeira', 'pais'));
    }

    public function update(ClassificacaoFinanceiraRequest $request, ClassificacaoFinanceira $classificacao_financeira): RedirectResponse
    {
        $classificacao_financeira->update($request->validated());

        return redirect()
            ->route('classificacao_financeira.index')
            ->with('success', 'Classificação atualizada com sucesso.');
    }

    public function destroy(ClassificacaoFinanceira $classificacao_financeira): RedirectResponse
    {
        // Se tiver filhos, você pode bloquear a exclusão:
        if ($classificacao_financeira->filhos()->exists()) {
            return back()->with('error', 'Não é possível excluir: existem classificações filhas.');
        }

        $classificacao_financeira->delete();

        return redirect()
            ->route('classificacao_financeira.index')
            ->with('success', 'Classificação excluída com sucesso.');
    }
}