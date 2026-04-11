<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Novo Cliente teste</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>➕ Novo Cliente</h1>
            <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">← Voltar</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.clientes.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="nome_cliente" class="form-label">Nome</label>
                               <input type="text" class="form-control" 
								   id="nome_cliente" name="nome_cliente" 
								   placeholder="Nome do cliente">
							<small class="text-muted">
								<i class="fas fa-info-circle"></i>
								Se vazio, usaremos seu Instagram ou TikTok como nome
							</small>
                        </div>
                        
                    </div>

                    <div class="row">
						<!-- Instagram -->
						<div class="col-md-6 mb-3">
							<label for="ig_instagram" class="form-label">
								<i class="fab fa-instagram" style="color: #E4405F;"></i> Instagram
							</label>
							<div class="input-group">
								<span class="input-group-text">@</span>
								<input type="text" class="form-control" 
									   id="ig_instagram" name="ig_instagram" 
									   placeholder="seu_usuario">
							</div>
							<small class="text-muted">Apenas o nome do usuário, sem @</small>
						</div>

						<!-- TikTok -->
						<div class="col-md-6 mb-3">
							<label for="ig_tiktok" class="form-label">
								<i class="fab fa-tiktok" style="color: #000000;"></i> TikTok
							</label>
							<div class="input-group">
								<span class="input-group-text">@</span>
								<input type="text" class="form-control" 
									   id="ig_tiktok" name="ig_tiktok" 
									   placeholder="seu_usuario">
							</div>
							<small class="text-muted">Apenas o nome do usuário, sem @</small>
						</div>                        
                    </div>	
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success">💾 Salvar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script>
	$(document).ready(function() {
		// Preview do nome que será usado
		function atualizarPreview() {
			const nome = $('#nome_cliente').val().trim();
			const instagram = $('#ig_instagram').val().trim();
			const tiktok = $('#ig_tiktok').val().trim();
			
			let nomeUsado = '';
			let emailUsado = '';
			
			if (nome) {
				nomeUsado = nome;
			} else if (instagram) {
				nomeUsado = instagram;
			} else if (tiktok) {
				nomeUsado = tiktok;
			} else {
				nomeUsado = 'Preencha pelo menos um campo';
			}
			
			emailUsado = nomeUsado.toLowerCase().replace(/[^a-z0-9]/g, '') + '@mania.com';
			
			$('#nome-preview').html(
				'<i class="fas fa-user"></i> <strong>Nome:</strong> ' + nomeUsado + '<br>' +
				'<i class="fas fa-envelope"></i> <strong>Email:</strong> ' + emailUsado
			);
		}
		
		// Atualizar preview quando qualquer campo mudar
		$('#nome_cliente, #ig_instagram, #ig_tiktok').on('input', atualizarPreview);
		
		// Preview inicial
		atualizarPreview();
		
		console.log('✅ Sistema de nome automático ativo!');
	});
</script>
</html>
