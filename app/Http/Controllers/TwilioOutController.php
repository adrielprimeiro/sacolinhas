<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioOutController extends Controller
{
    public function send(Request $request)
    {
        // Proteção opcional (RECOMENDADO) para não virar endpoint aberto de disparo
        $token = env('TWILIO_WEBHOOK_TOKEN');
        if ($token && $request->header('X-Webhook-Token') !== $token) {
            return response()->json(['status' => 'error', 'error' => 'Unauthorized'], 401);
        }

        // Validação: precisa ter "body" OU "content_sid"
        $request->validate([
            'to' => 'required|string', // Ex: whatsapp:+5511999999999
            'body' => 'nullable|string',
            'content_sid' => 'nullable|string',
            'content_variables' => 'nullable|array',
        ]);

        if (!$request->filled('body') && !$request->filled('content_sid')) {
            return response()->json([
                'status' => 'error',
                'error' => 'Validation error',
                'message' => 'Informe "body" (texto livre) ou "content_sid" (template).',
            ], 422);
        }

        $accountSid = env('TWILIO_ACCOUNT_SID');
        $authToken  = env('TWILIO_AUTH_TOKEN');
        $from       = env('TWILIO_WHATSAPP_FROM'); // Ex: whatsapp:+14155238886

        if (!$accountSid || !$authToken || !$from) {
            return response()->json([
                'status' => 'error',
                'error' => 'Missing config',
                'message' => 'Variáveis TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN e TWILIO_WHATSAPP_FROM são obrigatórias.',
            ], 500);
        }

        $to = $request->input('to');

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

        // Monta payload base
        $payload = [
            'From' => $from,
            'To'   => $to,
        ];

        // Template (mensagem ativa / fora da janela)
        if ($request->filled('content_sid')) {
            $payload['ContentSid'] = $request->input('content_sid');

            $vars = $request->input('content_variables', []);
            $payload['ContentVariables'] = json_encode($vars, JSON_UNESCAPED_UNICODE);
        } else {
            // Texto livre (somente dentro da janela de 24h)
            $payload['Body'] = $request->input('body');
        }

        try {
            $resp = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post($url, $payload);

            if ($resp->successful()) {
                $data = $resp->json();
                Log::info('Twilio WhatsApp enviado', [
                    'sid' => $data['sid'] ?? null,
                    'to' => $to,
                    'mode' => $request->filled('content_sid') ? 'template' : 'freeform',
                ]);

                return response()->json([
                    'status' => 'success',
                    'sid' => $data['sid'] ?? null,
                ]);
            }

            Log::error('Twilio erro ao enviar', [
                'http_status' => $resp->status(),
                'body' => $resp->body(),
                'to' => $to,
                'mode' => $request->filled('content_sid') ? 'template' : 'freeform',
            ]);

            return response()->json([
                'status' => 'error',
                'http_status' => $resp->status(),
                'body' => $resp->body(),
            ], 500);

        } catch (\Throwable $e) {
            Log::error('Exception envio Twilio', ['message' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'error' => 'Internal error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}