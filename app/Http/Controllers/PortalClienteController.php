<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ContaCorrente; 
use Carbon\Carbon;


class PortalClienteController extends Controller
{
	public function dashboard()
	{
		$user = Auth::user();

		// 1) Saldo correto (última transação)
		$saldo = 0;
		try {
			$ultima = \App\Models\ContaCorrente::where('user_id', $user->id)
				->orderByDesc('data_movimentacao')
				->orderByDesc('id')
				->first();

			$saldo = $ultima?->saldo_atual ?? 0;
		} catch (\Exception $e) {
			$saldo = 0;
		}

		// 2) Sacolinha (dados reais)
		$sacolinhaRow = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->selectRaw('COUNT(*) as itens')
			->selectRaw('COALESCE(SUM(price),0) as valor')
			->selectRaw('MIN(add_at) as aberto_em')
			->first();

		$sacolinha = [
			'itens'  => (int) ($sacolinhaRow->itens ?? 0),
			'valor'  => (float) ($sacolinhaRow->valor ?? 0),
			'status' => 'aberto',
			'data'   => !empty($sacolinhaRow->aberto_em)
				? \Carbon\Carbon::parse($sacolinhaRow->aberto_em)->format('d/m/Y')
				: 'N/A',
		];

		// 3) Limite Sacolinha (cliente_limites)
		$limitesRow = DB::table('cliente_limites')
			->where('user_id', $user->id)
			->first();

		$valorLimite = (float) ($limitesRow->limite_credito ?? 0);
		$utilizado   = (float) ($limitesRow->limite_utilizado ?? 0);
		$valorPago   = (float) ($saldo ?? 0);

		$disponivel = $valorLimite + $valorPago - $utilizado;
		$disponivelUI = max(0, $disponivel);

		$base = $valorLimite + $valorPago;
		$percentual = $base > 0 ? ($utilizado / $base) * 100 : 0;

		$limite = [
			'valor_limite' => $valorLimite,
			'utilizado'    => $utilizado,
			'valor_pago'   => $valorPago,
			'disponivel'   => $disponivelUI,
			'percentual'   => min(100, max(0, $percentual)),
		];

		// 4) NOVO: Dados do Clube Mania (snapshot apenas leitura)
		$clubeIndicadores = DB::table('cliente_clube_indicadores')
			->where('user_id', $user->id)
			->first();

		// Se não existir registro, usamos valores padrão para não quebrar a view
		$clubeData = [
			'mensalidade_status'        => $clubeIndicadores->mensalidade_status ?? 'inativa',
			'mensalidades_sequencia'    => (int) ($clubeIndicadores->mensalidades_sequencia ?? 0),
			'pedidos_concluidos'        => (int) ($clubeIndicadores->pedidos_concluidos ?? 0),
			'taxa_cancel_devol_percent' => (float) ($clubeIndicadores->taxa_cancel_devol_percent ?? 0.00),
			'atualizado_em'             => $clubeIndicadores->atualizado_em ?? null,
		];

		// 5) Retorno ÚNICO para a view correta
		return view('portal.cliente.dashboard', compact(
			'user', 
			'saldo', 
			'sacolinha', 
			'limite', 
			'clubeData'
		));
	}
    

	public function perfil(Request $request)
	{
		$user = Auth::user();
        $returnTo = $request->query('return_to');
		return view('portal.cliente.perfil', compact('user', 'returnTo'));
	}

	public function perfilAtualizar(Request $request)
	{
		$user = Auth::user();

		$rules = [
			'name' => ['required', 'string', 'max:255'],
			'email' => [
				'required',
				'email',
				'max:255',
				Rule::unique('users', 'email')->ignore($user->id),
			],
            'cep' => ['nullable', 'string', 'max:9'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero_endereco' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:2'],
		];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:6', 'confirmed'];
        }

        $request->validate($rules);

		$user->name = $request->name;
		$user->email = $request->email;

        // Atualizar endereço
        $user->cep = $request->cep;
        $user->endereco = $request->endereco;
        $user->numero_endereco = $request->numero_endereco;
        $user->complemento = $request->complemento;
        $user->bairro = $request->bairro;
        $user->cidade = $request->cidade;
        $user->estado = $request->estado;

		if (!empty($request->password)) {
			$user->password = Hash::make($request->password);
		}

		$user->save();

