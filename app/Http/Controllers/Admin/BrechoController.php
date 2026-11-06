<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brecho;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BrechoController extends Controller
{
    public function index()
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $brechos = Brecho::withCount(['items', 'lives', 'users'])->with('users')->orderBy('id', 'asc')->get();

        return view('admin.brechos.index', compact('brechos'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'chave_pix' => 'nullable|string|max:255',
            'tipo_chave_pix' => 'nullable|string|in:cpf,cnpj,email,telefone,aleatoria',
            'prefixo_codigo' => 'nullable|string|max:6',
            'taxa_cadastro_peca' => 'nullable|numeric|min:0',
            'comissao_porcentagem' => 'nullable|numeric|min:0|max:100',
            'fornecedor_id' => 'nullable|string|max:50',
            'fornecedor_nome' => 'nullable|string|max:255',
            'operador_nome' => 'nullable|string|max:255',
            'operador_email' => 'nullable|email|max:255|unique:users,email',
            'operador_password' => 'nullable|string|min:6',
        ]);

        $slug = Str::slug($validated['nome']);
        // Garante slug único
        $originalSlug = $slug;
        $count = 1;
        while (Brecho::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        $prefixo = !empty($validated['prefixo_codigo']) ? strtoupper(trim($validated['prefixo_codigo'])) : null;
        $taxaCadastro = isset($validated['taxa_cadastro_peca']) && $validated['taxa_cadastro_peca'] !== '' ? (float) $validated['taxa_cadastro_peca'] : 1.80;
        $comissao = isset($validated['comissao_porcentagem']) && $validated['comissao_porcentagem'] !== '' ? (float) $validated['comissao_porcentagem'] : 20.00;

        $fornecedorId = !empty($validated['fornecedor_id']) ? $validated['fornecedor_id'] : null;
        $fornecedorNome = $request->input('fornecedor_nome');
        if ($fornecedorId && empty($fornecedorNome)) {
            if (str_starts_with($fornecedorId, 'user_')) {
                $u = \App\Models\User::find(str_replace('user_', '', $fornecedorId));
                if ($u) $fornecedorNome = $u->name . ($u->apelido ? " ({$u->apelido})" : '');
            } elseif (str_starts_with($fornecedorId, 'pessoa_')) {
                $p = \App\Models\Pessoa::find(str_replace('pessoa_', '', $fornecedorId));
                if ($p) $fornecedorNome = $p->nome;
            }
        }

        $brechoData = [
            'nome' => $validated['nome'],
            'documento' => $validated['documento'] ?? null,
            'telefone' => $validated['telefone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'chave_pix' => $validated['chave_pix'] ?? null,
            'tipo_chave_pix' => $validated['tipo_chave_pix'] ?? null,
            'prefixo_codigo' => $prefixo,
            'taxa_cadastro_peca' => $taxaCadastro,
            'comissao_porcentagem' => $comissao,
            'fornecedor_id' => $fornecedorId,
            'fornecedor_nome' => $fornecedorNome,
            'slug' => $slug,
            'ativo' => true,
        ];

        $brecho = Brecho::create($brechoData);

        if (!empty($validated['operador_email']) && !empty($validated['operador_password'])) {
            $operador = \App\Models\User::create([
                'name' => $validated['operador_nome'] ?: $validated['nome'],
                'email' => $validated['operador_email'],
                'password' => \Illuminate\Support\Facades\Hash::make($validated['operador_password']),
                'whatsapp' => $validated['whatsapp'] ?? null,
                'role' => 'brecho_admin',
                'brecho_id' => $brecho->id,
                'is_admin' => 0,
            ]);

            return redirect()->back()->with('success', "Brechó '{$brecho->nome}' e usuário de acesso '{$operador->email}' criados com sucesso!");
        }

        return redirect()->back()->with('success', "Brechó '{$brecho->nome}' cadastrado com sucesso! Crie o acesso de login clicando em '+ Operador'.");
    }

    public function update(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'documento' => 'nullable|string|max:20',
            'telefone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'chave_pix' => 'nullable|string|max:255',
            'tipo_chave_pix' => 'nullable|string|in:cpf,cnpj,email,telefone,aleatoria',
            'prefixo_codigo' => 'nullable|string|max:6',
            'taxa_cadastro_peca' => 'nullable|numeric|min:0',
            'comissao_porcentagem' => 'nullable|numeric|min:0|max:100',
            'fornecedor_id' => 'nullable|string|max:50',
            'fornecedor_nome' => 'nullable|string|max:255',
            'ativo' => 'required|boolean',
        ]);

        if (array_key_exists('prefixo_codigo', $validated)) {
            $validated['prefixo_codigo'] = !empty($validated['prefixo_codigo']) ? strtoupper(trim($validated['prefixo_codigo'])) : null;
        }

        if (array_key_exists('fornecedor_id', $validated)) {
            $fId = !empty($validated['fornecedor_id']) ? $validated['fornecedor_id'] : null;
            $validated['fornecedor_id'] = $fId;
            $fNome = $request->input('fornecedor_nome');
            if ($fId && empty($fNome)) {
                if (str_starts_with($fId, 'user_')) {
                    $u = \App\Models\User::find(str_replace('user_', '', $fId));
                    if ($u) $fNome = $u->name . ($u->apelido ? " ({$u->apelido})" : '');
                } elseif (str_starts_with($fId, 'pessoa_')) {
                    $p = \App\Models\Pessoa::find(str_replace('pessoa_', '', $fId));
                    if ($p) $fNome = $p->nome;
                }
            }
            $validated['fornecedor_nome'] = $fId ? $fNome : null;
        }

        $brecho->update($validated);

        return redirect()->back()->with('success', "Brechó '{$brecho->nome}' atualizado com sucesso!");
    }

    public function createOperator(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $isAdminMatriz = ($brecho->id == 1);
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'whatsapp' => $validated['whatsapp'] ?? null,
            'role' => $isAdminMatriz ? 'admin' : 'brecho_admin',
            'brecho_id' => $brecho->id,
            'is_admin' => $isAdminMatriz ? 1 : 0,
        ]);

        return redirect()->back()->with('success', "Usuário '{$user->name}' ({$user->email}) criado com sucesso para o brechó '{$brecho->nome}'!");
    }

    /**
     * Tela de Prestação de Contas & Consignação do Brechó Parceiro.
     */
    public function prestacaoContas(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        if ($brecho->id === 1) {
            return redirect()->route('admin.brechos.index')->with('error', 'A Minha Mania (Matriz) não opera por regime de consignação.');
        }

        // 1. Resolver usuário vinculado na carteira (Conta Corrente)
        $user = null;
        $userId = null;
        if ($brecho->fornecedor_id) {
            if (str_starts_with($brecho->fornecedor_id, 'user_')) {
                $userId = (int) str_replace('user_', '', $brecho->fornecedor_id);
                $user = \App\Models\User::find($userId);
            } elseif (str_starts_with($brecho->fornecedor_id, 'pessoa_')) {
                $pessoa = \App\Models\Pessoa::find(str_replace('pessoa_', '', $brecho->fornecedor_id));
                if ($pessoa && $pessoa->user_id) {
                    $userId = $pessoa->user_id;
                    $user = \App\Models\User::find($userId);
                }
            }
        }

        // 2. Itens consignados do parceiro
        $queryItems = \App\Models\Item::where(function ($q) use ($brecho) {
            $q->where('parceiro_id', $brecho->id);
            if ($brecho->prefixo_codigo) {
                $q->orWhere('codigo', 'like', $brecho->prefixo_codigo . '-%');
            }
        });

        // Filtro de status se enviado
        $statusFiltro = $request->input('status', 'todos');
        if ($statusFiltro === 'disponivel') {
            $queryItems->whereIn('status', ['disponivel', 'estoque'])
                ->whereNotExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('sacolinhas')
                        ->whereColumn('sacolinhas.item_id', 'items.id')
                        ->where('sacolinhas.status', '!=', 'pedido');
                });
        } elseif ($statusFiltro === 'vendido') {
            $queryItems->where('status', 'vendido');
        } elseif ($statusFiltro === 'em_sacolinha' || $statusFiltro === 'sacolinha') {
            $queryItems->where(function($q) {
                $q->whereIn('status', ['sacolinha', 'em_sacolinha', 'solicitado na live'])
                  ->orWhereExists(function($sub) {
                      $sub->select(DB::raw(1))
                          ->from('sacolinhas')
                          ->whereColumn('sacolinhas.item_id', 'items.id')
                          ->where('sacolinhas.status', '!=', 'pedido');
                  });
            });
        }

        // Termo de busca de item
        if ($request->filled('q')) {
            $term = $request->input('q');
            $queryItems->where(function ($q) use ($term) {
                $q->where('nome_do_produto', 'like', "%{$term}%")
                  ->orWhere('codigo', 'like', "%{$term}%")
                  ->orWhere('marca', 'like', "%{$term}%");
            });
        }

        $items = $queryItems->orderBy('id', 'desc')->paginate(30)->withQueryString();

        // Enriquecer dados de venda/sacolinha para os itens da página
        $itemIds = $items->pluck('id')->toArray();
        $pedidosInfo = DB::table('items_pedido')
            ->join('pedidos', 'pedidos.id', '=', 'items_pedido.pedido_id')
            ->whereIn('items_pedido.item_id', $itemIds)
            ->select('items_pedido.item_id', 'pedidos.id as pedido_id', 'pedidos.data_pedido', 'pedidos.status_pedido', 'pedidos.status_pagamento')
            ->get()
            ->keyBy('item_id');

        $sacolinhasInfo = DB::table('sacolinhas')
            ->leftJoin('users', 'users.id', '=', 'sacolinhas.user_id')
            ->leftJoin('lives', 'lives.id', '=', 'sacolinhas.live_id')
            ->whereIn('sacolinhas.item_id', $itemIds)
            ->where('sacolinhas.status', '!=', 'pedido')
            ->select('sacolinhas.item_id', 'users.name as cliente_nome', 'lives.data as live_data', 'lives.tipo_live', 'sacolinhas.created_at as sacolinha_data')
            ->get()
            ->keyBy('item_id');

        foreach ($items as $item) {
            $item->pedido_info = $pedidosInfo->get($item->id);
            $item->sacolinha_info = $sacolinhasInfo->get($item->id);
        }

        // 3. Cálculos de totais gerais (sem paginação)
        $allItems = \App\Models\Item::where(function ($q) use ($brecho) {
            $q->where('parceiro_id', $brecho->id);
            if ($brecho->prefixo_codigo) {
                $q->orWhere('codigo', 'like', $brecho->prefixo_codigo . '-%');
            }
        })->get(['id', 'codigo', 'preco', 'custo', 'status', 'created_at']);

        $itemIdsEmSacolinha = DB::table('sacolinhas')
            ->whereIn('item_id', $allItems->pluck('id'))
            ->where('status', '!=', 'pedido')
            ->pluck('item_id')
            ->toArray();

        $totalPecas = $allItems->count();
        $pecasSacolinha = $allItems->filter(function($i) use ($itemIdsEmSacolinha) {
            return in_array($i->status, ['sacolinha', 'em_sacolinha', 'solicitado na live']) || in_array($i->id, $itemIdsEmSacolinha);
        })->count();
        $pecasVendidas = $allItems->where('status', 'vendido')->count();
        $pecasEstoque = $allItems->filter(function($i) use ($itemIdsEmSacolinha) {
            return in_array($i->status, ['disponivel', 'estoque']) && !in_array($i->id, $itemIdsEmSacolinha);
        })->count();

        $valorBrutoTotal = (float) $allItems->sum('preco');
        $itensVendidos = $allItems->where('status', 'vendido');
        $valorBrutoVendido = (float) $itensVendidos->sum('preco');
        $valorRepasseVendido = (float) $itensVendidos->sum('custo');
        $comissaoManiaVendido = max(0, $valorBrutoVendido - $valorRepasseVendido);

        // Taxa de cadastro retida
        $taxaPecaUnit = (float) ($brecho->taxa_cadastro_peca ?: 1.80);
        $taxaCadastroTotal = $totalPecas * $taxaPecaUnit;

        // 4. Extrato da Carteira do Parceiro (Conta Corrente)
        $carteiraMovimentacoes = collect();
        $saldoCarteira = 0.00;
        $totalRepassadoPago = 0.00;

        if ($user) {
            $saldoCarteira = (float) (\App\Models\ContaCorrente::where('user_id', $user->id)
                ->orderBy('data_movimentacao', 'desc')
                ->orderBy('id', 'desc')
                ->value('saldo_atual') ?? 0);

            $carteiraMovimentacoes = \App\Models\ContaCorrente::where('user_id', $user->id)
                ->orderBy('data_movimentacao', 'desc')
                ->orderBy('id', 'desc')
                ->take(30)
                ->get();

            $totalRepassadoPago = (float) \App\Models\ContaCorrente::where('user_id', $user->id)
                ->where('tipo_movimentacao', 'debito')
                ->where(function ($q) {
                    $q->where('referencia_tipo', 'like', '%repasse%')
                      ->orWhere('descricao', 'like', '%repasse%')
                      ->orWhere('descricao', 'like', '%pix%');
                })->sum('valor');
        }

        // Lotes de avaliação deste parceiro
        $avaliacoes = \App\Models\Avaliacao::where('parceiro_id', $brecho->id)
            ->when($userId, fn($q) => $q->orWhere('user_id', $userId))
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return view('admin.brechos.prestacao_contas', compact(
            'brecho',
            'user',
            'items',
            'totalPecas',
            'pecasEstoque',
            'pecasSacolinha',
            'pecasVendidas',
            'valorBrutoTotal',
            'valorBrutoVendido',
            'valorRepasseVendido',
            'comissaoManiaVendido',
            'taxaCadastroTotal',
            'saldoCarteira',
            'totalRepassadoPago',
            'carteiraMovimentacoes',
            'avaliacoes',
            'statusFiltro'
        ));
    }

    /**
     * Gera o relatório em PDF da Prestação de Contas & Consignação do Brechó Parceiro.
     */
    public function prestacaoContasPdf(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        if ($brecho->id === 1) {
            return redirect()->route('admin.brechos.index')->with('error', 'A Minha Mania (Matriz) não opera por regime de consignação.');
        }

        // 1. Resolver usuário vinculado na carteira (Conta Corrente)
        $user = null;
        $userId = null;
        if ($brecho->fornecedor_id) {
            if (str_starts_with($brecho->fornecedor_id, 'user_')) {
                $userId = (int) str_replace('user_', '', $brecho->fornecedor_id);
                $user = \App\Models\User::find($userId);
            } elseif (str_starts_with($brecho->fornecedor_id, 'pessoa_')) {
                $pessoa = \App\Models\Pessoa::find(str_replace('pessoa_', '', $brecho->fornecedor_id));
                if ($pessoa && $pessoa->user_id) {
                    $userId = $pessoa->user_id;
                    $user = \App\Models\User::find($userId);
                }
            }
        }

        // 2. Itens consignados do parceiro
        $queryItems = \App\Models\Item::where(function ($q) use ($brecho) {
            $q->where('parceiro_id', $brecho->id);
            if ($brecho->prefixo_codigo) {
                $q->orWhere('codigo', 'like', $brecho->prefixo_codigo . '-%');
            }
        });

        $statusFiltro = $request->input('status', 'todos');
        if ($statusFiltro === 'disponivel') {
            $queryItems->whereIn('status', ['disponivel', 'estoque'])
                ->whereNotExists(function($sub) {
                    $sub->select(DB::raw(1))
                        ->from('sacolinhas')
                        ->whereColumn('sacolinhas.item_id', 'items.id')
                        ->where('sacolinhas.status', '!=', 'pedido');
                });
        } elseif ($statusFiltro === 'vendido') {
            $queryItems->where('status', 'vendido');
        } elseif ($statusFiltro === 'em_sacolinha' || $statusFiltro === 'sacolinha') {
            $queryItems->where(function($q) {
                $q->whereIn('status', ['sacolinha', 'em_sacolinha', 'solicitado na live'])
                  ->orWhereExists(function($sub) {
                      $sub->select(DB::raw(1))
                          ->from('sacolinhas')
                          ->whereColumn('sacolinhas.item_id', 'items.id')
                          ->where('sacolinhas.status', '!=', 'pedido');
                  });
            });
        }

        if ($request->filled('q')) {
            $term = $request->input('q');
            $queryItems->where(function ($q) use ($term) {
                $q->where('nome_do_produto', 'like', "%{$term}%")
                  ->orWhere('codigo', 'like', "%{$term}%")
                  ->orWhere('marca', 'like', "%{$term}%");
            });
        }

        $items = $queryItems->orderBy('id', 'desc')->get();

        // Enriquecer dados de venda/sacolinha para os itens
        $itemIds = $items->pluck('id')->toArray();
        $pedidosInfo = DB::table('items_pedido')
            ->join('pedidos', 'pedidos.id', '=', 'items_pedido.pedido_id')
            ->whereIn('items_pedido.item_id', $itemIds)
            ->select('items_pedido.item_id', 'pedidos.id as pedido_id', 'pedidos.data_pedido', 'pedidos.status_pedido', 'pedidos.status_pagamento')
            ->get()
            ->keyBy('item_id');

        $sacolinhasInfo = DB::table('sacolinhas')
            ->leftJoin('users', 'users.id', '=', 'sacolinhas.user_id')
            ->leftJoin('lives', 'lives.id', '=', 'sacolinhas.live_id')
            ->whereIn('sacolinhas.item_id', $itemIds)
            ->where('sacolinhas.status', '!=', 'pedido')
            ->select('sacolinhas.item_id', 'users.name as cliente_nome', 'lives.data as live_data', 'lives.tipo_live', 'sacolinhas.created_at as sacolinha_data')
            ->get()
            ->keyBy('item_id');

        foreach ($items as $item) {
            $item->pedido_info = $pedidosInfo->get($item->id);
            $item->sacolinha_info = $sacolinhasInfo->get($item->id);
        }

        // 3. Cálculos de totais gerais
        $allItems = \App\Models\Item::where(function ($q) use ($brecho) {
            $q->where('parceiro_id', $brecho->id);
            if ($brecho->prefixo_codigo) {
                $q->orWhere('codigo', 'like', $brecho->prefixo_codigo . '-%');
            }
        })->get(['id', 'codigo', 'preco', 'custo', 'status', 'created_at']);

        $itemIdsEmSacolinha = DB::table('sacolinhas')
            ->whereIn('item_id', $allItems->pluck('id'))
            ->where('status', '!=', 'pedido')
            ->pluck('item_id')
            ->toArray();

        $totalPecas = $allItems->count();
        $pecasSacolinha = $allItems->filter(function($i) use ($itemIdsEmSacolinha) {
            return in_array($i->status, ['sacolinha', 'em_sacolinha', 'solicitado na live']) || in_array($i->id, $itemIdsEmSacolinha);
        })->count();
        $pecasVendidas = $allItems->where('status', 'vendido')->count();
        $pecasEstoque = $allItems->filter(function($i) use ($itemIdsEmSacolinha) {
            return in_array($i->status, ['disponivel', 'estoque']) && !in_array($i->id, $itemIdsEmSacolinha);
        })->count();

        $valorBrutoTotal = (float) $allItems->sum('preco');
        $itensVendidos = $allItems->where('status', 'vendido');
        $valorBrutoVendido = (float) $itensVendidos->sum('preco');
        $valorRepasseVendido = (float) $itensVendidos->sum('custo');
        $comissaoManiaVendido = max(0, $valorBrutoVendido - $valorRepasseVendido);

        $taxaPecaUnit = (float) ($brecho->taxa_cadastro_peca ?: 1.80);
        $taxaCadastroTotal = $totalPecas * $taxaPecaUnit;

        // 4. Extrato da Carteira do Parceiro
        $carteiraMovimentacoes = collect();
        $saldoCarteira = 0.00;
        $totalRepassadoPago = 0.00;

        if ($user) {
            $saldoCarteira = (float) (\App\Models\ContaCorrente::where('user_id', $user->id)
                ->orderBy('data_movimentacao', 'desc')
                ->orderBy('id', 'desc')
                ->value('saldo_atual') ?? 0);

            $carteiraMovimentacoes = \App\Models\ContaCorrente::where('user_id', $user->id)
                ->orderBy('data_movimentacao', 'desc')
                ->orderBy('id', 'desc')
                ->take(15)
                ->get();

            $totalRepassadoPago = (float) \App\Models\ContaCorrente::where('user_id', $user->id)
                ->where('tipo_movimentacao', 'debito')
                ->where(function ($q) {
                    $q->where('referencia_tipo', 'like', '%repasse%')
                      ->orWhere('descricao', 'like', '%repasse%')
                      ->orWhere('descricao', 'like', '%pix%');
                })->sum('valor');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.brechos.prestacao_contas_pdf', compact(
            'brecho',
            'user',
            'items',
            'totalPecas',
            'pecasEstoque',
            'pecasSacolinha',
            'pecasVendidas',
            'valorBrutoTotal',
            'valorBrutoVendido',
            'valorRepasseVendido',
            'comissaoManiaVendido',
            'taxaCadastroTotal',
            'saldoCarteira',
            'totalRepassadoPago',
            'carteiraMovimentacoes',
            'statusFiltro'
        ))->setPaper('a4', 'portrait');

        $fileName = 'prestacao-contas-' . \Illuminate\Support\Str::slug($brecho->nome) . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->stream($fileName);
    }

    /**
     * Sincroniza peças vendidas creditando na Carteira (Conta Corrente) do parceiro.
     */
    public function sincronizarVendasCarteira(Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $userId = null;
        if ($brecho->fornecedor_id && str_starts_with($brecho->fornecedor_id, 'user_')) {
            $userId = (int) str_replace('user_', '', $brecho->fornecedor_id);
        } elseif ($brecho->fornecedor_id && str_starts_with($brecho->fornecedor_id, 'pessoa_')) {
            $pessoa = \App\Models\Pessoa::find(str_replace('pessoa_', '', $brecho->fornecedor_id));
            $userId = $pessoa?->user_id;
        }

        if (!$userId) {
            return redirect()->back()->with('error', 'O parceiro não possui um usuário da Mania vinculado. Vincule um fornecedor no cadastro do brechó antes de sincronizar a carteira.');
        }

        // Busca todas as peças vendidas do parceiro
        $soldItems = \App\Models\Item::where(function ($q) use ($brecho) {
            $q->where('parceiro_id', $brecho->id);
            if ($brecho->prefixo_codigo) {
                $q->orWhere('codigo', 'like', $brecho->prefixo_codigo . '-%');
            }
        })->where('status', 'vendido')->get();

        if ($soldItems->isEmpty()) {
            return redirect()->back()->with('info', 'Nenhuma peça vendida para este parceiro no momento.');
        }

        // IDs já creditados
        $alreadyCreditedItemIds = \App\Models\ContaCorrente::where('user_id', $userId)
            ->where('referencia_tipo', 'consignacao_item')
            ->pluck('referencia_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $novosCreditos = 0;
        $totalCreditado = 0;
        $earliestDate = now()->toDateString();

        DB::transaction(function () use ($soldItems, $alreadyCreditedItemIds, $userId, &$novosCreditos, &$totalCreditado, &$earliestDate) {
            foreach ($soldItems as $item) {
                if (in_array($item->id, $alreadyCreditedItemIds)) {
                    continue;
                }

                $valorRepasse = (float) ($item->custo > 0 ? $item->custo : ($item->preco * 0.8));
                $dataMov = $item->updated_at ?? now();
                if ($dataMov->toDateString() < $earliestDate) {
                    $earliestDate = $dataMov->toDateString();
                }

                \App\Models\ContaCorrente::create([
                    'user_id' => $userId,
                    'tipo_movimentacao' => 'credito',
                    'valor' => $valorRepasse,
                    'descricao' => "Venda Consignação: {$item->codigo} - {$item->nome_do_produto} (Venda: R$ " . number_format($item->preco, 2, ',', '.') . ")",
                    'referencia_tipo' => 'consignacao_item',
                    'referencia_id' => $item->id,
                    'classificacao_id' => 19,
                    'saldo_anterior' => 0,
                    'saldo_atual' => 0,
                    'data_movimentacao' => $dataMov,
                ]);

                $novosCreditos++;
                $totalCreditado += $valorRepasse;
            }

            if ($novosCreditos > 0) {
                \App\Jobs\RecalcularSaldosJob::dispatchSync($userId, $earliestDate);
            }
        });

        if ($novosCreditos === 0) {
            return redirect()->back()->with('info', 'Todas as peças vendidas já estavam devidamente creditadas na carteira do parceiro.');
        }

        return redirect()->back()->with('success', "{$novosCreditos} peça(s) vendida(s) sincronizada(s) na carteira com sucesso! Total creditado: R$ " . number_format($totalCreditado, 2, ',', '.'));
    }

    /**
     * Registra pagamento de repasse (Pix / Transferência) baixando o saldo na Carteira.
     */
    public function registrarRepasse(Request $request, Brecho $brecho)
    {
        abort_if(!auth()->user() || auth()->user()->role !== 'admin_master', 403, 'Acesso restrito ao Master.');

        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'data_pagamento' => 'required|date',
            'forma_pagamento' => 'required|string|max:50',
            'comprovante_transacao' => 'nullable|string|max:255',
            'observacoes' => 'nullable|string|max:500',
        ]);

        $userId = null;
        if ($brecho->fornecedor_id && str_starts_with($brecho->fornecedor_id, 'user_')) {
            $userId = (int) str_replace('user_', '', $brecho->fornecedor_id);
        } elseif ($brecho->fornecedor_id && str_starts_with($brecho->fornecedor_id, 'pessoa_')) {
            $pessoa = \App\Models\Pessoa::find(str_replace('pessoa_', '', $brecho->fornecedor_id));
            $userId = $pessoa?->user_id;
        }

        if (!$userId) {
            return redirect()->back()->with('error', 'O parceiro não possui um usuário da Mania vinculado para registrar a baixa na carteira.');
        }

        DB::transaction(function () use ($validated, $userId, $brecho) {
            $desc = "Repasse Consignação ({$validated['forma_pagamento']}): {$brecho->nome}";
            if (!empty($validated['comprovante_transacao'])) {
                $desc .= " - Comp/Pix: {$validated['comprovante_transacao']}";
            }

            $mov = \App\Models\ContaCorrente::create([
                'user_id' => $userId,
                'tipo_movimentacao' => 'debito',
                'valor' => $validated['valor'],
                'descricao' => $desc,
                'referencia_tipo' => 'consignacao_repasse',
                'referencia_id' => $brecho->id,
                'classificacao_id' => 19,
                'saldo_anterior' => 0,
                'saldo_atual' => 0,
                'data_movimentacao' => $validated['data_pagamento'] . ' ' . date('H:i:s'),
                'observacoes' => $validated['observacoes'] ?? null,
            ]);

            \App\Jobs\RecalcularSaldosJob::dispatchSync($userId, $mov->data_movimentacao->toDateString());
        });

        return redirect()->back()->with('success', "Repasse de R$ " . number_format($validated['valor'], 2, ',', '.') . " baixado com sucesso na carteira do parceiro!");
    }

    /**
     * Relatório em PDF da quantidade de peças cadastradas por Brechós Parceiros no período.
     * Valor a ser pago = Quantidade total x R$ 1,00.
     */
    public function relatorioPecasCadastradasPdf(Request $request)
    {
        abort_if(!auth()->user() || !in_array(auth()->user()->role, ['admin_master', 'admin']), 403, 'Acesso restrito.');

        $dataInicioStr = $request->input('data_inicio', now()->startOfMonth()->toDateString());
        $dataFimStr = $request->input('data_fim', now()->toDateString());

        $dataInicio = \Carbon\Carbon::parse($dataInicioStr)->startOfDay();
        $dataFim = \Carbon\Carbon::parse($dataFimStr)->endOfDay();

        $brechoIds = $request->input('brechos', []);
        if (is_string($brechoIds)) {
            $brechoIds = array_filter(explode(',', $brechoIds));
        }

        $queryBrechos = Brecho::where('id', '>', 1)->orderBy('nome');
        if (!empty($brechoIds)) {
            $queryBrechos->whereIn('id', $brechoIds);
        }
        $brechos = $queryBrechos->get();

        $custoUnitario = 1.00;
        $relatorioItens = collect();
        $totalGeralPecas = 0;

        foreach ($brechos as $b) {
            $qtdPecas = \App\Models\Item::where(function ($q) use ($b) {
                $q->where('parceiro_id', $b->id);
                if ($b->prefixo_codigo) {
                    $q->orWhere('codigo', 'like', $b->prefixo_codigo . '-%');
                }
            })
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->count();

            $subtotalPagar = $qtdPecas * $custoUnitario;
            $totalGeralPecas += $qtdPecas;

            $relatorioItens->push([
                'brecho' => $b,
                'qtd_pecas' => $qtdPecas,
                'custo_unitario' => $custoUnitario,
                'subtotal' => $subtotalPagar,
            ]);
        }

        $totalGeralPagar = $totalGeralPecas * $custoUnitario;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.brechos.relatorio_pecas_pdf', [
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim,
            'relatorioItens' => $relatorioItens,
            'totalGeralPecas' => $totalGeralPecas,
            'custoUnitario' => $custoUnitario,
            'totalGeralPagar' => $totalGeralPagar,
            'totalBrechos' => $brechos->count(),
        ])->setPaper('a4', 'portrait');

        $fileName = 'relatorio-pecas-cadastradas-' . $dataInicio->format('Ymd') . '-a-' . $dataFim->format('Ymd') . '.pdf';
        return $pdf->stream($fileName);
    }
}
