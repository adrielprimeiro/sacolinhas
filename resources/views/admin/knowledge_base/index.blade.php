@extends('layouts.app')

@section('title', 'Base de Conhecimento RAG - Admin')

@section('content')
<div class="container-fluid p-4 space-y-6">
    <div class="d-flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div>
            <h1 class="h4 font-bold text-gray-800 m-0">🤖 Base de Conhecimento RAG (IA)</h1>
            <p class="text-muted text-sm m-0">Cadastre e edite regras de negócio, FAQs e instruções que a Inteligência Artificial utilizará para responder aos clientes.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success mr-2" onclick="openWhatsappModal()">
                <i class="fab fa-whatsapp mr-1"></i> Treinar via WhatsApp
            </button>
            <button type="button" class="btn btn-primary" onclick="openModal()">
                <i class="fas fa-plus mr-1"></i> Novo Artigo
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Status Vector</th>
                            <th>Última Atualização</th>
                            <th class="text-right" style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $art)
                            <tr>
                                <td>#{{ $art->id }}</td>
                                <td>
                                    <strong class="text-dark">{{ $art->title }}</strong>
                                    <p class="text-muted text-xs mb-0 text-truncate" style="max-width: 400px;">{{ $art->content }}</p>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-uppercase">{{ $art->category }}</span>
                                </td>
                                <td>
                                    @if(!empty($art->embedding))
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Vetorizado</span>
                                    @else
                                        <span class="badge bg-warning text-dark"><i class="fas fa-search"></i> Palavra-chave (Fallback)</span>
                                    @endif
                                </td>
                                <td class="text-muted text-sm">{{ $art->updated_at ? $art->updated_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="text-right">
                                    <button class="btn btn-sm btn-outline-info" onclick='editArticle(@json($art))'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.knowledge-base.destroy', $art->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este artigo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Nenhum artigo cadastrado na base de conhecimento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="articleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('admin.knowledge-base.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id" id="modal_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Cadastrar Artigo RAG</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body space-y-3">
                    <div class="form-group">
                        <label class="font-bold">Título do Artigo / Tópico</label>
                        <input type="text" name="title" id="modal_title" class="form-control" required placeholder="Ex: Regras de Envio e Frete">
                    </div>
                    <div class="form-group">
                        <label class="font-bold">Categoria</label>
                        <select name="category" id="modal_category" class="form-control" required>
                            <option value="sacolinha">Sacolinha</option>
                            <option value="pagamento">Pagamento</option>
                            <option value="envio">Envio / Frete</option>
                            <option value="devolucao">Trocas e Devoluções</option>
                            <option value="pontos">Pontos e Desafios</option>
                            <option value="lives">Lives e Reservas</option>
                            <option value="geral">Geral</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-bold">Conteúdo Completo</label>
                        <textarea name="content" id="modal_content" rows="6" class="form-control" required placeholder="Descreva detalhadamente a regra ou instrução para que a IA possa interpretar..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Salvar e Indexar (RAG)</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Treinamento WhatsApp -->
<div class="modal fade" id="whatsappModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('admin.knowledge-base.import-whatsapp') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fab fa-whatsapp"></i> Treinar IA via WhatsApp</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body space-y-3">
                    <p class="text-sm text-gray-600">
                        Exporte uma conversa de atendimento direto do seu aplicativo do WhatsApp (formato .txt) e faça o upload aqui. A Inteligência Artificial vai analisar o histórico, extrair o tom de voz e as dúvidas mais frequentes para gerar regras automaticamente.
                    </p>
                    <div class="form-group">
                        <label class="font-bold">Arquivo Exportado do WhatsApp (.txt)</label>
                        <input type="file" name="whatsapp_file" class="form-control-file" accept=".txt" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Analisando...'; this.disabled=true; this.form.submit();">Iniciar Análise Mágica</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal_id').value = '';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_category').value = 'geral';
    document.getElementById('modal_content').value = '';
    document.getElementById('modalTitle').innerText = 'Cadastrar Artigo RAG';
    $('#articleModal').modal('show');
}

function openWhatsappModal() {
    $('#whatsappModal').modal('show');
}

function editArticle(art) {
    document.getElementById('modal_id').value = art.id;
    document.getElementById('modal_title').value = art.title;
    document.getElementById('modal_category').value = art.category;
    document.getElementById('modal_content').value = art.content;
    document.getElementById('modalTitle').innerText = 'Editar Artigo RAG';
    $('#articleModal').modal('show');
}
</script>
@endsection
