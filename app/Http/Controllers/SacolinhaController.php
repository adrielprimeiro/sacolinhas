<?php

namespace App\Http\Controllers;

use App\Models\Sacolinhas;
use App\Models\Live;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Item;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\ClienteLimite;


class SacolinhaController extends Controller
{
    public function index()
    {
        return view('admin.live.index');
    }

    public function store(Request $request)
    {
        try {
            // Validação básica
            $request->validate([
                'client_id' => 'required|integer|exists:users,id',
                'item_id' => 'required|integer|exists:items,id',
                'item_price' => 'required|numeric|min:0',
            ]);

            $result = DB::transaction(function () use ($request) {
                
                // 1. Buscar live ativa
                $liveAtiva = DB::table('lives')
                              ->where('ativo', 1)
                              ->orderBy('created_at', 'desc')
                              ->first();

                if (!$liveAtiva) {
                    throw new \Exception('Não há live ativa no momento!', 400);
                }

                // 2. Verificar se o item já está na sacola (duplicidade)
                $sacolaExistente = Sacolinhas::where([
                    'user_id' => $request->client_id,
                    'item_id' => $request->item_id,
                    'live_id' => $liveAtiva->id
                ])->first();

                if ($sacolaExistente) {
                    return ['duplicate' => true];
                }

                // ---------------------------------------------------------
                // ✅ LÓGICA DE LIMITE SOLICITADA
                // ---------------------------------------------------------
                
                // 1. Buscar o valor de limite_disponivel
                $limiteDisponivel = DB::table('cliente_limites')
                    ->where('user_id', $request->client_id)
                    ->where('ativo', 1)
                    ->value('limite_disponivel') ?? 0;

                $priceToStore = (float) $request->item_price;

                // 2. Verificar se o valor é maior ou igual ao do item
                // 2.1 Menor -> 'em analise' | 2.2 Maior ou igual -> 'live'
                $status = ($limiteDisponivel >= $priceToStore) ? 'live' : 'em analise';

                // ---------------------------------------------------------

                // Preparar dados para gravação
                $columns = \Schema::getColumnListing('sacolinhas');
                $data = [
                    'user_id' => $request->client_id,
                    'item_id' => $request->item_id,
                    'live_id' => $liveAtiva->id,
                    'add_at'  => now(),
                    'status'  => $status, // ✅ Status definido pela lógica acima
                    'obs'     => $request->obs ?? null
                ];

                if (in_array('quantity', $columns)) $data['quantity'] = 1;
                if (in_array('price', $columns))    $data['price']    = $priceToStore;

                // 3. Gravar na sacolinha
                // A matemática (UPDATE cliente_limites) é feita pelo seu Trigger no MySQL
                $sacolinha = Sacolinhas::create($data);

				// 4. Atualizar status do item
				DB::table('items')
					->where('id', $request->item_id)
					->update([
						'status' => 'solicitado na live',
						'updated_at' => now(),
					]);

                return [
                    'success' => true,
                    'sacolinha' => $sacolinha,
                    'status' => $status,
                    'price' => $priceToStore
                ];
            });
			
			
            if (isset($result['duplicate'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este item já está na sacola deste cliente!'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item adicionado à sacola com sucesso!',
                'data' => [
                    'sacolinha' => $result['sacolinha'],
                    'status' => $result['status'],
                    'price' => $result['price'],
                    'formatted_price' => 'R$ ' . number_format($result['price'], 2, ',', '.'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao adicionar item à sacola: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ MANTENHA APENAS UMA VEZ ESTE MÉTODO OU REMOVA SE NÃO USAR MAIS
    private function getClienteLimiteDisponivel(int $userId): float
    {
        return (float) DB::table('cliente_limites')
            ->where('user_id', $userId)
            ->where('ativo', 1)
            ->value('limite_disponivel') ?? 0.0;
    }


    


	public function getBagsByLive($liveId = null)
	{
		try {
			Log::info("getBagsByLive iniciado com liveId: " . $liveId);
			
			// Verificar se as colunas existem
			$columns = \Schema::getColumnListing('sacolinhas');
			Log::info("Colunas da tabela sacolinhas: " . implode(', ', $columns));
			
			if (!$liveId) {
				$live = DB::table('lives')
						  ->where('ativo', 1)
						  ->orderBy('created_at', 'desc')
						  ->first();
				
				if (!$live) {
					return response()->json([
						'success' => true,
						'data' => [],
						'message' => 'Nenhuma live ativa encontrada'
					]);
				}
				
				$liveId = $live->id;
			}

			// Verificar se existem registros
			$count = DB::table('sacolinhas')->where('live_id', $liveId)->count();
			Log::info("Registros encontrados: " . $count);

			if ($count === 0) {
				return response()->json([
					'success' => true,
					'data' => [],
					'live_id' => $liveId,
					'total_bags' => 0,
					'total_items' => 0,
					'total_value' => 0
				]);
			}

			// Query adaptada às colunas existentes
			$selectFields = [
				's.id as sacolinha_id',
				's.user_id',
				's.item_id',
				's.add_at',
				'i.status', 
				's.obs',
				'u.id as user_id',
				'u.name as user_name', 
				'u.email as user_email',
				'u.instagram as user_instagram', // ✨ NOVO
				'u.tiktok as user_tiktok',       // ✨ NOVO
				'u.whatsapp as user_whatsapp' ,       // ✨ NOVO
				'i.id as item_id',
				'i.nome_do_produto as item_name',
				'i.codigo as item_sku',
				'i.marca as item_brand',
				'i.cor as item_color',
				'i.tamanho as item_size'
			];

			// MODIFICADO: Sempre usar o preço armazenado na sacola
			if (in_array('price', $columns)) {
				$selectFields[] = 's.price';
			} else {
				$selectFields[] = 'i.preco as price';
			}

			$sacolinhas = DB::table('sacolinhas as s')
				->join('users as u', 's.user_id', '=', 'u.id')
				->join('items as i', 's.item_id', '=', 'i.id')
				->where('s.live_id', $liveId)
				->select($selectFields)
				->orderBy('s.add_at', 'desc')
				->get();

			Log::info("Query executada. Registros retornados: " . $sacolinhas->count());

			// MODIFICADO: Processar resultados para itens únicos
			$bagsByClient = $sacolinhas->groupBy('user_id')->map(function ($clientSacolinhas) {
				$firstItem = $clientSacolinhas->first();
				
				$items = $clientSacolinhas->map(function ($sacola) {
					$itemPrice = $sacola->price ?? 0;
					
					return [
						'sacolinha_id' => $sacola->sacolinha_id,
						'item_id' => $sacola->item_id,
						'item_name' => $sacola->item_name,
						'item_sku' => $sacola->item_sku,
						'item_brand' => $sacola->item_brand,
						'item_color' => $sacola->item_color,
						'item_size' => $sacola->item_size,
						'price' => (float) $itemPrice,
						'formatted_total_price' => 'R$ ' . number_format($itemPrice, 2, ',', '.'),
						'status' => $sacola->status,
						'added_at' => $sacola->add_at,
						'obs' => $sacola->obs
					];
				});

				$totalItems = $items->count();
				$totalValue = $items->sum('price');

				// ✨ MODIFICADO: Adicionar instagram e tiktok
				return [
					'client' => [
						'id' => $firstItem->user_id,
						'name' => $firstItem->user_name,
						'email' => $firstItem->user_email,
						'instagram' => $firstItem->user_instagram, // ✨ NOVO
						'tiktok' => $firstItem->user_tiktok,       // ✨ NOVO
						'whatsapp' => $firstItem->user_whatsapp,       // ✨ NOVO
						'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($firstItem->user_name) . '&background=007bff&color=fff&size=128'
					],
					'items' => $items->values(),
					'total_items' => $totalItems,
					'total_value' => $totalValue,
					'formatted_total' => 'R$ ' . number_format($totalValue, 2, ',', '.')
				];
			});

			return response()->json([
				'success' => true,
				'data' => $bagsByClient->values(),
				'live_id' => $liveId,
				'total_bags' => $bagsByClient->count(),
				'total_items' => $sacolinhas->count(),
				'total_value' => $bagsByClient->sum('total_value')
			]);

		} catch (\Exception $e) {
			Log::error("Erro completo em getBagsByLive: " . $e->getMessage());
			Log::error("Linha: " . $e->getLine());
			Log::error("Arquivo: " . $e->getFile());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro ao buscar sacolinhas: ' . $e->getMessage()
			], 500);
		}
	}

	public function removeItems(Request $request)
	{
		Log::info("removeItems HIT! All data: " . json_encode($request->all()));
       
		try {
			$request->validate([
				'item_id' => 'required|integer',
				'user_id' => 'required|integer',
				'live_id' => 'required|integer'
			]);

			// Usamos transaction para garantir que o status 'estoque' seja a palavra final
			DB::transaction(function () use ($request) {
				
				// 1. Buscar a sacola
				$sacola = Sacolinhas::where([
					'item_id' => $request->item_id,
					'user_id' => $request->user_id,
					'live_id' => $request->live_id
				])->first();

				if (!$sacola) {
					throw new \Exception('Item não encontrado na sacola', 404);
				}

				// (Opcional) Cálculo de valor caso você use para atualizar limites depois
				// $valorMudanca = ($sacola->price ?? 0) * ($sacola->quantity ?? 1);

				// 2. Remover da sacola (Isso dispara o Trigger de DELETE do BD)
				$sacola->delete();

				// 3. FORÇA BRUTA: Atualizar status para 'estoque'
				// Executado após o delete, garantindo que sobrescreve qualquer alteração do Trigger
				DB::table('items')
					->where('id', $request->item_id)
					->update([
						'status' => 'estoque',
						'updated_at' => now()
					]);
			});

			return response()->json([
				'success' => true,
				'message' => 'Item removido da sacola e devolvido ao estoque!'
			]);

		} catch (\Exception $e) {
			// Tratamento para retornar 404 se foi a exceção que lançamos acima
			$code = $e->getCode();
			if ($code < 100 || $code > 599) $code = 500;

			Log::error("Erro ao remover item: " . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => $e->getMessage()
			], $code);
		}
	}
	
    /**
     * Retorna as sacolas de uma live com o status CORRETO do item
     */
	public function getSacolinhasByLive($liveId = null)
	{
		try {
			Log::info("getSacolinhasByLive iniciado com liveId: " . $liveId);
			
			// Se não fornecido liveId, buscar live ativa
			if (!$liveId) {
				$live = DB::table('lives')
					->where('ativo', 1)
					->orderBy('created_at', 'desc')
					->first();
				
				if (!$live) {
					return response()->json([
						'success' => true,
						'data' => [],
						'message' => 'Nenhuma live ativa encontrada'
					]);
				}
				
				$liveId = $live->id;
			}

			// Verificar se existem sacolas para esta live
			$count = DB::table('sacolinhas')->where('live_id', $liveId)->count();
			Log::info("Registros encontrados para live $liveId: " . $count);

			if ($count === 0) {
				return response()->json([
					'success' => true,
					'data' => [],
					'live_id' => $liveId,
					'total_bags' => 0,
					'total_items' => 0,
					'total_value' => 0
				]);
			}

			// Query unificada com tratamento consistente de preços
			$sacolinhas = DB::table('sacolinhas as s')
				->join('users as u', 's.user_id', '=', 'u.id')
				->join('items as i', 's.item_id', '=', 'i.id')
				->where('s.live_id', $liveId)
				->select([
					's.id as sacolinha_id',
					's.user_id',
					's.item_id',
					's.add_at',
					's.status as sacolinha_status',
					's.obs',
					// CORREÇÃO PRINCIPAL: Priorizar preço da sacolinha, fallback para preço do item
					DB::raw('COALESCE(s.price, i.preco) as final_price'),
					// Dados do usuário
					'u.id as user_id',
					'u.name as user_name', 
					'u.email as user_email',
					'u.avatar',
					// Dados do item
					'i.id as item_id',
					'i.nome_do_produto as item_name',
					'i.codigo as item_sku',
					'i.marca as item_brand',
					'i.cor as item_color',
					'i.tamanho as item_size',
					'i.status as item_status',
					'i.imagem as item_image'
				])
				->orderBy('u.name')
				->orderBy('s.add_at', 'desc')
				->get();

			Log::info("Query executada. Registros retornados: " . $sacolinhas->count());

			// Processar e retornar no formato existente (compatibilidade)
			$result = $sacolinhas->groupBy('user_id')->map(function ($userItems) {
				$firstItem = $userItems->first();
				
				return [
					'client' => [
						'id' => $firstItem->user_id,
						'name' => $firstItem->user_name,
						'email' => $firstItem->user_email,
						'avatar' => $firstItem->avatar
					],
					'total_items' => $userItems->count(),
					'total_value' => $userItems->sum('final_price'),
					'items' => $userItems->map(function ($item) {
						return [
							'sacolinha_id' => $item->sacolinha_id,
							'item_id' => $item->item_id,
							'item_name' => $item->item_name,
							'item_sku' => $item->item_sku,
							'item_brand' => $item->item_brand,
							'item_color' => $item->item_color,
							'item_size' => $item->item_size,
							'item_price' => (float) $item->final_price,
							'item_status' => $item->item_status,
							'sacolinha_status' => $item->sacolinha_status,
							'item_image' => $item->item_image,
							'obs' => $item->obs,
							'added_at' => $item->add_at
						];
					})->values()
				];
			})->values();

			return response()->json([
				'success' => true,
				'data' => $result,
				'live_id' => $liveId,
				'total_bags' => $result->count(),
				'total_items' => $sacolinhas->count(),
				'total_value' => $result->sum('total_value')
			]);

		} catch (\Exception $e) {
			Log::error("Erro em getSacolinhasByLive: " . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro ao buscar sacolinhas: ' . $e->getMessage()
			], 500);
		}
	}
	
	
	
	/**
	 * Atualizar status do item (independente da sacolinha)
	 * MÉTODO NOVO - adicionar no final da classe SacolinhaController
	 */
	public function updateItemStatus(Request $request, $itemId)
	{
		try {
			Log::info("updateItemStatus iniciado para item: $itemId");
			
			// Validar entrada
			$request->validate([
				'status' => 'required|string|in:disponivel,vendido,reservado,sacolinha,estoque'
			]);

			$newStatus = $request->input('status');
			Log::info("Tentando alterar status do item $itemId para: $newStatus");

			// Verificar se item existe
			$item = DB::table('items')->where('id', $itemId)->first();
			if (!$item) {
				Log::warning("Item $itemId não encontrado");
				return response()->json([
					'success' => false,
					'message' => 'Item não encontrado'
				], 404);
			}

			Log::info("Item encontrado: {$item->nome_do_produto}, status atual: {$item->status}");

			// Atualizar APENAS status do item (não mexer nas sacolas)
			$updated = DB::table('items')
				->where('id', $itemId)
				->update([
					'status' => $newStatus,
					'updated_at' => now()
				]);

			if (!$updated) {
				Log::error("Falha ao atualizar item $itemId no banco");
				return response()->json([
					'success' => false,
					'message' => 'Erro ao atualizar item no banco de dados'
				], 500);
			}

			Log::info("Status do item $itemId atualizado com sucesso para: $newStatus");

			return response()->json([
				'success' => true,
				'message' => "Status alterado para '{$newStatus}' com sucesso!",
				'data' => [
					'item_id' => $itemId,
					'old_status' => $item->status,
					'new_status' => $newStatus,
					'updated_at' => now()->toISOString()
				]
			]);

		} catch (\Illuminate\Validation\ValidationException $e) {
			Log::warning("Validação falhou para item $itemId: " . json_encode($e->errors()));
			return response()->json([
				'success' => false,
				'message' => 'Dados inválidos',
				'errors' => $e->errors()
			], 422);
			
		} catch (\Exception $e) {
			Log::error("Erro ao atualizar item {$itemId}: " . $e->getMessage());
			Log::error("Linha: " . $e->getLine() . ", Arquivo: " . $e->getFile());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage()
			], 500);
		}
	}	
	
	/**
	 * Consultar status atual do item (método GET)
	 * MÉTODO NOVO - adicionar após updateItemStatus()
	 */
	public function getItemStatus($itemId)
	{
		try {
			Log::info("getItemStatus iniciado para item: $itemId");
			
			// Verificar se item existe
			$item = DB::table('items')->where('id', $itemId)->first();
			
			if (!$item) {
				Log::warning("Item $itemId não encontrado para consulta de status");
				return response()->json([
					'success' => false,
					'message' => 'Item não encontrado'
				], 404);
			}

			Log::info("Status consultado - Item $itemId: {$item->status}");

			return response()->json([
				'success' => true,
				'item_id' => $itemId,
				'status' => $item->status,
				'item_name' => $item->nome_do_produto
			]);

		} catch (\Exception $e) {
			Log::error("Erro ao consultar status do item {$itemId}: " . $e->getMessage());
			
			return response()->json([
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage()
			], 500);
		}
	}	
	
	
	
/**--------------------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	public function consultarView()
		{
			return view('admin.sacolinhas.bag-client');
		}

    /**
     * Retorna todos os itens da sacolinha de um cliente específico.
     *
     * @param int $userId O ID do cliente.
     * @return \Illuminate\Http\JsonResponse
     */
	public function consultarSacolinhaCliente($userId)
	{
		try {
			Log::info("consultarSacolinhaCliente iniciado para userId: {$userId}");
			
			// Validar que userId é um inteiro válido
			$userId = (int) $userId;
			
			if ($userId <= 0) {
				return response()->json(
					['message' => 'ID de cliente inválido.'], 
					400
				);
			}

			// Verificar se cliente existe (query simples e rápida)
			$clienteExists = DB::table('users')
				->where('id', $userId)
				->exists();
				
			if (!$clienteExists) {
				return response()->json(
					['message' => 'Cliente não encontrado.'], 
					404
				);
			}

			// Query ultra-otimizada com apenas as colunas necessárias
			$items = DB::table('sacolinhas as s')
				->where('s.user_id', $userId)
				->where('s.status', '!=', 'enviado') // Filtro na sacolinha também
				->whereNotIn('s.item_id', function ($query) {
					// Excluir itens com status 'enviado'
					$query->select('items.id')
						->from('items')
						->where('items.status', 'enviado');
				})
				->join('items as i', 's.item_id', '=', 'i.id')
				->select(
					's.id as sacolinha_id',
					's.item_id',
					's.quantity',
					's.price as sacolinha_unit_price',
					's.obs',
					's.add_at',
					's.status as sacolinha_status',
					'i.nome_do_produto',					
					'i.codigo',
					'i.marca',
					'i.estado',
					'i.cor',
					'i.tamanho',				
					'i.preco as item_unit_price',
					'i.pedido',
					'i.status as item_status'
				)
				->orderBy('s.add_at', 'desc')
				->limit(500) // Proteger contra consultas muito grandes
				->get();

			Log::info("Query executada. Registros retornados: " . $items->count());

			if ($items->isEmpty()) {
				return response()->json(
					[
						'message' => 'Sacolinha do cliente está vazia.',
						'data' => []
					], 
					200
				);
			}

			// Formatar resposta
			$formattedItems = $items->map(function ($item) {
				return [
					'sacolinha_id' => (int) $item->sacolinha_id,
					'item_id' => (int) $item->item_id,
					'codigo' => $item->codigo,
					'nome_do_produto' => $item->nome_do_produto,
					'quantity' => (int) $item->quantity,
					'marca' => $item->marca,
					'estado' => $item->estado,
					'cor' => $item->cor,
					'tamanho' => $item->tamanho,

					'sacolinha_unit_price' => (float) $item->sacolinha_unit_price,
					'item_unit_price' => (float) $item->item_unit_price,
					'obs' => $item->obs,
					'add_at' => $item->add_at,
					'sacolinha_status' => $item->sacolinha_status,
					'item_status' => $item->item_status,
					'pedido' => $item->pedido,
					'subtotal' => ((float) $item->sacolinha_unit_price) * ((int) $item->quantity)
				];
			});

			return response()->json(
				[
					'message' => 'Itens da sacolinha recuperados com sucesso.',
					'data' => $formattedItems->values(),
					'total' => $formattedItems->count()
				], 
				200
			);

		} catch (\Exception $e) {
			Log::error("Erro ao consultar sacolinha para userId {$userId}: " . $e->getMessage());
			Log::error("Stack: " . $e->getTraceAsString());
			
			return response()->json(
				['message' => 'Erro interno: ' . $e->getMessage()], 
				500
			);
		}
	}	
	
	
    /**
     * Adiciona um novo item à sacolinha (Padrão Live ID = 1)
     */
	public function adicionarItemSacola(Request $request)
	{
		try {
			$validated = $request->validate([
				'user_id' => 'nullable|exists:users,id',
				'item_id' => 'required|exists:items,id',
				'quantity' => 'nullable|integer|min:1',
				'obs' => 'nullable|string',
				'tray' => 'nullable|integer',
				'status' => 'nullable|string',
				'price' => 'nullable|numeric|min:0',
			]);

			$result = DB::transaction(function () use ($validated) {

				$liveId = 1; // 🔒 FIXO: sempre live 1

				$itemId = (int) $validated['item_id'];
				$userId = isset($validated['user_id'])
					? (int) $validated['user_id']
					: (int) auth()->id();

				if (!$userId) {
					return [
						'success' => false,
						'code' => 401,
						'message' => 'Você precisa estar logado.'
					];
				}

				$quantity = (int) ($validated['quantity'] ?? 1);

				// Preço: se não vier, pega do items.preco.
				// Recomendo também IGNORAR o preço do request e sempre buscar do banco (mais seguro),
				// mas mantive seu comportamento.
				$price = $validated['price'] ?? DB::table('items')->where('id', $itemId)->value('preco');

				// 1) lock para evitar concorrência
				$sacolinhaItem = Sacolinhas::where('user_id', $userId)
					->where('item_id', $itemId)
					->where('live_id', $liveId)
					->lockForUpdate()
					->first();

				if ($sacolinhaItem) {
					$sacolinhaItem->quantity += $quantity;
					if (array_key_exists('obs', $validated)) $sacolinhaItem->obs = $validated['obs'];
					if (array_key_exists('price', $validated) && $validated['price'] !== null) $sacolinhaItem->price = $price;
					$sacolinhaItem->save();
					$message = 'Quantidade atualizada.';
				} else {
					$sacolinhaItem = Sacolinhas::create([
						'user_id' => $userId,
						'item_id' => $itemId,
						'live_id' => $liveId,
						'quantity' => $quantity,
						'price' => $price,
						'add_at' => now(),
						'tray' => $validated['tray'] ?? null,
						'status' => $validated['status'] ?? 'pendente',
						'obs' => $validated['obs'] ?? null,
					]);
					$message = 'Item adicionado à sacola.';
				}

				// 2) Atualiza status do item
				DB::table('items')
					->where('id', $itemId)
					->update([
						'status' => 'sacolinha',
						'updated_at' => now()
					]);

				return [
					'success' => true,
					'code' => 201,
					'message' => $message,
					'sacolinha' => $sacolinhaItem
				];
			});

			if (!$result['success']) {
				return response()->json([
					'success' => false,
					'message' => $result['message'],
				], $result['code']);
			}

			return response()->json([
				'success' => true,
				'message' => $result['message'],
				'data' => $result['sacolinha']
			], $result['code']);

		} catch (\Illuminate\Validation\ValidationException $e) {
			return response()->json([
				'success' => false,
				'message' => 'Erro de validação.',
				'errors' => $e->errors(),
			], 422);
		} catch (\Exception $e) {
			Log::error("Erro adicionarItemSacola: " . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro interno: ' . $e->getMessage()
			], 500);
		}
	}

