<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Criar Novo Item</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="codigo" class="form-label">Código *</label>
                                        <input type="text" class="form-control @error('codigo') is-invalid @enderror" 
                                               id="codigo" name="codigo" value="{{ old('codigo') }}" required>
                                        @error('codigo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nome_do_produto" class="form-label">Nome do Produto *</label>
                                        <input type="text" class="form-control @error('nome_do_produto') is-invalid @enderror" 
                                               id="nome_do_produto" name="nome_do_produto" value="{{ old('nome_do_produto') }}" required>
                                        @error('nome_do_produto')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

							<div class="mb-3">
								<label for="descricao" class="form-label">
									Descrição
								</label>
								<textarea class="form-control @error('descricao') is-invalid @enderror" 
										  id="descricao" 
										  name="descricao" 
										  rows="3"
										  placeholder="Descreva o item">.{{ old('descricao') }}</textarea>
								@error('descricao')
									<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="custo" class="form-label">Custo</label>
                                        <input type="number" step="0.01" class="form-control @error('custo') is-invalid @enderror" 
                                               id="custo" name="custo" value="{{ old('custo') }}">
                                        @error('custo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="preco" class="form-label">Preço *</label>
                                        <input type="number" step="0.01" class="form-control @error('preco') is-invalid @enderror" 
                                               id="preco" name="preco" value="{{ old('preco') }}" required>
                                        @error('preco')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="codigo_da_categoria" class="form-label">Código da Categoria</label>
                                        <input type="text" class="form-control @error('codigo_da_categoria') is-invalid @enderror" 
                                               id="codigo_da_categoria" name="codigo_da_categoria" value="{{ old('codigo_da_categoria') }}">
                                        @error('codigo_da_categoria')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control @error('marca') is-invalid @enderror" 
                                               id="marca" name="marca" value="{{ old('marca') }}">
                                        @error('marca')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control @error('modelo') is-invalid @enderror" 
                                               id="modelo" name="modelo" value="{{ old('modelo') }}">
                                        @error('modelo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="cor" class="form-label">Cor</label>
                                        <input type="text" class="form-control @error('cor') is-invalid @enderror" 
                                               id="cor" name="cor" value="{{ old('cor') }}">
                                        @error('cor')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tamanho" class="form-label">Tamanho</label>
                                        <input type="text" class="form-control @error('tamanho') is-invalid @enderror" 
                                               id="tamanho" name="tamanho" value="{{ old('tamanho') }}">
                                        @error('tamanho')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="estado" class="form-label">Estado *</label>
                                        <select class="form-control @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                                            <option value="novo" {{ old('estado') == 'novo' ? 'selected' : '' }}>Novo</option>
                                            <option value="usado" {{ old('estado') == 'usado' ? 'selected' : '' }}>Usado</option>
                                            <option value="semi-novo" {{ old('estado') == 'semi-novo' ? 'selected' : '' }}>Semi-novo</option>
                                            <option value="recondicionado" {{ old('estado') == 'recondicionado' ? 'selected' : '' }}>Recondicionado</option>
                                        </select>
                                        @error('estado')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                            <option value="disponivel" {{ old('status') == 'disponivel' ? 'selected' : '' }}>Disponível</option>
                                            <option value="reservado" {{ old('status') == 'reservado' ? 'selected' : '' }}>Reservado</option>
                                            <option value="vendido" {{ old('status') == 'vendido' ? 'selected' : '' }}>Vendido</option>
                                            <option value="em_transito" {{ old('status') == 'em_transito' ? 'selected' : '' }}>Em Trânsito</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="pedido" class="form-label">Pedido</label>
                                <input type="text" class="form-control @error('pedido') is-invalid @enderror" 
                                       id="pedido" name="pedido" value="{{ old('pedido') }}">
                                @error('pedido')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Imagem</label>
                                <input type="file" class="form-control @error('image') is-invalid @enderror" 
                                       id="image" name="image" accept="image/*">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>