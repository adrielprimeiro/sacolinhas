<?php

namespace App\Services\Ai;

use App\Models\KnowledgeBase;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\Sacolinhas;
use App\Models\Pedido;
use App\Models\Item;
use App\Models\ContaCorrente;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RagService
{
    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Calcula a similaridade de cosseno entre dois vetores.
     */
    public function calculateCosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $count = count($vecA);
        if ($count !== count($vecB) || $count === 0) {
            return 0.0;
        }

        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Busca na base de conhecimento os trechos mais parecidos semanticamente.
     */
    public function searchKnowledgeBase(string $query, int $topK = 4, float $minSimilarity = 0.25): array
    {
        $queryEmbedding = $this->gemini->generateEmbedding($query);
        $knowledgeItems = KnowledgeBase::where('is_active', true)->get();

        if ($knowledgeItems->isEmpty()) {
            return [];
        }

        $results = [];

        if ($queryEmbedding) {
            foreach ($knowledgeItems as $item) {
                if (!empty($item->embedding) && is_array($item->embedding)) {
                    $similarity = $this->calculateCosineSimilarity($queryEmbedding, $item->embedding);
                    if ($similarity >= $minSimilarity) {
                        $results[] = [
                            'item' => $item,
                            'similarity' => $similarity,
                        ];
                    }
                }
            }

            usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
            $results = array_slice($results, 0, $topK);
        } else {
            // Fallback para busca por palavra-chave se a geração de embedding falhar
            $words = array_filter(explode(' ', mb_strtolower($query)), fn($w) => mb_strlen($w) > 2);
            foreach ($knowledgeItems as $item) {
                $contentLower = mb_strtolower($item->title . ' ' . $item->content);
                $matches = 0;
                foreach ($words as $word) {
                    if (str_contains($contentLower, $word)) {
                        $matches++;
                    }
                }
                if ($matches > 0) {
                    $results[] = [
                        'item' => $item,
                        'similarity' => 0.3 + ($matches * 0.1),
                    ];
                }
            }
            usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
            $results = array_slice($results, 0, $topK);
        }

        return array_map(fn($res) => $res['item'], $results);
    }

    /**
     * Coleta informações do sistema em tempo real sobre o usuário logado e/ou produtos buscados.
     */
    public function getLiveSystemContext(?User $user, string $query): string
    {
        $contextLines = [];

        // 1. Dados do Cliente logado (Sacolinhas e Pedidos)
        if ($user) {
            $contextLines[] = "--- DADOS EM TEMPO REAL DO CLIENTE ---";
            $contextLines[] = "Nome do Cliente: {$user->name}";
            $contextLines[] = "Email: {$user->email}";

            // Sacolinhas do cliente
            try {
                $sacolinhas = Sacolinhas::where('user_id', $user->id)
                    ->with('item')
                    ->get();

                if ($sacolinhas->isNotEmpty()) {
                    $totalItens = $sacolinhas->count();
                    $contextLines[] = "O cliente possui {$totalItens} item(ns) na sacolinha aberta no momento:";
                    foreach ($sacolinhas as $sacola) {
                        $nomeItem = $sacola->item->nome_do_produto ?? $sacola->item->descricao ?? "Item #{$sacola->item_id}";
                        $valor = isset($sacola->item->preco) ? "R$ " . number_format($sacola->item->preco, 2, ',', '.') : '';
                        $contextLines[] = "- {$nomeItem} (Status: {$sacola->status}) {$valor}";
                    }
                } else {
                    $contextLines[] = "O cliente não tem nenhuma sacolinha aberta no momento.";
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar sacolinhas para contexto RAG: " . $e->getMessage());
            }

            // Pedidos do cliente
            try {
                $pedidos = Pedido::where('user_id', $user->id)
                    ->orderBy('id', 'desc')
                    ->limit(3)
                    ->get();

                if ($pedidos->isNotEmpty()) {
                    $contextLines[] = "Últimos pedidos do cliente:";
                    foreach ($pedidos as $ped) {
                        $dataPed = $ped->created_at ? $ped->created_at->format('d/m/Y') : 'N/A';
                        $valorPed = number_format($ped->total ?? 0, 2, ',', '.');
                        $rastreio = $ped->codigo_rastreio ? "Rastreio: {$ped->codigo_rastreio}" : "";
                        $contextLines[] = "- Pedido #{$ped->id} em {$dataPed} - Status: {$ped->status} - Total: R$ {$valorPed} {$rastreio}";
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar pedidos para contexto RAG: " . $e->getMessage());
            }

            // Conta corrente / Saldo
            try {
                $ultima = ContaCorrente::where('user_id', $user->id)
                    ->orderByDesc('data_movimentacao')
                    ->orderByDesc('id')
                    ->first();
                $saldo = $ultima?->saldo_atual ?? 0;
                $saldoFmt = number_format($saldo, 2, ',', '.');
                $contextLines[] = "Saldo em Conta Corrente / Créditos do Cliente: R$ {$saldoFmt}";
            } catch (\Exception $e) {
                // Tabela/relação opcional
            }
        }

        // 2. Busca de produtos no estoque se a pergunta parecer sobre produtos
        $keywords = array_filter(explode(' ', mb_strtolower($query)), fn($w) => mb_strlen($w) > 2);
        if (!empty($keywords)) {
            try {
                $itemsQuery = Item::query();
                foreach ($keywords as $kw) {
                    $itemsQuery->orWhere('nome_do_produto', 'LIKE', "%{$kw}%")
                               ->orWhere('codigo', 'LIKE', "%{$kw}%")
                               ->orWhere('descricao', 'LIKE', "%{$kw}%");
                }
                $produtosEncontrados = $itemsQuery->where('status', 'disponivel')->limit(5)->get();

                if ($produtosEncontrados->isNotEmpty()) {
                    $contextLines[] = "--- PRODUTOS DISPONÍVEIS NO ESTOQUE CORRESPONDENTES À BUSCA ---";
                    foreach ($produtosEncontrados as $prod) {
                        $preco = number_format($prod->preco ?? 0, 2, ',', '.');
                        $tamanho = $prod->tamanho ? "Tamanho: {$prod->tamanho}" : "";
                        $contextLines[] = "- Cod: {$prod->codigo} | {$prod->nome_do_produto} {$tamanho} | R$ {$preco}";
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao buscar estoque para contexto RAG: " . $e->getMessage());
            }
        }

        return implode("\n", $contextLines);
    }

    /**
     * Processa a pergunta do usuário e retorna a resposta da IA com base no RAG.
     */
    public function ask(string $userMessage, ?User $user = null, ?string $sessionId = null, bool $maniMode = false): array
    {
        $sessionId = $sessionId ?: (string) Str::uuid();

        // 1. Busca na base de conhecimento (Vector RAG)
        $knowledgeBaseMatches = $this->searchKnowledgeBase($userMessage);
        
        $knowledgeText = "";
        $sourcesUsed = [];
        if (!empty($knowledgeBaseMatches)) {
            $knowledgeText .= "--- REGRAS E CONHECIMENTO DA EMPRESA ---\n";
            foreach ($knowledgeBaseMatches as $kb) {
                $knowledgeText .= "📌 {$kb->title}:\n{$kb->content}\n\n";
                $sourcesUsed[] = $kb->title;
            }
        }

        // 2. Coleta dados ao vivo do sistema
        $liveContextText = $this->getLiveSystemContext($user, $userMessage);

        if ($maniMode) {
            $systemInstruction = "Seu nome é Mani. Você é uma capivara fofa que atua como Consultora de Moda e Estilo (Moda Circular) da loja Mania de Melissa.\n" .
                "IMPORTANTE 1: Você trabalha com ROUPAS (moda, looks, peças de vestuário). Você NÃO tem NENHUMA relação com manicure ou unhas.\n" .
                "IMPORTANTE 2: NÃO repita sua apresentação (\"Oi, sou a Mani...\") em cada mensagem. A conversa já está em andamento, responda direto à pergunta da cliente.\n" .
                "Você é carismática, usa emojis fofos (🐹✨👗) e entende tudo de moda sustentável.\n" .
                "Seu objetivo é ajudar as clientes a montar looks. Ao responder:\n" .
                "1. Olhe os itens que a cliente já tem na sacolinha (disponíveis nos DADOS EM TEMPO REAL) e sugira looks criativos com eles.\n" .
                "2. Se a cliente perguntar sobre saldos ou pedidos, responda baseando-se nos DADOS EM TEMPO REAL. Seja sempre muito simpática e motivadora. Você é a melhor amiga estilosa dela.\n\n" .
                "CONTEXTO DO SISTEMA E ESTOQUE:\n" .
                $knowledgeText . "\n" .
                $liveContextText;
        } else {
            $systemInstruction = "Você é o assistente virtual oficial do sistema Sacolinhas (Mania de Melissa).\n" .
                "Seu objetivo é ser atencioso, rápido e preciso. Responda sempre em Português do Brasil.\n\n" .
                "DIRETRIZES DE RESPOSTA:\n" .
                "1. Utilize as informações de regras da empresa e os dados do sistema fornecidos no contexto abaixo para responder.\n" .
                "2. Se a dúvida for sobre status de sacolinha, pedidos ou saldo, consulte diretamente os 'DADOS EM TEMPO REAL DO CLIENTE'.\n" .
                "3. Se as informações fornecidas não forem suficientes para responder à pergunta com certeza, seja honesto e diga que não encontrou os detalhes específicos e oriente o cliente a falar com o suporte humano.\n" .
                "4. Nunca invente status de pedidos ou valores que não estão no contexto.\n\n" .
                "CONTEXTO ATUALIZADO DO SISTEMA:\n" .
                $knowledgeText . "\n" .
                $liveContextText;
        }

        // 4. Carrega histórico recente da sessão (últimas 6 mensagens)
        $recentMessages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get()
            ->reverse()
            ->values();

        $historyFormatted = [];
        foreach ($recentMessages as $msg) {
            $historyFormatted[] = [
                'role' => $msg->role,
                'text' => $msg->message,
            ];
        }

        // 5. Salva mensagem do usuário
        ChatMessage::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        // 6. Chama a API do Gemini
        $assistantAnswer = $this->gemini->generateAnswer($userMessage, $systemInstruction, $historyFormatted);

        // 7. Salva a resposta da IA
        ChatMessage::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'role' => 'assistant',
            'message' => $assistantAnswer,
            'sources' => $sourcesUsed,
        ]);

        return [
            'answer' => $assistantAnswer,
            'sources' => $sourcesUsed,
            'session_id' => $sessionId,
        ];
    }
}
