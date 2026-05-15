@extends('layouts.app')

@section('title', 'Pedido #' . ($pedido->numero_pedido ?? $pedido->id))
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

        // Subtotal real: soma dos itens
        $subtotal      = (float) $itens->sum('valor_total');
        $frete         = (float) ($pedido->valor_frete ?? 0);
        $desconto      = (float) ($pedido->valor_desconto ?? 0);
        $saldoUsado    = (float) ($pedido->valor_saldo_utilizado ?? 0);
        $totalBruto    = max(0, $subtotal + $frete - $desconto);
        $valorPagar    = max(0, $totalBruto - $saldoUsado);

        // Saldo disponível na carteira do cliente
        $saldoCarteira = (float) (DB::table('conta_corrente')
            ->where('user_id', $pedido->user_id)
            ->orderByDesc('id')
            ->value('saldo_atual') ?? 0);
    @endphp

    {{-- Cabeçalho --}}
    <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Criado em: {{ !empty($pedido->created_at) ? $pedido->created_at->format('d/m/Y H:i') : '—' }}
                • Atualizado em: {{ !empty($pedido->updated_at) ? $pedido->updated_at->format('d/m/Y H:i') : '—' }}
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.pedido.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>
            <a href="{{ route('admin.pedido.pdf', $pedido->id) }}" target="_blank"
               class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-file-pdf mr-2"></i> Gerar PDF
            </a>
            <a href="{{ route('admin.pedido.edit', $pedido->id) }}"
               class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-edit mr-2"></i> Editar
            </a>
            @if(($pedido->status_pagamento ?? '') !== 'aprovado')
            <button onclick="copyPaymentLink('{{ $pedido->getPaymentUrl() }}')"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-link mr-2"></i> Copiar Link
            </button>
            @endif
            <form action="{{ route('admin.pedido.destroy', $pedido->id) }}" method="POST"
                  onsubmit="return confirm('Tem certeza que deseja excluir este pedido?');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                    <i class="fas fa-trash-alt mr-2"></i> Excluir
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

                        <div class="flex justify-between items-center font-semibold text-gray-700 text-xs uppercase">
                            <span>Total Bruto</span>
                            <span>R$ {{ number_format($totalBruto, 2, ',', '.') }}</span>
                        </div>

                        {{-- Carteira --}}
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 space-y-2">
                            <div class="flex justify-between items-center text-blue-700 text-xs font-bold uppercase tracking-wide">
                                <span><i class="fas fa-wallet mr-1"></i> Carteira</span>
                                <span class="text-blue-600 font-bold">R$ {{ number_format($saldoCarteira, 2, ',', '.') }}</span>
                            </div>
                            @if($saldoUsado > 0)
                            <div class="flex justify-between items-center text-blue-600 text-xs font-semibold">
                                <span>Saldo utilizado</span>
                                <span>− R$ {{ number_format($saldoUsado, 2, ',', '.') }}</span>
                            </div>
                            @endif
                        </div>

                        {{-- VALOR A PAGAR --}}
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex justify-between items-center">
                            <span class="text-xs font-bold text-green-600 uppercase tracking-widest">A Pagar</span>
                            <p class="text-2xl font-extrabold text-green-700">R$ {{ number_format($valorPagar, 2, ',', '.') }}</p>
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
                    <i class="fas fa-info-circle text-blue-500"></i> Resumo do Pedido
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
                </div>
            </div>

            {{-- Itens do Pedido --}}
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-boxes text-blue-500"></i> Itens do Pedido
                </h2>

                @if($itens->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-4 py-3 text-left">Produto</th>
                                    <th class="px-4 py-3 text-left">Detalhes</th>
                                    <th class="px-4 py-3 text-center">Qtde</th>
                                    <th class="px-4 py-3 text-right">Preço Unit.</th>
                                    <th class="px-4 py-3 text-right font-bold">Total</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                                @foreach($itens as $item)
                                    @php
                                        $img    = $item->image ?? null;
                                        $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                                        $si     = $item->status_item;
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                @if($imgUrl)
                                                    <img class="h-10 w-10 rounded-md object-cover mr-3 border border-gray-100 shadow-sm" src="{{ $imgUrl }}" alt="">
                                                @else
                                                    <div class="h-10 w-10 rounded-md bg-gray-100 flex items-center justify-center mr-3 text-gray-400">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                @endif
                                                <div class="max-w-xs overflow-hidden">
                                                    <div class="text-sm font-semibold text-gray-900 truncate">{{ $item->nome_do_produto }}</div>
                                                    <div class="text-xs text-gray-500">{{ $item->codigo ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-xs text-gray-600">
                                            {{ $item->marca }} • {{ $item->estado }} • {{ $item->cor }} • Tam: {{ $item->tamanho }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center text-gray-700">
                                            {{ $item->quantidade }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-gray-700">
                                            R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-gray-900 font-bold">
                                            R$ {{ number_format($item->valor_total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            @if($si === 'ativo')
                                                <span class="bg-green-100 text-green-700 py-0.5 px-2 rounded-full text-[10px] font-bold uppercase">Ativo</span>
                                            @elseif($si === 'cancelado')
                                                <span class="bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-[10px] font-bold uppercase">Cancelado</span>
                                            @elseif($si === 'devolvido')
                                                <span class="bg-orange-100 text-orange-700 py-0.5 px-2 rounded-full text-[10px] font-bold uppercase">Devolvido</span>
                                            @else
                                                <span class="bg-gray-100 text-gray-700 py-0.5 px-2 rounded-full text-[10px] font-bold uppercase">{{ $si }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Subtotal dos Itens:</td>
                                    <td class="px-4 py-3 text-right text-sm font-extrabold text-gray-900">R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
                                    <td></td>
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

            {{-- Entrega --}}
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-shipping-fast text-blue-500"></i> Entrega
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
    </script>
@endsection