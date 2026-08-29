<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Ai\SeverinoService;

class SeverinoController extends Controller
{
    public function index()
    {
        return view('admin.severino.index');
    }

    public function ask(Request $request, SeverinoService $severinoService)
    {
        set_time_limit(120);
        $message = $request->input('message');
        $history = $request->input('history', []);

        if (empty($message)) {
            return response()->json(['error' => 'Mensagem vazia.'], 400);
        }

        try {
            $sessionId = session()->getId();
            $answer = $severinoService->askSeverino($message, $history, $sessionId);
            
            // Dispara job assíncrono após o envio da resposta (FastCGI Finish)
            app()->terminating(function () use ($sessionId, $message, $answer) {
                try {
                    $svc = new \App\Services\Ai\SeverinoService();
                    $current = \Illuminate\Support\Facades\Cache::get('severino_summary_' . $sessionId, '');
                    $newSummary = $svc->summarizeChat($current, $message, $answer);
                    \Illuminate\Support\Facades\Cache::put('severino_summary_' . $sessionId, $newSummary, now()->addHours(24));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Erro ao gerar resumo: ' . $e->getMessage());
                }
            });

            // Grava mensagem do usuário
            \App\Models\ChatMessage::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'role' => 'user',
                'message' => $message,
            ]);

            // Grava resposta do Severino
            \App\Models\ChatMessage::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'role' => 'assistant',
                'message' => $answer,
            ]);

            return response()->json(['answer' => $answer]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro Severino: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }
}
