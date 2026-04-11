<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;  

class PedidoController extends Controller
{
    public function index()
    {
        return view('admin.pedidos.pedidos');
    }

    /* === AUTOCOMPLETE CLIENTES  */
    public function buscarClientes(Request $request)
    {
        $termo = $request->get('termo');

        if (!$termo || strlen($termo) < 2) {
            return response()->json([]);
        }

        try {
            $clientes = User::where(function ($query) use ($termo) {
                    $query->where('name', 'LIKE', "%{$termo}%")
                          ->orWhere('email', 'LIKE', "%{$termo}%");
                })
                ->with('latestContaCorrente') // Carrega o relacionamento da última movimentação
                ->limit(8)
                ->get(['id', 'name', 'email']); // Seleciona as colunas básicas do usuário

            // Mapeia os resultados para incluir o saldo formatado
            $clientes = $clientes->map(function ($cliente) {
                // Acessa o saldo_atual do relacionamento carregado, ou 0 se não houver movimentações
                $saldo = $cliente->latestContaCorrente->saldo_atual ?? 0;

                // Adiciona o saldo formatado e o saldo bruto ao objeto do cliente
                $cliente->saldo_formatado = 'R$ ' . number_format($saldo, 2, ',', '.');
                $cliente->saldo_bruto = $saldo; // Opcional: para ter o valor numérico puro

                // Remove o objeto do relacionamento se você não quiser ele na saída JSON final
                unset($cliente->latestContaCorrente);
                return $cliente;
            });

            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Erro buscar clientes: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /* === ITENS NA SACOLINHA  =================================== */
	public function itensSacolinha(Request $request)
	{
		$userId = $request->get('user_id');

		if (!$userId) {
			return response()->json([
				'success' => false,
				'message' => 'ID obrigatório'
			]);
		}

		try {
			/* ---- Itens na sacolinha (sem pedido) ---- */
			$itens = DB::table('items')
				->join('sacolinhas', 'items.id', '=', 'sacolinhas.item_id')
				->where('sacolinhas.user_id', $userId)
				->where('items.status', '!=', 'enviado')
				->select([
					'sacolinhas.id as sacola_id',
					'items.id as item_id',
					'items.codigo',
					'items.nome_do_produto',
					'items.preco',
					'items.marca',
					'items.modelo',
					'items.estado',
					'items.cor',
					'items.tamanho',
					'sacolinhas.price',
					'sacolinhas.quantity',
					'sacolinhas.add_at',
					'items.status',
					'sacolinhas.obs'
				])
				->orderBy('sacolinhas.add_at', 'desc')
				->get();

			$totalItens = $itens->sum('quantity');
			$valorTotal = $itens->sum(fn($item) => $item->price * $item->quantity);

			return response()->json([
				'success' => true,
				'itens_sacolinha' => $itens,
				'resumo' => [
					'total_itens' => $totalItens,
					'valor_total' => number_format($valorTotal, 2, ',', '.'),
					'quantidade_produtos' => $itens->count()
				]
			]);
		} catch (\Exception $e) {
			Log::error('Erro itens sacolinha: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro ao carregar itens'
			]);
		}
	}
	
    /* === ITENS EM PEDIDO =================================== */	
	public function itensPedido(Request $request)
	{
		$userId = $request->get('user_id');

		if (!$userId) {
			return response()->json([
				'success' => false,
				'message' => 'ID obrigatório'
			]);
		}

		try {
			/* ---- Buscar pedido pendente do cliente ---- */
			$pedidoPendente = DB::table('pedidos')
				->where('user_id', $userId)
				->where('status_pedido', '!=', 'concluido')
				->where('status_pedido', '!=', 'cancelado') 
				->first();

			$itensPedido = [];
			$valorTotalPedido = 0;
			$statusPedido = null;
			$pedidoId = null;
			$pedidoNumero = null;
			$valorFrete = 0;
			$valorFreteFormatado = '0,00';

			if ($pedidoPendente) {
				$pedidoId = $pedidoPendente->id;
				$pedidoNumero = $pedidoPendente->numero_pedido;
				$statusPedido = $pedidoPendente->status_pedido;
				$valorFrete = $pedidoPendente->valor_frete ?? 0;
				$valorFreteFormatado = number_format($valorFrete, 2, ',', '.');

				$itensPedido = DB::table('items_pedido')
					->join('items', 'items.id', '=', 'items_pedido.item_id')
					->where('items_pedido.pedido_id', $pedidoPendente->id)
					->select([
						'items_pedido.id as item_pedido_id',
						'items.id as item_id',
						'items.codigo',
						'items.nome_do_produto',
						'items.marca',
						'items.estado',
						'items.cor',
						'items.tamanho',
						'items_pedido.quantidade',
						'items_pedido.preco_unitario as preco',
						'items_pedido.valor_total',
						//'items_pedido.valor_frete',
						'items_pedido.status_item',
						'items_pedido.created_at'
					])
					->orderBy('items_pedido.created_at', 'desc')
					->get();

				$valorTotalPedido = $itensPedido->sum('valor_total');
			}

			return response()->json([
				'success' => true,
				'itens_pedido' => $itensPedido,
				'pedido_numero' => $pedidoNumero,
				'pedido_id' => $pedidoId,
				'pedido_status' => $statusPedido,
				'tem_pedido' => (bool)$pedidoPendente,
				'pedido_valor_frete' => $valorFrete,
				'pedido_valor_frete_formatado' => $valorFreteFormatado,
				'resumo' => [
					'valor_total_pedido' => number_format($valorTotalPedido, 2, ',', '.'),
					'quantidade_itens_pedido' => count($itensPedido)
				]
			]);
		} catch (\Exception $e) {
			Log::error('Erro itens pedido: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro ao carregar itens do pedido'
			]);
		}
	}	
	

