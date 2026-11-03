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
        @php
            $subtotalMania = 0.0;
            $subtotalParceiros = 0.0;
            $subtotalParceirosPendente = 0.0;
            $hasParceiro = false;
            foreach($itensSacolinha as $it) {
                $ehParceiro = (!empty($it->parceiro_id) && $it->parceiro_id > 1) || !empty($it->origem_parceiro);
                if ($ehParceiro) {
                    $hasParceiro = true;
                    $subtotalParceiros += (float) $it->price;
                    if (empty($it->pago_a_vista)) {
                        $subtotalParceirosPendente += (float) $it->price;
                    }
                } else {
                    $subtotalMania += (float) $it->price;
                }
            }
            $isClube = method_exists($cliente, 'isClubeAtivo') ? $cliente->isClubeAtivo() : false;
        @endphp
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
                @php
                    $ehParceiro = (!empty($item->parceiro_id) && $item->parceiro_id > 1) || !empty($item->origem_parceiro);
                    $nomeParceiro = $item->parceiro_nome ?? ($item->origem_parceiro ?? 'Brechó Parceiro');
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
                @if($hasParceiro)
                    @if($subtotalMania > 0)
                    <tr style="background: #fafafa; font-size: 11px;">
                        <td colspan="3" style="text-align: right;"><strong>Subtotal Minha Mania (Sacolinha):</strong></td>
                        <td class="money"><strong>R$ {{ number_format($subtotalMania, 2, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                    @endif
                    <tr style="background: #fffbeb; font-size: 11px; color: #92400e;">
                        <td colspan="3" style="text-align: right;"><strong>Subtotal Peças de Parceiro (Exigido À Vista):</strong></td>
                        <td class="money" style="color: #92400e;"><strong>R$ {{ number_format($subtotalParceiros, 2, ',', '.') }}</strong></td>
                        <td></td>
                    </tr>
                @endif
                <tr class="total">
                    <td colspan="3"><strong>TOTAL GERAL:</strong></td>
                    <td class="money"><strong>R$ {{ number_format($valorTotal, 2, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @if($hasParceiro)
        <div style="margin-top: 15px; border: 2px solid #d97706; background-color: #fffbeb; border-radius: 8px; padding: 12px; color: #78350f;">
            <div style="font-weight: bold; font-size: 12px; margin-bottom: 6px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px;">
                ⚠️ Informações de Pagamento — Peças de Brechós Parceiros
            </div>
            <p style="margin: 4px 0 8px 0; font-size: 11px; line-height: 1.4; color: #451a03;">
                @if($isClube)
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
    @else
        <p style="text-align: center; padding: 40px; background: #f8f9fa;">
            Nenhum item encontrado na sacolinha
        </p>
    @endif
</body>
</html>