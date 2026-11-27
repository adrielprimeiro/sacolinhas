<?php

namespace App\Services\Ai;

use App\Http\Controllers\LiveController;
use App\Http\Controllers\PortalClienteController;
use App\Jobs\ProcessSeverinoLiveClosingQueueJob;
use App\Models\ClientWish;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    protected static function configFilePath(): string
    {
        return storage_path('app/severino_whatsapp_config.json');
    }

    protected static function supervisionFilePath(): string
    {
        return storage_path('app/severino_supervision_modes.json');
    }

    /**
     * Obtém a configuração global do fluxo Severino WhatsApp na Live
     * (Persistido em arquivo no storage para NUNCA ser apagado por php artisan optimize:clear / cache:clear)
     */
    public static function getConfig(): array
    {
        $saved = [];
        $path = self::configFilePath();
        if (is_file($path)) {
            $raw = @file_get_contents($path);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $saved = $decoded;
                }
            }
        }
        if (empty($saved)) {
            $cached = Cache::get('severino_live_whatsapp_config', []);
            if (is_array($cached) && !empty($cached)) {
                $saved = $cached;
            }
        }

        return [
            // 'test' = envia para o telefone de teste | 'live' = envia para o cliente real | 'off' = desativado no clique
            'mode' => $saved['mode'] ?? 'test',
            'test_phone' => $saved['test_phone'] ?? '5548984136870',
            'auto_reply' => array_key_exists('auto_reply', $saved) ? (bool) $saved['auto_reply'] : false,
        ];
    }

    /**
     * Salva a configuração global do fluxo Severino WhatsApp na Live (em arquivo persistente + cache)
     */
    public static function saveConfig(array $data): array
    {
        $current = self::getConfig();
        $updated = [
            'mode' => in_array($data['mode'] ?? '', ['test', 'live', 'off']) ? $data['mode'] : $current['mode'],
            'test_phone' => !empty($data['test_phone']) ? preg_replace('/\D+/', '', (string) $data['test_phone']) : $current['test_phone'],
            'auto_reply' => array_key_exists('auto_reply', $data) ? (bool) $data['auto_reply'] : $current['auto_reply'],
        ];

        $path = self::configFilePath();
        @file_put_contents($path, json_encode($updated, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
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

    protected static function loadSupervisionMap(): array
    {
        $path = self::supervisionFilePath();
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Retorna 'severino' ou 'human' para a conversa (imune a cache:clear)
     */
    public static function getSupervisionMode(?string $remoteJid, ?int $userId = null): string
    {
        $key = self::getConvSupervisionKey($remoteJid, $userId);
        $map = self::loadSupervisionMap();
        if (isset($map[$key])) {
            return $map[$key] === 'human' ? 'human' : 'severino';
        }
        if ($userId && isset($map['severino_sup_user_' . $userId])) {
            return $map['severino_sup_user_' . $userId] === 'human' ? 'human' : 'severino';
        }
        return Cache::get($key, 'severino');
    }

    /**
     * Define 'severino' ou 'human' para a conversa (persistido em arquivo + cache)
     */
    public static function setSupervisionMode(?string $remoteJid, ?int $userId, string $mode, ?string $reason = null): void
    {
        $mode = ($mode === 'human') ? 'human' : 'severino';
        $key = self::getConvSupervisionKey($remoteJid, $userId);

        $map = self::loadSupervisionMap();
        $map[$key] = $mode;
        if ($userId) {
            $map['severino_sup_user_' . $userId] = $mode;
        }
        @file_put_contents(self::supervisionFilePath(), json_encode($map, JSON_UNESCAPED_UNICODE), LOCK_EX);

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
     * Consulta a atribuição oficial da conversa no Chat Evolution (tabela evolution_chat_assignments):
     * - assigned_type: 'none' | 'admin' | 'severino'
     */
    public static function getConversationAssignment(?string $remoteJid, ?int $userId = null): array
    {
        $default = [
            'assigned_type' => 'none',
            'assigned_admin_id' => null,
            'assigned_admin_name' => null,
            'assigned_value' => '',
        ];

        try {
            if (!DB::getSchemaBuilder()->hasTable('evolution_chat_assignments')) {
                return $default;
            }

            $row = null;
            if ($remoteJid) {
                $row = DB::table('evolution_chat_assignments as eca')
                    ->leftJoin('users as u', 'u.id', '=', 'eca.assigned_admin_id')
                    ->where('eca.remote_jid', $remoteJid)
                    ->select('eca.assigned_type', 'eca.assigned_admin_id', 'u.name as assigned_admin_name')
                    ->first();
            }

            if (!$row && $remoteJid) {
                $phoneKey = self::normalizePhoneKey($remoteJid);
                if ($phoneKey && strlen($phoneKey) >= 8) {
                    $row = DB::table('evolution_chat_assignments as eca')
                        ->leftJoin('users as u', 'u.id', '=', 'eca.assigned_admin_id')
                        ->where('eca.remote_jid', 'like', "%{$phoneKey}%")
                        ->select('eca.assigned_type', 'eca.assigned_admin_id', 'u.name as assigned_admin_name')
                        ->orderByDesc('eca.id')
                        ->first();
                }
            }

            if (!$row && $userId) {
                $row = DB::table('evolution_chat_assignments as eca')
                    ->leftJoin('users as u', 'u.id', '=', 'eca.assigned_admin_id')
                    ->where('eca.user_id', $userId)
                    ->select('eca.assigned_type', 'eca.assigned_admin_id', 'u.name as assigned_admin_name')
                    ->orderByDesc('eca.id')
                    ->first();
            }

            if (!$row) {
                return $default;
            }

            $type = in_array($row->assigned_type, ['admin', 'severino'], true) ? $row->assigned_type : 'none';
            return [
                'assigned_type' => $type,
                'assigned_admin_id' => $type === 'admin' ? ($row->assigned_admin_id ? (int) $row->assigned_admin_id : null) : null,
                'assigned_admin_name' => $type === 'severino'
                    ? '🤖 Severino (IA)'
                    : ($type === 'admin' ? ($row->assigned_admin_name ?: null) : null),
                'assigned_value' => $type === 'severino'
                    ? 'severino'
                    : ($type === 'admin' && $row->assigned_admin_id ? (string) $row->assigned_admin_id : ''),
            ];
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Atribui uma conversa do Chat Evolution para: 'none' (Ninguém), 'severino' (IA), ou 'admin' (Atendente humano).
     */
    public static function assignConversation(
        string $remoteJid,
        ?int $userId,
        string $assignedType,
        ?int $assignedAdminId = null,
        ?int $assignedByAdminId = null
    ): array {
        $type = in_array($assignedType, ['none', 'admin', 'severino'], true) ? $assignedType : 'none';
        if ($type === 'admin' && !$assignedAdminId) {
            $type = 'none';
        }

        if (DB::getSchemaBuilder()->hasTable('evolution_chat_assignments')) {
            DB::table('evolution_chat_assignments')->updateOrInsert(
                ['remote_jid' => $remoteJid],
                [
                    'user_id' => $userId,
                    'assigned_type' => $type,
                    'assigned_admin_id' => $type === 'admin' ? $assignedAdminId : null,
                    'assigned_by_admin_id' => $assignedByAdminId,
                    'assigned_at' => $type === 'none' ? null : now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Mantém supervision mode sincronizado
        self::setSupervisionMode(
            $remoteJid,
            $userId,
            $type === 'severino' ? 'severino' : 'human',
            $type === 'severino' ? null : 'Conversa atribuída fora do Severino'
        );

        return self::getConversationAssignment($remoteJid, $userId);
    }

    /**
     * Define se o Severino PODE responder automaticamente a esta conversa:
     * - Se atribuída explicitamente ao Severino ('severino') -> SIM (pode responder!)
     * - Se atribuída a um humano ('admin') -> NÃO
     * - Se não atribuída ('none') -> só responde se 'auto_reply' global estiver ON (e nesse caso assume a atribuição)
     */
    public static function canSeverinoReply(?string $remoteJid, ?int $userId = null): bool
    {
        $assignment = self::getConversationAssignment($remoteJid, $userId);
        if ($assignment['assigned_type'] === 'severino') {
            return true;
        }
        if ($assignment['assigned_type'] === 'admin') {
            return false;
        }

        $config = self::getConfig();
        if (!empty($config['auto_reply'])) {
            if ($remoteJid) {
                self::assignConversation($remoteJid, $userId, 'severino', null, null);
            }
            return true;
        }

        return false;
    }

    /**
     * Registra qual cliente/live está ativo para este telefone (em teste ou em produção)
     */
    public static function setTestContext(string $testPhone, int $userId, int $liveId, bool $isTest = true): void
    {
        $phoneKey = self::normalizePhoneKey($testPhone);
        $context = [
            'user_id' => $userId,
            'live_id' => $liveId,
            'test_phone' => $testPhone,
            'is_test' => $isTest,
            'updated_at' => now()->toDateTimeString(),
        ];
        if ($phoneKey) {
            Cache::put('severino_test_ctx_' . $phoneKey, $context, now()->addHours(24));
        }
        if ($isTest) {
            Cache::put('severino_test_ctx_latest', $context, now()->addHours(24));
        }
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
     * Sensores de Contexto da Cliente e da Conversa no WhatsApp:
     * - is_first_contact_ever: 1º contato na vida com a Minha Mania
     * - is_first_purchase: nunca teve pedido aprovado
     * - is_starting_conversation: sem mensagens recentes (~10h) nesta conversa
     * - saudacao_horario: 'bom dia' | 'boa tarde' | 'boa noite' (horário de Brasília)
     */
    public function detectConversationState(User $user, string $cleanPhone, int $liveId, bool $isTest = false): array
    {
        $hora = Carbon::now('America/Sao_Paulo')->hour;
        if ($hora >= 5 && $hora < 12) {
            $saudacao = 'bom dia';
        } elseif ($hora >= 12 && $hora < 18) {
            $saudacao = 'boa tarde';
        } else {
            $saudacao = 'boa noite';
        }

        $isFirstPurchase = !DB::table('pedidos')
            ->where('user_id', $user->id)
            ->where('status_pagamento', 'aprovado')
            ->where('status_pedido', '!=', 'cancelado')
            ->exists();

        $phoneSuffix = strlen($cleanPhone) >= 8 ? substr($cleanPhone, -8) : null;

        // Verifica se já houve QUALQUER conversa anterior na vida com esta cliente
        $hasEvoHistory = EvolutionMessage::where(function ($q) use ($user, $phoneSuffix, $isTest) {
                $q->where('user_id', $user->id);
                if (!$isTest && $phoneSuffix) {
                    $q->orWhere('remote_jid', 'like', "%{$phoneSuffix}%");
                }
            })
            ->where('status', '!=', 'failed')
            ->exists();

        $hasTwilioHistory = false;
        if (!$hasEvoHistory && DB::getSchemaBuilder()->hasTable('whatsapp_messages')) {
            $hasTwilioHistory = DB::table('whatsapp_messages')
                ->where('user_id', $user->id)
                ->exists();
        }

        $isFirstContactEver = $isFirstPurchase && !$hasEvoHistory && !$hasTwilioHistory;

        // Verifica se estamos "iniciando uma conversa" (nenhuma mensagem nas últimas 10h para esta cliente)
        $hasRecentMessage = EvolutionMessage::where(function ($q) use ($user, $phoneSuffix, $isTest) {
                $q->where('user_id', $user->id);
                if (!$isTest && $phoneSuffix) {
                    $q->orWhere('remote_jid', 'like', "%{$phoneSuffix}%");
                }
            })
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now()->subHours(10))
            ->exists();

        $isStartingConversation = !$hasRecentMessage;

        return [
            'is_first_contact_ever' => $isFirstContactEver,
            'is_first_purchase' => $isFirstPurchase,
            'is_starting_conversation' => $isStartingConversation,
            'saudacao_horario' => $saudacao,
            'saudacao_cap' => ucfirst($saudacao),
        ];
    }

    /**
     * 1. Envia mensagem humanizada em tempo real quando a cliente ganha uma peça na live
     *    - Se estamos iniciando conversa (1ª msg da live/dia): 2 balões curtos (abertura humana + peça)
     *    - Se for 1º contato na vida / 1ª compra: abertura dá boas-vindas e segurança de que é a Minha Mania
     *    - Se conversa já está ativa (2ª+ peça): apenas 1 balão curto (<= 5 palavras + peça em destaque)
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

        // Define telefone de destino (Teste vs Cliente Real) normalizando DDI 55 corretamente (inclusive DDD 55 do RS!)
        $isTest = ($overridePhone !== null) || ($mode === 'test');
        $targetPhone = $overridePhone ?: ($isTest ? ($config['test_phone'] ?: '5548984136870') : ($user->whatsapp ?: ($user->phone ?: $user->telefone_principal)));
        $cleanPhone = EvolutionApiService::normalizeBrazilPhone((string) $targetPhone);

        if (strlen($cleanPhone) < 10) {
            return ['success' => false, 'error' => 'Cliente sem telefone WhatsApp válido (com DDD).'];
        }

        // Trava atômica anti-duplicidade: impede 2 envios simultâneos/duplicados para a mesma peça + cliente + live
        $dedupKey = "severino_item_won_sent_{$liveId}_{$userId}_{$itemId}";
        if (!$forceSend && !Cache::add($dedupKey, now()->toIso8601String(), now()->addHours(12))) {
            return [
                'success' => false,
                'skipped' => true,
                'duplicate' => true,
                'reason' => 'Notificação desta peça já enviada para esta cliente nesta live.',
            ];
        }

        // Busca código da live se não informado
        if (!$codigoLive && DB::getSchemaBuilder()->hasTable('live_items')) {
            $codigoLive = DB::table('live_items')
                ->where('live_id', $liveId)
                ->where('item_id', $itemId)
                ->value('codigo_live');
        }

        // Quantidade de peças desta cliente nesta live
        $qtdItensLive = max(1, (int) DB::table('sacolinhas')
            ->where('user_id', $userId)
            ->where('live_id', $liveId)
            ->where('status', '!=', 'pedido')
            ->sum('quantity'));

        $precoPeca = $priceOverride !== null ? $priceOverride : (float) ($item->preco ?? 0);
        $primeiroNome = explode(' ', trim($user->apelido ?: $user->name))[0] ?: 'Lindeza';
        $codDisplay = $codigoLive ? "Cód {$codigoLive}" : ($item->codigo ? "Cód {$item->codigo}" : "Peça");
        $cleanSize = self::formatItemSize($item->tamanho);
        $detalhes = array_filter([
            $item->marca ?: null,
            $item->cor ?: null,
            $cleanSize ? "Tam {$cleanSize}" : null,
        ]);
        $detalhesStr = !empty($detalhes) ? ' (' . implode(' • ', $detalhes) . ')' : '';
        $nomePeca = trim($item->nome_do_produto ?: ($item->descricao ?: 'Peça da Live'));
        $precoFmt = 'R$ ' . number_format($precoPeca, 2, ',', '.');

        // Sensores de contexto: 1º contato na vida / iniciando conversa / conversa ativa
        $state = $this->detectConversationState($user, $cleanPhone, $liveId, $isTest);
        $saudacao = $state['saudacao_horario'];
        $saudacaoCap = $state['saudacao_cap'];

        // Registra contexto da live ativa para este número (marcando se é teste ou cliente real)
        self::setTestContext($cleanPhone, $userId, $liveId, $isTest);

        $greetingResult = null;
        $sentTwoBalloons = false;

        // Trava atômica de saudação: garante no máximo 1 abertura por cliente nesta live, mesmo com 2 peças clicadas em paralelo
        $greetingKey = "severino_live_greeted_{$liveId}_{$userId}";
        $shouldSendGreeting = $state['is_starting_conversation']
            && Cache::add($greetingKey, now()->toIso8601String(), now()->addHours(10));

        // Se estamos INICIANDO UMA CONVERSA (ou 1º contato na vida): envia Balão 1 (saudação/segurança) antes da peça
        if ($shouldSendGreeting) {
            if ($state['is_first_contact_ever'] || $state['is_first_purchase']) {
                $aberturas = [
                    "Oii, *{$primeiroNome}*! 💖 Aqui é a equipe da *Minha Mania Brechó*. Que alegria ter você na sua primeira live com a gente! Vou te mandando por aqui cada peça que você garantir, tá?",
                    "Oii *{$primeiroNome}*, {$saudacao}! 💖 Aqui é a equipe da *Minha Mania Brechó*! Seja super bem-vinda — vou te mandando aqui cada peça que você garantir na live ✨",
                ];
                $greetingType = 'live_first_contact_greeting';
            } else {
                $aberturas = [
                    "Oii *{$primeiroNome}*, {$saudacao}! 💖 Que bom te ver na live!",
                    "Oii *{$primeiroNome}*! ✨ {$saudacaoCap}, que alegria ter você na live de hoje! 💖",
                    "Eii *{$primeiroNome}*, {$saudacao}! 💖 Que bom ter você com a gente na live!",
                ];
                $greetingType = 'live_returning_greeting';
            }

            $greetingText = $aberturas[array_rand($aberturas)];
            $greetingResult = $this->sendAndRecordSeverinoMessage($cleanPhone, $user, $greetingText, [
                'event_type' => $greetingType,
                'live_id' => $liveId,
                'item_id' => $itemId,
                'is_test' => $isTest,
                'conversation_state' => $state,
            ], keepExistingMode: false, delay: 1600);

            if (empty($greetingResult['success'])) {
                Cache::forget($greetingKey);
                if (!$forceSend) {
                    Cache::forget($dedupKey);
                }
                return [
                    'success' => false,
                    'error' => $greetingResult['error'] ?? 'Falha ao enviar abertura no WhatsApp.',
                    'greeting_result' => $greetingResult,
                ];
            } else {
                $sentTwoBalloons = true;
            }
        }

        // Balão da peça (sempre frase curta <= 5 palavras + peça em destaque, sem somatórios)
        if ($sentTwoBalloons || $qtdItensLive <= 1) {
            $frases = [
                "Peça garantida na live! 🎉",
                "Anotado pra você! ✨",
                "Peça reservada com sucesso! 💖",
                "Arrasou no garimpo! 🎉",
            ];
        } else {
            $frases = [
                "Mais uma peça garantida! 🎉",
                "Anotada mais uma! ✨",
                "Mais uma pra você! 🛍️",
                "Outra peça garantida! 💖",
            ];
        }

        $frase = $frases[array_rand($frases)];
        $text = "{$frase}\n\n*[{$codDisplay}] {$nomePeca}{$detalhesStr}* — *{$precoFmt}*";

        $itemRes = $this->sendAndRecordSeverinoMessage($cleanPhone, $user, $text, [
            'event_type' => 'live_item_won',
            'live_id' => $liveId,
            'item_id' => $itemId,
            'is_test' => $isTest,
            'sent_two_balloons' => $sentTwoBalloons,
            'conversation_state' => $state,
        ], keepExistingMode: $sentTwoBalloons, delay: $sentTwoBalloons ? 2200 : 1500);

        if (empty($itemRes['success']) && !$forceSend) {
            Cache::forget($dedupKey);
        }

        $itemRes['sent_two_balloons'] = $sentTwoBalloons;
        $itemRes['conversation_state'] = $state;
        $itemRes['greeting_result'] = $greetingResult;

        return $itemRes;
    }

    /**
     * Sanitiza o tamanho da peça (corrige casos em que o Excel converteu ex: "5/6" em data JS "Fri Jun 05 2026... GMT-0300")
     */
    public static function formatItemSize(?string $rawSize): ?string
    {
        $size = trim((string) $rawSize);
        if ($size === '') {
            return null;
        }

        // Detecta string de Date do JavaScript (ex: "Fri Jun 05 2026 00:00:00 GMT-0300 ...")
        if (preg_match('/^[A-Z][a-z]{2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2})\s+\d{4}/i', $size, $m)) {
            $months = [
                'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
                'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
                'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
            ];
            $mes = $months[strtolower($m[1])] ?? null;
            $dia = (int) $m[2];
            if ($mes && $dia > 0) {
                return "{$dia}/{$mes}";
            }
            return null;
        }

        if (stripos($size, 'GMT') !== false || mb_strlen($size) > 20) {
            return null;
        }

        return $size;
    }

    /**
     * 2. Envia o Fechamento de Fim de Live pelo WhatsApp (PDF padrão + opcionalmente texto financeiro e opções)
     *    Quando $pdfOnly = true, envia APENAS o PDF com legenda "📄 Fechamento da Live #XXX" sem texto nem sequência do Severino.
     */
    public function sendLiveClosingMessage(
        int $userId,
        int $liveId,
        ?string $overridePhone = null,
        bool $useTestPhone = false,
        bool $pdfOnly = false
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
        $qtdPecasTermo = $qtdTotalLive === 1 ? '1 peça' : "{$qtdTotalLive} peças";

        // Cálculo de Carteira (Créditos vs Débitos) com rótulos claros solicitados pelo usuário
        $linhasValores = [];
        $linhasValores[] = "🛍️ *Valor da Live ({$qtdPecasTermo}):* {$subtotalFmt}";

        if ($saldoCliente > 0) {
            $saldoFmt = 'R$ ' . number_format($saldoCliente, 2, ',', '.');
            $linhasValores[] = "💰 *Seus Créditos com a Mania:* {$saldoFmt}";
            $totalPagar = max(0, $subtotalLive - $saldoCliente);
            $totalPagarFmt = 'R$ ' . number_format($totalPagar, 2, ',', '.');
            $linhasValores[] = "✨ *A pagar (descontando seu crédito):* *{$totalPagarFmt}*";

            if ($saldoCliente > $subtotalLive) {
                $restanteCredito = $saldoCliente - $subtotalLive;
                $restanteFmt = 'R$ ' . number_format($restanteCredito, 2, ',', '.');
                $linhasValores[] = "*(Você ainda tem {$restanteFmt} de crédito restante)*";
            }
        } elseif ($saldoCliente < 0) {
            $saldoFmt = 'R$ ' . number_format(abs($saldoCliente), 2, ',', '.');
            $linhasValores[] = "⚠️ *Seus Débitos com a Mania:* {$saldoFmt}";
            $totalPagar = $subtotalLive + abs($saldoCliente);
            $totalPagarFmt = 'R$ ' . number_format($totalPagar, 2, ',', '.');
            $linhasValores[] = "✨ *A pagar (adicionando seu débito):* *{$totalPagarFmt}*";
        } else {
            $totalPagar = $subtotalLive;
            $totalPagarFmt = 'R$ ' . number_format($totalPagar, 2, ',', '.');
            $linhasValores[] = "✨ *Total a pagar:* *{$totalPagarFmt}*";
        }

        if ($itensAnterioresCount > 0) {
            $termoSacola = $itensAnterioresCount === 1 ? '1 peça guardada' : "{$itensAnterioresCount} peças guardadas";
            $linhasValores[] = "*(Sua sacolinha já tem {$termoSacola})*";
        }

        $resumoFinanceiroTxt = implode("\n", $linhasValores);

        // Opções de ação diretas, sem repetição de informações ou saudações longas
        $opcao1 = match ($flow) {
            'first_purchase' => $totalPagar <= 0
                ? "1️⃣ *Guardar na Sacolinha* (seu crédito já quitou tudo! 🎉)"
                : "1️⃣ *Guardar na Sacolinha e Pagar* (*{$totalPagarFmt}* no Pix ou Cartão)",
            'no_bag' => $totalPagar <= 0
                ? "1️⃣ *Abrir Sacolinha* (seu crédito já quitou tudo! 🎉)"
                : "1️⃣ *Abrir Sacolinha e Pagar* (*{$totalPagarFmt}* no Pix ou Cartão)",
            'clube' => $totalPagar <= 0
                ? "1️⃣ *Adicionar à Sacolinha* (seu crédito já quitou tudo! 🎉)"
                : "1️⃣ *Adicionar à Sacolinha e Pagar* (*{$totalPagarFmt}* via Pix ou Cartão)",
            'pendencies' => $totalPagar <= 0
                ? "1️⃣ *Regularizar e manter na Sacolinha* (crédito quitou tudo! 🎉)"
                : "1️⃣ *Regularizar e manter na Sacolinha* (*{$totalPagarFmt}* no Pix ou Cartão)",
            default => $totalPagar <= 0
                ? "1️⃣ *Adicionar à Sacolinha* (seu crédito já quitou tudo! 🎉)"
                : "1️⃣ *Adicionar à Sacolinha e Pagar* (*{$totalPagarFmt}* no Pix ou Cartão)",
        };

        $opcao2 = match ($flow) {
            'first_purchase', 'no_bag' => "2️⃣ *Enviar Agora* (já cotamos o frete pro seu CEP)",
            'clube' => "2️⃣ *Fechar toda a Sacolinha e Enviar* (com frete fixo do Clube)",
            'pendencies' => "2️⃣ *Regularizar e Enviar tudo* (já cotamos o frete)",
            default => "2️⃣ *Fechar toda a Sacolinha e Enviar* (já cotamos o frete)",
        };

        $isTest = ($overridePhone !== null) || $useTestPhone || (($config['mode'] ?? 'test') === 'test');
        $targetPhone = $overridePhone ?: ($isTest ? ($config['test_phone'] ?: '5548984136870') : ($user->whatsapp ?: ($user->phone ?: $user->telefone_principal)));
        $cleanPhone = EvolutionApiService::normalizeBrazilPhone((string) $targetPhone);

        if (strlen($cleanPhone) < 10) {
            return ['success' => false, 'error' => 'Cliente sem telefone WhatsApp válido (com DDD).'];
        }

        // Sensor de conversa: só coloca saudação curta se ainda NÃO conversamos hoje com esta cliente
        $state = $this->detectConversationState($user, $cleanPhone, $liveId, $isTest);
        $saudacao = $state['saudacao_horario'];

        $iconeTitulo = match ($flow) {
            'clube' => '👑',
            'pendencies' => '⚠️',
            default => '✨',
        };

        $cabecalho = "*Fechamento da live de hoje:* {$iconeTitulo}";
        if ($state['is_starting_conversation']) {
            if ($state['is_first_contact_ever'] || $state['is_first_purchase']) {
                $cabecalho = "Oii *{$primeiroNome}*, {$saudacao}! 💖 Aqui é a equipe da *Minha Mania Brechó*.\n\n*Fechamento da live de hoje:* {$iconeTitulo}";
            } else {
                $cabecalho = "Oii *{$primeiroNome}*, {$saudacao}! 💖\n\n*Fechamento da live de hoje:* {$iconeTitulo}";
            }
        }

        $text = "{$cabecalho}\n\n"
            . "{$resumoFinanceiroTxt}\n\n"
            . "Como você quer fazer agora?\n"
            . "{$opcao1}\n"
            . "{$opcao2}\n"
            . "3️⃣ *Cancelar algum item ou cancelar tudo*\n\n"
            . "Me diz aqui sua opção que eu já resolvo pra você na hora! 😊";

        // Salva o contexto da última live fechada para este telefone (marcando se é teste ou real)
        self::setTestContext($cleanPhone, $userId, $liveId, $isTest);

        // 1) Gera e envia o mesmo PDF padrão da sacolinha da live antes do texto de fechamento
        $pdfSent = false;
        $pdfUrl = null;
        $pdfError = null;
        try {
            $pdfInfo = app(LiveController::class)->gerarPdfSacolinhaLiveSalvar($userId, $liveId);
            $relativePath = ltrim((string) ($pdfInfo['path'] ?? ''), '/');
            $publicUrl = rtrim((string) config('app.url', 'https://minhamania.net'), '/') . '/storage/' . $relativePath;
            $pdfUrl = $publicUrl;

            // Atualiza tabela live_pdfs mantendo compatibilidade com o fluxo atual
            DB::table('live_pdfs')->updateOrInsert(
                ['live_id' => $liveId, 'user_id' => $userId],
                [
                    'pdf_path' => $relativePath,
                    'pdf_url' => $publicUrl,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $mediaPayload = $publicUrl;
            if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
                $binary = Storage::disk('public')->get($relativePath);
                if ($binary !== null && $binary !== '') {
                    $mediaPayload = base64_encode($binary);
                }
            }

            $fileSlug = Str::slug($primeiroNome ?: 'cliente', '_');
            $pdfFileName = "Sacolinha_Live_{$liveId}_{$fileSlug}.pdf";

            $pdfRes = $this->sendAndRecordSeverinoMedia(
                $cleanPhone,
                $user,
                $mediaPayload,
                $publicUrl,
                "📄 Fechamento da Live #{$liveId}",
                $pdfFileName,
                [
                    'event_type' => 'live_closing_pdf',
                    'live_id' => $liveId,
                    'flow' => $flow,
                    'is_test' => $isTest,
                    'pdf_only' => $pdfOnly,
                ],
                keepExistingMode: $pdfOnly,
                delay: 1400
            );
            $pdfSent = !empty($pdfRes['success']);
            if (!$pdfSent) {
                $pdfError = $pdfRes['error'] ?? 'Falha ao enviar PDF';
                Log::warning('Falha ao enviar PDF de fechamento via Evolution', [
                    'live_id' => $liveId,
                    'user_id' => $userId,
                    'error' => $pdfError,
                ]);
            }
        } catch (\Throwable $e) {
            $pdfError = $e->getMessage();
            Log::warning('Erro ao gerar/enviar PDF no fechamento Severino: ' . $e->getMessage(), [
                'live_id' => $liveId,
                'user_id' => $userId,
            ]);
        }

        // Modo "Apenas PDF": não envia o texto de finalização nem aciona a sequência do Severino
        if ($pdfOnly) {
            return [
                'success' => $pdfSent,
                'error' => $pdfSent ? null : ($pdfError ?: 'Falha ao enviar PDF de fechamento.'),
                'flow' => $flow,
                'flow_label' => $resolved['label'],
                'target_phone' => $cleanPhone,
                'client_name' => $user->name,
                'pdf_only' => true,
                'pdf_sent' => $pdfSent,
                'pdf_url' => $pdfUrl,
                'pdf_error' => $pdfError,
            ];
        }

        // 2) Envia a mensagem de fechamento financeiro + opções
        $res = $this->sendAndRecordSeverinoMessage($cleanPhone, $user, $text, [
            'event_type' => 'live_closing',
            'live_id' => $liveId,
            'flow' => $flow,
            'flow_label' => $resolved['label'],
            'is_test' => $isTest,
            'pdf_sent' => $pdfSent,
            'pdf_url' => $pdfUrl,
        ], keepExistingMode: $pdfSent, delay: $pdfSent ? 2200 : 1800);

        $res['flow'] = $flow;
        $res['flow_label'] = $resolved['label'];
        $res['target_phone'] = $cleanPhone;
        $res['client_name'] = $user->name;
        $res['pdf_only'] = false;
        $res['pdf_sent'] = $pdfSent;
        $res['pdf_url'] = $pdfUrl;
        $res['pdf_error'] = $pdfError;

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
        // Verifica se este telefone está em um contexto de teste (ex: 5548984136870 simulando uma cliente da live)
        $testCtx = self::getTestContextForPhone($remoteJid);
        $liveId = null;
        if ($testCtx && !empty($testCtx['user_id'])) {
            $userId = (int) $testCtx['user_id'];
            $liveId = !empty($testCtx['live_id']) ? (int) $testCtx['live_id'] : null;
        }

        // Regra oficial de atribuição:
        // Se a conversa está atribuída ao Severino -> pode responder!
        // Se está com humano ou sem atribuição (com Auto-Atribuir IA OFF) -> não responde!
        if (!$forceReplyEvenIfHumanMode && !self::canSeverinoReply($remoteJid, $userId)) {
            return ['handled' => false, 'reason' => 'not_assigned_to_severino'];
        }

        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            $digits = ltrim(preg_replace('/\D+/', '', explode('@', $remoteJid)[0] ?? ''), '0');
            if (strlen($digits) >= 8) {
                $without55 = (strlen($digits) >= 12 && str_starts_with($digits, '55')) ? substr($digits, 2) : $digits;
                $with55 = (strlen($without55) === 10 || strlen($without55) === 11) ? ('55' . $without55) : $digits;
                $candidates = array_values(array_unique(array_filter([$digits, $without55, $with55])));
                $user = User::whereIn('whatsapp', $candidates)
                    ->orWhereIn('phone', $candidates)
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
            ], keepExistingMode: true, delay: 900);
        }

        // Pós-fechamento: se acabou de gerar pagamento ou quitar por créditos, envia 1 gancho inteligente
        // (em balão separado para manter o canal aberto, fazer upsell ou coletar preferência/curadoria)
        $postClosingHookSent = null;
        $paymentAction = collect($agentResult['actions_taken'] ?? [])->first(function ($a) {
            return ($a['tool'] ?? '') === 'gerar_pagamento_pix_ou_cartao' && !empty($a['result']['success']);
        });

        if ($paymentAction) {
            $hookCacheKey = "severino_hook_sent_{$liveId}_{$user->id}";
            if (!Cache::has($hookCacheKey)) {
                $modalidadeEscolhida = (string) ($paymentAction['args']['modalidade'] ?? 'guardar_sacolinha');
                $hook = $this->resolvePostClosingHook($user, $liveId, $modalidadeEscolhida);
                if ($hook && !empty($hook['message'])) {
                    Cache::put($hookCacheKey, $hook['type'], now()->addHours(48));
                    $this->sendAndRecordSeverinoMessage($remoteJid, $user, $hook['message'], [
                        'event_type' => 'post_closing_hook',
                        'hook_type' => $hook['type'],
                        'hook_data' => $hook['data'] ?? [],
                        'live_id' => $liveId,
                    ], keepExistingMode: true, delay: 2600);
                    $postClosingHookSent = $hook;
                }
            }
        }

        return [
            'handled' => true,
            'reply' => $replyText,
            'pix_copia_e_cola' => $pixCodeToSendSeparately,
            'post_closing_hook' => $postClosingHookSent,
            'actions_taken' => $agentResult['actions_taken'] ?? [],
            'send_result' => $sendRes,
        ];
    }

    /**
     * Motor de Ganchos Pós-Fechamento (Ordem de Prioridade):
     * 1º Peça que escapou na fila da live (live_code_requests)
     * 2º Sobras da live no tamanho dela (se estiver fechando envio imediato)
     * 3º Curadoria / Extrair desejos de peças/marcas (client_wishes)
     * 4º Upsell Clube Mania (recorrente com 3+ compras e não assinante)
     * 5º Fallback: CEP faltando no cadastro
     */
    public function resolvePostClosingHook(User $user, int $liveId, string $modalidade = 'guardar_sacolinha'): ?array
    {
        $primeiroNome = explode(' ', trim($user->apelido ?: $user->name))[0] ?: 'Lindeza';

        // Itens que a cliente efetivamente ganhou nesta live
        $wonRows = DB::table('sacolinhas')
            ->join('items', 'sacolinhas.item_id', '=', 'items.id')
            ->where('sacolinhas.user_id', $user->id)
            ->where('sacolinhas.live_id', $liveId)
            ->select('sacolinhas.item_id', 'items.tamanho', 'items.codigo')
            ->get();

        $wonItemIds = $wonRows->pluck('item_id')->filter()->map(fn($v) => (int) $v)->unique()->values()->toArray();
        $wonSizes = $wonRows->pluck('tamanho')
            ->map(fn($s) => trim((string) $s))
            ->filter(fn($s) => $s !== '')
            ->unique()
            ->values()
            ->toArray();

        // 1º PRIORIDADE: Peça que escapou na fila da live (live_code_requests)
        if (DB::getSchemaBuilder()->hasTable('live_code_requests')) {
            $missedReq = DB::table('live_code_requests')
                ->leftJoin('items', 'live_code_requests.item_id', '=', 'items.id')
                ->where('live_code_requests.live_id', $liveId)
                ->where('live_code_requests.user_id', $user->id)
                ->when(!empty($wonItemIds), function ($q) use ($wonItemIds) {
                    $q->where(function ($sub) use ($wonItemIds) {
                        $sub->whereNull('live_code_requests.item_id')
                            ->orWhereNotIn('live_code_requests.item_id', $wonItemIds);
                    });
                })
                ->select(
                    'live_code_requests.codigo',
                    'live_code_requests.item_id',
                    'items.nome_do_produto',
                    'items.marca',
                    'items.tamanho'
                )
                ->orderByDesc('live_code_requests.id')
                ->first();

            if ($missedReq) {
                $cod = $missedReq->codigo ?: '?';
                $nome = trim((string) ($missedReq->nome_do_produto ?? ''));
                $marca = trim((string) ($missedReq->marca ?? ''));
                $tam = trim((string) ($missedReq->tamanho ?? ''));

                $det = array_filter([$marca ?: null, $tam ? "Tam {$tam}" : null]);
                $detStr = !empty($det) ? ' (' . implode(' • ', $det) . ')' : '';
                $pecaDesc = $nome !== '' ? "*[Cód {$cod}] {$nome}{$detStr}*" : "*Cód {$cod}*";

                return [
                    'type' => 'missed_item',
                    'data' => [
                        'codigo' => $cod,
                        'item_id' => $missedReq->item_id,
                        'nome' => $nome,
                        'marca' => $marca,
                        'tamanho' => $tam,
                    ],
                    'message' => "Vi que você também tentou o {$pecaDesc} hoje na live e escapou por segundos 😅\n\nQuer que eu deixe anotado no seu radar da Mania pra te avisar primeiro no WhatsApp quando aparecer algo parecido?",
                ];
            }
        }

        // 2º PRIORIDADE: Sobras da live no tamanho dela (quando escolheu envio imediato)
        if ($modalidade === 'enviar_agora' && !empty($wonSizes) && DB::getSchemaBuilder()->hasTable('live_items')) {
            $primarySize = $wonSizes[0];
            $leftovers = DB::table('live_items')
                ->join('items', 'live_items.item_id', '=', 'items.id')
                ->where('live_items.live_id', $liveId)
                ->where('items.status', 'estoque')
                ->where('items.tamanho', $primarySize)
                ->when(!empty($wonItemIds), fn($q) => $q->whereNotIn('items.id', $wonItemIds))
                ->select('items.id', 'items.nome_do_produto', 'items.marca', 'items.tamanho', 'items.preco', 'live_items.codigo_live', 'items.codigo')
                ->limit(3)
                ->get();

            if ($leftovers->isNotEmpty()) {
                return [
                    'type' => 'live_leftovers_upsell',
                    'data' => [
                        'size' => $primarySize,
                        'count' => $leftovers->count(),
                    ],
                    'message' => "Como já vamos montar sua caixinha, ainda cabe mais 1 ou 2 pecinhas sem aumentar o frete! 📦😉\n\nSobraram peças *Tam {$primarySize}* da live de hoje — quer que eu te mande rapidinho antes de fechar o pacote?",
                ];
            }
        }

        // 3º PRIORIDADE: Curadoria / Extrair desejos (client_wishes)
        $hasRecentWish = false;
        if (DB::getSchemaBuilder()->hasTable('client_wishes')) {
            $activeWishesCount = DB::table('client_wishes')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->count();
            $recentWish = DB::table('client_wishes')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subDays(15))
                ->exists();
            $hasRecentWish = $recentWish || $activeWishesCount >= 4;
        }

        if (!$hasRecentWish) {
            return [
                'type' => 'curadoria_wish',
                'data' => ['sizes_hint' => $wonSizes],
                'message' => "Me conta uma coisa, *{$primeiroNome}* 💖\nTem alguma peça, marca ou tamanho que você tá caçando pras próximas lives? Me fala que já deixo anotado na sua Curadoria pra te avisar quando chegar! ✨",
            ];
        }

        // 4º PRIORIDADE: Upsell Clube Mania (recorrente com 3+ pedidos aprovados e não assinante)
        $isClube = DB::table('clube_assinaturas')
            ->where('user_id', $user->id)
            ->where('status', 'ativa')
            ->exists();

        if (!$isClube) {
            $approvedOrders = DB::table('pedidos')
                ->where('user_id', $user->id)
                ->where('status_pagamento', 'aprovado')
                ->where('status_pedido', '!=', 'cancelado')
                ->count();

            if ($approvedOrders >= 3) {
                return [
                    'type' => 'clube_upsell',
                    'data' => ['approved_orders' => $approvedOrders],
                    'message' => "Aliás, vi que você já garimpa direto com a gente! 🥰\nVocê já conhece o *Clube Mania* (com frete fixo e vantagens VIP)? Se quiser, te explico em 2 linhas como funciona 😉",
                ];
            }
        }

        // 5º FALLBACK: Dado faltante relevante (CEP)
        $cleanCep = preg_replace('/\D+/', '', (string) ($user->cep ?? ''));
        if (strlen($cleanCep) !== 8) {
            return [
                'type' => 'missing_cep',
                'data' => [],
                'message' => "Só pra agilizar quando você quiser fechar sua caixinha: qual é o seu CEP? Já deixo salvo na sua ficha aqui! 📦✨",
            ];
        }

        return null;
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
            . "   - Se ela responder ao gancho dizendo que quer ser avisada sobre uma peça/marca/tamanho (ou confirmar interesse na peça que escapou na live), CHAME IMEDIATAMENTE `salvar_desejo_curadoria` para gravar na Curadoria dela!\n"
            . "   - Se ela disser que quer ver as peças que sobraram da live no tamanho dela, CHAME `buscar_sobras_live_tamanho` e apresente de forma curta e convidativa.\n"
            . "   - Se ela escolher uma dessas peças extras para colocar na sacolinha/pacote, CHAME `adicionar_peca_extra_sacolinha`.\n"
            . "   - Se ela informar o CEP apenas para atualizar cadastro (sem pedir frete na hora), CHAME `salvar_cep_cliente`.\n"
            . "   - Se ela perguntar sobre o *Clube Mania*, explique de forma breve e simpática (frete fixo, benefícios VIP e prioridade na loja) e ofereça transferir pra equipe se quiser assinar.\n"
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
                        'name' => 'salvar_desejo_curadoria',
                        'description' => 'Salva na Curadoria da Minha Mania (tabela client_wishes) uma peça, marca, categoria ou tamanho que a cliente deseja encontrar ou que escapou na live, para avisá-la quando chegar.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'descricao' => [
                                    'type' => 'STRING',
                                    'description' => 'Texto descrevendo o que a cliente procura (ex: Vestido Farm Tam M, Melissa 37 preta).',
                                ],
                                'categoria' => [
                                    'type' => 'STRING',
                                    'description' => 'Categoria principal se identificada (ex: Vestido, Calça, Melissa, Jaqueta).',
                                ],
                                'tamanho' => [
                                    'type' => 'STRING',
                                    'description' => 'Tamanho se informado (ex: P, M, G, 37, 38).',
                                ],
                                'marca' => [
                                    'type' => 'STRING',
                                    'description' => 'Marca se informada (ex: Farm, Melissa, Zara, Shein).',
                                ],
                                'preco_maximo' => [
                                    'type' => 'NUMBER',
                                    'description' => 'Preço máximo desejado se a cliente mencionou.',
                                ],
                            ],
                            'required' => ['descricao'],
                        ],
                    ],
                    [
                        'name' => 'buscar_sobras_live_tamanho',
                        'description' => 'Busca até 3 peças disponíveis em estoque que sobraram da live atual no tamanho solicitado pela cliente para upsell na mesma caixa/frete.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'tamanho' => [
                                    'type' => 'STRING',
                                    'description' => 'Tamanho desejado (ex: P, M, G, 37). Se vazio, usa o tamanho das peças que ela comprou na live.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'adicionar_peca_extra_sacolinha',
                        'description' => 'Adiciona na sacolinha da live atual uma peça disponível em estoque que a cliente escolheu após ver as sobras da live.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'item_id' => [
                                    'type' => 'INTEGER',
                                    'description' => 'ID do item retornado por buscar_sobras_live_tamanho.',
                                ],
                            ],
                            'required' => ['item_id'],
                        ],
                    ],
                    [
                        'name' => 'salvar_cep_cliente',
                        'description' => 'Salva o CEP de 8 dígitos informado pela cliente na ficha dela.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'cep' => [
                                    'type' => 'STRING',
                                    'description' => 'CEP informado (apenas números ou formato 00000-000).',
                                ],
                            ],
                            'required' => ['cep'],
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

            if ($fnName === 'salvar_desejo_curadoria') {
                $descricao = trim((string) ($args['descricao'] ?? ''));
                if ($descricao === '') {
                    return ['success' => false, 'message' => 'Informe a descrição da peça ou marca que a cliente procura.'];
                }

                $categoria = isset($args['categoria']) ? trim((string) $args['categoria']) : null;
                $tamanho = isset($args['tamanho']) ? trim((string) $args['tamanho']) : null;
                $marca = isset($args['marca']) ? trim((string) $args['marca']) : null;
                $precoMax = isset($args['preco_maximo']) && is_numeric($args['preco_maximo']) ? (float) $args['preco_maximo'] : null;

                $wish = ClientWish::create([
                    'user_id' => $user->id,
                    'raw_prompt' => $descricao,
                    'category' => $categoria ?: null,
                    'size' => $tamanho ?: null,
                    'max_price' => $precoMax,
                    'parsed_attributes' => array_filter([
                        'brand' => $marca ?: null,
                        'source' => 'whatsapp_severino_live',
                        'live_id' => $liveId,
                    ]),
                    'status' => 'active',
                ]);

                return [
                    'success' => true,
                    'wish_id' => $wish->id,
                    'message' => 'Desejo salvo com sucesso na Curadoria da cliente! Agradeça com carinho e diga que ela será avisada assim que chegar algo parecido.',
                ];
            }

            if ($fnName === 'buscar_sobras_live_tamanho') {
                $tamanho = trim((string) ($args['tamanho'] ?? ''));
                if ($tamanho === '') {
                    $tamanho = (string) (DB::table('sacolinhas')
                        ->join('items', 'sacolinhas.item_id', '=', 'items.id')
                        ->where('sacolinhas.user_id', $user->id)
                        ->where('sacolinhas.live_id', $liveId)
                        ->whereNotNull('items.tamanho')
                        ->where('items.tamanho', '!=', '')
                        ->value('items.tamanho') ?? '');
                }

                $items = collect();
                if (DB::getSchemaBuilder()->hasTable('live_items')) {
                    $q = DB::table('live_items')
                        ->join('items', 'live_items.item_id', '=', 'items.id')
                        ->where('live_items.live_id', $liveId)
                        ->where('items.status', 'estoque')
                        ->where(function ($b) {
                            $b->whereNull('items.brecho_id')->orWhere('items.brecho_id', 1);
                        });
                    if ($tamanho !== '') {
                        $q->where('items.tamanho', $tamanho);
                    }
                    $items = $q->select(
                            'items.id',
                            'items.nome_do_produto',
                            'items.marca',
                            'items.cor',
                            'items.tamanho',
                            'items.preco',
                            'items.codigo',
                            'live_items.codigo_live'
                        )
                        ->limit(3)
                        ->get();
                }

                if ($items->isEmpty()) {
                    return [
                        'success' => false,
                        'tamanho' => $tamanho,
                        'message' => 'Não há sobras disponíveis deste tamanho nesta live no momento. Ofereça anotar o desejo na Curadoria!',
                    ];
                }

                return [
                    'success' => true,
                    'tamanho' => $tamanho,
                    'pecas_disponiveis' => $items->map(fn($it) => [
                        'item_id' => (int) $it->id,
                        'codigo' => $it->codigo_live ?: $it->codigo,
                        'nome' => $it->nome_do_produto,
                        'marca' => $it->marca,
                        'cor' => $it->cor,
                        'tamanho' => $it->tamanho,
                        'preco' => (float) $it->preco,
                        'preco_formatado' => 'R$ ' . number_format((float) $it->preco, 2, ',', '.'),
                    ])->values()->toArray(),
                ];
            }

            if ($fnName === 'adicionar_peca_extra_sacolinha') {
                $itemId = (int) ($args['item_id'] ?? 0);
                if ($itemId <= 0) {
                    return ['success' => false, 'message' => 'Informe o item_id válido da peça.'];
                }

                $item = Item::where('id', $itemId)
                    ->where('status', 'estoque')
                    ->where(function ($b) {
                        $b->whereNull('brecho_id')->orWhere('brecho_id', 1);
                    })
                    ->first();

                if (!$item) {
                    return ['success' => false, 'message' => 'Ops, essa peça acabou de sair do estoque! Avise com jeitinho à cliente.'];
                }

                DB::transaction(function () use ($user, $item, $liveId) {
                    DB::table('sacolinhas')->insert([
                        'user_id' => $user->id,
                        'item_id' => $item->id,
                        'live_id' => $liveId,
                        'quantity' => 1,
                        'price' => (float) ($item->preco ?? 0),
                        'status' => 'aberta',
                        'add_at' => now(),
                        'obs' => 'Upsell pós-live via Severino WhatsApp',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $item->status = 'reservado';
                    $item->save();
                });

                $updatedCtx = $this->buildClientLiveContext($user, $liveId);
                return [
                    'success' => true,
                    'peca_adicionada' => $item->nome_do_produto,
                    'preco_formatado' => 'R$ ' . number_format((float) $item->preco, 2, ',', '.'),
                    'novo_total_pagar_live' => $updatedCtx['total_pagar_live'],
                    'message' => 'Peça adicionada com sucesso na sacolinha da cliente! Informe o novo valor e pergunte se já quer o Pix atualizado.',
                ];
            }

            if ($fnName === 'salvar_cep_cliente') {
                $cepRaw = preg_replace('/\D+/', '', (string) ($args['cep'] ?? ''));
                if (strlen($cepRaw) !== 8) {
                    return ['success' => false, 'message' => 'O CEP precisa ter 8 dígitos. Peça para ela conferir o número.'];
                }

                $user->cep = $cepRaw;
                $user->save();

                return [
                    'success' => true,
                    'cep' => $cepRaw,
                    'message' => 'CEP salvo na ficha da cliente! Agradeça com carinho.',
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

    // =========================================================================
    // FILA CADENCIADA DE FECHAMENTO DE FIM DE LIVE (5s a 55s + PDF + ANTI-BAN)
    // =========================================================================

    protected static function closingQueueCacheKey(int $liveId): string
    {
        return "severino_closing_queue_live_{$liveId}";
    }

    /**
     * Inicia a fila cadenciada de fechamento para todas as sacolas abertas da live,
     * priorizando conversas quentes e respeitando intervalo aleatório humano (5 a 55s).
     */
    public function startClosingQueue(int $liveId, array $options = []): array
    {
        $live = DB::table('lives')->where('id', $liveId)->first();
        if (!$live) {
            return ['success' => false, 'error' => 'Live não encontrada.'];
        }
        if (!empty($live->brecho_id) && (int) $live->brecho_id > 1) {
            return ['success' => false, 'error' => 'Recurso exclusivo da Minha Mania (Matriz).'];
        }

        $config = self::getConfig();
        $useTestPhone = array_key_exists('use_test_phone', $options)
            ? (bool) $options['use_test_phone']
            : (($config['mode'] ?? 'test') !== 'live');
        $targetPhone = !empty($options['target_phone'])
            ? preg_replace('/\D+/', '', (string) $options['target_phone'])
            : preg_replace('/\D+/', '', (string) ($config['test_phone'] ?: '5548984136870'));

        $minDelay = max(5, min(55, (int) ($options['min_delay'] ?? 5)));
        $maxDelay = max($minDelay, min(120, (int) ($options['max_delay'] ?? 55)));
        $limitTest = !empty($options['limit_test']) ? max(1, (int) $options['limit_test']) : null;
        $pdfOnly = array_key_exists('pdf_only', $options) ? (bool) $options['pdf_only'] : true;

        // Busca clientes da live com itens em sacolinha (em modo teste, faz fallback para todos da live caso já encerrada)
        $userIds = DB::table('sacolinhas')
            ->where('live_id', $liveId)
            ->where('status', '!=', 'pedido')
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->values()
            ->toArray();

        if (empty($userIds) && $useTestPhone) {
            $userIds = DB::table('sacolinhas')
                ->where('live_id', $liveId)
                ->distinct()
                ->pluck('user_id')
                ->filter()
                ->values()
                ->toArray();
        }

        if (empty($userIds)) {
            return ['success' => false, 'error' => 'Nenhuma sacola aberta encontrada nesta live para fechamento.'];
        }

        // Busca clientes que responderam no WhatsApp nas últimas 24h (Prioridade 1 - Conversa Quente)
        $hotUserIds = EvolutionMessage::whereIn('user_id', $userIds)
            ->where('direction', 'inbound')
            ->where('created_at', '>=', now()->subHours(24))
            ->distinct()
            ->pluck('user_id')
            ->flip()
            ->toArray();

        // Clientes que receberam mensagem hoje nesta live (Prioridade 2)
        $contactedTodayIds = EvolutionMessage::whereIn('user_id', $userIds)
            ->where('direction', 'outbound')
            ->where('status', '!=', 'failed')
            ->where('created_at', '>=', now()->subHours(12))
            ->distinct()
            ->pluck('user_id')
            ->flip()
            ->toArray();

        // Clientes recorrentes (têm pedido aprovado anterior)
        $returningUserIds = DB::table('pedidos')
            ->whereIn('user_id', $userIds)
            ->where('status_pagamento', 'aprovado')
            ->where('status_pedido', '!=', 'cancelado')
            ->distinct()
            ->pluck('user_id')
            ->flip()
            ->toArray();

        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $queueItems = [];
        foreach ($userIds as $uid) {
            $u = $users->get($uid);
            if (!$u) {
                continue;
            }

            $realPhone = EvolutionApiService::normalizeBrazilPhone((string) ($u->whatsapp ?: ($u->phone ?: $u->telefone_principal)));

            // Calcula prioridade anti-bloqueio (1 = melhor reputação/conversa quente ... 4 = 1º contato)
            if (isset($hotUserIds[$uid])) {
                $priority = 1;
                $priorityLabel = '🔥 Conversa quente';
            } elseif (isset($contactedTodayIds[$uid]) && isset($returningUserIds[$uid])) {
                $priority = 2;
                $priorityLabel = '💬 Ativa hoje + Recorrente';
            } elseif (isset($returningUserIds[$uid])) {
                $priority = 3;
                $priorityLabel = '⭐ Cliente recorrente';
            } else {
                $priority = 4;
                $priorityLabel = '🆕 1º contato / 1ª compra';
            }

            $hasValidPhone = $useTestPhone ? (strlen($targetPhone) >= 10) : (strlen($realPhone) >= 10);

            $queueItems[] = [
                'user_id' => (int) $uid,
                'name' => $u->name,
                'phone' => $useTestPhone ? $targetPhone : $realPhone,
                'priority' => $priority,
                'priority_label' => $priorityLabel,
                'status' => $hasValidPhone ? 'pending' : 'skipped',
                'error' => $hasValidPhone ? null : 'Sem telefone WhatsApp cadastrado',
                'pdf_sent' => false,
                'sent_at' => null,
            ];
        }

        // Ordena por prioridade anti-bloqueio (1 -> 4)
        usort($queueItems, fn($a, $b) => $a['priority'] <=> $b['priority']);

        if ($useTestPhone && $limitTest !== null && count($queueItems) > $limitTest) {
            $queueItems = array_slice($queueItems, 0, $limitTest);
        }

        $state = [
            'live_id' => $liveId,
            'status' => 'running',
            'mode' => $useTestPhone ? 'test' : 'live',
            'pdf_only' => $pdfOnly,
            'test_phone' => $useTestPhone ? $targetPhone : null,
            'min_delay' => $minDelay,
            'max_delay' => $maxDelay,
            'started_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'finished_at' => null,
            'next_send_at' => now()->toIso8601String(),
            'last_delay_seconds' => 0,
            'current_client' => null,
            'queue' => $queueItems,
        ];

        Cache::put(self::closingQueueCacheKey($liveId), $state, now()->addHours(48));

        // Dispara o primeiro item imediatamente na fila whatsapp
        ProcessSeverinoLiveClosingQueueJob::dispatch($liveId)->onQueue('whatsapp');

        return [
            'success' => true,
            'state' => self::summarizeClosingQueueState($state),
        ];
    }

    public static function getClosingQueueStatus(int $liveId): array
    {
        $state = Cache::get(self::closingQueueCacheKey($liveId));
        if (!$state) {
            return [
                'active' => false,
                'status' => 'idle',
                'live_id' => $liveId,
            ];
        }
        return self::summarizeClosingQueueState($state);
    }

    public static function pauseClosingQueue(int $liveId): array
    {
        $key = self::closingQueueCacheKey($liveId);
        $state = Cache::get($key);
        if (!$state) {
            return ['success' => false, 'error' => 'Nenhuma fila encontrada para esta live.'];
        }
        if ($state['status'] === 'running') {
            $state['status'] = 'paused';
            $state['next_send_at'] = null;
            $state['updated_at'] = now()->toIso8601String();
            Cache::put($key, $state, now()->addHours(48));
        }
        return ['success' => true, 'state' => self::summarizeClosingQueueState($state)];
    }

    public static function resumeClosingQueue(int $liveId): array
    {
        $key = self::closingQueueCacheKey($liveId);
        $state = Cache::get($key);
        if (!$state) {
            return ['success' => false, 'error' => 'Nenhuma fila encontrada para esta live.'];
        }
        if (in_array($state['status'], ['paused'], true)) {
            $state['status'] = 'running';
            $state['next_send_at'] = now()->toIso8601String();
            $state['updated_at'] = now()->toIso8601String();
            Cache::put($key, $state, now()->addHours(48));
            ProcessSeverinoLiveClosingQueueJob::dispatch($liveId)->onQueue('whatsapp');
        }
        return ['success' => true, 'state' => self::summarizeClosingQueueState($state)];
    }

    public static function cancelClosingQueue(int $liveId): array
    {
        $key = self::closingQueueCacheKey($liveId);
        $state = Cache::get($key);
        if (!$state) {
            return ['success' => false, 'error' => 'Nenhuma fila encontrada para esta live.'];
        }
        $state['status'] = 'cancelled';
        $state['next_send_at'] = null;
        $state['finished_at'] = now()->toIso8601String();
        $state['updated_at'] = now()->toIso8601String();
        Cache::put($key, $state, now()->addHours(48));
        return ['success' => true, 'state' => self::summarizeClosingQueueState($state)];
    }

    /**
     * Processa 1 cliente pendente da fila cadenciada e agenda a próxima com intervalo aleatório (5 a 55s)
     */
    public function processNextInClosingQueue(int $liveId): void
    {
        $lock = Cache::lock("severino_closing_lock_{$liveId}", 90);
        if (!$lock->get()) {
            return;
        }

        try {
            $key = self::closingQueueCacheKey($liveId);
            $state = Cache::get($key);
            if (!$state || ($state['status'] ?? '') !== 'running') {
                return;
            }

            $nextIndex = null;
            foreach ($state['queue'] as $idx => $entry) {
                if (($entry['status'] ?? '') === 'pending') {
                    $nextIndex = $idx;
                    break;
                }
            }

            if ($nextIndex === null) {
                $state['status'] = 'completed';
                $state['current_client'] = null;
                $state['next_send_at'] = null;
                $state['finished_at'] = now()->toIso8601String();
                $state['updated_at'] = now()->toIso8601String();
                Cache::put($key, $state, now()->addHours(48));
                return;
            }

            $entry = $state['queue'][$nextIndex];
            $state['current_client'] = $entry['name'];
            $state['updated_at'] = now()->toIso8601String();
            Cache::put($key, $state, now()->addHours(48));

            $useTestPhone = ($state['mode'] ?? 'test') === 'test';
            $overridePhone = $useTestPhone ? ($state['test_phone'] ?? null) : null;
            $pdfOnly = !empty($state['pdf_only']);

            $res = $this->sendLiveClosingMessage(
                (int) $entry['user_id'],
                $liveId,
                $overridePhone,
                $useTestPhone,
                $pdfOnly
            );

            // Recarrega o estado caso o operador tenha clicado em Pausar/Cancelar durante o envio
            $state = Cache::get($key) ?: $state;
            $state['queue'][$nextIndex]['status'] = !empty($res['success']) ? 'sent' : 'failed';
            $state['queue'][$nextIndex]['pdf_sent'] = !empty($res['pdf_sent']);
            $state['queue'][$nextIndex]['error'] = !empty($res['success']) ? null : ($res['error'] ?? 'Falha no envio');
            $state['queue'][$nextIndex]['sent_at'] = now()->format('H:i:s');
            $state['current_client'] = null;
            $state['updated_at'] = now()->toIso8601String();

            // Verifica se ainda há pendentes
            $hasMorePending = collect($state['queue'])->contains(fn($e) => ($e['status'] ?? '') === 'pending');

            if (!$hasMorePending) {
                $state['status'] = 'completed';
                $state['next_send_at'] = null;
                $state['finished_at'] = now()->toIso8601String();
                Cache::put($key, $state, now()->addHours(48));
                return;
            }

            if (($state['status'] ?? '') === 'running') {
                $minDelay = max(5, (int) ($state['min_delay'] ?? 5));
                $maxDelay = max($minDelay, (int) ($state['max_delay'] ?? 55));
                $delaySeconds = random_int($minDelay, $maxDelay);

                $state['last_delay_seconds'] = $delaySeconds;
                $state['next_send_at'] = now()->addSeconds($delaySeconds)->toIso8601String();
                Cache::put($key, $state, now()->addHours(48));

                ProcessSeverinoLiveClosingQueueJob::dispatch($liveId)
                    ->onQueue('whatsapp')
                    ->delay(now()->addSeconds($delaySeconds));
            } else {
                Cache::put($key, $state, now()->addHours(48));
            }
        } finally {
            optional($lock)->release();
        }
    }

    protected static function summarizeClosingQueueState(array $state): array
    {
        $queue = $state['queue'] ?? [];
        $total = count($queue);
        $sent = count(array_filter($queue, fn($e) => ($e['status'] ?? '') === 'sent'));
        $failed = count(array_filter($queue, fn($e) => ($e['status'] ?? '') === 'failed'));
        $skipped = count(array_filter($queue, fn($e) => ($e['status'] ?? '') === 'skipped'));
        $pending = count(array_filter($queue, fn($e) => ($e['status'] ?? '') === 'pending'));
        $pdfsSent = count(array_filter($queue, fn($e) => !empty($e['pdf_sent'])));

        $nextInSeconds = null;
        if (($state['status'] ?? '') === 'running' && !empty($state['next_send_at'])) {
            $diff = Carbon::parse($state['next_send_at'])->diffInSeconds(now(), false);
            $nextInSeconds = max(0, -$diff);
        }

        return [
            'active' => in_array($state['status'] ?? '', ['running', 'paused'], true),
            'live_id' => $state['live_id'] ?? null,
            'status' => $state['status'] ?? 'idle',
            'mode' => $state['mode'] ?? 'test',
            'pdf_only' => !empty($state['pdf_only']),
            'test_phone' => $state['test_phone'] ?? null,
            'min_delay' => $state['min_delay'] ?? 5,
            'max_delay' => $state['max_delay'] ?? 55,
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'pending' => $pending,
            'pdfs_sent' => $pdfsSent,
            'progress_percent' => $total > 0 ? (int) round((($sent + $failed + $skipped) / $total) * 100) : 0,
            'current_client' => $state['current_client'] ?? null,
            'next_in_seconds' => $nextInSeconds,
            'last_delay_seconds' => $state['last_delay_seconds'] ?? null,
            'started_at' => $state['started_at'] ?? null,
            'finished_at' => $state['finished_at'] ?? null,
            'items' => array_slice($queue, 0, 100),
        ];
    }

    /**
     * Envia mídia (ex: PDF da live) via EvolutionApiService e registra em evolution_messages como enviada pelo Severino
     */
    protected function sendAndRecordSeverinoMedia(
        string $toPhoneOrJid,
        User $user,
        string $mediaUrlOrBase64,
        ?string $publicMediaUrl,
        string $caption,
        string $fileName,
        array $meta = [],
        bool $keepExistingMode = false,
        int $delay = 1400
    ): array {
        $res = $this->evolution->sendMedia($toPhoneOrJid, $mediaUrlOrBase64, $caption, 'document', $fileName, $delay);
        $status = ($res['success'] ?? false) ? 'sent' : 'failed';
        $messageId = $res['message_id'] ?? null;
        $canonicalJid = $res['remote_jid'] ?? (str_contains($toPhoneOrJid, '@') ? $toPhoneOrJid : (preg_replace('/\D+/', '', $toPhoneOrJid) . '@s.whatsapp.net'));
        $digits = preg_replace('/\D+/', '', explode('@', $canonicalJid)[0] ?? '');

        if (!$keepExistingMode && !empty(self::getConfig()['auto_reply'])) {
            $curAssign = self::getConversationAssignment($canonicalJid, $user->id);
            if ($curAssign['assigned_type'] === 'none') {
                self::assignConversation($canonicalJid, $user->id, 'severino', null, null);
            }
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
            'body' => $caption,
            'media_type' => 'document',
            'media_url' => $publicMediaUrl,
            'failed_reason' => ($status === 'failed') ? ($res['error'] ?? 'Falha no envio do PDF') : null,
            'raw_payload' => array_merge($res, [
                'sender_type' => 'severino',
                'file_name' => $fileName,
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

        if (!$keepExistingMode && !empty(self::getConfig()['auto_reply'])) {
            $curAssign = self::getConversationAssignment($canonicalJid, $user->id);
            if ($curAssign['assigned_type'] === 'none') {
                self::assignConversation($canonicalJid, $user->id, 'severino', null, null);
            }
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
