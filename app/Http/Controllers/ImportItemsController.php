<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportItemsController extends Controller
{
    public function import(Request $request)
    {
        // 1. Validação dos dados de entrada (da planilha)
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.nome_do_produto' => 'required|string|max:255', // Nome do produto da planilha
            'items.*.codigo' => 'required|string|max:255',
            'items.*.preco' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação dos dados.',
                'errors' => $validator->errors()
            ], 422);
        }

        $importedCount = 0;
        $failedItems = [];

        DB::beginTransaction();

        try {
			\Log::info('IMPORT-ITEMS HIT', [
				'path' => request()->path(),
				'first_item' => data_get($request->all(), 'items.0'),
			]);
            foreach ($request->input('items') as $itemData) {
                // Mapeamento dos dados da planilha para os campos do banco de dados
                $dataToUpdateOrCreate = [
                    'nome_do_produto' => $itemData['nome_do_produto'],     
					'codigo' => $itemData['codigo'],
                    'preco' => $itemData['preco'],
                    'estado' => $itemData['estado'] ?? 'seminovo', 
                    'status' => 'estoque', 
                    'marca' => $itemData['marca'] ?? null,
                    'modelo' => $itemData['modelo'] ?? null,
                    'cor' => $itemData['cor'] ?? null,
                    'tamanho' => $itemData['tamanho'] ?? null,					
					
                    // Você pode adicionar outros campos aqui se vierem da planilha
                    // 'descricao' => $itemData['descricao'] ?? null,
                    // 'custo' => $itemData['custo'] ?? null,
                    // 'pedido' => $itemData['pedido'] ?? null,
                    // 'codigo_da_categoria' => $itemData['codigo_da_categoria'] ?? null,

                    // 'image' => $itemData['image'] ?? null,
                ];

                // Condição para encontrar o item (pelo código)
                $itemIdentifier = ['codigo' => $itemData['codigo']];

                // Tenta encontrar o item pelo 'codigo'. Se encontrar, atualiza. Se não, insere.
                $item = DB::table('items')->where($itemIdentifier)->first();

                if ($item) {
                    DB::table('items')
                        ->where($itemIdentifier)
                        ->update($dataToUpdateOrCreate + ['updated_at' => now()]);
                } else {
                    DB::table('items')->insert($dataToUpdateOrCreate + ['created_at' => now(), 'updated_at' => now()]);
                }
				$check = DB::table('items')->where('codigo', $itemData['codigo'])->first();

				\Log::info('IMPORT-ITEMS AFTER SAVE', [
					'codigo' => $itemData['codigo'],
					'estado_saved' => $check->estado ?? null,
					'status_saved' => $check->status ?? null,
				]);
				
                $importedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$importedCount} itens importados/atualizados com sucesso.",
                'failed_items' => $failedItems
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao importar itens.',
                'error' => $e->getMessage(),
                'failed_items' => $failedItems
            ], 500);
        }
    }
}