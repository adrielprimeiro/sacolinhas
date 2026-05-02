@extends('layouts.app')

@section('title', 'Detalhes do Grupo')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-8 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">{{ $grupo->nome }} 👥</h1>
                <p class="text-gray-500 font-bold mt-1">
                    Líder: 
                    @if($grupo->lider_id)
                        <span class="text-indigo-600">{{ \App\Models\User::find($grupo->lider_id)?->name ?? 'N/A' }}</span>
                    @else
                        <span class="text-gray-400 italic">Sem líder</span>
                    @endif
                </p>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <a href="{{ route('admin.grupos.edit', $grupo->id) }}" 
                   class="flex-1 md:flex-none text-center px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 transition shadow-sm">
                   <i class="fas fa-edit mr-1"></i> Editar
                </a>
                <a href="{{ route('admin.grupos.index') }}" 
                   class="flex-1 md:flex-none text-center px-4 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-200 transition">
                   Voltar
                </a>
            </div>
        </div>

        <div class="p-8 grid md:grid-cols-2 gap-8">
            {{-- Lista de Membros --}}
            <div>
                <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-users text-indigo-500"></i> Membros ({{ count($membros) }})
                </h2>
                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-2 scrollbar-hide">
                    @forelse($membros as $membro)
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-2xl border border-gray-100 hover:border-indigo-200 transition">
                            <div>
                                <div class="font-bold text-gray-900">{{ $membro->name }}</div>
                                <div class="text-xs text-gray-500">{{ $membro->email }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.grupos.removeMembro', [$grupo->id, $membro->user_id]) }}" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" 
                                        class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition flex items-center justify-center text-xs" 
                                        onclick="return confirm('Remover este membro?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="p-12 text-center bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-gray-400 font-medium">
                            Nenhum membro vinculado.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Adicionar Membro --}}
            <div>
                <h2 class="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                    <i class="fas fa-user-plus text-emerald-500"></i> Adicionar Membro
                </h2>
                <form method="POST" action="{{ route('admin.grupos.addMembro', $grupo->id) }}" class="bg-gray-50 p-6 rounded-3xl border border-gray-100 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Selecione o Usuário</label>
                        <select name="user_id" required 
                                class="w-full px-4 py-3 bg-white border-2 border-gray-100 rounded-2xl focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold transition">
                            <option value="">Buscar participante...</option>
                            @foreach(\App\Models\User::whereNotIn('id', $membros->pluck('user_id'))->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" 
                            class="w-full bg-emerald-600 text-white py-4 rounded-2xl hover:bg-emerald-700 font-black uppercase text-xs tracking-widest transition shadow-lg shadow-emerald-100">
                        <i class="fas fa-plus mr-2"></i> Adicionar ao Grupo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection