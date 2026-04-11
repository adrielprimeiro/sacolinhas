<?php

namespace App\Services\Whatsapp;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoReplyService
{
    /**
     * Decide e monta a mensagem (msg1/msg2/msg3) para o fluxo de "Revisar e Confirmar".
     * Retorna:
     * - key: msg1|msg2|msg3
     * - text: texto final para envio
     * - meta: dados para log/diagnóstico
     */
    public function buildChecklistForReviewConfirm(int $userId, int $liveIdAtual): array
    {
        // Regra: sacolinha existe se existe registro na tabela sacolinhas para o user
        $hasSacolinha = DB::table('sacolinhas')
            ->where('user_id', $userId)
			->where('live_id', '!=', $liveIdAtual)
            ->exists();

        // Regra: limite vem de cliente_limites (se não existir, assume 0)
        $limiteDisponivel = (float) (DB::table('cliente_limites')
            ->where('user_id', $userId)
            ->where('ativo', 1)
            ->value('limite_disponivel') ?? 0);

        // Mantém essa info porque sua msg3 menciona "em analise" (e ajuda no debug)
        $itensEmAnaliseCount = DB::table('sacolinhas')
            ->where('user_id', $userId)
            ->where('live_id', $liveIdAtual)
            ->where('status', 'em analise')
            ->count();

        // Dados para msg2 (resumo)
        $dadosSacola = DB::table('sacolinhas')
            ->selectRaw('MIN(add_at) as abertura, COUNT(*) as num_items, SUM(price) as valor_total')
            ->where('user_id', $userId)
            ->where('live_id', $liveIdAtual)
            ->first();

		$key = $this->decideChecklistKey($hasSacolinhaEmOutraLive, $limiteDisponivel);

        $text = $this->buildChecklistText($key, $dadosSacola);

        return [
            'key' => $key,
            'text' => $text,
            'meta' => [
                'hasSacolinha' => $hasSacolinha,
                'limiteDisponivel' => $limiteDisponivel,
                'itensEmAnaliseCount' => $itensEmAnaliseCount,
                'liveIdAtual' => $liveIdAtual,
            ],
        ];
    }

	private function decideChecklistKey(bool $hasSacolinhaEmOutraLive, float $limiteDisponivel): string
	{
		if (!$hasSacolinhaEmOutraLive) return 'msg1';
		return $limiteDisponivel > 0 ? 'msg2' : 'msg3';
	}

    private function buildChecklistText(string $key, ?object $dadosSacola): string
    {
        if ($key === 'msg1') return $this->msg1Text();
        if ($key === 'msg2') return $this->msg2Text($dadosSacola);
        return $this->msg3Text();
    }

    private function msg3Text(): string
    {
        return "📋 Checklist!\n"
            . "🔍 Dá uma espiadinha no pedido anexado\n"
            . "✅ Verifique os items com status *'em analise'* porque excederam o limite de sua sacolinha.\n\n"
            . "Se quiser mantê-los, você tem estas opções:\n"
            . "1. Fazer o pagamento à vista\n"
            . "2. Solicitar aumento do seu limite\n"
            . "3. Retirar algum item em sua sacola\n"
            . "É só escolher sua opção para prosseguirmos.\n\n"
            . "Obs: Se não houver resposta os items serão cancelados em 24h ❌";
    }

    private function msg2Text(?object $dadosSacola): string
    {
        $abertura = $dadosSacola?->abertura
            ? Carbon::parse($dadosSacola->abertura)->format('d/m/Y')
            : '-';

        $numItems = $dadosSacola?->num_items ?? '-';

        $valorTotal = ($dadosSacola?->valor_total !== null)
            ? 'R$ ' . number_format((float) $dadosSacola->valor_total, 2, ',', '.')
            : '-';

        return "📋 Checklist!\n"
            . "🔍 Dá uma espiadinha no pedido anexado\n"
            . "🔧 Se precisar ajustar → grita aqui que a gente conserta!\n"
            . "✅ Se bater tudo certinho → os itens serão incluídos em sua sacolinha.\n\n"
            . "Sua sacolinha (com o pedido de hoje):\n"
            . "*Aberta em:* " . $abertura . "\n"
            . "*Número de itens:* " . $numItems . "\n"
            . "*Valor total:* " . $valorTotal . "\n\n"
            . "Quando quiser o envio do pedido é só falar!";
    }

    private function msg1Text(): string
    {
        return "📋 Checklist!\n"
            . "🔍 Dá uma espiadinha no pedido anexado\n"
            . "🔧 Se precisar ajustar → grita aqui que a gente conserta!\n"
            . "✅ Se bater tudo certinho, o 💲 *pagamento* pode ser por PIX *mania@maniademelissa.com*\n"
            . "   ou solicite o *link* para pagamento no CARTÃO\n"
            . "*O envio* pode ser:\n"
            . "1. 🛍️ *sacolinha* →  juntar  para envio em remessa única\n"
            . "2. Envio imediato\n\n"
            . "É só escolher sua opção para prosseguirmos.\n"
            . "Obs: Se não houver resposta o pedido será cancelado em 24h ❌";
    }
}