    /* === CRIAR PEDIDO  */
    public function criarPedido(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'ID do usuário obrigatório'
            ]);
        }

        try {
            DB::beginTransaction();

            $ultimoPedido  = DB::table('pedidos')->latest('id')->first();
            $numero        = $ultimoPedido ? $ultimoPedido->id + 1 : 1;
            $numeroPedido  = 'PED-' . str_pad($numero, 6, '0', STR_PAD_LEFT);

            $pedidoId = DB::table('pedidos')->insertGetId([
                'numero_pedido'   => $numeroPedido,
                'user_id'         => $userId,
                'status_pedido'   => 'pendente',
                'data_pedido'     => now(),
                'valor_total'     => 0, // Será recalculado com trigger no BD
                'valor_frete'     => 0,
                'valor_desconto'  => 0,
                'status_pagamento'=> 'pendente',
                'origem_pedido'   => 'site',
                'created_at'      => now(),
                'updated_at'      => now()
            ]);

            DB::commit();

            return response()->json([
                'success'       => true,
                'pedido_id'     => $pedidoId,
                'pedido_numero' => $numeroPedido,
                'message'       => 'Pedido criado com sucesso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido'
            ]);
        }
    }

	/* === MOVER ITEM DA SACOLINHA PARA O PEDIDO ========================= */
	public function moverParaPedido(Request $request)
	{
		$sacolaId = $request->get('sacola_id');
		$pedidoId = $request->get('pedido_id');
		$userId = $request->get('user_id');

		Log::info('📦 Mover para pedido iniciado', [
			'sacola_id' => $sacolaId,
			'pedido_id' => $pedidoId,
			'user_id' => $userId
		]);

		if (!$sacolaId || !$pedidoId || !$userId) {
			Log::warning('⚠️ Dados obrigatórios faltando');
			return response()->json([
				'success' => false,
				'message' => 'Dados obrigatórios faltando'
			]);
		}

		try {
			DB::beginTransaction();

			/* ---- Buscar item na sacolinha ---- */
			$sacolinha = DB::table('sacolinhas')
				->where('id', $sacolaId)
				->where('user_id', $userId)
				->first();

			if (!$sacolinha) {
				Log::warning('❌ Item da sacolinha não encontrado');
				return response()->json([
					'success' => false,
					'message' => 'Item da sacolinha não encontrado'
				]);
			}

			/* ---- Inserir no items_pedido ---- */
			DB::table('items_pedido')->insert([
				'pedido_id' => $pedidoId,
				'item_id' => $sacolinha->item_id,
				'quantidade' => 1,  // Sempre 1 pois item é único
				'preco_unitario' => $sacolinha->price,
				'status_item' => 'ativo',
				'created_at' => now(),
				'updated_at' => now()
			]);
			Log::info('✅ Item inserido em items_pedido');

			/* ---- Remover da sacolinha ---- */
			DB::table('sacolinhas')
				->where('id', $sacolaId)
				->delete();
			Log::info('✅ Item removido da sacolinha');

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Item movido para o pedido com sucesso'
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			Log::error('❌ Erro ao mover para pedido: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Erro ao mover item: ' . $e->getMessage()
			], 500);
		}
	}

    /* === DEVOLVER ITEM PARA A SACOLINHA ================================ */
    public function devolverParaSacolinha(Request $request)
    {
        $itemPedidoId = $request->get('item_pedido_id');
        $userId       = $request->get('user_id'); // user_id do cliente logado/selecionado

        Log::info('↩️ Devolução para sacolinha iniciada', [
            'item_pedido_id' => $itemPedidoId,
            'user_id'        => $userId
        ]);

        if (!$itemPedidoId || !$userId) {
            Log::warning('⚠️ Dados obrigatórios faltando para devolver para sacolinha');
            return response()->json([
                'success' => false,
                'message' => 'Dados obrigatórios faltando'
            ]);
        }

        try {
            DB::beginTransaction();

            /* ---- Buscar item no pedido ---- */
            $itemPedido = DB::table('items_pedido')
                ->where('id', $itemPedidoId)
                ->first();

            if (!$itemPedido) {
                Log::warning('❌ Item não encontrado em items_pedido');
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado no pedido'
                ]);
            }
            Log::info('🔎 Item do pedido encontrado', ['itemPedido' => $itemPedido]);

            /* ---- Buscar pedido para pegar o user_id (conforme mapeamento) ---- */
            $pedido = DB::table('pedidos')
                ->where('id', $itemPedido->pedido_id)
                ->first();

            if (!$pedido) {
                Log::warning('❌ Pedido não encontrado para o item_pedido');
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido associado não encontrado'
                ]);
            }
            Log::info('🔎 Pedido associado encontrado', ['pedido_id' => $pedido->id, 'pedido_user_id' => $pedido->user_id]);

            /* ---- Inserir na sacolinha ---- */
            // Mapeamento: user_id=pedido.user_id, item_id=item_pedido.item_id, live_id=1, quantity=1, price=item_pedido.preco_unitario
            DB::table('sacolinhas')->insert([
                'user_id'    => $pedido->user_id, // Conforme mapeamento: user_id do pedido
                'item_id'    => $itemPedido->item_id,
                'price'      => $itemPedido->preco_unitario,
                'quantity'   => 1, // Conforme mapeamento
                'live_id'    => 1, // Conforme mapeamento
                'status'     => 'ativo',
                'obs'        => null,
                'add_at'     => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            Log::info('✅ Item inserido na sacolinha', ['item_id' => $itemPedido->item_id, 'user_id' => $pedido->user_id]);

            /* ---- Remover de items_pedido ---- */
            DB::table('items_pedido')->where('id', $itemPedidoId)->delete();
            Log::info('✅ Item removido de items_pedido', ['item_pedido_id' => $itemPedidoId]);

            // O valor_total do pedido é uma coluna GENERATED e será recalculado automaticamente pelo DB
            Log::info('ℹ️ valor_total do pedido será recalculado automaticamente pelo DB.');

            DB::commit();
            Log::info('✅ Transação de devolução para sacolinha concluída com sucesso');

            return response()->json([
                'success' => true,
                'message' => 'Item devolvido para a sacolinha com sucesso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erro ao devolver item para sacolinha: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao devolver item: ' . $e->getMessage()
            ]);
        }
    }

    /* === DADOS DO CLIENTE (EXEMPLO) ==================================== */
    public function dadosCliente($clienteId)
    {
        try {
            $sacolinha = DB::table('sacolinhas')
                ->join('items', 'sacolinhas.item_id', '=', 'items.id')
                ->where('sacolinhas.user_id', $clienteId)
                ->select(
                    'sacolinhas.id',
                    'sacolinhas.price',
                    'sacolinhas.created_at',
                    'items.nome as produto_nome'
                )
                ->get();

            return response()->json(['sacolinha' => $sacolinha]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }
	
	
	/**
	 * Imprimir Sacolinha em PDF - VERSÃO CORRIGIDA
	 */
	public function imprimirSacolinha(Request $request)
	{
		try {
			$clienteId = $request->input('cliente_id');
			
			if (!$clienteId) {
				return response()->json(['error' => 'Cliente não selecionado'], 400);
			}

			$cliente = User::find($clienteId);
			
			if (!$cliente) {
				return response()->json(['error' => 'Cliente não encontrado'], 404);
			}

			// ✅ APENAS sacolinhas - sem items_pedido!
			$itensSacolinha = DB::table('sacolinhas')
				->join('items', 'sacolinhas.item_id', '=', 'items.id')
				->where('sacolinhas.user_id', $clienteId)
				->select(
					'items.codigo',
					'items.nome_do_produto',
					'sacolinhas.price',
					'items.marca',
					'items.estado',
					'items.cor',
					'items.tamanho',
					'sacolinhas.add_at'
				)
				->get();

			if ($itensSacolinha->count() === 0) {
				return response()->json(['error' => 'Nenhum item encontrado na sacolinha'], 404);
			}

			$valorTotal = $itensSacolinha->sum('price');
			$totalItens = $itensSacolinha->count();
			
			$logoPath = public_path('images/LogoColorida sem fundo.png');
			$logoDataUri = null;
			Log::info('Logo PDF', ['logoPath' => $logoPath, 'exists' => file_exists($logoPath)]);

			
			if (file_exists($logoPath)) {
				$logoMime = mime_content_type($logoPath) ?: 'image/png';
				$logoBase64 = base64_encode(file_get_contents($logoPath));
				$logoDataUri = "data:{$logoMime};base64,{$logoBase64}";
			}

			$html = '
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<title>Sacolinha - ' . htmlspecialchars($cliente->name) . '</title>
				<style>
				  body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
				  table { width: 100%; border-collapse: collapse; margin: 20px 0; }
				  th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
				  th { background: #f0f0f0; font-weight: bold; }
				  .header { margin-bottom: 20px; }
				  .header-grid { width: 100%; border-collapse: collapse; }
     			  .header-grid, .header-grid td, .header-grid th { border: none !important; }
				  .header-grid td { padding: 0 !important; }
				  .header-grid { width: 100%; }
				  .logo-cell { width: 120px; vertical-align: top; }
				  .logo { height: 60px; }
				  .title-cell { text-align: center; vertical-align: top; }
				  .title-cell h1 { margin: 0 0 6px 0; }
				  .title-cell p { margin: 3px 0; }
				  .total { background: #e8f4fd; font-weight: bold; }
				  .preco { text-align: right; }
				</style>
			</head>
			<body>
				<div class="header">
				  <table class="header-grid">
					<tr>
					  <td class="logo-cell">
						' . ($logoDataUri ? '<img class="logo" src="' . $logoDataUri . '" />' : '') . '
					  </td>
					  <td class="title-cell">
						<h1>Sacolinha Mania</h1>
						<p><strong>Cliente:</strong> ' . htmlspecialchars($cliente->name) . '</p>
						<p><strong>Data:</strong> ' . date('d/m/Y H:i:s') . '</p>
						<p><strong>Total de Itens:</strong> ' . $totalItens . '</p>
						<p><strong>Valor Total:</strong> R$ ' . number_format($valorTotal, 2, ',', '.') . '</p>
					  </td>
					  <td style="width:120px;"></td>
					</tr>
				  </table>
				</div>
				
				<table>
					<thead>
						<tr>
							<th>Código</th>
							<th>Produto</th>
							<th>Detalhes</th>
							<th class="preco">Preço</th>
							<th>Dia da Live</th>
						</tr>
					</thead>
					<tbody>';

			foreach ($itensSacolinha as $item) {
				$detalhes = [];
				if ($item->marca) $detalhes[] = $item->marca;
				if ($item->estado) $detalhes[] = $item->estado;
				if ($item->cor) $detalhes[] = $item->cor;
				if ($item->tamanho) $detalhes[] = 'Tam: ' . $item->tamanho;
				
				$html .= '<tr>
					<td>' . htmlspecialchars($item->codigo ?? 'N/A') . '</td>
					<td><strong>' . htmlspecialchars($item->nome_do_produto) . '</strong></td>
					<td>' . htmlspecialchars(implode(' • ', $detalhes)) . '</td>
					<td class="preco">R$ ' . number_format($item->price, 2, ',', '.') . '</td>
					<td>' . \Carbon\Carbon::parse($item->add_at)->format('d/m/Y') . '</td>
				</tr>';
			}

			$html .= '
					</tbody>
					<tfoot>
						<tr class="total">
							<td colspan="3"><strong>TOTAL GERAL:</strong></td>
							<td class="preco"><strong>R$ ' . number_format($valorTotal, 2, ',', '.') . '</strong></td>
							<td></td>
						</tr>
					</tfoot>
				</table>
			</body>
			</html>';

			$pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
			
			$clienteNameSanitized = str_replace('/[\/\\]/', '_', $cliente->name);
			$filename = 'sacolinha_' . str_replace(' ', '_', $clienteNameSanitized) . '_' . date('Y-m-d_H-i-s') . '.pdf';
			
			return $pdf->download($filename);

		} catch (\Exception $e) {
			Log::error('Erro PDF Sacolinha:', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			]);
			
			return response()->json([
				'error' => 'Erro: ' . $e->getMessage()
			], 500);
		}
	}


	/**
	 * Imprimir Pedidos - VERSÃO CORRIGIDA PARA COLLATION
	 */
	public function imprimirPedido(Request $request)
	{
			\Log::info('imprimirPedido method reached!', [
            'url' => $request->fullUrl(),
            'cliente_id' => $request->input('cliente_id')
			]);
			
		try {
			$pedidoId = $request->input('pedido_id');
			if (!$pedidoId) {
			  return response()->json(['error' => 'Pedido não selecionado'], 400);
			}		
			
			$clienteId = $request->input('cliente_id');
			if (!$clienteId) {
				return response()->json(['error' => 'Cliente não selecionado'], 400);
			}

			// Buscar cliente
			$cliente = \App\Models\User::find($clienteId);
			
			if (!$cliente) {
				\Log::warning('imprimirPedido: Cliente not found for ID: ' . $clienteId . '. Returning 404.');
				return response()->json(['error' => 'Cliente não encontrado'], 404);
			}
			 \Log::info('imprimirPedido: Cliente found: ' . $cliente->name . ' (ID: ' . $cliente->id . ')');

			// Query CORRIGIDA
			$itensPedido = \DB::table('pedidos') // Começamos pela tabela de pedidos
				->join('items_pedido', 'pedidos.id', '=', 'items_pedido.pedido_id') // Juntamos com a tabela intermediária
				->join('items', 'items_pedido.item_id', '=', 'items.id') // Juntamos com a tabela de detalhes do item
				->where('pedidos.user_id', $clienteId) // Filtramos pelo ID do cliente
				->where('pedidos.id', $pedidoId) 
				->select(
					'items.codigo',
					'items.nome_do_produto',
					'items.marca',
					'items.estado',
					'items.cor',
					'items.tamanho',
					'items_pedido.preco_unitario as preco', // Use o preço unitário do item_pedido para este pedido específico
					'pedidos.numero_pedido', // Obtenha o número do pedido da tabela pedidos
					'items_pedido.created_at' // Use a data de criação do item_pedido (quando foi adicionado ao pedido)
				)
				->orderBy('items_pedido.created_at', 'desc') // Ordenar pela data de adição ao pedido
				->get();

			if ($itensPedido->count() === 0) {
                \Log::warning('imprimirPedido: No items found for order for client ID: ' . $clienteId . '. Returning 404.');
				return response()->json(['error' => 'Nenhum item encontrado nos pedidos'], 404);
			}
            \Log::info('imprimirPedido: ' . $itensPedido->count() . ' items found for order.');
			
			$valorTotal = $itensPedido->sum('preco');
			$totalItens = $itensPedido->count();

			// HTML para PDF
			$html = '
			<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<title>Pedidos - ' . $cliente->name . '</title>
				<style>
					body { font-family: Arial, sans-serif; font-size: 12px; }
					table { width: 100%; border-collapse: collapse; margin: 20px 0; }
					th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
					th { background: #f0f0f0; }
					.header { text-align: center; margin-bottom: 20px; }
					.total { background: #e8f4fd; font-weight: bold; }
				</style>
			</head>
			<body>
				<div class="header">
					<h1>Pedido</h1>
					<p><strong>Cliente:</strong> ' . $cliente->name . '</p>
					<p><strong>Email:</strong> ' . $cliente->email . '</p>
					<p><strong>Data:</strong> ' . date('d/m/Y H:i:s') . '</p>
					<p><strong>Total de Itens:</strong> ' . $totalItens . '</p>
					<p><strong>Valor Total:</strong> R$ ' . number_format($valorTotal, 2, ',', '.') . '</p>
				</div>
				
				<table>
					<thead>
						<tr>
							<th>Pedido</th>
							<th>Código</th>
							<th>Produto</th>
							<th>Detalhes</th>
							<th>Preço</th>
							<th>Data</th>
						</tr>
					</thead>
					<tbody>';

			foreach ($itensPedido as $item) {
				$detalhes = [];
				if($item->marca) $detalhes[] = $item->marca;
				if($item->estado) $detalhes[] = $item->estado;
				if($item->cor) $detalhes[] = $item->cor;
				if($item->tamanho) $detalhes[] = 'Tam: ' . $item->tamanho;
				
				$html .= '<tr>
					<td><strong>' . ($item->numero_pedido ?? 'N/A') . '</strong></td>
					<td>' . ($item->codigo ?? 'N/A') . '</td>
					<td><strong>' . $item->nome_do_produto . '</strong></td>
					<td>' . implode(' • ', $detalhes) . '</td>
					<td style="text-align: right;">R$ ' . number_format($item->preco, 2, ',', '.') . '</td>
					<td>' . \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') . '</td>
				</tr>';
			}

			$html .= '
					</tbody>
					<tfoot>
						<tr class="total">
							<td colspan="4"><strong>TOTAL GERAL:</strong></td>
							<td style="text-align: right;"><strong>R$ ' . number_format($valorTotal, 2, ',', '.') . '</strong></td>
							<td></td>
						</tr>
					</tfoot>
				</table>
			</body>
			</html>';
            \Log::debug('imprimirPedido: Generated HTML (first 1000 chars): ' . substr($html, 0, 1000));

			// Gerar PDF
            \Log::info('imprimirPedido: Attempting to generate PDF with DomPDF...');
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            \Log::info('imprimirPedido: PDF object successfully created.');			

			$clienteNameSanitized = str_replace('/[\/\\]/', '_', $cliente->name); 

			$filename = 'pedidos_' . str_replace(' ', '_', $clienteNameSanitized) . '_' . date('Y-m-d_H-i-s') . '.pdf';

			 \Log::info('imprimirPedido: Filename for PDF: ' . $filename);

            \Log::info('imprimirPedido: Returning PDF for download...');
			return $pdf->download($filename);

		} catch (\Exception $e) {
			\Log::error('Erro PDF Pedidos:', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			]);
			
			return response()->json([
				'error' => 'Erro: ' . $e->getMessage()
			], 500);
		}
	}
	
		
	/* === ATUALIZAR STATUS DO PEDIDO ================================ */
    public function atualizarStatusPedido(Request $request)
    {
        $pedidoId = $request->get('pedido_id');
        $novoStatus = $request->get('status');

        Log::info('🔄 Atualizar status do pedido iniciado', [
            'pedido_id' => $pedidoId,
            'novo_status' => $novoStatus
        ]);

        if (!$pedidoId || !$novoStatus) {
            return response()->json([
                'success' => false,
                'message' => 'Dados obrigatórios faltando'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // ✅ Validar status do pedido
            $statusValidos = ['pendente', 'processando', 'pago', 'enviado', 'concluido', 'cancelado'];
            if (!in_array($novoStatus, $statusValidos)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Status de pedido inválido'
                ], 400);
            }

            // ✅ Buscar pedido
            $pedido = DB::table('pedidos')
                ->where('id', $pedidoId)
                ->first();

            if (!$pedido) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado'
                ], 404);
            }

            // ✅ Atualizar status do pedido
            DB::table('pedidos')
                ->where('id', $pedidoId)
                ->update([
                    'status_pedido' => $novoStatus,
                    'updated_at' => now()
                ]);

            // 🚀 NOVA LÓGICA: Atualizar status dos itens associados ao pedido através da tabela pivot 'items_pedido'
            // Primeiro, obtenha todos os IDs dos itens relacionados a este pedido na tabela 'items_pedido'
            $itemIdsNoPedido = DB::table('items_pedido')
                                 ->where('pedido_id', $pedidoId)
                                 ->pluck('item_id') // Pega apenas a coluna 'item_id'
                                 ->toArray();

            if (!empty($itemIdsNoPedido)) {
                if (in_array($novoStatus, ['pago', 'enviado', 'concluido'])) {
                    DB::table('items')
                        ->whereIn('id', $itemIdsNoPedido) // Atualiza os itens cujos IDs estão na lista
                        ->update([
                            'status' => 'vendido',
                            'updated_at' => now()
                        ]);

                    Log::info('✅ Status dos itens associados ao pedido atualizado para "vendido" via items_pedido', [
                        'pedido_id' => $pedidoId,
                        'item_ids_afetados' => $itemIdsNoPedido,
                        'novo_status_item' => 'vendido'
                    ]);
                }
                // Lógica opcional: Se o pedido for cancelado, os itens podem voltar a ser 'disponivel'
                else if ($novoStatus === 'cancelado') {
                    DB::table('items')
                        ->whereIn('id', $itemIdsNoPedido)
                        ->update([
                            'status' => 'disponivel', // Ou outro status apropriado para itens cancelados
                            'updated_at' => now()
                        ]);

                    Log::info('✅ Status dos itens associados ao pedido atualizado para "disponivel" devido ao cancelamento do pedido via items_pedido', [
                        'pedido_id' => $pedidoId,
                        'item_ids_afetados' => $itemIdsNoPedido,
                        'novo_status_item' => 'disponivel'
                    ]);
                }
            } else {
                Log::warning('⚠️ Nenhum item encontrado na tabela items_pedido para o pedido_id: ' . $pedidoId);
            }

            DB::commit();

            Log::info('✅ Status do pedido e itens associados atualizados', [
                'pedido_id' => $pedidoId,
                'status_anterior_pedido' => $pedido->status_pedido,
                'status_novo_pedido' => $novoStatus
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status do pedido e itens associados atualizados com sucesso',
                'novo_status_pedido' => $novoStatus
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erro ao atualizar status do pedido e itens: ' . $e->getMessage(), [
                'pedido_id' => $pedidoId,
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status do pedido e itens: ' . $e->getMessage()
            ], 500);
        }
    }

		
	public function imprimirSacolinhaLive(Request $request)
	{
		try {
			$clienteId = $request->input('cliente_id');
			$liveId = $request->input('live_id');

			if (!$clienteId || !$liveId) {
				return response()->json(['error' => 'Cliente e Live são obrigatórios'], 400);
			}

			$cliente = User::find($clienteId);
			if (!$cliente) {
				return response()->json(['error' => 'Cliente não encontrado'], 404);
			}

			$itensSacolinha = DB::table('sacolinhas')
				->join('items', 'sacolinhas.item_id', '=', 'items.id')
				->where('sacolinhas.user_id', $clienteId)
				->where('sacolinhas.live_id', $liveId)
				->select(
					'items.codigo',
					'items.nome_do_produto',
					'sacolinhas.price',
					'items.marca',
					'items.estado',
					'items.cor',
					'items.tamanho',
					'sacolinhas.add_at',
					'sacolinhas.obs'
				)
				->orderBy('sacolinhas.add_at')
				->get();

			if ($itensSacolinha->count() === 0) {
				return response()->json(['error' => 'Nenhum item encontrado nesta live para este cliente'], 404);
			}

			// ... (seu HTML igual, só muda título/data se quiser)

			$pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

			$filename = 'sacolinha_live_'.$liveId.'_cliente_'.$clienteId.'_'.date('Y-m-d_H-i-s').'.pdf';
			return $pdf->download($filename);

		} catch (\Exception $e) {
			Log::error('Erro PDF Sacolinha Live:', [
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine()
			]);

			return response()->json(['error' => 'Erro: '.$e->getMessage()], 500);
		}
	}	
}