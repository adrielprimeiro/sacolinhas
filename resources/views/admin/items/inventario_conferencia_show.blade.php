@extends('layouts.app')

@section('title', 'Relatório de Conferência de Estoque - Local ' . $conferencia->localizacao)

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header Principal (Ocultado na impressão) -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('inventario.conferencias.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-150">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Histórico de Conferências</span>
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-file-invoice text-indigo-600"></i>
                <span>Relatório de Conferência de Estoque</span>
            </h1>
            <p class="text-gray-500 mt-1">Sessão de auditoria realizada em <strong>{{ $conferencia->created_at->format('d/m/Y \à\s H:i') }}</strong> por <strong>{{ $conferencia->user->name ?? 'Sistema' }}</strong>.</p>
        </div>
        
        <!-- Botões de Ação -->
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm text-sm transition duration-150">
                <i class="fas fa-print"></i>
                <span>Imprimir Relatório</span>
            </button>
            <a href="{{ route('inventario.local', urlencode($conferencia->localizacao)) }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm text-sm transition duration-150">
                <i class="fas fa-warehouse"></i>
                <span>Ir para Local {{ $conferencia->localizacao }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center gap-3 print:hidden">
            <i class="fas fa-check-circle text-xl text-emerald-600"></i>
            <span class="font-semibold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Card de Cabeçalho do Relatório -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 border-b border-gray-100 pb-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-2xl shadow-md">
                    {{ $conferencia->localizacao }}
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Conferência Física — Local {{ $conferencia->localizacao }}</h2>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="far fa-user text-indigo-500 mr-1"></i> Operador: <strong>{{ $conferencia->user->name ?? 'Sistema' }}</strong> | 
                        <i class="far fa-clock text-indigo-500 mx-1"></i> Data: <strong>{{ $conferencia->created_at->format('d/m/Y H:i:s') }}</strong>
                    </p>
                </div>
            </div>

            <!-- Placa de Acurácia -->
            <div class="text-right bg-gray-50 p-4 rounded-xl border border-gray-200 self-start md:self-auto">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Índice de Acurácia</p>
                @php
                    $acuText = 'text-emerald-600';
                    if ($conferencia->acuracia_percentual < 90) $acuText = 'text-rose-600';
                    elseif ($conferencia->acuracia_percentual < 98) $acuText = 'text-yellow-600';
                @endphp
                <p class="text-3xl font-black {{ $acuText }} mt-0.5">
                    {{ number_format($conferencia->acuracia_percentual, 1, ',', '.') }}%
                </p>
            </div>
        </div>

        <!-- Indicadores de Resumo da Auditoria -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
            <div class="bg-gray-50 p-3.5 rounded-lg border border-gray-100">
                <p class="text-xs font-semibold text-gray-500">Esperados</p>
                <p class="text-xl font-bold text-gray-800 mt-1">{{ $conferencia->total_esperado }} pcs</p>
            </div>
            <div class="bg-indigo-50 p-3.5 rounded-lg border border-indigo-100">
                <p class="text-xs font-semibold text-indigo-700">Total Bipados</p>
                <p class="text-xl font-bold text-indigo-800 mt-1">{{ $conferencia->total_lido }} pcs</p>
            </div>
            <div class="bg-emerald-50 p-3.5 rounded-lg border border-emerald-100">
                <p class="text-xs font-semibold text-emerald-700">Confirmados (Match)</p>
                <p class="text-xl font-bold text-emerald-700 mt-1">{{ $conferencia->total_encontrados }} pcs</p>
            </div>
            <div class="bg-rose-50 p-3.5 rounded-lg border border-rose-100">
                <p class="text-xs font-semibold text-rose-700">Faltantes (Divergentes)</p>
                <p class="text-xl font-bold text-rose-700 mt-1">{{ $conferencia->total_faltantes }} pcs</p>
            </div>
            <div class="bg-blue-50 p-3.5 rounded-lg border border-blue-100 col-span-2 sm:col-span-1">
                <p class="text-xs font-semibold text-blue-700">Sobrando / Realocados</p>
                <p class="text-xl font-bold text-blue-700 mt-1">{{ $conferencia->total_sobrando }} pcs</p>
            </div>
        </div>
    </div>

    @php
        $detalhes = $conferencia->detalhes_json ?? [];
        $faltantes = $detalhes['faltantes'] ?? [];
        $sobrando = $detalhes['sobrando'] ?? [];
        $encontrados = $detalhes['encontrados'] ?? [];
    @endphp

    <!-- 1. ITENS FALTANTES (SE HOUVER) -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-rose-200 mb-8">
        <div class="px-6 py-4 bg-rose-50 border-b border-rose-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-rose-900 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-rose-600"></i>
                <span>Itens Faltantes / Divergentes ({{ count($faltantes) }} peças)</span>
            </h3>
            <span class="text-xs font-bold text-rose-700 bg-rose-100 px-2.5 py-1 rounded-full">
                Estavam cadastrados em {{ $conferencia->localizacao }} mas NÃO foram bipados
            </span>
        </div>

        @if(count($faltantes) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-rose-100/50 text-rose-900 text-xs uppercase tracking-wider border-b border-rose-200">
                            <th class="py-3 px-6 font-semibold">Código</th>
                            <th class="py-3 px-6 font-semibold">Produto</th>
                            <th class="py-3 px-6 font-semibold">Tam / Cor</th>
                            <th class="py-3 px-6 font-semibold">Marca</th>
                            <th class="py-3 px-6 font-semibold text-right">Preço Venda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100 text-gray-700">
                        @foreach($faltantes as $itemF)
                            <tr class="hover:bg-rose-50/50 transition duration-150">
                                <td class="py-3 px-6 font-mono font-bold text-rose-800">#{{ $itemF['codigo'] }}</td>
                                <td class="py-3 px-6 font-medium text-gray-800">{{ $itemF['nome_do_produto'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemF['tamanho'] }} / {{ $itemF['cor'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemF['marca'] }}</td>
                                <td class="py-3 px-6 text-right font-bold text-rose-700">R$ {{ $itemF['preco'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-emerald-700 font-semibold text-sm flex items-center justify-center gap-2">
                <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                <span>Nenhuma peça faltando! Todas as peças cadastradas no local foram confirmadas na leitura.</span>
            </div>
        @endif
    </div>

    <!-- 2. ITENS SOBRANDO / REALOCADOS (SE HOUVER) -->
    @if(count($sobrando) > 0)
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-blue-200 mb-8">
            <div class="px-6 py-4 bg-blue-50 border-b border-blue-200 flex items-center justify-between">
                <h3 class="text-base font-bold text-blue-900 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-blue-600"></i>
                    <span>Itens Sobrando / Realocados ({{ count($sobrando) }} peças)</span>
                </h3>
                <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2.5 py-1 rounded-full">
                    Bipados e atribuídos a {{ $conferencia->localizacao }} durante esta conferência
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-100/50 text-blue-900 text-xs uppercase tracking-wider border-b border-blue-200">
                            <th class="py-3 px-6 font-semibold">Código</th>
                            <th class="py-3 px-6 font-semibold">Produto</th>
                            <th class="py-3 px-6 font-semibold">Tam / Cor</th>
                            <th class="py-3 px-6 font-semibold">Marca</th>
                            <th class="py-3 px-6 font-semibold">Local Anterior</th>
                            <th class="py-3 px-6 font-semibold text-right">Preço Venda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-100 text-gray-700">
                        @foreach($sobrando as $itemS)
                            <tr class="hover:bg-blue-50/50 transition duration-150">
                                <td class="py-3 px-6 font-mono font-bold text-blue-800">#{{ $itemS['codigo'] }}</td>
                                <td class="py-3 px-6 font-medium text-gray-800">{{ $itemS['nome_do_produto'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemS['tamanho'] }} / {{ $itemS['cor'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemS['marca'] }}</td>
                                <td class="py-3 px-6 text-gray-500 font-mono text-xs">{{ $itemS['local_anterior'] }}</td>
                                <td class="py-3 px-6 text-right font-bold text-emerald-600">R$ {{ $itemS['preco'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- 3. ITENS CONFIRMADOSS (MATCH) -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100 mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-check-double text-emerald-500"></i>
                <span>Itens Confirmados / Match ({{ count($encontrados) }} peças)</span>
            </h3>
        </div>

        @if(count($encontrados) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3 px-6 font-semibold">Código</th>
                            <th class="py-3 px-6 font-semibold">Produto</th>
                            <th class="py-3 px-6 font-semibold">Tam / Cor</th>
                            <th class="py-3 px-6 font-semibold">Marca</th>
                            <th class="py-3 px-6 font-semibold text-right">Preço Venda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach($encontrados as $itemE)
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="py-3 px-6 font-mono font-bold text-gray-800">#{{ $itemE['codigo'] }}</td>
                                <td class="py-3 px-6 font-medium text-gray-800">{{ $itemE['nome_do_produto'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemE['tamanho'] }} / {{ $itemE['cor'] }}</td>
                                <td class="py-3 px-6 text-gray-600">{{ $itemE['marca'] }}</td>
                                <td class="py-3 px-6 text-right font-bold text-emerald-600">R$ {{ $itemE['preco'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-6 text-center text-gray-400 text-sm">Nenhum item do local foi confirmado na leitura.</div>
        @endif
    </div>
</div>
@endsection
