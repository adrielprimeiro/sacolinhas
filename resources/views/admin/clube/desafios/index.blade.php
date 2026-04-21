@extends('layouts.app')

@section('title', 'Desafios do Clube')

@section('content')

<div class="mb-8 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Desafios 🏆</h1>
        <p class="text-gray-500 text-sm mt-1">Cadastre os desafios que serão exibidos para as participantes.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.clube.dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-2xl text-xs font-black text-gray-500 hover:bg-gray-50 transition shadow-sm">
            <i class="fas fa-arrow-left"></i> Voltar ao Painel
        </a>
        <a href="{{ route('admin.clube.desafios.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-2xl text-sm font-black hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
            <i class="fas fa-plus"></i> Novo Desafio
        </a>
    </div>
</div>

{{-- Cards resumo --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @php
        $ativos   = $desafios->getCollection()->where('status','ativo')->count();
        $inativos = $desafios->getCollection()->where('status','inativo')->count();
        $vigentes = $desafios->getCollection()->filter(fn($d) => $d->estaVigente())->count();
    @endphp
    <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="p-2 bg-indigo-600 rounded-xl text-white"><i class="fas fa-trophy"></i></div>
        <div>
            <div class="text-2xl font-black text-gray-900">{{ $desafios->total() }}</div>
            <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Total</div>
        </div>
    </div>
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="p-2 bg-emerald-600 rounded-xl text-white"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="text-2xl font-black text-gray-900">{{ $ativos }}</div>
            <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Ativos</div>
        </div>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="p-2 bg-amber-500 rounded-xl text-white"><i class="fas fa-fire"></i></div>
        <div>
            <div class="text-2xl font-black text-gray-900">{{ $vigentes }}</div>
            <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Em Vigor</div>
        </div>
    </div>
    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 flex items-center gap-3">
        <div class="p-2 bg-gray-400 rounded-xl text-white"><i class="fas fa-pause-circle"></i></div>
        <div>
            <div class="text-2xl font-black text-gray-900">{{ $inativos }}</div>
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider">Inativos</div>
        </div>
    </div>
</div>

{{-- Tabela --}}
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest">Desafio</th>
                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Pontos</th>
                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Vigência</th>
                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                <th class="px-6 py-4 text-xs font-black text-gray-400 uppercase tracking-widest text-center">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($desafios as $d)
            <tr class="hover:bg-gray-50/50 transition">
                <td class="px-6 py-4">
                    <div class="font-black text-gray-900">{{ $d->nome }}</div>
                    @if($d->descricao)
                    <div class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $d->descricao }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 font-black text-sm">
                        <i class="fas fa-star text-[10px]"></i> {{ $d->pontos }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-xs text-gray-500 font-bold">
                    @if($d->inicio_em || $d->fim_em)
                        <div>{{ $d->inicio_em?->format('d/m/Y') ?? '∞' }} até {{ $d->fim_em?->format('d/m/Y') ?? '∞' }}</div>
                        @if($d->estaVigente())
                            <span class="text-emerald-600 font-black">● Em vigor</span>
                        @else
                            <span class="text-gray-400">● Fora do prazo</span>
                        @endif
                    @else
                        <span class="text-gray-400 italic">Sem prazo</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    @if($d->status === 'ativo')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-black">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Ativo
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-black">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inativo
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <a href="{{ route('admin.clube.desafios.edit', $d) }}"
                           class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white transition flex items-center justify-center shadow-sm" title="Editar">
                            <i class="fas fa-pen text-xs"></i>
                        </a>
                        <form action="{{ route('admin.clube.desafios.destroy', $d) }}" method="POST"
                              onsubmit="return confirm('Remover este desafio?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-9 h-9 rounded-xl bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition flex items-center justify-center shadow-sm" title="Remover">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="py-16 text-center">
                    <i class="fas fa-trophy text-4xl text-gray-200 mb-3 block"></i>
                    <p class="text-gray-400 font-bold">Nenhum desafio cadastrado ainda.</p>
                    <a href="{{ route('admin.clube.desafios.create') }}"
                       class="inline-flex items-center gap-2 mt-4 px-5 py-2 bg-indigo-600 text-white rounded-2xl text-sm font-black hover:bg-indigo-700 transition">
                        <i class="fas fa-plus"></i> Criar Primeiro Desafio
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($desafios->hasPages())
    <div class="p-6 border-t border-gray-50">{{ $desafios->links() }}</div>
    @endif
</div>

@endsection
