@extends('layouts.app')

@section('title', 'Gestão de Contatos (Pessoas)')

@section('content')
{{-- ===== SUB-NAV FINANCEIRO ===== --}}
@php
$currentRoute = Route::currentRouteName();
@endphp
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-home mr-1"></i> Dashboard</a>
    <a href="{{ route('financeiro.conciliacao.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-balance-scale mr-1"></i> Conciliação</a>
    <a href="{{ route('financeiro.movimentacoes.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-exchange-alt mr-1"></i> Movimentações</a>
    <a href="{{ route('financeiro.lancamentos.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos</a>
    <a href="{{ route('financeiro.contas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-university mr-1"></i> Contas</a>
    <a href="{{ route('financeiro.orcamento.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-chart-bar mr-1"></i> Orçamento</a>
    <a href="{{ route('financeiro.pessoas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-users mr-1"></i> Contatos</a>
</div>

<div class="mb-6 border-b border-gray-200 pb-3">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-gray-800">Contatos do Financeiro</h1>
        <div class="flex gap-2">
            <button onclick="abrirModalNovaPessoa()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl shadow-sm transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Novo Contato
            </button>
        </div>
    </div>
</div>

{{-- ===== FILTROS ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form action="{{ route('financeiro.pessoas.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Buscar</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nome ou documento..." 
                   class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Tipo</label>
            <select name="tipo" class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="cliente_circular" {{ request('tipo') == 'cliente_circular' ? 'selected' : '' }}>Cliente Circular</option>
                <option value="fornecedor_externo" {{ request('tipo') == 'fornecedor_externo' ? 'selected' : '' }}>Fornecedor</option>
                <option value="funcionario" {{ request('tipo') == 'funcionario' ? 'selected' : '' }}>Funcionário</option>
                <option value="outro" {{ request('tipo') == 'outro' ? 'selected' : '' }}>Outro</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-indigo-50 text-indigo-700 px-6 py-2 rounded-xl font-bold text-sm hover:bg-indigo-100 transition w-full md:w-auto">
                Filtrar
            </button>
        </div>
    </form>
</div>

{{-- ===== TABELA DE PESSOAS ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Nome</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Documento</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Tipo</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-right">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 text-sm">
            @forelse ($pessoas as $p)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800">{{ $p->nome }}</div>
                        @if($p->user_id)
                            <span class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded uppercase font-bold">Vinculado ao Sistema</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ $p->documento ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @php
                            $cores = [
                                'cliente_circular'   => 'bg-green-100 text-green-700',
                                'fornecedor_externo' => 'bg-orange-100 text-orange-700',
                                'funcionario'        => 'bg-purple-100 text-purple-700',
                                'outro'              => 'bg-gray-100 text-gray-700',
                            ];
                            $labels = [
                                'cliente_circular'   => 'Cliente',
                                'fornecedor_externo' => 'Fornecedor',
                                'funcionario'        => 'Funcionário',
                                'outro'              => 'Outro',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-lg text-xs font-bold {{ $cores[$p->tipo] ?? $cores['outro'] }}">
                            {{ $labels[$p->tipo] ?? 'Outro' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button onclick="editarPessoa({{ $p->id }})" class="text-indigo-600 hover:text-indigo-900 mx-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="excluirPessoa({{ $p->id }})" class="text-red-400 hover:text-red-600 mx-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-10 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-2 opacity-20"></i>
                        <p>Nenhum contato encontrado.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    @if($pessoas->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            {{ $pessoas->links() }}
        </div>
    @endif
</div>

{{-- ===== MODAL NOVA/EDITAR PESSOA ===== --}}
<div id="modalPessoa" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-8 py-6 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h3 id="modalTitle" class="text-xl font-black text-gray-800">Novo Contato</h3>
            <button onclick="fecharModalPessoa()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="formPessoa" class="p-8 space-y-4">
            @csrf
            <input type="hidden" id="pessoa_id" name="id">
            
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Nome Completo / Razão Social</label>
                <input type="text" id="nome" name="nome" required 
                       class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">CPF / CNPJ</label>
                    <input type="text" id="documento" name="documento" 
                           class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Tipo de Contato</label>
                    <select id="tipo" name="tipo" required 
                            class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="fornecedor_externo">Fornecedor</option>
                        <option value="funcionario">Funcionário</option>
                        <option value="cliente_circular">Cliente Circular</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
            </div>

            <div id="wrapper_user_id" class="hidden">
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Vincular Usuário do Sistema</label>
                <select id="user_id" name="user_id" class="w-full"></select>
                <p class="text-[10px] text-gray-400 mt-1">Opcional: use para puxar dados de clientes já cadastrados no Circular.</p>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="fecharModalPessoa()" class="flex-1 px-6 py-3 rounded-2xl font-bold text-gray-400 hover:bg-gray-100 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
{{-- Select2 CSS --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
.select2-container--default .select2-selection--single {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    height: 42px;
    display: flex;
    align-items: center;
    padding: 0 12px;
    font-size: 0.875rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
}
.select2-container--default .select2-results__option--highlighted {
    background-color: #4f46e5;
}
.select2-dropdown { border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.1); }
</style>

<script>
const modal = document.getElementById('modalPessoa');
const form = document.getElementById('formPessoa');

function abrirModalNovaPessoa() {
    document.getElementById('modalTitle').innerText = 'Novo Contato';
    form.reset();
    document.getElementById('pessoa_id').value = '';
    $('#user_id').val(null).trigger('change');
    document.getElementById('wrapper_user_id').classList.add('hidden');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function fecharModalPessoa() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function editarPessoa(id) {
    fetch(`/admin/financeiro/pessoas/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('modalTitle').innerText = 'Editar Contato';
            document.getElementById('pessoa_id').value = data.id;
            document.getElementById('nome').value = data.nome;
            document.getElementById('documento').value = data.documento || '';
            document.getElementById('tipo').value = data.tipo;
            
            // Gerenciar campo de usuário
            const wrapper = document.getElementById('wrapper_user_id');
            if (data.tipo === 'cliente_circular') {
                wrapper.classList.remove('hidden');
                if (data.user_id) {
                    const op = new Option(data.nome, data.user_id, true, true);
                    $('#user_id').append(op).trigger('change');
                } else {
                    $('#user_id').val(null).trigger('change');
                }
            } else {
                wrapper.classList.add('hidden');
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
}

// Lógica de exibir/esconder campo de usuário conforme o tipo
document.getElementById('tipo').addEventListener('change', function() {
    const wrapper = document.getElementById('wrapper_user_id');
    if (this.value === 'cliente_circular') {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
        $('#user_id').val(null).trigger('change');
    }
});

// Inicializar Select2 para usuários
$(document).ready(function() {
    $('#user_id').select2({
        dropdownParent: $('#modalPessoa'),
        placeholder: 'Buscar cliente no sistema...',
        minimumInputLength: 2,
        ajax: {
            url: '{{ route('api.users.search') }}',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term }),
            processResults: function(data) {
                // Mapeia os dados do seu ClienteController para o formato Select2
                return {
                    results: data.data.map(u => ({
                        id: u.id,
                        text: u.name + (u.cpf ? ' ('+u.cpf+')' : ''),
                        nome: u.name,
                        documento: u.cpf || u.cnpj || ''
                    }))
                };
            }
        }
    });

    // Ao selecionar um usuário, preencher nome e documento automaticamente
    $('#user_id').on('select2:select', function (e) {
        const data = e.params.data;
        document.getElementById('nome').value = data.nome;
        document.getElementById('documento').value = data.documento;
    });
});

form.onsubmit = function(e) {
    e.preventDefault();
    const id = document.getElementById('pessoa_id').value;
    const url = id ? `/admin/financeiro/pessoas/${id}` : '/admin/financeiro/pessoas';
    const method = id ? 'PUT' : 'POST';
    
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            window.location.reload();
        } else {
            alert(res.message || 'Erro ao salvar');
        }
    });
};

function excluirPessoa(id) {
    if(!confirm('Deseja realmente excluir este contato?')) return;
    
    fetch(`/admin/financeiro/pessoas/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(res => {
        if(res.success) {
            window.location.reload();
        } else {
            alert(res.message);
        }
    });
}
</script>
@endpush
