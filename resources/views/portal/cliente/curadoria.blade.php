@extends('layouts.portal-cliente')

@section('title', 'Curadoria Sob Medida - Portal do Cliente')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header / Hero da Curadoria -->
    <div class="bg-gradient-to-r from-emerald-800 to-emerald-600 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
        <!-- Detalhe decorativo -->
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
        
        <div class="relative z-10">
            <h1 class="text-2xl md:text-3xl font-bold mb-2 flex items-center gap-2">
                <i class="fas fa-gem text-emerald-200"></i> Curadoria Sob Medida
            </h1>
            <p class="text-emerald-50 text-sm md:text-base max-w-xl">
                Diga o que você está procurando. Nosso acervo trabalha para encontrar a sua próxima peça favorita antes de todo mundo.
            </p>
        </div>
    </div>

    <!-- Novo Pedido de Curadoria -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <form id="formCuradoria" onsubmit="enviarCuradoria(event)" class="space-y-4">
            <div>
                <label for="raw_prompt" class="block text-gray-700 font-semibold mb-2">Qual peça você deseja encomendar à Curadoria?</label>
                <textarea 
                    id="raw_prompt" 
                    name="raw_prompt" 
                    rows="3" 
                    required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all text-gray-800 resize-none"
                    placeholder="Ex: Quero um vestido midi floral tamanho M, até R$ 90, para usar no fim de semana..."
                ></textarea>
                <p class="mt-2 text-xs text-gray-500 italic">
                    <i class="fas fa-info-circle text-emerald-500 mr-1"></i>
                    Pode falar do seu jeito: inclua tamanho, cor, estilo ou faixa de preço. Cuidamos do resto.
                </p>
            </div>
            
            <div class="flex justify-end pt-2">
                <button 
                    type="submit" 
                    id="btnSubmit"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-6 rounded-xl transition-all shadow-md shadow-emerald-500/20 flex items-center gap-2 disabled:opacity-50"
                >
                    <span id="btnText">Ativar Curadoria</span>
                    <i id="btnIcon" class="fas fa-magic"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Curadorias Ativas -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Minhas Curadorias Ativas</h2>
        
        @if($wishes->isEmpty())
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                    <i class="fas fa-box-open text-2xl"></i>
                </div>
                <p class="text-gray-500 text-sm">Você ainda não encomendou nenhuma peça à curadoria.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($wishes as $wish)
                    <div class="border border-gray-100 rounded-xl p-4 flex flex-col sm:flex-row gap-4 items-start sm:items-center hover:bg-gray-50 transition-colors">
                        <!-- Icone / Status -->
                        <div class="flex-shrink-0">
                            @if($wish->status === 'matched' || $wish->status === 'fulfilled')
                                <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center border border-green-200">
                                    <i class="fas fa-check"></i>
                                </div>
                            @elseif($wish->status === 'expired')
                                <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center border border-gray-200">
                                    <i class="fas fa-clock"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center border border-emerald-100">
                                    <i class="fas fa-search animate-pulse"></i>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Conteúdo -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 line-clamp-2" title="{{ $wish->raw_prompt }}">
                                "{{ $wish->raw_prompt }}"
                            </p>
                            
                            <div class="flex flex-wrap gap-2 mt-2">
                                @if($wish->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-medium bg-gray-100 text-gray-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-pulse"></span> Buscando no Acervo...
                                    </span>
                                @elseif($wish->status === 'matched')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[10px] font-medium bg-green-100 text-green-700">
                                        Peça Encontrada!
                                    </span>
                                @endif
                                
                                @if($wish->category)
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-600 text-[10px] font-medium border border-blue-100 capitalize">
                                        {{ $wish->category }}
                                    </span>
                                @endif
                                @if($wish->size)
                                    <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-600 text-[10px] font-medium border border-purple-100">
                                        Tam: {{ $wish->size }}
                                    </span>
                                @endif
                                @if($wish->max_price)
                                    <span class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-600 text-[10px] font-medium border border-emerald-100">
                                        Até R$ {{ number_format($wish->max_price, 2, ',', '.') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Ação / Data -->
                        <div class="text-right sm:text-center text-xs text-gray-400 flex flex-col items-end sm:items-center justify-center">
                            <span>{{ $wish->created_at->format('d/m/y') }}</span>
                            @if($wish->status === 'matched')
                                <button class="mt-2 text-[10px] font-bold text-green-600 bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded-full transition-colors border border-green-200 whitespace-nowrap">
                                    Ver Minha Peça Exclusiva
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-4 text-center">
                <p class="text-[11px] text-gray-400">
                    Você pode ter até 3 pedidos de curadoria ativos ao mesmo tempo. Edite ou cancele quando quiser.
                </p>
            </div>
        @endif
    </div>

</div>

<!-- Modal de Sucesso Customizado (Tailwind/Alpine não incluso direto, usando Vanilla JS para controle do modal) -->
<div id="successModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm w-full border border-gray-100 p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-sparkles text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Pedido de Curadoria Ativado! ✨</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">
                        Já anotamos tudo. Nossos filtros estão ativos e, assim que uma peça com o seu perfil entrar no acervo, você será a primeira a saber.
                    </p>
                </div>
            </div>
            <div class="mt-5 sm:mt-6">
                <button type="button" onclick="window.location.reload()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 bg-emerald-600 text-base font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 sm:text-sm transition-colors">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    async function enviarCuradoria(e) {
        e.preventDefault();
        
        const form = document.getElementById('formCuradoria');
        const input = document.getElementById('raw_prompt');
        const btnSubmit = document.getElementById('btnSubmit');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');
        
        if(!input.value.trim()) return;

        // Loading state
        input.disabled = true;
        btnSubmit.disabled = true;
        btnText.innerText = 'Analisando...';
        btnIcon.className = 'fas fa-spinner fa-spin';

        try {
            const response = await fetch('/api/wishes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    raw_prompt: input.value
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Sucesso -> Mostrar Modal
                input.value = '';
                document.getElementById('successModal').classList.remove('hidden');
            } else {
                // Erro validacao ou API
                alert(data.message || 'Ocorreu um erro. Tente novamente.');
            }
        } catch (err) {
            alert('Não foi possível conectar ao servidor. Tente novamente mais tarde.');
            console.error(err);
        } finally {
            // Remove loading state
            input.disabled = false;
            btnSubmit.disabled = false;
            btnText.innerText = 'Ativar Curadoria';
            btnIcon.className = 'fas fa-magic';
        }
    }

    function closeModal() {
        document.getElementById('successModal').classList.add('hidden');
        window.location.reload(); // Recarrega para atualizar a lista
    }
</script>
@endsection
