<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PortalAcesso;

class TrackPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Somente loga requisições GET que não são AJAX e quando o usuário está logado
        if ($request->isMethod('GET') && !$request->ajax() && auth()->check()) {
            $user = auth()->user();
            
            // Determinar um nome amigável para a página baseado na rota
            $routeName = $request->route() ? $request->route()->getName() : null;
            $actionName = $this->resolveActionName($routeName, $request);

            try {
                PortalAcesso::create([
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'url' => $request->fullUrl(),
                    'route_name' => $routeName,
                    'action_name' => $actionName,
                ]);
            } catch (\Exception $e) {
                // Silenciar erros de log para não quebrar a navegação do usuário
                logger()->error('Erro ao registrar acesso no portal: ' . $e->getMessage());
            }
        }

        return $response;
    }

    private function resolveActionName(?string $routeName, Request $request): string
    {
        if (!$routeName) {
            return 'Acessou página desconhecida';
        }

        switch ($routeName) {
            case 'portal.dashboard':
                return 'Visualizou o Dashboard';
            case 'portal.perfil':
                return 'Visualizou o Perfil';
            case 'portal.pedidos':
                return 'Visualizou os Pedidos';
            case 'portal.sacolinha':
                return 'Visualizou a Sacolinha';
            case 'portal.movimentacao':
                return 'Visualizou o Extrato / Movimentações';
            case 'portal.desafios':
                return 'Visualizou os Desafios';
            case 'portal.checkout.show':
                $pedidoId = $request->route('pedido');
                return "Acessou Checkout do Pedido #{$pedidoId}";
            case 'portal.checkout_lancamento.show':
                $lancamentoId = $request->route('lancamento');
                return "Acessou Checkout de Recarga #{$lancamentoId}";
            case 'portal.ranking':
                return 'Visualizou o Ranking de Pontuações';
            default:
                return 'Navegou no portal';
        }
    }
}
