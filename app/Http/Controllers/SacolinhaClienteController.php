<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Sacolinha;
use App\Models\User;
use App\Models\Item;

class SacolinhaClienteController extends Controller
{
    // Página principal - idêntica ao Live mas sem live_id
    public function index()
    {
        return view('admin.sacolinhas.sacolinha-cliente');
    }

    // API: Pega sacolinhas do cliente (em vez de pegar da live)
    public function getSacolinhasByCliente($clienteId)
    {
        try {
            Log::info("Buscando sacolinhas para cliente ID: {$clienteId}");

            $sacolinhas = DB::select("
                SELECT s.id, s.user_id, u.name as client_name, u.email as client_email, u.avatar,
                       COUNT(s.item_id) as total_items,
                       SUM(COALESCE(s.price, i.preco)) as total_price,
                       GROUP_CONCAT(
                           JSON_OBJECT(
                               'item_id', s.item_id,
                               'codigo', i.codigo,
                               'nome_do_produto', i.nome_do_produto,
                               'preco', COALESCE(s.price, i.preco),
                               'obs', s.obs,
                               'pedido', s.pedido,
                               'status', i.status
                           )
                       ) as items
                FROM sacolinhas s
                INNER JOIN users u ON s.user_id = u.id
                INNER JOIN items i ON s.item_id = i.id
                WHERE s.user_id = ? AND i.status = 'sacolinha'
                GROUP BY s.user_id, u.name, u.email, u.avatar
            ", [$clienteId]);

            Log::info("Sacolinhas encontradas: " . count($sacolinhas));

            // Processa resposta para manter compatibilidade
            $result = [];
            foreach ($sacolinhas as $sacolinha) {
                $result = [
                    'id' => $sacolinha->id,
                    'user_id' => $sacolinha->user_id,
                    'client_name' => $sacolinha->client_name,
                    'client_email' => $sacolinha->client_email,
                    'avatar' => $sacolinha->avatar,
                    'total_items' => $sacolinha->total_items,
                    'total_price' => $sacolinha->total_price,
                    'items' => json_decode('[' . $sacolinha->items . ']', true)
                ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar sacolinhas do cliente: " . $e->getMessage());
            return response()->json(['error' => 'Erro ao carregar dados'], 500);
        }
    }

    // API: Adiciona item à sacolinha do cliente
	public function store(Request $request)
	{
		// Inicia uma transação para garantir integridade
		\DB::beginTransaction();

		try {
			$validated = $request->validate([
				'user_id' => 'required|integer|exists:users,id',
				'item_id' => 'required|integer|exists:items,id',
				'item_price' => 'required|numeric|min:0',
				'item_obs' => 'nullable|string',
				'item_pedido' => 'nullable|string',
			]);

			Log::info("Adicionando item {$validated['item_id']} para cliente {$validated['user_id']}");

			// Verificar se item já está na sacolinha
			$existe = Sacolinha::where('user_id', $validated['user_id'])
							  ->where('item_id', $validated['item_id'])
							  ->first();

			if ($existe) {
				\DB::rollBack();
				return response()->json(['error' => 'Item já está na sacolinha deste cliente'], 409);
			}

			// 1. Criar o registro na sacolinha
			$sacolinha = Sacolinha::create([
				'user_id' => $validated['user_id'],
				'item_id' => $validated['item_id'],
				'live_id' => 1, // Live padrão
				'price' => $validated['item_price'],
				'obs' => $validated['item_obs'] ?? '',
				'pedido' => $validated['item_pedido'] ?? '',
				'add_at' => now(),
				'status' => 'ativo'
			]);

			// 2. Atualizar o status do item na tabela 'items'
			// Assumindo que o status desejado seja 'vendido' ou 'reservado'
			\App\Models\Item::where('id', $validated['item_id'])->update([
				'status' => 'sacolinha' 
			]);

			\DB::commit();
			Log::info("Item adicionado e status atualizado com sucesso. ID Sacolinha: {$sacolinha->id}");

			return response()->json([
				'success' => true,
				'message' => 'Item adicionado com sucesso!',
				'data' => $sacolinha
			]);

		} catch (\Exception $e) {
			\DB::rollBack();
			Log::error("Erro ao adicionar item: " . $e->getMessage());
			return response()->json(['error' => $e->getMessage()], 500);
		}
	}

    // API: Remove item da sacolinha
    public function removeItems(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'item_id' => 'required|integer'
            ]);

            Log::info("Removendo item {$validated['item_id']} do cliente {$validated['user_id']}");

            $deleted = Sacolinha::where('user_id', $validated['user_id'])
                               ->where('item_id', $validated['item_id'])
                               ->delete();

            if ($deleted === 0) {
                return response()->json(['error' => 'Item não encontrado'], 404);
            }

            Log::info("Item removido com sucesso");

            return response()->json(['success' => true, 'message' => 'Item removido com sucesso!']);

        } catch (\Exception $e) {
            Log::error("Erro ao remover item: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}