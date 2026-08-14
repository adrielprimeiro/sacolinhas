{{-- =====================================================
     MODAL: CRIAR / EDITAR LANÇAMENTO
====================================================== --}}
<div id="modal-lancamento" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="fecharModalLancamento()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg z-10 overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-indigo-50 to-purple-50">
            <h2 id="modal-lancamento-titulo" class="text-lg font-black text-gray-800">Novo Lançamento</h2>
            <button onclick="fecharModalLancamento()" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        {{-- Formulário --}}
        <form id="form-lancamento" class="p-6 space-y-4">
            <input type="hidden" id="campo-id">
            <input type="hidden" id="campo-tipo" name="tipo">

            {{-- Descrição --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Descrição</label>
                <input type="text" id="campo-descricao" name="descricao" placeholder="Ex: Aluguel, Venda, Conta de luz..."
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            </div>

            {{-- Observações --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Observações (Opcional)</label>
                <textarea id="campo-observacoes" name="observacoes" rows="2" placeholder="Anotações internas, detalhes do lançamento..."
                          class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none"></textarea>
            </div>

            {{-- Pessoa --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Pessoa (Opcional)
                </label>
                <select id="select-pessoa" name="pessoa_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" style="width:100%">
                </select>
                <p class="text-xs text-gray-400 mt-1">Fornecedor, cliente ou funcionário vinculado ao lançamento.</p>
            </div>

            {{-- Classificação --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Categoria (Plano de Contas) <span class="text-red-400">*</span>
                </label>
                <select id="select-classificacao" name="classificacao_financeira_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm" style="width:100%">
                </select>
            </div>

            {{-- Valor + Datas --}}
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Valor Total <span class="text-red-400">*</span></label>
                    <input type="number" id="campo-valor" name="valor_total" min="0.01" step="0.01" placeholder="0,00"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Emissão</label>
                    <input type="date" id="campo-emissao" name="data_emissao"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Vencimento <span class="text-red-400">*</span></label>
                    <input type="date" id="campo-vencimento" name="data_vencimento"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                </div>
            </div>

            {{-- Opção de Parcelamento --}}
            <div id="secao-parcelamento-toggle" class="border-t border-gray-100 pt-3">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="campo-parcelar" name="parcelar" value="1" class="sr-only peer" onchange="toggleSecaoParcelas()">
                    <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                    <span class="ms-3 text-xs font-semibold text-gray-500 uppercase tracking-wider select-none">Parcelar Lançamento</span>
                </label>
            </div>

            <div id="secao-parcelas" class="hidden grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Parcelas</label>
                    <select id="campo-numero-parcelas" name="numero_parcelas" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        @for ($i = 2; $i <= 24; $i++)
                            <option value="{{ $i }}">{{ $i }}x</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Frequência</label>
                    <select id="campo-frequencia-parcelas" name="frequencia" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        <option value="mensal">Mensal</option>
                        <option value="semanal">Semanal</option>
                        <option value="quinzenal">Quinzenal</option>
                    </select>
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="fecharModalLancamento()"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition font-semibold">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black transition shadow-md shadow-indigo-100">
                    <i class="fas fa-save mr-1"></i> Salvar
                </button>
            </div>

            <script>
            function toggleSecaoParcelas() {
                const checkbox = document.getElementById('campo-parcelar');
                const secao = document.getElementById('secao-parcelas');
                if (checkbox.checked) {
                    secao.classList.remove('hidden');
                } else {
                    secao.classList.add('hidden');
                }
            }
            </script>
        </form>
    </div>
</div>

{{-- =====================================================
     MODAL: DAR BAIXA (LIQUIDAR)
====================================================== --}}
<div id="modal-baixa" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="fecharModalBaixa()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md z-10 overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-green-50 to-emerald-50">
            <div>
                <h2 class="text-lg font-black text-gray-800 flex items-center gap-2">
                    <i class="fas fa-check-circle text-green-500"></i> Dar Baixa
                </h2>
                <p id="baixa-descricao" class="text-xs text-gray-400 mt-0.5 truncate max-w-xs"></p>
            </div>
            <button onclick="fecharModalBaixa()" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        {{-- Formulário --}}
        <form id="form-baixa" class="p-6 space-y-4">
            <input type="hidden" id="baixa-lancamento-id">

            {{-- Conta Bancária --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Conta Bancária <span class="text-red-400">*</span>
                </label>
                <select id="baixa-conta" name="conta_bancaria_id"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none">
                    <option value="">Selecione a conta...</option>
                    @foreach ($contas as $conta)
                        <option value="{{ $conta->id }}">{{ $conta->nome }} ({{ ucfirst($conta->tipo) }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Forma de Pagamento --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                    Forma de Pagamento <span class="text-red-400">*</span>
                </label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['pix' => 'PIX', 'dinheiro' => 'Dinheiro', 'transferencia' => 'Transf.', 'boleto' => 'Boleto', 'cartao_credito' => 'Cartão'] as $val => $label)
                        <label class="flex items-center justify-center gap-1.5 border border-gray-200 rounded-xl py-2 px-3 cursor-pointer hover:border-green-400 hover:bg-green-50 transition text-sm font-medium has-[:checked]:border-green-500 has-[:checked]:bg-green-50 has-[:checked]:text-green-700">
                            <input type="radio" name="forma_pagamento" id="baixa-forma" value="{{ $val }}" class="sr-only" form="form-baixa">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                {{-- hidden real para submit --}}
                <select id="baixa-forma" name="forma_pagamento" class="hidden">
                    <option value="pix">PIX</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="transferencia">Transferência</option>
                    <option value="boleto">Boleto</option>
                    <option value="cartao_credito">Cartão</option>
                </select>
            </div>

            {{-- Data e Valor --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Data Pagamento <span class="text-red-400">*</span></label>
                    <input type="date" id="baixa-data" name="data_pagamento"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Valor Pago <span class="text-red-400">*</span></label>
                    <input type="number" id="baixa-valor" name="valor_pago" min="0.01" step="0.01"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none font-bold text-green-700">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Observações da Baixa (Opcional)</label>
                <input type="text" id="baixa-observacoes" name="observacoes" placeholder="Anotações da baixa..."
                       class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none">
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-700">
                <i class="fas fa-info-circle mr-1"></i>
                Se o valor pago for menor que o total do lançamento, o status ficará como <strong>Parcial</strong>.
            </div>

            {{-- Ações --}}
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="fecharModalBaixa()"
                        class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition font-semibold">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl text-sm font-black transition shadow-md shadow-green-100">
                    <i class="fas fa-check mr-1"></i> Confirmar Baixa
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Sync radio → select para forma de pagamento --}}
<script>
document.querySelectorAll('input[name="forma_pagamento"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.getElementById('baixa-forma').value = radio.value;
    });
});
</script>

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
