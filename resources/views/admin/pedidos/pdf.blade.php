<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .details-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .details-table td {
            vertical-align: top;
            width: 50%;
        }
        .box {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin-right: 10px;
            background-color: #f9f9f9;
        }
        .box h3 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f2f2f2;
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
            padding: 5px 8px;
        }
        .totals th {
            text-align: right;
            font-weight: normal;
        }
        .totals td {
            text-align: right;
            font-weight: bold;
        }
        .totals .grand-total th, .totals .grand-total td {
            font-size: 18px;
            border-top: 2px solid #333;
            padding-top: 10px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    @php
        $itens = DB::table('items_pedido as ip')
            ->join('items as i', 'i.id', '=', 'ip.item_id')
            ->where('ip.pedido_id', $pedido->id)
            ->select([
                'ip.quantidade',
                'ip.preco_unitario',
                'ip.valor_total',
                'i.nome_do_produto',
                'i.codigo',
                'i.tamanho'
            ])
            ->get();

        $subtotal = (float) $itens->sum('valor_total');
        $frete = (float) ($pedido->valor_frete ?? 0);
        $desconto = (float) ($pedido->valor_desconto ?? 0);
        $saldoUsado = (float) ($pedido->valor_saldo_utilizado ?? 0);
        $totalBruto = max(0, $subtotal + $frete - $desconto);
        $valorPagar = max(0, $totalBruto - $saldoUsado);
        
        $cliente = DB::table('users')->where('id', $pedido->user_id)->first();
    @endphp

    <div class="header">
        <h1>Pedido #{{ $pedido->numero_pedido ?? $pedido->id }}</h1>
        <p>Data do Pedido: {{ !empty($pedido->data_pedido) ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y H:i') : ($pedido->created_at ? $pedido->created_at->format('d/m/Y H:i') : 'N/A') }}</p>
    </div>

    <table class="details-table">
        <tr>
            <td>
                <div class="box">
                    <h3>Dados do Cliente</h3>
                    <strong>Nome:</strong> {{ $cliente->name ?? 'N/A' }}<br>
                    <strong>Email:</strong> {{ $cliente->email ?? 'N/A' }}<br>
                    <strong>Telefone:</strong> {{ $cliente->telefone ?? 'N/A' }}
                </div>
            </td>
            <td>
                <div class="box" style="margin-right: 0;">
                    <h3>Informações do Pedido</h3>
                    <strong>Status:</strong> {{ ucfirst($pedido->status_pedido ?? 'N/A') }}<br>
                    <strong>Pagamento:</strong> {{ !empty($pedido->forma_pagamento) ? str_replace('_', ' ', ucfirst($pedido->forma_pagamento)) : 'Não definido' }}<br>
                    <strong>Status Pgto:</strong> {{ ucfirst($pedido->status_pagamento ?? 'N/A') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Produto</th>
                <th class="text-center">Tam</th>
                <th class="text-center">Qtd</th>
                <th class="text-right">V. Unit</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($itens as $item)
            <tr>
                <td>{{ $item->codigo ?? 'N/A' }}</td>
                <td>{{ $item->nome_do_produto }}</td>
                <td class="text-center">{{ $item->tamanho ?? '-' }}</td>
                <td class="text-center">{{ $item->quantidade }}</td>
                <td class="text-right">R$ {{ number_format($item->preco_unitario, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($item->valor_total, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <table class="totals">
            <tr>
                <th>Subtotal:</th>
                <td>R$ {{ number_format($subtotal, 2, ',', '.') }}</td>
            </tr>
            @if($frete > 0)
            <tr>
                <th>Frete:</th>
                <td>R$ {{ number_format($frete, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($desconto > 0)
            <tr>
                <th>Descontos:</th>
                <td>- R$ {{ number_format($desconto, 2, ',', '.') }}</td>
            </tr>
            @endif
            @if($saldoUsado > 0)
            <tr>
                <th>Saldo Utilizado:</th>
                <td>- R$ {{ number_format($saldoUsado, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="grand-total">
                <th>Valor a Pagar:</th>
                <td>R$ {{ number_format($valorPagar, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Obrigado por comprar conosco!</p>
        <p>Este é um documento gerado automaticamente.</p>
    </div>

</body>
</html>
