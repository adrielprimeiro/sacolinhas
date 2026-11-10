<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ $isParceiro ? 'Relatório para Parceiro' : 'Recibo de Avaliação' }} #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4338ca;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-table .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 4px;
        }
        .header-table h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            color: #312e81;
            font-weight: bold;
        }
        .header-table p {
            margin: 0;
            color: #4b5563;
            font-size: 11px;
        }
        .header-table .lote {
            font-weight: bold;
            color: #312e81;
            font-size: 14px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .details-table {
            width: 100%;
            margin-bottom: 12px;
        }
        .details-table td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            border-radius: 6px;
            background-color: #f8fafc;
            font-size: 11px;
            line-height: 1.45;
        }
        .box h3 {
            margin-top: 0;
            font-size: 11px;
            color: #312e81;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: monospace;
            font-weight: bold;
        }
        .totals {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .totals th, .totals td {
            padding: 3px 6px;
        }
        .totals th {
            text-align: right;
            font-weight: normal;
            color: #4b5563;
        }
        .totals td {
            text-align: right;
            font-weight: bold;
        }
        .totals .grand-total th, .totals .grand-total td {
            font-size: 13px;
            border-top: 2px solid #4338ca;
            padding-top: 6px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .signature-section {
            margin-top: 25px;
            width: 100%;
        }
        .signature-line {
            width: 230px;
            border-top: 1px solid #475569;
            text-align: center;
            font-size: 10px;
            color: #334155;
            padding-top: 4px;
            float: left;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- Cabeçalho Padrão --}}
    <table class="header-table">
        <tr>
            <td style="text-align: left; width: 50%;">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" class="logo" alt="Logo">
                @else
                    <h1>Mania</h1>
                @endif
                <p>Brechó & Curadoria de Moda Circular</p>
            </td>
            <td style="text-align: right; width: 50%;">
                <div class="lote">
                    @if ($isParceiro)
                        Relatório para Parceiro - Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}
                    @else
                        Recibo de Avaliação - Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
                <p><strong>Data da Avaliação:</strong> {{ $avaliacao->formatted_data_avaliacao }}</p>
                <p><strong>Emissão do Documento:</strong> {{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>

    {{-- Detalhes do Fornecedor e da Operação --}}
    <table class="details-table">
        <tr>
            <td style="padding-right: 5px;">
                <div class="box">
                    <h3>{{ $isParceiro ? 'Brechó Parceiro (Fornecedor)' : 'Fornecedor / Fornecedora' }}</h3>
                    <strong>Nome:</strong> {{ $avaliacao->user->name ?? ($avaliacao->parceiro->nome ?? 'Desconhecido') }}<br>
                    @if ($avaliacao->user && $avaliacao->user->apelido)
                        <strong>Apelido:</strong> {{ $avaliacao->user->apelido }}<br>
                    @endif
                    @if ($isParceiro)
                        @if ($avaliacao->parceiro && $avaliacao->parceiro->chave_pix)
                            <strong>Chave Pix:</strong> {{ $avaliacao->parceiro->chave_pix }}<br>
                        @endif
                        @if ($avaliacao->parceiro && $avaliacao->parceiro->telefone)
                            <strong>Contato / WhatsApp:</strong> {{ $avaliacao->parceiro->telefone }}<br>
                        @elseif ($avaliacao->user && $avaliacao->user->whatsapp)
                            <strong>WhatsApp:</strong> {{ $avaliacao->user->whatsapp }}<br>
                        @endif
                    @else
                        <strong>Adesão:</strong> {{ $avaliacao->tipo_cliente === 'clube' ? 'Clube' : 'Fora do Clube' }}<br>
                        @if ($avaliacao->user && $avaliacao->user->whatsapp)
                            <strong>WhatsApp:</strong> {{ $avaliacao->user->whatsapp }}<br>
                        @endif
                    @endif
                    @if ($avaliacao->user && $avaliacao->user->email)
                        <strong>E-mail:</strong> {{ $avaliacao->user->email }}
                    @endif
                </div>
            </td>
            <td style="padding-left: 5px;">
                <div class="box">
                    <h3>Informações da Operação</h3>
                    @if ($isParceiro)
                        <strong>Operação:</strong> Parceria / Consignação<br>
                        @if ($avaliacao->parceiro)
                            <strong>Brechó Parceiro:</strong> {{ $avaliacao->parceiro->nome }}<br>
                        @endif
                        @if ($avaliacao->prefixo_codigo)
                            <strong>Prefixo SKU:</strong> {{ $avaliacao->prefixo_codigo }}<br>
                        @endif
                        <strong>Taxa Cadastro/Peça:</strong> R$ {{ number_format((float) $avaliacao->taxa_cadastro_peca, 2, ',', '.') }}<br>
                        <strong>Comissão Mania:</strong> {{ number_format((float) $avaliacao->comissao_parceiro_porcentagem, 1, ',', '.') }}% sobre as vendas<br>
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }} peças<br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @elseif ($avaliacao->tipo_compra === 'avaliados')
                        <strong>Operação:</strong> Regime de Avaliação e Desapego<br>
                        @if ($avaliacao->frete_cobrado)
                            <strong>Frete da Remessa:</strong> {{ $avaliacao->formatted_frete }}<br>
                        @endif
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }} peças<br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @else
                        <strong>Operação:</strong> Compra Direta<br>
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }} peças<br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- TABELA DE PEÇAS --}}
    @if ($isParceiro)
        {{-- RELATÓRIO PARA PARCEIRO: com Preço Cadastro, Valor Vendido (Sacolinha) e Repasse Parceiro --}}
        @php
            $comissaoPct = (float) ($avaliacao->comissao_parceiro_porcentagem ?: 20.00);
            $totalVendaSacolinhas = 0.00;
            $qtdVendidas = 0;
        @endphp
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 4%;">#</th>
                    <th class="text-center" style="width: 14%;">Código / SKU</th>
                    <th style="width: 32%;">Peça / Descrição</th>
                    <th style="width: 22%;">Detalhes</th>
                    <th class="text-right" style="width: 12%;">Preço Cad.</th>
                    <th class="text-right" style="width: 16%;">Vendido (Sacolinha)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($avaliacao->items as $item)
                @php
                    $marcaLabel = $item->marca;
                    if ($marcaLabel === 'sem_marca' || empty($marcaLabel)) $marcaLabel = 'Sem Marca';
                    elseif ($marcaLabel === 'de_marca') $marcaLabel = 'De Marca';
                    elseif (strtolower($marcaLabel) === 'farm') $marcaLabel = 'Farm';

                    $itemSku = $item->item ? ($item->item->codigo ?: $item->item->sku) : 'AV' . $item->id;

                    $detalhesList = [];
                    if (!empty($marcaLabel)) $detalhesList[] = $marcaLabel;
                    if (!empty($item->tamanho)) $detalhesList[] = 'Tam: ' . $item->tamanho;
                    if (!empty($item->cor)) $detalhesList[] = $item->cor;
                    if (!empty($item->estado)) $detalhesList[] = $item->estado;
                    $detalhesTxt = implode(' • ', $detalhesList);

                    $temSacolinha = !empty($item->sacolinha_info);
                    $precoSacolinha = $temSacolinha ? (float)$item->sacolinha_info->price : null;
                    if ($temSacolinha) {
                        $totalVendaSacolinhas += $precoSacolinha;
                        $qtdVendidas++;
                    }
                @endphp
                <tr>
                    <td class="text-center" style="font-weight: bold; color: #475569;">
                        {{ $loop->iteration }}
                    </td>
                    <td class="text-center">
                        <span class="font-mono" style="color: #312e81; font-size: 9px;">{{ $itemSku }}</span>
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $item->nome }}</strong>
                    </td>
                    <td style="color: #475569;">
                        {{ $detalhesTxt }}
                    </td>
                    <td class="text-right" style="color: #334155;">
                        R$ {{ number_format($item->preco_venda, 2, ',', '.') }}
                    </td>
                    <td class="text-right">
                        @if ($temSacolinha)
                            <strong style="color: #047857; font-size: 11px;">
                                R$ {{ number_format($precoSacolinha, 2, ',', '.') }}
                            </strong>
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 9px;">Em Estoque</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- QUADRADO FINAL DE TOTAIS DO PARCEIRO --}}
        @php
            $qtdTotal = $avaliacao->items->count();
            $qtdEstoque = max(0, $qtdTotal - $qtdVendidas);
            $comissaoManiaVendas = $totalVendaSacolinhas * ($comissaoPct / 100.00);
            $taxaCadTotal = (float) ($avaliacao->taxa_cadastro_peca ?: 0.00) * $qtdTotal;
            $repasseFinalVendas = max(0, $totalVendaSacolinhas - $comissaoManiaVendas);
        @endphp
        <div class="clearfix" style="margin-top: 15px; margin-bottom: 20px;">
            {{-- Painel Esquerdo: Contagem de Peças --}}
            <div style="width: 44%; float: left; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #f8fafc; padding: 10px 12px; font-size: 11px;">
                <div style="display: table; width: 100%; margin-bottom: 5px;">
                    <span style="display: table-cell; text-align: left; color: #374151; font-weight: 600;">Total de Peças no Lote:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 800; font-size: 12px; color: #1e1b4b;">{{ $qtdTotal }} peças</span>
                </div>
                <div style="display: table; width: 100%; margin-bottom: 5px;">
                    <span style="display: table-cell; text-align: left; color: #047857; font-weight: 600;">Peças Vendidas (Sacolinha):</span>
                    <span style="display: table-cell; text-align: right; font-weight: 800; font-size: 12px; color: #047857;">{{ $qtdVendidas }} peças</span>
                </div>
                <div style="display: table; width: 100%; margin-bottom: 5px;">
                    <span style="display: table-cell; text-align: left; color: #64748b; font-weight: 600;">Peças em Estoque:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 700; color: #334155;">{{ $qtdEstoque }} peças</span>
                </div>
                <div style="display: table; width: 100%; border-top: 1px solid #e2e8f0; padding-top: 4px; margin-top: 4px;">
                    <span style="display: table-cell; text-align: left; color: #374151; font-weight: 600;">Status do Lote:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 700; color: #312e81;">{{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}</span>
                </div>
            </div>

            {{-- Painel Direito: Valores Totais das Vendas e Repasse --}}
            <div style="width: 52%; float: right; border: 1.5px solid #312e81; border-radius: 6px; background-color: #f8fafc; padding: 8px 12px;">
                <table class="totals">
                    <tr>
                        <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #312e81; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                            Fechamento das Vendas do Parceiro
                        </th>
                    </tr>
                    <tr>
                        <th>Total Vendido (Sacolinhas):</th>
                        <td style="color: #111827; font-size: 12px;">R$ {{ number_format($totalVendaSacolinhas, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Taxa Cadastro ({{ $qtdTotal }} peças):</th>
                        <td style="color: #dc2626;">R$ {{ number_format($taxaCadTotal, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Comissão Mania ({{ number_format($comissaoPct, 1, ',', '.') }}%):</th>
                        <td style="color: #dc2626;">- R$ {{ number_format($comissaoManiaVendas, 2, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-total">
                        <th style="font-size: 12px; font-weight: bold; color: #1e1b4b;">Valor do Repasse (Vendas):</th>
                        <td style="color: #047857; font-size: 14px; font-weight: 800;">R$ {{ number_format($repasseFinalVendas, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 8px; color: #6b7280; text-align: right; padding-top: 3px;">
                            * Valores apurados com base no valor real de cada produto na sacolinha.
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    @else
        {{-- RECIBO DE AVALIAÇÃO (CLIENTES): SEM Categoria, SEM valores individuais por peça --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th class="text-center" style="width: 18%;">Código / SKU</th>
                    <th style="width: 42%;">Peça / Descrição</th>
                    <th style="width: 35%;">Detalhes (Marca, Cor, Tam, Estado)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($avaliacao->items as $item)
                @php
                    $marcaLabel = $item->marca;
                    if ($marcaLabel === 'sem_marca' || empty($marcaLabel)) $marcaLabel = 'Sem Marca';
                    elseif ($marcaLabel === 'de_marca') $marcaLabel = 'De Marca';
                    elseif (strtolower($marcaLabel) === 'farm') $marcaLabel = 'Farm';

                    $itemSku = $item->item ? ($item->item->codigo ?: $item->item->sku) : 'AV' . $item->id;

                    $detalhesList = [];
                    if (!empty($marcaLabel)) $detalhesList[] = $marcaLabel;
                    if (!empty($item->tamanho)) $detalhesList[] = 'Tam: ' . $item->tamanho;
                    if (!empty($item->cor)) $detalhesList[] = $item->cor;
                    if (!empty($item->estado)) $detalhesList[] = $item->estado;
                    $detalhesTxt = implode(' • ', $detalhesList);
                @endphp
                <tr>
                    <td class="text-center" style="font-weight: bold; color: #475569;">
                        {{ $loop->iteration }}
                    </td>
                    <td class="text-center">
                        <span class="font-mono" style="color: #312e81; font-size: 9px;">{{ $itemSku }}</span>
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $item->nome }}</strong>
                    </td>
                    <td style="color: #475569;">
                        {{ $detalhesTxt }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- QUADRADO FINAL: O que o cliente vai receber pelo lote --}}
        @php
            $sumPayoutCredito = $avaliacao->items->sum('payout_credito');
            $sumPayoutDinheiro = $avaliacao->items->sum('payout_dinheiro');
        @endphp
        <div class="clearfix" style="margin-top: 15px; margin-bottom: 20px;">
            {{-- Painel Esquerdo: Contagem de Peças --}}
            <div style="width: 44%; float: left; border: 1px solid #cbd5e1; border-radius: 6px; background-color: #f8fafc; padding: 10px 12px; font-size: 11px;">
                <div style="display: table; width: 100%; margin-bottom: 5px;">
                    <span style="display: table-cell; text-align: left; color: #374151; font-weight: 600;">Total de Peças Entregues:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 800; font-size: 13px; color: #1e1b4b;">{{ $avaliacao->items->count() }} peças</span>
                </div>
                <div style="display: table; width: 100%; margin-bottom: 5px;">
                    <span style="display: table-cell; text-align: left; color: #374151; font-weight: 600;">Data do Recebimento:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 700; color: #111827;">{{ $avaliacao->formatted_data_avaliacao }}</span>
                </div>
                <div style="display: table; width: 100%;">
                    <span style="display: table-cell; text-align: left; color: #374151; font-weight: 600;">Status do Lote:</span>
                    <span style="display: table-cell; text-align: right; font-weight: 700; color: #047857;">{{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}</span>
                </div>
            </div>

            {{-- Painel Direito: Valores do Repasse ao Cliente --}}
            <div style="width: 52%; float: right; border: 1.5px solid #312e81; border-radius: 6px; background-color: #f8fafc; padding: 8px 12px;">
                <table class="totals">
                    <tr>
                        <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #312e81; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                            Valores a Receber pelo Lote
                        </th>
                    </tr>
                    <tr>
                        <th style="font-size: 11px;">Repasse em Créditos na Loja:</th>
                        <td style="color: #2563eb; font-size: 13px; font-weight: 800;">R$ {{ number_format($sumPayoutCredito, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th style="font-size: 11px;">Repasse em Dinheiro / PIX:</th>
                        <td style="color: #059669; font-size: 13px; font-weight: 800;">R$ {{ number_format($sumPayoutDinheiro, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 8px; color: #6b7280; text-align: right; padding-top: 3px;">
                            @if ($avaliacao->status === 'finalizada')
                                Pagamento selecionado: <strong>{{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos em Carteira' : 'Dinheiro/PIX' }}</strong>
                            @else
                                Forma de recebimento a ser confirmada na finalização do lote.
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endif

    {{-- Assinaturas do Documento --}}
    <div class="clearfix signature-section">
        <div class="signature-line" style="margin-right: 40px;">
            Assinatura do {{ $isParceiro ? 'Brechó Parceiro' : 'Fornecedor / Cliente' }}<br>
            <strong>{{ $avaliacao->user->name ?? ($avaliacao->parceiro->nome ?? 'Fornecedor(a)') }}</strong>
        </div>
        <div class="signature-line" style="float: right;">
            Responsável pelo Recebimento<br>
            <strong>Minha Mania - Moda Circular</strong>
        </div>
    </div>

    {{-- Observações --}}
    @if ($avaliacao->observacoes)
    <div class="box" style="margin-top: 15px;">
        <strong>Observações do Lote:</strong><br>
        {{ $avaliacao->observacoes }}
    </div>
    @endif

    {{-- Rodapé --}}
    <div class="footer">
        Minha Mania - Moda Circular &bull; Documento gerado em {{ now()->format('d/m/Y \à\s H:i:s') }}
    </div>

</body>
</html>
