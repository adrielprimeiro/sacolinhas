@extends('layouts.app')

@section('title', 'Editar Pedido #' . ($pedido->numero_pedido ?? $pedido->id))
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
<div x-data="editarPedido()">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Editar Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Criado em:
                {{ !empty($pedido->created_at) ? $pedido->created_at->format('d/m/Y H:i') : 'â€”' }}
                â€¢ Atualizado em:
                {{ !empty($pedido->updated_at) ? $pedido->updated_at->format('d/m/Y H:i') : 'â€”' }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pedido.index') }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Voltar
            </a>

			<form id="devolucaoForm" action="{{ route('admin.pedido.devolucao', $pedido->id) }}" method="POST">
				@csrf
				<button id="btnDevolucao"
						type="submit"
						disabled
						class="bg-indigo-600 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300 opacity-50 cursor-not-allowed">
					<i class="fas fa-undo mr-2"></i> DevoluÃ§Ã£o
				</button>
			</form>

            <button type="button" onclick="copyPaymentLink('{{ $pedido->getPaymentUrl() }}')"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300 ml-2">
                <i class="fas fa-link mr-2"></i> Copiar Link
            </button>
        </div>
    </div>

    <form action="{{ route('admin.pedido.update', $pedido->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Coluna 1: Resumo (mesmo card do show, mas com inputs) --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Resumo</h2>

                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">ID</span>
                            <span class="text-gray-800 font-medium">{{ $pedido->id }}</span>
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">NÃºmero do Pedido</label>
                            <input type="text"
                                   name="numero_pedido"
                                   value="{{ old('numero_pedido', $pedido->numero_pedido) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('numero_pedido') border-red-500 @enderror">
                            @error('numero_pedido')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Data do pedido</label>
                            <input type="datetime-local"
                                   name="data_pedido"
                                   value="{{ old('data_pedido', !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('Y-m-d\TH:i') : null) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_pedido') border-red-500 @enderror">
                            @error('data_pedido')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Status do pedido</label>
                            @php $sp = old('status_pedido', $pedido->status_pedido); @endphp
                            <select name="status_pedido"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status_pedido') border-red-500 @enderror">
                                <option value="pendente" {{ $sp === 'pendente' ? 'selected' : '' }}>Pendente</option>
                                <option value="confirmado" {{ $sp === 'confirmado' ? 'selected' : '' }}>Confirmado</option>
                                <option value="processando" {{ $sp === 'processando' ? 'selected' : '' }}>Processando</option>
                                <option value="enviado" {{ $sp === 'enviado' ? 'selected' : '' }}>Enviado</option>
                                <option value="entregue" {{ $sp === 'entregue' ? 'selected' : '' }}>Entregue</option>
                                <option value="cancelado" {{ $sp === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            @error('status_pedido')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Origem do pedido</label>
                            @php $op = old('origem_pedido', $pedido->origem_pedido); @endphp
                            <select name="origem_pedido"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('origem_pedido') border-red-500 @enderror">
                                <option value="site" {{ $op === 'site' ? 'selected' : '' }}>Site</option>
                                <option value="live" {{ $op === 'live' ? 'selected' : '' }}>Live</option>
                                <option value="whatsapp" {{ $op === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="instagram" {{ $op === 'instagram' ? 'selected' : '' }}>Instagram</option>
                            </select>
                            @error('origem_pedido')
                              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Vincular Cliente</p>
                            <div>
                                <label class="block text-gray-500 mb-1 font-semibold">User ID (ID do Cliente)</label>
                                <input type="number"
                                       name="user_id"
                                       value="{{ old('user_id', $pedido->user_id) }}"
                                       class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-500 @enderror">
                                @error('user_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-[10px] text-gray-400 mt-1 italic">Atual: {{ $pedido->user->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pagamento</h2>

                    <div class="space-y-4 text-sm">
                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Forma de Pagamento</label>
                            @php $fp = old('forma_pagamento', $pedido->forma_pagamento); @endphp
                            <select name="forma_pagamento"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('forma_pagamento') border-red-500 @enderror">
                                <option value="" {{ empty($fp) ? 'selected' : '' }}>â€” Selecione â€”</option>
                                <option value="pix" {{ $fp === 'pix' ? 'selected' : '' }}>Pix</option>
                                <option value="cartao_credito" {{ $fp === 'cartao_credito' ? 'selected' : '' }}>CartÃ£o de crÃ©dito</option>
                                <option value="cartao_debito" {{ $fp === 'cartao_debito' ? 'selected' : '' }}>CartÃ£o de dÃ©bito</option>
                                <option value="boleto" {{ $fp === 'boleto' ? 'selected' : '' }}>Boleto</option>
                                <option value="dinheiro" {{ $fp === 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
                                <option value="transferencia" {{ $fp === 'transferencia' ? 'selected' : '' }}>TransferÃªncia</option>
                            </select>
                            @error('forma_pagamento')
                              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1 font-semibold">Status do Pagamento</label>
                            @php $pg = old('status_pagamento', $pedido->status_pagamento); @endphp
                            <select name="status_pagamento"
                                    class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('status_pagamento') border-red-500 @enderror">
                                <option value="pendente" {{ $pg === 'pendente' ? 'selected' : '' }}>Pendente</option>
                                <option value="aprovado" {{ $pg === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
                                <option value="rejeitado" {{ $pg === 'rejeitado' ? 'selected' : '' }}>Rejeitado</option>
                                <option value="estornado" {{ $pg === 'estornado' ? 'selected' : '' }}>Estornado</option>
                            </select>
                            @error('status_pagamento')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Valores</p>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-gray-500 mb-1">Frete R$</label>
                                    <input type="number" step="0.01" name="valor_frete"
                                           value="{{ old('valor_frete', $pedido->valor_frete) }}"
                                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-gray-500 mb-1">Desconto R$</label>
                                    <input type="number" step="0.01" name="valor_desconto"
                                           value="{{ old('valor_desconto', $pedido->valor_desconto) }}"
                                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>

                                <div>
                                    <label class="block text-gray-500 mb-1">Valor Total (Calculado)</label>
                                    <input type="number" step="0.01" name="valor_total"
                                           value="{{ old('valor_total', $pedido->valor_total) }}"
                                           readonly
                                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-50 cursor-not-allowed font-bold text-blue-700">
                                </div>

                                <div>
                                    <label class="block text-gray-500 mb-1">Cupom</label>
                                    <input type="text" name="cupom_desconto"
                                           value="{{ old('cupom_desconto', $pedido->cupom_desconto) }}"
                                           class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BotÃµes (no edit, fica mais natural aqui ou no rodapÃ©; deixei no rodapÃ© tambÃ©m) --}}
            </div>

            {{-- Coluna 2-3: Detalhes (mesma estrutura do show: Cliente -> Itens -> Pagamento -> Entrega -> ObservaÃ§Ãµes) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Cliente --}}


                {{-- Itens do Pedido (idÃªntico ao show: mesma query, mesma tabela, mesma posiÃ§Ã£o) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Itens do Pedido</h2>
                        <button type="button" @click="openModalAdicionarItem()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                            <i class="fas fa-plus mr-2"></i> Adicionar Item
                        </button>
                    </div>

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
                    @endphp

                    @if($itens->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produto</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detalhes</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Qtde</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">PreÃ§o Unit.</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
										<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Devolver</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">AÃ§Ã£o</th>
                                    </tr>
                                </thead>

                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($itens as $item)
                                        @php
                                            $img = $item->image ?? null;
                                            $imgUrl = $img ? asset('storage/' . ltrim($img, '/')) : null;
                                        @endphp

                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden flex items-center justify-center flex-shrink-0">
                                                        @if($imgUrl)
                                                            <img src="{{ $imgUrl }}" alt="{{ $item->nome_do_produto ?? 'Produto' }}" class="w-full h-full object-cover">
                                                        @else
                                                            <span class="text-xs text-gray-400">Sem img</span>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-semibold text-gray-900">
                                                            {{ $item->nome_do_produto ?? 'Produto' }}
                                                        </div>
                                                        <div class="text-xs text-gray-500">
                                                            {{ $item->codigo ?? '' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                {{ $item->marca }} â€¢ {{ $item->estado }} â€¢ {{ $item->cor }} â€¢ Tam: {{ $item->tamanho }}
                                            </td>

                                            <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                                {{ $item->quantidade }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-800">
                                                R$ {{ number_format($item->preco_unitario ?? 0, 2, ',', '.') }}
                                            </td>

                                            <td class="px-4 py-3 text-sm font-semibold text-gray-800">
                                                R$ {{ number_format($item->valor_total ?? 0, 2, ',', '.') }}
                                            </td>

                                            <td class="px-4 py-3">
                                                @php $si = $item->status_item; @endphp

                                                @if ($si === 'aprovado')
                                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aprovado</span>
                                                @elseif ($si === 'pendente')
                                                    <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                                                @elseif ($si === 'rejeitado')
                                                    <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Rejeitado</span>
                                                @elseif ($si === 'devolvido')
                                                    <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Devolvido</span>
                                                @else
                                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $si ?? 'â€”' }}</span>
                                                @endif
                                            </td>
																						
											<td class="px-4 py-3">
												@if(($item->status_item ?? null) === 'devolvido')
													<input type="checkbox" disabled class="h-4 w-4 opacity-50 cursor-not-allowed">
												@else
													<input type="checkbox"
														   name="itens_devolver[]"
														   value="{{ $item->id }}"
														   form="devolucaoForm"
														   class="chkDevolver h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
												@endif
											</td>

											<td class="px-4 py-3">
												@if(($item->status_item ?? null) !== 'devolvido')
													<button type="button" onclick="removerItem({{ $item->id }})" class="text-red-600 hover:text-red-800" title="Remover">
														<i class="fas fa-trash"></i>
													</button>
												@endif
											</td>											
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">Nenhum item encontrado para este pedido.</p>
                    @endif
                </div>

                {{-- Pagamento (mesmo card do show, mas com inputs + badge preview) --}}


                {{-- Entrega (mesmo card do show, mas com inputs) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Entrega</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                        <div>
                            <label class="block text-gray-500 mb-1">CEP</label>
                            <input type="text"
                                   name="cep_entrega"
                                   value="{{ old('cep_entrega', $pedido->cep_entrega) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cep_entrega') border-red-500 @enderror">
                            @error('cep_entrega')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Cidade</label>
                            <input type="text"
                                   name="cidade_entrega"
                                   value="{{ old('cidade_entrega', $pedido->cidade_entrega) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cidade_entrega') border-red-500 @enderror">
                            @error('cidade_entrega')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">UF</label>
                            <input type="text"
                                   name="estado_entrega"
                                   value="{{ old('estado_entrega', $pedido->estado_entrega) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('estado_entrega') border-red-500 @enderror">
                            @error('estado_entrega')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-gray-500 mb-1">EndereÃ§o</label>
                            <textarea name="endereco_entrega"
                                      rows="2"
                                      class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('endereco_entrega') border-red-500 @enderror">{{ old('endereco_entrega', $pedido->endereco_entrega) }}</textarea>
                            @error('endereco_entrega')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <label class="block text-gray-500 mb-1">CÃ³digo rastreio</label>
                            <input type="text"
                                   name="codigo_rastreamento"
                                   value="{{ old('codigo_rastreamento', $pedido->codigo_rastreamento) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('codigo_rastreamento') border-red-500 @enderror">
                            @error('codigo_rastreamento')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Data envio</label>
                            <input type="datetime-local"
                                   name="data_envio"
                                   value="{{ old('data_envio', !empty($pedido->data_envio) ? \Carbon\Carbon::parse($pedido->data_envio)->format('Y-m-d\TH:i') : null) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_envio') border-red-500 @enderror">
                            @error('data_envio')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Entrega prevista</label>
                            <input type="date"
                                   name="data_entrega_prevista"
                                   value="{{ old('data_entrega_prevista', !empty($pedido->data_entrega_prevista) ? \Carbon\Carbon::parse($pedido->data_entrega_prevista)->format('Y-m-d') : null) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_entrega_prevista') border-red-500 @enderror">
                            @error('data_entrega_prevista')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Entrega realizada</label>
                            <input type="datetime-local"
                                   name="data_entrega_realizada"
                                   value="{{ old('data_entrega_realizada', !empty($pedido->data_entrega_realizada) ? \Carbon\Carbon::parse($pedido->data_entrega_realizada)->format('Y-m-d\TH:i') : null) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_entrega_realizada') border-red-500 @enderror">
                            @error('data_entrega_realizada')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- ObservaÃ§Ãµes (mesmo card do show, mas com textarea) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">ObservaÃ§Ãµes</h2>

                    <textarea name="observacoes"
                              rows="4"
                              class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('observacoes') border-red-500 @enderror">{{ old('observacoes', $pedido->observacoes) }}</textarea>

                    @error('observacoes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- AÃ§Ãµes --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.pedido.show', $pedido->id) }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                        <i class="fas fa-times-circle mr-2"></i> Cancelar
                    </a>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                        <i class="fas fa-save mr-2"></i> Salvar alteraÃ§Ãµes
                    </button>
                </div>
            </div>
        </div>
    </form>
	@if ($errors->any())
	  <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4 text-sm">
		<div class="font-semibold mb-2">NÃ£o foi possÃ­vel salvar. Verifique:</div>
		<ul class="list-disc pl-5">
		  @foreach ($errors->all() as $error)
			<li>{{ $error }}</li>
		  @endforeach
		</ul>
	  </div>
	@endif

	<script>
	document.addEventListener('DOMContentLoaded', function () {
	  const btn = document.getElementById('btnDevolucao');
	  const form = document.getElementById('devolucaoForm');

	  function refresh() {
		const anyChecked = !!document.querySelector('.chkDevolver:checked');
		if (anyChecked) {
		  btn.disabled = false;
		  btn.classList.remove('opacity-50', 'cursor-not-allowed');
		  btn.classList.add('hover:bg-indigo-700');
		} else {
		  btn.disabled = true;
		  btn.classList.add('opacity-50', 'cursor-not-allowed');
		  btn.classList.remove('hover:bg-indigo-700');
		}
	  }

	  document.addEventListener('change', function (e) {
		if (e.target && e.target.classList.contains('chkDevolver')) refresh();
	  });

	  // SeguranÃ§a: evitar submit sem item marcado
	  form.addEventListener('submit', function (e) {
		const anyChecked = !!document.querySelector('.chkDevolver:checked');
		if (!anyChecked) e.preventDefault();
	  });

	  refresh();
	});

function copyPaymentLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Link de pagamento copiado para a Ã¡rea de transferÃªncia!');
        }).catch(err => {
            const textArea = document.createElement("textarea");
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            alert('Link de pagamento copiado!');
        });
    }

    document.getElementById('btnAdicionarItem')?.addEventListener('click', function() {
        document.getElementById('modalAdicionarItem').classList.remove('hidden');
        document.getElementById('item_search').focus();
    });

    document.getElementById('btnFecharModal')?.addEventListener('click', function() {
        document.getElementById('modalAdicionarItem').classList.add('hidden');
    });

    document.getElementById('btnCancelarItem')?.addEventListener('click', function() {
        document.getElementById('modalAdicionarItem').classList.add('hidden');
    });

    let debounceTimer;
    document.getElementById('item_search')?.addEventListener('input', function() {
        const termo = this.value.trim();
        clearTimeout(debounceTimer);
        if (termo.length < 2) {
            document.getElementById('item_results').innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch('/api/items/search?q=' + encodeURIComponent(termo))
                .then(r => r.json())
                .then(data => {
                    const results = document.getElementById('item_results');
                    if (data.data && data.data.length > 0) {
                        results.innerHTML = data.data.map(item => `
                            <div class="item-result p-3 border-b hover:bg-gray-50 cursor-pointer" data-id="${item.id}" data-nome="${item.nome_do_produto}" data-preco="${item.price || 0}" data-codigo="${item.codigo || ''}">
                                <div class="font-semibold">${item.nome_do_produto}</div>
                                <div class="text-xs text-gray-500">${item.codigo || ''} - R$ ${parseFloat(item.price || 0).toFixed(2).replace('.', ',')}</div>
                            </div>
                        `).join('');
                        results.querySelectorAll('.item-result').forEach(el => {
                            el.addEventListener('click', function() {
                                document.getElementById('item_id').value = this.dataset.id;
                                document.getElementById('item_nome').textContent = this.dataset.nome;
                                document.getElementById('item_preco').value = this.dataset.preco;
                                document.getElementById('item_search').value = this.dataset.nome + ' (' + this.dataset.codigo + ')';
                                document.getElementById('item_results').innerHTML = '';
                            });
                        });
                    } else {
                        results.innerHTML = '<div class="p-3 text-gray-500 text-sm">Nenhum item encontrado</div>';
                    }
                });
        }, 300);
    });

    document.getElementById('formAdicionarItem')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const itemId = document.getElementById('item_id').value;
        const preco = document.getElementById('item_preco').value;
        const quantidade = document.getElementById('item_quantidade').value || 1;
        const pedidoId = {{ $pedido->id }};

        if (!itemId) {
            alert('Selecione um item primeiro');
            return;
        }

        fetch('/admin/pedido/' + pedidoId + '/adicionar-item', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                item_id: itemId,
                preco_unitario: preco,
                quantidade: quantidade
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao adicionar item');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao adicionar item');
        });
    });

    window.removerItem = function(itemId) {
        if (!confirm('Tem certeza que deseja remover este item do pedido?')) return;
        const pedidoId = {{ $pedido->id }};
        fetch('/admin/pedido/' + pedidoId + '/remover-item/' + itemId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao remover item');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao remover item');
        });
    };
	</script>

    <!-- Modal Adicionar Item -->
    <div x-show="modalAddItem" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalAddItem = false"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 overflow-hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Adicionar Item ao Pedido</h3>
                    <button @click="modalAddItem = false" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">CÃ³digo do Item</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="searchCode" @keydown.enter="searchItem()"
                                   placeholder="Digite o cÃ³digo (ex: 00123)"
                                   class="flex-1 rounded-xl border-gray-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                            <button @click="searchItem()" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 rounded-xl transition duration-200 flex items-center gap-2"
                                    :disabled="searching">
                                <template x-if="searching">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </template>
                                <template x-if="!searching">
                                    <i class="fas fa-search"></i>
                                </template>
                                Buscar
                            </button>
                        </div>
                        <p x-show="errorMessage" class="text-red-500 text-sm mt-1" x-text="errorMessage"></p>
                    </div>

                    <div x-show="foundItem" class="bg-gray-50 rounded-2xl p-4 border border-gray-100" x-transition>
                        <div class="flex gap-4">
                            <div class="w-20 h-20 bg-white rounded-xl overflow-hidden shadow-sm flex-shrink-0 border border-gray-100">
                                <img :src="foundItem.image_url" class="w-full h-full object-cover" @error="$el.src='/images/no-image.png'">
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-800" x-text="foundItem.nome_do_produto"></p>
                                <p class="text-xs text-gray-500" x-text="foundItem.marca + ' â€¢ ' + foundItem.tamanho"></p>
                                
                                <div class="mt-3">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">PreÃ§o do Item</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">R$</span>
                                        <input type="number" step="0.01" x-model="editPrice"
                                               class="w-full pl-10 rounded-xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition font-bold text-lg text-gray-800">
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">PreÃ§o original: R$ <span x-text="foundItem.price"></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Quantidade</label>
                            <input type="number" x-model="itemQuantidade" min="1" class="w-full rounded-xl border-gray-200 text-sm">
                        </div>

                        <button @click="confirmAddItem()" 
                                class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg transition duration-200 flex items-center justify-center gap-2"
                                :disabled="adding">
                            <template x-if="adding">
                                <i class="fas fa-spinner fa-spin"></i>
                            </template>
                            <template x-if="!adding">
                                <i class="fas fa-plus-circle"></i>
                            </template>
                            Adicionar ao Pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editarPedido() {
    return {
        modalAddItem: false,
        searchCode: '',
        foundItem: null,
        searching: false,
        adding: false,
        errorMessage: '',
        editPrice: 0,
        itemQuantidade: 1,
        pedidoId: {{ $pedido->id }},

        openModalAdicionarItem() {
            this.modalAddItem = true;
            this.searchCode = '';
            this.foundItem = null;
            this.errorMessage = '';
            this.itemQuantidade = 1;
        },

        async searchItem() {
            if (!this.searchCode) return;
            this.searching = true;
            this.errorMessage = '';
            this.foundItem = null;

            try {
                const res = await fetch(`/api/items/search?q=${this.searchCode}`);
                const data = await res.json();
                if (data.success && data.data.length > 0) {
                    this.foundItem = data.data[0];
                    this.editPrice = this.foundItem.price;
                } else {
                    this.errorMessage = 'Item nÃ£o encontrado';
                }
            } catch (e) {
                this.errorMessage = 'Erro ao buscar item.';
            } finally {
                this.searching = false;
            }
        },

        async confirmAddItem() {
            if (!this.foundItem) return;
            this.adding = true;
            try {
                const res = await fetch(`/admin/pedido/${this.pedidoId}/adicionar-item`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        item_id: this.foundItem.id,
                        preco_unitario: this.editPrice,
                        quantidade: this.itemQuantidade
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao adicionar item');
                }
            } catch (e) {
                alert('Erro ao adicionar item.');
            } finally {
                this.adding = false;
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
	  const btn = document.getElementById('btnDevolucao');
	  const form = document.getElementById('devolucaoForm');

	  function refresh() {
		const anyChecked = !!document.querySelector('.chkDevolver:checked');
		if (anyChecked) {
		  btn.disabled = false;
		  btn.classList.remove('opacity-50', 'cursor-not-allowed');
		  btn.classList.add('hover:bg-indigo-700');
		} else {
		  btn.disabled = true;
		  btn.classList.add('opacity-50', 'cursor-not-allowed');
		  btn.classList.remove('hover:bg-indigo-700');
		}
	  }

	  document.addEventListener('change', function (e) {
		if (e.target && e.target.classList.contains('chkDevolver')) refresh();
	  });

	  form.addEventListener('submit', function (e) {
		const anyChecked = !!document.querySelector('.chkDevolver:checked');
		if (!anyChecked) e.preventDefault();
	  });

	  refresh();
	});

    function copyPaymentLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            alert('Link de pagamento copiado para a Ã¡rea de transferÃªncia!');
        }).catch(err => {
            const textArea = document.createElement("textarea");
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            alert('Link de pagamento copiado!');
        });
    }

    window.removerItem = function(itemId) {
        if (!confirm('Tem certeza que deseja remover este item do pedido?')) return;
        fetch('/admin/pedido/{{ $pedido->id }}/remover-item/' + itemId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Erro ao remover item');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Erro ao remover item');
        });
    };
	</script>
@endsection
