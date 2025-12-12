<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sacolinha - {{ $cliente->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .info { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th { background: #f0f0f0; padding: 8px; border: 1px solid #ccc; font-weight: bold; }
        .table td { padding: 8px; border: 1px solid #ccc; }
        .table tbody tr:nth-child(even) { background: #f9f9f9; }
        .money { text-align: right; font-weight: bold; color: #27ae60; }
        .total { background: #e8f4fd !important; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎒 Sacolinha</h1>
        <p><strong>Cliente:</strong> {{ $cliente->name }}</p>
        <p><strong>Email:</strong> {{ $cliente->email }}</p>
        <p><strong>Data:</strong> {{ date('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info">
        <p><strong>Total de Itens:</strong> {{ $totalItens }}</p>
        <p><strong>Valor Total:</strong> R$ {{ number_format($valorTotal, 2, ',', '.') }}</p>
    </div>

    @if($itensSacolinha->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Produto</th>
                    <th>Detalhes</th>
                    <th>Preço</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($itensSacolinha as $item)
                <tr>
                    <td>{{ $item->codigo ?? 'N/A' }}</td>
                    <td><strong>{{ $item->nome_do_produto }}</strong></td>
                    <td>
                        @php
                            $detalhes = [];
                            if($item->marca) $detalhes[] = $item->marca;
                            if($item->estado) $detalhes[] = $item->estado;
                            if($item->cor) $detalhes[] = $item->cor;
                            if($item->tamanho) $detalhes[] = 'Tam: ' . $item->tamanho;
                        @endphp
                        {{ implode(' • ', $detalhes) }}
                    </td>
                    <td class="money">R$ {{ number_format($item->price, 2, ',', '.') }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->add_at)->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3"><strong>TOTAL GERAL:</strong></td>
                    <td class="money"><strong>R$ {{ number_format($valorTotal, 2, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; padding: 40px; background: #f8f9fa;">
            Nenhum item encontrado na sacolinha
        </p>
    @endif
</body>
</html>