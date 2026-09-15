@extends('layouts.app')

@section('title', 'Brechós Parceiros')
@section('brand_icon', 'fas fa-store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ createModalOpen: false, editModalOpen: false, editBrecho: {} }">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Gerenciar Brechós</h1>
            <p class="text-sm text-gray-500">Cadastre brechós parceiros para utilizarem a estrutura de lives, bipagem e pedidos.</p>
        </div>
        <button @click="createModalOpen = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 transition">
            <i class="fas fa-plus mr-2"></i> Novo Brechó Parceiro
        </button>
    </div>

    <!-- Tabela de Brechós -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nome do Brechó</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contato / WhatsApp</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Peças</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Lives</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($brechos as $b)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-700">#{{ $b->id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-store text-indigo-500"></i>
                            {{ $b->nome }}
                            @if($b->id === 1)
                                <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded-full">Matriz</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400">slug: {{ $b->slug }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div>{{ $b->whatsapp ?: ($b->telefone ?: 'Não informado') }}</div>
                        @if($b->chave_pix)
                            <div class="text-xs text-emerald-600"><i class="fas fa-money-bill-wave mr-1"></i>Pix: {{ $b->chave_pix }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">
                        {{ $b->items_count }} itens
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-semibold">
                        {{ $b->lives_count }} lives
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($b->ativo)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inativo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button @click="editBrecho = {{ json_encode($b) }}; editModalOpen = true;" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100 transition">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum brechó cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Criar Brechó -->
    <div x-show="createModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="createModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.brechos.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-store text-indigo-600"></i> Cadastrar Brechó Parceiro
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700">Nome do Brechó *</label>
                                <input type="text" name="nome" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">CNPJ / CPF</label>
                                    <input type="text" name="documento" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">WhatsApp / Telefone</label>
                                    <input type="text" name="whatsapp" placeholder="(99) 99999-9999" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">Chave Pix</label>
                                    <input type="text" name="chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">Tipo de Chave</label>
                                    <select name="tipo_chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        <option value="cpf">CPF</option>
                                        <option value="cnpj">CNPJ</option>
                                        <option value="email">E-mail</option>
                                        <option value="telefone">Telefone</option>
                                        <option value="aleatoria">Chave Aleatória</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="createModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Salvar Brechó</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Brechó -->
    <div x-show="editModalOpen" x-cloak class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="editModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="'/admin/brechos/' + editBrecho.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-edit text-indigo-600"></i> Editar Brechó
                        </h3>
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="block font-medium text-gray-700">Nome do Brechó *</label>
                                <input type="text" name="nome" x-model="editBrecho.nome" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">CNPJ / CPF</label>
                                    <input type="text" name="documento" x-model="editBrecho.documento" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">WhatsApp / Telefone</label>
                                    <input type="text" name="whatsapp" x-model="editBrecho.whatsapp" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-700">Chave Pix</label>
                                    <input type="text" name="chave_pix" x-model="editBrecho.chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block font-medium text-gray-700">Tipo de Chave</label>
                                    <select name="tipo_chave_pix" x-model="editBrecho.tipo_chave_pix" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecione...</option>
                                        <option value="cpf">CPF</option>
                                        <option value="cnpj">CNPJ</option>
                                        <option value="email">E-mail</option>
                                        <option value="telefone">Telefone</option>
                                        <option value="aleatoria">Chave Aleatória</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700">Status</label>
                                <select name="ativo" x-model="editBrecho.ativo" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option :value="1">Ativo</option>
                                    <option :value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-3 flex justify-end gap-2">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
