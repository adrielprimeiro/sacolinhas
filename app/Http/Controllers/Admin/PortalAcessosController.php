<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalAcesso;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalAcessosController extends Controller
{
    public function index(Request $request)
    {
        // 1. Iniciar query de acessos
        $query = PortalAcesso::with('user');

        // Aplicar filtros
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('route_name')) {
            $query->where('route_name', $request->route_name);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        // Listagem de logs paginada
        $acessos = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();

        // 2. Estatísticas Consolidadas (Baseadas nos mesmos filtros de data/cliente para consistência)
        $statsQuery = DB::table('portal_acessos');

        if ($request->filled('user_id')) {
            $statsQuery->where('user_id', $request->user_id);
        }
        if ($request->filled('route_name')) {
            $statsQuery->where('route_name', $request->route_name);
        }
        if ($request->filled('date_start')) {
            $statsQuery->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $statsQuery->whereDate('created_at', '<=', $request->date_end);
        }

        // Total de visualizações
        $totalAcessos = (clone $statsQuery)->count();

        // Clientes únicos ativos
        $clientesAtivos = (clone $statsQuery)->distinct('user_id')->count('user_id');

        // Página mais acessada
        $mostVisitedPageLog = (clone $statsQuery)
            ->select('action_name', DB::raw('count(*) as total'))
            ->groupBy('action_name')
            ->orderByDesc('total')
            ->first();

        // Cliente mais ativo
        $mostActiveUserLog = (clone $statsQuery)
            ->select('user_id', DB::raw('count(*) as total'))
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->first();

        $mostActiveUser = null;
        if ($mostActiveUserLog) {
            $mostActiveUser = User::find($mostActiveUserLog->user_id);
            if ($mostActiveUser) {
                $mostActiveUser->total_acessos = $mostActiveUserLog->total;
            }
        }

        // Top 5 páginas mais visitadas (ranking)
        $topPages = (clone $statsQuery)
            ->select('action_name', 'route_name', DB::raw('count(*) as total'))
            ->groupBy('action_name', 'route_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 3. Carregar dados de apoio para os filtros
        $clients = User::where('role', 'client')->orderBy('name')->get();
        
        $routes = PortalAcesso::select('route_name', 'action_name')
            ->groupBy('route_name', 'action_name')
            ->orderBy('action_name')
            ->get()
            ->filter(fn($r) => !empty($r->route_name));

        return view('admin.portal-acessos.index', compact(
            'acessos',
            'totalAcessos',
            'clientesAtivos',
            'mostVisitedPageLog',
            'mostActiveUser',
            'topPages',
            'clients',
            'routes'
        ));
    }
}