    /**
     * Remove um item específico da sacolinha.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removerItemSacola(Request $request, $sacolinhaId)  // ✅ Param route explícito
    {
        try {
            $validated = $request->validate([
                'sacolinha_id' => ['sometimes', 'exists:sacolinhas,id'],  // ✅ Opcional (usa route fallback)
            ]);

            $sacolinhaId = $sacolinhaId ?: $validated['sacolinha_id'] ?? null;  // ✅ Prioriza route param

            if (!$sacolinhaId || !is_numeric($sacolinhaId)) {
                return response()->json(['message' => 'ID da sacolinha inválido.'], 400);
            }

            $sacolinhaItem = Sacolinhas::find($sacolinhaId);

            if (!$sacolinhaItem) {
                return response()->json(['message' => 'Item na sacolinha não encontrado.'], 404);
            }

            // ✅ ATUALIZAÇÃO DE STATUS DO ITEM (Novo Bloco)
            // Busca o item original usando o ID salvo na sacolinha
            $item = Item::find($sacolinhaItem->item_id);
            
            if ($item) {
                // Atualiza o status para 'Fora da Sacola'
                $item->update(['status' => 'Fora da Sacola']);
            }

            // Remove o registro da sacolinha
            $sacolinhaItem->delete(); 

            Log::info("Item sacolinha_id {$sacolinhaId} removido para user {$sacolinhaItem->user_id}. Status do item atualizado.");

            return response()->json([
                'success' => true,  // ✅ Para JS if(response.success)
                'message' => 'Item removido da sacolinha e status atualizado.'
            ], 200);

        } catch (ValidationException $e) {
            Log::warning("Validação removerItemSacola: " . $e->getMessage(), $e->errors());
            return response()->json(['message' => 'Dados de entrada inválidos.', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error("Erro removerItemSacola sacolinha_id {$sacolinhaId}: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno.'], 500);
        }
    }

    /**
     * Retorna o total de itens e o valor total da sacola de um cliente.
     *
     * @param int $userId O ID do cliente.
     * @return \Illuminate\Http\JsonResponse
     */
    public function obterTotalSacola(int $userId)
    {
        try {
            // Verifica se o cliente existe
            if (!User::find($userId)) {
                return response()->json(['message' => 'Cliente não encontrado.'], 404);
            }

            $sacolinhaItems = Sacolinhas::where('user_id', $userId)->get();

            $totalItems = $sacolinhaItems->sum('quantity');
            $valorTotal = $sacolinhaItems->sum(function ($item) {
                return $item->quantity * $item->price;
            });

            return response()->json([
                'message' => 'Totais da sacolinha recuperados com sucesso.',
                'data' => [
                    'total_itens' => $totalItems,
                    'valor_total' => number_format($valorTotal, 2, '.', ''), // Formata para 2 casas decimais
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            Log::error("Erro ao obter totais da sacolinha para o usuário {$userId}: Cliente não encontrado. " . $e->getMessage());
            return response()->json(['message' => 'Cliente não encontrado.'], 404);
        } catch (\Exception $e) {
            Log::error("Erro interno do servidor ao obter totais da sacolinha para o usuário {$userId}: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao obter totais da sacolinha.'], 500);
        }
    }

    /**
     * Atualiza a quantidade de um item específico na sacolinha.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function atualizarQuantidadeItem(Request $request)
    {
        try {
            $validated = $request->validate([
                'sacolinha_id' => 'required|exists:sacolinhas,id', // ID do registro na tabela 'sacolinhas'
                'quantity' => 'required|integer|min:1',
            ]);

            $sacolinhaItem = Sacolinhas::find($validated['sacolinha_id']);

            if (!$sacolinhaItem) {
                return response()->json(['message' => 'Item na sacolinha não encontrado.'], 404);
            }

            $sacolinhaItem->quantity = $validated['quantity'];
            $sacolinhaItem->save();

            // Carrega o relacionamento com o item para retornar dados completos
            return response()->json(['message' => 'Quantidade do item na sacolinha atualizada com sucesso.', 'data' => $sacolinhaItem->load('item')], 200);

        } catch (ValidationException $e) {
            Log::warning("Erro de validação ao atualizar quantidade do item na sacolinha: " . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json(['message' => 'Dados de entrada inválidos.', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            Log::error("Erro interno do servidor ao atualizar quantidade do item na sacolinha: " . $e->getMessage());
            return response()->json(['message' => 'Erro interno do servidor ao atualizar quantidade do item na sacolinha.'], 500);
        }
    }
//-----------------------------------------------------------------------------------------------------------
	public function pedidoView()
		{
			return view('admin.pedido.index');
		}	
		
//-----------------------------------------------------------------------------------------------------------		
//Controle dos Limites

	public function getLimites(Request $request)
	{
		try {
			$userId = $request->query('user_id') ?: $request->input('user_id');
			
			Log::info("getLimites HIT! user_id: {$userId}", $request->all());

			if (!$userId) {
				Log::warning("getLimites: user_id vazio", $request->all());
				return response()->json(['error' => 'User ID não fornecido'], 400);
			}

			// ✅ FORÇAR BUSCA DIRETA NO BD (ignorar accessors)
			$limite = \DB::table('cliente_limites')
				->where('user_id', $userId)
				->where('ativo', 1)
				->first();

			if (!$limite) {
				Log::info("Criando limites default para user {$userId}");
				
				try {
					\DB::table('cliente_limites')->insert([
						'user_id' => $userId,
						'limite_credito' => 100.00,
						'limite_utilizado' => 0.00,
						'limite_disponivel' => 100.00,
						'ativo' => 1,
						'created_at' => now(),
						'updated_at' => now()
					]);
					
					// Buscar novamente após criar
					$limite = \DB::table('cliente_limites')
						->where('user_id', $userId)
						->where('ativo', 1)
						->first();
						
				} catch (\Exception $e) {
					Log::error("Erro ao criar limite: " . $e->getMessage());
					return response()->json(['error' => 'Erro ao criar limite'], 500);
				}
			}

			Log::info("Limites retornados (BD direto): " . json_encode([
				'credito' => $limite->limite_credito,
				'utilizado' => $limite->limite_utilizado,
				'disponivel' => $limite->limite_disponivel
			]));

			return response()->json([
				'credito' => number_format($limite->limite_credito, 2, ',', '.'),
				'utilizado' => number_format($limite->limite_utilizado, 2, ',', '.'),
				'disponivel' => number_format($limite->limite_disponivel, 2, ',', '.'),
			]);

		} catch (\Exception $e) {
			Log::error("Erro em getLimites: " . $e->getMessage());
			return response()->json(['error' => 'Erro interno do servidor'], 500);
		}
	}


		
}