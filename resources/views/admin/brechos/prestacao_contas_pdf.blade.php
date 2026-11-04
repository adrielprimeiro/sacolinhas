<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Prestação de Contas - {{ $brecho->nome }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 8px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
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
            color: #4f46e5;
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
            margin-bottom: 4px;
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
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            border-radius: 6px;
            background-color: #f9fafb;
            font-size: 11px;
            line-height: 1.45;
        }
        .box h3 {
            margin-top: 0;
            font-size: 11px;
            color: #4338ca;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .kpi-table td {
            width: 25%;
            padding: 0 3px;
        }
        .kpi-card {
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
        }
        .kpi-title {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #6b7280;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #e5e7eb;
            padding: 5px 6px;
            text-align: left;
        }
        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            width: 55%;
            float: right;
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
            border-top: 2px solid #4f46e5;
            padding-top: 6px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .signature {
            margin-top: 25px;
            width: 220px;
            border-top: 1px solid #9ca3af;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding-top: 4px;
            float: left;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    {{-- Cabeçalho Padrão de Avaliação --}}
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
                <div class="lote">Prestação de Contas & Consignação</div>
                <p style="font-size: 13px; font-weight: bold; color: #111827; margin: 2px 0;">{{ $brecho->nome }}</p>
                <p>Data de Emissão: {{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>

    {{-- Informações da Operação & Dados do Parceiro --}}
    <table class="details-table">
        <tr>
            <td style="padding-right: 5px;">
                <div class="box">
                    <h3>Dados do Parceiro (Fornecedor)</h3>
                    <strong>Brechó Parceiro:</strong> {{ $brecho->nome }}<br>
                    <strong>Fornecedor Vinculado:</strong> {{ $brecho->fornecedor_nome ?: ($user ? $user->name : 'Não vinculado') }}<br>
                    <strong>Contato / WhatsApp:</strong> {{ $brecho->telefone ?: ($user ? $user->whatsapp : 'Não informado') }}<br>
                    <strong>Chave Pix:</strong> {{ $brecho->chave_pix ?: ($user ? $user->chave_pix : 'Não informada') }}<br>
                    <strong>Status no Sistema:</strong> {{ $brecho->ativo ? 'Ativo' : 'Inativo' }}
                </div>
            </td>
            <td style="padding-left: 5px;">
                <div class="box">
                    <h3>Condições Comerciais & Saldo</h3>
                    <strong>Prefixo SKU:</strong> {{ $brecho->prefixo_codigo ?: 'PAR' }}<br>
                    <strong>Comissão da Mania:</strong> {{ number_format((float)($brecho->comissao_porcentagem ?: 20), 1, ',', '.') }}% sobre a venda<br>
                    <strong>Taxa Cadastro / Peça:</strong> R$ {{ number_format((float)($brecho->taxa_cadastro_peca ?: 1.80), 2, ',', '.') }}<br>
                    <strong>Saldo Atual na Carteira:</strong> <span style="font-weight: bold; color: {{ $saldoCarteira > 0 ? '#047857' : ($saldoCarteira < 0 ? '#dc2626' : '#111827') }};">R$ {{ number_format($saldoCarteira, 2, ',', '.') }}</span><br>
                    <strong>Total Já Repassado (Pix):</strong> R$ {{ number_format($totalRepassadoPago, 2, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Cards com Indicadores Financeiros (KPIs) --}}
    <table class="kpi-table">
        <tr>
            <td style="padding-left: 0;">
                <div class="kpi-card" style="border-top: 3px solid #4f46e5;">
                    <div class="kpi-title">Saldo na Carteira</div>
                    <div class="kpi-value" style="color: {{ $saldoCarteira > 0 ? '#047857' : ($saldoCarteira < 0 ? '#dc2626' : '#111827') }};">
                        R$ {{ number_format($saldoCarteira, 2, ',', '.') }}
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card" style="border-top: 3px solid #111827;">
                    <div class="kpi-title">Total Bruto Vendido</div>
                    <div class="kpi-value" style="color: #111827;">
                        R$ {{ number_format($valorBrutoVendido, 2, ',', '.') }}
                    </div>
                </div>
            </td>
            <td>
                <div class="kpi-card" style="border-top: 3px solid #dc2626;">
                    <div class="kpi-title">Comissão Mania ({{ number_format((float)($brecho->comissao_porcentagem ?: 20), 0) }}%)</div>
                    <div class="kpi-value" style="color: #dc2626;">
                        R$ {{ number_format($comissaoManiaVendido, 2, ',', '.') }}
                    </div>
                </div>
            </td>
            <td style="padding-right: 0;">
                <div class="kpi-card" style="border-top: 3px solid #047857;">
                    <div class="kpi-title">Repasse pelas Vendas</div>
                    <div class="kpi-value" style="color: #047857;">
                        R$ {{ number_format($valorRepasseVendido, 2, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tabela de Peças em Consignação --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 22%;">SKU / Código</th>
                <th style="width: 32%;">Produto / Detalhes</th>
                <th class="text-center" style="width: 14%;">Status</th>
                <th class="text-right" style="width: 16%;">Preço Venda</th>
                <th class="text-right" style="width: 16%;">Repasse</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
            @php
                $statusLabel = 'Em Estoque';
                $statusColor = '#2563eb';
                if ($item->status === 'vendido') {
                    $statusLabel = 'Vendido';
                    $statusColor = '#047857';
                } elseif (in_array($item->status, ['sacolinha', 'em_sacolinha', 'solicitado na live']) || !empty($item->sacolinha_info)) {
                    $statusLabel = 'Sacolinha';
                    $statusColor = '#d97706';
                }

                $repasseUnit = (float) $item->custo;
            @endphp
            <tr>
                <td>
                    <strong style="font-family: monospace; font-size: 10px; color: #1e1b4b;">{{ $item->codigo }}</strong>
                </td>
                <td>
                    <strong>{{ $item->nome_do_produto }}</strong>
                    @if ($item->marca || $item->tamanho || $item->cor)
                        <br><span style="font-size: 8.5px; color: #6b7280;">{{ $item->marca }} • {{ $item->cor ?: '-' }} • Tam: {{ $item->tamanho ?: '-' }}</span>
                    @endif
                </td>
                <td class="text-center" style="font-weight: bold; color: {{ $statusColor }}; font-size: 9px;">
                    {{ $statusLabel }}
                </td>
                <td class="text-right">
                    R$ {{ number_format((float)$item->preco, 2, ',', '.') }}
                </td>
                <td class="text-right" style="font-weight: bold; color: #047857;">
                    R$ {{ number_format($repasseUnit, 2, ',', '.') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center" style="padding: 15px; color: #6b7280;">
                    Nenhuma peça encontrada para os filtros selecionados.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Resumo Geral & Assinaturas --}}
    <div class="clearfix">
        <div class="signature">
            Assinatura do Parceiro / Responsável
        </div>

        <table class="totals">
            <tr>
                <th colspan="2" style="text-align: right; text-transform: uppercase; font-size: 10px; color: #4338ca; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px;">
                    Resumo Financeiro de Consignação
                </th>
            </tr>
            <tr>
                <th>Peças Listadas / Total:</th>
                <td style="color: #111827;">{{ $items->count() }} de {{ $totalPecas }} peças ({{ $pecasVendidas }} vendidas)</td>
            </tr>
            <tr>
                <th>Total Bruto Vendido:</th>
                <td style="color: #111827;">R$ {{ number_format($valorBrutoVendido, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Comissão Mania ({{ number_format((float)($brecho->comissao_porcentagem ?: 20), 1, ',', '.') }}%):</th>
                <td style="color: #dc2626;">- R$ {{ number_format($comissaoManiaVendido, 2, ',', '.') }}</td>
            </tr>
            @if ($taxaCadastroTotal > 0)
            <tr>
                <th>Taxa Cadastro ({{ $totalPecas }} peças):</th>
                <td style="color: #6b7280;">R$ {{ number_format($taxaCadastroTotal, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr>
                <th>Repasse Líquido pelas Vendas:</th>
                <td style="color: #047857;">R$ {{ number_format($valorRepasseVendido, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Repasses Já Pagos (Pix):</th>
                <td style="color: #dc2626;">- R$ {{ number_format($totalRepassadoPago, 2, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <th>Saldo Atual na Carteira:</th>
                <td style="color: {{ $saldoCarteira > 0 ? '#047857' : ($saldoCarteira < 0 ? '#dc2626' : '#111827') }}; font-size: 13px;">
                    R$ {{ number_format($saldoCarteira, 2, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 8.5px; color: #6b7280; text-align: right; padding-top: 3px;">
                    Regime de <strong>Consignação</strong> (Acerto pós-venda via Carteira)
                </td>
            </tr>
        </table>
    </div>

    {{-- Últimas Movimentações da Carteira --}}
    @if ($carteiraMovimentacoes->isNotEmpty())
        <div style="margin-top: 20px; page-break-inside: avoid;">
            <h4 style="font-size: 10px; text-transform: uppercase; color: #4338ca; margin-bottom: 4px; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px;">
                Extrato Recente da Carteira (Últimos Repasses & Créditos)
            </h4>
            <table class="items-table" style="font-size: 9px; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 20%;">Data / Hora</th>
                        <th style="width: 16%;">Tipo</th>
                        <th style="width: 44%;">Descrição</th>
                        <th class="text-right" style="width: 20%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($carteiraMovimentacoes->take(8) as $mov)
                    <tr>
                        <td>{{ $mov->data_movimentacao ? \Carbon\Carbon::parse($mov->data_movimentacao)->format('d/m/Y H:i') : $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td style="font-weight: bold; color: {{ $mov->tipo_movimentacao === 'credito' ? '#047857' : '#dc2626' }};">
                            {{ $mov->tipo_movimentacao === 'credito' ? 'Crédito (+)' : 'Débito / Pix (-)' }}
                        </td>
                        <td>{{ $mov->descricao ?: ($mov->referencia_tipo ?: 'Lançamento em conta') }}</td>
                        <td class="text-right" style="font-weight: bold; color: {{ $mov->tipo_movimentacao === 'credito' ? '#047857' : '#dc2626' }};">
                            {{ $mov->tipo_movimentacao === 'credito' ? '+' : '-' }} R$ {{ number_format((float)$mov->valor, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        <p>Mania - Prestação de Contas gerada em {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>
</html>
