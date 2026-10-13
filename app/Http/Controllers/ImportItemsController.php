<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportItemsController extends Controller
{
    public function import(Request $request, $brechoId = null)
    {
        // 1. Validação dos dados de entrada (da planilha)
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.nome_do_produto' => 'required|string|max:255', // Nome do produto da planilha
            'items.*.codigo' => 'nullable|string|max:255',
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
        $importedItems = [];
        $allocatedCodigos = [];

        DB::beginTransaction();

        try {
            // Padrão Minha Mania (brecho_id = 1)
            $defaultBrechoId = 1;
            if ($brechoId && is_numeric($brechoId) && (int)$brechoId > 0) {
                $defaultBrechoId = (int)$brechoId;
            } elseif ($request->filled('brecho_id') && is_numeric($request->input('brecho_id')) && (int)$request->input('brecho_id') > 0) {
                $defaultBrechoId = (int)$request->input('brecho_id');
            }

            \Log::info('IMPORT-ITEMS HIT', [
                'path' => request()->path(),
                'default_brecho_id' => $defaultBrechoId,
                'first_item' => data_get($request->all(), 'items.0'),
            ]);

            foreach ($request->input('items') as $itemData) {
                $itemBrechoId = !empty($itemData['brecho_id']) ? (int)$itemData['brecho_id'] : (int)$defaultBrechoId;

                $rawCodigo = isset($itemData['codigo']) ? strtoupper(trim((string)$itemData['codigo'])) : '';
                $codigoFinal = $rawCodigo;

                // Se código veio vazio ou "AUTO", gera automaticamente o próximo código seguro do sistema
                if ($codigoFinal === '' || $codigoFinal === 'AUTO') {
                    $codigoFinal = \App\Http\Controllers\ItemController::generateNextCodigo($itemBrechoId);
                    while (in_array($codigoFinal, $allocatedCodigos)) {
                        $dec = base_convert($codigoFinal, 36, 10) + 1;
                        $codigoFinal = strtoupper(str_pad(base_convert($dec, 10, 36), 4, '0', STR_PAD_LEFT));
                    }
                } else {
                    // Se o código foi fornecido manualmente, verifica se já existe no banco
                    $itemIdentifier = ['brecho_id' => $itemBrechoId, 'codigo' => $codigoFinal];
                    $existingItem = DB::table('items')->where($itemIdentifier)->first();

                    if ($existingItem) {
                        // Verifica se o item existente já foi vendido ou possui histórico protegido
                        $isProtectedStatus = !in_array($existingItem->status, ['estoque', 'disponivel', null, '']);
                        $hasSacolinha = DB::table('sacolinhas')->where('item_id', $existingItem->id)->exists();
                        $hasPedido = DB::table('items_pedido')->where('item_id', $existingItem->id)->exists();
                        $hasLive = DB::table('live_items')->where('item_id', $existingItem->id)->exists();

                        if ($isProtectedStatus || $hasSacolinha || $hasPedido || $hasLive) {
                            // PROTEÇÃO: O item existente é uma peça histórica/vendida! JAMAIS sobrescrever!
                            // Gera um código novo para a nova peça inserida
                            $novoCodigo = \App\Http\Controllers\ItemController::generateNextCodigo($itemBrechoId);
                            while (in_array($novoCodigo, $allocatedCodigos)) {
                                $dec = base_convert($novoCodigo, 36, 10) + 1;
                                $novoCodigo = strtoupper(str_pad(base_convert($dec, 10, 36), 4, '0', STR_PAD_LEFT));
                            }
                            \Log::warning("IMPORT-ITEMS: Código {$codigoFinal} já pertencia a um item vendido/em sacola (ID {$existingItem->id}). Alocado novo código {$novoCodigo} para evitar sobrescrita.");
                            $codigoFinal = $novoCodigo;
                        }
                    }
                }

                $allocatedCodigos[] = $codigoFinal;

                // Mapeamento dos dados da planilha para os campos do banco de dados
                $dataToUpdateOrCreate = [
                    'brecho_id' => $itemBrechoId,
                    'nome_do_produto' => $itemData['nome_do_produto'],     
                    'codigo' => $codigoFinal,
                    'preco' => $itemData['preco'],
                    'estado' => $itemData['estado'] ?? 'seminovo', 
                    'status' => 'estoque', 
                    'marca' => $itemData['marca'] ?? null,
                    'modelo' => $itemData['modelo'] ?? null,
                    'cor' => $itemData['cor'] ?? null,
                    'tamanho' => $itemData['tamanho'] ?? null,					
                ];

                // Condição para encontrar o item (pelo brecho_id e código final)
                $itemIdentifier = ['brecho_id' => $itemBrechoId, 'codigo' => $codigoFinal];
                $item = DB::table('items')->where($itemIdentifier)->first();

                if ($item) {
                    DB::table('items')
                        ->where($itemIdentifier)
                        ->update($dataToUpdateOrCreate + ['updated_at' => now()]);
                    $itemId = $item->id;
                } else {
                    $itemId = DB::table('items')->insertGetId($dataToUpdateOrCreate + ['created_at' => now(), 'updated_at' => now()]);
                }

                $importedItems[] = [
                    'id' => $itemId,
                    'codigo' => $codigoFinal,
                    'nome' => $itemData['nome_do_produto'],
                ];

                $importedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$importedCount} itens importados/atualizados com sucesso.",
                'items' => $importedItems,
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