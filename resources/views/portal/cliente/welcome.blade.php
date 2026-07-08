@extends('layouts.portal-cliente')

@section('title', 'Boas-vindas - Portal do Cliente | Mania')

@section('content')
{{-- Custom Styles from portal-boas-vindas_v3.html template --}}
<style>
:root {
  --pink: #F5148C;
  --pink-dark: #C90E72;
  --pink-soft: #FDE7F3;
  --pink-soft-40: rgba(253,231,243,.45);
  --ink: #141414;
  --paper: #FBF9FA;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --radius: 24px;
  --shadow-card: 0 4px 24px -6px rgba(20,20,20,.08);
  --shadow-mania: 0 12px 40px -12px rgba(245,20,140,.25);
}

.hero-custom {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-card);
  border: 1px solid #fce7f3;
  padding: 48px 24px;
  text-align: center;
}
.blob-custom {
  position: absolute;
  border-radius: 50%;
  background: var(--pink-soft);
}
.blob-tr-custom {
  top: -40px;
  right: -40px;
  width: 160px;
  height: 160px;
  opacity: .6;
}
.blob-bl-custom {
  bottom: -56px;
  left: -56px;
  width: 192px;
  height: 192px;
  opacity: .4;
}
.hero-content-custom {
  position: relative;
}
.heart-scribble-custom {
  display: inline-block;
  font-size: 52px;
  color: var(--pink);
  transform: rotate(-8deg);
  animation: heartbeat 2.4s ease-in-out infinite;
}
@keyframes heartbeat {
  0%, 100% { transform: rotate(-8deg) scale(1); }
  10% { transform: rotate(-8deg) scale(1.12); }
  20% { transform: rotate(-8deg) scale(1); }
}
.hero-title-custom {
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: clamp(28px, 5vw, 38px);
  letter-spacing: -.5px;
  margin-top: 12px;
}
.hero-title-custom .pink-custom {
  color: var(--pink);
}
.hero-sub-custom {
  margin-top: 12px;
  font-size: 18px;
  font-weight: 500;
  color: var(--gray-700);
}
.hero-text-custom {
  margin: 16px auto 0;
  max-width: 640px;
  color: var(--gray-500);
  line-height: 1.7;
}
.hero-text-custom strong {
  color: var(--ink);
}

/* CARDS */
.cards-custom {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
  margin-top: 32px;
}
@media(min-width:768px) {
  .cards-custom { grid-template-columns: 1fr 1fr; }
  .hero-custom { padding: 48px; }
}
.card-custom {
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-card);
  border: 1px solid #fce7f3;
  padding: 28px;
  transition: transform .25s ease, box-shadow .25s ease;
}
.card-pink-custom {
  background: linear-gradient(135deg, #FFF0F7 0%, #FCDCEC 100%);
  border-color: #f9c6e0;
}
.card-pink-custom .card-icon-custom {
  background: #fff;
  color: var(--pink);
  box-shadow: 0 4px 14px -4px rgba(245,20,140,.35);
}
.card-pink-custom .info-box-custom {
  background: rgba(255,255,255,.75);
}
.card-custom:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px -12px rgba(245,20,140,.28);
}
.card-icon-custom {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: var(--pink-soft);
  color: var(--pink);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin-bottom: 16px;
}
.card-custom h2 {
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  font-size: 20px;
}
.card-custom p {
  margin-top: 12px;
  color: var(--gray-600);
  font-size: 14px;
  line-height: 1.7;
}
.card-custom p strong {
  color: var(--ink);
}
.info-box-custom {
  margin-top: 20px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  background: rgba(253,231,243,.6);
  border-radius: 16px;
  padding: 16px;
}
.info-box-custom i {
  color: var(--pink);
  margin-top: 2px;
}
.info-box-custom p {
  margin: 0;
  font-size: 14px;
  color: var(--gray-700);
  line-height: 1.6;
}

