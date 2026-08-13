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

        $result = $this->ragService->ask($message, $user, $sessionId);

        return response()->json([
            'success' => true,
            'answer' => $result['answer'],
            'sources' => $result['sources'],
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
}
