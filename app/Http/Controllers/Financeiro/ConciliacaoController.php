<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\Lancamento;
use App\Models\TransacaoExtrato;
use App\Models\ClassificacaoFinanceira;
use App\Models\Pessoa;
use App\Services\ConciliacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConciliacaoController extends Controller
{
    protected $service;

    public function __construct(ConciliacaoService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $extrato = TransacaoExtrato::where('status', 'pendente')
            ->orderBy('data', 'desc')
            ->get();

        $lancamentos = Lancamento::with('pessoa')
            ->where(function ($q) {
                $q->where('status', 'pendente')
                    ->orWhere(function ($q2) {
                        // Também mostra os pagos que ainda não foram vinculados a nenhuma transação do extrato
                        $q2->where('status', 'pago')
                            ->whereDoesntHave('movimentacoes', function ($q3) {
                                $q3->whereHas('transacaoExtrato');
                            });
                    });
            })
            ->orderBy('data_vencimento', 'asc')
            ->get();

        $classificacoes = ClassificacaoFinanceira::all();
        $pessoas = Pessoa::all(['id', 'nome']);
        $contas = \App\Models\ContaBancaria::all(['id', 'nome']);

        return view('admin.financeiro.conciliacao', compact('extrato', 'lancamentos', 'classificacoes', 'pessoas', 'contas'));
    }

    public function sincronizarMp(Request $request)
    {
        try {
            $count = $this->service->sincronizarMercadoPago($request->start_date, $request->end_date);
            return back()->with('success', "{$count} transações sincronizadas do Mercado Pago.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function importarOfx(Request $request)
    {
        $request->validate(['arquivo_ofx' => 'required|file']);
        
        try {
            $count = $this->service->importarOfx($request->file('arquivo_ofx'), $request->conta_bancaria_id);
            return back()->with('success', "{$count} transações importadas do arquivo OFX.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function conciliar(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'lancamento_id' => 'required|exists:lancamentos,id',
        ]);

        try {
            $this->service->vincular($request->transacao_id, $request->lancamento_id);
            return back()->with('success', 'Conciliação realizada com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function vincular(Request $request)
    {
        return $this->conciliar($request);
    }

    public function criarRapido(Request $request)
    {
        $request->validate([
            'transacao_id' => 'required|exists:transacoes_extrato,id',
            'classificacao_financeira_id' => 'required|exists:classificacao_financeira,id',
        ]);

        try {
            $this->service->vincularNovoLancamento(
                $request->transacao_id,
                $request->classificacao_financeira_id,
                $request->pessoa_id,
                $request->conta_bancaria_id
            );
            return back()->with('success', 'Lançamento criado e conciliado!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function ignorar(TransacaoExtrato $transacao)
    {
        $transacao->update(['status' => 'ignorado']);
        return back()->with('success', 'Transação ignorada.');
    }

    public function getSugestaoPessoa(TransacaoExtrato $transacao)
    {
        $pedidoId = $transacao->getPedidoId();
        if (!$pedidoId) {
            return response()->json(['success' => false, 'message' => 'Sem referência de pedido']);
        }

        $pedido = \App\Models\Pedido::with('user.perfilFinanceiro')->find($pedidoId);
        if (!$pedido || !$pedido->user) {
            return response()->json(['success' => false, 'message' => 'Pedido ou usuário não encontrado']);
        }

        $user = $pedido->user;
        $pessoa = $user->perfilFinanceiro;

        if (!$pessoa) {
            // Criar perfil financeiro automaticamente se não existir
            $pessoa = \App\Models\Pessoa::create([
                'user_id' => $user->id,
                'nome' => $user->name,
                'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                'tipo' => 'cliente_circular',
            ]);
            Log::info("Perfil financeiro criado automaticamente para User #{$user->id}");
        }

        return response()->json([
            'success' => true,
            'pessoa' => [
                'id' => $pessoa->id,
                'text' => "[#{$pessoa->id}] {$pessoa->nome}" . ($pessoa->documento ? " - {$pessoa->documento}" : '')
            ]
        ]);
    }

    public function buscarPessoas(Request $request)
    {
        $search = $request->get('q', '');
        
        $pessoas = \App\Models\Pessoa::when($search, function($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                  ->orWhere('documento', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            })
            ->orderBy('nome')
            ->limit(30)
            ->get(['id', 'nome', 'documento']);
            
        return response()->json($pessoas);
    }
}
