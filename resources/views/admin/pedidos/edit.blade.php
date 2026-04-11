@extends('layouts.app')

@section('title', 'Editar Pedido #' . ($pedido->numero_pedido ?? $pedido->id))
@section('brand_route', 'admin.pedido.index')
@section('brand_icon', 'fas fa-receipt')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-semibold text-gray-800">
                Editar Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Criado em:
                {{ !empty($pedido->created_at) ? $pedido->created_at->format('d/m/Y H:i') : '—' }}
                • Atualizado em:
                {{ !empty($pedido->updated_at) ? $pedido->updated_at->format('d/m/Y H:i') : '—' }}
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
					<i class="fas fa-undo mr-2"></i> Devolução
				</button>
			</form>
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
                            <label class="block text-gray-500 mb-1">Número</label>
                            <input type="text"
                                   name="numero_pedido"
                                   value="{{ old('numero_pedido', $pedido->numero_pedido) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('numero_pedido') border-red-500 @enderror">
                            @error('numero_pedido')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Data do pedido</label>
                            <input type="datetime-local"
                                   name="data_pedido"
                                   value="{{ old('data_pedido', !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('Y-m-d\TH:i') : null) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('data_pedido') border-red-500 @enderror">
                            @error('data_pedido')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-between gap-4 items-center">
                            <span class="text-gray-500">Status do pedido</span>
                            <span>
                                @php $spPreview = old('status_pedido', $pedido->status_pedido); @endphp
                                @if ($spPreview === 'entregue')
                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Entregue</span>
                                @elseif ($spPreview === 'enviado')
                                    <span class="bg-blue-200 text-blue-800 py-1 px-3 rounded-full text-xs font-semibold">Enviado</span>
                                @elseif ($spPreview === 'processando')
                                    <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Processando</span>
                                @elseif ($spPreview === 'confirmado')
                                    <span class="bg-indigo-200 text-indigo-800 py-1 px-3 rounded-full text-xs font-semibold">Confirmado</span>
                                @elseif ($spPreview === 'pendente')
                                    <span class="bg-gray-200 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                                @elseif ($spPreview === 'cancelado')
                                    <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Cancelado</span>
                                @else
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $spPreview ?? '—' }}</span>
                                @endif
                            </span>
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Alterar status do pedido</label>
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
							<label class="block text-gray-500 mb-1">Origem do pedido</label>
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

                        <div>
                            <label class="block text-gray-500 mb-1">Live ID</label>
                            <input type="number"
                                   name="live_id"
                                   value="{{ old('live_id', $pedido->live_id) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('live_id') border-red-500 @enderror">
                            @error('live_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Valores (mesmo card do show, mas com inputs) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Valores</h2>

                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Subtotal</span>
                            @php
                                $subtotal = (float)$pedido->valor_total - (float)$pedido->valor_frete + (float)$pedido->valor_desconto;
                            @endphp
                            <span class="text-gray-800 font-medium">R$ {{ number_format($subtotal, 2, ',', '.') }}</span>
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Frete</label>
                            <input type="number"
                                   step="0.01"
                                   name="valor_frete"
                                   value="{{ old('valor_frete', $pedido->valor_frete) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor_frete') border-red-500 @enderror">
                            @error('valor_frete')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Desconto</label>
                            <input type="number"
                                   step="0.01"
                                   name="valor_desconto"
                                   value="{{ old('valor_desconto', $pedido->valor_desconto) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('valor_desconto') border-red-500 @enderror">
                            @error('valor_desconto')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Total</label>
                            <input type="number"
                                   step="0.01"
                                   name="valor_total"
                                   value="{{ old('valor_total', $pedido->valor_total) }}"
                                   readonly
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 bg-gray-100 cursor-not-allowed sm:text-sm @error('valor_total') border-red-500 @enderror">
                            @error('valor_total')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Cupom</label>
                            <input type="text"
                                   name="cupom_desconto"
                                   value="{{ old('cupom_desconto', $pedido->cupom_desconto) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('cupom_desconto') border-red-500 @enderror">
                            @error('cupom_desconto')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Botões (no edit, fica mais natural aqui ou no rodapé; deixei no rodapé também) --}}
            </div>

            {{-- Coluna 2-3: Detalhes (mesma estrutura do show: Cliente -> Itens -> Pagamento -> Entrega -> Observações) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Cliente --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Cliente</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">Nome</div>
                            <div class="text-gray-900 font-medium">{{ $pedido->user->name ?? 'N/A' }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500">E-mail</div>
                            <div class="text-gray-900 font-medium">{{ $pedido->user->email ?? '—' }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500">User ID</div>
                            <div class="text-gray-900 font-medium">{{ $pedido->user_id }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-500 mb-1">Alterar usuário (ID)</label>
                            <input type="number"
                                   name="user_id"
                                   value="{{ old('user_id', $pedido->user_id) }}"
                                   class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('user_id') border-red-500 @enderror">
                            @error('user_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-400 mt-1">Se preferir, mantenha o mesmo User ID.</p>
                        </div>
                    </div>
                </div>

                {{-- Itens do Pedido (idêntico ao show: mesma query, mesma tabela, mesma posição) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Itens do Pedido</h2>

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
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Preço Unit.</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
										<th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Devolver</th>
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
                                                {{ $item->marca }} • {{ $item->estado }} • {{ $item->cor }} • Tam: {{ $item->tamanho }}
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
                                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $si ?? '—' }}</span>
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
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Pagamento</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-gray-500 mb-1">Forma</label>
								@php $fp = old('forma_pagamento', $pedido->forma_pagamento); @endphp
								<select name="forma_pagamento"
										class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('forma_pagamento') border-red-500 @enderror">
									<option value="" {{ empty($fp) ? 'selected' : '' }}>—</option>
									<option value="pix" {{ $fp === 'pix' ? 'selected' : '' }}>Pix</option>
									<option value="cartao_credito" {{ $fp === 'cartao_credito' ? 'selected' : '' }}>Cartão de crédito</option>
									<option value="cartao_debito" {{ $fp === 'cartao_debito' ? 'selected' : '' }}>Cartão de débito</option>
									<option value="boleto" {{ $fp === 'boleto' ? 'selected' : '' }}>Boleto</option>
									<option value="dinheiro" {{ $fp === 'dinheiro' ? 'selected' : '' }}>Dinheiro</option>
									<option value="transferencia" {{ $fp === 'transferencia' ? 'selected' : '' }}>Transferência</option>
								</select>
								@error('forma_pagamento')
								  <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
								@enderror
                        </div>

                        <div>
                            <div class="text-gray-500">Status (preview)</div>
                            <div class="mt-1">
                                @php $pgPreview = old('status_pagamento', $pedido->status_pagamento); @endphp
                                @if ($pgPreview === 'aprovado')
                                    <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-semibold">Aprovado</span>
                                @elseif ($pgPreview === 'pendente')
                                    <span class="bg-yellow-200 text-yellow-800 py-1 px-3 rounded-full text-xs font-semibold">Pendente</span>
                                @elseif ($pgPreview === 'rejeitado')
                                    <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-semibold">Rejeitado</span>
                                @elseif ($pgPreview === 'estornado')
                                    <span class="bg-gray-300 text-gray-800 py-1 px-3 rounded-full text-xs font-semibold">Estornado</span>
                                @else
                                    <span class="bg-gray-200 text-gray-700 py-1 px-3 rounded-full text-xs font-semibold">{{ $pgPreview ?? '—' }}</span>
                                @endif
                            </div>

                            <label class="block text-gray-500 mb-1 mt-3">Alterar status</label>
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
                    </div>
                </div>

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
                            <label class="block text-gray-500 mb-1">Endereço</label>
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
                            <label class="block text-gray-500 mb-1">Código rastreio</label>
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

                {{-- Observações (mesmo card do show, mas com textarea) --}}
                <div class="bg-white shadow-lg rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Observações</h2>

                    <textarea name="observacoes"
                              rows="4"
                              class="w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('observacoes') border-red-500 @enderror">{{ old('observacoes', $pedido->observacoes) }}</textarea>

                    @error('observacoes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Ações --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.pedido.show', $pedido->id) }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                        <i class="fas fa-times-circle mr-2"></i> Cancelar
                    </a>

                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-sm transition duration-300">
                        <i class="fas fa-save mr-2"></i> Salvar alterações
                    </button>
                </div>
            </div>
        </div>
    </form>
	@if ($errors->any())
	  <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-4 text-sm">
		<div class="font-semibold mb-2">Não foi possível salvar. Verifique:</div>
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

	  // Segurança: evitar submit sem item marcado
	  form.addEventListener('submit', function (e) {
		const anyChecked = !!document.querySelector('.chkDevolver:checked');
		if (!anyChecked) e.preventDefault();
	  });

	  refresh();
	});
	</script>	

@endsection