@extends('layouts.app')
@section('title', 'Detalhes do Cliente')
@section('brand_route', 'admin.clientes.index')
@section('brand_icon', 'fas fa-users')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Detalhes do Cliente</h1>
        <p class="text-sm text-gray-400 mt-0.5">Visualização completa dos dados cadastrais e financeiros do cliente</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.clientes.index') }}" 
           class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 text-sm font-bold px-4 py-2.5 rounded-xl transition">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="{{ route('admin.clientes.edit', $cliente) }}" 
           class="inline-flex items-center gap-2 bg-amber-50 hover:bg-amber-100 text-amber-600 hover:text-amber-700 text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <i class="fas fa-edit"></i> Editar Cliente
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    {{-- Coluna Esquerda: Informações Principais & Limites --}}
    <div class="lg:col-span-1 space-y-6">
        
        {{-- Card de Perfil Rápido --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center">
            <div class="w-20 h-20 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-3xl shadow-sm mx-auto mb-4">
                {{ strtoupper(substr($cliente->nome_completo, 0, 1)) }}
            </div>
            <h2 class="text-xl font-black text-gray-800">{{ $cliente->nome_completo }}</h2>
            @if($cliente->apelido)
                <p class="text-sm text-gray-400 font-medium">"{{ $cliente->apelido }}"</p>
            @endif
            
            <div class="flex justify-center gap-2 mt-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold 
                    {{ $cliente->bloqueado ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    <i class="fas fa-{{ $cliente->bloqueado ? 'lock' : 'check-circle' }} text-[10px]"></i>
                    {{ $cliente->status }}
                </span>
                @if($cliente->tipo_cliente)
                    <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $cliente->tipo_cliente }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Card de Limite de Crédito solicitado! --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fas fa-wallet text-indigo-500"></i> Limite e Crédito
            </h3>
            <div class="space-y-4">
                {{-- Limite de Crédito Total --}}
                <div class="flex items-center justify-between border-b border-gray-50 pb-2.5">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Limite de Crédito</p>
                        <p class="text-xs text-gray-300">Valor total aprovado</p>
                    </div>
                    <p class="text-lg font-black text-gray-800">
                        R$ {{ number_format($cliente->limite?->limite_credito ?? 300.00, 2, ',', '.') }}
                    </p>
                </div>

                {{-- Limite Utilizado --}}
                <div class="flex items-center justify-between border-b border-gray-50 pb-2.5">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Limite Utilizado</p>
                        <p class="text-xs text-gray-300">Total em sacolinhas ativas</p>
                    </div>
                    <p class="text-lg font-black text-amber-600">
                        R$ {{ number_format($cliente->limite?->limite_utilizado ?? 0.00, 2, ',', '.') }}
                    </p>
                </div>

                {{-- Limite Disponível --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Limite Disponível</p>
                        <p class="text-xs text-gray-300">Disponível para compras</p>
                    </div>
                    @php
                        $disp = $cliente->limite?->limite_disponivel ?? 300.00;
                        $color = $disp < 0 ? 'text-red-600' : ($disp == 0 ? 'text-orange-500' : 'text-green-600');
                    @endphp
                    <p class="text-lg font-black {{ $color }}">
                        R$ {{ number_format($disp, 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- Coluna Direita: Detalhes Cadastrais --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Dados Pessoais --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 flex items-center gap-2">
                <i class="fas fa-user text-indigo-500"></i> Informações Cadastrais
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3.5 gap-x-6 text-sm">
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">E-mail</span>
                    <a href="mailto:{{ $cliente->email }}" class="font-semibold text-indigo-600 hover:text-indigo-700 transition">{{ $cliente->email }}</a>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">CPF</span>
                    <span class="font-semibold text-gray-700">{{ $cliente->cpf_formatado ?: 'Não Informado' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">RG</span>
                    <span class="font-semibold text-gray-700">{{ $cliente->rg ?: 'Não Informado' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Data de Nascimento</span>
                    <span class="font-semibold text-gray-700">
                        {{ $cliente->data_nascimento ? $cliente->data_nascimento->format('d/m/Y') : 'Não Informado' }}
                        @if($cliente->idade)
                            <span class="text-gray-400 font-medium">({{ $cliente->idade }} anos)</span>
                        @endif
                    </span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Sexo</span>
                    <span class="font-semibold text-gray-700">
                        @switch($cliente->sexo)
                            @case('M') Masculino @break
                            @case('F') Feminino @break
                            @case('Outro') Outro @break
                            @default {{ $cliente->sexo ?: 'Não Informado' }}
                        @endswitch
                    </span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Data de Cadastro</span>
                    <span class="font-semibold text-gray-700">
                        {{ $cliente->data_cadastro ? $cliente->data_cadastro->format('d/m/Y H:i') : $cliente->created_at->format('d/m/Y H:i') }}
                        <span class="text-gray-400 font-medium block text-xs mt-0.5">{{ $cliente->tempo_com_cliente }}</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Contatos & Redes Sociais --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 flex items-center gap-2">
                <i class="fas fa-phone text-indigo-500"></i> Contato e Redes Sociais
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3.5 gap-x-6 text-sm">
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Telefone Principal</span>
                    <span class="font-semibold text-gray-700">{{ $cliente->telefone_principal_formatado ?: 'Não Informado' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Telefone Secundário</span>
                    <span class="font-semibold text-gray-700">{{ $cliente->telefone_2_formatado ?: 'Não Informado' }}</span>
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">WhatsApp</span>
                    @if($cliente->whatsapp)
                        <a href="{{ $cliente->whatsapp_url }}" target="_blank" class="font-semibold text-green-600 hover:text-green-700 transition inline-flex items-center gap-1">
                            <i class="fab fa-whatsapp"></i> {{ $cliente->whatsapp_formatado }} <i class="fas fa-external-link-alt text-[9px]"></i>
                        </a>
                    @else
                        <span class="font-semibold text-gray-700">Não Informado</span>
                    @endif
                </div>
                <div>
                    <span class="block text-xs font-bold text-gray-400 uppercase">Redes Sociais</span>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        @if($cliente->instagram)
                            <a href="{{ $cliente->instagram_url }}" target="_blank" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white text-xs font-semibold px-3 py-1 rounded-full flex items-center gap-1 shadow-sm hover:opacity-90 transition">
                                <i class="fab fa-instagram"></i> @ {{ $cliente->instagram }}
                            </a>
                        @endif
                        @if($cliente->tiktok)
                            <a href="{{ $cliente->tiktok_url }}" target="_blank" class="bg-black text-white text-xs font-semibold px-3 py-1 rounded-full flex items-center gap-1 shadow-sm hover:opacity-90 transition">
                                <i class="fab fa-tiktok"></i> @ {{ $cliente->tiktok }}
                            </a>
                        @endif
                        @if(!$cliente->instagram && !$cliente->tiktok)
                            <span class="font-semibold text-gray-700">Nenhuma cadastrada</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Endereço --}}
        @if($cliente->endereco || $cliente->cidade)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 flex items-center gap-2">
                    <i class="fas fa-map-marker-alt text-indigo-500"></i> Endereço
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3.5 gap-x-6 text-sm">
                    @if($cliente->endereco)
                        <div class="sm:col-span-2">
                            <span class="block text-xs font-bold text-gray-400 uppercase">Endereço</span>
                            <span class="font-semibold text-gray-700">
                                {{ $cliente->endereco }}
                                @if($cliente->numero_endereco), nº {{ $cliente->numero_endereco }}@endif
                                @if($cliente->complemento) ({{ $cliente->complemento }})@endif
                            </span>
                        </div>
                    @endif
                    @if($cliente->bairro)
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase">Bairro</span>
                            <span class="font-semibold text-gray-700">{{ $cliente->bairro }}</span>
                        </div>
                    @endif
                    @if($cliente->cidade)
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase">Cidade / Estado</span>
                            <span class="font-semibold text-gray-700">
                                {{ $cliente->cidade }}
                                @if($cliente->estado) — {{ $cliente->estado }}@endif
                            </span>
                        </div>
                    @endif
                    @if($cliente->cep)
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase">CEP</span>
                            <span class="font-semibold text-gray-700">{{ $cliente->cep_formatado }}</span>
                        </div>
                    @endif
                    @if($cliente->pais && $cliente->pais !== 'Brasil')
                        <div>
                            <span class="block text-xs font-bold text-gray-400 uppercase">País</span>
                            <span class="font-semibold text-gray-700">{{ $cliente->pais }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Observações --}}
        @if($cliente->observacao_cliente)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-2">
                    <i class="fas fa-sticky-note text-indigo-500"></i> Observações
                </h3>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-sm text-gray-600 leading-relaxed">
                    {{ $cliente->observacao_cliente }}
                </div>
            </div>
        @endif

        {{-- Histórico e Ações Administrativas --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-50 pb-2 flex items-center gap-2">
                <i class="fas fa-cogs text-indigo-500"></i> Operações do Sistema
            </h3>
            <div class="flex flex-wrap gap-2.5">
                {{-- Bloquear / Desbloquear --}}
                <form action="{{ route('admin.clientes.toggle_block', $cliente) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="inline-flex items-center gap-2 text-sm font-semibold px-5 py-2.5 rounded-xl border transition
                            {{ $cliente->bloqueado 
                                ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100' 
                                : 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100' }}">
                        <i class="fas fa-{{ $cliente->bloqueado ? 'unlock' : 'lock' }}"></i>
                        {{ $cliente->bloqueado ? 'Desbloquear Acesso' : 'Bloquear Acesso' }}
                    </button>
                </form>

                {{-- Excluir Permanente --}}
                <form action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST" class="inline"
                      onsubmit="return confirm('⚠️ ATENÇÃO: Esta ação é definitiva e removerá o cliente e todo seu histórico permanentemente. Deseja prosseguir?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition shadow-sm">
                        <i class="fas fa-trash"></i>
                        Excluir Cadastro
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>
@endsection