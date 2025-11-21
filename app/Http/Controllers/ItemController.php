<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        // Busca por nome do produto
        if ($request->filled('search')) {
            $query->where('codigo', 'like',  $request->search . '%');
        }

        // Filtro por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate(10);
         
        return view('admin.items.index', compact('items'));
    }

    public function create()
    {
        return view('admin.items.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:items,codigo',
            'nome_do_produto' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'custo' => 'nullable|numeric|min:0',
            'preco' => 'required|numeric|min:0',
            'codigo_da_categoria' => 'nullable|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'estado' => 'required|in:novo,usado,semi-novo,recondicionado',
            'cor' => 'nullable|string',
            'tamanho' => 'nullable|string',
            'pedido' => 'nullable|string',
            'status' => 'required|in:indisponivel,disponivel,reservado,vendido,em_sacolinha',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Item criado com sucesso!');
    }

    public function show(Item $item)
    {
        return view('admin.items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('admin.items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:items,codigo,' . $item->id,
            'nome_do_produto' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'custo' => 'nullable|numeric|min:0',
            'preco' => 'required|numeric|min:0',
            'codigo_da_categoria' => 'nullable|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'estado' => 'required|in:novo,usado,semi-novo,recondicionado',
            'cor' => 'nullable|string',
            'tamanho' => 'nullable|string',
            'pedido' => 'nullable|string',
            'status' => 'required|in:indisponivel,disponivel,reservado,vendido,em_sacolinha',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('image')) {
            // Deletar imagem antiga se existir
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $item->update($validated);

        return redirect()->route('items.index')->with('success', 'Item atualizado com sucesso!');
    }

    public function destroy(Item $item)
    {
        // Deletar imagem se existir
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deletado com sucesso!');
    }

    /**
     * Search items for API
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
      
       
        $items = Item::where('nome_do_produto', 'like', "%{$query}%")
                     ->orWhere('codigo', 'like', "%{$query}%")
                     ->orWhere('descricao', 'like', "%{$query}%")
                     ->where('status', 'disponivel')
                     ->limit(10)
                     ->get();
        
        // Formatar dados para o component
        $formattedItems = $items->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->nome_do_produto,
                'sku' => $item->codigo,
                'price' => $item->preco,
                'formatted_price' => 'R$ ' . number_format($item->preco, 2, ',', '.'),
                'image_url' => $item->image ? asset('storage/' . $item->image) : asset('images/no-image.png'),
                'stock' => 'Disponível',
                'description' => $item->descricao ?? ''
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $formattedItems
        ]);
    }
	
	
	    // NOVO MÉTODO PARA INVENTÁRIO
	public function inventario(Request $request)
	{
		// Filtrar apenas itens modificados HOJE
		$query = Item::whereDate('updated_at', now()->toDateString());

		// 🎯 LÓGICA PRINCIPAL: Busca por código exato
		if ($request->filled('search')) {
			$codigoBuscado = trim($request->get('search'));
			
			// Buscar item por código EXATO
			$itemEncontrado = Item::where('codigo', $codigoBuscado)->first();
			
			if ($itemEncontrado) {
				// ✅ ITEM ENCONTRADO
				if ($request->filled('status')) {
					$novoStatus = $request->get('status');
					
					// Atualizar status e timestamp
					$itemEncontrado->update([
						'status' => $novoStatus,
						'updated_at' => now()
					]);
					
					$statusLabel = $this->getStatusLabel($novoStatus);
					$message = "✅ Item '{$itemEncontrado->nome_do_produto}' (código: {$codigoBuscado}) teve status alterado para '{$statusLabel}'!";
					
					// 🎯 MANTER STATUS SELECIONADO na URL
					return redirect()->route('inventario', ['status' => $novoStatus])->with('success', $message);
				}
				
				// Se não tem status selecionado, apenas encontrou o item
				$message = "✅ Item encontrado: '{$itemEncontrado->nome_do_produto}' (código: {$codigoBuscado})";
				return redirect()->route('inventario')->with('info', $message);
				
			} else {
				// ❌ ITEM NÃO ENCONTRADO - manter status se existir
				$params = $request->filled('status') ? ['status' => $request->get('status')] : [];
				return redirect()->route('inventario', $params)->with('warning', "❌ Item não encontrado com o código: {$codigoBuscado}");
			}
		}

		// Filtro por status (para navegação normal)
		if ($request->filled('status')) {
			$query->where('status', $request->status);
		}

		// Listar itens normalmente
		$items = $query->orderBy('updated_at', 'desc')->paginate(10);
		
		return view('admin.items.inventario', compact('items'));
	}

	private function getStatusLabel($status)
	{
		$labels = [
			'disponivel' => 'Disponível',
			'vendido' => 'Vendido',
			'reservado' => 'Reservado'
		];

		return $labels[$status] ?? $status;
	}
}
