@extends('layouts.app')
@section('title', 'Gerenciar Clientes')
@section('brand_route', 'admin.clientes.index')
@section('brand_icon', 'fas fa-users')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Gerenciar Clientes</h1>
        <p class="text-sm text-gray-400 mt-0.5">Cadastre e gerencie a carteira de clientes e seus limites de crédito</p>
    </div>
    <div>
        <a href="{{ route('admin.clientes.create') }}" 
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
    </div>
</div>

{{-- Filtros Avançados --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Filtros de Pesquisa</h2>
    <form method="GET" action="{{ route('admin.clientes.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- Busca Geral --}}
        <div class="sm:col-span-2">
            <label class="text-xs text-gray-400 font-medium block mb-1">Busca Geral</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nome, email, CPF ou rede social..."
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        </div>

        {{-- Status --}}
        <div>
            <label class="text-xs text-gray-400 font-medium block mb-1">Status</label>
            <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Todos</option>
                <option value="ativo" {{ request('status') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                <option value="bloqueado" {{ request('status') === 'bloqueado' ? 'selected' : '' }}>Bloqueado</option>
            </select>
        </div>

        {{-- Cidade --}}
        <div>
            <label class="text-xs text-gray-400 font-medium block mb-1">Cidade</label>
            <input type="text" name="cidade" value="{{ request('cidade') }}"
                   placeholder="Filtrar por cidade"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
        </div>

        {{-- Estado --}}
        <div>
            <label class="text-xs text-gray-400 font-medium block mb-1">Estado</label>
            <select name="estado" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Todos</option>
                @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                    <option value="{{ $uf }}" {{ request('estado') === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                @endforeach
            </select>
        </div>

        {{-- Redes Sociais --}}
        <div>
            <label class="text-xs text-gray-400 font-medium block mb-1">Redes Sociais</label>
            <select name="rede_social" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Todos</option>
                <option value="instagram" {{ request('rede_social') === 'instagram' ? 'selected' : '' }}>Com Instagram</option>
                <option value="whatsapp" {{ request('rede_social') === 'whatsapp' ? 'selected' : '' }}>Com WhatsApp</option>
                <option value="tiktok" {{ request('rede_social') === 'tiktok' ? 'selected' : '' }}>Com TikTok</option>
            </select>
        </div>

        {{-- Pedidos --}}
        <div>
            <label class="text-xs text-gray-400 font-medium block mb-1">Pedidos</label>
            <select name="pedidos" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">Todos</option>
                <option value="com" {{ request('pedidos') === 'com' ? 'selected' : '' }}>Com Pedidos</option>
                <option value="sem" {{ request('pedidos') === 'sem' ? 'selected' : '' }}>Sem Pedidos</option>
            </select>
        </div>

        {{-- Botões de Ação --}}
        <div class="lg:col-span-5 flex justify-end gap-2 mt-2">
            <a href="{{ route('admin.clientes.index') }}" 
               class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 text-sm font-bold px-4 py-2 rounded-lg transition">
                <i class="fas fa-redo"></i> Limpar
            </a>
            <button type="submit" 
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2 rounded-lg transition shadow-sm">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>
    </form>
</div>

{{-- Lista de Clientes --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    @if($clientes->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider w-16">Avatar</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Contato / CPF</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider text-right">Limite de Crédito</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider text-right">L. Disponível</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Status</th>
                        <th class="px-5 py-4 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center w-36">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($clientes as $cliente)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-lg shadow-sm"
                                     title="{{ $cliente->nome_completo }}">
                                    {{ strtoupper(substr($cliente->nome_completo, 0, 1)) }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div>
                                    <p class="font-bold text-gray-800 text-sm sm:text-base">{{ $cliente->nome_completo }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-xs text-gray-400">ID: #{{ $cliente->id }}</span>
                                        @if($cliente->codigo_cliente)
                                            <span class="bg-gray-100 text-gray-600 text-[10px] font-semibold px-1.5 py-0.5 rounded">Cód: {{ $cliente->codigo_cliente }}</span>
                                        @endif
                                        @if($cliente->tipo_cliente)
                                            <span class="bg-indigo-50 text-indigo-600 text-[10px] font-semibold px-1.5 py-0.5 rounded">{{ $cliente->tipo_cliente }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs text-gray-500 space-y-0.5">
                                    <p class="flex items-center gap-1.5"><i class="fas fa-envelope text-gray-400 w-3.5"></i> {{ $cliente->email }}</p>
                                    @if($cliente->telefone_principal)
                                        <p class="flex items-center gap-1.5"><i class="fas fa-phone text-gray-400 w-3.5"></i> {{ $cliente->telefone_principal_formatado }}</p>
                                    @endif
                                    @if($cliente->cpf)
                                        <p class="flex items-center gap-1.5"><i class="fas fa-id-card text-gray-400 w-3.5"></i> {{ $cliente->cpf_formatado }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($cliente->instagram)
                                            <a href="{{ $cliente->instagram_url }}" target="_blank" class="text-pink-500 hover:text-pink-600 transition" title="Instagram">
                                                <i class="fab fa-instagram text-sm"></i>
                                            </a>
                                        @endif
                                        @if($cliente->tiktok)
                                            <a href="{{ $cliente->tiktok_url }}" target="_blank" class="text-gray-800 hover:text-black transition" title="TikTok">
                                                <i class="fab fa-tiktok text-sm"></i>
                                            </a>
                                        @endif
                                        @if($cliente->whatsapp)
                                            <a href="{{ $cliente->whatsapp_url }}" target="_blank" class="text-green-500 hover:text-green-600 transition" title="WhatsApp">
                                                <i class="fab fa-whatsapp text-sm"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-gray-700 text-sm">
                                R$ {{ number_format($cliente->limite?->limite_credito ?? 300.00, 2, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-sm">
                                @php
                                    $disp = $cliente->limite?->limite_disponivel ?? 300.00;
                                    $color = $disp < 0 ? 'text-red-600' : ($disp == 0 ? 'text-orange-500' : 'text-green-600');
                                @endphp
                                <span class="{{ $color }}">R$ {{ number_format($disp, 2, ',', '.') }}</span>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($cliente->bloqueado)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-lock text-[10px]"></i> Bloqueado
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle text-[10px]"></i> Ativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.clientes.show', $cliente) }}"
                                       class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 hover:text-blue-700 transition flex items-center justify-center"
                                       title="Visualizar">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('admin.clientes.edit', $cliente) }}"
                                       class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-600 hover:text-amber-700 transition flex items-center justify-center"
                                       title="Editar">
                                        <i class="fas fa-edit text-xs"></i>
                                    </a>
                                    <form action="{{ route('admin.clientes.destroy', $cliente) }}"
                                          method="POST"
                                          class="inline"
                                          onsubmit="return confirm('Tem certeza que deseja deletar este cliente permanentemente?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 hover:text-red-700 transition flex items-center justify-center"
                                                title="Deletar">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="px-5 py-4 border-t border-gray-50">
            {{ $clientes->appends(request()->query())->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-50 border border-gray-100 text-gray-400 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <h5 class="font-bold text-gray-700 text-base">Nenhum cliente encontrado</h5>
            <p class="text-sm text-gray-400 mt-1 max-w-md mx-auto">Nenhum registro corresponde aos filtros selecionados. Comece criando seu primeiro cliente!</p>
            <div class="mt-5">
                <a href="{{ route('admin.clientes.create') }}" 
                   class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
                    <i class="fas fa-plus"></i> Criar Primeiro Cliente
                </a>
            </div>
        </div>
    @endif
</div>
@endsection