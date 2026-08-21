@extends('layouts.app')

@section('title', 'Inventário - Locais Físicos do Estoque')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Principal com Botão para o Scanner -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-boxes text-indigo-600"></i>
                <span>Inventário do Estoque</span>
            </h1>
            <p class="text-gray-500 mt-1">Selecione ou clique em qualquer local físico para ver a lista detalhada de todos os itens guardados nele.</p>
        </div>
        
        <!-- Botão Destaque para abrir a interface do Inventário Scanner -->
        <div class="flex items-center gap-3">
            <a href="{{ route('inventario.scanner') }}" class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-2.5 px-5 rounded-xl shadow-md hover:shadow-lg transition duration-200 text-sm">
                <i class="fas fa-qrcode text-lg"></i>
                <span>Inventário Scanner</span>
            </a>
        </div>
    </div>

    <!-- Cards de Resumo Geral -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Total de Locais -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-indigo-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Locais Cadastrados</p>
                <p class="text-2xl font-extrabold text-gray-800 mt-1">{{ count($locaisEstoque) }}</p>
            </div>
            <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                <i class="fas fa-map-marker-alt text-xl"></i>
            </div>
        </div>

        <!-- Card 2: Peças com Local -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-blue-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Peças Endereçadas</p>
                <p class="text-2xl font-extrabold text-blue-600 mt-1">
                    {{ number_format($locaisEstoque->sum('qtd_pecas'), 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <i class="fas fa-tshirt text-xl"></i>
            </div>
        </div>

        <!-- Card 3: Valor Total de Venda nos Locais -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-green-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor Em Prateleiras</p>
                <p class="text-2xl font-extrabold text-green-600 mt-1">
                    R$ {{ number_format($locaisEstoque->sum('valor_total_venda'), 2, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                <i class="fas fa-tag text-xl"></i>
            </div>
        </div>

        <!-- Card 4: Sem Localização -->
        <div class="bg-white rounded-xl shadow-md p-5 border-l-4 border-yellow-500 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sem Localização</p>
                <p class="text-2xl font-extrabold text-yellow-600 mt-1">
                    {{ number_format($itensSemLocal ?? 0, 0, ',', '.') }}
                </p>
            </div>
            <div class="p-3 bg-yellow-50 text-yellow-600 rounded-lg">
                <i class="fas fa-question-circle text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Barra de Pesquisa de Locais -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('inventario') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" name="localizacao" value="{{ $buscaLocal }}" placeholder="Filtrar por código de local (Ex: A11, A12, A21...)" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition duration-150">
            </div>
            <button type="submit" class="w-full sm:w-auto bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-5 py-2 rounded-lg transition duration-150">
                Filtrar
            </button>
            @if(!empty($buscaLocal))
                <a href="{{ route('inventario') }}" class="w-full sm:w-auto text-center text-sm font-medium text-gray-500 hover:text-gray-700 px-3 py-2">
                    Limpar Filtro
                </a>
            @endif
        </form>
    </div>

    <!-- Tabela de Locais Físicos do Estoque (Clique para Expandir os Itens) -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 mb-8">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-warehouse text-indigo-500"></i>
                <span>Locais Físicos do Estoque</span>
            </h3>
            <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2.5 py-1 rounded-full">
                💡 Clique na linha do local para abrir os itens detalhados
            </span>
        </div>

        @if(count($locaisEstoque) > 0)
            <div class="divide-y divide-gray-100">
                @foreach($locaisEstoque as $loc)
                    <div class="location-group">
                        <!-- Linha do Local Físico -->
                        <div onclick="toggleLocalItens('{{ $loc->localizacao }}')" class="flex items-center justify-between px-6 py-4 hover:bg-blue-50/70 cursor-pointer transition duration-150 group select-none">
                            <div class="flex items-center gap-4">
                                <span class="px-3 py-1 font-extrabold rounded-lg bg-blue-600 text-white text-sm shadow-sm group-hover:bg-blue-700">
                                    {{ $loc->localizacao }}
                                </span>
                                <span class="text-gray-700 font-semibold text-sm">
                                    {{ number_format($loc->qtd_pecas, 0, ',', '.') }} peças armazenadas
                                </span>
                            </div>

                            <div class="flex items-center gap-4">
                                <span class="font-bold text-emerald-600 text-base">
                                    R$ {{ number_format($loc->valor_total_venda, 2, ',', '.') }}
                                </span>
                                <span class="text-xs font-semibold text-indigo-600 group-hover:translate-x-1 transition duration-150 flex items-center gap-1">
                                    <span>Ver itens</span>
                                    <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="icon-{{ $loc->localizacao }}"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Container Expansível de Itens -->
                        <div id="container-{{ $loc->localizacao }}" class="hidden bg-gray-50 border-t border-b border-gray-200 p-4 transition-all">
                            <div class="flex items-center justify-between mb-3 px-2">
                                <h4 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-list text-indigo-500"></i>
                                    <span>Itens Guardados no Local: <strong class="text-indigo-600">{{ $loc->localizacao }}</strong></span>
                                </h4>
                                <div class="flex items-center gap-2">
                                    <input type="text" id="search-{{ $loc->localizacao }}" onkeyup="filterLocalTable('{{ $loc->localizacao }}')" placeholder="Buscar neste local..." class="px-3 py-1 bg-white border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <a href="{{ route('items.index') }}?localizacao={{ urlencode($loc->localizacao) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold underline">
                                        Abrir no Gerenciador de Itens &rarr;
                                    </a>
                                </div>
                            </div>

                            <!-- Estado de Carregamento -->
                            <div id="loading-{{ $loc->localizacao }}" class="text-center py-6 text-gray-500 text-xs">
                                <i class="fas fa-circle-notch fa-spin text-indigo-600 text-xl mb-2"></i>
                                <p>Carregando itens do local {{ $loc->localizacao }}...</p>
                            </div>

                            <!-- Tabela de Itens do Local -->
                            <div id="table-wrapper-{{ $loc->localizacao }}" class="hidden overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
                                <table class="w-full text-left border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-gray-100 text-gray-600 font-semibold uppercase tracking-wider border-b border-gray-200">
                                            <th class="py-2.5 px-4">Código</th>
                                            <th class="py-2.5 px-4">Produto</th>
                                            <th class="py-2.5 px-4">Tam / Cor</th>
                                            <th class="py-2.5 px-4">Marca / Estado</th>
                                            <th class="py-2.5 px-4">Status</th>
                                            <th class="py-2.5 px-4 text-right">Preço</th>
                                            <th class="py-2.5 px-4 text-right">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-{{ $loc->localizacao }}" class="divide-y divide-gray-100 text-gray-700">
                                        <!-- Preenchido via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-folder-open text-4xl text-gray-300 mb-3"></i>
                <p class="font-medium text-base">Nenhum local físico encontrado.</p>
            </div>
        @endif
    </div>
</div>

<script>
const cacheItens = {};

function toggleLocalItens(local) {
    const container = document.getElementById(`container-${local}`);
    const icon = document.getElementById(`icon-${local}`);
    
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
        
        if (!cacheItens[local]) {
            fetchLocalItens(local);
        }
    } else {
        container.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }
}

function fetchLocalItens(local) {
    const loading = document.getElementById(`loading-${local}`);
    const tableWrapper = document.getElementById(`table-wrapper-${local}`);
    const tbody = document.getElementById(`tbody-${local}`);

    loading.classList.remove('hidden');
    tableWrapper.classList.add('hidden');

    fetch(`{{ route('inventario.itens-local') }}?localizacao=${encodeURIComponent(local)}`)
        .then(res => res.json())
        .then(data => {
            cacheItens[local] = data.itens || [];
            renderLocalTable(local, cacheItens[local]);
            loading.classList.add('hidden');
            tableWrapper.classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            loading.innerHTML = `<p class="text-red-500">Erro ao carregar itens do local ${local}.</p>`;
        });
}

function renderLocalTable(local, itens) {
    const tbody = document.getElementById(`tbody-${local}`);
    
    if (!itens || itens.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-center text-gray-400">Nenhum item encontrado neste local.</td></tr>`;
        return;
    }

    let html = '';
    itens.forEach(item => {
        let badgeColor = 'bg-gray-100 text-gray-800';
        if (item.status === 'disponivel') badgeColor = 'bg-green-100 text-green-800';
        else if (item.status === 'vendido') badgeColor = 'bg-red-100 text-red-800';
        else if (item.status === 'reservado') badgeColor = 'bg-yellow-100 text-yellow-800';
        else if (item.status === 'estoque') badgeColor = 'bg-blue-100 text-blue-800';

        html += `
            <tr class="hover:bg-gray-50 item-row">
                <td class="py-2.5 px-4 font-mono font-bold text-gray-800">#${item.codigo}</td>
                <td class="py-2.5 px-4 font-medium text-gray-800">${escapeHtml(item.nome_do_produto)}</td>
                <td class="py-2.5 px-4 text-gray-600">${escapeHtml(item.tamanho)} / ${escapeHtml(item.cor)}</td>
                <td class="py-2.5 px-4 text-gray-600">${escapeHtml(item.marca)} (${escapeHtml(item.estado)})</td>
                <td class="py-2.5 px-4">
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full ${badgeColor}">
                        ${escapeHtml(item.status_label)}
                    </span>
                </td>
                <td class="py-2.5 px-4 text-right font-bold text-emerald-600">R$ ${item.preco}</td>
                <td class="py-2.5 px-4 text-right">
                    <a href="${item.edit_url}" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterLocalTable(local) {
    const input = document.getElementById(`search-${local}`).value.toLowerCase();
    const rows = document.querySelectorAll(`#tbody-${local} tr.item-row`);

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

// Auto abrir caso venha ?localizacao=XXX na URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const locParam = urlParams.get('localizacao');
    if (locParam) {
        toggleLocalItens(locParam);
    }
});
</script>
@endsection
