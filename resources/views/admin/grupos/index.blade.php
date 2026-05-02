@extends('layouts.app')

@section('title', 'Gerenciar Grupos')

@section('content')
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Grupos 👥</h1>
            <p class="text-gray-500 text-sm mt-0.5">Gerencie as equipes e suas pontuações.</p>
        </div>
        <a href="{{ route('admin.grupos.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
            <i class="fas fa-plus"></i> Novo Grupo
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50/50">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Grupo</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Líder</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Membros</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-center">Pontos ({{ $mesAtual }})</th>
                    <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($grupos as $grupo)
                <tr class="hover:bg-gray-50/50 transition duration-150">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900">{{ $grupo->nome }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($grupo->lider_id)
                            @php $lider = \App\Models\User::find($grupo->lider_id); @endphp
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600 border border-white">
                                    {{ substr($lider->name ?? '?', 0, 1) }}
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ $lider->name ?? 'N/A' }}</span>
                            </div>
                        @else
                            <span class="text-xs text-gray-400 italic">Sem líder</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                            {{ $grupo->membros_count }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="text-sm font-black text-emerald-600">
                            {{ number_format($grupo->pontos_total, 0, ',', '.') }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ route('admin.grupos.show', $grupo->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-wider">Ver</a>
                        <a href="{{ route('admin.grupos.edit', $grupo->id) }}" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase tracking-wider">Editar</a>
                        <form method="POST" action="{{ route('admin.grupos.destroy', $grupo->id) }}" class="inline" onsubmit="return confirm('Excluir este grupo?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider">Excluir</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 font-medium">Nenhum grupo cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($grupos->hasPages())
    <div class="p-6 bg-gray-50/50 border-t border-gray-100">
        {{ $grupos->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection