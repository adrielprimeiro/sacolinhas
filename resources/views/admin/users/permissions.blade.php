@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Gerenciamento de Equipe</h1>
    </div>

    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-4 border-b border-gray-200">
            <form action="{{ route('admin.equipe.index') }}" method="GET" class="flex gap-4 items-center">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nome, email ou telefone..." class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 flex-grow py-2 px-3">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md shadow-sm transition">
                    Buscar
                </button>
                @if(request()->has('search'))
                    <a href="{{ route('admin.equipe.index') }}" class="text-gray-500 hover:text-gray-700">Limpar</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email/Tel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível Atual</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr class="{{ $user->is_admin ? 'bg-indigo-50/30' : '' }}" x-data="{ editModalOpen: false }">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $user->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $user->email }}</div>
                                <div>{{ $user->telefone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->role === 'admin_master')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Master</span>
                                @elseif($user->role === 'admin')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Atendente</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Cliente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2 items-center">
                                    <form action="{{ route('admin.equipe.updateRole', $user->id) }}" method="POST" class="flex gap-2 items-center">
                                        @csrf
                                        <select name="role" class="block w-full pl-3 pr-10 py-1 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                            <option value="client" {{ $user->role === 'client' ? 'selected' : '' }}>Cliente Padrão</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Atendente (Admin)</option>
                                            <option value="admin_master" {{ $user->role === 'admin_master' ? 'selected' : '' }}>Master (Admin Master)</option>
                                        </select>
                                        <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium py-1 px-3 rounded border border-indigo-200 transition">
                                            Salvar Nível
                                        </button>
                                    </form>

                                    <!-- Botão Editar -->
                                    <button @click="editModalOpen = true" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-1 px-3 rounded border border-gray-300 transition">
                                        Editar
                                    </button>
                                </div>

                                <!-- Modal de Edição -->
                                <div x-show="editModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                        
                                        <div x-show="editModalOpen" @click="editModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                                        <div x-show="editModalOpen" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                            <form action="{{ route('admin.equipe.update', $user->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                                        Editar Cadastro
                                                    </h3>
                                                    <div class="mt-4">
                                                        <div class="mb-4">
                                                            <label for="name_{{ $user->id }}" class="block text-sm font-medium text-gray-700">Nome</label>
                                                            <input type="text" name="name" id="name_{{ $user->id }}" value="{{ $user->name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="email_{{ $user->id }}" class="block text-sm font-medium text-gray-700">E-mail</label>
                                                            <input type="email" name="email" id="email_{{ $user->id }}" value="{{ $user->email }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="telefone_{{ $user->id }}" class="block text-sm font-medium text-gray-700">Telefone / WhatsApp</label>
                                                            <input type="text" name="telefone" id="telefone_{{ $user->id }}" value="{{ $user->telefone }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                        </div>
                                                        <div class="mb-4">
                                                            <label for="password_{{ $user->id }}" class="block text-sm font-medium text-gray-700">Nova Senha (deixe em branco para manter)</label>
                                                            <input type="password" name="password" id="password_{{ $user->id }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                        Salvar
                                                    </button>
                                                    <button type="button" @click="editModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                                        Cancelar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Nenhum usuário encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
