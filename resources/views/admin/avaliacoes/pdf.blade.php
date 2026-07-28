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
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb; /* Blue border */
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #2563eb;
        }
        .header p {
            margin: 3px 0 0;
            color: #666;
            font-size: 11px;
        }
        .header .lote {
            font-weight: bold;
            color: #4f46e5;
            font-size: 14px;
            margin-top: 5px;
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

    <div class="header">
        <h1>Mania</h1>
        <p>Clube de Moda Circular</p>
        <div class="lote">Recibo de Entrada - Lote #{{ str_pad($avaliacao->id, 5, '0', STR_PAD_LEFT) }}</div>
        <p>Data da Avaliação: {{ $avaliacao->formatted_data_avaliacao }}</p>
    </div>

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
                    <strong>Operação:</strong> {{ $avaliacao->tipo_compra === 'avaliados' ? 'Regime de Avaliação e Desapego' : 'Compra Direta' }}<br>
                    <strong>Frete da Remessa:</strong> {{ $avaliacao->formatted_frete }}<br>
                    <strong>Quantidade de Peças:</strong> {{ $avaliacao->items->count() }}<br>
                    <strong>Forma de Repasse:</strong> 
                    @if ($avaliacao->status === 'finalizada')
                        {{ $avaliacao->pagamento_escolhido === 'credito' ? 'Créditos na Loja' : 'Dinheiro/PIX' }}
                    @else
                        Pendente (Lote em Rascunho)
                    @endif
                    <br>
                    <strong>Status:</strong> {{ ucfirst($avaliacao->status_label ?? $avaliacao->status) }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Peça / Descrição</th>
                <th class="text-center" style="width: 25%;">Marca</th>
                <th class="text-center" style="width: 25%;">Cor/Tamanho</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($avaliacao->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->nome }}</strong><br>
                    <span style="color: #6b7280; font-size: 9px;">{{ $item->categoria ? $item->categoria->name : 'Sem categoria' }}</span>
                </td>
                <td class="text-center">
                    {{ $item->marca === 'sem_marca' ? 'Sem Marca' : ($item->marca === 'de_marca' ? 'De Marca' : 'Farm') }}
                </td>
                <td class="text-center">
                    {{ $item->cor ?: '-' }} / {{ $item->tamanho ?: '-' }}
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="signature">
            Assinatura do Fornecedor / Responsável
        </div>

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
