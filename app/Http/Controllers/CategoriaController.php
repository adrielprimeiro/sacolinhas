<?php

namespace App\Http\Controllers;

use App\Models\Brecho;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();

        if ($isParceiro) {
            $brechoId = (int) auth()->user()->brecho_id;
        } else {
            // Matriz (Minha Mania): pode filtrar por brechó, ver padrão ou todos
            if ($request->filled('brecho_id')) {
                $brechoId = $request->brecho_id === 'all' ? 'all' : ($request->brecho_id === 'padrao' ? 'padrao' : (int) $request->brecho_id);
            } else {
                $brechoId = 1; // Padrão da Matriz: Minha Mania (exibe Padrão + Mania)
            }
        }

        // Query para categorias raiz
        $query = Categoria::whereNull('parent_id');

        if ($brechoId === 'padrao') {
            $query->whereNull('brecho_id');
        } elseif ($brechoId !== 'all') {
            $targetId = (int) $brechoId;
            $query->where(function ($q) use ($targetId) {
                $q->whereNull('brecho_id')->orWhere('brecho_id', $targetId);
            });
        }

        $treeTargetId = ($brechoId === 'all') ? null : (($brechoId === 'padrao') ? 0 : (int) $brechoId);

        $categorias = $query->withCount('items')
            ->with($this->treeWith($treeTargetId))
            ->orderBy('name')
            ->get();

        // Contagens com o mesmo escopo
        $countQuery = Categoria::query();
        if ($brechoId === 'padrao') {
            $countQuery->whereNull('brecho_id');
        } elseif ($brechoId !== 'all') {
            $targetId = (int) $brechoId;
            $countQuery->where(function ($q) use ($targetId) {
                $q->whereNull('brecho_id')->orWhere('brecho_id', $targetId);
            });
        }
        $totalCategorias = $countQuery->count();
        $totalRaiz       = $categorias->count();

        $brechos = Brecho::where('ativo', 1)->orderBy('id')->get();

        return view('admin.categorias.index', compact(
            'categorias', 
            'totalCategorias', 
            'totalRaiz', 
            'isParceiro', 
            'brechoId', 
            'brechos'
        ));
    }

    /**
     * Carrega a árvore de filhos recursivamente até 5 níveis com contagem de itens e escopo por brechó
     */
    private function treeWith(?int $brechoId = null): array
    {
        $applyScope = function ($q) use ($brechoId) {
            $q->withCount('items')->orderBy('name');
            if ($brechoId === 0) {
                $q->whereNull('brecho_id');
            } elseif (!empty($brechoId)) {
                $q->where(function ($sub) use ($brechoId) {
                    $sub->whereNull('brecho_id')->orWhere('brecho_id', $brechoId);
                });
            }
        };

        return [
            'children' => fn($q) => $applyScope($q->with([
                'children' => fn($q2) => $applyScope($q2->with([
                    'children' => fn($q3) => $applyScope($q3->with([
                        'children' => fn($q4) => $applyScope($q4)
                    ]))
                ]))
            ]))
        ];
    }

    public function create(Request $request)
    {
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();
        $brechoId = $isParceiro ? (int) auth()->user()->brecho_id : ($request->filled('brecho_id') && $request->brecho_id !== 'padrao' ? (int) $request->brecho_id : 1);

        $parentCategorias = $this->getTreeCategoriesList(null, $brechoId);
        $brechos = Brecho::where('ativo', 1)->orderBy('id')->get();

        return view('admin.categorias.form', compact('parentCategorias', 'isParceiro', 'brechoId', 'brechos'));
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
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:categorias,id',
            'brecho_id'      => 'nullable',
            'is_padrao'      => 'nullable|boolean',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto'  => 'required|in:porcentagem,fixo',
            'altura'         => 'nullable|numeric|min:0',
            'largura'        => 'nullable|numeric|min:0',
            'comprimento'    => 'nullable|numeric|min:0',
            'peso'           => 'nullable|numeric|min:0',
            'preco_base'     => 'nullable|numeric|min:0',
        ]);

        if ($isParceiro) {
            // Parceiro grava estritamente no seu próprio brechó
            $validated['brecho_id'] = (int) auth()->user()->brecho_id;
        } else {
            // Matriz: se marcou como padrão (global), salva brecho_id = null
            if (!empty($validated['is_padrao'])) {
                $validated['brecho_id'] = null;
            } else {
                $validated['brecho_id'] = $request->filled('brecho_id') && $request->brecho_id !== 'padrao'
                    ? (int) $request->brecho_id
                    : 1;
            }
        }
        unset($validated['is_padrao']);

        $validated['slug'] = $this->gerarSlug($validated['name'], $validated['parent_id'] ?? null);

        Categoria::create($validated);

        Cache::flush();

        return redirect()->route('admin.categorias.index', [
            'brecho_id' => $isParceiro ? null : ($validated['brecho_id'] ?? 'padrao')
        ])->with('success', 'Categoria criada com sucesso!');
    }

    public function edit(Categoria $categoria)
    {
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();

        if ($isParceiro) {
            $userBrechoId = (int) auth()->user()->brecho_id;
            if ($categoria->brecho_id !== null && (int) $categoria->brecho_id !== $userBrechoId) {
                abort(403, 'Acesso não autorizado a categoria de outro brechó.');
            }
            if ($categoria->brecho_id === null) {
                abort(403, 'Categorias padrão não podem ser editadas diretamente por parceiros.');
            }
            $brechoId = $userBrechoId;
        } else {
            $brechoId = $categoria->brecho_id ?: 1;
        }

        $parentCategorias = $this->getTreeCategoriesList($categoria->id, $brechoId);
        $brechos = Brecho::where('ativo', 1)->orderBy('id')->get();

        return view('admin.categorias.form', compact('categoria', 'parentCategorias', 'isParceiro', 'brechoId', 'brechos'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();

        if ($isParceiro) {
            $userBrechoId = (int) auth()->user()->brecho_id;
            abort_if((int) $categoria->brecho_id !== $userBrechoId, 403, 'Você só pode editar categorias pertencentes ao seu brechó.');
        }

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'parent_id'      => 'nullable|exists:categorias,id',
            'brecho_id'      => 'nullable',
            'is_padrao'      => 'nullable|boolean',
            'valor_desconto' => 'required|numeric|min:0',
            'tipo_desconto'  => 'required|in:porcentagem,fixo',
            'altura'         => 'nullable|numeric|min:0',
            'largura'        => 'nullable|numeric|min:0',
            'comprimento'    => 'nullable|numeric|min:0',
            'peso'           => 'nullable|numeric|min:0',
            'preco_base'     => 'nullable|numeric|min:0',
        ]);

        if (!$isParceiro) {
            if (!empty($validated['is_padrao'])) {
                $validated['brecho_id'] = null;
            } elseif ($request->filled('brecho_id')) {
                $validated['brecho_id'] = $request->brecho_id === 'padrao' ? null : (int) $request->brecho_id;
            }
        }
        unset($validated['is_padrao']);

        $validated['slug'] = $this->gerarSlug($validated['name'], $validated['parent_id'] ?? null, $categoria->id);

        $categoria->update($validated);

        Cache::flush();

        return redirect()->route('admin.categorias.index', [
            'brecho_id' => $isParceiro ? null : ($categoria->brecho_id ?? 'padrao')
        ])->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(Categoria $categoria)
    {
        $isParceiro = auth()->check() && auth()->user()->isBrechoParceiro();

        if ($isParceiro) {
            $userBrechoId = (int) auth()->user()->brecho_id;
            abort_if((int) $categoria->brecho_id !== $userBrechoId, 403, 'Você só pode excluir categorias criadas pelo seu brechó.');
        }

        $categoria->delete();

        Cache::flush();

        return redirect()->route('admin.categorias.index')->with('success', 'Categoria removida com sucesso!');
    }

    /**
     * Retorna a lista de categorias ordenada de forma hierárquica e filtrada por brechó.
     */
    private function getTreeCategoriesList(?int $exceptId = null, ?int $brechoId = null)
    {
        $categorias = [];
        $buildTreeList = function($cats, $level = 0, $path = '') use (&$buildTreeList, &$categorias, $exceptId) {
            foreach ($cats as $cat) {
                if ($exceptId !== null && $cat->id == $exceptId) {
                    continue;
                }

                $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}\u{00A0}", $level);
                $prefix = $level > 0 ? '↳ ' : '';
                $currentPath = $path ? $path . ' › ' . $cat->name : $cat->name;
                
                $categorias[] = [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'formatted_name' => $indent . $prefix . $cat->name,
                    'path' => $currentPath,
                ];
                
                if ($cat->children->isNotEmpty()) {
                    $buildTreeList($cat->children, $level + 1, $currentPath);
                }
            }
        };

        $query = Categoria::whereNull('parent_id');
        if (!empty($brechoId)) {
            $query->where(function($q) use ($brechoId) {
                $q->whereNull('brecho_id')->orWhere('brecho_id', $brechoId);
            });
        }

        $rootCats = $query->with(['children' => function($q) use ($brechoId) {
            $q->orderBy('name');
            if (!empty($brechoId)) {
                $q->where(function($sub) use ($brechoId) {
                    $sub->whereNull('brecho_id')->orWhere('brecho_id', $brechoId);
                });
            }
        }])->orderBy('name')->get();

        $buildTreeList($rootCats);

        return $categorias;
    }
}
