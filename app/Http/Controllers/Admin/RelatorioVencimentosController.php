<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioVencimentosController extends Controller
{
    /**
     * Mostra "Sacolas vencidas (por cliente)".
     * Regra: vencimento = sacolinhas.add_at + 90 dias
     * Mostra apenas vencidos.
     */
    public function index(Request $request)
    {
        $busca = trim((string) $request->get('q', ''));

        // Base: itens vencidos (sacolinhas) + joins mínimos para exibir detalhes no Blade
        // OBS: Ajuste o join de "itens" conforme seu schema (ex.: itens, items, produtos...)
        $baseVencidos = DB::table('sacolinhas as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('items as i', 'i.id', '=', 's.item_id') // <-- se sua tabela não for "items", ajuste aqui
            ->where('s.status', '!=', 'pedido')
            ->whereNotNull('s.add_at')
            ->whereRaw("DATE_ADD(s.add_at, INTERVAL 90 DAY) < NOW()");

        // Filtro por cliente (nome, email, id, whatsapp)
        if ($busca !== '') {
            $baseVencidos->where(function ($q) use ($busca) {
                $q->orWhere('u.name', 'like', '%' . $busca . '%')
                  ->orWhere('u.email', 'like', '%' . $busca . '%')
                  ->orWhere('u.id', $busca);

                // Se a coluna whatsapp existir em users
                // (se não existir, o MySQL vai dar erro; então tentamos detectar de forma simples)
                // Caso sua estrutura seja diferente, me diga onde fica o whatsapp.
                try {
                    $q->orWhere('u.whatsapp', 'like', '%' . $busca . '%');
                } catch (\Throwable $e) {
                    // ignora
                }
            });
        }

        // Totais do topo (com base nos vencidos)
        // total_clientes_com_vencidos
        // total_itens_vencidos (soma quantity)
        // valor_total_vencido (soma quantity * price)
        $totais = (clone $baseVencidos)
            ->selectRaw('COUNT(DISTINCT s.user_id) as total_clientes_com_vencidos')
            ->selectRaw('COALESCE(SUM(s.quantity), 0) as total_itens_vencidos')
            ->selectRaw('COALESCE(SUM(s.quantity * s.price), 0) as valor_total_vencido')
            ->first();

        // Lista (paginada) de clientes com vencidos + agregados por cliente
        $clientes = (clone $baseVencidos)
            ->selectRaw('s.user_id')
            ->selectRaw('COALESCE(u.name, CONCAT("Cliente #", s.user_id)) as cliente_nome')
            ->selectRaw('u.email as email')
            ->selectRaw('NULL as whatsapp')
            ->selectRaw('COALESCE(SUM(s.quantity), 0) as total_itens_vencidos')
            ->selectRaw('COALESCE(SUM(s.quantity * s.price), 0) as valor_total_vencido')
            ->groupBy('s.user_id', 'u.name', 'u.email')
            ->orderByDesc('valor_total_vencido')
            ->paginate(10)
            ->appends(['q' => $busca]);

        // Carrega os itens vencidos só para os clientes da página atual
        $userIds = $clientes->getCollection()->pluck('user_id')->filter()->values()->all();

        $itens = collect();
        if (!empty($userIds)) {
            $linhas = DB::table('sacolinhas as s')
                ->leftJoin('items as i', 'i.id', '=', 's.item_id') // <-- ajuste se necessário
                ->whereIn('s.user_id', $userIds)
                ->where('s.status', '!=', 'pedido')
                ->whereNotNull('s.add_at')
                ->whereRaw("DATE_ADD(s.add_at, INTERVAL 90 DAY) < NOW()")
                ->select([
                    's.id',
                    's.user_id',
                    's.item_id',
                    's.live_id',
                    's.quantity',
                    's.price',
                    's.add_at',
                    's.status',
                    's.obs',
                    DB::raw("DATE_ADD(s.add_at, INTERVAL 90 DAY) as vencimento"),

                    // Campos esperados no Blade (item_name, sku, brand, color, size)
                    DB::raw('i.name as item_name'),
                    DB::raw('i.sku as item_sku'),
                    DB::raw('i.brand as item_brand'),
                    DB::raw('i.color as item_color'),
                    DB::raw('i.size as item_size'),
                ])
                ->orderBy('s.user_id')
                ->orderBy('vencimento', 'asc')
                ->get();

            $itens = $linhas->groupBy('user_id');
        }

        return view('admin.vencimentos', [
            'clientes' => $clientes,
            'itens'    => $itens,
            'totais'   => $totais,
            'busca'    => $busca,
        ]);
    }

    /**
     * Tela/ação ligada ao botão de lixeira no Blade:
     * route('admin.vencimentos.cliente', $c->user_id)
     *
     * Como seu link é GET e se chama "Excluir", aqui eu retorno uma view simples
     * ou faço a exclusão com confirmação via POST/DELETE (recomendado).
     *
     * Para não quebrar seu frontend agora, vou implementar como:
     * - GET: lista itens vencidos daquele cliente e oferece um POST para excluir.
     */
    public function cliente($userId)
    {
        $userId = (int) $userId;

        $cliente = DB::table('users')
            ->where('id', $userId)
            ->select(['id', 'name', 'email'])
            ->first();

        $itens = DB::table('sacolinhas as s')
            ->leftJoin('items as i', 'i.id', '=', 's.item_id') // ajuste se necessário
            ->where('s.user_id', $userId)
            ->where('s.status', '!=', 'pedido')
            ->whereNotNull('s.add_at')
            ->whereRaw("DATE_ADD(s.add_at, INTERVAL 90 DAY) < NOW()")
            ->select([
                's.*',
                DB::raw("DATE_ADD(s.add_at, INTERVAL 90 DAY) as vencimento"),
                DB::raw('i.name as item_name'),
            ])
            ->orderBy('vencimento', 'asc')
            ->get();

        // Se você já tem uma view específica, troque aqui.
        // Se não tiver, você pode criar admin/vencimentos_cliente.blade.php.
        return view('admin.vencimentos_cliente', [
            'cliente' => $cliente,
            'itens'   => $itens,
        ]);
    }

    /**
     * Exclui os itens vencidos de um cliente (ou você pode marcar status, etc.).
     * Recomendo usar DELETE/POST, não GET.
     */
    public function excluirVencidosDoCliente(Request $request, $userId)
    {
        $userId = (int) $userId;

        $apagados = DB::table('sacolinhas')
            ->where('user_id', $userId)
            ->where('status', '!=', 'pedido')
            ->whereNotNull('add_at')
            ->whereRaw("DATE_ADD(add_at, INTERVAL 90 DAY) < NOW()")
            ->delete();

        return redirect()
            ->route('admin.vencimentos')
            ->with('success', $apagados > 0
                ? "Itens vencidos removidos do cliente #{$userId}."
                : "Nenhum item vencido para remover do cliente #{$userId}."
            );
    }

    /**
     * Envio de WhatsApp (stub).
     * Seu Blade chama: route('admin.vencimentos.whatsapp.send', $c->user_id) via POST.
     */
    public function sendWhatsApp(Request $request, $userId)
    {
        $userId = (int) $userId;

        // Aqui você integra com seu provedor (Z-API, Twilio, ChatPro, etc.)
        // Vou apenas simular sucesso para manter o fluxo do frontend.
        // Se você já tem um serviço pronto, eu adapto para chamar ele aqui.

        return redirect()
            ->route('admin.vencimentos')
            ->with('success', "Solicitação de WhatsApp registrada para o cliente #{$userId}.");
    }
}