@extends('layouts.portal-cliente')

@section('title', 'Dashboard - Portal do Cliente')

@section('content')

<div class="space-y-6">
    <!-- Saudação -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h1 class="text-xl font-bold text-gray-800">Olá, {{ $user->name }}!</h1>
        <p class="text-gray-600 text-sm">Bem-vindo ao seu portal</p>
    </div>

    @if(empty($user->brecho_id) || (int) $user->brecho_id === 1)
    <!-- Destaque Loja Online (Aproveite o mesmo frete!) -->
    <div class="rounded-xl bg-gradient-to-r from-pink-600 to-purple-600 p-5 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-block text-xs font-bold uppercase tracking-wider bg-black/20 px-2.5 py-0.5 rounded-full">
                Loja Online Minha Mania
            </span>
            <h2 class="text-lg font-extrabold mt-1.5">
                @if(($sacolinha['itens'] ?? 0) > 0)
                    Aproveite o mesmo frete da sua sacolinha!
                @else
                    Garimpe peças únicas no seu tamanho agora!
                @endif
            </h2>
            <p class="text-xs sm:text-sm text-pink-100 mt-1">
                Filtre por tamanho, veja novidades e reserve direto na sua sacolinha em 1 clique.
            </p>
        </div>
        <a href="{{ route('loja.index') }}"
           class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-white hover:bg-pink-50 text-pink-700 font-extrabold text-sm px-5 py-3 shadow-sm transition">
            <i class="fas fa-store"></i>
            <span>Ir para a Loja</span>
            <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>
    @endif

    <!-- Grid de Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
	
        <!-- Card: Sacolinha -->
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Sacolinha</h3>
                <i class="fas fa-shopping-bag text-gray-400"></i>
            </div>
            
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Itens:</span>
                    <span class="font-medium">{{ $sacolinha['itens'] ?? 0 }}</span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Valor:</span>
                    <span class="font-medium">R$ {{ number_format($sacolinha['valor'] ?? 0, 2, ',', '.') }}</span>
                </div>
                
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ ($sacolinha['status'] ?? 'aberto') === 'aberto' ? 'Aberto em:' : 'Vence:' }}</span>
                    <span class="font-medium">{{ $sacolinha['data'] ?? 'N/A' }}</span>
                </div>
            </div>
            
            <a href="{{ route('portal.sacolinha') }}" 
               class="inline-block mt-3 w-full bg-blue-500 hover:bg-blue-600 text-white text-center text-sm py-2 rounded-md transition duration-200">
                Ver Sacolinha
            </a>
        </div>        
		
        <!-- Card: Limite Sacolinha -->
        <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200 md:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Limite Sacolinha</h3>
                <i class="fas fa-chart-pie text-gray-400"></i>
            </div>

			<div class="space-y-2">
				<div class="flex justify-between text-sm">
					<span class="text-gray-600">Valor do Limite:</span>
					<span class="font-medium">R$ {{ number_format($limite['valor_limite'] ?? 0, 2, ',', '.') }}</span>
				</div>

				<div class="flex justify-between text-sm">
					<span class="text-gray-600">Utilizado:</span>
					<span class="font-medium text-red-600">R$ {{ number_format($limite['utilizado'] ?? 0, 2, ',', '.') }}</span>
				</div>

				<div class="flex justify-between text-sm">
					<span class="text-gray-600">Valor Pago:</span>
					<span class="font-medium text-blue-600">R$ {{ number_format($limite['valor_pago'] ?? 0, 2, ',', '.') }}</span>
				</div>

				<div class="flex justify-between text-sm">
					<span class="text-gray-600">Disponível:</span>
					<span class="font-medium text-green-600">R$ {{ number_format($limite['disponivel'] ?? 0, 2, ',', '.') }}</span>
				</div>
			</div>
            
            <!-- Barra de Progresso -->
            <div class="mt-3">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" 
                         style="width: {{ $limite['percentual'] ?? 0 }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1 text-center">
                    {{ round($limite['percentual'] ?? 0) }}% utilizado
                </p>
            </div>
        </div>		
		
        <!-- Card: Saldo -->
        <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg shadow-sm p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Saldo</h3>
                    <p class="text-2xl font-bold">R$ {{ number_format($saldo ?? 0, 2, ',', '.') }}</p>
                </div>
                <i class="fas fa-wallet text-3xl opacity-75"></i>
            </div>
            <a href="{{ route('portal.movimentacao') }}" 
               class="inline-block mt-2 bg-white/20 hover:bg-white/30 text-xs px-3 py-1 rounded-full transition duration-200">
                Ver Movimentação
            </a>
			<p class="text-xs opacity-75 mt-2">*Valor pago (em dinheiro ou avaliados) que ainda não foi abatido em nenhum pedido enviado</p>
        </div>		
		
        <!-- Card: Pontos 
        <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg shadow-sm p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Pontos</h3>
                    <p class="text-2xl font-bold">1.250</p> {{-- Placeholder - implementar busca real --}}
                </div>
                <i class="fas fa-star text-3xl opacity-75"></i>
				<i class="fas fa-gamepad text-3xl opacity-75"></i>
            </div>
            <p class="text-xs opacity-75 mt-2">Acumule pontos em compras</p>
        </div>-->
		
        <!-- Card: Clube Mania -->
        <div class="bg-gradient-to-r from-purple-400 to-purple-500 rounded-lg shadow-sm p-4 text-white md:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium opacity-90">Clube Mania</h3>
                    <p class="text-2xl font-bold">
                        {{ $clubeData['mensalidade_status'] === 'ativa' ? 'Ativo' : 'Inativo' }}
                    </p>
                </div>
                <i class="fas fa-users text-3xl opacity-75"></i>
            </div>
            
            <div class="mt-3 space-y-1">
                <div class="flex justify-between text-xs opacity-90">
                    <span>Sequência:</span>
                    <span class="font-medium">{{ $clubeData['mensalidades_sequencia'] }} meses</span>
                </div>
                
                <div class="flex justify-between text-xs opacity-90">
                    <span>Pedidos concluídos:</span>
                    <span class="font-medium">{{ $clubeData['pedidos_concluidos'] }}</span>
                </div>
                
                <div class="flex justify-between text-xs opacity-90">
                    <span>Taxa cancel/devol (6m):</span>
                    <span class="font-medium">{{ number_format($clubeData['taxa_cancel_devol_percent'], 2, ',', '.') }}%</span>
                </div>
                
                @if($clubeData['atualizado_em'])
                    <div class="text-xs opacity-75 mt-2">
                        Atualizado em: {{ \Carbon\Carbon::parse($clubeData['atualizado_em'])->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>		
		
		

    </div>

    <!-- Ações Rápidas -->
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @if(empty($user->brecho_id) || (int) $user->brecho_id === 1)
            <a href="{{ route('loja.index') }}"
               class="flex flex-col items-center p-3 bg-pink-50 hover:bg-pink-100 border border-pink-200 rounded-lg transition duration-200">
                <i class="fas fa-store text-2xl text-pink-600 mb-2"></i>
                <span class="text-sm font-bold text-pink-800">Loja Online</span>
            </a>
            @endif

            <a href="{{ route('portal.sacolinha') }}" 
               class="flex flex-col items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200">
                <i class="fas fa-shopping-bag text-2xl text-purple-500 mb-2"></i>
                <span class="text-sm text-gray-700">Sacolinha</span>
            </a>

            <a href="{{ route('portal.pedidos') }}" 
               class="flex flex-col items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200">
                <i class="fas fa-receipt text-2xl text-green-500 mb-2"></i>
                <span class="text-sm text-gray-700">Meus Pedidos</span>
            </a>

            <a href="{{ route('portal.perfil') }}" 
               class="flex flex-col items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200">
                <i class="fas fa-user text-2xl text-blue-500 mb-2"></i>
                <span class="text-sm text-gray-700">Meu Perfil</span>
            </a>

            <a href="{{ route('portal.ranking') }}"
               class="flex flex-col items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition duration-200">
                <i class="fas fa-gamepad text-2xl text-yellow-500 mb-2"></i>
                <span class="text-sm text-gray-700">Jogar</span>
            </a>
        </div>
    </div>
</div>

<script>
    // Solicita à IA que prepare a próxima saudação de forma assíncrona, para que seja instantânea quando o chat abrir.
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            fetch('{{ route("chat-ia.prefetch-greeting") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }).catch(e => console.error("Prefetch greeting failed", e));
        }, 1000); // 1 segundo de atraso para não atrapalhar o carregamento inicial da página
    });
</script>

@endsection