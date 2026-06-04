@extends('layouts.app')
@section('title', 'Lançamentos')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')
@php
$currentRoute = Route::currentRouteName();
@endphp
{{-- Sub-nav --}}
<div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 pb-3">
    <a href="{{ route('financeiro.dashboard') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'dashboard') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-home mr-1"></i> Dashboard</a>
    <a href="{{ route('financeiro.conciliacao.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'conciliacao') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-balance-scale mr-1"></i> Conciliação</a>
    <a href="{{ route('financeiro.movimentacoes.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'movimentacoes') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-exchange-alt mr-1"></i> Movimentações</a>
    <a href="{{ route('financeiro.lancamentos.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'lancamentos') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-file-invoice-dollar mr-1"></i> Lançamentos</a>
    <a href="{{ route('financeiro.contas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'contas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-university mr-1"></i> Contas</a>
    <a href="{{ route('financeiro.orcamento.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'orcamento') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-chart-bar mr-1"></i> Orçamento</a>
    <a href="{{ route('financeiro.pessoas.index') }}" class="px-4 py-2 rounded-t-lg text-sm font-bold transition {{ str_contains($currentRoute, 'pessoas') ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-600' }}"><i class="fas fa-users mr-1"></i> Contatos</a>
</div>

{{-- Cabeçalho --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5" x-data>
    <div>
        <h1 class="text-2xl font-black text-gray-800">Lançamentos</h1>
        <p class="text-sm text-gray-400 mt-0.5">Regime de Competência — gerencie receitas e despesas</p>
    </div>
    <div class="flex gap-2">
        <button onclick="abrirModalNovo('despesa')"
                class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
            <i class="fas fa-plus"></i> Nova Despesa
        </button>
        <button onclick="abrirModalNovo('receita')"
                class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
            <i class="fas fa-plus"></i> Nova Receita
        </button>
    </div>
</div>

{{-- Abas --}}
<div class="flex gap-1 mb-4 bg-gray-100 p-1 rounded-xl w-fit">
    @foreach (['todos' => 'Todos', 'pagar' => 'A Pagar', 'receber' => 'A Receber', 'atrasados' => 'Atrasados'] as $key => $label)
        <a href="{{ route('financeiro.lancamentos.index', ['aba' => $key]) }}"
           class="px-4 py-2 rounded-lg text-sm font-semibold transition
           {{ $aba === $key
               ? ($key === 'atrasados' ? 'bg-red-600 text-white shadow-sm'
                   : ($key === 'pagar' ? 'bg-orange-500 text-white shadow-sm'
                   : ($key === 'receber' ? 'bg-green-600 text-white shadow-sm'
                   : 'bg-white text-gray-800 shadow-sm')))
               : 'text-gray-500 hover:text-gray-800' }}">
            {{ $label }}
            @if ($key === 'atrasados' && $aba !== 'atrasados')
                <span class="ml-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">!</span>
            @endif
        </a>
    @endforeach
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('financeiro.lancamentos.index') }}"
      class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
    <input type="hidden" name="aba" value="{{ $aba }}">
    <div>
        <label class="text-xs text-gray-400 font-medium block mb-1">De</label>
        <input type="date" name="de" value="{{ request('de') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
    </div>
    <div>
        <label class="text-xs text-gray-400 font-medium block mb-1">Até</label>
        <input type="date" name="ate" value="{{ request('ate') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
    </div>
    <div>
        <label class="text-xs text-gray-400 font-medium block mb-1">Status</label>
        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">Todos</option>
            <option value="pendente" {{ request('status') === 'pendente' ? 'selected' : '' }}>Pendente</option>
            <option value="pago_parcial" {{ request('status') === 'pago_parcial' ? 'selected' : '' }}>Parcial</option>
            <option value="pago" {{ request('status') === 'pago' ? 'selected' : '' }}>Pago</option>
            <option value="cancelado" {{ request('status') === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 rounded-lg transition">
            <i class="fas fa-search mr-1"></i> Filtrar
        </button>
        <a href="{{ route('financeiro.lancamentos.index', ['aba' => $aba]) }}"
           class="px-3 py-2 border border-gray-200 rounded-lg text-gray-500 hover:bg-gray-50 text-sm transition">
            <i class="fas fa-times"></i>
        </a>
    </div>
</form>

{{-- Tabela --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Descrição / Pessoa</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Categoria</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Vencimento</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Valor</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($lancamentos as $l)
                    @php
                        $hoje   = \Carbon\Carbon::today();
                        $venc   = \Carbon\Carbon::parse($l->data_vencimento);
                        $atras  = !in_array($l->status, ['pago','cancelado']) && $venc->isPast();
                        $hj     = !in_array($l->status, ['pago','cancelado']) && $venc->isToday();

                        $rowBg  = match(true) {
                            $l->status === 'pago'      => 'bg-green-50/40',
                            $l->status === 'cancelado' => 'bg-gray-50 opacity-60',
                            $atras                     => 'bg-red-50/60',
                            $hj                        => 'bg-yellow-50/60',
                            default                    => '',
                        };

                        $badges = [
                            'pendente'    => 'bg-yellow-100 text-yellow-800',
                            'pago_parcial'=> 'bg-blue-100 text-blue-800',
                            'pago'        => 'bg-green-100 text-green-800',
                            'cancelado'   => 'bg-gray-100 text-gray-600',
                        ];
                        $badgeClass = $badges[$l->status] ?? 'bg-gray-100 text-gray-600';

                        $statusLabel = [
                            'pendente'    => 'Pendente',
                            'pago_parcial'=> 'Parcial',
                            'pago'        => 'Pago',
                            'cancelado'   => 'Cancelado',
                        ];
                    @endphp
                    <tr id="lancamento-{{ $l->id }}" class="hover:bg-gray-50/80 transition {{ $rowBg }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $l->tipo === 'receita' ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                <div>
                                    <p class="font-semibold text-gray-800 truncate max-w-xs">
                                        {{ $l->descricao ?: '—' }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $l->pessoa->nome ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ $l->classificacaoFinanceira->nome ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm {{ $atras ? 'text-red-600 font-bold' : ($hj ? 'text-yellow-700 font-semibold' : 'text-gray-600') }}">
                                {{ $venc->format('d/m/Y') }}
                            </span>
                            @if ($atras)
                                <p class="text-xs text-red-500">{{ $venc->diffForHumans() }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-black {{ $l->tipo === 'receita' ? 'text-green-700' : 'text-red-700' }}">
                            R$ {{ number_format($l->valor_total, 2, ',', '.') }}
                            @if ($l->status === 'pago_parcial')
                                <p class="text-xs font-normal text-blue-600">
                                    Pago: R$ {{ number_format($l->movimentacoes->sum('valor_pago'), 2, ',', '.') }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                {{ $statusLabel[$l->status] ?? $l->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                @unless (in_array($l->status, ['pago', 'cancelado']))
                                    <button onclick="abrirModalBaixa({{ $l->id }}, {{ $l->valor_total }}, '{{ addslashes($l->descricao ?? '') }}')"
                                            class="w-8 h-8 rounded-lg bg-green-100 text-green-700 hover:bg-green-200 transition flex items-center justify-center"
                                            title="Dar Baixa">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                @endunless
                                <button onclick='abrirModalEditar({{ json_encode([
                                    "id"                          => $l->id,
                                    "tipo"                        => $l->tipo,
                                    "status"                      => $l->status,
                                    "descricao"                   => $l->descricao,
                                    "valor_total"                 => $l->valor_total,
                                    "data_emissao"                => $l->data_emissao?->format("Y-m-d"),
                                    "data_vencimento"             => $l->data_vencimento?->format("Y-m-d"),
                                    "pessoa_id"                   => $l->pessoa_id,
                                    "pessoa_nome"                 => $l->pessoa?->nome,
                                    "classificacao_financeira_id" => $l->classificacao_financeira_id,
                                    "classificacao_nome"          => $l->classificacaoFinanceira?->nome,
                                ]) }})'
                                        class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition flex items-center justify-center"
                                        title="Editar">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </button>
                                @if ($l->status !== 'cancelado')
                                    <button onclick="cancelarLancamento({{ $l->id }})"
                                            class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600 transition flex items-center justify-center"
                                            title="Cancelar">
                                        <i class="fas fa-ban text-xs"></i>
                                    </button>
                                @endif
                                <button onclick="excluirLancamento({{ $l->id }})"
                                        class="w-8 h-8 rounded-lg bg-gray-100 text-gray-400 hover:bg-red-100 hover:text-red-600 transition flex items-center justify-center"
                                        title="Excluir">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center text-gray-400">
                            <i class="fas fa-file-invoice-dollar text-4xl text-gray-200 mb-3 block"></i>
                            Nenhum lançamento encontrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($lancamentos->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $lancamentos->links() }}
        </div>
    @endif
</div>

@include('admin.financeiro.lancamentos._modais', ['contas' => $contas])
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;

/* ===== MODAL NOVO/EDITAR ===== */
function abrirModalNovo(tipo) {
    document.getElementById('modal-lancamento').classList.remove('hidden');
    document.getElementById('modal-lancamento-titulo').textContent = tipo === 'receita' ? 'Nova Receita' : 'Nova Despesa';
    document.getElementById('campo-tipo').value = tipo;
    document.getElementById('campo-id').value = '';
    document.getElementById('form-lancamento').reset();
    document.getElementById('campo-tipo').value = tipo;
    // Resetar campos bloqueados
    document.getElementById('campo-valor').disabled = false;
    document.getElementById('campo-valor').classList.remove('bg-gray-100', 'cursor-not-allowed');
    // Limpar select2
    if (window.$) { $('#select-pessoa').val(null).trigger('change'); $('#select-classificacao').val(null).trigger('change'); }
}

function abrirModalEditar(data) {
    document.getElementById('modal-lancamento').classList.remove('hidden');
    document.getElementById('modal-lancamento-titulo').textContent = 'Editar Lançamento';
    document.getElementById('campo-id').value            = data.id;
    document.getElementById('campo-tipo').value          = data.tipo;
    document.getElementById('campo-descricao').value     = data.descricao || '';
    document.getElementById('campo-valor').value         = data.valor_total;
    document.getElementById('campo-emissao').value       = data.data_emissao;
    document.getElementById('campo-vencimento').value    = data.data_vencimento;

    // Se estiver pago, bloqueia o valor total para edição
    const isPago = data.status === 'pago';
    document.getElementById('campo-valor').disabled = isPago;
    if (isPago) {
        document.getElementById('campo-valor').classList.add('bg-gray-100', 'cursor-not-allowed');
    } else {
        document.getElementById('campo-valor').classList.remove('bg-gray-100', 'cursor-not-allowed');
    }

    // Select2: injeta opção selecionada
    if (window.$) {
        // Limpa antes de injetar
        $('#select-pessoa').val(null).trigger('change');
        $('#select-classificacao').val(null).trigger('change');
        
        if (data.pessoa_id) {
            const op1 = new Option(data.pessoa_nome, data.pessoa_id, true, true);
            $('#select-pessoa').append(op1).trigger('change');
        }
        if (data.classificacao_financeira_id) {
            const op2 = new Option(data.classificacao_nome, data.classificacao_financeira_id, true, true);
            $('#select-classificacao').append(op2).trigger('change');
        }
    }
}

function fecharModalLancamento() {
    document.getElementById('modal-lancamento').classList.add('hidden');
}

document.getElementById('form-lancamento').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id     = document.getElementById('campo-id').value;
    const url    = id ? `/admin/financeiro/lancamentos/${id}` : '{{ route('financeiro.lancamentos.store') }}';
    const method = id ? 'PUT' : 'POST';

    const body = {
        tipo:                        document.getElementById('campo-tipo').value,
        descricao:                   document.getElementById('campo-descricao').value,
        valor_total:                 document.getElementById('campo-valor').value,
        data_emissao:                document.getElementById('campo-emissao').value,
        data_vencimento:             document.getElementById('campo-vencimento').value,
        pessoa_id:                   document.getElementById('select-pessoa').value,
        classificacao_financeira_id: document.getElementById('select-classificacao').value,
    };

    const btn = this.querySelector('[type=submit]');
    btn.disabled = true; btn.textContent = 'Salvando...';

    try {
        const res  = await fetch(url, { 
            method, 
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF 
            }, 
            body: JSON.stringify(body) 
        });
        const data = await res.json();
        if (res.ok && data.success) { 
            fecharModalLancamento(); 
            location.reload(); 
        } else { 
            let msg = data.message || 'Erro ao salvar.';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join('\n');
            }
            alert(msg); 
        }
    } catch(err) { 
        alert('Erro de comunicação.'); 
    }
    btn.disabled = false; btn.textContent = 'Salvar';
});

