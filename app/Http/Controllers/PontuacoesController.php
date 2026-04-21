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

        // 1. Meus pontos
        $meusPontos = DB::table('pontuacoes_clientes')
            ->where('user_id', $user->id)
            ->where('mes_ano', $mesAtual)
            ->select('total', 'pontos_mensalidade', 'pontos_itens', 'pontos_desafios', 'pontos_bonus_grupo')
            ->first() ?? (object)[
                'total' => 0, 'pontos_mensalidade' => 0, 'pontos_itens' => 0,
                'pontos_desafios' => 0, 'pontos_bonus_grupo' => 0
            ];

        // 2. Meu grupo (Informações e Pontuações)
        $meuGrupo = DB::table('grupo_membros')
            ->join('grupos', 'grupo_membros.grupo_id', '=', 'grupos.id')
            ->where('grupo_membros.user_id', $user->id)
            ->select('grupos.id as grupo_id', 'grupos.nome')
            ->first();

        $pontosGrupo = null;
        $membrosPagosGrupo = 0;
        $totalMembrosGrupo = 0;

        if ($meuGrupo) {
            // Pontuação total do grupo
            $pontosGrupo = DB::table('pontuacoes_grupos')
                ->where('grupo_id', $meuGrupo->grupo_id)
                ->where('mes_ano', $mesAtual)
                ->select('total', 'pontos_mensalidades', 'pontos_itens')
                ->first() ?? (object)['total' => 0, 'pontos_mensalidades' => 0, 'pontos_itens' => 0];

            // Contagem de membros (Total e Em Dia)
            $membrosIds = DB::table('grupo_membros')
                ->where('grupo_id', $meuGrupo->grupo_id)
                ->pluck('user_id');
            
            $totalMembrosGrupo = $membrosIds->count();

            [$ano, $mes] = explode('-', $mesAtual);
            $membrosPagosGrupo = DB::table('clube_mensalidades')
                ->whereIn('user_id', $membrosIds)
                ->where('competencia_ano', (int)$ano)
                ->where('competencia_mes', (int)$mes)
                ->where('status_pagamento', 'pago')
                ->count();
        }

        // 3. Ranking Individual Top 10
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

        // Posição real do usuário (pode estar fora do top 10)
        $userNoRanking = DB::table('pontuacoes_clientes')
            ->where('mes_ano', $mesAtual)
            ->where('total', '>', $meusPontos->total)
            ->count() + 1;

        // 4. Ranking de Grupos Top 10
        $rankingGrupos = DB::table('pontuacoes_grupos')
            ->join('grupos', 'pontuacoes_grupos.grupo_id', '=', 'grupos.id')
            ->where('pontuacoes_grupos.mes_ano', $mesAtual)
            ->select('grupos.id as grupo_id', 'grupos.nome', 'pontuacoes_grupos.total')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard-pontuacoes', compact(
            'meusPontos', 
            'pontosGrupo', 
            'meuGrupo', 
            'ranking', 
            'rankingGrupos',
            'mesAtual', 
            'userNoRanking', 
            'user',
            'totalMembrosGrupo',
            'membrosPagosGrupo'
        ));
    }

}