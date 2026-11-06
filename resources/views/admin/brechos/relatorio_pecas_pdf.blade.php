<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Peças Cadastradas - Brechós Parceiros</title>
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
            margin-bottom: 14px;
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
            margin: 0 0 2px 0;
            font-size: 20px;
            color: #312e81;
            font-weight: bold;
        }
        .header-table p {
            margin: 0;
            color: #4b5563;
            font-size: 11px;
        }
        .header-table .report-title {
            font-weight: 800;
            color: #312e81;
            font-size: 15px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-table .report-subtitle {
            font-size: 12px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .kpi-table {
            width: 100%;
            margin-bottom: 16px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .kpi-td {
            width: 25%;
            vertical-align: top;
            padding: 0;
        }
        .kpi-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .kpi-title {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .kpi-value {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
        }
        .kpi-highlight {
            color: #047857;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10px;
        }
        .items-table th, .items-table td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.3px;
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
        .font-bold {
            font-weight: bold;
        }
        .badge-sku {
            display: inline-block;
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
        }
        .summary-container {
            width: 100%;
            margin-top: 10px;
            margin-bottom: 25px;
        }
        .summary-box {
            width: 58%;
            float: right;
            border: 2px solid #312e81;
            border-radius: 6px;
            background-color: #f8fafc;
            padding: 10px 14px;
        }
        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
            font-size: 11px;
        }
        .summary-label {
            display: table-cell;
            text-align: left;
            color: #374151;
            font-weight: 600;
        }
        .summary-val {
            display: table-cell;
            text-align: right;
            font-weight: 700;
            color: #111827;
        }
        .summary-total {
            border-top: 2px solid #4338ca;
            padding-top: 6px;
            margin-top: 6px;
        }
        .summary-total .summary-label {
            font-size: 13px;
            font-weight: 800;
            color: #1e1b4b;
            text-transform: uppercase;
        }
        .summary-total .summary-val {
            font-size: 16px;
            font-weight: 800;
            color: #047857;
        }
        .summary-note {
            font-size: 9px;
            color: #64748b;
            text-align: right;
            margin-top: 4px;
            font-style: italic;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-line {
            width: 240px;
            border-top: 1px solid #475569;
            text-align: center;
            font-size: 10px;
            color: #334155;
            padding-top: 4px;
            float: left;
        }
        .footer {
            margin-top: 30px;
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
                <div class="report-title">Relatório de Peças Cadastradas</div>
                <div class="report-subtitle">Brechós Parceiros</div>
                <p><strong>Período:</strong> {{ $dataInicio->format('d/m/Y') }} a {{ $dataFim->format('d/m/Y') }}</p>
                <p><strong>Emissão:</strong> {{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>

    {{-- Cards Informativos / KPIs --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-td">
                <div class="kpi-card">
                    <div class="kpi-title">Brechós Selecionados</div>
                    <div class="kpi-value">{{ $totalBrechos }}</div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card">
                    <div class="kpi-title">Período</div>
                    <div class="kpi-value" style="font-size: 11px; padding-top: 3px;">
                        {{ $dataInicio->format('d/m/y') }} a {{ $dataFim->format('d/m/y') }}
                    </div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card">
                    <div class="kpi-title">Total de Peças Cadastradas</div>
                    <div class="kpi-value">{{ number_format($totalGeralPecas, 0, ',', '.') }}</div>
                </div>
            </td>
            <td class="kpi-td">
                <div class="kpi-card">
                    <div class="kpi-title">Total a Ser Pago</div>
                    <div class="kpi-value kpi-highlight">R$ {{ number_format($totalGeralPagar, 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Tabela de Peças por Brechó Parceiro --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;" class="text-center">#</th>
                <th style="width: 28%;">Brechó Parceiro</th>
                <th style="width: 12%;" class="text-center">Prefixo SKU</th>
                <th style="width: 24%;">Chave Pix</th>
                <th style="width: 12%;" class="text-right">Qtd. Peças</th>
                <th style="width: 10%;" class="text-right">Custo Unit.</th>
                <th style="width: 10%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($relatorioItens as $idx => $item)
                @php
                    $b = $item['brecho'];
                @endphp
                <tr>
                    <td class="text-center font-bold">{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a;">{{ $b->nome }}</strong>
                        @if($b->fornecedor_nome)
                            <br><span style="color: #64748b; font-size: 9px;">Fornecedor: {{ $b->fornecedor_nome }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge-sku">{{ $b->prefixo_codigo ?: '—' }}</span>
                    </td>
                    <td>
                        @if($b->chave_pix)
                            <strong style="color: #1e293b;">{{ $b->chave_pix }}</strong>
                            @if($b->tipo_chave_pix)
                                <span style="color: #64748b; font-size: 8px;">({{ strtoupper($b->tipo_chave_pix) }})</span>
                            @endif
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Não cadastrada</span>
                        @endif
                    </td>
                    <td class="text-right font-bold" style="font-size: 11px; color: #0f172a;">
                        {{ number_format($item['qtd_pecas'], 0, ',', '.') }}
                    </td>
                    <td class="text-right" style="color: #475569;">
                        R$ {{ number_format($item['custo_unitario'], 2, ',', '.') }}
                    </td>
                    <td class="text-right font-bold" style="color: #047857;">
                        R$ {{ number_format($item['subtotal'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 16px; color: #64748b;">
                        Nenhum brechó parceiro selecionado ou localizado para o período.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Resumo Final e Cálculo Total --}}
    <div class="clearfix summary-container">
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">Brechós Analisados:</div>
                <div class="summary-val">{{ $totalBrechos }} brechó(s)</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Quantidade Total de Peças Cadastradas:</div>
                <div class="summary-val">{{ number_format($totalGeralPecas, 0, ',', '.') }} peças</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Custo Unitário por Peça:</div>
                <div class="summary-val">R$ {{ number_format($custoUnitario, 2, ',', '.') }}</div>
            </div>
            <div class="summary-row summary-total">
                <div class="summary-label">TOTAL A SER PAGO:</div>
                <div class="summary-val">R$ {{ number_format($totalGeralPagar, 2, ',', '.') }}</div>
            </div>
            <div class="summary-note">
                Cálculo: Quantidade Total ({{ number_format($totalGeralPecas, 0, ',', '.') }} peças) &times; R$ 1,00
            </div>
        </div>
    </div>

    {{-- Assinaturas --}}
    <div class="clearfix signature-section">
        <div class="signature-line" style="margin-right: 40px;">
            Responsável pelo Fechamento<br>
            <strong>Minha Mania (Matriz)</strong>
        </div>
        <div class="signature-line" style="float: right;">
            Data de Conferência e Pagamento<br>
            ____ / ____ / ________
        </div>
    </div>

    {{-- Rodapé --}}
    <div class="footer">
        Minha Mania - Moda Circular &bull; Relatório emitido em {{ now()->format('d/m/Y \à\s H:i:s') }} por {{ auth()->user()->name ?? 'Administrador' }}.
    </div>

</body>
</html>
