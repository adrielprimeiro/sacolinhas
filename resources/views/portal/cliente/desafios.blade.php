@extends('layouts.portal-cliente')

@section('title', 'Desafios do Clube')

@section('content')

<div class="space-y-6">

    {{-- Cabeçalho --}}
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">🏆 Desafios do Clube</h1>
                <p class="text-indigo-100 text-sm mt-0.5">Complete desafios e ganhe pontos extras!</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-black">{{ number_format($totalDesafios, 0, ',', '.') }}</div>
                <div class="text-indigo-200 text-xs font-bold uppercase tracking-wider">pts ganhos</div>
            </div>
        </div>
    </div>

    {{-- Desafios Disponíveis --}}
    <div>
        <h2 class="text-base font-black text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-fire text-orange-500"></i> Desafios Disponíveis
        </h2>

        @if($desafios->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <i class="fas fa-trophy text-4xl text-gray-200 block mb-3"></i>
                <p class="text-gray-400 font-bold text-sm">Nenhum desafio disponível no momento.</p>
                <p class="text-gray-300 text-xs mt-1">Volte em breve para novos desafios!</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($desafios as $d)
                <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm p-4 flex items-start gap-4">
                    <div class="p-3 bg-indigo-100 rounded-2xl flex-shrink-0">
                        <i class="fas fa-trophy text-indigo-500 text-xl"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-black text-gray-900 text-sm">{{ $d->nome }}</div>
                        @if($d->descricao)
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $d->descricao }}</p>
                        @endif
                        @if($d->fim_em)
                            <div class="text-[10px] text-amber-600 font-bold mt-2 flex items-center gap-1">
                                <i class="fas fa-clock"></i>
                                Até {{ \Carbon\Carbon::parse($d->fim_em)->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-indigo-500 text-white font-black text-sm shadow-sm">
                            <i class="fas fa-star text-yellow-300 text-[10px]"></i>
                            {{ $d->pontos }}
                        </div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase mt-1">pontos</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Como funciona --}}
    <div class="bg-indigo-50 rounded-2xl border border-indigo-100 p-4">
        <h3 class="text-xs font-black text-indigo-700 uppercase tracking-wider mb-3">
            <i class="fas fa-info-circle mr-1"></i> Como funciona
        </h3>
        <ul class="space-y-2">
            <li class="flex items-start gap-2 text-xs text-indigo-700">
                <i class="fas fa-check-circle text-indigo-400 mt-0.5 flex-shrink-0"></i>
                <span>Participe dos desafios indicados acima</span>
            </li>
            <li class="flex items-start gap-2 text-xs text-indigo-700">
                <i class="fas fa-check-circle text-indigo-400 mt-0.5 flex-shrink-0"></i>
                <span>Após completar, a administração registra os pontos para você</span>
            </li>
            <li class="flex items-start gap-2 text-xs text-indigo-700">
                <i class="fas fa-check-circle text-indigo-400 mt-0.5 flex-shrink-0"></i>
                <span>Pontos são acumulados e usados no ranking do clube</span>
            </li>
        </ul>
    </div>

    {{-- Histórico --}}
    <div>
        <h2 class="text-base font-black text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-history text-gray-400"></i> Meus Pontos de Desafios
        </h2>

        @if($historico->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center">
                <p class="text-gray-400 font-bold text-sm">Você ainda não recebeu pontos por desafios.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="divide-y divide-gray-50">
                    @foreach($historico as $h)
                    <div class="flex items-center justify-between px-4 py-3.5">
                        <div>
                            <div class="font-black text-gray-900 text-sm">{{ $h->desafio_nome ?? 'Desafio' }}</div>
                            <div class="text-[11px] text-gray-400 font-medium mt-0.5">
                                {{ \Carbon\Carbon::parse($h->created_at)->format('d/m/Y') }}
                                · {{ \Carbon\Carbon::parse($h->created_at)->diffForHumans() }}
                            </div>
                        </div>
                        <div class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 font-black text-sm">
                            <i class="fas fa-star text-[10px]"></i>
                            +{{ $h->pontos }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Voltar --}}
    <a href="{{ route('portal.dashboard') }}"
       class="flex items-center justify-center gap-2 py-3 bg-white border border-gray-200 rounded-2xl text-sm font-black text-gray-500 hover:bg-gray-50 transition shadow-sm">
        <i class="fas fa-arrow-left text-xs"></i> Voltar ao Dashboard
    </a>

</div>

@endsection
