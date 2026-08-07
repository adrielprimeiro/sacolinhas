@extends('layouts.app')

@section('title', $item->nome_do_produto)

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('items.index') }}" class="hover:text-indigo-600 transition">
            <i class="fas fa-box mr-1"></i>Itens
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-800 font-medium truncate max-w-xs">{{ $item->nome_do_produto }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $item->nome_do_produto }}</h1>
            @if($item->codigo)
                <p class="text-sm text-gray-500 mt-1">
                    <i class="fas fa-barcode mr-1"></i>{{ $item->codigo }}
                </p>
            @endif
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ route('items.edit', $item) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="{{ route('items.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

        {{-- ── Coluna Imagem ── --}}
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if($item->image)
                    <img src="{{ asset('storage/' . $item->image) }}"
                         alt="{{ $item->nome_do_produto }}"
                         class="w-full object-cover"
                         style="max-height: 380px;">
                @else
                    <div class="flex flex-col items-center justify-content-center py-20 text-gray-300">
                        <i class="fas fa-image text-6xl mb-3"></i>
                        <span class="text-sm">Sem imagem</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Coluna Info ── --}}
        <div class="md:col-span-3 flex flex-col gap-5">

            {{-- Status + Preço --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-4">
                    {{-- Status badge --}}
                    @php
                        $statusMap = [
                            'disponivel'  => ['label' => 'Disponível',   'class' => 'bg-green-100 text-green-800'],
                            'indisponivel'=> ['label' => 'Indisponível', 'class' => 'bg-red-100 text-red-800'],
                            'reservado'   => ['label' => 'Reservado',    'class' => 'bg-yellow-100 text-yellow-800'],
                            'vendido'     => ['label' => 'Vendido',      'class' => 'bg-gray-100 text-gray-700'],
                            'em_sacolinha'=> ['label' => 'Em Sacolinha', 'class' => 'bg-purple-100 text-purple-800'],
                            'estoque'     => ['label' => 'Estoque',      'class' => 'bg-blue-100 text-blue-800'],
                            'loja'        => ['label' => 'Loja',         'class' => 'bg-cyan-100 text-cyan-800'],
                            'live'        => ['label' => 'Live',         'class' => 'bg-pink-100 text-pink-800'],
                        ];
                        $s = $statusMap[$item->status] ?? ['label' => ucfirst($item->status), 'class' => 'bg-gray-100 text-gray-700'];
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $s['class'] }}">
                        {{ $s['label'] }}
                    </span>

                    {{-- Localização --}}
                    @if($item->localizacao)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-600">
                            <i class="fas fa-map-marker-alt text-xs"></i>
                            {{ $item->localizacao }}
                        </span>
                    @endif
                </div>

                <div class="flex items-end gap-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">Preço de Venda</p>
                        <p class="text-3xl font-bold text-green-600">{{ $item->formatted_price }}</p>
                    </div>
                    @if($item->custo)
                        <div class="pb-1">
                            <p class="text-xs text-gray-400">Custo: R$ {{ number_format($item->custo, 2, ',', '.') }}</p>
                            @php
                                $margem = $item->custo > 0
                                    ? (($item->preco - $item->custo) / $item->custo) * 100
                                    : null;
                            @endphp
                            @if($margem !== null)
                                <p class="text-xs {{ $margem >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                    Margem: {{ number_format($margem, 1) }}%
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Detalhes --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Detalhes do Item</h2>

                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-400 font-medium">Marca</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->marca ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Modelo</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->modelo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Estado</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ ucfirst($item->estado ?? '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Cor</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->cor ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Tamanho</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->tamanho ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Categoria</dt>
                        <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->codigo_da_categoria ?? '—' }}</dd>
                    </div>
                    @if($item->pedido)
                        <div class="col-span-2">
                            <dt class="text-gray-400 font-medium">Pedido</dt>
                            <dd class="text-gray-800 font-semibold mt-0.5">{{ $item->pedido }}</dd>
                        </div>
                    @endif
                </dl>

                @if($item->descricao)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <dt class="text-gray-400 font-medium text-sm mb-1">Descrição</dt>
                        <dd class="text-gray-700 text-sm leading-relaxed">{{ $item->descricao }}</dd>
                    </div>
                @endif
            </div>

            {{-- Datas --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-400 font-medium">Criado em</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $item->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-400 font-medium">Atualizado em</dt>
                        <dd class="text-gray-700 mt-0.5">{{ $item->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Ações destrutivas --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('items.edit', $item) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-edit"></i> Editar Item
                </a>
                <form action="{{ route('items.destroy', $item) }}" method="POST"
                      onsubmit="return confirm('Tem certeza que deseja deletar este item? Esta ação não pode ser desfeita.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                        <i class="fas fa-trash"></i> Excluir
                    </button>
                </form>
            </div>

        </div>{{-- /col info --}}
    </div>{{-- /grid --}}
</div>
@endsection