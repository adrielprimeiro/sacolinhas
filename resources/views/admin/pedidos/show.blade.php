@extends('layouts.app')

@section('title', $pedido->numero_pedido ?? $pedido->id)
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    @php
        $itens = DB::table('items_pedido as ip')
            ->join('items as i', 'i.id', '=', 'ip.item_id')
            ->where('ip.pedido_id', $pedido->id)
            ->select([
                'ip.id',
                'ip.quantidade',
                'ip.preco_unitario',
                'ip.valor_total',
                'ip.status_item',
                'i.nome_do_produto',
                'i.codigo',
                'i.marca',
                'i.estado',
                'i.cor',
                'i.tamanho',
                'i.image',
            ])
            ->get();

        // Subtotal real: soma de todos os itens do pedido (ativos + devolvidos)
        $subtotal      = (float) $itens->sum('valor_total');
        $frete         = (float) ($pedido->valor_frete ?? 0);
        $desconto      = (float) ($pedido->valor_desconto ?? 0);
        $saldoUsado    = (float) ($pedido->valor_saldo_utilizado ?? 0);
        
        // No modelo contábil completo, o total do pedido não diminui.
        // O valor_total do pedido já é o valor original bruto total (itens + frete - desconto).
        $totalBruto    = (float) $pedido->valor_total;
        $valorPagar    = max(0, $totalBruto - $saldoUsado);
        $valorDevolvido = (float) $itens->where('status_item', 'devolvido')->sum('valor_total');

        // Saldo disponível na carteira do cliente
        $saldoCarteira = (float) (DB::table('conta_corrente')
            ->where('user_id', $pedido->user_id)
            ->orderByDesc('id')
            ->value('saldo_atual') ?? 0);

        // Calcular quanto já foi pago de fato para este pedido
        $lancamento = DB::table('lancamentos')->where('referencia_tipo', 'pedido')->where('referencia_id', $pedido->id)->first();
        $jaPago = $lancamento ? (float)DB::table('movimentacoes')->where('lancamento_id', $lancamento->id)->sum('valor_pago') : 0;
        $valorRestante = max(0, $valorPagar - $jaPago);
    @endphp

    {{-- Cabeçalho --}}
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap">
            <a href="{{ route('admin.pedido.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 text-xs sm:text-sm">
                <i class="fas fa-arrow-left mr-1.5"></i> Voltar
            </a>
            <a href="{{ route('admin.pedido.pdf', $pedido->id) }}" target="_blank"
               class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 text-xs sm:text-sm">
                <i class="fas fa-file-pdf mr-1.5"></i> PDF
            </a>
            <a href="{{ route('admin.pedido.edit', $pedido->id) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 text-xs sm:text-sm">
                <i class="fas fa-edit mr-1.5"></i> Editar
            </a>
            <form action="{{ route('admin.pedido.destroy', $pedido->id) }}" method="POST"
                  onsubmit="return confirm('Tem certeza que deseja excluir este pedido?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 text-xs sm:text-sm">
                    <i class="fas fa-trash-alt mr-1.5"></i> Excluir
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Coluna Esquerda: Pagamento --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-calculator text-blue-500"></i> Pagamento
                </h2>

                <div class="space-y-4 text-sm">
                    {{-- Forma --}}
                    <div class="flex justify-between gap-4 items-center">
                        <span class="text-gray-500 font-semibold">Forma</span>
                        <span class="text-gray-800 font-medium capitalize">
                            {{ str_replace('_', ' ', $pedido->forma_pagamento ?? '—') }}
                        </span>
                    </div>

                    {{-- Status --}}
                    <div class="flex justify-between gap-4 items-center">
                        <span class="text-gray-500 font-semibold">Status</span>
                        @php $pg = $pedido->status_pagamento; @endphp
                        @if ($pg === 'aprovado')
                            <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aprovado</span>
                        @elseif ($pg === 'pendente')
                            <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                        @elseif ($pg === 'rejeitado')
                            <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Rejeitado</span>
                        @elseif ($pg === 'estornado')
                            <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Estornado</span>
                        @else
                            <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $pg ?? '—' }}</span>
                        @endif
                    </div>

                    {{-- Qtd. Pagamentos --}}
                    <div class="flex justify-between gap-4 items-center">
                        <span class="text-gray-500 font-semibold">Qtd. Pagamentos</span>
                        <span class="text-gray-800 font-bold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-xs border border-blue-100 flex items-center gap-1">
                            <i class="fas fa-money-check-alt"></i> {{ $pedido->movimentacoes->count() }}
                        </span>
                    </div>

                    {{-- Lista de Pagamentos --}}
                    @if($pedido->movimentacoes->count() > 0)
                    <div class="border-t border-gray-100 pt-3 mt-1 space-y-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2"><i class="fas fa-list-ul mr-1"></i> Lançamentos/Pagamentos</span>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            @php
                                $movs = $pedido->movimentacoes;
                            @endphp
                            @foreach($movs as $mov)
                                <div class="flex justify-between items-center bg-gray-50 hover:bg-gray-100/70 p-2 rounded-xl border border-gray-100/50 transition">
                                    <div>
                                        <span class="text-xs font-bold text-gray-700 capitalize flex items-center gap-1">
                                            @if($mov->forma_pagamento === 'saldo_carteira')
                                                <i class="fas fa-wallet text-blue-500 text-[10px]"></i>
                                            @elseif($mov->forma_pagamento === 'pix')
                                                <i class="fab fa-pix text-green-500 text-[10px]"></i>
                                            @else
                                                <i class="fas fa-coins text-gray-400 text-[10px]"></i>
                                            @endif
                                            {{ str_replace('_', ' ', $mov->forma_pagamento) }}
                                        </span>
                                        <span class="text-[9px] text-gray-400 block mt-0.5">{{ $mov->data_pagamento instanceof \Carbon\Carbon ? $mov->data_pagamento->format('d/m/Y') : \Carbon\Carbon::parse($mov->data_pagamento)->format('d/m/Y') }}</span>
                                    </div>
                                    <span class="text-xs font-black text-green-600">R$ {{ number_format($mov->valor_pago, 2, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Resumo Financeiro --}}
                    <div class="border-t border-gray-100 pt-4 mt-2 space-y-3">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                            <i class="fas fa-receipt text-gray-300"></i> Resumo Financeiro
                        </p>

                        <div class="flex justify-between items-center text-gray-600">
                            <span>Subtotal (itens)</span>
                            <span class="font-medium text-gray-800">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between items-center text-gray-600">
                            <span>Frete</span>
                            <span class="font-medium text-gray-800">R$ {{ number_format($frete, 2, ',', '.') }}</span>
                        </div>

                        <div class="flex justify-between items-center text-gray-600">
                            <span class="text-red-400">Desconto</span>
                            <span class="font-medium {{ $desconto > 0 ? 'text-red-500' : 'text-gray-400' }}">
                                {{ $desconto > 0 ? '− ' : '' }}R$ {{ number_format($desconto, 2, ',', '.') }}
                            </span>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-2"></div>

                        {{-- VALOR TOTAL --}}
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex justify-between items-center">
                            <span class="text-xs font-bold text-green-600 uppercase tracking-widest">Valor Total</span>
                            <p class="text-2xl font-extrabold text-green-700">R$ {{ number_format($totalBruto, 2, ',', '.') }}</p>
                        </div>

                        @if($valorDevolvido > 0)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex justify-between items-center mt-2">
                            <span class="text-xs font-bold text-amber-700 uppercase tracking-widest">Itens Devolvidos</span>
                            <p class="text-lg font-bold text-amber-800">R$ {{ number_format($valorDevolvido, 2, ',', '.') }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Entrega --}}
            <div class="bg-white shadow-lg rounded-lg p-6" x-data="melhorEnvio()">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-shipping-fast text-blue-500"></i> Entrega
                    </span>
                    <div class="flex items-center gap-2">
                        @if($pedido->codigo_rastreamento || $pedido->status_pedido !== 'pendente')
                        <form action="{{ route('admin.pedido.sincronizarMelhorEnvio', $pedido->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition duration-200">
                                <i class="fas fa-sync-alt mr-1"></i> Sincronizar
                            </button>
                        </form>
                        @endif
                        <button @click="openModal = true" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition duration-200">
                            <i class="fas fa-truck-loading mr-1"></i> Gerar Etiqueta
                        </button>
                    </div>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
                    <div class="space-y-3">
                        <div>
                            <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">CEP</span>
                            <span class="text-gray-800 font-medium">{{ $pedido->cep_entrega ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Cidade / UF</span>
                            <span class="text-gray-800 font-medium">{{ $pedido->cidade_entrega ?? '—' }} / {{ $pedido->estado_entrega ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Endereço</span>
                            <span class="text-gray-800 font-medium">{{ $pedido->endereco_entrega ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Frete Pago</span>
                            <span class="text-green-600 font-bold">{{ $pedido->valor_frete_real ? 'R$ ' . number_format($pedido->valor_frete_real, 2, ',', '.') : '—' }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Rastreio</span>
                                <span class="text-gray-800 font-medium">{{ $pedido->codigo_rastreamento ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Envio</span>
                                <span class="text-gray-800 font-medium">{{ !empty($pedido->data_envio) ? \Carbon\Carbon::parse($pedido->data_envio)->format('d/m/Y') : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Prev. Entrega</span>
                                <span class="text-gray-800 font-medium">{{ !empty($pedido->data_entrega_prevista) ? \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('d/m/Y') : '—' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 font-bold uppercase text-[10px] tracking-widest block mb-1">Entregue em</span>
                                <span class="text-gray-800 font-medium">{{ !empty($pedido->data_entrega_realizada) ? \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('d/m/Y') : '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline de Rastreamento --}}
                <div class="border-t border-gray-200 mt-6 pt-6">
                    <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-route text-blue-500 mr-2"></i> Linha do Tempo de Rastreamento
                    </h4>
                    
                    @php
                        $rastreamentos = DB::table('pedido_rastreamentos')
                            ->where('pedido_id', $pedido->id)
                            ->orderBy('data_hora', 'desc')
                            ->get();
                    @endphp

                    @if($rastreamentos->count() > 0)
                        <div class="relative pl-8 border-l-2 border-gray-200 space-y-6">
                            @foreach($rastreamentos as $index => $rastreio)
                                @php
                                    $isUltimo = ($index === 0);
                                    $icon = 'fas fa-check';
                                    $color = 'text-gray-400';
                                    $bgClass = 'bg-gray-100';
                                    
                                    if ($rastreio->status === 'Entregue') {
                                        $icon = 'fas fa-box-open';
                                        $color = 'text-green-500';
                                        $bgClass = 'bg-green-100';
                                    } elseif ($rastreio->status === 'Saiu para entrega') {
                                        $icon = 'fas fa-truck-fast';
                                        $color = 'text-blue-500';
                                        $bgClass = 'bg-blue-100';
                                    } elseif ($rastreio->status === 'Em trânsito') {
                                        $icon = 'fas fa-truck';
                                        $color = 'text-blue-500';
                                        $bgClass = 'bg-blue-100';
                                    } elseif ($rastreio->status === 'Postado') {
                                        $icon = 'fas fa-box';
                                        $color = 'text-orange-500';
                                        $bgClass = 'bg-orange-100';
                                    }
                                @endphp
                                <div class="relative">
                                    <div class="absolute -left-[44px] mt-1 h-6 w-6 rounded-full {{ $bgClass }} flex items-center justify-center border-2 border-white shadow-sm">
                                        <i class="{{ $icon }} text-[10px] {{ $color }}"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold {{ $isUltimo ? 'text-gray-900' : 'text-gray-600' }}">{{ $rastreio->status }}</p>
                                        @if($rastreio->descricao)
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $rastreio->descricao }}</p>
                                        @endif
                                        <p class="text-[10px] text-gray-400 mt-1 font-medium">
                                            {{ \Carbon\Carbon::parse($rastreio->data_hora)->format('d/m/Y \à\s H:i') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-gray-50 rounded p-4 text-center border border-gray-100">
                            <i class="fas fa-truck-loading text-gray-400 text-lg mb-2 block"></i>
                            <p class="text-xs text-gray-500">Nenhuma atualização de rastreamento disponível ainda.</p>
                        </div>
                    @endif
                </div>

                {{-- Modal Melhor Envio --}}
                <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="openModal" class="fixed inset-0 transition-opacity" aria-hidden="true" @click="openModal = false">
                            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                        </div>
                        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                        <div x-show="openModal" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 relative">
                                <button type="button" @click="openModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                                    <i class="fas fa-times"></i>
                                </button>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 flex justify-between items-center" id="modal-title">
                                    <span><i class="fas fa-box text-blue-500 mr-2"></i> Cálculo e Etiqueta</span>
                                    <span class="text-sm font-semibold bg-gray-100 text-gray-800 px-3 py-1 rounded-full shadow-inner" x-show="balance !== null">
                                        <i class="fas fa-wallet text-green-500 mr-1"></i> 
                                        R$ <span x-text="balance.toFixed(2).replace('.', ',')"></span>
                                    </span>
                                </h3>

                                <div x-show="error" class="mb-4 bg-red-50 text-red-600 p-3 rounded text-sm border border-red-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i> <span x-text="error"></span>
                                </div>
                                
                                <div x-show="successMsg" class="mb-4 bg-green-50 text-green-600 p-3 rounded text-sm border border-green-200">
                                    <i class="fas fa-check-circle mr-1"></i> <span x-text="successMsg"></span>
                                </div>
                                
                                <div x-show="labelUrl" class="mb-4 text-center">
                                    <a :href="labelUrl" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded shadow inline-flex items-center">
                                        <i class="fas fa-print mr-2"></i> Imprimir Etiqueta
                                    </a>
                                </div>

                                <div class="space-y-4 mb-4">
                                    <div class="flex justify-between items-center border-b pb-2">
                                        <span class="text-sm font-semibold text-gray-700">Volumes do Envio</span>
                                        <button type="button" @click="addVolume()" class="text-blue-600 hover:text-blue-700 text-xs font-semibold flex items-center gap-1">
                                            <i class="fas fa-plus text-[10px]"></i> Adicionar Volume
                                        </button>
                                    </div>
                                    
                                    <div class="space-y-4 max-h-60 overflow-y-auto pr-1">
                                        <template x-for="(vol, idx) in volumes" :key="idx">
                                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 relative">
                                                <button type="button" @click="removeVolume(idx)" x-show="volumes.length > 1" class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-xs">
                                                    <i class="fas fa-trash-alt text-xs"></i>
                                                </button>
                                                <p class="text-xs font-bold text-gray-500 mb-2" x-text="'Volume ' + (idx + 1)"></p>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 mb-1">Peso (kg)</label>
                                                        <input type="text" x-model="vol.weight" placeholder="0,00" class="w-full border-gray-300 rounded-md shadow-sm text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 mb-1">Largura (cm)</label>
                                                        <input type="number" x-model="vol.width" placeholder="cm" class="w-full border-gray-300 rounded-md shadow-sm text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 mb-1">Altura (cm)</label>
                                                        <input type="number" x-model="vol.height" placeholder="cm" class="w-full border-gray-300 rounded-md shadow-sm text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-semibold text-gray-600 mb-1">Compr. (cm)</label>
                                                        <input type="number" x-model="vol.length" placeholder="cm" class="w-full border-gray-300 rounded-md shadow-sm text-xs py-1 px-2 focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <button @click="calculate" :disabled="calculating" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium py-2 px-4 rounded shadow-sm disabled:opacity-50">
                                        <i class="fas fa-calculator mr-1"></i> <span x-text="calculating ? 'Calculando...' : 'Calcular Opções'"></span>
                                    </button>
                                </div>

                                <div x-show="options.length > 0" class="mt-6 border-t pt-4">
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Opções Disponíveis:</h4>
                                    <div class="space-y-3 max-h-60 overflow-y-auto">
                                        <template x-for="opt in options" :key="opt.id">
                                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                                <div class="flex items-center gap-3">
                                                    <img x-show="opt.company && opt.company.picture" :src="opt.company.picture" class="h-8 w-8 object-contain">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-800" x-text="opt.name"></p>
                                                        <p class="text-xs text-gray-500" x-text="'Prazo: ' + opt.delivery_time + ' dias úteis'"></p>
                                                    </div>
                                                </div>
                                                <div class="text-right flex flex-col items-end gap-2">
                                                    <span class="text-sm font-bold text-gray-900" x-text="'R$ ' + parseFloat(opt.price).toFixed(2).replace('.', ',')"></span>
                                                    <button @click="buyLabel(opt.id)" :disabled="loading" class="bg-green-600 hover:bg-green-700 text-white text-[10px] uppercase font-bold py-1 px-2 rounded disabled:opacity-50">
                                                        Comprar
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coluna Direita: Resumo + Itens --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Card Resumo (Movido para cima dos itens) --}}
            <div class="bg-white shadow-lg rounded-lg p-6 border-l-4 border-blue-500">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i> Pedido
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4 text-sm">
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">ID</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->id }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Número</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->numero_pedido }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Data</span>
                        <span class="text-gray-800 font-medium">{{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Status Pedido</span>
                        @php $st = $pedido->status_pedido; @endphp
                        <span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded text-xs font-semibold capitalize">{{ $st }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2 md:col-span-1">
                        <span class="text-gray-500">Cliente</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->user->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">WhatsApp</span>
                        <span class="text-gray-800 font-medium">{{ $pedido->user->whatsapp ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">E-mail</span>
                        <span class="text-gray-600 text-xs">{{ $pedido->user->email ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Origem</span>
                        <span class="text-gray-800 font-medium capitalize">{{ $pedido->origem_pedido ?? 'Site' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Criado em</span>
                        <span class="text-gray-800 font-medium">{{ !empty($pedido->created_at) ? $pedido->created_at->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-gray-50 pb-2">
                        <span class="text-gray-500">Atualizado em</span>
                        <span class="text-gray-800 font-medium">{{ !empty($pedido->updated_at) ? $pedido->updated_at->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Itens do Pedido --}}
            <div class="bg-white shadow-lg rounded-lg pt-6 pb-4 px-4 sm:px-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-boxes text-blue-500"></i> Itens do Pedido
                </h2>

                @if($itens->count() > 0)
                    <div class="overflow-x-auto -mx-4 sm:-mx-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-2 sm:px-4 py-3 text-left">Produto</th>
                                    <th class="px-2 sm:px-4 py-3 text-left">Detalhes</th>
                                    <th class="px-2 sm:px-4 py-3 text-right font-bold">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                @foreach($itens as $item)
                                    @php
                                        $img    = $item->image ?? null;
                                        $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50 {{ $item->status_item === 'devolvido' ? 'bg-gray-50/50 text-gray-400' : '' }}">
                                        <td class="px-2 sm:px-4 py-4">
                                            <div class="flex items-center">
                                                @if($imgUrl)
                                                    <img class="h-8 w-8 rounded-md object-cover mr-2 sm:mr-3 border border-gray-100 shadow-sm {{ $item->status_item === 'devolvido' ? 'opacity-50' : '' }}" src="{{ $imgUrl }}" alt="">
                                                @else
                                                    <div class="h-8 w-8 rounded-md bg-gray-100 flex items-center justify-center mr-2 sm:mr-3 text-gray-400">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                @endif
                                                <div class="max-w-[80px] sm:max-w-xs overflow-hidden">
                                                    <div class="text-sm font-semibold text-gray-900 truncate {{ $item->status_item === 'devolvido' ? 'line-through text-gray-400' : '' }}">{{ $item->nome_do_produto }}</div>
                                                    <div class="text-xs text-gray-500 truncate">
                                                        Cód: {{ $item->codigo ?? 'N/A' }}
                                                        @if($item->status_item === 'devolvido')
                                                            <span class="text-red-500 font-bold ml-1">(Devolvido)</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-2 sm:px-4 py-4 text-xs text-gray-500 {{ $item->status_item === 'devolvido' ? 'line-through' : '' }}">
                                            {{ $item->marca }} • {{ $item->estado }} • {{ $item->cor }} • Tam: {{ $item->tamanho }}
                                        </td>
                                        <td class="px-2 sm:px-4 py-4 whitespace-nowrap text-right text-gray-900 font-bold {{ $item->status_item === 'devolvido' ? 'line-through text-gray-400 font-normal' : '' }}">
                                            R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-2 sm:px-4 py-3 text-right text-sm font-semibold text-gray-600">Subtotal dos Itens:</td>
                                    <td class="px-2 sm:px-4 py-3 text-right text-sm font-extrabold text-gray-900">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 italic">Nenhum item vinculado a este pedido.</p>
                    </div>
                @endif
            </div>

            {{-- Observações --}}
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-comment-alt text-blue-500"></i> Observações
                </h2>
                <div class="bg-gray-50 rounded-lg p-4 text-gray-700 text-sm border border-gray-100 italic min-h-[80px]">
                    {!! nl2br(e($pedido->observacoes ?? 'Nenhuma observação informada.')) !!}
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyPaymentLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('Link de pagamento copiado com sucesso!');
            }).catch(err => {
                console.error('Erro ao copiar link: ', err);
            });
        }

        function melhorEnvio() {
            return {
                openModal: false,
                loading: false,
                calculating: false,
                volumes: [
                    { weight: '0,50', width: '', height: '', length: '' }
                ],
                options: [],
                error: null,
                successMsg: null,
                labelUrl: null,
                balance: null,

                init() {
                    this.$watch('openModal', value => {
                        if (value && this.balance === null) {
                            this.fetchBalance();
                        }
                    });
                },

                addVolume() {
                    this.volumes.push({ weight: '', width: '', height: '', length: '' });
                },

                removeVolume(index) {
                    if (this.volumes.length > 1) {
                        this.volumes.splice(index, 1);
                    }
                },

                async fetchBalance() {
                    try {
                        const res = await fetch(`{{ route('admin.pedido.saldoMelhorEnvio') }}`);
                        const data = await res.json();
                        if (res.ok && data.saldo !== undefined) {
                            this.balance = parseFloat(data.saldo);
                        }
                    } catch (err) {
                        console.error('Erro ao buscar saldo', err);
                    }
                },

                async calculate() {
                    // Validar todos os volumes
                    for (let i = 0; i < this.volumes.length; i++) {
                        const vol = this.volumes[i];
                        const normalizedWeight = String(vol.weight).replace(',', '.');
                        if (!normalizedWeight || !vol.weight || !vol.width || !vol.height || !vol.length) {
                            this.error = 'Preencha todas as medidas e o peso para todos os volumes.';
                            return;
                        }
                    }

                    this.calculating = true;
                    this.error = null;
                    this.options = [];

                    const formattedVolumes = this.volumes.map(vol => ({
                        weight: parseFloat(String(vol.weight).replace(',', '.')),
                        width: parseInt(vol.width),
                        height: parseInt(vol.height),
                        length: parseInt(vol.length)
                    }));
                    
                    try {
                        const res = await fetch(`{{ route('admin.pedido.freteOpcoes', $pedido->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                volumes: formattedVolumes
                            })
                        });

                        const data = await res.json();
                        if (!res.ok) throw new Error(data.error || 'Erro ao calcular frete');
                        
                        this.options = data;
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.calculating = false;
                    }
                },

                async buyLabel(serviceId) {
                    if (!confirm('Deseja realmente gerar a etiqueta? Isso irá usar o saldo da carteira.')) return;
                    
                    this.loading = true;
                    this.error = null;

                    const formattedVolumes = this.volumes.map(vol => ({
                        weight: parseFloat(String(vol.weight).replace(',', '.')),
                        width: parseInt(vol.width),
                        height: parseInt(vol.height),
                        length: parseInt(vol.length)
                    }));

                    try {
                        const res = await fetch(`{{ route('admin.pedido.gerarEtiqueta', $pedido->id) }}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                volumes: formattedVolumes
                            })
                        });

                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message || 'Erro ao gerar etiqueta');
                        
                        this.successMsg = data.message;
                        this.labelUrl = data.url;
                        this.options = [];
                        
                    } catch (err) {
                        this.error = err.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }

    </script>
@endsection