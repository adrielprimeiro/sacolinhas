{{-- resources/views/admin/financeiro/_subnav.blade.php --}}
@php
$currentRoute = Route::currentRouteName();
@endphp

<div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-3" x-data="{ activeDropdown: null }">
    
    {{-- Dropdown: Análises & Relatórios --}}
    @php
    $isAnalisesActive = str_contains($currentRoute, 'dashboard') || str_contains($currentRoute, 'fluxodecaixa') || str_contains($currentRoute, 'relatoriogerencial') || str_contains($currentRoute, 'dre') || str_contains($currentRoute, 'orcamento');
    @endphp
    <div class="relative" @click.outside="activeDropdown = null">
        <button @click.stop="activeDropdown = activeDropdown === 'analises' ? null : 'analises'"
                class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 {{ $isAnalisesActive ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600' }}">
            <i class="fas fa-chart-bar"></i>
            <span>Análise</span>
            <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="activeDropdown === 'analises' ? 'rotate-180' : ''"></i>
        </button>
        
        <div x-show="activeDropdown === 'analises'" 
             x-transition
             x-cloak
             class="absolute left-0 mt-2 w-56 rounded-xl bg-white border border-gray-100 shadow-xl py-2 z-50">
            <a href="{{ route('financeiro.dashboard') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'dashboard') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-home w-5 text-center"></i> Dashboard
            </a>
            <a href="{{ route('financeiro.fluxodecaixa') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'fluxodecaixa') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-funnel-dollar w-5 text-center"></i> Fluxo de Caixa
            </a>
            <a href="{{ route('financeiro.dre') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'dre') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-file-invoice w-5 text-center"></i> DRE Contábil
            </a>
            <a href="{{ route('financeiro.relatoriogerencial') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'relatoriogerencial') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i> Relatório Gerencial
            </a>
            <a href="{{ route('financeiro.orcamento.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'orcamento') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-chart-line w-5 text-center"></i> Orçamento
            </a>
        </div>
    </div>

    {{-- Dropdown: Dia a Dia / Operações --}}
    @php
    $isOperacoesActive = str_contains($currentRoute, 'lancamentos') || str_contains($currentRoute, 'conciliacao') || str_contains($currentRoute, 'movimentacoes');
    @endphp
    <div class="relative" @click.outside="activeDropdown = null">
        <button @click.stop="activeDropdown = activeDropdown === 'operacoes' ? null : 'operacoes'"
                class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 {{ $isOperacoesActive ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600' }}">
            <i class="fas fa-exchange-alt"></i>
            <span>Operações</span>
            <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="activeDropdown === 'operacoes' ? 'rotate-180' : ''"></i>
        </button>
        
        <div x-show="activeDropdown === 'operacoes'" 
             x-transition
             x-cloak
             class="absolute left-0 mt-2 w-56 rounded-xl bg-white border border-gray-100 shadow-xl py-2 z-50">
            <a href="{{ route('financeiro.lancamentos.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'lancamentos') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-file-invoice-dollar w-5 text-center"></i> Lançamentos
            </a>
            <a href="{{ route('financeiro.conciliacao.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'conciliacao') && !str_contains($currentRoute, 'auditoria') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-balance-scale w-5 text-center"></i> Conciliação
            </a>
            <a href="{{ route('financeiro.conciliacao.auditoria') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'auditoria') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-shield-alt w-5 text-center"></i> Auditoria de Saldo
            </a>
            <a href="{{ route('financeiro.movimentacoes.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'movimentacoes') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-history w-5 text-center"></i> Movimentações
            </a>
        </div>
    </div>

    {{-- Dropdown: Cadastros & Estrutura --}}
    @php
    $isCadastrosActive = str_contains($currentRoute, 'classificacao_financeira') || str_contains($currentRoute, 'contas') || str_contains($currentRoute, 'pessoas');
    @endphp
    <div class="relative" @click.outside="activeDropdown = null">
        <button @click.stop="activeDropdown = activeDropdown === 'cadastros' ? null : 'cadastros'"
                class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 {{ $isCadastrosActive ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600' }}">
            <i class="fas fa-cogs"></i>
            <span>Cadastro</span>
            <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="activeDropdown === 'cadastros' ? 'rotate-180' : ''"></i>
        </button>
        
        <div x-show="activeDropdown === 'cadastros'" 
             x-transition
             x-cloak
             class="absolute left-0 mt-2 w-56 rounded-xl bg-white border border-gray-100 shadow-xl py-2 z-50">
            <a href="{{ route('financeiro.contas.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'contas') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-university w-5 text-center"></i> Contas Bancárias
            </a>
            <a href="{{ route('classificacao_financeira.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'classificacao_financeira') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-list-ul w-5 text-center"></i> Plano de Contas
            </a>
            <a href="{{ route('financeiro.pessoas.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 {{ str_contains($currentRoute, 'pessoas') ? 'font-bold text-indigo-600 bg-indigo-50/50' : '' }}">
                <i class="fas fa-users w-5 text-center"></i> Contatos
            </a>
        </div>
    </div>

    {{-- Botão de Link Direto: Carteira Cliente --}}
    @php
    $isCarteiraActive = str_contains($currentRoute, 'conta_corrente');
    @endphp
    <a href="{{ route('admin.conta_corrente.index') }}"
       class="px-4 py-2 rounded-xl text-sm font-bold transition flex items-center gap-2 {{ $isCarteiraActive ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-600 bg-gray-50 hover:bg-indigo-50 hover:text-indigo-600' }}">
        <i class="fas fa-wallet"></i>
        <span>Carteira Cliente</span>
    </a>
</div>
