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

    /**
     * Gera slug único usando a cadeia de pais (ex: feminino-roupas-vestidos)
     */
    private function gerarSlug(string $name, ?int $parentId, ?int $ignorarId = null): string
    {
        $partes = [Str::slug($name)];
        $current = $parentId;

        while ($current) {
            $pai = Categoria::find($current);
            if (!$pai) break;
            array_unshift($partes, Str::slug($pai->name));
            $current = $pai->parent_id;
        }

        $baseSlug = implode('-', $partes);
        $slug = $baseSlug;
        $i = 2;

        // Garante unicidade
        while (Categoria::where('slug', $slug)->when($ignorarId, fn($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        return $slug;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:categorias,id',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto'  => 'required|in:porcentagem,fixo',
        ]);

        $validated['slug'] = $this->gerarSlug($validated['name'], $validated['parent_id'] ?? null);

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
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:categorias,id',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto'  => 'required|in:porcentagem,fixo',
        ]);

        $validated['slug'] = $this->gerarSlug($validated['name'], $validated['parent_id'] ?? null, $categoria->id);

        $categoria->update($validated);

        return redirect()->route('admin.categorias.index')->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();
        return redirect()->route('admin.categorias.index')->with('success', 'Categoria removida com sucesso!');
    }
}