.card-club-custom {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #B37FFB 0%, #8A52E8 100%);
  color: #fff;
  border: none;
  box-shadow: 0 12px 36px -10px rgba(138,82,232,.5);
}
.card-club-custom .club-logo-custom {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  box-shadow: 0 8px 20px -6px rgba(0,0,0,.3);
  margin-bottom: 16px;
  display: block;
}
.card-club-custom .blob-club-custom {
  position: absolute;
  top: -32px;
  right: -32px;
  width: 128px;
  height: 128px;
  border-radius: 50%;
  background: rgba(255,255,255,.14);
}
.card-club-custom .card-icon-custom {
  background: rgba(255,255,255,.2);
  color: #fff;
}
.card-club-custom p {
  color: rgba(255,255,255,.9);
}
.card-club-custom p strong {
  color: #fff;
}
.club-btn-custom {
  margin-top: 24px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  color: #8A52E8;
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  font-size: 14px;
  padding: 12px 24px;
  border-radius: 999px;
  transition: background .2s;
  position: relative;
}
.club-btn-custom:hover {
  background: var(--pink-soft);
}

/* PEDIDO */
.order-section-custom {
  margin-top: 40px;
}
.order-title-custom {
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  font-size: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.order-title-custom i {
  color: var(--pink);
}
.order-card-custom {
  margin-top: 20px;
  background: #fff;
  border-radius: var(--radius);
  box-shadow: var(--shadow-card);
  border: 1px solid #fce7f3;
  overflow: hidden;
}
.order-item-custom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #fdf2f8;
}
.order-item-left-custom {
  display: flex;
  align-items: center;
  gap: 16px;
}
.item-icon-custom {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  background: var(--pink-soft);
  color: var(--pink);
  display: flex;
  align-items: center;
  justify-content: center;
}
.item-name-custom {
  font-weight: 600;
}
.item-meta-custom {
  font-size: 14px;
  color: var(--gray-500);
}
.item-price-custom {
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
}
.order-total-custom {
  padding: 24px;
  background: var(--pink-soft-40);
}
.total-row-custom {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.total-label-custom {
  font-weight: 500;
  color: var(--gray-700);
}
.total-value-custom {
  font-family: 'Outfit', sans-serif;
  font-weight: 800;
  font-size: 26px;
  color: var(--pink);
}
.pay-btn-custom {
  margin-top: 20px;
  width: 100%;
  background: var(--pink);
  color: #fff;
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
  font-size: 16px;
  padding: 16px;
  border-radius: 18px;
  box-shadow: var(--shadow-mania);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: background .2s;
}
.pay-btn-custom:hover {
  background: var(--pink-dark);
}
.secure-note-custom {
  margin-top: 12px;
  text-align: center;
  font-size: 12px;
  color: var(--gray-500);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.secure-note-custom i {
  color: var(--pink);
}
</style>

<div class="space-y-8">
    
    {{-- MENSAGENS FLASH --}}
    @if(session('success'))
        <div class="p-4 bg-pink-50 border-l-4 border-pink-500 rounded text-pink-700 text-sm shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- HERO HEADER PRINCIPAL COM LOGO (EXIBIDO PARA TODOS OS FLUXOS) --}}
    <section class="hero-custom">
        <div class="blob-custom blob-tr-custom"></div>
        <div class="blob-custom blob-bl-custom"></div>
        <div class="hero-content-custom">
            <span class="heart-scribble-custom"><i class="fas fa-heart"></i></span>
            <h1 class="hero-title-custom flex flex-col sm:flex-row items-center justify-center gap-3">
                <span>Bem-vinda à</span>
                <img src="https://minhamania.net/images/Logo_Grande_Preto.png" alt="Mania" class="h-10 md:h-12 w-auto object-contain">
            </h1>
            @if($flow === 'clube')
                <p class="hero-sub-custom">Pronta para ver os detalhes da live, {{ explode(' ', $user->name)[0] }}? 🌟</p>
                <p class="hero-text-custom">Sua sacolinha de membro VIP do Clube Mania está atualizada. Confirme seu pedido abaixo!</p>
            @elseif($flow === 'first_purchase')
                <p class="hero-sub-custom">Estamos muito felizes em ter você aqui! 💖</p>
                <p class="hero-text-custom">O seu primeiro pedido está garantido. Conheça abaixo como funciona nosso sistema e como você pode obter os <strong>melhores fretes</strong> e vantagens exclusivas!</p>
            @elseif($flow === 'pendencies')
                <p class="hero-sub-custom">Ajustes pendentes na sua sacolinha ⚠️</p>
                <p class="hero-text-custom">Para continuar comprando, precisamos regularizar ou esvaziar os itens antigos da sua sacolinha acumulada.</p>
            @else
                <p class="hero-sub-custom">Que bom ter você de volta! 🛍️</p>
                <p class="hero-text-custom">Seu novo pedido da live já está disponível. Escolha a melhor opção para você abaixo.</p>
            @endif
        </div>
    </section>

    {{-- ========================================================================= --}}
    {{-- CASO 1: CLIENTE DO CLUBE (FLOW = CLUBE)                                   --}}
    {{-- ========================================================================= --}}
    @if($flow === 'clube')
        <div class="bg-gradient-to-r from-purple-800 to-indigo-900 rounded-2xl shadow-md text-white p-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="px-3 py-1 bg-purple-500/30 text-purple-200 text-xs font-bold uppercase rounded-full tracking-wider border border-purple-500/20">
                🌟 Membro VIP do Clube Mania
            </span>
            <div class="flex items-center gap-2">
                <span class="text-xs opacity-75 font-semibold uppercase">Pontos no Jogo:</span>
                <span class="text-xl font-black text-yellow-300 flex items-center gap-1.5">
                    <i class="fas fa-trophy text-yellow-400"></i> {{ number_format($pontosClube, 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="order-section-custom">
            <h2 class="order-title-custom"><i class="fas fa-list"></i> Pedido da Live Atual</h2>
            
            <div class="order-card-custom">
                <div class="divide-y divide-gray-100 max-h-[400px] overflow-y-auto">
                    @forelse($itensLiveAtual as $item)
                        <div class="order-item-custom">
                            <div class="order-item-left-custom">
                                <div class="item-icon-custom">
                                    <i class="fas fa-shoe-prints"></i>
                                </div>
                                <div>
                                    <h4 class="item-name-custom text-gray-800 text-sm md:text-base">{{ $item->item_name }}</h4>
                                    <p class="item-meta-custom">Cód: {{ $item->item_sku }} • Marca: {{ $item->item_brand }} • Tam: {{ $item->item_size }}</p>
                                </div>
                            </div>
                            <div class="item-price-custom text-gray-900 text-sm md:text-base">
                                R$ {{ number_format($item->price, 2, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 text-sm">
                            <i class="fas fa-shopping-cart text-gray-300 text-3xl mb-2 block"></i>
                            Não há novos itens registrados nesta live.
                        </div>
                    @endforelse
                </div>
                
                <div class="order-total-custom space-y-4">
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Quantidade:</span>
                            <span class="font-bold">{{ $itensLiveAtual->count() }} itens</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-bold text-gray-800">R$ {{ number_format($itensLiveAtual->sum('price'), 2, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Saldo em Carteira (Créditos):</span>
                            <span class="font-bold text-blue-600">- R$ {{ number_format($saldoCliente, 2, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="total-row-custom border-t border-gray-200/50 pt-4">
                        <span class="total-label-custom text-base md:text-lg">Total do Pedido:</span>
                        <span class="total-value-custom text-2xl md:text-3xl">R$ {{ number_format(max(0, $itensLiveAtual->sum('price') - $saldoCliente), 2, ',', '.') }}</span>
                    </div>
                    
                    <button onclick="iniciarCheckout({{ json_encode($itensLiveAtual->pluck('sacolinha_id')->toArray()) }})" 
                            class="pay-btn-custom">
                        <i class="fas fa-wallet"></i> Adicionar à Sacolinha e Pagar
                    </button>
                    <div class="secure-note-custom">
                        <i class="fas fa-lock"></i> Checkout Seguro • Seus dados estão protegidos
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- CASO 2: PRIMEIRA COMPRA (FLOW = FIRST_PURCHASE)                           --}}
    {{-- ========================================================================= --}}
    @elseif($flow === 'first_purchase')
        <section class="cards-custom">
            <div class="card-custom card-pink-custom">
                <div class="card-icon-custom"><i class="fas fa-bag-shopping"></i></div>
                <h2>Como funciona a Sacolinha?</h2>
                <p>Aqui você pode guardar seus itens na sacolinha por até <strong>30 dias</strong>. Isso permite que você acumule novos pedidos em lives futuras e pague apenas <strong>um único frete</strong> por todas as suas compras!</p>
                <div class="info-box-custom">
                    <i class="fas fa-circle-info"></i>
                    <p>O pagamento dos seus itens é feito após cada live, e eles ficam guardados esperando você solicitar o envio.</p>
                </div>
            </div>

            <div class="card-custom card-club-custom">
                <div class="blob-club-custom"></div>
                <div class="card-icon-custom"><i class="fas fa-crown"></i></div>
                <h2>Faça parte do Clube Mania!</h2>
                <p>Membros do clube têm créditos para compra sem pagamento imediato. Podem trocar seus desapegos por créditos e muitas outras vantagem. Participam de jogos e sorteios mensais com prêmios!</p>
                <a href="{{ route('portal.desafios') }}" class="club-btn-custom">
                    Ver Benefícios do Clube <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </section>

        <div class="order-section-custom">
            <h2 class="order-title-custom"><i class="fas fa-receipt"></i> Revisão do Seu Primeiro Pedido</h2>
            
            <div class="order-card-custom">
                <div class="divide-y divide-gray-100 max-h-[300px] overflow-y-auto">
                    @foreach($itensLiveAtual as $item)
                        <div class="order-item-custom">
                            <div class="order-item-left-custom">
                                <div class="item-icon-custom">
                                    <i class="fas fa-shoe-prints"></i>
                                </div>
                                <div>
                                    <h4 class="item-name-custom text-gray-800">{{ $item->item_name }}</h4>
                                    <p class="item-meta-custom">Cód: {{ $item->item_sku }} • Marca: {{ $item->item_brand }} • Tam: {{ $item->item_size }}</p>
                                </div>
                            </div>
                            <div class="item-price-custom text-gray-950">
                                R$ {{ number_format($item->price, 2, ',', '.') }}
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="order-total-custom space-y-4">
                    <div class="total-row-custom">
                        <span class="total-label-custom text-base md:text-lg">Total do Pedido:</span>
                        <span class="total-value-custom text-2xl md:text-3xl">R$ {{ number_format($itensLiveAtual->sum('price'), 2, ',', '.') }}</span>
                    </div>
                    
                    <button onclick="iniciarCheckout({{ json_encode($itensLiveAtual->pluck('sacolinha_id')->toArray()) }})" 
                            class="pay-btn-custom">
                        <i class="fas fa-credit-card"></i> Prosseguir para Pagamento
                    </button>
                    <div class="secure-note-custom">
                        <i class="fas fa-lock"></i> Pagamento 100% Protegido
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- CASO 3: FLUXO DE PENDÊNCIAS (SACOLINHA VENCIDA OU FORA DE SALDO)            --}}
    {{-- ========================================================================= --}}
    @elseif($flow === 'pendencies')
        <div class="bg-red-500 rounded-2xl shadow-md text-white p-4 flex items-center gap-3" style="background: linear-gradient(135deg, #EF4444 0%, #B91C1C 100%);">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <div>
                @if($isExpired)
                    <strong>Sua sacolinha ultrapassou o prazo de 30 dias!</strong> Ela expirou em {{ $oldestItemDateFmt }}. Para continuar adicionando novos pedidos, envie ou limpe os itens antigos.
                @else
                    <strong>Ajustes pendentes na sua sacolinha!</strong> O valor acumulado excede seu limite de créditos em carteira.
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mt-6">
            {{-- Itens na Sacolinha --}}
            <div class="lg:col-span-7">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-red-500"></i>
                        <h2 class="text-sm font-bold text-gray-800 uppercase">Itens Acumulados na Sacolinha</h2>
                    </div>
                    <div class="divide-y divide-gray-100 max-h-[350px] overflow-y-auto">
                        @foreach($sacolinhaItens as $item)
                            <div class="p-4 flex justify-between items-center text-sm">
                                <div>
                                    <span class="font-bold text-gray-800">{{ $item->item_name }}</span>
                                    <span class="text-xs text-gray-500 block">Adicionado em: {{ \Carbon\Carbon::parse($item->add_at)->format('d/m/Y') }}</span>
                                </div>
                                <span class="font-bold text-gray-900">R$ {{ number_format($item->price, 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Opções de Ação --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-gray-855 uppercase border-b border-gray-100 pb-2">Como deseja regularizar?</h3>
                    
                    @if($sacolinhaItens->isNotEmpty())
                        {{-- Opção A: Enviar tudo --}}
                        <div class="p-4 border rounded-xl border-pink-100 bg-pink-50/20 hover:bg-pink-50/50 transition">
                            <h4 class="text-xs font-bold text-pink-800 uppercase mb-1">Opção A: Finalizar e Enviar Tudo</h4>
                            <p class="text-xs text-gray-500 mb-3">Pague o saldo total da sacolinha e receba tudo no seu endereço cadastrado.</p>
                            <button onclick="iniciarCheckout({{ json_encode($sacolinhaItens->pluck('sacolinha_id')->toArray()) }})" 
                                    class="w-full py-2.5 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-lg text-xs transition shadow-sm">
                                Enviar Tudo
                            </button>
                        </div>

                        {{-- Opção B: Desfazer sacolinha antiga --}}
                        <div class="p-4 border rounded-xl border-gray-200 hover:bg-gray-50 transition">
                            <h4 class="text-xs font-bold text-gray-855 uppercase mb-1">Opção B: Desfazer itens antigos</h4>
                            <p class="text-xs text-gray-500 mb-3">Libera os itens antigos de volta para o estoque e mantém apenas a compra atual da live.</p>
                            <form action="{{ route('portal.sacolinha.desfazer') }}" method="POST" onsubmit="return confirm('Confirmar remoção de itens antigos da sacolinha?')">
                                @csrf
                                <button type="submit" class="w-full py-2.5 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded-lg text-xs transition">
                                    Desfazer Sacolinha Antiga
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Suporte --}}
                    <div class="text-center pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-500 mb-2">Precisa de suporte financeiro ou quer negociar?</p>
                        <a href="https://wa.me/5521996228604" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-bold text-green-600 hover:text-green-700">
                            <i class="fab fa-whatsapp"></i> Chamar no WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- CASO 4: FLUXO SACOLINHA OK (REGULAR)                                      --}}
    {{-- ========================================================================= --}}
    @elseif($flow === 'regular')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- Opção 1: Guardar na Sacolinha --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between space-y-4">
                <div>
                    <span class="px-2.5 py-0.5 bg-pink-50 text-pink-700 text-xs font-bold uppercase rounded border border-pink-100">
                        OPÇÃO 1
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 mt-2 flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-pink-600"></i> Deixar na Sacolinha
                    </h3>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                        Pague apenas o valor dos novos itens da live e mantenha-os guardados na sua sacolinha por até 30 dias para economizar frete.
                    </p>
                    <div class="mt-4 p-3 bg-pink-50/30 rounded-xl">
                        <div class="flex justify-between text-xs text-gray-500 font-bold uppercase">
                            <span>Novos Itens da Live</span>
                            <span>Total à pagar</span>
                        </div>
                        <div class="flex justify-between text-sm font-extrabold text-gray-855 mt-1">
                            <span>{{ $itensLiveAtual->count() }} itens</span>
                            <span>R$ {{ number_format($itensLiveAtual->sum('price'), 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <button onclick="iniciarCheckout({{ json_encode($itensLiveAtual->pluck('sacolinha_id')->toArray()) }})" 
                        class="w-full py-3 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl transition duration-200 shadow-sm">
                    Pagar e Guardar na Sacolinha
                </button>
            </div>

            {{-- Opção 2: Envio Imediato --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col justify-between space-y-4" x-data="{ openSimulate: false }">
                <div>
                    <span class="px-2.5 py-0.5 bg-green-50 text-green-700 text-xs font-bold uppercase rounded border border-green-100">
                        OPÇÃO 2
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 mt-2 flex items-center gap-2">
                        <i class="fas fa-truck text-green-600"></i> Envio Imediato
                    </h3>
                    <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                        Receba tudo o que está acumulado na sua sacolinha + novos itens da live diretamente na sua casa agora.
                    </p>
                    <div class="mt-4 p-3 bg-gray-50 rounded-xl">
                        <div class="flex justify-between text-xs text-gray-500 font-bold uppercase">
                            <span>Total de itens (acumulados + novos)</span>
                            <span>Subtotal</span>
                        </div>
                        <div class="flex justify-between text-sm font-extrabold text-gray-855 mt-1">
                            <span>{{ $sacolinhaItens->count() }} itens</span>
                            <span>R$ {{ number_format($sacolinhaItens->sum('price'), 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    {{-- Endereço faltando --}}
                    @if(empty($user->cep) || empty($user->endereco))
                        <button @click="openSimulate = !openSimulate" 
                                class="w-full py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl transition duration-200">
                            Completar Endereço para Envio
                        </button>
                        
                        <div x-show="openSimulate" class="p-4 border rounded-xl bg-gray-50 space-y-3 mt-2" x-cloak>
                            <h4 class="text-xs font-bold text-gray-800 uppercase">Preencha seus dados de entrega</h4>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block text-gray-600">CEP</label>
                                    <input type="text" id="input_cep" value="{{ $user->cep }}" placeholder="00000-000" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-gray-600">CPF (Para Envio/Nota)</label>
                                    <input type="text" id="input_cpf" value="{{ $user->cpf }}" placeholder="000.000.000-00" class="w-full p-2 border rounded">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-gray-600">Endereço</label>
                                    <input type="text" id="input_endereco" value="{{ $user->endereco }}" placeholder="Rua, Avenida..." class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-gray-600">Número</label>
                                    <input type="text" id="input_numero" value="{{ $user->numero_endereco }}" placeholder="123" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-gray-600">Bairro</label>
                                    <input type="text" id="input_bairro" value="{{ $user->bairro }}" placeholder="Centro..." class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-gray-600">Cidade</label>
                                    <input type="text" id="input_cidade" value="{{ $user->cidade }}" placeholder="Rio de Janeiro" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="block text-gray-600">Estado (UF)</label>
                                    <input type="text" id="input_estado" value="{{ $user->estado }}" placeholder="RJ" class="w-full p-2 border rounded" maxlength="2">
                                </div>
                            </div>
                            <button onclick="salvarEnderecoECheckout({{ json_encode($sacolinhaItens->pluck('sacolinha_id')->toArray()) }})" 
                                    class="w-full py-2 bg-green-500 hover:bg-green-600 text-white font-bold rounded text-xs transition">
                                Salvar e Ir para o Checkout
                            </button>
                        </div>
                    @else
                        {{-- Endereço Completo --}}
                        <button onclick="iniciarCheckout({{ json_encode($sacolinhaItens->pluck('sacolinha_id')->toArray()) }})" 
                                class="w-full py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition duration-200">
                            Pagar e Solicitar Envio Completo
                        </button>
                    @endif
                </div>
            </div>
        </div>

    {{-- ========================================================================= --}}
    {{-- CASO 5: CLIENTE SEM SACOLINHA (FLOW = NO_BAG)                             --}}
    {{-- ========================================================================= --}}
    @elseif($flow === 'no_bag')
        <div class="order-section-custom">
            <h2 class="order-title-custom"><i class="fas fa-receipt"></i> Revisão do Seu Pedido da Live</h2>
            
            <div class="order-card-custom mb-6">
                <div class="divide-y divide-gray-100 max-h-[300px] overflow-y-auto">
                    @forelse($itensLiveAtual as $item)
                        <div class="order-item-custom">
                            <div class="order-item-left-custom">
                                <div class="item-icon-custom">
                                    <i class="fas fa-shoe-prints"></i>
                                </div>
                                <div>
                                    <h4 class="item-name-custom text-gray-800">{{ $item->item_name }}</h4>
                                    <p class="item-meta-custom">Cód: {{ $item->item_sku }} • Marca: {{ $item->item_brand }} • Tam: {{ $item->item_size }}</p>
                                </div>
                            </div>
                            <div class="item-price-custom text-gray-950">
                                R$ {{ number_format($item->price, 2, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 text-sm">
                            <i class="fas fa-shopping-cart text-gray-300 text-3xl mb-2 block"></i>
                            Não há novos itens registrados nesta live.
                        </div>
                    @endforelse
                </div>
                
                <div class="order-total-custom">
                    <div class="total-row-custom">
                        <span class="total-label-custom text-base md:text-lg">Total do Pedido:</span>
                        <span class="total-value-custom text-2xl md:text-3xl">R$ {{ number_format($itensLiveAtual->sum('price'), 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            {{-- Abrir Nova Sacolinha --}}
            <div class="p-5 border rounded-2xl border-pink-100 bg-white hover:bg-pink-50/10 shadow-sm transition flex flex-col justify-between">
                <div>
                    <span class="px-2.5 py-0.5 bg-pink-50 text-pink-750 text-xs font-bold uppercase rounded border border-pink-100" style="color: var(--pink);">
                        OPÇÃO 1
                    </span>
                    <h3 class="text-base font-bold text-gray-800 mt-3 flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-pink-600"></i> Guardar em Nova Sacolinha
                    </h3>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">Guarde na sacolinha por até 30 dias para economizar no frete acumulando novos pedidos.</p>
                </div>
                <button onclick="iniciarCheckout({{ json_encode($itensLiveAtual->pluck('sacolinha_id')->toArray()) }})" 
                        class="w-full bg-pink-650 hover:bg-pink-700 text-white font-bold py-2.5 rounded-xl text-sm mt-6 transition" style="background: var(--pink);">
                    Abrir Sacolinha e Pagar
                </button>
            </div>

            {{-- Envio Imediato --}}
            <div class="p-5 border rounded-2xl border-green-100 bg-white hover:bg-green-50/10 shadow-sm transition flex flex-col justify-between">
                <div>
                    <span class="px-2.5 py-0.5 bg-green-50 text-green-700 text-xs font-bold uppercase rounded border border-green-100">
                        OPÇÃO 2
                    </span>
                    <h3 class="text-base font-bold text-gray-800 mt-3 flex items-center gap-2">
                        <i class="fas fa-truck text-green-600"></i> Receber Envio Imediato
                    </h3>
                    <p class="text-xs text-gray-500 mt-2 leading-relaxed">Escolha o frete e pague tudo agora para receber suas peças o mais rápido possível.</p>
                </div>
                <button onclick="iniciarCheckout({{ json_encode($itensLiveAtual->pluck('sacolinha_id')->toArray()) }})" 
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl text-sm mt-6 transition">
                    Pagar e Receber Envio
                </button>
            </div>
        </div>
    @endif

</div>

{{-- SCRIPT PARA INTEGRAÇÃO AJAX --}}
<script>
    async function iniciarCheckout(itensIds) {
        if (!itensIds || itensIds.length === 0) return;
        
        try {
            const res = await fetch('{{ route("portal.checkout.iniciar") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ itens: itensIds })
            });
            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Erro ao iniciar checkout.');
            }
        } catch (e) {
            alert('Erro de conexão ao processar checkout.');
        }
    }

    async function salvarEnderecoECheckout(itensIds) {
        const cep = document.getElementById('input_cep').value.trim();
        const cpf = document.getElementById('input_cpf').value.trim();
        const endereco = document.getElementById('input_endereco').value.trim();
        const numero = document.getElementById('input_numero').value.trim();
        const bairro = document.getElementById('input_bairro').value.trim();
        const cidade = document.getElementById('input_cidade').value.trim();
        const estado = document.getElementById('input_estado').value.trim();

        if (!cep || !endereco || !numero || !bairro || !cidade || !estado || !cpf) {
            alert('Por favor, preencha todos os campos do endereço e CPF.');
            return;
        }

        try {
            // Salvar no perfil
            const profileRes = await fetch('{{ route("portal.perfil.atualizar") }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: '{{ $user->name }}',
                    email: '{{ $user->email }}',
                    apelido: '{{ $user->apelido }}',
                    whatsapp: '{{ $user->whatsapp }}',
                    cep: cep,
                    cpf: cpf,
                    endereco: endereco,
                    numero_endereco: numero,
                    bairro: bairro,
                    cidade: cidade,
                    estado: estado
                })
            });

            const profileData = await profileRes.json();
            if (profileData.success) {
                // Iniciar checkout após salvar endereço
                await iniciarCheckout(itensIds);
            } else {
                alert(profileData.message || 'Erro ao atualizar dados cadastrais.');
            }
        } catch (e) {
            alert('Erro ao atualizar endereço e CPF.');
        }
    }
</script>
@endsection
