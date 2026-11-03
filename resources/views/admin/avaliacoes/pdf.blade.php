<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Avaliação #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-table .logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 5px;
        }
        .header-table h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: #2563eb;
        }
        .header-table p {
            margin: 0;
            color: #666;
            font-size: 11px;
        }
        .header-table .lote {
            font-weight: bold;
            color: #4f46e5;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .details-table {
            width: 100%;
            margin-bottom: 15px;
        }
        .details-table td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            border: 1px solid #e5e7eb;
            padding: 10px;
            border-radius: 6px;
            background-color: #f9fafb;
        }
        .box h3 {
            margin-top: 0;
            font-size: 12px;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        .items-table th, .items-table td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            text-align: left;
        }
        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 10px;
            text-transform: uppercase;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            width: 50%;
            float: right;
            border-collapse: collapse;
        }
        .totals th, .totals td {
            padding: 4px 6px;
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
            font-size: 14px;
            border-top: 2px solid #2563eb;
            padding-top: 8px;
            color: #2563eb;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
        .signature {
            margin-top: 30px;
            width: 250px;
            border-top: 1px solid #9ca3af;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding-top: 5px;
            float: left;
        }
    </style>
</head>
<body>

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
                        Recibo de Entrada - Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}
                    @endif
                </div>
                <p>Data da Avaliação: {{ $avaliacao->formatted_data_avaliacao }}</p>
            </td>
        </tr>
    </table>

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
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }}<br>
                        <strong>Forma de Repasse:</strong> Consignação (Acerto Pós-Venda)<br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @elseif ($avaliacao->tipo_compra === 'avaliados')
                        <strong>Operação:</strong> Regime de Avaliação e Desapego<br>
                        <strong>Frete da Remessa:</strong> {{ $avaliacao->formatted_frete }}<br>
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }}<br>
                        <strong>Forma de Repasse:</strong> 
                        @if ($avaliacao->status === 'finalizada')
                            {{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos na Loja' : 'Dinheiro/PIX' }}
                        @else
                            Pendente (Lote em Rascunho)
                        @endif
                        <br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @else
                        <strong>Operação:</strong> Compra Direta<br>
                        <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }}<br>
                        <strong>Forma de Repasse:</strong> 
                        @if ($avaliacao->status === 'finalizada')
                            {{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos na Loja' : 'Dinheiro/PIX' }}
                        @else
                            Pendente (Lote em Rascunho)
                        @endif
                        <br>
                        <strong>Status Lote:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 35%;">Peça / Descrição</th>
                <th style="width: 25%;">Detalhes</th>
                <th class="text-right" style="width: 20%;">Preço</th>
                <th class="text-right" style="width: 20%;">Repasse</th>
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
                
                $payoutVal = 0.00;
                if ($avaliacao->tipo_compra === 'parceria') {
                    $payoutVal = $item->payout_dinheiro;
                } elseif ($avaliacao->status === 'finalizada') {
                    $payoutVal = $avaliacao->pagamento_escolhido === 'credito' ? $item->payout_credito : $item->payout_dinheiro;
                } else {
                    $payoutVal = $avaliacao->tipo_cliente === 'clube' ? $item->payout_credito : $item->payout_credito;
                }

                $detalhesList = [];
                if (!empty($marcaLabel)) $detalhesList[] = $marcaLabel;
                if (!empty($item->estado)) $detalhesList[] = $item->estado;
                if (!empty($item->cor)) $detalhesList[] = $item->cor;
                if (!empty($item->tamanho)) $detalhesList[] = 'Tam: ' . $item->tamanho;
                $detalhesTxt = implode(' • ', $detalhesList);
            @endphp
            <tr>
                <td>
                    <strong>{{ $item->nome }}</strong>
                    <br><span style="font-size: 9px; color: #4f46e5; font-family: monospace;">{{ $itemSku }}</span>
                </td>
                <td>
                    {{ $detalhesTxt }}
                </td>
                <td class="text-right">
                    R$ {{ number_format($item->preco_venda, 2, ',', '.') }}
                </td>
                <td class="text-right" style="font-weight: bold; color: #4338ca;">
                    R$ {{ number_format($payoutVal, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="signature">
            Assinatura do Fornecedor / Responsável
        </div>

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
                    <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #4338ca; font-weight: bold;">
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
                    <th>Comissão Mania:</th>
                    <td style="color: #dc2626;">R$ {{ number_format($comissaoValor, 2, ',', '.') }}</td>
                </tr>
                <tr class="grand-total">
                    <th>Valor do Repasse:</th>
                    <td style="color: #047857; font-size: 14px;">R$ {{ number_format($totalRepasseLote, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 9px; color: #6b7280; text-align: right; padding-top: 4px;">
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
                    <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #6b7280;">Total Repasse Fornecedor</th>
                </tr>
                <tr>
                    <th>Repasse Crédito:</th>
                    <td style="color: #2563eb;">R$ {{ number_format($sumPayoutCredito, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Repasse Dinheiro:</th>
                    <td style="color: #059669;">R$ {{ number_format($sumPayoutDinheiro, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 9px; color: #9ca3af; text-align: right;">
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

    @if ($avaliacao->observacoes)
    <div class="box" style="margin-top: 15px;">
        <strong>Observações do Lote:</strong><br>
        {{ $avaliacao->observacoes }}
    </div>
    @endif

    <div class="footer">
        <p>Mania - Documento gerado em {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>
</html>
