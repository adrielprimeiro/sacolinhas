@php use App\Models\User; @endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupo {{ $grupo->nome }} - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <div class="p-8 bg-white rounded-lg shadow">
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $grupo->nome }}</h1>
                        <p class="text-lg text-gray-600 mt-2">Líder: {{ $grupo->lider_id ? User::find($grupo->lider_id)?->name ?? 'N/A' : 'Sem líder' }}</p>
                    </div>
                    <div class="space-x-2">
                        <a href="{{ route('admin.grupos.edit', $grupo->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Editar</a>
                        <a href="{{ route('admin.grupos.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Voltar</a>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Membros ({{ count($membros) }})</h2>
                        <ul class="space-y-2">
                            @forelse($membros as $membro)
                                <li class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <span>{{ $membro->name }} ({{ $membro->email }})</span>
                                    <form method="POST" action="{{ route('admin.grupos.removeMembro', [$grupo->id, $membro->user_id]) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm" onclick="return confirm('Remover?')">Remover</button>
                                    </form>
                                </li>
                            @empty
                                <li class="p-3 text-gray-500 text-center">Nenhum membro.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Adicionar Membro</h2>
                        <form method="POST" action="{{ route('admin.grupos.addMembro', $grupo->id) }}" class="space-y-4">
                            @csrf
                            <select name="user_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione usuário</option>
                                @foreach(User::whereNotIn('id', $membros->pluck('user_id'))->get() as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-medium">Adicionar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>