<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório Executivo da Live #{{ $live->id }} - {{ $live->data->format('d/m/Y') }}</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header-container {
            width: 100%;
            border-bottom: 2.5px solid #6d28d9;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
        }
        .logo-title h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
            color: #5b21b6;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .logo-title p {
            margin: 2px 0 0 0;
            font-size: 11px;
            color: #64748b;
            font-weight: bold;
        }
        .live-meta {
            text-align: right;
        }
        .live-badge {
            display: inline-block;
            background-color: #f5f3ff;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 11px;
            text-transform: uppercase;
        }
        .live-date {
            margin-top: 4px;
            font-size: 11px;
            font-weight: bold;
            color: #334155;
        }

        /* KPI CARDS GRID */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 14px;
        }
        .kpi-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi-card.highlight {
            background-color: #f5f3ff;
            border: 1.5px solid #c4b5fd;
        }
        .kpi-card.success {
            background-color: #ecfdf5;
            border: 1.5px solid #a7f3d0;
        }
        .kpi-card.warning {
            background-color: #fffbeb;
            border: 1.5px solid #fde68a;
        }
        .kpi-label {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
            letter-spacing: 0.3px;
        }
        .kpi-value {
            font-size: 15px;
            font-weight: 900;
            color: #0f172a;
        }
        .kpi-card.highlight .kpi-value {
            color: #5b21b6;
        }
        .kpi-card.success .kpi-value {
            color: #047857;
        }
        .kpi-card.warning .kpi-value {
            color: #b45309;
        }
        .kpi-sub {
            font-size: 8.5px;
            color: #94a3b8;
            margin-top: 2px;
            font-weight: 600;
        }

        /* SECTION HEADINGS */
        .section-heading {
            background: #f1f5f9;
            border-left: 4px solid #6d28d9;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 900;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 14px;
            margin-bottom: 8px;
            border-radius: 0 6px 6px 0;
        }

        /* DATA TABLES */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 6px 8px;
            border: 1px solid #1e293b;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
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
        .font-black {
            font-weight: 900;
        }
        .text-purple {
            color: #6d28d9;
        }
        .text-green {
            color: #059669;
        }
        .text-slate {
            color: #475569;
        }
        .badge-code {
            display: inline-block;
            background-color: #f1f5f9;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 1px 5px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 800;
            font-size: 10px;
        }

        /* CLIENTS LIST TABLE */
        .client-name {
            font-weight: 800;
            color: #0f172a;
            font-size: 10.5px;
        }
        .client-social {
            font-size: 9px;
            color: #64748b;
            margin-top: 1px;
        }

        /* SIGNATURES BOX */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .signatures-table td {
            width: 50%;
            padding: 0 20px;
            text-align: center;
            vertical-align: top;
        }
        .sig-line {
            border-top: 1.5px solid #94a3b8;
            margin-top: 35px;
            padding-top: 5px;
            font-weight: 800;
            font-size: 10px;
            color: #1e293b;
        }
        .sig-sub {
            font-size: 9px;
            color: #64748b;
        }

        .footer {
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- CABEÇALHO -->
    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="logo-title">
                    <h1>Relatório de Fechamento de Live</h1>
                    <p>
                        {{ $live->brecho ? $live->brecho->nome : 'Minha Mania Brechó' }} &bull; 
                        Tipo: {{ $live->tipo_live_formatado }}
                    </p>
                </td>
                <td class="live-meta">
                    <span class="live-badge">Live #{{ $live->id }}</span>
                    <div class="live-date">Data: {{ $live->data->format('d/m/Y') }}</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 2px;">
                        Plataformas: {{ strtoupper(implode(', ', $live->plataformas_array)) ?: 'Instagram / TikTok' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- DASHBOARD DE METRICAS / KPIS -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card highlight" style="width: 25%;">
                <div class="kpi-label">Faturamento Bruto</div>
                <div class="kpi-value">R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</div>
                <div class="kpi-sub">Total das Vendas na Live</div>
            </td>
            <td class="kpi-card warning" style="width: 25%;">
                <div class="kpi-label">Repasse / Custo Loja</div>
                <div class="kpi-value">R$ {{ number_format($totalCusto, 2, ',', '.') }}</div>
                <div class="kpi-sub">{{ $faturamentoBruto > 0 ? number_format(($totalCusto / $faturamentoBruto) * 100, 1, ',', '.') : 0 }}% do faturamento</div>
            </td>
            <td class="kpi-card success" style="width: 25%;">
                <div class="kpi-label">Comissão / Margem</div>
                <div class="kpi-value">R$ {{ number_format($totalComissao, 2, ',', '.') }}</div>
                <div class="kpi-sub">{{ $faturamentoBruto > 0 ? number_format(($totalComissao / $faturamentoBruto) * 100, 1, ',', '.') : 0 }}% de comissão</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Peças Vendidas</div>
                <div class="kpi-value">{{ $totalPecas }} un.</div>
                <div class="kpi-sub">{{ $totalClientes }} cliente(s) compradora(s)</div>
            </td>
        </tr>
        <tr>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Ticket Médio / Peça</div>
                <div class="kpi-value" style="font-size: 13px;">R$ {{ number_format($ticketPeca, 2, ',', '.') }}</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Ticket Médio / Cliente</div>
                <div class="kpi-value" style="font-size: 13px;">R$ {{ number_format($ticketCliente, 2, ',', '.') }}</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Comentários Capturados</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $totalMensagens }} msgs</div>
                <div class="kpi-sub">
                    @if(isset($msgsParceiro) && $msgsParceiro > 0)
                        MM: {{ $msgsMinhaMania }} | @{{ $topHostAccount ?: 'Parceiro' }}: {{ $msgsParceiro }}
                    @else
                        IG: {{ $msgsInsta }} | TT: {{ $msgsTiktok }}
                    @endif
                </div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Média Peças / Cliente</div>
                <div class="kpi-value" style="font-size: 13px;">{{ $totalClientes > 0 ? number_format($totalPecas / $totalClientes, 1, ',', '.') : 0 }} un/cli</div>
            </td>
        </tr>
    </table>

    <!-- 1. RESUMO POR CLIENTE COMPRADORA -->
    <div class="section-heading">1. Resumo Consolidado por Cliente Compradora ({{ $totalClientes }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35%;">Cliente / Identificação</th>
                <th style="width: 25%;">Contato / Social</th>
                <th class="text-center" style="width: 15%;">Qtd. Peças</th>
                <th class="text-right" style="width: 25%;">Total Comprado (R$)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clientesAgrupados as $cliente)
                <tr>
                    <td>
                        <div class="client-name">{{ $cliente['nome'] }}</div>
                        @if(!empty($cliente['apelido']) && $cliente['apelido'] !== $cliente['nome'])
                            <div style="font-size: 8.5px; color: #6d28d9; font-weight: bold;">Tag: {{ $cliente['apelido'] }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="client-social">
                            @if(!empty($cliente['instagram']))
                                <span>IG: @{{ $cliente['instagram'] }}</span><br>
                            @endif
                            @if(!empty($cliente['tiktok']))
                                <span>TT: @{{ $cliente['tiktok'] }}</span><br>
                            @endif
                            @if(!empty($cliente['whatsapp']))
                                <span class="font-bold" style="color: #047857;">Zap: {{ $cliente['whatsapp'] }}</span>
                            @endif
                            @if(empty($cliente['instagram']) && empty($cliente['tiktok']) && empty($cliente['whatsapp']))
                                <span style="color: #94a3b8;">Não informado</span>
                            @endif
                        </div>
                    </td>
                    <td class="text-center font-bold" style="font-size: 11px;">
                        {{ $cliente['quantidade'] }}
                    </td>
                    <td class="text-right font-black" style="font-size: 11px; color: #5b21b6;">
                        R$ {{ number_format($cliente['total_valor'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Nenhuma peça vinculada a clientes nesta live.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($totalClientes > 0)
            <tfoot>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="2" class="text-right" style="padding: 6px 8px; text-transform: uppercase; font-size: 9.5px;">Total Geral:</td>
                    <td class="text-center" style="padding: 6px 8px; font-size: 11px; color: #0f172a;">{{ $totalPecas }} un.</td>
                    <td class="text-right" style="padding: 6px 8px; font-size: 12px; color: #5b21b6; font-weight: 900;">R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- 2. RELAÇÃO COMPLETA DAS PEÇAS VENDIDAS -->
    <div class="section-heading" style="margin-top: 18px;">2. Detalhamento de Peças Vendidas na Live ({{ $totalPecas }})</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 8%;">Cód.</th>
                <th style="width: 32%;">Descrição do Produto</th>
                <th class="text-center" style="width: 10%;">Tam/Cor</th>
                <th class="text-right" style="width: 14%;">Venda (R$)</th>
                <th class="text-right" style="width: 14%;">Repasse (R$)</th>
                <th style="width: 22%;">Compradora</th>
            </tr>
        </thead>
        <tbody>
            @forelse($itensDetalhados as $item)
                <tr>
                    <td class="text-center">
                        <span class="badge-code">#{{ $item['codigo_live'] ?: $item['codigo'] }}</span>
                    </td>
                    <td>
                        <div class="font-bold" style="color: #0f172a;">{{ $item['nome'] }}</div>
                        @if(!empty($item['descricao']) && $item['descricao'] !== $item['nome'])
                            <div style="font-size: 8.5px; color: #64748b;">{{ $item['descricao'] }}</div>
                        @endif
                    </td>
                    <td class="text-center text-slate">
                        {{ $item['tamanho'] ?: '-' }} {{ $item['cor'] ? ' / ' . $item['cor'] : '' }}
                    </td>
                    <td class="text-right font-black" style="color: #0f172a;">
                        R$ {{ number_format($item['preco_venda'], 2, ',', '.') }}
                    </td>
                    <td class="text-right font-bold" style="color: #b45309;">
                        R$ {{ number_format($item['preco_custo'], 2, ',', '.') }}
                    </td>
                    <td>
                        <div class="font-bold" style="color: #5b21b6; font-size: 9.5px;">{{ $item['comprador_nome'] }}</div>
                        @if(!empty($item['comprador_social']))
                            <div style="font-size: 8px; color: #64748b;">@{{ $item['comprador_social'] }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #94a3b8;">
                        Nenhum item vendido registrado nesta live.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($totalPecas > 0)
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: 900;">
                    <td colspan="3" class="text-right" style="padding: 6px 8px; text-transform: uppercase; font-size: 9.5px;">Totais Finais:</td>
                    <td class="text-right" style="padding: 6px 8px; font-size: 11px; color: #0f172a;">R$ {{ number_format($faturamentoBruto, 2, ',', '.') }}</td>
                    <td class="text-right" style="padding: 6px 8px; font-size: 11px; color: #b45309;">R$ {{ number_format($totalCusto, 2, ',', '.') }}</td>
                    <td style="padding: 6px 8px; font-size: 9px; color: #047857; font-weight: bold;">
                        Comissão: R$ {{ number_format($totalComissao, 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- TERMO E ASSINATURAS -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="sig-line">
                    {{ $live->brecho ? $live->brecho->nome : 'Brechó Parceiro / Loja' }}
                </div>
                <div class="sig-sub">Assinatura do Responsável</div>
            </td>
            <td>
                <div class="sig-line">
                    Minha Mania Brechó
                </div>
                <div class="sig-sub">Equipe de Operações de Live</div>
            </td>
        </tr>
    </table>

    <!-- RODAPÉ -->
    <div class="footer">
        Relatório gerado automaticamente em {{ date('d/m/Y \à\s H:i:s') }} &bull; Sistema de Controle de Sacolinhas &bull; Página 1 de 1
    </div>

</body>
</html>
