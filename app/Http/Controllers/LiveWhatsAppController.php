<?php

namespace App\Http\Controllers;

use App\Services\LiveWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
}