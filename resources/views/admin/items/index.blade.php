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
                <div class="md:col-span-3">
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
                <div class="md:col-span-3">
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

                {{-- Localização --}}
                <div class="md:col-span-2">
                    <label for="localizacao" class="block text-sm font-medium text-gray-700 mb-1">Localização</label>
                    <input
                        id="localizacao"
                        name="localizacao"
                        value="{{ request('localizacao') }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: Prateleira..."
                        autocomplete="off"
                    />
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
        <div class="p-4 bg-gray-50 flex items-center justify-between border-b border-gray-200">
            <h2 class="text-gray-700 font-semibold flex items-center">
                <i class="fas fa-list mr-2"></i> Listagem de Itens ({{ $items->total() }})
            </h2>
            <button type="button" id="btnPrintSelected" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded shadow-sm text-sm transition hidden flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir Etiquetas Selecionadas
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full w-full bg-white">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs leading-normal">
                        <th class="py-3 px-6 text-center w-10">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        </th>
                        <th class="py-3 px-6 text-left w-16">Foto</th>
                        <th class="py-3 px-6 text-left">Cód / SKU</th>
                        <th class="py-3 px-6 text-left">Nome</th>
                        <th class="py-3 px-6 text-left">Detalhes (Marca/Cor/Tam/Est/Local)</th>
                        <th class="py-3 px-6 text-left">Status</th>
                        <th class="py-3 px-6 text-left">Preço</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm font-light">
                    @forelse ($items as $item)
                        @php
                            $nome   = $item->nome_do_produto ?? $item->nome ?? $item->title ?? $item->titulo ?? 'N/A';
                            $codigo = $item->codigo ?? $item->sku ?? null;
                            $marca  = $item->marca ?? null;
                            $cor    = $item->cor ?? null;
                            $tamanho= $item->tamanho ?? null;
                            $estado = $item->estado ?? null;
                            
                            $statusRaw = $item->status ?? 'Desconhecido';
                            $status = strtolower((string) $statusRaw);

                            $pill = 'bg-gray-200 text-gray-800';
                            if (in_array($status, ['disponivel','disponível','ativo','available'])) $pill = 'bg-green-200 text-green-800';
                            if (in_array($status, ['reservado','reserved'])) $pill = 'bg-yellow-200 text-yellow-800';
                            if (in_array($status, ['vendido','sold','inativo','indisponivel','indisponível'])) $pill = 'bg-red-200 text-red-800';

                            $preco = $item->preco ?? $item->price ?? 0;

                            $img = $item->imagem_url ?? $item->imagem ?? $item->foto ?? $item->foto_url ?? null;
                            if (!$img && isset($item->medias) && $item->medias->count() > 0) {
                                $media = $item->medias->first();
                                $img = asset('storage/' . $media->url);
                            }

                            $detalhesParts = [];
                            if ($marca) $detalhesParts[] = $marca;
                            if ($cor) $detalhesParts[] = $cor;
                            if ($tamanho) $detalhesParts[] = 'Tam: ' . $tamanho;
                            if ($estado) $detalhesParts[] = $estado;
                            if (!empty($item->localizacao)) $detalhesParts[] = 'Local: ' . $item->localizacao;

                            $detalhes = count($detalhesParts) ? implode(' • ', $detalhesParts) : '-';

                            $etiquetaData = [
                                'codigo' => (string)$codigo,
                                'produto' => strtoupper($marca ?: '') . '   ' . \Illuminate\Support\Str::title($nome) . ' ' . \Illuminate\Support\Str::title($cor ?: '') . ' [' . strtolower($estado ?: '') . ']',
                                'tamanho' => (string)$tamanho,
                                'preco' => number_format((float)$preco, 2, ',', '')
                            ];
                        @endphp

                        <tr class="border-b border-gray-200 hover:bg-gray-100 align-top">
                            <td class="py-3 px-6 text-center whitespace-nowrap">
                                <input type="checkbox" class="item-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ json_encode($etiquetaData) }}">
                            </td>
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

        // ====== Lógica de Seleção e Impressão de Etiquetas ======
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllBtn = document.getElementById('selectAll');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const printBtn = document.getElementById('btnPrintSelected');

            function updatePrintButtonVisibility() {
                const checkedCount = document.querySelectorAll('.item-checkbox:checked').length;
                if (checkedCount > 0) {
                    printBtn.classList.remove('hidden');
                } else {
                    printBtn.classList.add('hidden');
                }
            }

            if (selectAllBtn) {
                selectAllBtn.addEventListener('change', function() {
                    itemCheckboxes.forEach(cb => {
                        cb.checked = selectAllBtn.checked;
                    });
                    updatePrintButtonVisibility();
                });
            }

            itemCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (!this.checked) selectAllBtn.checked = false;
                    updatePrintButtonVisibility();
                });
            });

            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
                    if (checkedBoxes.length === 0) {
                        alert('Nenhum item selecionado!');
                        return;
                    }

                    const etiquetas = [];
                    checkedBoxes.forEach(cb => {
                        try {
                            etiquetas.push(JSON.parse(cb.value));
                        } catch (e) {
                            console.error('Erro ao ler dados da etiqueta', e);
                        }
                    });

                    printLabelsMultiplas(etiquetas);
                });
            }
        });

        function printLabelsMultiplas(etiquetas) {
            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                    @page {
                        size: 60mm 30mm;
                        margin: 0;
                    }
                    body {
                        margin: 0.5mm;
                        padding: 0;
                        display: flex;
                        flex-wrap: wrap;
                        flex-direction: row;
                        font-family: Arial, sans-serif;
                        line-height: 1.05;
                    }
                    .label {
                        width: 60mm;
                        height: 29mm;
                        display: flex;
                        flex-direction: row;
                        justify-content: space-between;
                        align-items: flex-start;
                        font-size: 12px;
                        padding: 1mm;
                        box-sizing: border-box;
                        overflow: hidden;
                    }
                    .left {
                        flex: 1.3;
                        display: flex;
                        flex-direction: column;
                        justify-content: flex-start;
                        align-items: flex-start;
                        height: 100%;
                        gap: 0.1mm;
                        overflow: hidden;
                    }
                    .produto {
                        font-weight: normal;
                        font-size: 9px;
                        text-align: left;
                        word-wrap: break-word;
                        white-space: normal;
                        margin: 0;
                        line-height: 1.1;
                    }
                    .tamanho {
                        font-weight: bold;
                        font-size: 22px;
                        text-align: center;
                        margin-top: 0 !important;
                        margin-bottom: 0;
                        line-height: 1;
                    }
                    .preco {
                        font-weight: bold;
                        font-size: 11px;
                        margin-top: 0 !important;
                        white-space: nowrap;
                    }
                    .valor {
                        font-weight: normal;
                        font-size: 11px;
                        margin-top: 0 !important;
                        margin-left: 0.5mm;
                    }
                    .right {
                        flex: 0 0 22.5mm;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        height: 100%;
                        gap: 0.1mm;
                    }
                    .barcode {
                        width: 22.5mm;
                        height: 22.5mm;
                    }
                    .codigoBarra {
                        font-weight: normal;
                        font-size: 10px;
                        text-align: center;
                        margin-top: 0 !important;
                    }
                    @media print {
                        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    }
                    </style>
                </head>
                <body>
                    ${etiquetas.map(etiqueta => `
                        <div class="label">
                            <div class="left">
                                <div>
                                    <span class="produto">- ${etiqueta.produto.replace(/'/g, "\\'")}</span>
                                </div>
                                <div class="tamanho">${etiqueta.tamanho}</div>
                                <div>
                                    <span class="preco">Preço:  </span>
                                    <span class="valor">${etiqueta.preco}</span>
                                </div>
                            </div>
                            <div class="right">
                            <img class="barcode" 
                                 src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(etiqueta.codigo)}"
                                 alt="QR Code ${etiqueta.codigo}" 
                                 onload="this.style.opacity=1;" 
                                 style="opacity:0.5;" />
                                <div class="codigoBarra">${etiqueta.codigo}</div>
                            </div>
                        </div>
                    `).join('')}
                    <script>
                        window.onload = function() {
                            setTimeout(function() {
                                window.print();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `;

            const printWindow = window.open('', 'EtiquetasLote', 'width=1000,height=700,scrollbars=yes,resizable=yes');
            printWindow.document.write(htmlContent);
            printWindow.document.close();
        }
    </script>
@endpush