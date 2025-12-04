<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sacolinha Cliente</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            background-color: #f8f9fa;
        }

        body {
            display: flex;
            overflow: hidden;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            z-index: 1000;
            flex-shrink: 0;
        }

        .sidebar-content {
            padding: 15px;
        }

        .sidebar-brand {
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: white;
        }

        .nav-link {
            color: white !important;
            border-radius: 6px;
            margin: 2px 0;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(3px);
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: bold;
        }

        .nav-item {
            margin-bottom: 2px;
        }

        /* ===== MAIN CONTENT ===== */
        .main-wrapper {
            margin-left: 250px;
            width: calc(100% - 250px);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            color: #333;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
        }

        .card-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #e9ecef;
            padding: 10px 12px;
        }

        .card-header h6 {
            font-size: 0.95rem;
            color: #333;
            margin: 0;
        }

        .card-body {
            padding: 12px;
        }

        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .form-control, .form-select {
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
        }

        .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .table {
            font-size: 0.8rem;
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #333;
            color: white;
            padding: 0.5rem;
            font-weight: 600;
            border: none;
        }

        .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }

        .table-responsive {
            max-height: 800px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .list-group-item {
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        .list-group-item.cliente-item:hover,
        .list-group-item.item-result:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }

        .alert {
            margin-bottom: 10px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
        }

        .badge {
            font-size: 0.75rem;
        }

        .position-absolute {
            z-index: 1050;
        }

        hr {
            margin: 0.5rem 0;
        }

        .row {
            margin-bottom: 0;
        }

        .col-md-6, .col-md-4, .col-md-8 {
            padding-left: 6px;
            padding-right: 6px;
        }

        .mb-3 {
            margin-bottom: 0.75rem !important;
        }

        .mb-4 {
            margin-bottom: 0.75rem !important;
        }

        .g-3 > * {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                max-height: 200px;
            }

            .main-wrapper {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar text-white p-0">
        <div class="sidebar-content">
            <div class="sidebar-brand">
                <i class="fas fa-store"></i> Admin
            </div>
            <hr class="text-white-50">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('clientes.index') }}">
                        <i class="fas fa-users"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('items.index') }}">
                        <i class="fas fa-box"></i> Itens
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('bags.index') }}">
                        <i class="fas fa-broadcast-tower"></i> Live
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('sacolinhas.consultar') }}">
                        <i class="fas fa-shopping-bag"></i> Sacolinha
                    </a>
                </li>
                <hr class="text-white-50 my-2">
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}" class="d-inline w-100">
                        @csrf
                        <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    <!-- ===== MAIN WRAPPER ===== -->
    <div class="main-wrapper">
        <div class="main-content">
            <!-- Título -->
            <h2>
                <i class="fas fa-shopping-bag text-primary"></i> Sacolinha Cliente
            </h2>

            <!-- CARD 1: BUSCA DE CLIENTE -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-search"></i> Selecionar Cliente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="cliente-search" class="form-label">
                                <i class="fas fa-user"></i> Buscar Cliente
                            </label>
                            <input 
                                type="text" 
                                id="cliente-search" 
                                class="form-control"
                                placeholder="Digite nome, email ou CPF..."
                                autocomplete="off"
                            >
                            <div id="cliente-list" class="list-group position-absolute" style="width: 85%; max-width: 400px; display: none; margin-top: 2px;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info mt-3" id="cliente-info" style="display: none; margin-bottom: 0;">
                                <strong>✓</strong> <span id="cliente-nome" class="fw-bold"></span><br>
                                <small>(<span id="cliente-email"></span>)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: ADICIONAR ITEM -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-plus-circle"></i> Adicionar Item
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="item-search" class="form-label">Item</label>
                            <input 
                                type="text" 
                                id="item-search" 
                                class="form-control"
                                placeholder="Digite código ou nome..."
                                autocomplete="off"
                            >
                            <div id="item-list" class="list-group position-absolute" style="width: 45%; max-width: 400px; display: none; margin-top: 2px;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="item-price" class="form-label">Preço</label>
                            <input 
                                type="number" 
                                id="item-price" 
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="0"
                            >
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-success w-100" id="btn-add-item" disabled>
                                <i class="fas fa-plus"></i> Adicionar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: TABELA -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-list"></i> Itens na Sacolinha
                        </h6>
                        <div class="text-end">
                            <span class="badge bg-secondary" id="total-itens-badge">0</span><br>
                            <small class="text-success fw-bold">R$ <span id="total-valor">0,00</span></small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Data</th>
                                    <th>Obs</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="sacolinha-body">
                                <tr id="empty-row">
                                    <td colspan="7" class="text-center text-muted py-3">
                                        <i class="fas fa-inbox"></i> Nenhum item
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
$(document).ready(function() {
    let clienteId = null;
    let sacolinhaData = [];
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ===== BUSCA CLIENTE =====
    $('#cliente-search').on('keyup', function() {
        const search = $(this).val();
        if (search.length < 2) {
            $('#cliente-list').hide();
            return;
        }

        $.ajax({
            url: '/api/users/search?q=' + encodeURIComponent(search),
            method: 'GET',
            success: function(data) {
                renderClienteResults(data);
            },
            error: function(err) {
                $('#cliente-list').html('<div class="list-group-item text-danger">Erro ao buscar</div>').show();
            }
        });
    });

    function renderClienteResults(data) {
        const list = $('#cliente-list');
        list.empty();
        let clientes = Array.isArray(data) ? data : (data && data.data ? data.data : []);
        
        if (!clientes.length) {
            list.html('<div class="list-group-item text-muted">Nenhum resultado</div>');
            list.show();
            return;
        }

        clientes.forEach(cliente => {
            list.append(`
                <button type="button" class="list-group-item list-group-item-action cliente-item" 
                    data-id="${cliente.id}" data-name="${cliente.name}" data-email="${cliente.email}">
                    <div class="fw-bold">${cliente.name}</div>
                    <small class="text-muted">${cliente.email}</small>
                </button>
            `);
        });
        list.show();
    }

    $(document).on('click', '.cliente-item', function() {
        clienteId = $(this).data('id');
        $('#cliente-search').val($(this).data('name'));
        $('#cliente-list').hide();
        $('#cliente-info').show();
        $('#cliente-nome').text($(this).data('name'));
        $('#cliente-email').text($(this).data('email'));
        carregarSacolinha(clienteId);
    });

    // ===== CARREGAR SACOLINHA =====
    function carregarSacolinha(userId) {
        if (!userId) {
            sacolinhaData = [];
            renderSacola();
            return;
        }

        $.ajax({
            url: '/api/sacolinhas/' + userId,
            method: 'GET',
            headers: {'X-CSRF-TOKEN': csrfToken},
            success: function(response) {
                sacolinhaData = response && response.data ? response.data : [];
                renderSacola();
            },
            error: function() {
                sacolinhaData = [];
                renderSacola();
            }
        });
    }

	function renderSacola() {
		const body = $('#sacolinha-body');
		body.empty();

		if (!sacolinhaData.length) {
			body.append($('#empty-row').clone());
			atualizarTotais();
			return;
		}

		sacolinhaData.forEach(item => {
			// ✅ Melhor tratamento do preço
			const preco = parseFloat(item.price || item.sacolinha_unit_price || item.item?.price || 0);
			const qtd = parseInt(item.quantity || 1);
			const data = new Date(item.add_at).toLocaleDateString('pt-BR');

			console.log('Item:', item, 'Preço:', preco);  // ✅ DEBUG

			body.append(`
				<tr data-sacola-id="${item.sacolinha_id}">
					<td>${item.codigo || item.item?.sku || '-'}</td>
					<td>${item.nome_do_produto || item.item?.name || '-'}</td>
					<td>R$ ${preco.toFixed(2)}</td>
					<td>${data}</td>
					<td><small>${item.obs || '-'}</small></td>
					<td class="text-center">
						<button class="btn btn-sm btn-danger btn-remove">
							<i class="fas fa-trash"></i>
						</button>
					</td>
				</tr>
			`);
		});
		atualizarTotais();
	}

	function atualizarTotais() {
		let total = 0, valor = 0;
		sacolinhaData.forEach(item => {
			const qtd = parseInt(item.quantity || 1);
			// ✅ Melhor tratamento do preço
			const preco = parseFloat(item.price || item.sacolinha_unit_price || item.item?.price || 0);
			total += qtd;
			valor += qtd * preco;
		});
		$('#total-itens-badge').text(total);
		$('#total-valor').text(valor.toFixed(2).replace('.', ','));
	}

    // ===== BUSCA ITEM =====
    $('#item-search').on('keyup', function() {
        const search = $(this).val();
        if (search.length < 2) {
            $('#item-list').hide();
            return;
        }

        $.ajax({
            url: '/api/items/search?q=' + encodeURIComponent(search),
            method: 'GET',
            success: function(data) {
                renderItemResults(data);
            },
            error: function() {
                $('#item-list').html('<div class="list-group-item text-danger">Erro</div>').show();
            }
        });
    });

    function renderItemResults(data) {
        const list = $('#item-list');
        list.empty();
        let items = Array.isArray(data) ? data : (data && data.data ? data.data : []);

        if (!items.length) {
            list.html('<div class="list-group-item text-muted">Nenhum resultado</div>');
            list.show();
            return;
        }

        items.forEach(item => {
            list.append(`
                <button type="button" class="list-group-item list-group-item-action item-result"
                    data-id="${item.id}" data-name="${item.name}" data-codigo="${item.sku}" 
                    data-price="${item.price}">
                    <div class="fw-bold">${item.sku} - ${item.name}</div>
                    <small class="text-success">R$ ${parseFloat(item.price).toFixed(2)}</small>
                </button>
            `);
        });
        list.show();
    }

    let selectedItemId = null;
    $(document).on('click', '.item-result', function() {
        selectedItemId = $(this).data('id');
        $('#item-search').val($(this).data('codigo') + ' - ' + $(this).data('name'));
        $('#item-price').val(parseFloat($(this).data('price')).toFixed(2));
        $('#item-list').hide();
        $('#btn-add-item').prop('disabled', false);
    });

    $('#btn-add-item').on('click', function() {
        if (!clienteId) {
            alert('Selecione um cliente');
            return;
        }
        if (!selectedItemId) {
            alert('Selecione um item');
            return;
        }

        $.ajax({
            url: '/api/sacolinhas/add',
            method: 'POST',
            data: {
                user_id: clienteId,
                item_id: selectedItemId,
                quantity: 1,
                live_id: 1,
                price: parseFloat($('#item-price').val()) || 0,
                obs: null,
                tray: null,
                status: 'sacolinha'
            },
            headers: {'X-CSRF-TOKEN': csrfToken},
            success: function() {
                alert('Item adicionado!');
                $('#item-search').val('');
                $('#item-price').val('0');
                selectedItemId = null;
                $('#btn-add-item').prop('disabled', true);
                carregarSacolinha(clienteId);
            },
            error: function() {
                alert('Erro ao adicionar');
            }
        });
    });

	// ===== REMOVER ITEM =====
	$(document).on('click', '.btn-remove', function() {
		if (!confirm('Remover?')) return;
		
		const sacolinhaId = $(this).closest('tr').data('sacola-id');
		console.log('Tentando remover sacolinha_id:', sacolinhaId);
		
		$.ajax({
			url: '/api/sacolinhas/' + sacolinhaId,
			method: 'DELETE',
			data: {
				sacolinha_id: sacolinhaId  // ✅ ADICIONAR ISTO
			},
			headers: {'X-CSRF-TOKEN': csrfToken},
			success: function(response) {
				console.log('Removido com sucesso:', response);
				sacolinhaData = sacolinhaData.filter(item => item.sacolinha_id != sacolinhaId);
				renderSacola();
				alert('Item removido!');
			},
			error: function(err) {
				console.error('Erro ao remover:', err);
				alert('Erro ao remover o item');
			}
		});
	});

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#cliente-search, #cliente-list').length) $('#cliente-list').hide();
        if (!$(e.target).closest('#item-search, #item-list').length) $('#item-list').hide();
    });
});
    </script>
</body>
</html>