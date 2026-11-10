<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Avaliação #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            margin-top: 30px;
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
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    @php
        $isFinanceiro = (($tipo ?? 'recibo') === 'financeiro');
    @endphp

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
                    @if ($avaliacao->tipo_compra === 'parceria')
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
                    <h3>Fornecedor / Fornecedora</h3>
                    <strong>Nome:</strong> {{ $avaliacao->user->name ?? 'Desconhecido' }}<br>
                    @if ($avaliacao->user && $avaliacao->user->apelido)
                        <strong>Apelido:</strong> {{ $avaliacao->user->apelido }}<br>
                    @endif
                    <strong>Adesão:</strong> {{ $avaliacao->tipo_cliente === 'clube' ? 'Clube' : 'Fora do Clube' }}<br>
                    @if ($avaliacao->user && $avaliacao->user->whatsapp)
                        <strong>WhatsApp:</strong> {{ $avaliacao->user->whatsapp }}<br>
                    @endif
                    @if ($avaliacao->user && $avaliacao->user->email)
                        <strong>E-mail:</strong> {{ $avaliacao->user->email }}
                    @endif
                </div>
            </td>
            <td style="padding-left: 5px;">
                <div class="box">
                    <h3>Informações da Operação</h3>
                    @if ($avaliacao->tipo_compra === 'parceria')
                        <strong>Operação:</strong> Parceria / Consignação<br>
                        @if ($avaliacao->parceiro)
                            <strong>Brechó Parceiro:</strong> {{ $avaliacao->parceiro->nome }}<br>
                        @endif
                        @if ($avaliacao->prefixo_codigo)
                            <strong>Prefixo SKU:</strong> {{ $avaliacao->prefixo_codigo }}<br>
                        @endif
                        <strong>Taxa Cadastro/Peça:</strong> R$ {{ number_format((float) $avaliacao->taxa_cadastro_peca, 2, ',', '.') }}<br>
                        <strong>Comissão Mania:</strong> {{ number_format((float) $avaliacao->comissao_parceiro_porcentagem, 1, ',', '.') }}%<br>
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

    {{-- Tabela de Peças: SEM valores individuais das peças (sem PREÇO VENDA, TAXA CURAD., REPASSE) --}}
    <table class="items-table">
        <thead>
            <tr>
                @if ($isFinanceiro)
                    {{-- Caso solicitado explicitamente como relatório financeiro detalhado por peça --}}
                    <th style="width: 35%;">Peça / Descrição</th>
                    <th style="width: 25%;">Detalhes</th>
                    <th class="text-right" style="width: 20%;">Preço</th>
                    <th class="text-right" style="width: 20%;">Repasse</th>
                @else
                    {{-- Padrão do Recibo de Avaliação: sem colunas de valores individuais --}}
                    <th class="text-center" style="width: 4%;">#</th>
                    <th class="text-center" style="width: 16%;">Código / SKU</th>
                    <th style="width: 36%;">Peça / Descrição</th>
                    <th style="width: 18%;">Categoria</th>
                    <th style="width: 26%;">Detalhes (Marca, Cor, Tam, Estado)</th>
                @endif
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
                $categoriaNome = $item->categoria ? $item->categoria->name : ($item->item && $item->item->categoria ? $item->item->categoria->name : 'Geral');

                $detalhesList = [];
                if (!empty($marcaLabel)) $detalhesList[] = $marcaLabel;
                if (!empty($item->tamanho)) $detalhesList[] = 'Tam: ' . $item->tamanho;
                if (!empty($item->cor)) $detalhesList[] = $item->cor;
                if (!empty($item->estado)) $detalhesList[] = $item->estado;
                $detalhesTxt = implode(' • ', $detalhesList);

                $payoutVal = 0.00;
                if ($isFinanceiro) {
                    if ($avaliacao->tipo_compra === 'parceria') {
                        $payoutVal = $item->payout_dinheiro;
                    } elseif ($avaliacao->status === 'finalizada') {
                        $payoutVal = $avaliacao->pagamento_escolhido === 'credito' ? $item->payout_credito : $item->payout_dinheiro;
                    } else {
                        $payoutVal = $avaliacao->tipo_cliente === 'clube' ? $item->payout_credito : $item->payout_credito;
                    }
                }
            @endphp
            <tr>
                @if ($isFinanceiro)
                    <td>
                        <strong style="color: #0f172a;">{{ $item->nome }}</strong>
                        <br><span class="font-mono" style="font-size: 9px; color: #4338ca;">{{ $itemSku }}</span>
                    </td>
                    <td>
                        {{ $detalhesTxt }}
                    </td>
                    <td class="text-right">
                        R$ {{ number_format($item->preco_venda, 2, ',', '.') }}
                    </td>
                    <td class="text-right" style="font-weight: bold; color: #047857;">
                        R$ {{ number_format($payoutVal, 2, ',', '.') }}
                    </td>
                @else
                    <td class="text-center" style="font-weight: bold; color: #475569;">
                        {{ $loop->iteration }}
                    </td>
                    <td class="text-center">
                        <span class="font-mono" style="color: #312e81; font-size: 9px;">{{ $itemSku }}</span>
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 11px;">{{ $item->nome }}</strong>
                    </td>
                    <td style="color: #334155;">
                        {{ $categoriaNome }}
                    </td>
                    <td style="color: #475569;">
                        {{ $detalhesTxt }}
                    </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Quadrado Final de Resumo e Valores Totais --}}
    <div class="clearfix" style="margin-top: 15px; margin-bottom: 20px;">
        
        {{-- Total de Peças e Dados do Lote (lado esquerdo) --}}
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

        {{-- Quadrado Final com Valores Totais do Lote (lado direito) --}}
        <div style="width: 52%; float: right; border: 1.5px solid #312e81; border-radius: 6px; background-color: #f8fafc; padding: 8px 12px;">
            @if ($avaliacao->tipo_compra === 'parceria')
                @php
                    $totalVendaLote = $avaliacao->items->sum('preco_venda');
                    $comissaoPct = (float) ($avaliacao->comissao_parceiro_porcentagem ?: 20.00);
                    $comissaoValor = $totalVendaLote * ($comissaoPct / 100.00);
                    $taxaCadTotal = (float) ($avaliacao->taxa_cadastro_peca ?: 0.00) * $avaliacao->items->count();
                    $totalRepasseLote = $avaliacao->items->sum('payout_dinheiro');
                @endphp
                <table class="totals">
                    <tr>
                        <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #312e81; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                            Repasse Estimado ao Parceiro
                        </th>
                    </tr>
                    <tr>
                        <th>Total Venda:</th>
                        <td style="color: #111827;">R$ {{ number_format($totalVendaLote, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Taxa Cadastro:</th>
                        <td style="color: #dc2626;">R$ {{ number_format($taxaCadTotal, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Comissão Mania ({{ number_format($comissaoPct, 1, ',', '.') }}%):</th>
                        <td style="color: #dc2626;">R$ {{ number_format($comissaoValor, 2, ',', '.') }}</td>
                    </tr>
                    <tr class="grand-total">
                        <th style="font-size: 12px; font-weight: bold; color: #1e1b4b;">Valor do Repasse:</th>
                        <td style="color: #047857; font-size: 14px; font-weight: 800;">R$ {{ number_format($totalRepasseLote, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 8px; color: #6b7280; text-align: right; padding-top: 3px;">
                            Regime de <strong>Consignação</strong> (Acerto pós-venda via Carteira)
                        </td>
                    </tr>
                </table>
            @else
                @php
                    $sumPayoutCredito = $avaliacao->items->sum('payout_credito');
                    $sumPayoutDinheiro = $avaliacao->items->sum('payout_dinheiro');
                @endphp
                <table class="totals">
                    <tr>
                        <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #312e81; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px;">
                            Total Repasse Fornecedor
                        </th>
                    </tr>
                    <tr>
                        <th style="font-size: 11px;">Repasse Crédito:</th>
                        <td style="color: #2563eb; font-size: 13px; font-weight: 800;">R$ {{ number_format($sumPayoutCredito, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th style="font-size: 11px;">Repasse Dinheiro:</th>
                        <td style="color: #059669; font-size: 13px; font-weight: 800;">R$ {{ number_format($sumPayoutDinheiro, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="font-size: 8px; color: #6b7280; text-align: right; padding-top: 3px;">
                            @if ($avaliacao->status === 'finalizada')
                                Pago via <strong>{{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos em Carteira' : 'Dinheiro/PIX' }}</strong>
                            @else
                                Aguardando finalização do lote (Rascunho)
                            @endif
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    </div>

    {{-- Assinaturas do Recibo --}}
    <div class="clearfix signature-section">
        <div class="signature-line" style="margin-right: 40px;">
            Assinatura do Fornecedor / Cliente<br>
            <strong>{{ $avaliacao->user->name ?? 'Fornecedor(a)' }}</strong>
        </div>
        <div class="signature-line" style="float: right;">
            Responsável pelo Recebimento<br>
            <strong>Minha Mania - Moda Circular</strong>
        </div>
    </div>

    {{-- Observações --}}
    @if ($avaliacao->observacoes)
    <div class="box" style="margin-top: 20px;">
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
