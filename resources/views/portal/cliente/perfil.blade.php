@extends('layouts.portal-cliente')

@section('title', 'Meu Perfil - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Meu Perfil</h1>
            <p class="text-gray-600 text-sm">Atualize seus dados e sua senha</p>
        </div>
    </div>

    <!-- Mensagens -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
            <p class="font-semibold mb-1">Verifique os campos:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Dados do Perfil</h2>
        </div>

        <form action="{{ route('portal.perfil.atualizar') }}" method="POST" class="p-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="pt-2 border-t border-gray-200">
                <p class="text-sm font-semibold text-gray-800 mb-2">Alterar senha (opcional)</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                        <input type="password" name="password"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-2">Deixe em branco para manter a senha atual.</p>
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-md transition duration-200">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

</div>
@endsection