@extends('layouts.app')

@section('title', $desafio->exists ? 'Editar Desafio' : 'Novo Desafio')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.clube.desafios.index') }}"
           class="w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 flex items-center justify-center shadow-sm transition">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                {{ $desafio->exists ? '✏️ Editar Desafio' : '🏆 Novo Desafio' }}
            </h1>
            <p class="text-gray-400 text-sm">{{ $desafio->exists ? 'Atualize as informações do desafio.' : 'Preencha os dados do novo desafio para as participantes.' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 bg-gradient-to-r from-indigo-50 to-blue-100 border-b border-indigo-200">
            <h2 class="text-lg font-black text-indigo-900 flex items-center gap-2">
                <i class="fas fa-trophy text-indigo-500"></i>
                Informações do Desafio
            </h2>
        </div>

        <form action="{{ $desafio->exists ? route('admin.clube.desafios.update', $desafio) : route('admin.clube.desafios.store') }}"
              method="POST" class="p-8 space-y-6">
            @csrf
            @if($desafio->exists) @method('PUT') @endif

            {{-- Nome --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                    Nome do Desafio <span class="text-red-400">*</span>
                </label>
                <input type="text" name="nome" value="{{ old('nome', $desafio->nome) }}" required
                       placeholder="Ex: Desafio de Compra, Live Premiada, Indicação Fechada..."
                       class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-bold px-4 py-3">
                @error('nome')<p class="text-xs text-red-500 mt-1 font-bold">{{ $message }}</p>@enderror
            </div>

            {{-- Descrição --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                    Descrição <span class="text-gray-300">(opcional)</span>
                </label>
                <textarea name="descricao" rows="4"
                          placeholder="Explique como a participante pode completar este desafio e ganhar os pontos..."
                          class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 px-4 py-3 resize-none">{{ old('descricao', $desafio->descricao) }}</textarea>
                @error('descricao')<p class="text-xs text-red-500 mt-1 font-bold">{{ $message }}</p>@enderror
            </div>

            {{-- Pontos e Status --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                        Pontos <span class="text-red-400">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="pontos" value="{{ old('pontos', $desafio->pontos ?? 0) }}" required min="0"
                               class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-black pl-12 py-3">
                        <i class="fas fa-star absolute left-4 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                    </div>
                    @error('pontos')<p class="text-xs text-red-500 mt-1 font-bold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Status</label>
                    <select name="status"
                            class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-bold py-3">
                        <option value="ativo"   {{ old('status', $desafio->status ?? 'ativo') === 'ativo'   ? 'selected' : '' }}>✅ Ativo</option>
                        <option value="inativo" {{ old('status', $desafio->status ?? 'ativo') === 'inativo' ? 'selected' : '' }}>⏸️ Inativo</option>
                    </select>
                </div>
            </div>

            {{-- Vigência --}}
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">
                    Período de Vigência <span class="text-gray-300">(opcional — sem prazo = aparece sempre)</span>
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase">Início</label>
                        <input type="date" name="inicio_em" value="{{ old('inicio_em', $desafio->inicio_em?->format('Y-m-d')) }}"
                               class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-bold px-4 py-3">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 mb-1 uppercase">Fim</label>
                        <input type="date" name="fim_em" value="{{ old('fim_em', $desafio->fim_em?->format('Y-m-d')) }}"
                               class="w-full rounded-2xl border-gray-300 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 font-bold px-4 py-3">
                    </div>
                </div>
                @error('fim_em')<p class="text-xs text-red-500 mt-1 font-bold">{{ $message }}</p>@enderror
            </div>

            {{-- Botões --}}
            <div class="flex gap-3 pt-2 border-t border-gray-100">
                <a href="{{ route('admin.clube.desafios.index') }}"
                   class="flex-1 text-center px-4 py-3 bg-gray-100 text-gray-700 font-black rounded-2xl hover:bg-gray-200 transition uppercase text-xs tracking-widest">
                    Cancelar
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-3 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 transition uppercase text-xs tracking-widest shadow-lg shadow-indigo-200">
                    <i class="fas fa-save mr-1"></i>
                    {{ $desafio->exists ? 'Salvar Alterações' : 'Criar Desafio' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
