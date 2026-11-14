@extends('layouts.app')

@section('title', 'Gestão de Equipe e Permissões')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-7xl" x-data="equipePermissionsManager()">
    <!-- Cabeçalho -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-user-shield text-indigo-600"></i>
                Gestão de Equipe e Permissões de Acesso
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Crie logins para a sua equipe e defina quais módulos cada pessoa pode <strong>Apenas Visualizar (Leitura)</strong>, <strong>Editar</strong> ou <strong>Não Acessar</strong>.
            </p>
        </div>

        <button type="button"
                @click="openCreateModal()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-lg shadow transition">
            <i class="fas fa-user-plus"></i>
            <span>Novo Login de Equipe</span>
        </button>
    </div>

    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-600 text-red-800 p-4 mb-6 rounded shadow-sm">
            <div class="font-bold mb-1"><i class="fas fa-exclamation-circle mr-1"></i> Verifique os campos abaixo:</div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Abas de Filtro e Busca -->
    <div class="bg-white rounded-lg shadow mb-6 border border-gray-200">
        <div class="p-4 border-b border-gray-200 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4">
            <!-- Abas -->
            <div class="flex space-x-2">
                <a href="{{ route('admin.equipe.index', ['filter' => 'equipe', 'search' => request('search')]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ ($filter ?? 'equipe') === 'equipe' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-users-cog mr-1.5"></i> Logins da Equipe / Sistema
                </a>
                <a href="{{ route('admin.equipe.index', ['filter' => 'all', 'search' => request('search')]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ ($filter ?? 'equipe') === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    <i class="fas fa-globe mr-1.5"></i> Todos os Cadastros (Incluindo Clientes)
                </a>
            </div>

            <!-- Busca -->
            <form action="{{ route('admin.equipe.index') }}" method="GET" class="flex gap-2 items-center flex-grow max-w-xl">
                <input type="hidden" name="filter" value="{{ $filter ?? 'equipe' }}">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nome, e-mail ou WhatsApp..."
                       class="border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 flex-grow py-2 px-3 text-sm text-gray-900 bg-white">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition text-sm">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.equipe.index', ['filter' => $filter ?? 'equipe']) }}"
                       class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm font-medium">
                        Limpar
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabela de Usuários -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Usuário</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Login / Contato</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Perfil</th>
                        <th class="px-5 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Permissões por Módulo</th>
                        <th class="px-5 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                        @php
                            $userPerms = [];
                            foreach ($modules as $modKey => $modInfo) {
                                $userPerms[$modKey] = $user->getModulePermission($modKey);
                            }
                            $writeCount = count(array_filter($userPerms, fn($v) => $v === 'write'));
                            $readCount  = count(array_filter($userPerms, fn($v) => $v === 'read'));
                        @endphp
                        <tr class="{{ $user->is_admin ? 'bg-indigo-50/20' : '' }} hover:bg-gray-50 transition">
                            <!-- Usuário -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-500">#{{ $user->id }}</span>
                                    <span class="font-semibold text-gray-900">{{ $user->name }}</span>
                                    @if($user->bloqueado)
                                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-800 border border-red-200">Bloqueado</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Login / Contato -->
                            <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-medium text-gray-900"><i class="far fa-envelope text-gray-400 mr-1"></i>{{ $user->email }}</div>
                                @if($user->whatsapp)
                                    <div class="text-xs text-gray-500 mt-0.5"><i class="fab fa-whatsapp text-green-600 mr-1"></i>{{ $user->whatsapp }}</div>
                                @endif
                            </td>

                            <!-- Perfil -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($user->role === 'admin_master')
                                    <span class="px-2.5 py-1 inline-flex items-center text-xs font-bold rounded-full bg-purple-100 text-purple-800 border border-purple-200">
                                        <i class="fas fa-crown mr-1"></i> Master
                                    </span>
                                @elseif($user->role === 'brecho_admin')
                                    <span class="px-2.5 py-1 inline-flex items-center text-xs font-bold rounded-full bg-green-100 text-green-800 border border-green-200">
                                        <i class="fas fa-store mr-1"></i> Parceiro ({{ $user->brecho->nome ?? 'Brechó' }})
                                    </span>
                                @elseif($user->role === 'admin')
                                    <span class="px-2.5 py-1 inline-flex items-center text-xs font-bold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                        <i class="fas fa-user-check mr-1"></i> Equipe / Operador
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 inline-flex items-center text-xs font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                        Cliente
                                    </span>
                                @endif
                            </td>

                            <!-- Resumo de Permissões -->
                            <td class="px-5 py-4">
                                @if($user->role === 'admin_master')
                                    <span class="text-xs font-semibold text-purple-800 bg-purple-50 px-2.5 py-1 rounded border border-purple-200">
                                        <i class="fas fa-check-double mr-1"></i> Acesso Irrestrito (Todos os Módulos)
                                    </span>
                                @elseif($user->role === 'client' && !$user->is_admin)
                                    <span class="text-xs text-gray-500">Apenas Portal do Cliente</span>
                                @elseif(!$user->hasCustomPermissions())
                                    <span class="text-xs font-semibold text-green-800 bg-green-100 px-2.5 py-1 rounded border border-green-200">
                                        <i class="fas fa-unlock mr-1"></i> Acesso Total Padrão ({{ count($modules) }} módulos)
                                    </span>
                                @else
                                    <div class="flex flex-wrap gap-1.5 items-center max-w-md">
                                        @if($writeCount > 0)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-green-100 text-green-800 border border-green-300" title="Módulos com permissão de Leitura e Edição">
                                                <i class="fas fa-pen mr-1"></i>{{ $writeCount }} Edição
                                            </span>
                                        @endif
                                        @if($readCount > 0)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-yellow-100 text-yellow-800 border border-yellow-300" title="Módulos com permissão Somente Leitura">
                                                <i class="fas fa-eye mr-1"></i>{{ $readCount }} Leitura
                                            </span>
                                        @endif
                                        @if($writeCount === 0 && $readCount === 0)
                                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-red-100 text-red-800 border border-red-300">
                                                <i class="fas fa-ban mr-1"></i>Sem Módulos Liberados
                                            </span>
                                        @endif
                                        <div class="w-full flex flex-wrap gap-1 mt-1">
                                            @foreach($modules as $mKey => $mInfo)
                                                @php $p = $userPerms[$mKey] ?? 'none'; @endphp
                                                @if($p === 'write')
                                                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-green-50 text-green-800 border border-green-200 font-medium">
                                                        {{ $mInfo['label'] }}: <strong>Edição</strong>
                                                    </span>
                                                @elseif($p === 'read')
                                                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-yellow-50 text-yellow-800 border border-yellow-200 font-medium">
                                                        {{ $mInfo['label'] }}: <strong>Leitura</strong>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <!-- Ações -->
                            <td class="px-5 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex flex-wrap justify-end gap-2 items-center">
                                    @if($user->role !== 'admin_master')
                                        <button type="button"
                                                @click="openPermModal({{ json_encode([
                                                    'id' => $user->id,
                                                    'name' => $user->name,
                                                    'email' => $user->email,
                                                    'role' => $user->role,
                                                    'has_custom' => $user->hasCustomPermissions(),
                                                    'permissions' => $userPerms,
                                                ]) }})"
                                                class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1.5 px-3 rounded-md shadow-sm transition text-xs">
                                            <i class="fas fa-sliders-h"></i>
                                            <span>Permissões</span>
                                        </button>
                                    @endif

                                    <button type="button"
                                            @click="openEditModal({{ json_encode([
                                                'id' => $user->id,
                                                'name' => $user->name,
                                                'email' => $user->email,
                                                'whatsapp' => $user->whatsapp,
                                                'role' => $user->role,
                                                'brecho_id' => $user->brecho_id,
                                                'bloqueado' => (bool) $user->bloqueado,
                                            ]) }})"
                                            class="inline-flex items-center gap-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-1.5 px-3 rounded-md border border-gray-300 transition text-xs">
                                        <i class="fas fa-user-edit"></i>
                                        <span>Dados / Senha</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Nenhum usuário encontrado para este filtro.
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

    <!-- ======================================================================= -->
    <!-- MODAL 1: CRIAR NOVO LOGIN DE EQUIPE JÁ COM PERMISSÕES                   -->
    <!-- ======================================================================= -->
    <div x-show="createModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="createModalOpen" @click="createModalOpen = false" class="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="createModalOpen" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200">
                <form action="{{ route('admin.equipe.store') }}" method="POST">
                    @csrf
                    <div class="bg-gray-800 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fas fa-user-plus text-indigo-400"></i>
                            Criar Novo Login de Equipe
                        </h3>
                        <button type="button" @click="createModalOpen = false" class="text-gray-300 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 max-h-[80vh] overflow-y-auto">
                        <!-- Dados de Login -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome do Colaborador *</label>
                                <input type="text" name="name" required placeholder="Ex: Maria Financeiro"
                                       class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail de Login *</label>
                                <input type="email" name="email" required placeholder="Ex: maria@minhamania.com.br"
                                       class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Senha de Acesso * (Mín. 6 caracteres)</label>
                                <input type="password" name="password" required minlength="6" placeholder="Digite a senha inicial"
                                       class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">WhatsApp / Telefone (Opcional)</label>
                                <input type="text" name="whatsapp" placeholder="Ex: 11999999999"
                                       class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de Conta</label>
                                <select name="role" x-model="newRole" class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                                    <option value="admin">Equipe / Operador (Com Permissões por Módulo)</option>
                                    <option value="admin_master">Administrador Master (Acesso Total Irrestrito)</option>
                                    <option value="brecho_admin">Operador de Brechó Parceiro</option>
                                </select>
                            </div>
                            <div x-show="newRole === 'brecho_admin'">
                                <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Brechó Vinculado</label>
                                <select name="brecho_id" class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                                    <option value="1">Minha Mania (Matriz)</option>
                                    @foreach($brechos as $b)
                                        <option value="{{ $b->id }}">{{ $b->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Matriz de Permissões -->
                        <div x-show="newRole === 'admin'">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-2">
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider">
                                    <i class="fas fa-key text-indigo-600 mr-1"></i> Permissões por Parte do Sistema
                                </h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click="applyPreset(newPerms, 'all_write')" class="text-xs px-2.5 py-1 rounded bg-green-100 hover:bg-green-200 text-green-800 font-semibold border border-green-300">
                                        Tudo Edição
                                    </button>
                                    <button type="button" @click="applyPreset(newPerms, 'all_read')" class="text-xs px-2.5 py-1 rounded bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold border border-yellow-300">
                                        Tudo Leitura
                                    </button>
                                    <button type="button" @click="applyPreset(newPerms, 'comercial')" class="text-xs px-2.5 py-1 rounded bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold border border-blue-300">
                                        Perfil Comercial / Live
                                    </button>
                                    <button type="button" @click="applyPreset(newPerms, 'estoque')" class="text-xs px-2.5 py-1 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-semibold border border-indigo-300">
                                        Perfil Estoque / Cadastro
                                    </button>
                                    <button type="button" @click="applyPreset(newPerms, 'financeiro')" class="text-xs px-2.5 py-1 rounded bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold border border-purple-300">
                                        Perfil Financeiro
                                    </button>
                                    <button type="button" @click="applyPreset(newPerms, 'all_none')" class="text-xs px-2.5 py-1 rounded bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold border border-gray-300">
                                        Zerar Tudo
                                    </button>
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                                @foreach($modules as $modKey => $modInfo)
                                    <div class="p-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:bg-gray-50">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-indigo-100">
                                                <i class="{{ $modInfo['icon'] }}"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm text-gray-900">{{ $modInfo['label'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $modInfo['description'] }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="permissions[{{ $modKey }}]" value="none" x-model="newPerms['{{ $modKey }}']" class="sr-only">
                                                <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                      :class="newPerms['{{ $modKey }}'] === 'none' ? 'bg-red-600 text-white border-red-700 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                    <i class="fas fa-ban mr-1"></i> Sem Acesso
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="permissions[{{ $modKey }}]" value="read" x-model="newPerms['{{ $modKey }}']" class="sr-only">
                                                <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                      :class="newPerms['{{ $modKey }}'] === 'read' ? 'bg-yellow-500 text-gray-900 border-yellow-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                    <i class="fas fa-eye mr-1"></i> Somente Leitura
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="permissions[{{ $modKey }}]" value="write" x-model="newPerms['{{ $modKey }}']" class="sr-only">
                                                <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                      :class="newPerms['{{ $modKey }}'] === 'write' ? 'bg-green-600 text-white border-green-700 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                    <i class="fas fa-pen mr-1"></i> Leitura e Edição
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow">
                            <i class="fas fa-check mr-1"></i> Criar Login e Salvar Permissões
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 2: CONFIGURAR PERMISSÕES POR MÓDULO (USUÁRIO EXISTENTE)           -->
    <!-- ======================================================================= -->
    <div x-show="permModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="permModalOpen" @click="permModalOpen = false" class="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="permModalOpen" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200">
                <form :action="'/admin/equipe/' + permUser.id + '/permissions'" method="POST">
                    @csrf
                    <div class="bg-gray-800 px-6 py-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                <i class="fas fa-sliders-h text-indigo-400"></i>
                                Permissões de Acesso: <span x-text="permUser.name" class="text-indigo-300"></span>
                            </h3>
                            <p class="text-xs text-gray-300 mt-0.5" x-text="permUser.email"></p>
                        </div>
                        <button type="button" @click="permModalOpen = false" class="text-gray-300 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 max-h-[75vh] overflow-y-auto">
                        <!-- Atalhos Rápidos -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2 bg-gray-50 p-3 rounded-lg border border-gray-200">
                            <span class="text-xs font-bold text-gray-700 uppercase">Preenchimento Rápido:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="applyPreset(permUser.permissions, 'all_write')" class="text-xs px-2.5 py-1 rounded bg-green-100 hover:bg-green-200 text-green-800 font-semibold border border-green-300">
                                    Liberar Tudo (Edição)
                                </button>
                                <button type="button" @click="applyPreset(permUser.permissions, 'all_read')" class="text-xs px-2.5 py-1 rounded bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold border border-yellow-300">
                                    Tudo Somente Leitura
                                </button>
                                <button type="button" @click="applyPreset(permUser.permissions, 'comercial')" class="text-xs px-2.5 py-1 rounded bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold border border-blue-300">
                                    Comercial / Atendimento
                                </button>
                                <button type="button" @click="applyPreset(permUser.permissions, 'estoque')" class="text-xs px-2.5 py-1 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-800 font-semibold border border-indigo-300">
                                    Produtos / Estoque
                                </button>
                                <button type="button" @click="applyPreset(permUser.permissions, 'financeiro')" class="text-xs px-2.5 py-1 rounded bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold border border-purple-300">
                                    Financeiro / Contábil
                                </button>
                                <button type="button" @click="applyPreset(permUser.permissions, 'all_none')" class="text-xs px-2.5 py-1 rounded bg-red-100 hover:bg-red-200 text-red-800 font-semibold border border-red-300">
                                    Bloquear Tudo
                                </button>
                            </div>
                        </div>

                        <!-- Lista de Módulos -->
                        <div class="border border-gray-200 rounded-lg divide-y divide-gray-200">
                            @foreach($modules as $modKey => $modInfo)
                                <div class="p-3.5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:bg-gray-50">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-indigo-100">
                                            <i class="{{ $modInfo['icon'] }}"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm text-gray-900">{{ $modInfo['label'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $modInfo['description'] }}</div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 self-end sm:self-center flex-shrink-0">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="permissions[{{ $modKey }}]" value="none" x-model="permUser.permissions['{{ $modKey }}']" class="sr-only">
                                            <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                  :class="permUser.permissions['{{ $modKey }}'] === 'none' ? 'bg-red-600 text-white border-red-700 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                <i class="fas fa-ban mr-1"></i> Sem Acesso
                                            </span>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="permissions[{{ $modKey }}]" value="read" x-model="permUser.permissions['{{ $modKey }}']" class="sr-only">
                                            <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                  :class="permUser.permissions['{{ $modKey }}'] === 'read' ? 'bg-yellow-500 text-gray-900 border-yellow-600 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                <i class="fas fa-eye mr-1"></i> Somente Leitura
                                            </span>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="permissions[{{ $modKey }}]" value="write" x-model="permUser.permissions['{{ $modKey }}']" class="sr-only">
                                            <span class="px-3 py-1.5 rounded-md text-xs font-bold border inline-block transition"
                                                  :class="permUser.permissions['{{ $modKey }}'] === 'write' ? 'bg-green-600 text-white border-green-700 shadow-sm' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'">
                                                <i class="fas fa-pen mr-1"></i> Leitura e Edição
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-gray-100 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-gray-200">
                        <div class="text-xs text-gray-600">
                            <i class="fas fa-info-circle text-indigo-600 mr-1"></i>
                            Em <strong>Somente Leitura</strong>, o usuário visualiza páginas, relatórios e filtros, mas não pode criar, editar ou excluir registros.
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="permModalOpen = false" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-semibold">
                                Cancelar
                            </button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow">
                                <i class="fas fa-save mr-1"></i> Salvar Permissões
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ======================================================================= -->
    <!-- MODAL 3: EDITAR DADOS CADASTRAIS, SENHA E PAPEL DO USUÁRIO              -->
    <!-- ======================================================================= -->
    <div x-show="editModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="editModalOpen" @click="editModalOpen = false" class="fixed inset-0 bg-gray-800 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="editModalOpen" class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-200">
                <form :action="'/admin/equipe/' + editUser.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-gray-800 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white">
                            <i class="fas fa-user-edit text-indigo-400 mr-1.5"></i> Editar Dados e Senha
                        </h3>
                        <button type="button" @click="editModalOpen = false" class="text-gray-300 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome</label>
                            <input type="text" name="name" x-model="editUser.name" required
                                   class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail de Login</label>
                            <input type="email" name="email" x-model="editUser.email" required
                                   class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Telefone / WhatsApp</label>
                            <input type="text" name="whatsapp" x-model="editUser.whatsapp"
                                   class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nova Senha (deixe em branco para manter a atual)</label>
                            <input type="password" name="password" placeholder="••••••••"
                                   class="w-full border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 bg-white">
                        </div>
                        <div class="pt-2 border-t border-gray-200 flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-800">Bloquear acesso deste login?</label>
                            <input type="checkbox" name="bloqueado" value="1" x-model="editUser.bloqueado" class="h-4 w-4 text-red-600 border-gray-300 rounded">
                        </div>
                    </div>

                    <div class="bg-gray-100 px-6 py-4 flex justify-end gap-3 border-t border-gray-200">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-semibold">
                            Cancelar
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow">
                            Salvar Alterações
                        </button>
                    </div>
                </form>

                <!-- Seção para alterar o papel geral (Cliente / Equipe / Master / Parceiro) -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <form :action="'/admin/equipe/' + editUser.id + '/role'" method="POST" class="flex flex-wrap items-center justify-between gap-2">
                        @csrf
                        <span class="text-xs font-bold text-gray-700 uppercase">Papel da Conta:</span>
                        <div class="flex flex-wrap gap-2 items-center">
                            <select name="role" x-model="editUser.role" class="border border-gray-300 rounded-md py-1.5 px-2 text-xs text-gray-900 bg-white">
                                <option value="client">Cliente</option>
                                <option value="admin">Equipe / Operador</option>
                                <option value="brecho_admin">Brechó Parceiro</option>
                                <option value="admin_master">Master</option>
                            </select>
                            @if(isset($brechos) && $brechos->count() > 0)
                                <select name="brecho_id" x-model="editUser.brecho_id" class="border border-gray-300 rounded-md py-1.5 px-2 text-xs text-gray-900 bg-white">
                                    <option value="">(Sem Brechó)</option>
                                    @foreach($brechos as $b)
                                        <option value="{{ $b->id }}">{{ $b->nome }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold py-1.5 px-3 rounded-md">
                                Atualizar Papel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function equipePermissionsManager() {
    const moduleKeys = @json(array_keys($modules));

    function buildDefaultPerms(defaultVal = 'read') {
        const obj = {};
        moduleKeys.forEach(k => { obj[k] = defaultVal; });
        return obj;
    }

    return {
        createModalOpen: false,
        permModalOpen: false,
        editModalOpen: false,
        newRole: 'admin',
        newPerms: buildDefaultPerms('read'),
        permUser: {
            id: null,
            name: '',
            email: '',
            role: 'admin',
            has_custom: false,
            permissions: buildDefaultPerms('write'),
        },
        editUser: {
            id: null,
            name: '',
            email: '',
            whatsapp: '',
            role: 'admin',
            brecho_id: '',
            bloqueado: false,
        },

        openCreateModal() {
            this.newRole = 'admin';
            this.newPerms = buildDefaultPerms('read');
            this.newPerms['dashboard'] = 'read';
            this.createModalOpen = true;
        },

        openPermModal(user) {
            this.permUser = {
                id: user.id,
                name: user.name,
                email: user.email,
                role: user.role,
                has_custom: user.has_custom,
                permissions: Object.assign(buildDefaultPerms('none'), user.permissions || {}),
            };
            this.permModalOpen = true;
        },

        openEditModal(user) {
            this.editUser = {
                id: user.id,
                name: user.name,
                email: user.email,
                whatsapp: user.whatsapp || '',
                role: user.role,
                brecho_id: user.brecho_id || '',
                bloqueado: !!user.bloqueado,
            };
            this.editModalOpen = true;
        },

        applyPreset(targetPerms, preset) {
            if (preset === 'all_write') {
                moduleKeys.forEach(k => { targetPerms[k] = 'write'; });
            } else if (preset === 'all_read') {
                moduleKeys.forEach(k => { targetPerms[k] = 'read'; });
            } else if (preset === 'all_none') {
                moduleKeys.forEach(k => { targetPerms[k] = 'none'; });
            } else if (preset === 'comercial') {
                moduleKeys.forEach(k => { targetPerms[k] = 'none'; });
                targetPerms['dashboard']   = 'read';
                targetPerms['lives']       = 'write';
                targetPerms['sacolinhas']  = 'write';
                targetPerms['pedidos']     = 'write';
                targetPerms['clientes']    = 'write';
                targetPerms['atendimento'] = 'write';
                targetPerms['produtos']    = 'read';
            } else if (preset === 'estoque') {
                moduleKeys.forEach(k => { targetPerms[k] = 'none'; });
                targetPerms['dashboard']   = 'read';
                targetPerms['produtos']    = 'write';
                targetPerms['avaliacoes']  = 'write';
                targetPerms['sacolinhas']  = 'write';
                targetPerms['pedidos']     = 'read';
            } else if (preset === 'financeiro') {
                moduleKeys.forEach(k => { targetPerms[k] = 'none'; });
                targetPerms['dashboard']   = 'read';
                targetPerms['financeiro']  = 'write';
                targetPerms['relatorios']  = 'write';
                targetPerms['pedidos']     = 'read';
                targetPerms['clientes']    = 'read';
                targetPerms['avaliacoes']  = 'read';
            }
        }
    };
}
</script>
@endpush
@endsection
