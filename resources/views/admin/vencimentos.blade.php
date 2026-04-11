<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Vencimentos - Sacolas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .sidebar {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;}
        .card {border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);}
        .btn-primary {background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;}
        .nav-link {border-radius: 8px; margin: 2px 0; transition: all 0.3s ease;}
        .nav-link:hover {background-color: rgba(255,255,255,0.1); transform: translateX(5px);}
        .nav-link.active {background-color: rgba(255,255,255,0.2); font-weight: bold;}
        .sidebar-brand {font-weight: bold; font-size: 1.4rem; margin-bottom: 1rem;}
        .nav-link i {width: 20px; margin-right: 8px;}
    </style>
</head>

<body class="bg-light">
<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-2 sidebar text-white p-0">
            <div class="p-3">
                <div class="sidebar-brand">
                    <i class="fas fa-store"></i> Admin
                </div>
                <hr class="text-white-50">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('items.index') }}">
                            <i class="fas fa-box"></i> Itens
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('bags.index') }}">
                            <i class="fas fa-broadcast-tower"></i> Live
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="{{ route('admin.sacolinhas.index') }}">
                            <i class="fas fa-shopping-bag"></i> Sacolas
                        </a>
                    </li>

                    <hr class="text-white-50 my-3">

                    <li class="nav-item">
                        <a class="nav-link text-white-50 small active" href="{{ route('admin.vencimentos') }}">
                            <i class="fas fa-triangle-exclamation"></i> Vencimentos
                        </a>
                    </li>

                    <li class="nav-item mt-3">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link text-white-50 small border-0 bg-transparent w-100 text-start">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main -->
        <div class="col-md-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="mb-0 text-danger">
                        <i class="fas fa-triangle-exclamation me-2"></i> Sacolas vencidas (por cliente)
                    </h3>
                    <div class="text-muted small">
                        Regra: <strong>vencimento = add_at + 90 dias</strong> • mostrando apenas <strong>vencidos</strong>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>

            <!-- Cards Totais -->
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

            <!-- Busca -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.vencimentos') }}" class="row g-2 align-items-end">
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
                            <a href="{{ route('admin.vencimentos') }}" class="btn btn-outline-secondary w-100">
                                Limpar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela -->
			<div class="card">
				<div class="card-header">
					<div class="d-flex justify-content-between align-items-center">
						<h6 class="mb-0">
							<i class="fas fa-triangle-exclamation text-danger"></i>
							Sacolinhas vencidas (por cliente)
						</h6>

						<div class="text-end">
							<div class="fw-bold text-danger fs-6">
								Total: R$ {{ number_format($totais->valor_total_vencido ?? 0, 2, ',', '.') }}
							</div>
							<small class="text-muted">
								{{ $totais->total_clientes_com_vencidos ?? 0 }} cliente(s) • {{ $totais->total_itens_vencidos ?? 0 }} item(ns)
							</small>
						</div>
					</div>
				</div>
				
				@if(session('success'))
					<div class="alert alert-success alert-dismissible fade show" role="alert">
						<i class="fas fa-check-circle me-1"></i> {{ session('success') }}
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
				@endif

				@if(session('error'))
					<div class="alert alert-danger alert-dismissible fade show" role="alert">
						<i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
						<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
					</div>
				@endif				

				<div class="card-body">
					@if($clientes->count() === 0)
						<div class="text-center text-muted py-5">
							<i class="fas fa-check-circle fa-3x mb-3 opacity-50"></i>
							<h5>Nenhum vencimento encontrado</h5>
							<p class="mb-0">Não há itens vencidos no momento.</p>
						</div>
					@else

						@foreach($clientes as $c)
							@php
								$lista = $itens[$c->user_id] ?? collect();
								$iniciais = collect(explode(' ', trim($c->cliente_nome)))
									->filter()
									->take(2)
									->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))
									->implode('');
							@endphp

							<div class="card mb-3">
								<div class="card-header">
									<div class="d-flex align-items-center">
										<!-- bolinha com iniciais (igual ao exemplo) -->
										<div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
											 style="width:32px;height:32px;font-weight:700;">
											{{ $iniciais ?: 'CL' }}
										</div>

										<div class="flex-grow-1">
											<h6 class="mb-0">{{ $c->cliente_nome }}</h6>
											<small class="text-muted">
												{{ $c->email ?? '' }} (ID: {{ $c->user_id }})
												@if(!empty($c->whatsapp))
													<span class="ms-2">{{ $c->whatsapp }}</span>
												@endif
											</small>
										</div>

										<div class="text-end">
											<form method="POST" action="{{ route('admin.vencimentos.whatsapp.send', $c->user_id) }}" class="d-inline">
												@csrf
												<button type="submit" class="btn btn-sm btn-success me-2" title="Enviar WhatsApp">
													<i class="fab fa-whatsapp"></i>
												</button>
											</form>

											<span class="badge bg-primary">
												Total de Itens: {{ (int)($c->total_itens_vencidos ?? 0) }}
											</span>

											<div class="fw-bold text-danger">
												R$ {{ number_format($c->valor_total_vencido ?? 0, 2, ',', '.') }}
											</div>
										</div>
									</div>
								</div>

								<div class="card-body p-0">
									<div class="table-responsive">
										<table class="table table-sm mb-0">
											<thead class="table-light">
												<tr>
													<th>Item</th>
													<th>Detalhes</th>
													<th>Preço</th>
													<th width="140">Ações</th>
												</tr>
											</thead>
											<tbody>
											@forelse($lista as $l)
												@php
													$detalhes = [];
													if (!empty($l->item_sku)) $detalhes[] = "SKU: {$l->item_sku}";
													if (!empty($l->item_brand)) $detalhes[] = "Marca: {$l->item_brand}";
													if (!empty($l->item_color)) $detalhes[] = "Cor: {$l->item_color}";
													if (!empty($l->item_size)) $detalhes[] = "Tam: {$l->item_size}";
													if (!empty($l->vencimento)) $detalhes[] = "Venc: " . \Carbon\Carbon::parse($l->vencimento)->format('d/m/Y');
												@endphp

												<tr>
													<td>
														<strong>{{ $l->item_name ?? ('Item #' . $l->item_id) }}</strong>
													</td>
													<td>
														<small class="text-muted">
															{{ count($detalhes) ? implode(' | ', $detalhes) : 'Sem detalhes adicionais' }}
														</small>

														@if(!empty($l->obs))
															<small class="text-muted d-block mt-1">
																<i class="fas fa-sticky-note"></i> <strong>Obs:</strong> {{ $l->obs }}
															</small>
														@endif
													</td>
													<td class="fw-bold text-danger">
														R$ {{ number_format(($l->quantity ?? 1) * ($l->price ?? 0), 2, ',', '.') }}
													</td>
													<td>
														<a class="btn btn-sm btn-outline-danger"
														   href="{{ route('admin.vencimentos.cliente', $c->user_id) }}"
														   title="Excluir">
															<i class="fas fa-trash"></i>
														</a>
													</td>
												</tr>
											@empty
												<tr>
													<td colspan="4" class="text-center text-muted py-3">
														Nenhum item vencido encontrado para este cliente.
													</td>
												</tr>
											@endforelse
											</tbody>
										</table>
									</div>
								</div>
							</div>
						@endforeach

						<div class="mt-3">
							{{ $clientes->links() }}
						</div>

					@endif
				</div>
			</div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>