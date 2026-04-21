@extends('layouts.portal-cliente')

@section('title', 'Dashboard Pontuações - Portal do Cliente')

@section('content')

@php
    \Carbon\Carbon::setLocale('pt_BR');
@endphp

<!-- Saudação no topo -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <h1 class="text-xl font-bold text-gray-800">{{ Str::before($user->name, ' ') }}, o pódio é seu – chegou a vez de brilhar no topo!</h1>
    <p class="text-gray-600 text-sm">
        Suas pontuações de 
        <span class="font-semibold">
            {{ \Carbon\Carbon::createFromFormat('Y-m', $mesAtual)->translatedFormat('F \d\e Y') }}
        </span>
    </p>
</div>

<!-- Grid 3 Colunas: Pontos Combinados | Ranking Individual | Ranking Grupos -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Coluna 1: Pontos Individuais + Grupo combinados -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Seção Individual (azul) -->
        <div class="bg-gradient-to-br from-blue-400 to-blue-500 rounded-lg p-4 text-white mb-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold">🔹 Meus Pontos</h3>
                <i class="fas fa-star text-2xl opacity-90"></i>
            </div>
            <div class="text-2xl font-bold mb-2 drop-shadow-md">{{ number_format($meusPontos->total, 0, ',', '.') }} pts</div>
            <ul class="space-y-1 text-sm">
                <li><span class="opacity-90">💰 Mensalidade:</span> {{ number_format($meusPontos->pontos_mensalidade, 0, ',', '.') }}</li>
                <li><span class="opacity-90">🛒 Itens:</span> {{ number_format($meusPontos->pontos_itens, 0, ',', '.') }}</li>
                <li><span class="opacity-90">⭐ Desafios:</span> {{ number_format($meusPontos->pontos_desafios, 0, ',', '.') }}</li>
                <li><span class="opacity-90">👥 Bônus Grupo:</span> {{ number_format($meusPontos->pontos_bonus_grupo, 0, ',', '.') }}</li>
            </ul>
        </div>
        <!-- Seção Grupo (verde) -->
        <div class="bg-gradient-to-br from-green-400 to-green-500 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold">👥 {{ $meuGrupo->nome ?? 'Meu Grupo' }}</h3>
                <i class="fas fa-users text-2xl opacity-90"></i>
            </div>
            @if($meuGrupo)
                <div class="text-2xl font-bold mb-2 drop-shadow-md">{{ number_format($pontosGrupo->total ?? 0, 0, ',', '.') }} pts</div>
                <ul class="space-y-1 mb-2 text-sm">
                    <li><span class="opacity-90">💰 Mensal ({{ $membrosPagosGrupo ?? 0 }}/{{ $totalMembrosGrupo ?? 0 }} em dia):</span> {{ number_format($pontosGrupo->pontos_mensalidades ?? 0, 0, ',', '.') }}</li>
                    <li><span class="opacity-90">⭐ Desafios:</span> {{ number_format($pontosGrupo->pontos_desafios ?? 0, 0, ',', '.') }}</li>
                    <li><span class="opacity-90">🛒 Itens total:</span> {{ number_format($pontosGrupo->pontos_itens ?? 0, 0, ',', '.') }}</li>
                </ul>
                <p class="text-sm mb-2 opacity-90">Seu bônus: {{ number_format($meusPontos->pontos_bonus_grupo ?? 0, 0, ',', '.') }} pts (50%)</p>
                <a href="#" class="w-full bg-white/20 hover:bg-white/30 text-xs px-3 py-2 rounded-full transition duration-200 text-center block">Ver Grupo</a>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-users-slash text-3xl mb-2 opacity-50"></i>
                    <h4 class="text-lg font-bold mb-2">Sem Grupo</h4>
                    <p class="text-sm mb-4 opacity-90">Participe para +50% bônus!</p>
                    <a href="#" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-full text-sm font-semibold transition duration-200">Entrar em Grupo</a>
                </div>
            @endif
        </div>
    </div>

	<!-- Coluna 3: Ranking Individual (amarelo, SEM scroll nos grupos abaixo) -->
	<div class="bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-lg shadow-sm p-4 text-white relative">
		<div class="flex items-center justify-between mb-4">
			<h3 class="text-lg font-semibold">🏆 Ranking Top 10</h3>
			<!-- Badge sempre com posição correta: lista (se top10) ou real (fora) -->
			<span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold">
				Você: {{ $userNoRanking }}°
			</span>
		</div>
		<div class="bg-white/90 backdrop-blur-sm rounded-lg p-3 border border-white/50">
			<ol class="space-y-2 divide-y divide-gray-200">
				@foreach($ranking as $index => $item)
					<li class="flex justify-between items-center py-2 px-2 first:pt-0 last:pb-0 {{ $item->user_id == $user->id ? 'bg-green-50 border-l-4 border-green-400' : '' }}">
						<span class="font-bold text-sm {{ $item->user_id == $user->id ? 'text-green-700' : 'text-gray-900' }}">
							{{ $index + 1 }}° {{ Str::limit($item->name, 18) }}
						</span>
						<span class="font-bold px-3 py-1 bg-green-500 text-white rounded-full text-xs shadow-sm">{{ number_format($item->total, 0, ',', '.') }}</span>
					</li>
				@endforeach
				
				<!-- Linha extra só se fora do top10 -->
				@php
					$userInTop10 = false;
					foreach($ranking as $item) {
						if($item->user_id == $user->id) {
							$userInTop10 = true;
							break;
						}
					}
				@endphp
				@if(!$userInTop10 && $userNoRanking > 10)
					<li class="flex justify-between items-center py-2 px-2 bg-green-50 border-l-4 border-green-400">
						<span class="font-bold text-sm text-green-700">
							{{ $userNoRanking }}° {{ Str::limit($user->name, 18) }}
						</span>
						<span class="font-bold px-3 py-1 bg-green-500 text-white rounded-full text-xs shadow-sm">
							{{ number_format($meusPontos->total, 0, ',', '.') }}
						</span>
					</li>
				@endif
			</ol>
		</div>
	</div>

	<!-- Coluna 4: Ranking Grupos (roxo, SEM SCROLL) -->
	<div class="bg-gradient-to-br from-purple-400 to-purple-500 rounded-lg shadow-sm p-4 text-white relative">
		<div class="flex items-center justify-between mb-4">
			<h3 class="text-lg font-semibold">👥 Ranking Grupos Top 10</h3>
		</div>
		<div class="bg-white/90 backdrop-blur-sm rounded-lg p-3 border border-white/50"> <!-- 👈 Removido max-h-80 overflow-y-auto -->
			<ol class="space-y-2 divide-y divide-gray-200">
				@forelse($rankingGrupos ?? [] as $index => $item)
					<li class="flex justify-between items-center py-2 px-2 first:pt-0 last:pb-0 {{ ($meuGrupo && $item->grupo_id == $meuGrupo->grupo_id) ? 'bg-green-50 border-l-4 border-green-400' : '' }}">
						<span class="font-bold text-sm {{ ($meuGrupo && $item->grupo_id == $meuGrupo->grupo_id) ? 'text-green-700' : 'text-gray-900' }}">
							{{ $index + 1 }}° {{ Str::limit($item->nome ?? 'Sem nome', 18) }}
						</span>
						<span class="font-bold px-3 py-1 bg-green-500 text-white rounded-full text-xs shadow-sm">{{ number_format($item->total ?? 0, 0, ',', '.') }}</span>
					</li>
				@empty
					<li class="text-center py-4 text-gray-500">Nenhum grupo no ranking ainda.</li>
				@endforelse
			</ol>
		</div>
	</div>

</div>

<!-- Ações Rápidas -->
<div class="bg-white rounded-lg shadow-sm p-4 mt-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ route('portal.sacolinha') }}" class="flex flex-col items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200 text-center">
            <i class="fas fa-shopping-bag text-2xl text-blue-500 mb-2"></i>
            <span class="text-sm text-gray-700 font-medium">Sacolinha</span>
        </a>
        <a href="{{ route('portal.pedidos') }}" class="flex flex-col items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200 text-center">
            <i class="fas fa-receipt text-2xl text-green-500 mb-2"></i>
            <span class="text-sm text-gray-700 font-medium">Pedidos</span>
        </a>
        <a href="{{ route('portal.desafios') }}" class="flex flex-col items-center p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition duration-200 text-center">
            <i class="fas fa-trophy text-2xl text-indigo-500 mb-2"></i>
            <span class="text-sm text-indigo-700 font-medium">Desafios</span>
        </a>
        <a href="#" class="flex flex-col items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200 text-center">
            <i class="fas fa-trophy text-2xl text-purple-500 mb-2"></i>
            <span class="text-sm text-gray-700 font-medium">Ranking Completo</span>
        </a>
    </div>
</div>

@endsection