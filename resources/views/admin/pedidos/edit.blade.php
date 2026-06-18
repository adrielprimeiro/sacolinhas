@extends('layouts.app')

@section('title', 'Editar #' . $pedido->id)
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Editar #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pedido.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1.5 px-3 rounded-md shadow-sm transition duration-300 text-xs sm:text-sm">
                <i class="fas fa-arrow-left mr-1.5"></i> Voltar
            </a>
        </div>
    </div>

    @php
        $subtotal      = (float) DB::table('items_pedido')->where('pedido_id', $pedido->id)->where('status_item', 'ativo')->sum('valor_total');
        $saldoCarteira = (float) (DB::table('conta_corrente')->where('user_id', $pedido->user_id)->orderByDesc('id')->value('saldo_atual') ?? 0);
        $isPaid        = $pedido->status_pagamento === 'aprovado';
    @endphp

    <form action="{{ route('admin.pedido.update', $pedido->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna 1: Pagamento --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calculator text-blue-500"></i> Pagamento
                    </h2>

                    <div class="space-y-4 text-sm">
                        {{-- Forma de Pagamento --}}
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Forma de Pagamento</label>
                            @php $fp = old('forma_pagamento', $pedido->forma_pagamento); @endphp
                            <select name="forma_pagamento"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('forma_pagamento') border-red-500 @enderror">
                                <option value="" {{ empty($fp) ? 'selected' : '' }}>— Selecione —</option>
                                <option value="pix" {{ $fp === 'pix' ? 'selected' : '' }}>Pix</option>
                                <option value="cartao_credito" {{ $fp === 'cartao_credito' ? 'selected' : '' }}>Cartão de crédito</option>
                                <option value="cartao_debito" {{ $fp === 'cartao_debito' ? 'selected' : '' }}>Cartão de débito</option>
                                <option value="boleto" {{ $fp === 'boleto' ? 'selected' : '' }}>Boleto</option>
                                <option value="dinheiro" {{ $fp === 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                                <option value="transferencia" {{ $fp === 'transferencia' ? 'selected' : '' }}>Transferência</option>
                                <option value="saldo_carteira" {{ $fp === 'saldo_carteira' ? 'selected' : '' }}>Saldo Carteira</option>
                            </select>
                        </div>

                        {{-- Status do Pagamento --}}
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Status do Pagamento</label>
                            @php $pg = old('status_pagamento', $pedido->status_pagamento); @endphp
                            <select name="status_pagamento"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="pendente" {{ $pg === 'pendente' ? 'selected' : '' }}>Pendente</option>
                                <option value="aprovado" {{ $pg === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
                                <option value="rejeitado" {{ $pg === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                                <option value="estornado" {{ $pg === 'estornado' ? 'selected' : '' }}>Estornado</option>
                            </select>
                        </div>

                        {{-- Cupom --}}
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Cupom de Desconto</label>
                            <input type="text" name="cupom_desconto"
                                   value="{{ old('cupom_desconto', $pedido->cupom_desconto) }}"
                                   placeholder="Código do cupom"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div class="border-t border-gray-100 pt-4 mt-2 space-y-3">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                                <i class="fas fa-receipt text-gray-300"></i> Resumo Financeiro
                            </p>

                            {{-- Subtotal --}}
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Subtotal (itens)</span>
                                <span id="exib_subtotal" class="font-medium">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                            </div>

                            {{-- Frete --}}
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Frete</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400 text-xs">R$</span>
                                    <input type="number" step="0.01" name="valor_frete" id="inp_frete"
                                           value="{{ old('valor_frete', $pedido->valor_frete ?? 0) }}"
                                           oninput="recalcular()"
                                           {{ $isPaid ? 'readonly' : '' }}
                                           class="w-24 border border-gray-300 rounded-md py-1 px-2 text-right text-sm focus:outline-none focus:ring-blue-400 focus:border-blue-400 {{ $isPaid ? 'bg-gray-100 cursor-not-allowed' : '' }}">
                                </div>
                            </div>

                            {{-- Custo Real do Frete --}}
                            <div class="flex justify-between items-center text-gray-600">
                                <span>Custo Real <span class="text-[10px] text-gray-400 block">(Interno)</span></span>
                                <span id="exib_frete_real" class="font-medium">R$ {{ number_format($pedido->valor_frete_real ?? 0, 2, ',', '.') }}</span>
                            </div>

                            {{-- Desconto --}}
                            <div class="flex justify-between items-center text-gray-600">
                                <span class="text-red-400">Desconto</span>
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-400 text-xs">- R$</span>
                                    <input type="number" step="0.01" name="valor_desconto" id="inp_desconto"
                                           value="{{ old('valor_desconto', $pedido->valor_desconto ?? 0) }}"
                                           oninput="recalcular()"
                                           {{ $isPaid ? 'readonly' : '' }}
                                           class="w-24 border border-gray-300 rounded-md py-1 px-2 text-right text-sm focus:outline-none focus:ring-red-400 focus:border-red-400 {{ $isPaid ? 'bg-gray-100 cursor-not-allowed' : '' }}">
                                </div>
                            </div>

                            <div class="border-t border-dashed border-gray-200 pt-2"></div>

                            {{-- VALOR TOTAL --}}
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex justify-between items-center mt-2">
                                <span class="text-xs font-bold text-green-600 uppercase">Valor Total</span>
                                <p id="exib_total_bruto" class="text-2xl font-extrabold text-green-700">
                                    R$ {{ number_format(max(0, $subtotal + ($pedido->valor_frete ?? 0) - ($pedido->valor_desconto ?? 0)), 2, ',', '.') }}
                                </p>
                            </div>

                            <input type="hidden" name="valor_saldo_utilizado" id="inp_saldo_utilizado" value="{{ old('valor_saldo_utilizado', $saldoJaAlocado) }}">

                            <input type="hidden" name="valor_total" id="inp_valor_total" value="{{ old('valor_total', $pedido->valor_total) }}">
                        </div>
                    </div>
                </div>

                {{-- Entrega --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-shipping-fast text-blue-500"></i> Entrega
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">CEP</label>
                            <input type="text" name="cep_entrega" value="{{ old('cep_entrega', $pedido->cep_entrega) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Cidade</label>
                            <input type="text" name="cidade_entrega" value="{{ old('cidade_entrega', $pedido->cidade_entrega) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">UF</label>
                            <input type="text" name="estado_entrega" value="{{ old('estado_entrega', $pedido->estado_entrega) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-gray-500 mb-1 font-semibold">Endereço Completo</label>
                            <input type="text" name="endereco_entrega" value="{{ old('endereco_entrega', $pedido->endereco_entrega) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Rastreio</label>
                            <input type="text" name="codigo_rastreamento" value="{{ old('codigo_rastreamento', $pedido->codigo_rastreamento) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Data Envio</label>
                            <input type="datetime-local" name="data_envio" value="{{ old('data_envio', !empty($pedido->data_envio) ? \Carbon\Carbon::parse($pedido->data_envio)->format('Y-m-d\TH:i') : null) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Prev. Entrega</label>
                            <input type="date" name="data_entrega_prevista" value="{{ old('data_entrega_prevista', !empty($pedido->data_entrega_prevista) ? \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('Y-m-d') : null) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Entregue em</label>
                            <input type="datetime-local" name="data_entrega_realizada" value="{{ old('data_entrega_realizada', !empty($pedido->data_entrega_realizada) ? \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('Y-m-d\TH:i') : null) }}" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Frete Pago</label>
                            <div class="flex shadow-sm rounded-md">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-xs">R$</span>
                                <input type="number" step="0.01" name="valor_frete_real" id="inp_frete_real" 
                                       value="{{ old('valor_frete_real', $pedido->valor_frete_real ?? 0) }}" 
                                       oninput="recalcular()"
                                       class="w-full border border-gray-300 rounded-r-md py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Coluna Direita: Resumo + Itens --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Card Resumo (ID, Cliente, etc.) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-blue-500"></i> Pedido
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">Número do Pedido</label>
                                <input type="text" name="numero_pedido" value="{{ old('numero_pedido', $pedido->numero_pedido) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">Data do Pedido</label>
                                <input type="datetime-local" name="data_pedido" 
                                       value="{{ old('data_pedido', !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('Y-m-d\TH:i') : null) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">Status do Pedido</label>
                                @php $sp = old('status_pedido', $pedido->status_pedido); @endphp
                                <select name="status_pedido" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="pendente" {{ $sp === 'pendente' ? 'selected' : '' }}>Pendente</option>
                                    <option value="confirmado" {{ $sp === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
                                    <option value="processando" {{ $sp === 'processando' ? 'selected' : '' }}>Processando</option>
                                    <option value="embalado" {{ $sp === 'embalado' ? 'selected' : '' }}>Embalado</option>
                                    <option value="pago" {{ $sp === 'pago' ? 'selected' : '' }}>Pago</option>
                                    <option value="enviado" {{ $sp === 'enviado' ? 'selected' : '' }}>Enviado</option>
                                    <option value="entregue" {{ $sp === 'entregue' ? 'selected' : '' }}>Entregue</option>
                                    <option value="concluido" {{ $sp === 'concluido' ? 'selected' : '' }}>Concluído</option>
                                    <option value="cancelado" {{ $sp === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">ID do Cliente</label>
                                <input type="number" name="user_id" value="{{ old('user_id', $pedido->user_id) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <p class="text-[10px] text-gray-400 mt-1">Atual: {{ $pedido->user->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">Origem do Pedido</label>
                                @php $op = old('origem_pedido', $pedido->origem_pedido); @endphp
                                <select name="origem_pedido" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="site" {{ $op === 'site' ? 'selected' : '' }}>Site</option>
                                    <option value="live" {{ $op === 'live' ? 'selected' : '' }}>Live</option>
                                    <option value="whatsapp" {{ $op === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                    <option value="instagram" {{ $op === 'instagram' ? 'selected' : '' }}>Instagram</option>
                                    <option value="admin" {{ $op === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="portal" {{ $op === 'portal' ? 'selected' : '' }}>Portal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Itens do Pedido --}}
                <div class="bg-white shadow-lg rounded-lg pt-6 pb-4 px-4 sm:px-6">
                    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-boxes text-blue-500"></i> Itens
                        </h2>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button id="btnDevolucao"
                                    type="submit"
                                    form="devolucaoForm"
                                    disabled
                                    class="bg-indigo-600 text-white font-bold py-1.5 px-3 rounded-lg text-xs sm:text-sm shadow-sm transition duration-300 opacity-50 cursor-not-allowed">
                                <i class="fas fa-undo mr-1"></i> Devolução
                            </button>
                            @if(!$isPaid)
                                <button type="button" onclick="abrirModalAdicionarItem()"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold py-1.5 px-3 rounded-lg shadow transition duration-200">
                                    <i class="fas fa-plus mr-1"></i> Item
                                </button>
                            @endif
                        </div>
                    </div>

                    @php
                        $itens = DB::table('items_pedido as ip')
                            ->join('items as i', 'i.id', '=', 'ip.item_id')
                            ->where('ip.pedido_id', $pedido->id)
                            ->select(['ip.*', 'i.nome_do_produto', 'i.codigo', 'i.marca', 'i.estado', 'i.cor', 'i.tamanho', 'i.image'])
                            ->get();
                    @endphp

                    @if($itens->count() > 0)
                        <div class="overflow-x-auto -mx-4 sm:-mx-6">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-gray-600 text-[10px] uppercase font-bold tracking-wider">
                                    <tr>
                                        <th class="px-2 sm:px-4 py-3 text-left">Produto</th>
                                        <th class="px-2 sm:px-4 py-3 text-right">Total</th>
                                        <th class="px-2 sm:px-4 py-3 text-center">Status</th>
                                        <th class="px-2 sm:px-4 py-3 text-center">Devolver</th>
                                        <th class="px-2 sm:px-4 py-3 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($itens as $item)
                                        <tr class="hover:bg-gray-50 {{ $item->status_item === 'devolvido' ? 'bg-gray-50/50 text-gray-400' : '' }}">
                                            <td class="px-2 sm:px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gray-100 rounded overflow-hidden flex-shrink-0 border border-gray-100 shadow-sm {{ $item->status_item === 'devolvido' ? 'opacity-50' : '' }}">
                                                        @if($item->image)
                                                            <img src="{{ asset('storage/' . ltrim($item->image, '/')) }}" class="w-full h-full object-cover">
                                                        @endif
                                                    </div>
                                                    <div class="max-w-[80px] sm:max-w-xs overflow-hidden">
                                                        <div class="font-bold text-gray-900 leading-tight truncate {{ $item->status_item === 'devolvido' ? 'line-through text-gray-400' : '' }}">{{ $item->nome_do_produto }}</div>
                                                        <div class="text-[10px] text-gray-500 truncate">Cód: {{ $item->codigo }} • {{ $item->marca }} • Tam: {{ $item->tamanho }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-2 sm:px-4 py-3 text-right font-bold {{ $item->status_item === 'devolvido' ? 'line-through text-gray-400 font-normal' : '' }}">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
                                            <td class="px-2 sm:px-4 py-3 text-center">
                                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase
                                                    {{ $item->status_item === 'ativo' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                                    {{ $item->status_item }}
                                                </span>
                                            </td>
                                            <td class="px-2 sm:px-4 py-3 text-center">
                                                <input type="checkbox" name="itens_devolver[]" value="{{ $item->id }}" form="devolucaoForm" class="chkDevolver h-4 w-4 text-indigo-600">
                                            </td>
                                            <td class="px-2 sm:px-4 py-3 text-center">
                                                @if(!$isPaid)
                                                    <button type="button" onclick="confirmarRemocao({{ $item->id }}, '{{ addslashes($item->nome_do_produto) }}')" class="text-red-500 hover:text-red-700">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @else
                                                    <span class="text-gray-300 cursor-not-allowed flex items-center justify-center" title="Pedido Pago (Não é possível alterar itens)">
                                                        <i class="fas fa-lock text-xs"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Observações --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-comment-alt text-blue-500"></i> Observações
                    </h2>
                    <textarea name="observacoes" rows="3" class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 sm:text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">{{ old('observacoes', $pedido->observacoes) }}</textarea>
                </div>

                {{-- Botões de Ação --}}
                <div class="flex justify-end gap-3 pt-4">
                    <a href="{{ route('admin.pedido.show', $pedido->id) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-6 rounded-xl transition">Cancelar</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </form>

    <form id="devolucaoForm" action="{{ route('admin.pedido.devolucao', $pedido->id) }}" method="POST" class="hidden">
        @csrf
    </form>

    {{-- Modais e Scripts (Mantidos) --}}
    @include('admin.pedidos.partials.modais-edit')

    <script>
    const SUBTOTAL_BASE  = {{ (float) $subtotal }};
    const SALDO_ALOCADO  = {{ (float) $saldoJaAlocado }}; // positivo = desconto, negativo = dívida

    function fmt(v) {
        return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function recalcular() {
        const frete    = parseFloat(document.getElementById('inp_frete')?.value)    || 0;
        const desconto = parseFloat(document.getElementById('inp_desconto')?.value) || 0;
        const freteReal = parseFloat(document.getElementById('inp_frete_real')?.value) || 0;

        const totalBruto  = Math.max(0, SUBTOTAL_BASE + frete - desconto);

        const elBruto = document.getElementById('exib_total_bruto');
        const elFreteReal = document.getElementById('exib_frete_real');
        const inpTotal = document.getElementById('inp_valor_total');

        if (elBruto)  elBruto.textContent  = fmt(totalBruto);
        if (elFreteReal) elFreteReal.textContent = fmt(freteReal);
        
        // O valor_total do pedido no banco é o bruto (itens + frete - desconto)
        if (inpTotal) inpTotal.value       = totalBruto.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', recalcular);

    // Devolução script
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btnDevolucao');
        function refresh() {
            const anyChecked = !!document.querySelector('.chkDevolver:checked');
            btn.disabled = !anyChecked;
            btn.className = anyChecked 
                ? 'bg-indigo-600 text-white font-bold py-1.5 px-3 rounded-lg text-xs sm:text-sm shadow-sm transition' 
                : 'bg-indigo-600 text-white font-bold py-1.5 px-3 rounded-lg text-xs sm:text-sm shadow-sm transition opacity-50 cursor-not-allowed';
        }
        document.addEventListener('change', e => { if (e.target.classList.contains('chkDevolver')) refresh(); });
    });
    </script>
@endsection