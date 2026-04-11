@php use App\Models\User; @endphp

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Grupo - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto">
            <div class="p-8 bg-white rounded-lg shadow">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Editar Grupo: {{ $grupo->nome }}</h1>
                
                <form method="POST" action="{{ route('admin.grupos.update', $grupo->id) }}">
                    @csrf @method('PUT')
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Grupo</label>
                        <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('nome') border-red-500 @enderror" value="{{ old('nome', $grupo->nome) }}">
                        @error('nome')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Líder (opcional)</label>
                        <select name="lider_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Sem líder</option>
                            @foreach(User::where('role', '!=', 'cliente')->get() as $user)
                                <option value="{{ $user->id }}" {{ old('lider_id', $grupo->lider_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('lider_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 font-medium">Atualizar</button>
                        <a href="{{ route('admin.grupos.index') }}" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 font-medium">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>