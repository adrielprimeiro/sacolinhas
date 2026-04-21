<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\PontuacoesService;
use Carbon\Carbon;

class ClubeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $mesAtual = $request->get('mes', date('Y-m'));
        [$ano, $mes] = array_map('intval', explode('-', $mesAtual));

        // 1. Estatísticas Globais (Apenas Membros Ativos)
        $stats = [
            'total_membros' => DB::table('clube_assinaturas')->where('status', 'ativa')->count(),
            'pagos_mes' => DB::table('clube_mensalidades')
                ->join('clube_assinaturas', 'clube_mensalidades.user_id', '=', 'clube_assinaturas.user_id')
                ->where('clube_assinaturas.status', 'ativa')
                ->where('clube_mensalidades.competencia_ano', $ano)
                ->where('clube_mensalidades.competencia_mes', $mes)
                ->where('clube_mensalidades.status_pagamento', 'pago')
                ->count(),
            'total_pontos' => (float) DB::table('pontuacoes_clientes')
                ->join('clube_assinaturas', 'pontuacoes_clientes.user_id', '=', 'clube_assinaturas.user_id')
                ->where('clube_assinaturas.status', 'ativa')
                ->where('pontuacoes_clientes.mes_ano', $mesAtual)
                ->sum('pontuacoes_clientes.total'),
        ];

        // 2. Participantes (Apenas Ativos)
        $query = User::query()
            ->join('clube_assinaturas', 'users.id', '=', 'clube_assinaturas.user_id')
            ->where('clube_assinaturas.status', 'ativa')
            ->where('users.role', 'client') // Garantia extra
            ->leftJoin('pontuacoes_clientes', function ($join) use ($mesAtual) {
                $join->on('users.id', '=', 'pontuacoes_clientes.user_id')
                     ->where('pontuacoes_clientes.mes_ano', $mesAtual);
            })
            ->leftJoin('grupo_membros', 'users.id', '=', 'grupo_membros.user_id')
            ->leftJoin('grupos', 'grupo_membros.grupo_id', '=', 'grupos.id')
            ->leftJoin('clube_mensalidades', function ($join) use ($ano, $mes) {
                $join->on('users.id', '=', 'clube_mensalidades.user_id')
                     ->where('clube_mensalidades.competencia_ano', $ano)
                     ->where('clube_mensalidades.competencia_mes', $mes);
            })
            ->select(
                'users.id', 'users.name', 'users.apelido', 'users.nome_cliente', 
                'pontuacoes_clientes.total as pontos_total',
                'pontuacoes_clientes.pontos_mensalidade',
                'pontuacoes_clientes.pontos_itens',
                'pontuacoes_clientes.pontos_desafios',
                'pontuacoes_clientes.pontos_bonus_grupo',
                'grupos.nome as grupo_nome',
                'clube_mensalidades.status_pagamento',
                'clube_mensalidades.pago_em'
            );

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'like', "%$search%")
                  ->orWhere('users.apelido', 'like', "%$search%")
                  ->orWhere('users.nome_cliente', 'like', "%$search%");
            });
        }


        // Ordenação
        $sort  = $request->get('sort', 'nome');
        $order = $request->get('order', 'asc') === 'desc' ? 'desc' : 'asc';

        $sortMap = [
            'nome'      => 'users.name',
            'pagamento' => 'clube_mensalidades.status_pagamento',
            'grupo'     => 'grupos.nome',
            'pontos'    => 'pontuacoes_clientes.total',
        ];
        $orderColumn = $sortMap[$sort] ?? 'users.name';
        $query->orderBy($orderColumn, $order);

        $participantes = $query->paginate(20);

        // Lista de grupos para o modal de trocar grupo
        $grupos = DB::table('grupos')->get(['id', 'nome']);

        // Desafios ativos para o modal de lançar desafio
        $desafiosAtivos = \App\Models\Desafio::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'descricao', 'pontos', 'inicio_em', 'fim_em']);

        return view('admin.clube.dashboard', compact('participantes', 'stats', 'mesAtual', 'grupos', 'desafiosAtivos'));

    }

    public function lancarDesafio(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'pontos' => 'required|numeric',
            'desafio_nome' => 'required|string|max:255',
            'mes_ano' => 'required|date_format:Y-m'
        ]);

        PontuacoesService::addChallengePoints(
            $request->user_id,
            $request->pontos,
            $request->desafio_nome,
            $request->mes_ano
        );

        return back()->with('success', 'Desafio lançado com sucesso!');
    }

    public function registrarPagamento(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'mes_ano' => 'required|date_format:Y-m',
            'valor' => 'required|numeric|min:0',
            'data_pagamento' => 'required|date',
        ]);

        [$ano, $mes] = array_map('intval', explode('-', $request->mes_ano));

        DB::transaction(function () use ($request, $ano, $mes) {
            // Reutilizando lógica do ClubeMensalidadesController simplificada
            $assinaturaId = DB::table('clube_assinaturas')
                ->where('user_id', $request->user_id)
                ->value('id');

            if (!$assinaturaId) {
                $assinaturaId = DB::table('clube_assinaturas')->insertGetId([
                    'user_id' => $request->user_id,
                    'status' => 'ativa',
                    'inicio_em' => now()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('clube_mensalidades')->updateOrInsert(
                [
                    'user_id' => $request->user_id,
                    'competencia_ano' => $ano,
                    'competencia_mes' => $mes
                ],
                [
                    'assinatura_id' => $assinaturaId,
                    'status_pagamento' => 'pago',
                    'pago_em' => $request->data_pagamento,
                    'valor' => $request->valor,
                ]
            );
            
            // Recalcular pontos (a mensalidade dá 100 pontos via procedure)
            DB::unprepared("CALL atualizar_pontuacoes_user({$request->user_id}, '{$request->mes_ano}')");
            
            // Se tiver grupo
            $grupoId = DB::table('grupo_membros')
                ->where('user_id', $request->user_id)
                ->value('grupo_id');

            if ($grupoId) {
                DB::unprepared("CALL atualizar_pontuacoes_grupo($grupoId, '{$request->mes_ano}')");
            }
        });

        return back()->with('success', 'Pagamento registrado e pontos atualizados!');
    }

    public function mudarGrupo(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'grupo_id' => 'nullable|exists:grupos,id'
        ]);

        $mesAno = date('Y-m');

        DB::transaction(function () use ($request, $mesAno) {
            // Remove do grupo atual
            DB::table('grupo_membros')->where('user_id', $request->user_id)->delete();

            // Adiciona no novo (se selecionado)
            if ($request->grupo_id) {
                DB::table('grupo_membros')->insert([
                    'grupo_id' => $request->grupo_id,
                    'user_id' => $request->user_id
                ]);
            }

            // Recalcula pontos para o usuário (pode mudar bônus de grupo)
            DB::unprepared("CALL atualizar_pontuacoes_user({$request->user_id}, '$mesAno')");
            
            // Recalcula o grupo novo e o antigo (seria complexo achar o antigo agora, mas simplificaremos)
            if ($request->grupo_id) {
                DB::unprepared("CALL atualizar_pontuacoes_grupo({$request->grupo_id}, '$mesAno')");
            }
        });

        return back()->with('success', 'Grupo atualizado!');
    }
}
