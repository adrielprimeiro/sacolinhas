@extends('layouts.app')
@section('title', 'Contas Bancárias')
@section('brand_route', 'financeiro.dashboard')
@section('brand_icon', 'fas fa-chart-line')

@section('content')

{{-- Sub-nav --}}
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

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Contas Bancárias</h1>
        <p class="text-sm text-gray-400 mt-0.5">Gerencie caixa, contas e gateways de pagamento.</p>
    </div>
    <button onclick="abrirModalConta()"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl shadow-sm transition">
        <i class="fas fa-plus"></i> Nova Conta
    </button>
</div>

{{-- Cards de contas --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
    @forelse ($contas as $conta)
        @php
            $saldo = $conta->saldo_atual;
            $iconMap = ['corrente' => 'fas fa-university','poupanca' => 'fas fa-piggy-bank','caixa' => 'fas fa-cash-register','gateway' => 'fas fa-credit-card'];
            $colorMap = ['corrente' => 'indigo','poupanca' => 'green','caixa' => 'yellow','gateway' => 'purple'];
            $cor = $colorMap[$conta->tipo] ?? 'gray';
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 pt-5 pb-4">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-xl bg-{{ $cor }}-100 flex items-center justify-center">
                        <i class="{{ $iconMap[$conta->tipo] ?? 'fas fa-wallet' }} text-{{ $cor }}-600 text-lg"></i>
                    </div>
                    <div class="flex gap-1">
                        <button onclick='abrirModalConta({{ json_encode(["id"=>$conta->id,"nome"=>$conta->nome,"tipo"=>$conta->tipo,"saldo_inicial"=>$conta->saldo_inicial]) }})'
                                class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 flex items-center justify-center transition" title="Editar">
                            <i class="fas fa-pencil-alt text-xs"></i>
                        </button>
                        <button onclick="excluirConta({{ $conta->id }})"
                                class="w-8 h-8 rounded-lg bg-gray-100 text-gray-400 hover:bg-red-100 hover:text-red-600 flex items-center justify-center transition" title="Excluir">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>
                <p class="font-black text-gray-800 text-base truncate">{{ $conta->nome }}</p>
                <p class="text-xs text-gray-400 capitalize">{{ $conta->tipo }}</p>
                <p class="text-2xl font-black mt-3 {{ $saldo >= 0 ? 'text-gray-900' : 'text-red-600' }}">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">Saldo inicial: R$ {{ number_format($conta->saldo_inicial, 2, ',', '.') }}</p>
            </div>
            <div class="border-t border-gray-100 px-5 py-3 flex items-center justify-between bg-gray-50/50">
                <span class="text-xs text-gray-400">{{ $conta->movimentacoes_count }} movimentações</span>
                <a href="{{ route('financeiro.contas.extrato', $conta) }}"
                   class="text-xs text-indigo-600 hover:underline font-semibold">
                    Ver extrato <i class="fas fa-arrow-right ml-0.5 text-xs"></i>
                </a>
            </div>
        </div>
    @empty
        <div class="col-span-3 py-20 text-center text-gray-400">
            <i class="fas fa-university text-5xl text-gray-200 mb-4 block"></i>
            <p class="font-semibold text-gray-500">Nenhuma conta cadastrada.</p>
            <p class="text-sm mt-1">Clique em "Nova Conta" para começar.</p>
        </div>
    @endforelse
</div>

{{-- Modal Criar/Editar Conta --}}
<div id="modal-conta" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="fecharModalConta()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50">
            <h2 id="modal-conta-titulo" class="text-lg font-black text-gray-800">Nova Conta</h2>
            <button onclick="fecharModalConta()" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        <form id="form-conta" class="p-6 space-y-4">
            <input type="hidden" id="conta-id">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Nome da Conta <span class="text-red-400">*</span></label>
                <input type="text" id="conta-nome" placeholder="Ex: Nubank, Caixa Físico, Mercado Pago..."
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tipo</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['corrente' => ['Conta Corrente', 'fa-university'], 'poupanca' => ['Poupança', 'fa-piggy-bank'], 'caixa' => ['Caixa Físico', 'fa-cash-register'], 'gateway' => ['Gateway', 'fa-credit-card']] as $val => [$label, $icon])
                        <label class="flex items-center gap-2 border border-gray-200 rounded-xl py-2.5 px-3 cursor-pointer hover:border-indigo-400 hover:bg-indigo-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                            <input type="radio" name="conta_tipo_radio" value="{{ $val }}" class="accent-indigo-600">
                            <i class="fas {{ $icon }} text-gray-500 w-4"></i>
                            <span class="text-sm font-medium">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <select id="conta-tipo" name="tipo" class="hidden">
                    <option value="corrente">corrente</option>
                    <option value="poupanca">poupanca</option>
                    <option value="caixa">caixa</option>
                    <option value="gateway">gateway</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Saldo Inicial (R$)</label>
                <input type="number" id="conta-saldo" step="0.01" min="0" placeholder="0,00"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <p class="text-xs text-gray-400 mt-1">Informe o saldo que já existe nessa conta antes de começar os registros.</p>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="fecharModalConta()"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition font-semibold">Cancelar</button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black transition shadow-md shadow-indigo-100">
                    <i class="fas fa-save mr-1"></i> Salvar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;

// Sync radios → select
document.querySelectorAll('input[name="conta_tipo_radio"]').forEach(r => {
    r.addEventListener('change', () => { document.getElementById('conta-tipo').value = r.value; });
});

function abrirModalConta(data = null) {
    document.getElementById('modal-conta').classList.remove('hidden');
    document.getElementById('form-conta').reset();
    if (data) {
        document.getElementById('modal-conta-titulo').textContent = 'Editar Conta';
        document.getElementById('conta-id').value    = data.id;
        document.getElementById('conta-nome').value  = data.nome;
        document.getElementById('conta-saldo').value = data.saldo_inicial;
        document.getElementById('conta-tipo').value  = data.tipo;
        const radio = document.querySelector(`input[name="conta_tipo_radio"][value="${data.tipo}"]`);
        if (radio) radio.checked = true;
    } else {
        document.getElementById('modal-conta-titulo').textContent = 'Nova Conta';
        document.getElementById('conta-id').value = '';
    }
}

function fecharModalConta() { document.getElementById('modal-conta').classList.add('hidden'); }

document.getElementById('form-conta').addEventListener('submit', async function(e) {
    e.preventDefault();
    const id     = document.getElementById('conta-id').value;
    const url    = id ? `/admin/financeiro/contas/${id}` : '{{ route('financeiro.contas.store') }}';
    const method = id ? 'PUT' : 'POST';
    const body   = {
        nome:          document.getElementById('conta-nome').value,
        tipo:          document.getElementById('conta-tipo').value,
        saldo_inicial: document.getElementById('conta-saldo').value || 0,
    };
    const btn = this.querySelector('[type=submit]');
    btn.disabled = true; btn.textContent = 'Salvando...';
    try {
        const res  = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) });
        const data = await res.json();
        if (data.success) { fecharModalConta(); location.reload(); }
        else alert(data.message || 'Erro ao salvar.');
    } catch(e) { alert('Erro de comunicação.'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save mr-1"></i> Salvar';
});

async function excluirConta(id) {
    if (!confirm('Excluir esta conta? (Só é possível se não houver movimentações.)')) return;
    const res  = await fetch(`/admin/financeiro/contas/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF } });
    const data = await res.json();
    if (data.success) location.reload(); else alert(data.message);
}
</script>
@endpush
