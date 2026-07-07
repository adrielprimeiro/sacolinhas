<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avaliacao;
use App\Models\AvaliacaoItem;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Item;
use App\Models\ContaCorrente;
use App\Models\Lancamento;
use App\Models\Movimentacao;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvaliacaoController extends Controller
{
    /**
     * Display a listing of evaluations.
     */
    public function index(Request $request)
    {
        $query = Avaliacao::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('tipo_compra')) {
            $query->where('tipo_compra', $request->tipo_compra);
        }

        $avaliacoes = $query->orderBy('created_at', 'desc')->paginate(15);
        $clientes = Cliente::clientes()->orderBy('name')->get();

        return view('admin.avaliacoes.index', compact('avaliacoes', 'clientes'));
    }

    /**
     * Show the form for creating a new evaluation.
     */
    public function create()
    {
        $clientes = Cliente::clientes()->orderBy('name')->get();
        $categorias = $this->getTreeCategoriesList();
        $marcas = \App\Models\Marca::orderBy('total_registros', 'desc')->orderBy('nome')->get();
        return view('admin.avaliacoes.form', compact('clientes', 'categorias', 'marcas'));
    }

    /**
     * Store a newly created evaluation in storage (as draft).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo_compra' => 'required|in:avaliados,direta',
            'tipo_cliente' => 'required|in:clube,fora_clube',
            'frete' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.nome' => 'required|string|max:255',
            'items.*.categoria_id' => 'nullable|exists:categorias,id',
            'items.*.marca_id' => 'required|exists:marcas,id',
            'items.*.estado' => 'required|integer|min:1|max:10',
            'items.*.nota_curadoria' => 'required|integer|min:1|max:10',
            'items.*.cor' => 'nullable|string|max:100',
            'items.*.tamanho' => 'nullable|string|max:50',
            'items.*.preco_base' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $frete = $request->input('frete', 0.00) ?: 0.00;

            $avaliacao = Avaliacao::create([
                'user_id' => $validated['user_id'],
                'tipo_compra' => $validated['tipo_compra'],
                'tipo_cliente' => $validated['tipo_cliente'],
                'frete' => $frete,
                'pagamento_escolhido' => 'pendente',
                'status' => 'rascunho',
                'data_avaliacao' => now(),
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            $itemsData = $validated['items'];
            $itemCount = count($itemsData);
            $fretePorItem = $itemCount > 0 ? ($frete / $itemCount) : 0.00;

            $totalVenda = 0;
            $totalPayout = 0;

            foreach ($itemsData as $itemData) {
                $marcaObj = \App\Models\Marca::find($itemData['marca_id']);
                $avItem = new AvaliacaoItem([
                    'avaliacao_id' => $avaliacao->id,
                    'categoria_id' => $itemData['categoria_id'] ?? null,
                    'marca_id' => $itemData['marca_id'],
                    'nome' => $itemData['nome'],
                    'marca' => $marcaObj ? $marcaObj->nome : 'Sem Marca',
                    'estado' => $itemData['estado'],
                    'nota_curadoria' => $itemData['nota_curadoria'],
                    'cor' => $itemData['cor'] ?? null,
                    'tamanho' => $itemData['tamanho'] ?? null,
                    'preco_base' => $itemData['preco_base'],
                ]);

                // Se for Compra Direta, o valor de payout (crédito/dinheiro) é o próprio valor inserido em preco_base,
                // e o preco_venda é calculado multiplicando pelo Markup (2.0231)
                if ($avaliacao->tipo_compra === 'direta') {
                    $avItem->preco_venda = $itemData['preco_base'] * 2.023121387;
                    $avItem->taxa_curadoria = 0.00;
                    $avItem->payout_credito = $itemData['preco_base'];
                    $avItem->payout_dinheiro = $itemData['preco_base'];
                } else {
                    $avItem->recalculate($fretePorItem, $avaliacao->tipo_cliente);
                }

                $avItem->save();

                $totalVenda += $avItem->preco_venda;
                // Calculamos um payout temporário com base na preferência padrão (crédito)
                $totalPayout += ($avaliacao->tipo_cliente === 'clube' ? $avItem->payout_credito : $avItem->payout_credito);
            }

            $avaliacao->update([
                'total_venda' => $totalVenda,
                'total_payout' => $totalPayout,
            ]);

            DB::commit();
            return redirect()->route('admin.avaliacoes.index')->with('success', 'Avaliação de desapego salva como Rascunho com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar avaliação: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Ocorreu um erro ao salvar a avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified evaluation.
     */
    public function show(Avaliacao $avaliacao)
    {
        $avaliacao->load(['user', 'items.categoria']);
        return view('admin.avaliacoes.show', compact('avaliacao'));
    }

    /**
     * Show the form for editing the specified evaluation.
     */
    public function edit(Avaliacao $avaliacao)
    {
        if ($avaliacao->status !== 'rascunho') {
            return redirect()->route('admin.avaliacoes.index')->with('error', 'Apenas avaliações em status Rascunho podem ser editadas.');
        }

        $clientes = Cliente::clientes()->orderBy('name')->get();
        $categorias = $this->getTreeCategoriesList();
        $marcas = \App\Models\Marca::orderBy('total_registros', 'desc')->orderBy('nome')->get();
        $avaliacao->load('items');

        return view('admin.avaliacoes.form', compact('avaliacao', 'clientes', 'categorias', 'marcas'));
    }

    /**
     * Update the specified evaluation in storage.
     */
    public function update(Request $request, Avaliacao $avaliacao)
    {
        if ($avaliacao->status !== 'rascunho') {
            return redirect()->route('admin.avaliacoes.index')->with('error', 'Apenas avaliações em status Rascunho podem ser editadas.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tipo_compra' => 'required|in:avaliados,direta',
            'tipo_cliente' => 'required|in:clube,fora_clube',
            'frete' => 'nullable|numeric|min:0',
            'observacoes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.nome' => 'required|string|max:255',
            'items.*.categoria_id' => 'nullable|exists:categorias,id',
            'items.*.marca_id' => 'required|exists:marcas,id',
            'items.*.estado' => 'required|integer|min:1|max:10',
            'items.*.nota_curadoria' => 'required|integer|min:1|max:10',
            'items.*.cor' => 'nullable|string|max:100',
            'items.*.tamanho' => 'nullable|string|max:50',
            'items.*.preco_base' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $frete = $request->input('frete', 0.00) ?: 0.00;

            $avaliacao->update([
                'user_id' => $validated['user_id'],
                'tipo_compra' => $validated['tipo_compra'],
                'tipo_cliente' => $validated['tipo_cliente'],
                'frete' => $frete,
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            // Remover itens antigos
            $avaliacao->items()->delete();

            $itemsData = $validated['items'];
            $itemCount = count($itemsData);
            $fretePorItem = $itemCount > 0 ? ($frete / $itemCount) : 0.00;

            $totalVenda = 0;
            $totalPayout = 0;

            foreach ($itemsData as $itemData) {
                $marcaObj = \App\Models\Marca::find($itemData['marca_id']);
                $avItem = new AvaliacaoItem([
                    'avaliacao_id' => $avaliacao->id,
                    'categoria_id' => $itemData['categoria_id'] ?? null,
                    'marca_id' => $itemData['marca_id'],
                    'nome' => $itemData['nome'],
                    'marca' => $marcaObj ? $marcaObj->nome : 'Sem Marca',
                    'estado' => $itemData['estado'],
                    'nota_curadoria' => $itemData['nota_curadoria'],
                    'cor' => $itemData['cor'] ?? null,
                    'tamanho' => $itemData['tamanho'] ?? null,
                    'preco_base' => $itemData['preco_base'],
                ]);

                if ($avaliacao->tipo_compra === 'direta') {
                    $avItem->preco_venda = $itemData['preco_base'] * 2.023121387;
                    $avItem->taxa_curadoria = 0.00;
                    $avItem->payout_credito = $itemData['preco_base'];
                    $avItem->payout_dinheiro = $itemData['preco_base'];
                } else {
                    $avItem->recalculate($fretePorItem, $avaliacao->tipo_cliente);
                }

                $avItem->save();

                $totalVenda += $avItem->preco_venda;
                $totalPayout += ($avaliacao->tipo_cliente === 'clube' ? $avItem->payout_credito : $avItem->payout_credito);
            }

            $avaliacao->update([
                'total_venda' => $totalVenda,
                'total_payout' => $totalPayout,
            ]);

            DB::commit();
            return redirect()->route('admin.avaliacoes.index')->with('success', 'Avaliação atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar avaliação: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Ocorreu um erro ao atualizar a avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified evaluation from storage (only if draft).
     */
    public function destroy(Avaliacao $avaliacao)
    {
        if ($avaliacao->status !== 'rascunho') {
            return redirect()->route('admin.avaliacoes.index')->with('error', 'Apenas avaliações em status Rascunho podem ser deletadas.');
        }

        $avaliacao->delete();
        return redirect()->route('admin.avaliacoes.index')->with('success', 'Lote de avaliação removido com sucesso!');
    }

    /**
     * Finaliza a avaliação, gera créditos/pagamento e cadastra os itens no estoque.
     */
    public function finalize(Request $request, Avaliacao $avaliacao)
    {
        if ($avaliacao->status !== 'rascunho') {
            return redirect()->route('admin.avaliacoes.index')->with('error', 'Esta avaliação já foi finalizada ou cancelada.');
        }

        $validated = $request->validate([
            'pagamento_escolhido' => 'required|in:credito,dinheiro',
        ]);

        $pagamento = $validated['pagamento_escolhido'];

        $items = $avaliacao->items;
        if ($items->isEmpty()) {
            return redirect()->back()->with('error', 'Não é possível finalizar uma avaliação sem itens.');
        }

        DB::beginTransaction();
        try {
            $itemCount = count($items);
            $fretePorItem = $avaliacao->frete / $itemCount;

            $totalVenda = 0;
            $totalPayout = 0;

            foreach ($items as $item) {
                // Recalcula pra ter certeza absoluta
                if ($avaliacao->tipo_compra === 'direta') {
                    $item->preco_venda = $item->preco_base * 2.023121387;
                    $item->taxa_curadoria = 0.00;
                    $item->payout_credito = $item->preco_base;
                    $item->payout_dinheiro = $item->preco_base;
                } else {
                    $item->recalculate($fretePorItem, $avaliacao->tipo_cliente);
                }
                $item->save();

                $payout = $pagamento === 'credito' ? $item->payout_credito : $item->payout_dinheiro;

                // 1. Cadastra o item no estoque (tabela items) se o preço de venda > 0
                if ($item->preco_venda > 0) {
                    $uniqueCode = 'DES-' . str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) . '-' . str_pad($item->id, 3, '0', STR_PAD_LEFT);
                    
                    $marcaTxt = $item->marcaRel ? $item->marcaRel->nome : ($item->marca ?: 'Sem Marca');

                    $newItem = Item::create([
                        'codigo' => $uniqueCode,
                        'nome_do_produto' => $item->nome,
                        'custo' => $payout,
                        'preco' => $item->preco_venda,
                        'codigo_da_categoria' => $item->categoria_id,
                        'marca' => $marcaTxt,
                        'estado' => 'Usado - Nota ' . $item->estado . '/10',
                        'cor' => $item->cor,
                        'tamanho' => $item->tamanho,
                        'status' => 'disponivel',
                    ]);

                    // Vincula categoria na tabela pivô se existir relacionamento belongsToMany
                    if ($item->categoria_id) {
                        $newItem->categorias()->sync([$item->categoria_id]);
                    }

                    $item->update(['item_id' => $newItem->id]);
                }

                $totalVenda += $item->preco_venda;
                $totalPayout += $payout;
            }

            // 2. Realiza o lançamento contábil/carteira do repasse
            if ($totalPayout > 0) {
                if ($pagamento === 'credito') {
                    // Lançamento de crédito na conta corrente virtual do fornecedor
                    ContaCorrente::create([
                        'user_id' => $avaliacao->user_id,
                        'tipo_movimentacao' => 'credito',
                        'valor' => $totalPayout,
                        'descricao' => 'Crédito por Avaliação de Desapego #' . $avaliacao->id,
                        'referencia_tipo' => 'avaliacao',
                        'referencia_id' => $avaliacao->id,
                        'classificacao_id' => 19, // Classificação Padrão de Avaliação
                        'data_movimentacao' => now(),
                    ]);
                } else {
                    $userName = $avaliacao->user ? $avaliacao->user->name : 'Fornecedor #' . $avaliacao->user_id;
                    $userDoc = $avaliacao->user 
                        ? ($avaliacao->user->cpf ?? $avaliacao->user->whatsapp ?? $avaliacao->user->email)
                        : 'FORN-' . $avaliacao->user_id;

                    $pessoa = Pessoa::firstOrCreate(
                        ['user_id' => $avaliacao->user_id],
                        [
                            'nome' => $userName,
                            'documento' => $userDoc ?? 'FORN-' . $avaliacao->user_id,
                            'tipo' => 'cliente_circular',
                        ]
                    );

                    $lancamento = Lancamento::create([
                        'tipo' => 'despesa',
                        'status' => 'pago',
                        'pessoa_id' => $pessoa->id,
                        'classificacao_financeira_id' => 19, // Classificação de Avaliação
                        'data_emissao' => now(),
                        'data_vencimento' => now(),
                        'valor_total' => $totalPayout,
                        'descricao' => 'Pagamento em Dinheiro - Avaliação de Desapego #' . $avaliacao->id,
                        'referencia_tipo' => 'avaliacao',
                        'referencia_id' => $avaliacao->id,
                    ]);

                    $contaCaixa = ContaBancaria::where('nome', 'like', '%caixa%')->first();
                    $contaId = $contaCaixa ? $contaCaixa->id : 3;

                    Movimentacao::create([
                        'lancamento_id' => $lancamento->id,
                        'conta_bancaria_id' => $contaId,
                        'data_pagamento' => now(),
                        'valor_pago' => $totalPayout,
                        'forma_pagamento' => 'dinheiro',
                    ]);
                }
            }

            // 3. Atualiza status da avaliação
            $avaliacao->update([
                'status' => 'finalizada',
                'pagamento_escolhido' => $pagamento,
                'total_venda' => $totalVenda,
                'total_payout' => $totalPayout,
            ]);

            DB::commit();
            return redirect()->route('admin.avaliacoes.show', $avaliacao)->with('success', 'Avaliação finalizada com sucesso! Itens criados no estoque.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao finalizar avaliação: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocorreu um erro ao finalizar a avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Cancela uma avaliação finalizada, removendo os itens do estoque e estornando o financeiro.
     */
    public function cancel(Avaliacao $avaliacao)
    {
        if ($avaliacao->status !== 'finalizada') {
            return redirect()->route('admin.avaliacoes.index')->with('error', 'Apenas avaliações finalizadas podem ser canceladas.');
        }

        DB::beginTransaction();
        try {
            // Verificar se alguma peça já foi vendida ou está em sacolinha
            $itemIds = $avaliacao->items()->whereNotNull('item_id')->pluck('item_id')->toArray();
            if (!empty($itemIds)) {
                $soldItems = Item::whereIn('id', $itemIds)->where('status', '!=', 'disponivel')->count();
                if ($soldItems > 0) {
                    return redirect()->back()->with('error', 'Não é possível cancelar esta avaliação pois algumas peças já foram vendidas ou estão em sacolinhas.');
                }

                // Deletar itens do estoque
                Item::whereIn('id', $itemIds)->delete();
            }

            // Reverter Financeiro
            if ($avaliacao->pagamento_escolhido === 'credito') {
                // Deleta a movimentação da conta corrente (isso deleta automaticamente o lançamento/movimentação associados via hooks)
                ContaCorrente::where('referencia_tipo', 'avaliacao')
                    ->where('referencia_id', $avaliacao->id)
                    ->delete();
            } else {
                // Pagamento em dinheiro - deletar Lançamento e suas Movimentações
                $lancamento = Lancamento::where('referencia_tipo', 'avaliacao')
                    ->where('referencia_id', $avaliacao->id)
                    ->first();
                if ($lancamento) {
                    $lancamento->movimentacoes()->delete();
                    $lancamento->delete();
                }
            }

            // Resetar referências dos itens
            $avaliacao->items()->update(['item_id' => null]);

            // Atualiza status
            $avaliacao->update([
                'status' => 'cancelada',
            ]);

            DB::commit();
            return redirect()->route('admin.avaliacoes.index')->with('success', 'Avaliação cancelada com sucesso! Itens removidos do estoque e acerto contábil estornado.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao cancelar avaliação: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocorreu um erro ao cancelar a avaliação: ' . $e->getMessage());
        }
    }

    /**
     * Retorna a lista de categorias ordenada de forma hierárquica.
     */
    private function getTreeCategoriesList()
    {
        $categorias = [];
        $buildTreeList = function($cats, $level = 0, $path = '') use (&$buildTreeList, &$categorias) {
            foreach ($cats as $cat) {
                $indent = str_repeat("\u{00A0}\u{00A0}\u{00A0}\u{00A0}", $level);
                $prefix = $level > 0 ? '↳ ' : '';
                $currentPath = $path ? $path . ' › ' . $cat->name : $cat->name;
                
                $categorias[] = [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'formatted_name' => $indent . $prefix . $cat->name,
                    'path' => $currentPath,
                    'preco_base' => (float) $cat->preco_base,
                ];
                
                if ($cat->children->isNotEmpty()) {
                    $buildTreeList($cat->children, $level + 1, $currentPath);
                }
            }
        };

        $rootCats = Categoria::whereNull('parent_id')->with('children')->orderBy('name')->get();
        $buildTreeList($rootCats);

        return $categorias;
    }
}
