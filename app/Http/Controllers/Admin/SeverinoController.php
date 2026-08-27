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
        $message = $request->input('message');
        $history = $request->input('history', []);

        if (empty($message)) {
            return response()->json(['error' => 'Mensagem vazia.'], 400);
        }

        try {
            $answer = $severinoService->askSeverino($message, $history);
            
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
