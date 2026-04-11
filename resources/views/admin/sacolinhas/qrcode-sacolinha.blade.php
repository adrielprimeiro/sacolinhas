<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buscar Sacolinha</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #4a00e0; --secondary-color: #8e2de2; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .header-section { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 20px 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        #video-scanner { width: 100%; max-width: 500px; border-radius: 15px; transform: scaleX(-1); background: #000; }
        .item-photo { width: 100%; max-height: 320px; object-fit: cover; border-radius: 12px; border: 1px solid #eee; }
        .info-label { font-size: 0.75rem; text-transform: uppercase; color: #6c757d; font-weight: bold; }
        .info-value { font-weight: 600; color: #333; }
    </style>
</head>
<body>

<div class="header-section">
    <div class="container d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0"><i class="fas fa-search me-2"></i>Buscar Sacolinha</h1>
        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm rounded-pill">Voltar</a>
    </div>
</div>

<div class="container">
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label fw-bold">Código do Item</label>
            <div class="input-group input-group-lg">
                <input type="text" id="searchInput" class="form-control" placeholder="Digite ou escaneie...">
                <button class="btn btn-primary" id="qrScanBtn"><i class="fas fa-camera"></i></button>
                <button class="btn btn-dark" id="btnBuscar">Buscar</button>
            </div>
        </div>
    </div>

    <!-- Área onde o Card será injetado -->
    <div id="result-area"></div>
</div>

<!-- Modal Scanner -->
<div class="modal fade" id="qrModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Scanner QR Code</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0 bg-black">
                <video id="video-scanner"></video>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/umd/index.min.js"></script>

<script>
    const codeReader = new ZXing.BrowserQRCodeReader();
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    const searchInput = document.getElementById('searchInput');

    async function buscarItem(codigo) {
        if (!codigo) return;
        const area = document.getElementById('result-area');
        area.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

        try {
            const response = await fetch(`{{ route('admin.sacolinhas.qrcode.buscar-item') }}?codigo=${codigo}`);
            const res = await response.json();

            if (!res.success) {
                area.innerHTML = `<div class="alert alert-warning text-center">${res.message}</div>`;
                return;
            }

            renderCard(res.data);
        } catch (e) {
            area.innerHTML = '<div class="alert alert-danger text-center">Erro na comunicação com o servidor.</div>';
        }
    }

	function renderCard(item) {
		const area = document.getElementById('result-area');
		
		// 📍 ESTA É A LISTA QUE MOSTRA OS STATUS NO CARD
		const statusLabels = {
			indisponivel: 'Indisponível',
			disponivel: 'Disponível',
			reservado: 'Reservado',
			vendido: 'Vendido',
			em_sacolinha: 'Em Sacolinha',
			loja: 'Loja',
			estoque: 'Estoque',
			live: 'Live',
			'solicitado na loja': 'Solicitado na Loja',
			'solicitado na live': 'Solicitado na Live'
		};

		const optionsHtml = Object.entries(statusLabels).map(([value, label]) => {
			const selected = item.status === value ? 'selected' : '';
			return `<option value="${value}" ${selected}>${label.toUpperCase()}</option>`;
		}).join('');

		area.innerHTML = `
			<div class="card p-4 mb-5">
				<div class="row g-4 align-items-center">
					<div class="col-md-5 text-center">
						<img src="${item.image_url}" class="item-photo shadow-sm">
					</div>
					<div class="col-md-7">
						<h3 class="text-primary mb-3">${item.nome_do_produto}</h3>
						
						<div class="row g-3 mb-4">
							<div class="col-6"><span class="info-label">ID</span><br><span class="info-value">${item.id}</span></div>
							<div class="col-6"><span class="info-label">Código</span><br><span class="info-value">${item.codigo}</span></div>
							<div class="col-12">
								<span class="info-label">Cliente / Sacolinha</span><br>
								<span class="info-value text-primary"><i class="fas fa-user me-1"></i> ${item.cliente_nome}</span>
							</div>
						</div>

						<div class="p-3 bg-light rounded border">
							<label class="fw-bold small mb-2">Alterar Status do Item</label>
							<div class="input-group">
								<select id="selectStatus" class="form-select">${optionsHtml}</select>
								<button class="btn btn-success" onclick="salvarStatus(${item.id})">
									<i class="fas fa-save me-1"></i> Atualizar
								</button>
							</div>
							<div id="feedback" class="mt-2"></div>
						</div>
					</div>
				</div>
			</div>
		`;
	}

    async function salvarStatus(id) {
        const status = document.getElementById('selectStatus').value;
        const feedback = document.getElementById('feedback');
        const token = document.querySelector('meta[name="csrf-token"]').content;

        feedback.innerHTML = '<small class="text-muted">Salvando...</small>';

        try {
            const response = await fetch(`/admin/sacolinhas/item/${id}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify({ status })
            });
            const res = await response.json();
            feedback.innerHTML = `<span class="text-success fw-bold small"><i class="fas fa-check me-1"></i>${res.message}</span>`;
        } catch (e) {
            feedback.innerHTML = '<span class="text-danger small">Erro ao salvar status.</span>';
        }
    }

    // Scanner
    document.getElementById('qrScanBtn').addEventListener('click', () => {
        modal.show();
        codeReader.decodeFromVideoDevice(null, 'video-scanner', (result, err) => {
            if (result) {
                codeReader.reset();
                modal.hide();
                searchInput.value = result.text;
                buscarItem(result.text);
            }
        });
    });

    document.getElementById('btnBuscar').addEventListener('click', () => buscarItem(searchInput.value));
    searchInput.addEventListener('keypress', (e) => { if(e.key === 'Enter') buscarItem(searchInput.value) });
</script>
</body>
</html>