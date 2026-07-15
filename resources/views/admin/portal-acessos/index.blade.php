@extends('layouts.app')
@section('title', 'Histórico de Acessos ao Portal')
@section('brand_route', 'admin.portal-acessos.index')
@section('brand_icon', 'fas fa-history')

@section('content')
@php
function parseUserAgent($ua) {
    if (empty($ua)) return ['platform' => 'Desconhecido', 'browser' => 'Outro', 'icon' => 'fas fa-question-circle'];
    
    $platform = 'Desktop';
    $icon = 'fas fa-desktop text-gray-500';
    if (preg_match('/(android|iphone|ipad|ipod)/i', $ua)) {
        $platform = 'Celular';
        $icon = 'fas fa-mobile-alt text-gray-500';
    }
    
    $browser = 'Outro';
    if (preg_match('/chrome/i', $ua) && !preg_match('/chromeframe|edge|opr|opera|silk/i', $ua)) {
        $browser = 'Chrome';
    } elseif (preg_match('/safari/i', $ua) && !preg_match('/chrome|crios|edge/i', $ua)) {
        $browser = 'Safari';
    } elseif (preg_match('/firefox/i', $ua)) {
        $browser = 'Firefox';
    } elseif (preg_match('/edge/i', $ua)) {
        $browser = 'Edge';
    }
    
    return [
        'platform' => $platform,
        'browser' => $browser,
        'icon' => $icon
    ];
}
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Controle de Acessos</h1>
        <p class="text-sm text-gray-400 mt-0.5">Monitore os acessos e visualizações dos clientes no Portal</p>
    </div>
</div>

