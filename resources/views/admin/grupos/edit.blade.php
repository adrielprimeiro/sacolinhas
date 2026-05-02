@extends('layouts.app')

@section('title', 'Editar Grupo')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Editar Grupo: {{ $grupo->nome }} 👥</h1>
            <p class="text-gray-500 text-sm mt-0.5">Atualize os dados da equipe abaixo.</p>
        </div>

        <form method="POST" action="{{ route('admin.grupos.update', $grupo->id) }}" class="p-8 space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nome do Grupo</label>
                <input type="text" name="nome" required 
                       class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold transition @error('nome') border-red-500 @enderror" 
                       value="{{ old('nome', $grupo->nome) }}"
                       placeholder="Ex: Equipe Alpha">
                @error('nome')
                    <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Líder (Opcional)</label>
                <select name="lider_id" 
                        class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold transition">
                    <option value="">Sem líder definido</option>
                    @foreach($usuarios as $user)
                        <option value="{{ $user->id }}" {{ old('lider_id', $grupo->lider_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('lider_id')
                    <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" 
                        class="flex-1 bg-indigo-600 text-white px-6 py-3.5 rounded-2xl hover:bg-indigo-700 font-black uppercase text-xs tracking-widest transition shadow-lg shadow-indigo-100">
                    <i class="fas fa-check mr-2"></i> Salvar Alterações
                </button>
                <a href="{{ route('admin.grupos.index') }}" 
                   class="flex-1 bg-gray-100 text-gray-500 px-6 py-3.5 rounded-2xl hover:bg-gray-200 font-black uppercase text-xs tracking-widest transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection