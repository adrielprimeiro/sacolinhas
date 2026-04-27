@extends('layouts.app')

@section('title', 'Itens')

@section('content')

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-semibold text-gray-800">Itens</h1>

        <a href="{{ route('items.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md shadow-md transition duration-300">
            <i class="fas fa-plus-circle mr-2"></i> Novo Item
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-lg rounded-lg p-4 mb-6">
        <form id="itemsFilterForm" method="GET" action="{{ route('items.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                {{-- Busca --}}
                <div class="md:col-span-4">
                    <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Buscar por código</label>
                    <div class="relative">
                        <input
                            id="codigo"
                            name="codigo"
                            value="{{ request('codigo') }}"
                            class="w-full border border-gray-300 rounded-md pl-3 pr-12 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Digite ou leia o QRCode"
                            autocomplete="off"
                        />

                        <button type="button"
                                id="btnToggleQr"
                                class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-gray-500 hover:text-indigo-700"
                                title="Ler QRCode">
                            <i class="fas fa-qrcode text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Status --}}
                <div class="md:col-span-2">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select
                        id="status"
                        name="status"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Todos</option>
						<option value="indisponivel" {{ request('status') == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
                        <option value="disponivel" {{ request('status') == 'disponivel' ? 'selected' : '' }}>Disponível</option>
                        <option value="reservado" {{ request('status') == 'reservado' ? 'selected' : '' }}>Reservado</option>
                        <option value="vendido" {{ request('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                        <option value="em_sacolinha" {{ request('status') == 'em_sacolinha' ? 'selected' : '' }}>Em Sacolinha</option>
						<option value="loja" {{ request('status') == 'loja' ? 'selected' : '' }}>Loja</option>
                        <option value="estoque" {{ request('status') == 'estoque' ? 'selected' : '' }}>Estoque</option>						
						<option value="live" {{ request('status') == 'live' ? 'selected' : '' }}>Live</option>
                        <option value="solicitado na loja" {{ request('status') == 'solicitado na loja' ? 'selected' : '' }}>Solicitado na Loja</option>
						<option value="solicitado na live" {{ request('status') == 'solicitado na live' ? 'selected' : '' }}>Solicitado na Live</option>
                    </select>
                </div>

                {{-- Categoria --}}
                <div class="md:col-span-4">
                    <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                    <select
                        id="categoria_id"
                        name="categoria_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Todas Categorias</option>
                        <option value="none" {{ request('categoria_id') == 'none' ? 'selected' : '' }} class="font-bold text-red-600">-- SEM CATEGORIA --</option>
                        
                        @php
                            $renderOptions = function($cats, $level = 0) use (&$renderOptions) {
                                foreach ($cats as $cat) {
                                    $selected = request('categoria_id') == $cat->id ? 'selected' : '';
                                    $indent = str_repeat('&nbsp;&nbsp;', $level);
                                    $prefix = $level > 0 ? '↳ ' : '';
                                    echo "<option value=\"{$cat->id}\" {$selected}>{$indent}{$prefix}{$cat->name}</option>";
                                    if ($cat->children->isNotEmpty()) {
                                        $renderOptions($cat->children, $level + 1);
                                    }
                                }
                            };
                        @endphp
                        {!! $renderOptions($treeCategories) !!}
                    </select>
                </div>

                {{-- Botões --}}
                <div class="md:col-span-2 flex gap-1">
                    <button type="submit"
                            class="flex-1 bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-3 rounded-md shadow-md transition text-sm">
                        Filtrar
                    </button>

                    <a href="{{ route('items.index') }}"
                       class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-3 rounded-md shadow-md transition text-sm text-center">
                        Limpar
                    </a>
                </div>

            </div>
        </form>

        <div id="qrReaderWrap" class="hidden mt-4 border border-gray-200 rounded-md p-3">
            <div class="text-sm text-gray-600 mb-2">
                Permita o acesso à câmera e aponte para o QRCode.
            </div>
            <div id="qrReader" class="w-full"></div>
        </div>
    </div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                        <th class="py-3 px-6 text-left">Imagem</th>
                        <th class="py-3 px-6 text-left">Código</th>
                        <th class="py-3 px-6 text-left">Produto</th>
                        <th class="py-3 px-6 text-left">Detalhes</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Preço</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 text-sm">
                    @forelse ($items as $item)
                        @php
                            $codigo = $item->codigo ?? $item->code ?? null;
                            $nome = $item->nome_do_produto
                                ?? $item->nome
                                ?? $item->name
                                ?? $item->produto
                                ?? '—';

                            $marca = $item->marca ?? null;
                            $cor = $item->cor ?? null;
                            $tamanho = $item->tamanho ?? $item->tam ?? null;
                            $estado = $item->estado ?? null;

                            $statusRaw = $item->status ?? $item->situacao ?? '—';
                            $status = strtolower((string) $statusRaw);

                            $pill = 'bg-gray-200 text-gray-800';
                            if (in_array($status, ['disponivel','disponível','ativo','available'])) $pill = 'bg-green-200 text-green-800';
                            if (in_array($status, ['reservado','reserved'])) $pill = 'bg-yellow-200 text-yellow-800';
                            if (in_array($status, ['vendido','sold','inativo','indisponivel','indisponível'])) $pill = 'bg-red-200 text-red-800';

                            $preco = $item->preco ?? $item->price ?? null;

                            $img = $item->imagem_url
                                ?? $item->imagem
                                ?? $item->foto
                                ?? $item->foto_url
                                ?? null;

                            $detalhesParts = [];
                            if ($marca) $detalhesParts[] = $marca;
                            if ($cor) $detalhesParts[] = $cor;
                            if ($tamanho) $detalhesParts[] = 'Tam: ' . $tamanho;
                            if ($estado) $detalhesParts[] = $estado;

                            $detalhes = count($detalhesParts) ? implode(' • ', $detalhesParts) : '—';
                        @endphp

                        <tr class="border-b border-gray-200 hover:bg-gray-100 align-top">
                            <td class="py-3 px-6 text-left whitespace-nowrap">
                                @if ($img)
                                    <img src="{{ $img }}" alt="Imagem do item"
                                         class="w-12 h-12 rounded-md object-cover border border-gray-200">
                                @else
                                    <div class="w-12 h-12 rounded-md bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                        <i class="fas fa-image"></i>
                                    </div>
                                @endif
                            </td>

                            <td class="py-3 px-6 text-left whitespace-nowrap font-medium">
                                {{ $codigo ?? '—' }}
                            </td>

                            <td class="py-3 px-6 text-left">
                                {{ $nome }}
                            </td>

                            <td class="py-3 px-6 text-left text-gray-600">
                                {{ $detalhes }}
                            </td>

                            <td class="py-3 px-6 text-left">
                                <span class="{{ $pill }} py-1 px-3 rounded-full text-xs font-semibold">
                                    {{ $statusRaw }}
                                </span>
                            </td>

                            <td class="py-3 px-6 text-left font-medium whitespace-nowrap">
                                @if ($preco !== null)
                                    R$ {{ number_format((float) $preco, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="py-3 px-6 text-center">
                                <div class="flex item-center justify-center space-x-2">
                                    <a href="{{ route('items.show', $item) }}"
                                       class="w-8 h-8 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 flex items-center justify-center transition duration-300"
                                       title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <a href="{{ route('items.edit', $item) }}"
                                       class="w-8 h-8 rounded-full bg-yellow-100 hover:bg-yellow-200 text-yellow-700 flex items-center justify-center transition duration-300"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form action="{{ route('items.destroy', $item) }}"
                                          method="POST"
                                          onsubmit="return confirm('Tem certeza que deseja excluir este item?');"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center transition duration-300"
                                                title="Excluir">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 px-6 text-center text-gray-500">
                                Nenhum item encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $items->appends(request()->query())->links() }}
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        (function () {
            let qrScanner = null;
            let running = false;

            const btn = document.getElementById('btnToggleQr');
            const wrap = document.getElementById('qrReaderWrap');
            const input = document.getElementById('codigo');
            const form = document.getElementById('itemsFilterForm');

            if (!btn || !wrap || !input || !form) return;

            async function stopScanner() {
                if (!qrScanner || !running) return;
                try { await qrScanner.stop(); } catch (e) {}
                running = false;
            }

            btn.addEventListener('click', async () => {
                const opening = wrap.classList.contains('hidden');

                if (opening) {
                    wrap.classList.remove('hidden');

                    if (!qrScanner) qrScanner = new Html5Qrcode("qrReader");

                    try {
                        await qrScanner.start(
                            { facingMode: "environment" },
                            { fps: 10, qrbox: { width: 240, height: 240 } },
                            async (decodedText) => {
                                input.value = decodedText;
                                await stopScanner();
                                wrap.classList.add('hidden');
                                form.submit();
                            }
                        );

                        running = true;
                    } catch (e) {
                        console.error(e);
                        wrap.classList.add('hidden');
                        alert('Não foi possível acessar a câmera. Verifique permissões do navegador.');
                    }
                } else {
                    await stopScanner();
                    wrap.classList.add('hidden');
                }
            });

            window.addEventListener('beforeunload', () => { stopScanner(); });
        })();
    </script>
@endpush