{{-- Filtros --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Filtrar Acessos</h3>
    <form action="{{ route('admin.portal-acessos.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
            <label for="user_id" class="block text-xs font-bold text-gray-500 uppercase mb-1">Cliente</label>
            <select name="user_id" id="user_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todos os clientes</option>
                @foreach($clients as $c)
                    <option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }} {{ $c->apelido ? "({$c->apelido})" : '' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="route_name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Página/Ação</label>
            <select name="route_name" id="route_name" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Todas as ações</option>
                @foreach($routes as $r)
                    <option value="{{ $r->route_name }}" {{ request('route_name') == $r->route_name ? 'selected' : '' }}>
                        {{ $r->action_name ?: $r->route_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="date_start" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Início</label>
            <input type="date" name="date_start" id="date_start" value="{{ request('date_start') }}" 
                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
            <label for="date_end" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Fim</label>
            <input type="date" name="date_end" id="date_end" value="{{ request('date_end') }}" 
                   class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition duration-150 flex items-center justify-center gap-1.5 shadow-sm">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="{{ route('admin.portal-acessos.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2.5 px-3 rounded-xl transition duration-150 flex items-center justify-center shadow-sm" title="Limpar Filtros">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Total de visualizações --}}
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl flex-shrink-0">
            <i class="fas fa-eye"></i>
        </div>
        <div>
            <span class="block text-xs text-gray-400 font-bold uppercase tracking-wider">Visualizações</span>
            <span class="text-2xl font-black text-gray-800">{{ number_format($totalAcessos, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Clientes ativos --}}
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center text-green-600 text-xl flex-shrink-0">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <span class="block text-xs text-gray-400 font-bold uppercase tracking-wider">Clientes Ativos</span>
            <span class="text-2xl font-black text-gray-800">{{ number_format($clientesAtivos, 0, ',', '.') }}</span>
        </div>
    </div>

    {{-- Página mais acessada --}}
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 text-xl flex-shrink-0">
            <i class="fas fa-star"></i>
        </div>
        <div class="overflow-hidden">
            <span class="block text-xs text-gray-400 font-bold uppercase tracking-wider">Página Top</span>
            <span class="text-base font-bold text-gray-800 block truncate" title="{{ $mostVisitedPageLog->action_name ?? 'Nenhuma' }}">
                {{ $mostVisitedPageLog->action_name ?? 'Nenhuma' }}
            </span>
        </div>
    </div>

    {{-- Cliente mais ativo --}}
    <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-600 text-xl flex-shrink-0">
            <i class="fas fa-fire"></i>
        </div>
        <div class="overflow-hidden">
            <span class="block text-xs text-gray-400 font-bold uppercase tracking-wider">Cliente Mais Ativo</span>
            <span class="text-base font-bold text-gray-800 block truncate" title="{{ $mostActiveUser ? $mostActiveUser->name : 'Nenhum' }}">
                {{ $mostActiveUser ? ($mostActiveUser->apelido ?: explode(' ', $mostActiveUser->name)[0]) : 'Nenhum' }}
            </span>
            @if($mostActiveUser)
                <span class="text-xs text-gray-400">{{ $mostActiveUser->total_acessos }} acessos</span>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    {{-- Ranking de páginas mais acessadas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:col-span-1">
        <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-indigo-500"></i> Páginas Mais Acessadas
        </h3>
        <div class="space-y-4">
            @forelse($topPages as $idx => $p)
                @php
                    $percentage = $totalAcessos > 0 ? ($p->total / $totalAcessos) * 100 : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-1">
                        <span class="text-gray-700 truncate max-w-[70%]" title="{{ $p->action_name ?: $p->route_name }}">
                            {{ $idx + 1 }}. {{ $p->action_name ?: $p->route_name }}
                        </span>
                        <span class="text-gray-500">{{ $p->total }} views ({{ round($percentage) }}%)</span>
                    </div>
                    <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-gray-400 text-xs py-4 text-center">Nenhum dado de acesso registrado.</p>
            @endforelse
        </div>
    </div>

    {{-- Listagem de logs detalhados --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:col-span-2">
        <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-list text-indigo-500"></i> Histórico Detalhado
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 text-xs font-bold uppercase tracking-wider">
                        <th class="pb-3 pr-2">Cliente</th>
                        <th class="pb-3 px-2">Visualizou / Ação</th>
                        <th class="pb-3 px-2">Dispositivo / IP</th>
                        <th class="pb-3 pl-2">Data/Hora</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($acessos as $acesso)
                        @php
                            $ua = parseUserAgent($acesso->user_agent);
                        @endphp
                        <tr>
                            <td class="py-3.5 pr-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 border overflow-hidden flex items-center justify-center flex-shrink-0 relative">
                                        @if($acesso->user->photo)
                                            <img src="{{ asset('storage/' . $acesso->user->photo) }}" alt="Foto" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xs font-bold text-gray-400">
                                                {{ mb_strtoupper(mb_substr($acesso->user->name, 0, 1)) }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="overflow-hidden">
                                        <a href="{{ route('admin.sacolinha.gestao', ['user_id' => $acesso->user_id]) }}" class="font-bold text-gray-700 hover:text-indigo-600 truncate block max-w-[120px]" title="{{ $acesso->user->name }}">
                                            {{ $acesso->user->apelido ?: explode(' ', $acesso->user->name)[0] }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-2">
                                <div class="font-semibold text-gray-800 text-xs">
                                    {{ $acesso->action_name ?: 'Navegou' }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono truncate max-w-[180px]" title="{{ $acesso->url }}">
                                    {{ parse_url($acesso->url, PHP_URL_PATH) }}
                                </div>
                            </td>
                            <td class="py-3.5 px-2">
                                <div class="flex items-center flex-wrap gap-1">
                                    <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-500 text-[10px] font-bold px-1.5 py-0.5 rounded border border-gray-100">
                                        <i class="{{ $ua['icon'] }}"></i>
                                        {{ $ua['platform'] }}
                                    </span>
                                    <span class="inline-flex items-center bg-indigo-50 text-indigo-600 text-[10px] font-bold px-1.5 py-0.5 rounded">
                                        {{ $ua['browser'] }}
                                    </span>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5 font-mono">
                                    {{ $acesso->ip_address }}
                                </div>
                            </td>
                            <td class="py-3.5 pl-2 text-xs text-gray-500 font-medium">
                                <div>{{ $acesso->created_at->diffForHumans() }}</div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ $acesso->created_at->format('d/m/Y H:i:s') }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-6 text-gray-400 text-xs">
                                Nenhum log de acesso correspondente encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $acessos->links() }}
        </div>
    </div>
</div>
@endsection