/* ===== MODAL BAIXA ===== */
function abrirModalBaixa(id, valor, descricao) {
    document.getElementById('modal-baixa').classList.remove('hidden');
    document.getElementById('baixa-lancamento-id').value   = id;
    document.getElementById('baixa-descricao').textContent = descricao || `Lançamento #${id}`;
    document.getElementById('baixa-valor').value           = parseFloat(valor).toFixed(2);
    document.getElementById('baixa-data').value            = new Date().toISOString().split('T')[0];
}

function fecharModalBaixa() { document.getElementById('modal-baixa').classList.add('hidden'); }

document.getElementById('form-baixa').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id  = document.getElementById('baixa-lancamento-id').value;
    const body = {
        data_pagamento:    document.getElementById('baixa-data').value,
        valor_pago:        document.getElementById('baixa-valor').value,
        conta_bancaria_id: document.getElementById('baixa-conta').value,
        forma_pagamento:   document.getElementById('baixa-forma').value,
    };
    const btn = this.querySelector('[type=submit]');
    btn.disabled = true; btn.textContent = 'Registrando...';
    try {
        const res  = await fetch(`/admin/financeiro/lancamentos/${id}/baixar`, { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json', 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF 
            }, 
            body: JSON.stringify(body) 
        });
        const data = await res.json();
        if (res.ok && data.success) { 
            fecharModalBaixa(); 
            location.reload(); 
        } else { 
            let msg = data.message || 'Erro ao registrar baixa.';
            if (data.errors) {
                msg = Object.values(data.errors).flat().join('\n');
            }
            alert(msg); 
        }
    } catch(err) { 
        alert('Erro de comunicação.'); 
    }
    btn.disabled = false; btn.textContent = 'Confirmar Baixa';
});

