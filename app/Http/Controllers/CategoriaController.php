<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::with('parent')->get();
        return view('admin.categorias.index', compact('categorias'));
    }

    public function create()
    {
        $parentCategorias = Categoria::all();
        return view('admin.categorias.form', compact('parentCategorias'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto' => 'required|in:porcentagem,fixo',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Categoria::create($validated);

        return redirect()->route('admin.categorias.index')->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Categoria $categoria)
    {
        $parentCategorias = Categoria::where('id', '!=', $categoria->id)->get();
        return view('admin.categorias.form', compact('categoria', 'parentCategorias'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto' => 'required|in:porcentagem,fixo',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $categoria->update($validated);

        return redirect()->route('admin.categorias.index')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();
        return redirect()->route('admin.categorias.index')->with('success', 'Categoria removida com sucesso!');
    }
}
