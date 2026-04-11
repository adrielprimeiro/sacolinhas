<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PontuacoesController extends Controller
{
    public function dashboard()
    {
        $mesAtual = date('Y-m');
        $user = Auth::user();

        // Meus pontos
        $meusPontos = DB::table('pontuacoes_clientes')
            ->where('user_id', $user->id)
            ->where('mes_ano', $mesAtual)
            ->select('total', 'pontos_mensalidade', 'pontos_itens', 'pontos_desafios', 'pontos_bonus_grupo')
            ->first() ?? (object)[
                'total' => 0, 'pontos_mensalidade' => 0, 'pontos_itens' => 0,
                'pontos_desafios' => 0, 'pontos_bonus_grupo' => 0
            ];

        // Meu grupo
        $meuGrupo = DB::table('grupo_membros')
            ->join('grupos', 'grupo_membros.grupo_id', '=', 'grupos.id')
            ->where('grupo_membros.user_id', $user->id)
            ->select('grupos.id as grupo_id', 'grupos.nome')
            ->first();

        $pontosGrupo = null;
        if ($meuGrupo) {
            $pontosGrupo = DB::table('pontuacoes_grupos')
                ->where('grupo_id', $meuGrupo->grupo_id)
                ->where('mes_ano', $mesAtual)
                ->select('total', 'pontos_mensalidades', 'pontos_itens')
                ->first() ?? (object)['total' => 0, 'pontos_mensalidades' => 0, 'pontos_itens' => 0];
        }

        // Ranking com nomes e posição da logada
        $ranking = DB::table('pontuacoes_clientes')
            ->join('users', 'pontuacoes_clientes.user_id', '=', 'users.id')
            ->where('pontuacoes_clientes.mes_ano', $mesAtual)
            ->select(
                'pontuacoes_clientes.user_id', 'users.name',
                'pontuacoes_clientes.total', 'pontuacoes_clientes.pontos_mensalidade', 'pontuacoes_clientes.pontos_itens'
            )
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $userNoRanking = $ranking->search(fn($r) => $r->user_id == $user->id);
        $userNoRanking = $userNoRanking !== false ? ($userNoRanking + 1) : null;

        return view('dashboard-pontuacoes', compact(
            'meusPontos', 'pontosGrupo', 'meuGrupo', 'ranking', 'mesAtual', 'userNoRanking', 'user'
        ));
    }
}