<?php

namespace App\Http\Controllers;

use App\Services\LiveWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveWhatsAppController extends Controller
{
    public function send(Request $request, int $liveId, LiveWhatsAppService $service)
    {
        $token = config('services.twilio.webhook_token');
        if ($token && $request->header('X-Webhook-Token') !== $token) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $result = $service->sendForLive($liveId);

        // ✅ Opção 2: marcar enviado só para quem realmente foi enviado
        $sentUserIds = $result['sent_user_ids'] ?? [];
        if (!empty($sentUserIds)) {
            DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->whereIn('user_id', $sentUserIds)
                ->update([
                    'status' => 'enviado',
                    'updated_at' => now(),
                ]);
        }

        return response()->json(array_merge(['live_id' => $liveId], $result));
    }

    /**
     * Envia em lote as notificações de link seguro do Portal para todos os clientes da live.
     */
    public function sendPortalNotifications(Request $request, int $liveId)
    {
        Log::info('Iniciando disparo em lote de links do portal para live', ['live_id' => $liveId]);

        $rows = DB::table('sacolinhas')
            ->join('users', 'users.id', '=', 'sacolinhas.user_id')
            ->where('sacolinhas.live_id', $liveId)
            ->whereNotNull('users.whatsapp')
            ->where('users.whatsapp', '<>', '')
            ->selectRaw('
                users.id as user_id,
                users.name as user_name,
                users.whatsapp as user_whatsapp
            ')
            ->groupBy('users.id', 'users.name', 'users.whatsapp')
            ->get();

        $queued = 0;
        $failed = 0;

        foreach ($rows as $index => $r) {
            $digits = preg_replace('/\D+/', '', $r->user_whatsapp);
            if (!$digits || strlen($digits) < 8) {
                $failed++;
                continue;
            }
            $to = str_starts_with($digits, '55') ? ('+' . $digits) : ('+55' . $digits);
            $to = 'whatsapp:' . $to;

            // Dispara o job com delay (1 segundo entre cada envio para rate limit)
            \App\Jobs\SendWhatsAppMessage::dispatch($to, $liveId, $r->user_id, 'portal')
                ->onQueue('whatsapp')
                ->delay(now()->addSeconds($index));

            $queued++;
        }

        return response()->json([
            'success' => true,
            'queued' => $queued,
            'failed' => $failed,
            'message' => "Notificações do portal enfileiradas com sucesso: {$queued} agendadas, {$failed} falhas."
        ]);
    }
}