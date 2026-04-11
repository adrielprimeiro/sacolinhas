<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vencimentos - Sacolas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-0 text-danger">
                <i class="fas fa-triangle-exclamation me-2"></i> Sacolas vencidas (agrupado por cliente)
            </h3>
            <div class="text-muted small">
                Regra: <strong>vencimento = add_at + 90 dias</strong> (mostrando apenas <strong>vencidos</strong>)
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">Clientes com vencidos</div>
                    <div class="fs-3 fw-bold text-danger">{{ $totais->total_clientes_com_vencidos ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">Linhas vencidas</div>
                    <div class="fs-3 fw-bold text-danger">{{ $totais->total_linhas_vencidas ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">Itens vencidos (qtd)</div>
                    <div class="fs-3 fw-bold text-danger">{{ $totais->total_itens_vencidos ?? 0 }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <div class="small text-muted">Valor total vencido</div>
                    <div class="fs-5 fw-bold text-danger">
                        R$ {{ number_format($totais->valor_total_vencido ?? 0, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.relatorios.vencimentos') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label mb-1">Buscar cliente</label>
                    <input type="text" name="q" value="{{ $busca ?? '' }}" class="form-control"
                           placeholder="Nome, código, CPF, e-mail...">
                </div>
                <div class="col-12 col-md-3">
                    <button class="btn btn-danger w-100" type="submit">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
                <div class="col-12 col-md-3">
                    <a href="{{ route('admin.relatorios.vencimentos') }}" class="btn btn-outline-secondary w-100">
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            @if($clientes->count() === 0)
                <div class="alert alert-success mb-0">
                    Nenhum cliente com sacolas vencidas encontrado.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>CPF</th>
                                <th>WhatsApp</th>
                                <th class="text-end">Linhas vencidas</th>
                                <th class="text-end">Itens vencidos</th>
                                <th class="text-end">Valor vencido</th>
                                <th class="text-center">Período</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $c)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $c->cliente_nome }}</div>
                                        <div class="small text-muted">
                                            ID: {{ $c->user_id }}
                                            @if(!empty($c->codigo_cliente))
                                                • Cód: {{ $c->codigo_cliente }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $c->cpf ?? '—' }}</td>
                                    <td>{{ $c->whatsapp ?? '—' }}</td>
                                    <td class="text-end fw-bold">{{ $c->total_linhas_vencidas }}</td>
                                    <td class="text-end fw-bold">{{ $c->total_itens_vencidos }}</td>
                                    <td class="text-end fw-bold text-danger">
                                        R$ {{ number_format($c->valor_total_vencido ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="small">
                                            {{ $c->primeiro_vencimento ? \Carbon\Carbon::parse($c->primeiro_vencimento)->format('d/m/Y') : '—' }}
                                            <span class="text-muted">até</span>
                                            {{ $c->ultimo_vencimento ? \Carbon\Carbon::parse($c->ultimo_vencimento)->format('d/m/Y') : '—' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $clientes->links() }}
                </div>
            @endif

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>