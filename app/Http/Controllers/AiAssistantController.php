<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Ai\RagService;
use App\Services\Ai\GeminiService;
use App\Models\KnowledgeBase;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    protected RagService $ragService;

    public function __construct(RagService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Endpoint API para enviar mensagem e receber a resposta da IA via RAG.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string',
        ]);

        $user = Auth::user();
        $message = trim($request->input('message'));
        $sessionId = $request->input('session_id');
        $maniMode = filter_var($request->input('mani_mode', false), FILTER_VALIDATE_BOOLEAN);

        $result = $this->ragService->ask($message, $user, $sessionId, $maniMode);

        return response()->json([
            'success' => true,
            'answer' => $result['answer'],
            'sources' => $result['sources'],
            'session_id' => $result['session_id'],
        ]);
    }

    /**
     * Endpoint API para buscar a saudação inicial dinâmica da IA.
     */
    public function getGreeting(Request $request)
    {
        $user = Auth::user();
        $maniMode = filter_var($request->input('mani_mode', false), FILTER_VALIDATE_BOOLEAN);

        $prompts = [
            "A cliente abriu o chat. Dê um 'Oi [nome/apelido]! Que bom te ver por aqui.' e pergunte o que ela busca. Seja muito direta. Um parágrafo curto apenas. SEM apresentações.",
            "A cliente abriu o chat. Dê um 'Oie!' e vá direto ao ponto, perguntando de forma casual se ela procura algo específico hoje. Apenas 1 parágrafo curto. SEM apresentações.",
            "A cliente abriu o chat. Diga 'Oi [nome/apelido], como posso te ajudar hoje?' e mais nada. Seja bem direta e curta. SEM apresentações.",
            "A cliente abriu o chat. Dê um oi simples e pergunte que tipo de look ela tem em mente. Um parágrafo curto, sem frases longas de boas vindas.",
            "A cliente abriu o chat. Diga algo como 'Oi [nome/apelido]! Pronta pra garimpar hoje?' e aguarde. Seja extremamente curta e direta."
        ];
        
        $promptBase = $prompts[array_rand($prompts)];
        $prompt = "ATENÇÃO: ESTA É A PRIMEIRA MENSAGEM DO CHAT. \n" . 
                  $promptBase . 
                  "\nIMPORTANTE: A resposta deve ter APENAS 1 parágrafo bem curto, direto ao ponto. NUNCA diga 'Eu sou a Mani' (ela já sabe). Sem frases longas de boas vindas. Se a cliente não tiver o 'Apelido' cadastrado, substitua a pergunta final por uma pergunta curta perguntando como ela gosta de ser chamada.";
        
        $result = $this->ragService->ask($prompt, $user, null, $maniMode);

        return response()->json([
            'success' => true,
            'answer' => $result['answer'],
            'session_id' => $result['session_id'],
        ]);
    }

    /**
     * Retorna o histórico de mensagens de uma sessão.
     */
    public function getHistory(Request $request)
    {
        $sessionId = $request->input('session_id');
        if (!$sessionId) {
            return response()->json(['success' => false, 'messages' => []]);
        }

        $messages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('id', 'asc')
            ->get(['role', 'message', 'sources', 'created_at']);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Exibe a página do assistente virtual no portal do cliente.
     */
    public function portalChat()
    {
        return view('portal.ajuda');
    }

    /**
     * Painel de Gestão da Base de Conhecimento (Admin).
     */
    public function adminKnowledgeBaseIndex()
    {
        $articles = KnowledgeBase::orderBy('id', 'desc')->get();
        return view('admin.knowledge_base.index', compact('articles'));
    }

    /**
     * Salva ou atualiza um artigo na Base de Conhecimento RAG.
     */
    public function adminKnowledgeBaseStore(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'content' => 'required|string',
        ]);

        $id = $request->input('id');

        $kb = KnowledgeBase::updateOrCreate(
            ['id' => $id],
            [
                'title' => $request->input('title'),
                'category' => $request->input('category'),
                'content' => $request->input('content'),
                'is_active' => $request->has('is_active') ? true : false,
            ]
        );

        // Tenta gerar embedding via Gemini
        $embedding = $gemini->generateEmbedding($kb->title . "\n" . $kb->content);
        if ($embedding) {
            $kb->embedding = $embedding;
            $kb->save();
        }

        return redirect()->back()->with('success', 'Artigo salvo e indexado com sucesso!');
    }

    /**
     * Exclui um artigo da base de conhecimento.
     */
    public function adminKnowledgeBaseDestroy($id)
    {
        KnowledgeBase::destroy($id);
        return redirect()->back()->with('success', 'Artigo removido da base de conhecimento.');
    }

    /**
     * Importa e treina IA com histórico de WhatsApp.
     */
    public function adminKnowledgeBaseImportWhatsApp(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'whatsapp_file' => 'required|file|mimes:txt|max:5120', // max 5MB
        ]);

        $file = $request->file('whatsapp_file');
        $content = file_get_contents($file->getRealPath());

        // Limita o tamanho do texto para não exceder limites de tokens (Gemini 3.6 aguenta muito, mas melhor garantir as últimas mensagens)
        $content = mb_substr($content, -30000); 

        $prompt = "Você é um especialista em atendimento ao cliente. Analise o seguinte histórico de chat do WhatsApp entre uma atendente da loja Mania de Melissa e clientes.\n\n" .
                  "Sua tarefa é extrair:\n" .
                  "1. O tom de voz da atendente (ex: como cumprimenta, expressões, emojis).\n" .
                  "2. Perguntas frequentes dos clientes e como foram respondidas.\n" .
                  "3. Regras de negócio ou processos explicados (ex: trocas, pagamentos, envios, numeração).\n\n" .
                  "Retorne EXATAMENTE UM JSON válido (sem markdown de bloco de código, comece direto com [{ e termine com }]) no formato de uma lista de objetos, onde cada objeto representa um 'Fato/Regra' com os campos:\n" .
                  "- title: Um título claro e curto para a regra.\n" .
                  "- category: Categoria (ex: atendimento, vendas, envio, geral).\n" .
                  "- content: O conteúdo detalhado, escrito como uma instrução que uma IA futura deve seguir.\n\n" .
                  "Aqui está o histórico de chat:\n" .
                  $content;

        try {
            $response = $gemini->generateAnswer($prompt, "Você é um extrator de regras especializado. Responda APENAS com um Array JSON puro e válido, sem backticks ou formatação Markdown. Ex: [{\"title\":\"...\",\"category\":\"...\",\"content\":\"...\"}]");
            
            // Limpa o markdown caso venha
            $jsonString = trim(str_replace(['```json', '```'], '', $response));
            $rules = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($rules)) {
                $count = 0;
                foreach ($rules as $rule) {
                    if (!isset($rule['title']) || !isset($rule['content'])) continue;
                    
                    $kb = KnowledgeBase::create([
                        'title' => $rule['title'] . ' (Via WhatsApp)',
                        'category' => $rule['category'] ?? 'geral',
                        'content' => $rule['content'],
                        'is_active' => true,
                    ]);

                    $embedding = $gemini->generateEmbedding($kb->title . "\n" . $kb->content);
                    if ($embedding) {
                        $kb->embedding = $embedding;
                        $kb->save();
                    }
                    $count++;
                }

                return redirect()->back()->with('success', "Análise mágica concluída! O Gemini extraiu, separou e indexou {$count} novas regras do seu atendimento.");
            } else {
                return redirect()->back()->with('error', 'O arquivo foi analisado, mas a IA retornou um formato inesperado. Tente exportar uma conversa diferente.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao processar arquivo whatsapp: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocorreu um erro ao processar o arquivo com a IA: ' . $e->getMessage());
        }
    }
}
