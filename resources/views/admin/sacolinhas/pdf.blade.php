<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sacolinha - {{ $user->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #5b21b6; /* Purple border */
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            color: #5b21b6;
        }
        .header p {
            margin: 3px 0 0;
            color: #666;
            font-size: 12px;
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
            font-size: 14px;
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
        }
        .items-table th, .items-table td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
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
        .totals .wallet-row th, .totals .wallet-row td {
            font-size: 12px;
        }
        .totals .grand-total th, .totals .grand-total td {
            font-size: 16px;
            border-top: 2px solid #5b21b6;
            padding-top: 8px;
            color: #5b21b6;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Sacolinha do Cliente - {{ $brechoAtual->nome ?? 'Minha Mania' }}</h1>
        <p>Gerado em: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="details-table">
        <tr>
            <td style="padding-right: 8px;">
                <div class="box">
                    <h3>Dados do Cliente</h3>
                    <strong>Nome:</strong> {{ $user->name ?? 'N/A' }}<br>
                    <strong>WhatsApp:</strong> {{ $user->whatsapp ?? '—' }}<br>
                    <strong>E-mail:</strong> {{ $user->email ?? '—' }}
                </div>
            </td>
            <td style="padding-left: 8px;">
                <div class="box">
                    <h3>Resumo da Sacola</h3>
                    <strong>Brechó:</strong> {{ $brechoAtual->nome ?? 'Minha Mania' }}<br>
                    <strong>Total de Itens:</strong> {{ $itens->count() }}<br>
                    @if(empty($isParceiro) && (($brechoAtual->id ?? 1) == 1))
                    <strong>Saldo da Carteira:</strong> 
                    <span style="color: {{ $valorPago >= 0 ? '#10b981' : '#ef4444' }}; font-weight: bold;">
                        R$ {{ number_format($valorPago, 2, ',', '.') }}
                    </span>
                    <br>
                    <strong>Observação:</strong> O saldo atual será consolidado no fechamento.
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th>Produto</th>
                <th>Detalhes</th>
                <th class="text-center" style="width: 10%;">Tam</th>
                <th class="text-right" style="width: 18%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            @php
                $ehParceiro = (!empty($item->parceiro_id) && $item->parceiro_id > 1) || !empty($item->origem_parceiro);
                $nomeParceiro = $item->parceiro_nome ?: ($item->origem_parceiro ?: 'Brechó Parceiro');
            @endphp
            <tr>
                <td>{{ $item->codigo ?? 'N/A' }}</td>
                <td>
                    <strong>{{ $item->nome_do_produto }}</strong>
                    @if($ehParceiro)
                        <div style="margin-top: 3px;">
                            <span style="display:inline-block; padding: 1px 4px; font-size: 9px; font-weight: bold; background-color: #fef3c7; color: #92400e; border: 1px solid #f59e0b; border-radius: 3px;">
                                PARCEIRO: {{ $nomeParceiro }}
                            </span>
                            @if(!empty($item->pago_a_vista))
                                <span style="display:inline-block; padding: 1px 4px; font-size: 9px; font-weight: bold; background-color: #d1fae5; color: #065f46; border: 1px solid #10b981; border-radius: 3px;">
                                    ✓ PAGO À VISTA (PIX)
                                </span>
                            @else
                                <span style="display:inline-block; padding: 1px 4px; font-size: 9px; font-weight: bold; background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; border-radius: 3px;">
                                    ⚡ EXIGE PAGAMENTO À VISTA
                                </span>
                            @endif
                        </div>
                    @endif
                </td>
                <td>{{ implode(' • ', array_filter([$item->marca])) }}</td>
                <td class="text-center">{{ $item->tamanho ?? '-' }}</td>
                <td class="text-right">R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals">
            @if(!empty($hasParceiro) && $hasParceiro)
                @if($subtotalMania > 0)
                <tr>
                    <th>Subtotal Minha Mania:</th>
                    <td>R$ {{ number_format($subtotalMania, 2, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="color: #92400e;">
                    <th>Subtotal Parceiros (À Vista):</th>
                    <td>R$ {{ number_format($subtotalParceiros, 2, ',', '.') }}</td>
                </tr>
            @else
                <tr>
                    <th>Subtotal Itens:</th>
                    <td>R$ {{ number_format($total, 2, ',', '.') }}</td>
                </tr>
            @endif

            @if(empty($isParceiro) && (($brechoAtual->id ?? 1) == 1))
                @if($valorPago > 0)
                <tr class="wallet-row" style="color: #059669;">
                    <th>Saldo Utilizado (Desconto):</th>
                    <td>- R$ {{ number_format($valorPago, 2, ',', '.') }}</td>
                </tr>
                @elseif($valorPago < 0)
                <tr class="wallet-row" style="color: #dc2626;">
                    <th>Dívida Anterior Embutida:</th>
                    <td>+ R$ {{ number_format(abs($valorPago), 2, ',', '.') }}</td>
                </tr>
                @endif
            @endif
            <tr class="grand-total">
                <th>Total Geral:</th>
                <td>R$ {{ number_format((empty($isParceiro) && (($brechoAtual->id ?? 1) == 1)) ? max(0, $total - $valorPago) : $total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($hasParceiro) && $hasParceiro)
    <div style="margin-top: 25px; border: 2px solid #d97706; background-color: #fffbeb; border-radius: 8px; padding: 12px; color: #78350f;">
        <div style="font-weight: bold; font-size: 12px; margin-bottom: 6px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px;">
            ⚠️ Informações de Pagamento — Peças de Brechós Parceiros
        </div>
        <p style="margin: 4px 0 8px 0; font-size: 11px; line-height: 1.4; color: #451a03;">
            @if(!empty($isClube) && $isClube)
                Você é membro do <strong>Clube Mania</strong>! Suas peças Minha Mania (R$ {{ number_format($subtotalMania, 2, ',', '.') }}) permanecem na sua sacolinha aberta para pagamento no fechamento.<br>
                <strong>No entanto, peças de brechós parceiros (consignação) pertencem a lojas parceiras e NÃO podem acumular na sacolinha: DEVEM SER PAGAS À VISTA IMEDIATAMENTE VIA PIX.</strong>
            @else
                Itens de brechós parceiros (consignação) totalizam <strong>R$ {{ number_format($subtotalParceiros, 2, ',', '.') }}</strong> e <strong>devem ser pagos à vista imediatamente via Pix</strong>.
            @endif
        </p>
        <table style="width: 100%; border-collapse: collapse; background: #ffffff; border: 1px solid #fde68a; border-radius: 6px; margin: 0;">
            <tr style="border-bottom: 1px solid #fef3c7;">
                <td style="padding: 6px 10px; font-size: 11px; color: #374151; width: 45%; border: none;"><strong>Total Peças de Parceiro À Vista:</strong></td>
                <td style="padding: 6px 10px; text-align: right; font-size: 12px; border: none;">
                    @if($subtotalParceirosPendente > 0)
                        <span style="color:#b91c1c; font-size:13px; font-weight:bold;">R$ {{ number_format($subtotalParceirosPendente, 2, ',', '.') }} (Pendente)</span>
                    @else
                        <span style="color:#047857; font-size:13px; font-weight:bold;">R$ {{ number_format($subtotalParceiros, 2, ',', '.') }} (Quitado via Pix ✓)</span>
                    @endif
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #fef3c7;">
                <td style="padding: 6px 10px; font-size: 11px; color: #374151; border: none;"><strong>Chave Pix Minha Mania:</strong></td>
                <td style="padding: 6px 10px; text-align: right; font-size: 12px; font-weight: bold; font-family: monospace; color: #1e3a8a; border: none;">mania@maniademelissa.com</td>
            </tr>
            <tr>
                <td style="padding: 6px 10px; font-size: 10px; color: #6b7280; border: none;"><strong>Favorecido:</strong> Minha Mania Brechó</td>
                <td style="padding: 6px 10px; text-align: right; font-size: 10px; color: #6b7280; border: none;">Envie o comprovante para nosso WhatsApp para validação imediata</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Este é um documento de conferência impresso.</p>
    </div>

</body>
</html>