		return redirect($request->input('return_to', route('portal.dashboard')))
			->with('success', 'Perfil atualizado com sucesso.');
	}
	
	
	public function pedidos()
	{
		$user = Auth::user();

		$pedidos = DB::table('pedidos')
			->where('user_id', $user->id)
			->orderBy('data_pedido', 'desc')
			->select([
				'id',
				'numero_pedido',
				'data_pedido',
				'status_pedido',
				'valor_total',
				'valor_frete',
				'valor_desconto',
				'forma_pagamento',
				'status_pagamento',
				'origem_pedido',
				'codigo_rastreamento',
				'data_envio',
				'data_entrega_prevista',
				'data_entrega_realizada',
			])
			->get();

		return view('portal.cliente.pedidos', compact('user', 'pedidos'));
	}	

	
	public function sacolinha()
	{
		$user = Auth::user();

		// Verificar se tem itens com status 'Em Analize'
		$temEmAnalise = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->where('status', 'em analise')
			->exists();

		// Calcular total dos itens em análise
		$totalItensEmAnalise = DB::table('sacolinhas')
			->where('user_id', $user->id)
			->where('status', 'em analise')
			->sum('price');

		// Excedente = total dos itens em análise
		$excedente = $totalItensEmAnalise;

		// Buscar dados do limite
		$limitesRow = DB::table('cliente_limites')
			->where('user_id', $user->id)
			->first();

		$valorLimite = (float) ($limitesRow->limite_credito ?? 0);
		$utilizado   = (float) ($limitesRow->limite_utilizado ?? 0);
		
		// Buscar saldo
		$saldo = 0;
		try {
			$ultima = ContaCorrente::where('user_id', $user->id)
				->orderByDesc('data_movimentacao')
				->orderByDesc('id')
				->first();
			$saldo = $ultima?->saldo_atual ?? 0;
		} catch (\Exception $e) {
			$saldo = 0;
		}
		
		$valorPago   = (float) ($saldo ?? 0);
		$disponivel  = $valorLimite + $valorPago - $utilizado;
		$disponivelUI = max(0, $disponivel);

		// Buscar itens da sacolinha
		$itens = DB::table('sacolinhas as s')
			->join('items as i', 'i.id', '=', 's.item_id')
			->where('s.user_id', $user->id)
			->orderBy('s.add_at', $temEmAnalise ? 'desc' : 'asc') // Se tiver em análise, mais novo primeiro
			->select([
				's.id as sacolinha_id',
				's.item_id',
				's.price',
				's.add_at',
				's.status as sacolinha_status',
				's.obs',

				'i.codigo',
				'i.nome_do_produto',
				'i.estado',
				'i.cor',
				'i.tamanho',
				'i.image',
				'i.marca',
			])
			->get();

		$total = (float) $itens->sum('price');

		return view('portal.cliente.sacolinha', compact(
			'user', 
			'itens', 
			'total',
			'temEmAnalise',
			'totalItensEmAnalise',
			'excedente',
			'valorLimite',
			'utilizado',
			'valorPago',
			'disponivelUI'
		));
	}

    public function sacolinhaExcluir($id)
    {
        $user = Auth::user();

        DB::table('sacolinhas')
            ->where('id', $id)
            ->where('user_id', $user->id) // Segurança: só exclui do próprio cliente
            ->delete();

        return redirect()
            ->route('portal.sacolinha')
            ->with('success', 'Item removido da sacolinha.');
    }

	public function movimentacao()
	{
		$user = Auth::user();
		$movimentacoes = ContaCorrente::where('user_id', $user->id)
									  ->orderBy('data_movimentacao', 'desc')
									  ->get();
		
		return view('portal.cliente.movimentacao', compact('user', 'movimentacoes'));
	}

    public function desafios()
    {
        $user = Auth::user();

        // Desafios ativos e dentro do prazo
        $desafios = \App\Models\Desafio::where('status', 'ativo')
            ->where(function ($q) {
                $hoje = now()->toDateString();
                $q->whereNull('inicio_em')->orWhereDate('inicio_em', '<=', $hoje);
            })
            ->where(function ($q) {
                $hoje = now()->toDateString();
                $q->whereNull('fim_em')->orWhereDate('fim_em', '>=', $hoje);
            })
            ->orderBy('nome')
            ->get();

        // Histórico de pontos de desafio lançados para este cliente
        $historico = DB::table('pontos_desafios')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Total de pontos de desafios acumulados
        $totalDesafios = $historico->sum('pontos');

        return view('portal.cliente.desafios', compact('user', 'desafios', 'historico', 'totalDesafios'));
    }
}