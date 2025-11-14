<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar {{ $item->nome_do_produto }} - Sacolinhas</title> {{-- Título atualizado --}}

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 10px;
        }
        .current-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar text-white p-0">
                <div class="p-3">
                    <h4>Admin</h4>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="{{ route('items.index') }}">
                                <i class="fas fa-box"></i> Itens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Editar Item</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a href="{{ route('items.index') }}">Itens</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{ route('items.show', $item) }}">{{ $item->nome_do_produto }}</a> {{-- Atualizado --}}
                                </li>
                                <li class="breadcrumb-item active">Editar</li>
                            </ol>
                        </nav>
                    </div>
                    <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <!-- Formulário -->
                <div class="card">
                    <div class="card-body">
                        <!-- Exibir erros de validação -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <h6><i class="fas fa-exclamation-triangle"></i> Corrija os erros abaixo:</h6>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('items.update', $item) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                {{-- Código --}}
                                <div class="col-md-6 mb-3">
                                    <label for="codigo" class="form-label">Código *</label>
                                    <input type="text"
                                           class="form-control @error('codigo') is-invalid @enderror"
                                           id="codigo"
                                           name="codigo"
                                           value="{{ old('codigo', $item->codigo) }}"
                                           placeholder="Ex: SKU12345"
                                           required>
                                    @error('codigo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Nome do Produto --}}
                                <div class="col-md-6 mb-3">
                                    <label for="nome_do_produto" class="form-label">Nome do Produto *</label>
                                    <input type="text"
                                           class="form-control @error('nome_do_produto') is-invalid @enderror"
                                           id="nome_do_produto"
                                           name="nome_do_produto"
                                           value="{{ old('nome_do_produto', $item->nome_do_produto) }}" {{-- Atualizado --}}
                                           placeholder="Ex: Hambúrguer Artesanal"
                                           required>
                                    @error('nome_do_produto')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Descrição --}}
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição *</label> {{-- Atualizado para 'descricao' --}}
                                <textarea class="form-control @error('descricao') is-invalid @enderror" {{-- Atualizado para 'descricao' --}}
                                          id="descricao" {{-- Atualizado para 'descricao' --}}
                                          name="descricao" {{-- Atualizado para 'descricao' --}}
                                          rows="4"
                                          placeholder="Descreva o item, ingredientes, características especiais..."
                                          required>{{ old('descricao', $item->descricao) }}</textarea> {{-- Atualizado para 'descricao' --}}
                                @error('descricao')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                {{-- Custo --}}
                                <div class="col-md-4 mb-3">
                                    <label for="custo" class="form-label">Custo (R\$)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R\$</span>
                                        <input type="number"
                                               class="form-control @error('custo') is-invalid @enderror"
                                               id="custo"
                                               name="custo"
                                               value="{{ old('custo', $item->custo) }}"
                                               step="0.01"
                                               min="0">
                                        @error('custo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Preço --}}
                                <div class="col-md-4 mb-3">
                                    <label for="preco" class="form-label">Preço (R\$) *</label> {{-- Atualizado para 'preco' --}}
                                    <div class="input-group">
                                        <span class="input-group-text">R\$</span>
                                        <input type="number"
                                               class="form-control @error('preco') is-invalid @enderror" {{-- Atualizado para 'preco' --}}
                                               id="preco" {{-- Atualizado para 'preco' --}}
                                               name="preco" {{-- Atualizado para 'preco' --}}
                                               value="{{ old('preco', $item->preco) }}" {{-- Atualizado para 'preco' --}}
                                               step="0.01"
                                               min="0.01"
                                               placeholder="0,00"
                                               required>
                                        @error('preco')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Código da Categoria --}}
                                <div class="col-md-4 mb-3">
                                    <label for="codigo_da_categoria" class="form-label">Código da Categoria</label> {{-- Atualizado --}}
                                    <input type="text"
                                           class="form-control @error('codigo_da_categoria') is-invalid @enderror"
                                           id="codigo_da_categoria"
                                           name="codigo_da_categoria"
                                           value="{{ old('codigo_da_categoria', $item->codigo_da_categoria) }}" {{-- Atualizado --}}
                                           placeholder="Ex: LANCHES, BEBIDAS">
                                    @error('codigo_da_categoria')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                {{-- Marca --}}
                                <div class="col-md-4 mb-3">
                                    <label for="marca" class="form-label">Marca</label>
                                    <input type="text"
                                           class="form-control @error('marca') is-invalid @enderror"
                                           id="marca"
                                           name="marca"
                                           value="{{ old('marca', $item->marca) }}"
                                           placeholder="Ex: Nike, Apple">
                                    @error('marca')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Modelo --}}
                                <div class="col-md-4 mb-3">
                                    <label for="modelo" class="form-label">Modelo</label>
                                    <input type="text"
                                           class="form-control @error('modelo') is-invalid @enderror"
                                           id="modelo"
                                           name="modelo"
                                           value="{{ old('modelo', $item->modelo) }}"
                                           placeholder="Ex: Air Max, iPhone 15">
                                    @error('modelo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Estado --}}
                                <div class="col-md-4 mb-3">
                                    <label for="estado" class="form-label">Estado *</label>
                                    <select class="form-select @error('estado') is-invalid @enderror"
                                            id="estado"
                                            name="estado"
                                            required>
                                        <option value="novo" {{ old('estado', $item->estado) == 'novo' ? 'selected' : '' }}>Novo</option>
                                        <option value="usado" {{ old('estado', $item->estado) == 'usado' ? 'selected' : '' }}>Usado</option>
                                        <option value="semi-novo" {{ old('estado', $item->estado) == 'semi-novo' ? 'selected' : '' }}>Semi-novo</option>
                                        <option value="recondicionado" {{ old('estado', $item->estado) == 'com detalhe' ? 'selected' : '' }}>Recondicionado</option>
                                    </select>
                                    @error('estado')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                {{-- Cor --}}
                                <div class="col-md-4 mb-3">
                                    <label for="cor" class="form-label">Cor</label>
                                    <input type="text"
                                           class="form-control @error('cor') is-invalid @enderror"
                                           id="cor"
                                           name="cor"
                                           value="{{ old('cor', $item->cor) }}"
                                           placeholder="Ex: Azul, Vermelho">
                                    @error('cor')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Tamanho --}}
                                <div class="col-md-4 mb-3">
                                    <label for="tamanho" class="form-label">Tamanho</label>
                                    <input type="text"
                                           class="form-control @error('tamanho') is-invalid @enderror"
                                           id="tamanho"
                                           name="tamanho"
                                           value="{{ old('tamanho', $item->tamanho) }}"
                                           placeholder="Ex: P, M, G, 38, 40">
                                    @error('tamanho')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- Pedido --}}
                                <div class="col-md-4 mb-3">
                                    <label for="pedido" class="form-label">Pedido (Opcional)</label>
                                    <input type="text"
                                           class="form-control @error('pedido') is-invalid @enderror"
                                           id="pedido"
                                           name="pedido"
                                           value="{{ old('pedido', $item->pedido) }}"
                                           placeholder="Ex: Pedido#123">
                                    @error('pedido')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        id="status"
                                        name="status"
                                        required>
            						<option value="indisponivel" {{ old('status', $item->status) == 'indisponivel' ? 'selected' : '' }}>Indisponível</option>
                                    <option value="disponivel" {{ old('status', $item->status) == 'disponivel' ? 'selected' : '' }}>Disponível</option>
                                    <option value="reservado" {{ old('status', $item->status) == 'reservado' ? 'selected' : '' }}>Reservado</option>
                                    <option value="vendido" {{ old('status', $item->status) == 'vendido' ? 'selected' : '' }}>Vendido</option>
                                    <option value="em_sacolinha" {{ old('status', $item->status) == 'em_sacolinha' ? 'selected' : '' }}>Em Sacolinha</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Imagem --}}
                            <div class="mb-3">
                                <label for="image" class="form-label">Nova Imagem (opcional)</label>

                                <!-- Imagem atual e opção para remover -->
                                @if($item->image)
                                    <div class="mb-2">
                                        <small class="text-muted">Imagem atual:</small><br>
                                        <img src="{{ asset('storage/' . $item->image) }}"
                                             alt="{{ $item->nome_do_produto }}" {{-- Atualizado --}}
                                             class="current-image">
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                            <label class="form-check-label" for="remove_image">
                                                Remover imagem atual
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                <input type="file"
                                       class="form-control @error('image') is-invalid @enderror"
                                       id="image"
                                       name="image"
                                       accept="image/*"
                                       onchange="previewImage(this)">
                                <div class="form-text">
                                    Formatos aceitos: JPG, PNG, GIF (máx. 2MB)
                                </div>
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror

                                <!-- Preview da nova imagem -->
                                <div id="imagePreview" style="display: none;">
                                    <small class="text-muted">Nova imagem:</small><br>
                                    <img id="preview" class="image-preview" alt="Preview">
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('items.show', $item) }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Atualizar Item
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script para preview da imagem -->
    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewDiv = document.getElementById('imagePreview');

            if (input.files && input.files[0]) {
                const fileSize = input.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 2) { // Max 2MB, ajuste conforme sua validação de backend
                    alert('O tamanho da imagem não pode exceder 2MB.');
                    input.value = ''; // Limpa o input do arquivo
                    previewDiv.style.display = 'none';
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewDiv.style.display = 'block';
                };

                reader.readAsDataURL(input.files[0]);
            } else {
                previewDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>