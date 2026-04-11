<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveWhatsAppService
{
	public function sendForLive(int $liveId): array
	{
		Log::info('Iniciando envio WhatsApp para live via jobs', ['live_id' => $liveId]);

		$accountSid = env('TWILIO_ACCOUNT_SID');
		$authToken  = env('TWILIO_AUTH_TOKEN');
		$from       = env('TWILIO_WHATSAPP_FROM');
		$contentSid = env('TWILIO_WHATSAPP_ORDER_TEMPLATE_SID');

		if (!$accountSid || !$authToken || !$from || !$contentSid) {
			return [
				'success' => false,
				'sent' => 0,
				'failed' => 0,
				'message' => 'Config Twilio ausente no .env.',
			];
		}

		$rows = DB::table('sacolinhas')
			->join('users', 'users.id', '=', 'sacolinhas.user_id')
			->where('sacolinhas.live_id', $liveId)
			->whereNotNull('users.whatsapp')
			->where('users.whatsapp', '<>', '')
			->selectRaw('
				users.id as user_id,
				users.name as user_name,
				users.whatsapp as user_whatsapp,
				MIN(sacolinhas.id) as pedido_id,
				SUM(sacolinhas.quantity) as total_itens,
				SUM(sacolinhas.quantity * sacolinhas.price) as valor_total
			')
			->groupBy('users.id', 'users.name', 'users.whatsapp')
			->get();

		$queued = 0;
		$failed = 0;
		$sentUserIds = [];

		foreach ($rows as $index => $r) {
			$to = $this->normalizeToWhatsappE164BR($r->user_whatsapp);
			if (!$to) {
				Log::warning('Telefone inválido detectado', [
					'live_id' => $liveId,
					'user_id' => $r->user_id,
					'telefone' => $r->user_whatsapp
				]);
				$failed++;
				continue;
			}

			$vars = [
				'1' => $r->user_name,
			];

			// Dispatch job com delay para rate limit (1 segundo entre envios)
			SendWhatsAppMessage::dispatch($liveId, $r->user_id, $r->user_name, $to, $vars)
				->onQueue('whatsapp')
				->delay(now()->addSeconds($index)); // Envia 1 por segundo

			$queued++;
			$sentUserIds[] = (int) $r->user_id;

			Log::info('Job agendado para envio', [
				'live_id' => $liveId,
				'user_id' => $r->user_id,
				'phone' => $to,
				'delay_seconds' => $index
			]);
		}

		Log::info('Jobs agendados para live', [
			'live_id' => $liveId,
			'total_clientes' => count($rows),
			'agendados' => $queued,
			'falhas_validacao' => $failed
		]);

		return [
			'success' => true,
			'queued' => $queued,
			'failed' => $failed,
			'sent_user_ids' => array_values(array_unique($sentUserIds)),
			'message' => 'Mensagens agendadas para envio assíncrono. Status será atualizado conforme processamento.'
		];
	}
    private function normalizeToWhatsappE164BR(?string $raw): ?string
    {
        if (!$raw) return null;

        $digits = preg_replace('/\D+/', '', $raw);
        if (!$digits) return null;

        $e164 = str_starts_with($digits, '55') ? ('+' . $digits) : ('+55' . $digits);

        if (strlen(preg_replace('/\D+/', '', $e164)) < 12) return null;

        return 'whatsapp:' . $e164;
    }

    private function formatBRL(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}