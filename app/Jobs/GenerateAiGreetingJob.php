<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Ai\RagService;
use App\Services\Ai\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiGreetingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(RagService $ragService, GeminiService $geminiService): void
    {
        if (!$this->user) return;

        // 1. Gera o dossiê atualizado do cliente
        $liveContextText = $ragService->getLiveSystemContext($this->user, '');
        
        // 2. Cria um hash deste dossiê (estado da cliente neste exato momento)
        $currentHash = md5($liveContextText);

        // Se o hash for igual ao que já está salvo no banco, não precisa gerar outro greeting!
        if ($this->user->next_ai_greeting_hash === $currentHash && !empty($this->user->next_ai_greeting)) {
            return; 
        }

        // 3. Monta o Prompt para a IA preparar a recepção
        $nome = $this->user->apelido ?? $this->user->name ?? 'amiga';
        $firstName = explode(' ', trim($nome))[0];

        $systemInstruction = "Você é a Mani, capivara mascote e consultora de moda da loja Mania de Melissa.\n" .
            "IMPORTANTE 1: Você trabalha com ROUPAS. Nunca se apresente ('Oi, sou a Mani'). Vá DIRETO ao ponto, parecendo mensagem curta de WhatsApp (1 parágrafo curto).\n" .
            "IMPORTANTE 2: Crie UMA frase inicial animada para a cliente {$firstName} que acabou de abrir o chat. " .
            "Olhe os dados abaixo. Se ela tiver avaliação em andamento, saldos, itens na sacolinha, mencione ISSO sutilmente. Caso não tenha nada especial, puxe assunto sobre que tipo de roupa ela gosta.\n" .
            "ATENÇÃO: MANTENHA A MENSAGEM CURTA! No máximo 2 frases.";

        $historyFormatted = [];

        // 4. Chama a Gemini API
        try {
            $prompt = "A cliente acabou de abrir o chat. Use estes dados do momento para puxar um assunto personalizado com ela em UMA mensagem super curta:\n\n" . $liveContextText;
            $assistantAnswer = $geminiService->generateAnswer($prompt, $systemInstruction, $historyFormatted);
            
            // Limpa formatação Markdown
            $assistantAnswer = preg_replace('/```(\w+)?/', '', $assistantAnswer);
            $assistantAnswer = trim($assistantAnswer, "` \t\n\r\0\x0B");

            // 5. Salva no banco de dados do usuário
            $this->user->update([
                'next_ai_greeting' => $assistantAnswer,
                'next_ai_greeting_hash' => $currentHash
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro no Job GenerateAiGreetingJob: " . $e->getMessage());
        }
    }
}
