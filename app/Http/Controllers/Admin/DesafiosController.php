<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Desafio;
use Illuminate\Http\Request;

class DesafiosController extends Controller
{
    public function index()
    {
        $desafios = Desafio::latest()->paginate(15);
        return view('admin.clube.desafios.index', compact('desafios'));
    }

    public function create()
    {
        return view('admin.clube.desafios.form', ['desafio' => new Desafio()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'pontos'    => 'required|integer|min:0',
            'inicio_em' => 'nullable|date',
            'fim_em'    => 'nullable|date|after_or_equal:inicio_em',
            'status'    => 'required|in:ativo,inativo',
        ]);

        Desafio::create($data);

        return redirect()->route('admin.clube.desafios.index')
            ->with('success', 'Desafio cadastrado com sucesso!');
    }

    public function edit(Desafio $desafio)
    {
        return view('admin.clube.desafios.form', compact('desafio'));
    }

    public function update(Request $request, Desafio $desafio)
    {
        $data = $request->validate([
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'pontos'    => 'required|integer|min:0',
            'inicio_em' => 'nullable|date',
            'fim_em'    => 'nullable|date|after_or_equal:inicio_em',
            'status'    => 'required|in:ativo,inativo',
        ]);

        $desafio->update($data);

        return redirect()->route('admin.clube.desafios.index')
            ->with('success', 'Desafio atualizado com sucesso!');
    }

    public function destroy(Desafio $desafio)
    {
        $desafio->delete();
        return back()->with('success', 'Desafio removido.');
    }
}
