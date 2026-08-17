<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClientWish;
use App\Services\DemandParserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WishController extends Controller
{
    protected DemandParserService $demandParser;

    public function __construct(DemandParserService $demandParser)
    {
        $this->demandParser = $demandParser;
    }

    /**
     * Cadastra um novo desejo a partir de um texto livre.
     * POST /api/wishes
     */
    public function store(Request $request)
    {
        $request->validate([
            'raw_prompt' => 'required|string|min:5|max:1000',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        $rawPrompt = trim($request->input('raw_prompt'));

        // Chama o serviço de IA para extrair as intenções
        $parsedData = $this->demandParser->parseDemand($rawPrompt);

        if (!$parsedData) {
            return response()->json([
                'success' => false, 
                'message' => 'Não conseguimos processar os detalhes do seu desejo no momento. Tente reformular o texto ou aguarde uns instantes.'
            ], 422);
        }

        try {
            // Varredura Inicial no Estoque Atual
            $query = \App\Models\Item::query();
            
            // Só roupas que estão ativas/disponíveis no estoque
            // Assumindo que status '1' ou 'disponivel' (ajuste conforme seu banco)
            // Se não houver filtro rígido, remova.
            
            if ($parsedData['size']) {
                $query->where('tamanho', $parsedData['size']);
            }
            
            if ($parsedData['max_price']) {
                $query->where('preco', '<=', $parsedData['max_price']);
            }
            
            if (!empty($parsedData['keywords'])) {
                $query->where(function($q) use ($parsedData) {
                    foreach($parsedData['keywords'] as $keyword) {
                        $q->orWhere('nome_do_produto', 'LIKE', '%' . $keyword . '%')
                          ->orWhere('descricao', 'LIKE', '%' . $keyword . '%');
                    }
                });
            }
            
            $matchedItem = clone $query;
            $matchedItem = $matchedItem->first();
            
            $status = $matchedItem ? 'matched' : 'active';
            $parsedAttributes = [
                'colors' => $parsedData['colors'],
                'style' => $parsedData['style'],
                'keywords' => $parsedData['keywords']
            ];

            if ($matchedItem) {
                $parsedAttributes['matched_item_id'] = $matchedItem->id;
            }

            // Persiste o desejo no banco
            $wish = ClientWish::create([
                'user_id' => $user->id,
                'raw_prompt' => $rawPrompt,
                'category' => $parsedData['category'],
                'size' => $parsedData['size'],
                'max_price' => $parsedData['max_price'],
                'parsed_attributes' => $parsedAttributes,
                'status' => $status,
            ]);

            $message = $status === 'matched' 
                ? 'Sorte grande! Já encontramos uma peça no acervo que bate com o seu desejo! Verifique em suas curadorias.' 
                : 'Seu desejo foi registrado! Avisaremos você assim que entrar uma peça que combine.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $wish
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao salvar o desejo do cliente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro interno ao salvar seu desejo.'
            ], 500);
        }
    }

    /**
     * Retorna a lista de desejos ativos da cliente logada.
     * GET /api/wishes
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Não autenticado.'], 401);
        }

        $wishes = ClientWish::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $wishes
        ]);
    }
}
