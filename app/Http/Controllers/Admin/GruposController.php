<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class GruposController extends Controller
{
    public function index()
    {
        $mesAtual = date('Y-m');
        $grupos = DB::table('grupos')
            ->leftJoin('grupo_membros', 'grupos.id', '=', 'grupo_membros.grupo_id')
            ->leftJoin('pontuacoes_grupos', function($join) use ($mesAtual) {
                $join->on('grupos.id', '=', 'pontuacoes_grupos.grupo_id')
                     ->where('pontuacoes_grupos.mes_ano', $mesAtual);
            })
            ->select(
                'grupos.id',
                'grupos.nome',
                'grupos.lider_id',
                DB::raw('COUNT(grupo_membros.user_id) as membros_count'),
                DB::raw('COALESCE(SUM(pontuacoes_grupos.total), 0) as pontos_total')
            )
            ->groupBy('grupos.id', 'grupos.nome', 'grupos.lider_id')
            ->orderBy('grupos.id', 'desc')
            ->paginate(10);

        return view('admin.grupos.index', compact('grupos', 'mesAtual'));
    }

    public function create()
    {
        $usuarios = User::where('role', '!=', 'cliente')->get(); // Líderes possíveis
        return view('admin.grupos.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'lider_id' => 'nullable|exists:users,id'
        ]);

        DB::table('grupos')->insert([
            'nome' => $request->nome,
            'lider_id' => $request->lider_id
        ]);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo criado!');
    }

    public function show($id)
    {
        $grupo = DB::table('grupos')->find($id);
        $membros = DB::table('grupo_membros')
            ->join('users', 'grupo_membros.user_id', '=', 'users.id')
            ->where('grupo_membros.grupo_id', $id)
            ->get();
        return view('admin.grupos.show', compact('grupo', 'membros'));
    }

    public function edit($id)
    {
        $grupo = DB::table('grupos')->find($id);
        $usuarios = User::whereNotIn('id', function ($query) use ($id) {
            $query->select('user_id')->from('grupo_membros')->where('grupo_id', $id);
        })->get();
        return view('admin.grupos.edit', compact('grupo', 'usuarios'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'lider_id' => 'nullable|exists:users,id'
        ]);

        DB::table('grupos')->where('id', $id)->update([
            'nome' => $request->nome,
            'lider_id' => $request->lider_id
        ]);

        return redirect()->route('admin.grupos.index')->with('success', 'Grupo atualizado!');
    }

    public function destroy($id)
    {
        DB::table('grupos')->delete($id);
        return redirect()->route('admin.grupos.index')->with('success', 'Grupo excluído!');
    }

    public function addMembro(Request $request, $grupo)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        DB::table('grupo_membros')->insert([
            'grupo_id' => $grupo,
            'user_id' => $request->user_id
        ]);
        return back()->with('success', 'Membro adicionado!');
    }

    public function removeMembro(Request $request, $grupo, $user)
    {
        DB::table('grupo_membros')->where('grupo_id', $grupo)->where('user_id', $user)->delete();
        return back()->with('success', 'Membro removido!');
    }
}