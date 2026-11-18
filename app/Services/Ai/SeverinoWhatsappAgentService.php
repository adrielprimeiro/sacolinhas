<?php

namespace App\Services\Ai;

use App\Http\Controllers\PortalClienteController;
use App\Models\EvolutionContactMapping;
use App\Models\EvolutionMessage;
use App\Models\Item;
use App\Models\Lancamento;
use App\Models\Live;
use App\Models\Pedido;
use App\Models\Pessoa;
use App\Models\Sacolinhas;
use App\Models\User;
use App\Services\BancoInterPixService;
use App\Services\MelhorEnvioService;
use App\Services\ShippingCalculatorService;
use App\Services\Whatsapp\EvolutionApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeverinoWhatsappAgentService
{
    protected EvolutionApiService $evolution;
    protected string $geminiApiKey;
    protected string $geminiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(EvolutionApiService $evolution)
    {
        $this->evolution = $evolution;
        $this->geminiApiKey = (string) (config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', '')));
    }

    /**
     * Obtém a configuração global do fluxo Severino WhatsApp na Live
     */
    public static function getConfig(): array
    {
        $saved = Cache::get('severino_live_whatsapp_config', []);
        return [
            // 'test' = envia para o telefone de teste | 'live' = envia para o cliente real | 'off' = desativado no clique
            'mode' => $saved['mode'] ?? 'test',
            'test_phone' => $saved['test_phone'] ?? '5548984136870',
            'auto_reply' => isset($saved['auto_reply']) ? (bool) $saved['auto_reply'] : true,
        ];
    }

    /**
     * Salva a configuração global do fluxo Severino WhatsApp na Live
     */
    public static function saveConfig(array $data): array
    {
        $current = self::getConfig();
        $updated = [
            'mode' => in_array($data['mode'] ?? '', ['test', 'live', 'off']) ? $data['mode'] : $current['mode'],
            'test_phone' => !empty($data['test_phone']) ? preg_replace('/\D+/', '', (string) $data['test_phone']) : $current['test_phone'],
            'auto_reply' => isset($data['auto_reply']) ? (bool) $data['auto_reply'] : $current['auto_reply'],
        ];
        Cache::forever('severino_live_whatsapp_config', $updated);
        return $updated;
    }

    /**
     * Normaliza os últimos 8 dígitos de um número/JID para chave única de conversa/teste
     */
    public static function normalizePhoneKey(?string $phoneOrJid): ?string
    {
        if (!$phoneOrJid) {
            return null;
        }
        $raw = explode('@', $phoneOrJid)[0] ?? $phoneOrJid;
        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) >= 8) {
            return substr($digits, -8);
        }
        return $digits ?: null;
    }

    /**
     * Retorna a chave de controle de supervisão (Severino vs Humano) para uma conversa
     */
    public static function getConvSupervisionKey(?string $remoteJid, ?int $userId = null): string
    {
        $phoneKey = self::normalizePhoneKey($remoteJid);
        if ($phoneKey) {
            return 'severino_sup_phone_' . $phoneKey;
        }
        if ($userId) {
            return 'severino_sup_user_' . $userId;
        }
        return 'severino_sup_jid_' . md5((string) $remoteJid);
    }

    /**
     * Retorna 'severino' ou 'human' para a conversa
     */
    public static function getSupervisionMode(?string $remoteJid, ?int $userId = null): string
    {
        $key = self::getConvSupervisionKey($remoteJid, $userId);
        return Cache::get($key, 'severino');
    }

    /**
     * Define 'severino' ou 'human' para a conversa
     */
    public static function setSupervisionMode(?string $remoteJid, ?int $userId, string $mode, ?string $reason = null): void
    {
        $mode = ($mode === 'human') ? 'human' : 'severino';
        $key = self::getConvSupervisionKey($remoteJid, $userId);
        Cache::forever($key, $mode);

        if ($userId) {
            Cache::forever('severino_sup_user_' . $userId, $mode);
        }

        if ($reason) {
            Cache::put($key . '_reason', $reason, now()->addHours(48));
        } else {
            Cache::forget($key . '_reason');
        }
    }

    /**
     * Registra qual cliente/live está sendo simulado no telefone de teste
     */
    public static function setTestContext(string $testPhone, int $userId, int $liveId): void
    {
        $phoneKey = self::normalizePhoneKey($testPhone);
        $context = [
            'user_id' => $userId,
            'live_id' => $liveId,
            'test_phone' => $testPhone,
            'updated_at' => now()->toDateTimeString(),
        ];
        if ($phoneKey) {
            Cache::put('severino_test_ctx_' . $phoneKey, $context, now()->addHours(24));
        }
        Cache::put('severino_test_ctx_latest', $context, now()->addHours(24));
    }

    /**
     * Recupera o contexto de teste para um telefone/JID (se aplicável)
     */
    public static function getTestContextForPhone(?string $remoteJid): ?array
    {
        $phoneKey = self::normalizePhoneKey($remoteJid);
        if ($phoneKey) {
            $ctx = Cache::get('severino_test_ctx_' . $phoneKey);
            if ($ctx && !empty($ctx['user_id'])) {
                return $ctx;
            }
        }
        return null;
    }

    /**
     * 1. Envia mensagem humanizada em tempo real quando a cliente ganha uma peça na live
     */
    public function sendLiveItemWonNotification(
        int $userId,
        int $itemId,
        int $liveId,
        ?float $priceOverride = null,
        ?string $codigoLive = null,
        bool $forceSend = false,
        ?string $overridePhone = null
    ): array {
        $config = self::getConfig();
        $mode = $config['mode'] ?? 'test';

        if (!$forceSend && $mode === 'off') {
            return ['success' => false, 'skipped' => true, 'reason' => 'Modo Severino Live desativado.'];
        }

        $live = DB::table('lives')->where('id', $liveId)->first();
        if ($live && !empty($live->brecho_id) && (int) $live->brecho_id > 1) {
            return ['success' => false, 'skipped' => true, 'reason' => 'Exclusivo para Minha Mania (Matriz).'];
        }

        $user = User::find($userId);
        $item = Item::find($itemId);
        if (!$user || !$item) {
            return ['success' => false, 'error' => 'Cliente ou peça não encontrados.'];
        }

        // Define telefone de destino (Teste vs Cliente Real)
        $isTest = ($overridePhone !== null) || ($mode === 'test');
        $targetPhone = $overridePhone ?: ($isTest ? ($config['test_phone'] ?: '5548984136870') : ($user->whatsapp ?: ($user->phone ?: $user->telefone_principal)));
        $cleanPhone = preg_replace('/\D+/', '', (string) $targetPhone);

        if (strlen($cleanPhone) < 8) {
            return ['success' => false, 'error' => 'Cliente sem telefone WhatsApp cadastrado.'];
        }

        // Busca código da live se não informado
        if (!$codigoLive && DB::getSchemaBuilder()->hasTable('live_items')) {
            $codigoLive = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('item_id', $itemId)
                ->value('codigo_live');
        }

        // Busca todos os itens desta cliente nesta live para saber se é a 1ª peça ou 2ª+ peça
        $itensLive = DB::table('sacolinhas')
            ->where('user_id', $userId)
            ->where('live_id', $liveId)
            ->where('status', '!=', 'pedido')
            ->get();

        $qtdItensLive = max(1, (int) $itensLive->sum(fn($i) => $i->quantity ?? 1));
        $subtotalLive = (float) $itensLive->sum(fn($i) => ($i->price ?? 0) * ($i->quantity ?? 1));

        $precoPeca = $priceOverride !== null ? $priceOverride : (float) ($item->preco ?? 0);
        if ($subtotalLive <= 0) {
            $subtotalLive = $precoPeca;
        }

        $primeiroNome = explode(' ', trim($user->apelido ?: $user->name))[0] ?: 'Lindeza';
        $codDisplay = $codigoLive ? "Cód {$codigoLive}" : ($item->codigo ? "Cód {$item->codigo}" : "Peça");
        $detalhes = array_filter([
            $item->marca ?: null,
            $item->cor ?: null,
            $item->tamanho ? "Tam {$item->tamanho}" : null,
        ]);
        $detalhesStr = !empty($detalhes) ? ' (' . implode(' • ', $detalhes) . ')' : '';
        $nomePeca = trim($item->nome_do_produto ?: ($item->descricao ?: 'Peça da Live'));
        $precoFmt = 'R$ ' . number_format($precoPeca, 2, ',', '.');
        $subtotalFmt = 'R$ ' . number_format($subtotalLive, 2, ',', '.');

        if ($qtdItensLive <= 1) {
            $templates = [
                "Oii *{$primeiroNome}*! 🎉 Passando rapidinho pra confirmar que você garantiu a peça *[{$codDisplay}] {$nomePeca}{$detalhesStr}* por *{$precoFmt}* agora na live! 🛍️✨\n\nPode continuar aproveitando a transmissão tranquila que vou anotando tudo que você pegar por aqui e, no final da live, te mando o fechamento completo, tá bom?",
                "Ebaaa *{$primeiroNome}*! 😍 Sua primeira peça de hoje já tá garantida e separada aqui: *[{$codDisplay}] {$nomePeca}{$detalhesStr}* — *{$precoFmt}*! ✨\n\nContinua curtindo a live que qualquer outra peça que você conseguir eu vou somando por aqui e no final a gente fecha tudo!",
                "Oiê *{$primeiroNome}*! 💖 Anotei aqui pra você a peça *[{$codDisplay}] {$nomePeca}{$detalhesStr}* (*{$precoFmt}*)! Já está reservadinha no seu nome. 🛍️\n\nSegue acompanhando a live tranquila que no final te mando o resuminho completo por aqui mesmo!",
            ];
        } else {
            $templates = [
                "Mais uma pra sua sacola, *{$primeiroNome}*! 😍 Anotei aqui: *[{$codDisplay}] {$nomePeca}{$detalhesStr}* — *{$precoFmt}*.\n\nAgora você já está com *{$qtdItensLive} peças* separadas na live de hoje (subtotal *{$subtotalFmt}*)! ✨",
                "Arrasou no garimpo, *{$primeiroNome}*! 🎉 Garantimos mais uma peça pra você: *[{$codDisplay}] {$nomePeca}{$detalhesStr}* (*{$precoFmt}*).\n\nJá são *{$qtdItensLive} peças* anotadas hoje somando *{$subtotalFmt}*! 🛍️💖",
                "Anotadinha, *{$primeiroNome}*! ✨ +1 peça garantida: *[{$codDisplay}] {$nomePeca}{$detalhesStr}* por *{$precoFmt}*.\n\nSeu subtotal na live de hoje está em *{$subtotalFmt}* ({$qtdItensLive} itens). No final da live te passo o fechamento completo! 😊",
            ];
        }

        $text = $templates[array_rand($templates)];
        if ($isTest) {
            self::setTestContext($cleanPhone, $userId, $liveId);
        }

        return $this->sendAndRecordSeverinoMessage($cleanPhone, $user, $text, [
            'event_type' => 'live_item_won',
            'live_id' => $liveId,
            'item_id' => $itemId,
            'is_test' => $isTest,
        ]);
    }

    /**
     * 2. Envia a mensagem completa de Fechamento de Fim de Live pelo WhatsApp (sem precisar de PDF ou site)
     */
    public function sendLiveClosingMessage(
        int $userId,
        int $liveId,
        ?string $overridePhone = null,
        bool $useTestPhone = false
    ): array {
        $config = self::getConfig();
        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'Cliente não encontrada.'];
        }

        // Busca itens da cliente nesta live (em teste, busca mesmo se status for 'pedido')
        $itensLive = DB::table('sacolinhas')
            ->join('items', 'sacolinhas.item_id', '=', 'items.id')
            ->leftJoin('live_items', function ($join) use ($liveId) {
                $join->on('live_items.item_id', '=', 'sacolinhas.item_id')
                     ->where('live_items.live_id', '=', $liveId);
            })
            ->where('sacolinhas.user_id', $userId)
            ->where('sacolinhas.live_id', $liveId)
            ->where('sacolinhas.status', '!=', 'pedido')
            ->select(
                'sacolinhas.id as sacolinha_id',
                'sacolinhas.item_id',
                'sacolinhas.price',
                'sacolinhas.quantity',
                'items.nome_do_produto as item_name',
                'items.codigo as item_sku',
                'items.marca as item_brand',
                'items.cor as item_color',
                'items.tamanho as item_size',
                'live_items.codigo_live'
            )
            ->get();

        if ($itensLive->isEmpty()) {
            // Fallback caso esteja testando uma live antiga já convertida em pedido
            $itensLive = DB::table('sacolinhas')
                ->join('items', 'sacolinhas.item_id', '=', 'items.id')
                ->leftJoin('live_items', function ($join) use ($liveId) {
                    $join->on('live_items.item_id', '=', 'sacolinhas.item_id')
                         ->where('live_items.live_id', '=', $liveId);
                })
                ->where('sacolinhas.user_id', $userId)
                ->where('sacolinhas.live_id', $liveId)
                ->select(
                    'sacolinhas.id as sacolinha_id',
                    'sacolinhas.item_id',
                    'sacolinhas.price',
                    'sacolinhas.quantity',
                    'items.nome_do_produto as item_name',
                    'items.codigo as item_sku',
                    'items.marca as item_brand',
                    'items.cor as item_color',
                    'items.tamanho as item_size',
                    'live_items.codigo_live'
                )
                ->get();
        }

        if ($itensLive->isEmpty()) {
            return ['success' => false, 'error' => 'Esta cliente não possui itens registrados nesta live.'];
        }

        $resolved = PortalClienteController::resolveUserWelcomeFlow($userId, $liveId);
        $flow = $resolved['flow'];
        $saldoCliente = (float) $resolved['saldoCliente'];

        // Itens anteriores na sacolinha (fora desta live)
        $itensAnterioresCount = (int) DB::table('sacolinhas')
            ->where('user_id', $userId)
            ->where('live_id', '!=', $liveId)
            ->where('status', '!=', 'pedido')
            ->sum('quantity');

        $primeiroNome = explode(' ', trim($user->apelido ?: $user->name))[0] ?: 'Lindeza';

        // Lista formatada de peças da live
        $linhasItens = [];
        $subtotalLive = 0.0;
        $qtdTotalLive = 0;

        foreach ($itensLive as $idx => $it) {
            $qtd = max(1, (int) ($it->quantity ?? 1));
            $val = (float) ($it->price ?? 0) * $qtd;
            $subtotalLive += $val;
            $qtdTotalLive += $qtd;

            $cod = $it->codigo_live ?: ($it->item_sku ?: ($idx + 1));
            $det = array_filter([
                $it->item_brand ?: null,
                $it->item_color ?: null,
                $it->item_size ? "Tam {$it->item_size}" : null,
            ]);
            $detStr = !empty($det) ? ' (' . implode(' • ', $det) . ')' : '';
            $valFmt = 'R$ ' . number_format($val, 2, ',', '.');
            $linhasItens[] = "• *[Cód {$cod}] {$it->item_name}*{$detStr} — *{$valFmt}*";
        }

        $listaPecasTxt = implode("\n", $linhasItens);
        $subtotalFmt = 'R$ ' . number_format($subtotalLive, 2, ',', '.');

        // Cálculo de Carteira (Créditos vs Débitos) idêntico ao Portal
        $linhasValores = [];
        $linhasValores[] = "📦 *Subtotal da Live ({$qtdTotalLive} " . ($qtdTotalLive === 1 ? 'peça' : 'peças') . "):* {$subtotalFmt}";

        if ($saldoCliente > 0) {
            $saldoFmt = 'R$ ' . number_format($saldoCliente, 2, ',', '.');
            $linhasValores[] = "💰 *Saldo em Carteira (Créditos):* - {$saldoFmt}";
            $totalPagar = max(0, $subtotalLive - $saldoCliente);
        } elseif ($saldoCliente < 0) {
            $saldoFmt = 'R$ -' . number_format(abs($saldoCliente), 2, ',', '.');
            $linhasValores[] = "⚠️ *Saldo em Carteira (Débitos):* {$saldoFmt}";
            $totalPagar = $subtotalLive + abs($saldoCliente);
        } else {
            $totalPagar = $subtotalLive;
        }

        $totalPagarFmt = 'R$ ' . number_format($totalPagar, 2, ',', '.');
        $linhasValores[] = "✨ *Total do Pedido:* *{$totalPagarFmt}*";
        $resumoFinanceiroTxt = implode("\n", $linhasValores);

        // Monta o texto personalizado conforme as 5 opções de perfil
        $text = match ($flow) {
            'first_purchase' => "Oii *{$primeiroNome}*! 💖 Que alegria ter você na sua *primeira compra* aqui na *Minha Mania*!\n\n"
                . "A live acabou de encerrar e eu separei com muito carinho o resumo do seu garimpo de hoje:\n\n"
                . "{$listaPecasTxt}\n\n"
                . "{$resumoFinanceiroTxt}\n\n"
                . "Como é sua primeira vez com a gente, funciona super fácil por aqui mesmo no WhatsApp:\n"
                . "1️⃣ *Guardar na Sacolinha:* Você paga apenas as peças de hoje (*{$totalPagarFmt}*) e nós guardamos tudo aqui na loja por até 30 dias pra você ir juntando de outras lives e pagar um frete só no final!\n"
                . "2️⃣ *Enviar Agora:* Eu já calculo o frete pro seu CEP e a gente despacha pra sua casa!\n"
                . "3️⃣ *Ajustar ou Cancelar algum item:* Se quiser retirar alguma peça, é só me falar qual.\n\n"
                . "Como você prefere fazer, *{$primeiroNome}*? Se quiser guardar na sacolinha, já te mando o Pix Copia e Cola ou link de cartão aqui mesmo! 😊",

            'no_bag' => "Oii *{$primeiroNome}*! 💖 Que bom te ver de novo na nossa live!\n\n"
                . "Aqui está o fechamento das peças que você garantiu hoje:\n\n"
                . "{$listaPecasTxt}\n\n"
                . "{$resumoFinanceiroTxt}\n\n"
                . "Como você está sem sacolinha aberta no momento, me conta como prefere seguir:\n"
                . "1️⃣ *Abrir uma Nova Sacolinha* (pagar só o pedido de hoje de *{$totalPagarFmt}* e deixar guardado pra acumular)\n"
                . "2️⃣ *Pedir o Envio Imediato* (calculo o frete pro seu CEP agora mesmo)\n"
                . "3️⃣ *Cancelar algum item* (se precisar tirar alguma peça da lista)\n\n"
                . "É só me responder aqui que eu já gero o seu Pix ou link de pagamento! ✨",

            'regular' => "Oii *{$primeiroNome}*! 💖 Obrigada por estar com a gente em mais uma live!\n\n"
                . "Olha só o resumo das peças que você pegou hoje:\n\n"
                . "{$listaPecasTxt}\n\n"
                . "{$resumoFinanceiroTxt}\n"
                . ($itensAnterioresCount > 0 ? "*(Você também já tem {$itensAnterioresCount} " . ($itensAnterioresCount === 1 ? 'peça guardada' : 'peças guardadas') . " na sua sacolinha aberta)*\n" : "") . "\n"
                . "Como você quer fazer agora?\n"
                . "1️⃣ *Adicionar à Sacolinha e Pagar* (*{$totalPagarFmt}* no Pix ou Cartão)\n"
                . "2️⃣ *Fechar toda a Sacolinha e Enviar* (já cotamos o frete de todas as suas peças juntas)\n"
                . "3️⃣ *Cancelar algum item ou cancelar tudo*\n\n"
                . "Me diz aqui sua opção que eu já resolvo pra você na hora! 😊",

            'clube' => "Oii *{$primeiroNome}*! 👑 Membro VIP do *Clube Mania*!\n\n"
                . "Sua sacolinha da live de hoje já está atualizada! Confira suas peças abaixo:\n\n"
                . "{$listaPecasTxt}\n\n"
                . "{$resumoFinanceiroTxt}\n\n"
                . "Como você deseja prosseguir com as peças de hoje?\n"
                . "1️⃣ *Adicionar à Sacolinha e Pagar* (*{$totalPagarFmt}* via Pix ou Cartão)\n"
                . "2️⃣ *Cancelar algum item* (me diga qual peça deseja excluir)\n"
                . "3️⃣ *Cancelar Tudo* (excluir os itens desta live)\n"
                . "4️⃣ *Solicitar Envio da Sacolinha* (cotar frete)\n\n"
                . ($totalPagar <= 0
                    ? "Como seus créditos em carteira já cobriram tudo, é só confirmar *1* que já deixo tudo quitado na sua sacolinha! 🎉"
                    : "Se estiver tudo certinho e quiser manter na sacolinha, me avisa que já te envio o *Pix Copia e Cola* aqui! ✨"),

            default => "Oii *{$primeiroNome}*! 💖 Passando com o resumo das peças que você separou na live de hoje:\n\n"
                . "{$listaPecasTxt}\n\n"
                . "{$resumoFinanceiroTxt}\n\n"
                . "⚠️ Notamos que sua sacolinha possui pendência ou prazo vencido. Para manter as peças de hoje garantidas, podemos regularizar tudo agora mesmo por aqui!\n\n"
                . "Você prefere:\n"
                . "1️⃣ *Regularizar no Pix ou Cartão e manter na Sacolinha*\n"
                . "2️⃣ *Regularizar e já pedir o Envio (cotar frete)*\n"
                . "3️⃣ *Cancelar algum item da live de hoje*\n\n"
                . "Me chama aqui que te ajudo a resolver rapidinho! 😊",
        };

        $isTest = ($overridePhone !== null) || $useTestPhone || (($config['mode'] ?? 'test') === 'test');
        $targetPhone = $overridePhone ?: ($isTest ? ($config['test_phone'] ?: '5548984136870') : ($user->whatsapp ?: ($user->phone ?: $user->telefone_principal)));
        $cleanPhone = preg_replace('/\D+/', '', (string) $targetPhone);

        if (strlen($cleanPhone) < 8) {
            return ['success' => false, 'error' => 'Cliente sem telefone WhatsApp cadastrado.'];
        }

        if ($isTest) {
            self::setTestContext($cleanPhone, $userId, $liveId);
        } else {
            // Mesmo no modo cliente real, salva o contexto da última live fechada para este telefone
            self::setTestContext($cleanPhone, $userId, $liveId);
        }

        $res = $this->sendAndRecordSeverinoMessage($cleanPhone, $user, $text, [
            'event_type' => 'live_closing',
            'live_id' => $liveId,
            'flow' => $flow,
            'flow_label' => $resolved['label'],
            'is_test' => $isTest,
        ]);

        $res['flow'] = $flow;
        $res['flow_label'] = $resolved['label'];
        $res['target_phone'] = $cleanPhone;
        $res['client_name'] = $user->name;

        return $res;
    }

    /**
     * 3. Processa mensagem recebida da cliente no WhatsApp e responde com o Severino (se ativo)
     */
    public function handleIncomingClientMessage(
        string $remoteJid,
        ?int $userId,
        string $messageBody,
        bool $forceReplyEvenIfHumanMode = false
    ): array {
        $config = self::getConfig();
        if (!$forceReplyEvenIfHumanMode && empty($config['auto_reply'])) {
            return ['handled' => false, 'reason' => 'auto_reply_disabled'];
        }

        // Verifica se este telefone está em um contexto de teste (ex: 5548984136870 simulando uma cliente da live)
        $testCtx = self::getTestContextForPhone($remoteJid);
        $liveId = null;
        if ($testCtx && !empty($testCtx['user_id'])) {
            $userId = (int) $testCtx['user_id'];
            $liveId = !empty($testCtx['live_id']) ? (int) $testCtx['live_id'] : null;
        }

        $supMode = self::getSupervisionMode($remoteJid, $userId);
        if (!$forceReplyEvenIfHumanMode && $supMode === 'human') {
            return ['handled' => false, 'reason' => 'human_supervision_active'];
        }

        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            $digits = preg_replace('/\D+/', '', explode('@', $remoteJid)[0] ?? '');
            if (strlen($digits) >= 8) {
                $without55 = str_starts_with($digits, '55') ? substr($digits, 2) : $digits;
                $user = User::whereIn('whatsapp', [$digits, $without55, '55' . $without55])
                    ->orWhereIn('phone', [$digits, $without55, '55' . $without55])
                    ->first();
                if ($user) {
                    $userId = $user->id;
                }
            }
        }

        if (!$user) {
            return ['handled' => false, 'reason' => 'user_not_found'];
        }

        if (!$liveId) {
            $liveId = (int) (DB::table('sacolinhas')
                ->where('user_id', $user->id)
                ->where('status', '!=', 'pedido')
                ->max('live_id') ?: DB::table('lives')->orderByDesc('id')->value('id'));
        }

        // Monta o estado atual da sacolinha e carteira da cliente
        $contextData = $this->buildClientLiveContext($user, $liveId);

        // Carrega histórico recente da conversa no WhatsApp Evolution
        $recentMessages = EvolutionMessage::where(function ($q) use ($remoteJid, $user) {
                if ($remoteJid) {
                    $q->where('remote_jid', $remoteJid);
                } else {
                    $q->where('user_id', $user->id);
                }
            })
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        // Executa o raciocínio do Severino + Ferramentas (Cancelar item, Cotar Frete, Gerar Pix/Cartão, Chamar Humano)
        $agentResult = $this->runAgentTurn($user, $liveId, $remoteJid, $messageBody, $contextData, $recentMessages);

        $replyText = trim($agentResult['reply'] ?? '');
        $pixCodeToSendSeparately = $agentResult['pix_copia_e_cola'] ?? null;

        if ($replyText === '') {
            return ['handled' => false, 'reason' => 'empty_reply'];
        }

        $sendRes = $this->sendAndRecordSeverinoMessage($remoteJid, $user, $replyText, [
            'event_type' => 'severino_ai_reply',
            'live_id' => $liveId,
            'actions_taken' => $agentResult['actions_taken'] ?? [],
        ], keepExistingMode: true);

        // Se gerou código Pix Copia e Cola, envia em um balão isolado logo em seguida para facilitar copiar no celular
        if (!empty($pixCodeToSendSeparately)) {
            $this->sendAndRecordSeverinoMessage($remoteJid, $user, $pixCodeToSendSeparately, [
                'event_type' => 'severino_pix_code',
                'live_id' => $liveId,
            ], keepExistingMode: true, delay: 800);
        }

        return [
            'handled' => true,
            'reply' => $replyText,
            'pix_copia_e_cola' => $pixCodeToSendSeparately,
            'actions_taken' => $agentResult['actions_taken'] ?? [],
            'send_result' => $sendRes,
        ];
    }

    /**
     * Monta o snapshot completo da cliente na live atual + sacolinha acumulada + carteira
     */
    protected function buildClientLiveContext(User $user, int $liveId): array
    {
        $resolved = PortalClienteController::resolveUserWelcomeFlow($user->id, $liveId);

        $itensLive = DB::table('sacolinhas')
            ->join('items', 'sacolinhas.item_id', '=', 'items.id')
            ->leftJoin('live_items', function ($join) use ($liveId) {
                $join->on('live_items.item_id', '=', 'sacolinhas.item_id')
                     ->where('live_items.live_id', '=', $liveId);
            })
            ->where('sacolinhas.user_id', $user->id)
            ->where('sacolinhas.live_id', $liveId)
            ->where('sacolinhas.status', '!=', 'pedido')
            ->select(
                'sacolinhas.id as sacolinha_id',
                'sacolinhas.item_id',
                'sacolinhas.price',
                'sacolinhas.quantity',
                'items.nome_do_produto as item_name',
                'items.codigo as item_sku',
                'items.marca as item_brand',
                'items.cor as item_color',
                'items.tamanho as item_size',
                'live_items.codigo_live'
            )
            ->get();

        $itensAnteriores = DB::table('sacolinhas')
            ->join('items', 'sacolinhas.item_id', '=', 'items.id')
            ->where('sacolinhas.user_id', $user->id)
            ->where('sacolinhas.live_id', '!=', $liveId)
            ->where('sacolinhas.status', '!=', 'pedido')
            ->select(
                'sacolinhas.id as sacolinha_id',
                'sacolinhas.item_id',
                'sacolinhas.live_id',
                'sacolinhas.price',
                'sacolinhas.quantity',
                'items.nome_do_produto as item_name',
                'items.codigo as item_sku'
            )
            ->get();

        $subtotalLive = (float) $itensLive->sum(fn($i) => ($i->price ?? 0) * ($i->quantity ?? 1));
        $saldoCliente = (float) $resolved['saldoCliente'];

        if ($saldoCliente > 0) {
            $totalPagarLive = max(0, $subtotalLive - $saldoCliente);
        } elseif ($saldoCliente < 0) {
            $totalPagarLive = $subtotalLive + abs($saldoCliente);
        } else {
            $totalPagarLive = $subtotalLive;
        }

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'primeiro_nome' => explode(' ', trim($user->apelido ?: $user->name))[0] ?: $user->name,
            'cep' => $user->cep,
            'endereco' => trim(($user->endereco ?? '') . ', ' . ($user->numero_endereco ?? '') . ' - ' . ($user->bairro ?? '') . ' - ' . ($user->cidade ?? '') . '/' . ($user->estado ?? '')),
            'live_id' => $liveId,
            'flow' => $resolved['flow'],
            'flow_label' => $resolved['label'],
            'is_clube' => $resolved['isClube'],
            'saldo_carteira' => $saldoCliente,
            'subtotal_live' => $subtotalLive,
            'total_pagar_live' => $totalPagarLive,
            'itens_live' => $itensLive->map(fn($i) => [
                'sacolinha_id' => $i->sacolinha_id,
                'item_id' => $i->item_id,
                'codigo_live' => $i->codigo_live ?: $i->item_sku,
                'sku' => $i->item_sku,
                'nome' => $i->item_name,
                'marca' => $i->item_brand,
                'cor' => $i->item_color,
                'tamanho' => $i->item_size,
                'preco' => (float) $i->price,
                'preco_formatado' => 'R$ ' . number_format((float) $i->price, 2, ',', '.'),
            ])->values()->toArray(),
            'qtd_itens_anteriores_sacolinha' => (int) $itensAnteriores->sum(fn($i) => $i->quantity ?? 1),
            'valor_itens_anteriores_sacolinha' => (float) $itensAnteriores->sum(fn($i) => ($i->price ?? 0) * ($i->quantity ?? 1)),
        ];
    }

    /**
     * Executa o turno de conversação do Severino com suporte a Function Calling (Tools)
     */
    protected function runAgentTurn(
        User $user,
        int $liveId,
        string $remoteJid,
        string $messageBody,
        array $contextData,
        $recentMessages
    ): array {
        $actionsTaken = [];
        $pixCopiaECola = null;

        $systemPrompt = "Você é o Severino, atendente virtual super simpático, prestativo e natural da loja *Minha Mania* (brechó especializado em Melissas e moda circular).\n"
            . "Você está conversando pelo WhatsApp com a cliente *{$contextData['name']}* (chame-a carinhosamente de *{$contextData['primeiro_nome']}*).\n\n"
            . "REGRAS DE OURO DE HUMANIZAÇÃO NO WHATSAPP:\n"
            . "1. Escreva como uma pessoa real da equipe no WhatsApp: mensagens curtas, diretas, calorosas, usando formatação nativa do WhatsApp (*negrito* com um asterisco só, nunca use **dois asteriscos** nem tabelas Markdown!).\n"
            . "2. NUNCA mande a cliente entrar no site para resolver coisas simples! Resolva TUDO aqui mesmo na conversa usando suas ferramentas:\n"
            . "   - Se ela pedir para *tirar/cancelar uma peça* ou *cancelar tudo*, CHAME IMEDIATAMENTE a ferramenta `remover_itens_sacolinha_live`.\n"
            . "   - Se ela quiser *guardar na sacolinha / adicionar à sacolinha e pagar* (ou pedir o *Pix* / *link de cartão*), CHAME IMEDIATAMENTE a ferramenta `gerar_pagamento_pix_ou_cartao`.\n"
            . "   - Se ela quiser *pedir envio / saber o valor do frete*, CHAME a ferramenta `cotar_frete_envio` (se ela não tiver CEP cadastrado e não informou na mensagem, peça o CEP dela de forma simpática).\n"
            . "   - Se ela tiver uma dúvida muito complexa, reclamação, quiser falar com a Grasi/atendente humana ou negociar algo fora da sua alçada, CHAME `transferir_para_atendente_humano`.\n"
            . "3. Quando a ferramenta `gerar_pagamento_pix_ou_cartao` retornar o código Pix Copia e Cola, NÃO cole o código longo no meio do seu texto, pois o sistema já vai enviar o código Pix Copia e Cola em um balão separado logo abaixo da sua mensagem para facilitar ela copiar! Apenas avise: 'Já gerei o seu Pix de R$ XX,XX! Vou mandar o código Copia e Cola logo aqui embaixo pra você só copiar e colar no app do banco 👇✨'.\n\n"
            . "DADOS ATUAIS DA CLIENTE (TEMPO REAL):\n"
            . json_encode($contextData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $tools = [
            [
                'functionDeclarations' => [
                    [
                        'name' => 'remover_itens_sacolinha_live',
                        'description' => 'Remove (cancela) uma ou mais peças da live atual da sacolinha da cliente, ou cancela todas as peças da live caso ela peça para cancelar tudo.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'sacolinha_ids' => [
                                    'type' => 'ARRAY',
                                    'items' => ['type' => 'INTEGER'],
                                    'description' => 'Lista de sacolinha_id dos itens que a cliente quer remover/cancelar.',
                                ],
                                'cancelar_tudo' => [
                                    'type' => 'BOOLEAN',
                                    'description' => 'True se a cliente pediu para cancelar todos os itens da live.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'cotar_frete_envio',
                        'description' => 'Calcula as opções reais de frete (PAC, Sedex, transportadora) via Melhor Envio para enviar as peças da sacolinha da cliente.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'cep' => [
                                    'type' => 'STRING',
                                    'description' => 'CEP de destino (8 dígitos). Se a cliente enviou o CEP na mensagem, passe aqui. Se vazio, usa o CEP do cadastro.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'gerar_pagamento_pix_ou_cartao',
                        'description' => 'Gera a cobrança Pix Copia e Cola (Banco Inter) e/ou link de Cartão de Crédito para a cliente pagar as peças (seja para manter na sacolinha ou para fechar pedido com envio).',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'modalidade' => [
                                    'type' => 'STRING',
                                    'description' => "'guardar_sacolinha' (para adicionar/manter na sacolinha e pagar as peças) ou 'enviar_agora' (para fechar pedido com envio).",
                                ],
                                'forma_pagamento' => [
                                    'type' => 'STRING',
                                    'description' => "'pix' ou 'cartao'",
                                ],
                                'valor_frete' => [
                                    'type' => 'NUMBER',
                                    'description' => 'Valor do frete escolhido (0 se for guardar_sacolinha).',
                                ],
                            ],
                            'required' => ['modalidade', 'forma_pagamento'],
                        ],
                    ],
                    [
                        'name' => 'transferir_para_atendente_humano',
                        'description' => 'Pausa a IA e transfere a conversa para supervisão de uma atendente humana da Minha Mania.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'motivo' => [
                                    'type' => 'STRING',
                                    'description' => 'Resumo curto do motivo para a atendente humana.',
                                ],
                            ],
                            'required' => ['motivo'],
                        ],
                    ],
                ],
            ],
        ];

        $contents = [];
        foreach ($recentMessages as $m) {
            $role = ($m->direction === 'outbound') ? 'model' : 'user';
            $txt = trim((string) $m->body);
            if ($txt === '') {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $txt]],
            ];
        }

        // Garante que a última mensagem é a mensagem atual do usuário
        if (empty($contents) || end($contents)['role'] !== 'user' || (end($contents)['parts'][0]['text'] ?? '') !== $messageBody) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $messageBody]],
            ];
        }

        if (!empty($this->geminiApiKey)) {
            $models = ['gemini-2.5-flash', 'gemini-2.0-flash'];
            foreach ($models as $model) {
                try {
                    for ($step = 0; $step < 3; $step++) {
                        $url = "{$this->geminiBaseUrl}/models/{$model}:generateContent?key={$this->geminiApiKey}";
                        $payload = [
                            'systemInstruction' => [
                                'parts' => [['text' => $systemPrompt]],
                            ],
                            'contents' => $contents,
                            'tools' => $tools,
                            'generationConfig' => [
                                'temperature' => 0.35,
                                'maxOutputTokens' => 900,
                            ],
                        ];

                        $resp = Http::timeout(20)->post($url, $payload);
                        if (!$resp->successful()) {
                            Log::warning("SeverinoWhatsapp Gemini ({$model}) erro HTTP {$resp->status()}: " . $resp->body());
                            break;
                        }

                        $data = $resp->json();
                        $candidate = $data['candidates'][0]['content'] ?? null;
                        $parts = $candidate['parts'] ?? [];

                        $functionCalls = [];
                        $textParts = [];
                        foreach ($parts as $part) {
                            if (isset($part['functionCall'])) {
                                $functionCalls[] = $part['functionCall'];
                            }
                            if (isset($part['text'])) {
                                $textParts[] = $part['text'];
                            }
                        }

                        if (!empty($functionCalls)) {
                            $contents[] = $candidate;
                            $responseParts = [];

                            foreach ($functionCalls as $fc) {
                                $fnName = $fc['name'] ?? '';
                                $args = (array) ($fc['args'] ?? []);

                                $toolResult = $this->executeAgentTool($fnName, $args, $user, $liveId, $remoteJid);
                                $actionsTaken[] = ['tool' => $fnName, 'args' => $args, 'result' => $toolResult];

                                if (!empty($toolResult['pix_copia_e_cola'])) {
                                    $pixCopiaECola = $toolResult['pix_copia_e_cola'];
                                }

                                $responseParts[] = [
                                    'functionResponse' => [
                                        'name' => $fnName,
                                        'response' => ['result' => $toolResult],
                                    ],
                                ];
                            }

                            $contents[] = [
                                'role' => 'user',
                                'parts' => $responseParts,
                            ];
                            continue;
                        }

                        $finalText = trim(implode("\n", $textParts));
                        if ($finalText !== '') {
                            // Converte **negrito** markdown para *negrito* do WhatsApp
                            $finalText = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $finalText);
                            return [
                                'reply' => $finalText,
                                'pix_copia_e_cola' => $pixCopiaECola,
                                'actions_taken' => $actionsTaken,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error("Exceção SeverinoWhatsapp Gemini ({$model}): " . $e->getMessage());
                }
            }
        }

        // Fallback via GeminiService (Groq) se Gemini não responder
        $fallbackReply = app(GeminiService::class)->generateAnswer($messageBody, $systemPrompt, []);
        $fallbackReply = preg_replace('/\*\*(.+?)\*\*/s', '*$1*', $fallbackReply);

        return [
            'reply' => $fallbackReply,
            'pix_copia_e_cola' => $pixCopiaECola,
            'actions_taken' => $actionsTaken,
        ];
    }

    /**
     * Executa uma ferramenta chamada pelo Severino
     */
    protected function executeAgentTool(string $fnName, array $args, User $user, int $liveId, string $remoteJid): array
    {
        try {
            if ($fnName === 'remover_itens_sacolinha_live') {
                $cancelarTudo = !empty($args['cancelar_tudo']);
                $sacolinhaIds = array_values(array_filter(array_map('intval', (array) ($args['sacolinha_ids'] ?? []))));

                $query = DB::table('sacolinhas')
                    ->where('user_id', $user->id)
                    ->where('live_id', $liveId)
                    ->where('status', '!=', 'pedido');

                if (!$cancelarTudo && !empty($sacolinhaIds)) {
                    $query->whereIn('id', $sacolinhaIds);
                } elseif (!$cancelarTudo && empty($sacolinhaIds)) {
                    return ['success' => false, 'message' => 'Informe quais sacolinha_ids devem ser removidos ou cancelar_tudo=true.'];
                }

                $sacolas = $query->get();
                if ($sacolas->isEmpty()) {
                    return ['success' => false, 'message' => 'Nenhum item encontrado na sacolinha desta live para remover.'];
                }

                $removedNames = [];
                DB::transaction(function () use ($sacolas, &$removedNames) {
                    foreach ($sacolas as $sac) {
                        $it = DB::table('items')->where('id', $sac->item_id)->first();
                        if ($it) {
                            $removedNames[] = $it->nome_do_produto ?: "Item #{$it->codigo}";
                        }
                        DB::table('sacolinhas')->where('id', $sac->id)->delete();

                        $otherExists = DB::table('sacolinhas')
                            ->where('item_id', $sac->item_id)
                            ->where('status', '!=', 'pedido')
                            ->exists();

                        if (!$otherExists) {
                            DB::table('items')->where('id', $sac->item_id)->update([
                                'status' => 'estoque',
                                'updated_at' => now(),
                            ]);
                        }
                    }
                });

                $updatedContext = $this->buildClientLiveContext($user, $liveId);
                return [
                    'success' => true,
                    'removed_count' => count($removedNames),
                    'removed_items' => $removedNames,
                    'novo_subtotal_live' => $updatedContext['subtotal_live'],
                    'novo_total_pagar_live' => $updatedContext['total_pagar_live'],
                    'itens_restantes_live' => $updatedContext['itens_live'],
                ];
            }

            if ($fnName === 'cotar_frete_envio') {
                $cepRaw = preg_replace('/\D+/', '', (string) ($args['cep'] ?? $user->cep ?? ''));
                if (strlen($cepRaw) !== 8) {
                    return [
                        'success' => false,
                        'needs_cep' => true,
                        'message' => 'A cliente ainda não possui CEP válido de 8 dígitos cadastrado. Peça o CEP para cotar o frete.',
                    ];
                }

                if ($user->cep !== $cepRaw) {
                    $user->cep = $cepRaw;
                    $user->save();
                }

                $itemIds = DB::table('sacolinhas')
                    ->where('user_id', $user->id)
                    ->where('status', '!=', 'pedido')
                    ->pluck('item_id')
                    ->toArray();

                if (empty($itemIds)) {
                    return ['success' => false, 'message' => 'Nenhum item aberto na sacolinha para calcular frete.'];
                }

                $shippingCalc = app(ShippingCalculatorService::class);
                $melhorEnvio = app(MelhorEnvioService::class);
                $packageData = $shippingCalc->calculateForItems($itemIds);
                $resFrete = $melhorEnvio->calculateShipping($cepRaw, $packageData);

                $opcoes = [];
                if (!empty($resFrete['success']) && !empty($resFrete['options'])) {
                    foreach ($resFrete['options'] as $opt) {
                        if (isset($opt['price']) && (float) $opt['price'] > 0) {
                            $opcoes[] = [
                                'servico' => ($opt['company']['name'] ?? '') . ' - ' . ($opt['name'] ?? ''),
                                'valor' => (float) $opt['price'],
                                'valor_formatado' => 'R$ ' . number_format((float) $opt['price'], 2, ',', '.'),
                                'prazo_dias' => $opt['delivery_time'] ?? null,
                            ];
                        }
                    }
                }

                $opcoes[] = [
                    'servico' => 'Retirar na Loja (Minha Mania)',
                    'valor' => 0.0,
                    'valor_formatado' => 'Grátis',
                    'prazo_dias' => 0,
                ];

                $ctx = $this->buildClientLiveContext($user, $liveId);
                return [
                    'success' => true,
                    'cep' => $cepRaw,
                    'opcoes_frete' => $opcoes,
                    'total_pecas_sem_frete' => $ctx['total_pagar_live'],
                ];
            }

            if ($fnName === 'gerar_pagamento_pix_ou_cartao') {
                $modalidade = $args['modalidade'] ?? 'guardar_sacolinha';
                $forma = strtolower($args['forma_pagamento'] ?? 'pix');
                $valorFrete = (float) ($args['valor_frete'] ?? 0);

                $ctx = $this->buildClientLiveContext($user, $liveId);
                $valorCobrar = max(0, (float) $ctx['total_pagar_live'] + $valorFrete);

                if ($valorCobrar <= 0) {
                    return [
                        'success' => true,
                        'ja_quitado_por_creditos' => true,
                        'message' => 'O saldo de créditos em carteira da cliente já cobre 100% do valor! Não há nada a pagar.',
                    ];
                }

                // Garante registro em pessoas para gerar Lançamento financeiro com payment_token
                $pessoa = $user->perfilFinanceiro;
                if (!$pessoa) {
                    $pessoa = Pessoa::create([
                        'user_id' => $user->id,
                        'nome' => $user->name,
                        'documento' => $user->cpf ?? $user->whatsapp ?? $user->phone,
                        'tipo' => 'cliente_circular',
                    ]);
                }

                $classificacao = \App\Models\ClassificacaoFinanceira::where('nome', 'Recarga de Carteira')
                    ->orWhere('nome', 'Aporte de Carteira')
                    ->orWhere('nome', 'Receitas')
                    ->first();
                $classificacaoId = $classificacao ? $classificacao->id : 3;

                $lancamento = Lancamento::create([
                    'tipo' => 'receita',
                    'status' => 'pendente',
                    'pessoa_id' => $pessoa->id,
                    'classificacao_financeira_id' => $classificacaoId,
                    'data_emissao' => now(),
                    'data_vencimento' => now()->addDay(),
                    'valor_total' => $valorCobrar,
                    'descricao' => ($modalidade === 'enviar_agora' ? "Fechamento Live #{$liveId} + Frete" : "Aporte Sacolinha Live #{$liveId}"),
                    'referencia_tipo' => 'live',
                    'referencia_id' => $liveId,
                    'payment_token' => bin2hex(random_bytes(32)),
                ]);

                $linkCheckout = route('portal.checkout.pagamento', $lancamento->payment_token);
                $pixCopiaECola = null;
                $chavePixFallback = config('services.banco_inter.chave_pix', 'mania@maniademelissa.com');

                if ($forma === 'pix') {
                    try {
                        $pixService = app(BancoInterPixService::class);
                        $refCob = 'LIV-' . str_pad((string) $lancamento->id, 6, '0', STR_PAD_LEFT);
                        $respPix = $pixService->criarPixCob($refCob, $valorCobrar, $user->name, $user->cpf);
                        $pixCopiaECola = $respPix['pixCopiaECola'] ?? $respPix['pix_copia_e_cola'] ?? null;
                        if (!empty($respPix['txid'])) {
                            $lancamento->inter_txid = $respPix['txid'];
                            $lancamento->save();
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Fallback chave Pix estática no SeverinoWhatsapp: ' . $e->getMessage());
                    }
                }

                return [
                    'success' => true,
                    'valor_cobrar' => $valorCobrar,
                    'valor_formatado' => 'R$ ' . number_format($valorCobrar, 2, ',', '.'),
                    'forma_pagamento' => $forma,
                    'pix_copia_e_cola' => $pixCopiaECola,
                    'chave_pix_manual' => $chavePixFallback,
                    'link_pagamento_cartao_ou_pix' => $linkCheckout,
                    'instrucao' => $pixCopiaECola
                        ? 'O código Pix Copia e Cola será enviado automaticamente em uma mensagem separada logo abaixo da sua resposta.'
                        : "Informe a chave Pix {$chavePixFallback} ou envie o link direto {$linkCheckout}.",
                ];
            }

            if ($fnName === 'transferir_para_atendente_humano') {
                $motivo = $args['motivo'] ?? 'Solicitação de atendimento humano';
                self::setSupervisionMode($remoteJid, $user->id, 'human', $motivo);
                return [
                    'success' => true,
                    'supervision_mode' => 'human',
                    'message' => 'Conversa transferida para atendimento humano. Avise a cliente com carinho que uma atendente da equipe já vai responder.',
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Erro executando tool {$fnName} no SeverinoWhatsapp: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return ['success' => false, 'error' => 'Ferramenta desconhecida'];
    }

    /**
     * Envia mensagem via EvolutionApiService e registra em evolution_messages como enviada pelo Severino
     */
    protected function sendAndRecordSeverinoMessage(
        string $toPhoneOrJid,
        User $user,
        string $text,
        array $meta = [],
        bool $keepExistingMode = false,
        int $delay = 1800
    ): array {
        $res = $this->evolution->sendText($toPhoneOrJid, $text, $delay);
        $status = ($res['success'] ?? false) ? 'sent' : 'failed';
        $messageId = $res['message_id'] ?? null;
        $canonicalJid = $res['remote_jid'] ?? (str_contains($toPhoneOrJid, '@') ? $toPhoneOrJid : (preg_replace('/\D+/', '', $toPhoneOrJid) . '@s.whatsapp.net'));
        $digits = preg_replace('/\D+/', '', explode('@', $canonicalJid)[0] ?? '');

        if (!$keepExistingMode) {
            self::setSupervisionMode($canonicalJid, $user->id, 'severino');
        }

        $pushName = $user->name;
        if (!empty($meta['is_test'])) {
            $pushName = "🧪 [Teste: {$user->name}]";
        }

        $msg = EvolutionMessage::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'instance_name' => 'sacolinhas',
            'remote_jid' => $canonicalJid,
            'push_name' => $pushName,
            'direction' => 'outbound',
            'status' => $status,
            'message_id' => $messageId,
            'from' => 'severino',
            'to' => $digits ?: $canonicalJid,
            'body' => $text,
            'failed_reason' => ($status === 'failed') ? ($res['error'] ?? 'Falha no envio') : null,
            'raw_payload' => array_merge($res, [
                'sender_type' => 'severino',
                'severino_meta' => $meta,
            ]),
        ]);

        return [
            'success' => $status === 'sent',
            'message' => $msg,
            'remote_jid' => $canonicalJid,
            'error' => $res['error'] ?? null,
        ];
    }
}
