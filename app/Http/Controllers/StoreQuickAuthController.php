<?php

namespace App\Http\Controllers;

use App\Http\Middleware\TrackLastVisitedUrl;
use App\Models\User;
use App\Services\Whatsapp\EvolutionApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreQuickAuthController extends Controller
{
    /**
     * Solicita código OTP de 6 dígitos via WhatsApp para entrar/cadastrar cliente na loja.
     */
    public function requestWhatsAppCode(Request $request)
    {
        $validated = $request->validate([
            'whatsapp' => ['required', 'string', 'min:10', 'max:25'],
            'name'     => ['nullable', 'string', 'min:2', 'max:120'],
            'email'    => ['nullable', 'email', 'max:255'],
        ], [
            'whatsapp.required' => 'Informe seu número de WhatsApp com DDD.',
            'whatsapp.min'      => 'Informe o WhatsApp completo com DDD (ex: 48 99999-9999).',
            'name.min'          => 'Informe seu nome completo.',
            'email.email'       => 'Informe um e-mail válido.',
        ]);

        $normalized = EvolutionApiService::normalizeBrazilPhone($validated['whatsapp']);
        if (strlen($normalized) < 12 || strlen($normalized) > 13) {
            return response()->json([
                'success' => false,
                'message' => 'Número inválido. Digite DDD + número (ex: 48 99999-9999).',
            ], 422);
        }

        // Busca cliente existente pelo telefone (apenas clientes; nunca admins/operadores)
        $existingUser = $this->findClientByPhone($normalized);

        if ($existingUser && !empty($existingUser->bloqueado)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta conta está temporariamente bloqueada. Fale com nossa equipe no WhatsApp.',
            ], 403);
        }

        // Se é cliente nova e ainda não informou o nome, solicita nome antes de enviar código
        $providedName = trim((string) ($validated['name'] ?? ''));
        if (!$existingUser && $providedName === '') {
            return response()->json([
                'success'    => true,
                'needs_name' => true,
                'is_new'     => true,
                'message'    => 'Primeira vez por aqui? Informe seu nome para criarmos sua sacolinha!',
            ]);
        }

        // Se e-mail foi informado para novo cadastro, garante que não pertence a outra conta
        $providedEmail = trim((string) ($validated['email'] ?? ''));
        if (!$existingUser && $providedEmail !== '') {
            $emailExists = User::where('email', $providedEmail)->exists();
            if ($emailExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este e-mail já está em uso. Tente entrar usando a aba E-mail e Senha.',
                ], 422);
            }
        }

        // Rate limit suave por número (evita spam de reenvio em menos de 25s)
        $cooldownKey = 'store_otp_cooldown_' . $normalized;
        if (Cache::has($cooldownKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Aguarde alguns segundos antes de solicitar um novo código.',
            ], 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $firstName = $existingUser
            ? (trim(explode(' ', trim($existingUser->apelido ?: $existingUser->name))[0] ?? '') ?: 'cliente')
            : (trim(explode(' ', $providedName)[0] ?? '') ?: 'cliente');

        $message = "🛍️ *Minha Mania Brechó*\n\n"
            . "Olá, {$firstName}! Seu código de acesso rápido é:\n\n"
            . "*{$code}*\n\n"
            . "Digite este código no site para entrar na sua sacolinha. _(Válido por 10 minutos)_";

        $sent = false;
        $sendError = null;
        try {
            $evolution = app(EvolutionApiService::class);
            $res = $evolution->sendText($normalized, $message, 500);
            $sent = !empty($res['success']);
            $sendError = $res['error'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('[StoreQuickAuth] Falha ao enviar OTP via Evolution API', [
                'phone' => $normalized,
                'error' => $e->getMessage(),
            ]);
            $sendError = $e->getMessage();
        }

        if (!$sent) {
            Log::warning('[StoreQuickAuth] Evolution API indisponível para OTP', [
                'phone' => $normalized,
                'error' => $sendError,
            ]);

            return response()->json([
                'success'          => false,
                'fallback_email'   => true,
                'message'          => 'Não conseguimos enviar o código pelo WhatsApp neste momento. Use a opção "E-mail e Senha" ou "Criar Conta" abaixo.',
            ], 503);
        }

        Cache::put('store_otp_' . $normalized, [
            'code_hash' => Hash::make($code),
            'user_id'   => $existingUser?->id,
            'name'      => $existingUser ? $existingUser->name : $providedName,
            'email'     => $existingUser ? $existingUser->email : ($providedEmail ?: null),
            'attempts'  => 0,
        ], now()->addMinutes(10));

        Cache::put($cooldownKey, true, now()->addSeconds(25));

        return response()->json([
            'success'    => true,
            'needs_name' => false,
            'is_new'     => !$existingUser,
            'first_name' => $firstName,
            'phone_mask' => $this->maskPhone($normalized),
            'message'    => "Enviamos um código de 6 dígitos para o seu WhatsApp ({$this->maskPhone($normalized)}).",
        ]);
    }

    /**
     * Valida o código OTP, cria o cliente (se novo) e autentica com sessão persistente.
     */
    public function verifyWhatsAppCode(Request $request)
    {
        $validated = $request->validate([
            'whatsapp'    => ['required', 'string', 'min:10', 'max:25'],
            'code'        => ['required', 'string', 'size:6'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ], [
            'code.required' => 'Digite o código de 6 dígitos enviado no WhatsApp.',
            'code.size'     => 'O código deve ter exatamente 6 números.',
        ]);

        $normalized = EvolutionApiService::normalizeBrazilPhone($validated['whatsapp']);
        $cacheKey = 'store_otp_' . $normalized;
        $payload = Cache::get($cacheKey);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Código expirado ou não encontrado. Solicite um novo código.',
            ], 422);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= 5) {
            Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Muitas tentativas incorretas. Solicite um novo código.',
            ], 429);
        }

        $cleanCode = preg_replace('/\D+/', '', $validated['code']);
        if (!Hash::check($cleanCode, $payload['code_hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($cacheKey, $payload, now()->addMinutes(10));

            return response()->json([
                'success' => false,
                'message' => 'Código incorreto. Confira a mensagem no seu WhatsApp e tente novamente.',
            ], 422);
        }

        Cache::forget($cacheKey);

        $user = null;
        if (!empty($payload['user_id'])) {
            $user = User::where('id', $payload['user_id'])
                ->where('role', 'client')
                ->where(function ($q) {
                    $q->whereNull('bloqueado')->orWhere('bloqueado', 0);
                })
                ->first();
        }

        if (!$user) {
            // Re-checa por telefone caso tenha sido cadastrado enquanto aguardava
            $user = $this->findClientByPhone($normalized);
        }

        if (!$user) {
            $name = trim((string) ($payload['name'] ?? 'Cliente Minha Mania'));
            $email = $payload['email'] ?: ("cliente_{$normalized}@minhamania.local");
            if (User::where('email', $email)->exists()) {
                $email = "cliente_{$normalized}_" . Str::lower(Str::random(4)) . '@minhamania.local';
            }

            $user = User::create([
                'brecho_id'     => 1,
                'name'          => $name,
                'email'         => $email,
                'password'      => Hash::make(Str::random(24)),
                'role'          => 'client',
                'phone'         => $normalized,
                'whatsapp'      => $normalized,
                'bloqueado'     => 0,
            ]);
        } else {
            // Garante que o campo whatsapp esteja preenchido no cadastro
            if (empty($user->whatsapp)) {
                $user->whatsapp = $normalized;
                $user->save();
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $redirectUrl = route('loja.index');
        if (!empty($validated['redirect_to']) && TrackLastVisitedUrl::isNavigableUrl($validated['redirect_to'], $request)) {
            $redirectUrl = TrackLastVisitedUrl::normalizeRelativeUrl($validated['redirect_to'], $request);
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Acesso confirmado! Bem-vinda(o), ' . explode(' ', trim($user->name))[0] . '!',
            'csrf_token'   => csrf_token(),
            'redirect_url' => $redirectUrl,
            'user'         => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Login rápido via e-mail e senha em JSON (para o modal da loja sem recarregar a página).
     */
    public function ajaxEmailLogin(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Informe seu e-mail.',
            'email.email'       => 'Informe um e-mail válido.',
            'password.required' => 'Informe sua senha.',
        ]);

        if (!Auth::attempt(array_merge($credentials, ['bloqueado' => 0]), true)) {
            return response()->json([
                'success' => false,
                'message' => 'E-mail ou senha incorretos. Se não lembra a senha, entre usando seu WhatsApp!',
            ], 422);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        return response()->json([
            'success'    => true,
            'message'    => 'Login realizado com sucesso!',
            'csrf_token' => csrf_token(),
            'user'       => [
                'id'   => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Exibe formulário moderno de criação de conta de cliente (substitui /register quebrado).
     */
    public function showRegisterForm(Request $request)
    {
        if (auth()->check()) {
            return redirect()->route('loja.index');
        }

        $redirectTo = $request->query('redirect_to');
        return view('auth.register', compact('redirectTo'));
    }

    /**
     * Cria nova conta de cliente de forma rápida (Nome + WhatsApp + E-mail + Senha).
     */
    public function registerClient(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'min:2', 'max:255'],
            'whatsapp'    => ['required', 'string', 'min:10', 'max:25'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', 'string', 'min:6', 'confirmed'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required'      => 'Informe seu nome completo.',
            'whatsapp.required'  => 'Informe seu WhatsApp com DDD.',
            'whatsapp.min'       => 'Informe seu WhatsApp completo com DDD.',
            'email.required'     => 'Informe seu e-mail.',
            'email.email'        => 'Informe um e-mail válido.',
            'email.unique'       => 'Este e-mail já está cadastrado. Tente entrar com sua conta ou pelo WhatsApp.',
            'password.required'  => 'Crie uma senha de acesso.',
            'password.min'       => 'A senha deve ter no mínimo 6 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ]);

        $normalized = EvolutionApiService::normalizeBrazilPhone($validated['whatsapp']);

        $user = User::create([
            'brecho_id' => 1,
            'name'      => trim($validated['name']),
            'email'     => strtolower(trim($validated['email'])),
            'phone'     => $normalized ?: trim($validated['whatsapp']),
            'whatsapp'  => $normalized ?: trim($validated['whatsapp']),
            'role'      => 'client',
            'bloqueado' => 0,
            'password'  => Hash::make($validated['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        $redirectUrl = route('loja.index');
        if (!empty($validated['redirect_to']) && TrackLastVisitedUrl::isNavigableUrl($validated['redirect_to'], $request)) {
            $redirectUrl = TrackLastVisitedUrl::normalizeRelativeUrl($validated['redirect_to'], $request);
        }

        return redirect($redirectUrl)->with('success', 'Conta criada com sucesso! Boas compras na Minha Mania!');
    }

    /**
     * Localiza cliente por telefone tolerando formatos com/sem DDI 55 e com/sem 9º dígito.
     */
    protected function findClientByPhone(string $normalized): ?User
    {
        $withoutDdi = str_starts_with($normalized, '55') ? substr($normalized, 2) : $normalized;
        $ddd = substr($withoutDdi, 0, 2);
        $local = substr($withoutDdi, 2);

        $candidates = array_unique(array_filter([
            $normalized,
            $withoutDdi,
            // Variante com/sem nono dígito
            strlen($local) === 9 && str_starts_with($local, '9') ? ('55' . $ddd . substr($local, 1)) : null,
            strlen($local) === 9 && str_starts_with($local, '9') ? ($ddd . substr($local, 1)) : null,
            strlen($local) === 8 ? ('55' . $ddd . '9' . $local) : null,
            strlen($local) === 8 ? ($ddd . '9' . $local) : null,
        ]));

        // Busca apenas clientes ativos da matriz (ou sem brecho_id)
        $matches = User::where('role', 'client')
            ->where(function ($q) {
                $q->whereNull('brecho_id')->orWhere('brecho_id', 1);
            })
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $cand) {
                    $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(whatsapp, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$cand])
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(phone, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$cand])
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telefone_principal, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$cand]);
                }
            })
            ->orderByDesc('id')
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        // Prioriza conta não bloqueada
        return $matches->firstWhere('bloqueado', 0) ?? $matches->first();
    }

    protected function maskPhone(string $normalized): string
    {
        $clean = str_starts_with($normalized, '55') ? substr($normalized, 2) : $normalized;
        if (strlen($clean) >= 10) {
            $ddd = substr($clean, 0, 2);
            $last4 = substr($clean, -4);
            return "({$ddd}) *****-{$last4}";
        }
        return $normalized;
    }
}
