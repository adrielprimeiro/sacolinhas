<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    public function index()
    {
        // Carrega apenas as categorias raiz com toda a árvore de filhos aninhada
        $categorias = Categoria::whereNull('parent_id')
            ->withCount('items')
            ->with($this->treeWith())
            ->orderBy('name')
            ->get();

        $totalCategorias = Categoria::count();
        $totalRaiz       = $categorias->count();

        return view('admin.categorias.index', compact('categorias', 'totalCategorias', 'totalRaiz'));
    }

    /**
     * Carrega a árvore de filhos recursivamente até 5 níveis com contagem de itens
     */
    private function treeWith(): array
    {
        $nivel = fn($depth) => $depth > 0
            ? ['children' => fn($q) => $q->withCount('items')->orderBy('name')->with(['children' => fn($q2) => $q2->withCount('items')->orderBy('name')])]
            : ['children' => fn($q) => $q->withCount('items')->orderBy('name')];

        return [
            'children' => fn($q) => $q
                ->withCount('items')
                ->orderBy('name')
                ->with([
                    'children' => fn($q2) => $q2
                        ->withCount('items')
                        ->orderBy('name')
                        ->with([
                            'children' => fn($q3) => $q3
                                ->withCount('items')
                                ->orderBy('name')
                                ->with([
                                    'children' => fn($q4) => $q4
                                        ->withCount('items')
                                        ->orderBy('name')
                                ])
                        ])
                ])
        ];
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
            'altura'         => 'nullable|numeric|min:0',
            'largura'        => 'nullable|numeric|min:0',
            'comprimento'    => 'nullable|numeric|min:0',
            'peso'           => 'nullable|numeric|min:0',
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
            'altura'         => 'nullable|numeric|min:0',
            'largura'        => 'nullable|numeric|min:0',
            'comprimento'    => 'nullable|numeric|min:0',
            'peso'           => 'nullable|numeric|min:0',
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