/* ===== AÇÕES DIRETAS ===== */
async function cancelarLancamento(id) {
    if (!confirm('Cancelar este lançamento?')) return;
    try {
        const res  = await fetch(`/admin/financeiro/lancamentos/${id}/cancelar`, { 
            method: 'POST', 
            headers: { 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF 
            } 
        });
        const data = await res.json();
        if (res.ok && data.success) {
            location.reload(); 
        } else {
            alert(data.message || 'Erro ao cancelar lançamento.');
        }
    } catch(err) {
        alert('Erro de comunicação.');
    }
}

async function excluirLancamento(id) {
    if (!confirm('Excluir permanentemente este lançamento?')) return;
    try {
        const res  = await fetch(`/admin/financeiro/lancamentos/${id}`, { 
            method: 'DELETE', 
            headers: { 
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF 
            } 
        });
        const data = await res.json();
        if (res.ok && data.success) {
            location.reload(); 
        } else {
            alert(data.message || 'Erro ao excluir lançamento.');
        }
    } catch(err) {
        alert('Erro de comunicação.');
    }
}

/* ===== SELECT2 ===== */
document.addEventListener('DOMContentLoaded', function() {
    if (!window.$) return;
    $('#select-pessoa').select2({
        dropdownParent: $('#modal-lancamento'),
        placeholder: 'Buscar pessoa...',
        minimumInputLength: 1,
        ajax: {
            url: '{{ route('financeiro.search.pessoas') }}',
            dataType: 'json',
            delay: 300,
            processResults: d => ({ results: d })
        }
    });
    $('#select-classificacao').select2({
        dropdownParent: $('#modal-lancamento'),
        placeholder: 'Buscar categoria...',
        minimumInputLength: 0,
        ajax: {
            url: '{{ route('financeiro.search.classificacoes') }}',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term }),
            processResults: d => ({ results: d })
        }
    });
});
</script>
@endpush
