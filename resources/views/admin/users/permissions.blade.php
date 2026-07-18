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
                        <tr class="{{ $user->is_admin ? 'bg-indigo-50/30' : '' }}">
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
                                <form action="{{ route('admin.equipe.updateRole', $user->id) }}" method="POST" class="flex gap-2 items-center">
                                    @csrf
                                    <select name="role" class="block w-full pl-3 pr-10 py-1 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="client" {{ $user->role === 'client' ? 'selected' : '' }}>Cliente Padrão</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Atendente (Admin)</option>
                                        <option value="admin_master" {{ $user->role === 'admin_master' ? 'selected' : '' }}>Master (Admin Master)</option>
                                    </select>
                                    <button type="submit" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium py-1 px-3 rounded border border-indigo-200 transition">
                                        Salvar
                                    </button>
                                </form>
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
