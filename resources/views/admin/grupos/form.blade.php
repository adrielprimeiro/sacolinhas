@extends('layouts.admin')
@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">{{ isset($grupo) ? 'Editar' : 'Novo' }} Grupo</h1>
    <form method="POST" {{ isset($grupo) ? 'action="'.route('admin.grupos.update', $grupo).'"' : 'action="'.route('admin.grupos.store').'"' }}>
        @csrf @if(isset($grupo)) @method('PUT') @endif
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="nome" value="{{ $grupo->nome ?? '' }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Líder (User ID)</label>
                <select name="lider_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Sem líder</option>
                    @foreach(User::all() as $u) <option value="{{ $u->id }}" {{ ($grupo->lider_id ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->id }})</option> @endforeach
                </select>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">Salvar</button>
                <a href="{{ route('admin.grupos.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-md font-medium">Cancelar</a>
            </div>
        </div>
    </form>

    @if(isset($grupo))
    <div class="mt-8 bg-gray-50 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Gerenciar Membros ({{ $grupo->membros()->count() ?? 0 }})</h3>
        <form method="POST" action="{{ route('admin.grupos.addMembro', $grupo) }}" class="mb-4">
            @csrf
            <div class="flex gap-2">
                <select name="user_id" class="flex-1 px-3 py-2 border border-gray-300 rounded-md">
                    <option>Selecione usuário disponível</option>
                    @foreach(User::whereNotIn('id', $grupo->membros()->pluck('user_id'))->get() as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->id }})</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">Adicionar</button>
            </div>
        </form>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-64 overflow-y-auto">
            @foreach($grupo->membros as $membro)
                <form method="POST" action="{{ route('admin.grupos.removeMembro', [$grupo, $membro]) }}" class="flex justify-between items-center p-3 bg-white border rounded-md">
                    @csrf @method('DELETE')
                    <span class="font-medium">{{ $membro->name }}</span>
                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium" onclick="return confirm('Remover?')">Remover</button>
                </form>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection