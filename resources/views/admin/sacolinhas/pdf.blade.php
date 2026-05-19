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
        <h1>Sacolinha do Cliente</h1>
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
                    <strong>Total de Itens:</strong> {{ $itens->count() }}<br>
                    <strong>Saldo da Carteira:</strong> 
                    <span style="color: {{ $valorPago >= 0 ? '#10b981' : '#ef4444' }}; font-weight: bold;">
                        R$ {{ number_format($valorPago, 2, ',', '.') }}
                    </span>
                    <br>
                    <strong>Observação:</strong> O saldo atual será consolidado no fechamento.
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
            <tr>
                <td>{{ $item->codigo ?? 'N/A' }}</td>
                <td><strong>{{ $item->nome_do_produto }}</strong></td>
                <td>{{ implode(' • ', array_filter([$item->marca])) }}</td>
                <td class="text-center">{{ $item->tamanho ?? '-' }}</td>
                <td class="text-right">R$ {{ number_format($item->price ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals">
            <tr>
                <th>Subtotal Itens:</th>
                <td>R$ {{ number_format($total, 2, ',', '.') }}</td>
            </tr>
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
            <tr class="grand-total">
                <th>Total Estimado a Pagar:</th>
                <td>R$ {{ number_format(max(0, $total - $valorPago), 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Este é um documento de conferência impresso.</p>
    </div>

</body>
</html>
