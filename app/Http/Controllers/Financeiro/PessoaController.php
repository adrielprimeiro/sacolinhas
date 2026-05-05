<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PessoaController extends Controller
{
    /**
     * Lista as pessoas (fornecedores, funcionários etc.) para o financeiro.
     */
    public function index(Request $request)
    {
        $query = Pessoa::orderBy('nome');

        if ($request->filled('q')) {
            $query->where('nome', 'like', '%' . $request->q . '%')
                  ->orWhere('documento', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $pessoas = $query->paginate(30)->withQueryString();

        return view('admin.financeiro.pessoas.index', compact('pessoas'));
    }

    /**
     * Salva uma nova pessoa via AJAX.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:25'],
            'tipo'      => ['required', Rule::in(['cliente_circular', 'fornecedor_externo', 'funcionario', 'outro'])],
            'user_id'   => ['nullable', 'exists:users,id'],
        ]);

        Pessoa::create($data);

        return response()->json(['success' => true, 'message' => 'Pessoa cadastrada com sucesso.']);
    }

    /**
     * Retorna os dados de uma pessoa para edição.
     */
    public function show(Pessoa $pessoa)
    {
        return response()->json($pessoa);
    }

    /**
     * Atualiza os dados de uma pessoa.
     */
    public function update(Request $request, Pessoa $pessoa)
    {
        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:25'],
            'tipo'      => ['required', Rule::in(['cliente_circular', 'fornecedor_externo', 'funcionario', 'outro'])],
            'user_id'   => ['nullable', 'exists:users,id'],
        ]);

        $pessoa->update($data);

        return response()->json(['success' => true, 'message' => 'Pessoa atualizada com sucesso.']);
    }

    /**
     * Exclui uma pessoa (se não houver lançamentos vinculados).
     */
    public function destroy(Pessoa $pessoa)
    {
        if ($pessoa->lancamentos()->exists()) {
            return response()->json([
                'success' => false, 
                'message' => 'Não é possível excluir uma pessoa com lançamentos financeiros vinculados.'
            ], 422);
        }

        $pessoa->delete();

        return response()->json(['success' => true, 'message' => 'Pessoa excluída com sucesso.']);
    }
}
