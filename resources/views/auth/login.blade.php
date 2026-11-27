<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar - Minha Mania Brechó</title>

    <!-- PWA Settings & Web App Manifest -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Minha Mania">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @include('partials.site-tracking')
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col justify-between antialiased">

    <main class="flex-grow flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-6">
                <a href="{{ route('loja.index') }}" class="inline-flex items-center justify-center gap-2.5">
                    <img src="{{ asset('favicon-32x32.png') }}" alt="Ícone Minha Mania" class="w-9 h-9 object-contain">
                    <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-11 max-w-[210px] object-contain">
                </a>
                <p class="mt-2 text-sm text-gray-600 font-medium">Acesse sua sacolinha, pedidos e loja online</p>
            </div>

            <!-- Card principal -->
            <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/70 border border-gray-200 overflow-hidden">
                @php
                    $defaultEmailTab = $errors->any() || old('email') || request('tab') === 'email';
                @endphp

                <!-- Abas -->
                <div class="grid grid-cols-2 border-b border-gray-200 bg-gray-100 p-1.5 gap-1.5">
                    <button type="button" id="tabBtnWhatsapp"
                        class="py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 {{ !$defaultEmailTab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        <i class="fab fa-whatsapp text-emerald-600 text-base"></i>
                        <span>Entrar c/ WhatsApp</span>
                    </button>
                    <button type="button" id="tabBtnEmail"
                        class="py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 {{ $defaultEmailTab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                        <i class="fas fa-envelope text-pink-600 text-sm"></i>
                        <span>E-mail e Senha</span>
                    </button>
                </div>

                <div class="p-6 sm:p-7">
                    @if (session('error'))
                        <div class="mb-4 rounded-xl bg-amber-50 border border-amber-300 px-4 py-3 text-sm text-amber-900 font-medium">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-300 px-4 py-3 text-sm text-emerald-900 font-medium">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 rounded-xl bg-red-50 border border-red-300 px-4 py-3 text-sm text-red-900">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- ABA 1: WHATSAPP SEM SENHA -->
                    <div id="panelWhatsapp" class="{{ $defaultEmailTab ? 'hidden' : '' }}">
                        <div class="mb-4">
                            <h1 class="text-lg font-extrabold text-gray-900">Acesso rápido sem senha</h1>
                            <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                                Digite seu WhatsApp com DDD. Enviamos um código de 6 dígitos na hora — ideal para quem compra nas lives!
                            </p>
                        </div>

                        <div id="waAlert" class="hidden mb-4 rounded-xl px-4 py-3 text-xs font-semibold"></div>

                        <!-- Etapa 1: Informar telefone (e nome se cliente nova) -->
                        <form id="waRequestForm" class="space-y-4">
                            <div>
                                <label for="waPhone" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">
                                    Seu WhatsApp com DDD
                                </label>
                                <input type="tel" id="waPhone" name="whatsapp" autocomplete="tel" required
                                       placeholder="(48) 99999-9999"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-base font-semibold text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                            </div>

                            <div id="waNewClientFields" class="hidden space-y-3 rounded-2xl bg-pink-50 border border-pink-200 p-4">
                                <p class="text-xs font-bold text-pink-900">
                                    <i class="fas fa-sparkles mr-1"></i> Primeira vez por aqui! Informe seu nome para criar sua sacolinha:
                                </p>
                                <div>
                                    <label for="waName" class="block text-xs font-bold text-gray-800 mb-1">Nome completo *</label>
                                    <input type="text" id="waName" name="name" placeholder="Ex: Maria Oliveira"
                                           class="w-full rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                                </div>
                            </div>

                            <button type="submit" id="waRequestBtn"
                                    class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm py-3.5 px-4 shadow-sm transition active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fab fa-whatsapp text-base"></i>
                                <span>Receber código no WhatsApp</span>
                            </button>
                        </form>

                        <!-- Etapa 2: Digitar código de 6 dígitos -->
                        <form id="waVerifyForm" class="hidden space-y-4">
                            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-3.5 text-xs text-emerald-900 font-medium">
                                <span id="waSentText">Código enviado no seu WhatsApp!</span>
                                <button type="button" id="waChangePhoneBtn" class="block mt-1.5 text-emerald-800 underline font-bold">
                                    Trocar número
                                </button>
                            </div>

                            <div>
                                <label for="waCode" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">
                                    Código de 6 dígitos
                                </label>
                                <input type="text" id="waCode" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required
                                       placeholder="000000"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3.5 text-center text-2xl font-extrabold tracking-[0.4em] text-gray-900 placeholder:text-gray-300 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                            </div>

                            <button type="submit" id="waVerifyBtn"
                                    class="w-full rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-extrabold text-sm py-3.5 px-4 shadow-sm transition active:scale-[0.99] cursor-pointer">
                                Confirmar e Entrar
                            </button>
                        </form>
                    </div>

                    <!-- ABA 2: E-MAIL E SENHA -->
                    <div id="panelEmail" class="{{ $defaultEmailTab ? '' : 'hidden' }}">
                        <div class="mb-4">
                            <h2 class="text-lg font-extrabold text-gray-900">Entrar com e-mail e senha</h2>
                            <p class="text-xs text-gray-600 mt-1">Para clientes com senha cadastrada ou acesso da equipe.</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="redirect_to" id="redirect_to" value="{{ old('redirect_to', $redirectTo ?? request('redirect_to')) }}">

                            <div>
                                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">E-mail</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                                       placeholder="seuemail@exemplo.com"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gray-700">Senha</label>
                                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-pink-700 hover:text-pink-800 hover:underline">
                                        Esqueci minha senha
                                    </a>
                                </div>
                                <input type="password" id="password" name="password" required autocomplete="current-password"
                                       placeholder="••••••••"
                                       class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                            </div>

                            <button type="submit"
                                    class="w-full rounded-xl bg-gray-900 hover:bg-black text-white font-extrabold text-sm py-3.5 px-4 shadow-sm transition active:scale-[0.99] cursor-pointer">
                                Entrar na Conta
                            </button>
                        </form>
                    </div>

                    <!-- Rodapé do card: Criar conta e Voltar à loja -->
                    <div class="mt-6 pt-5 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <a href="{{ route('register', ['redirect_to' => old('redirect_to', $redirectTo ?? request('redirect_to'))]) }}"
                           class="font-bold text-pink-700 hover:text-pink-800 hover:underline">
                            Ainda não tem conta? Criar conta grátis
                        </a>
                        <a href="{{ route('loja.index') }}" class="font-semibold text-gray-600 hover:text-gray-900">
                            ← Voltar para a Loja
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-6 px-4 text-xs text-gray-500">
        <a href="{{ route('legal.privacidade') }}" class="hover:text-gray-900 underline">Política de Privacidade</a>
        <span class="mx-2">&bull;</span>
        <a href="{{ route('legal.termos') }}" class="hover:text-gray-900 underline">Termos de Serviço</a>
        <span class="mx-2">&bull;</span>
        <span>YouTube API</span>
        <p class="mt-2">&copy; {{ date('Y') }} Minha Mania Brechó</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Preserva redirect_to
            var redirectInput = document.getElementById('redirect_to');
            var resolvedRedirect = redirectInput ? (redirectInput.value || '').trim() : '';

            if (resolvedRedirect) {
                try { sessionStorage.setItem('sacolinhas_redirect_to', resolvedRedirect); } catch (e) {}
            } else {
                var candidate = '';
                try {
                    candidate = sessionStorage.getItem('sacolinhas_redirect_to') || localStorage.getItem('sacolinhas_last_url') || '';
                } catch (e) {}

                if (!candidate && document.referrer) {
                    try {
                        var refUrl = new URL(document.referrer);
                        if (refUrl.origin === window.location.origin && !refUrl.pathname.match(/^\/($|home$|login|logout|register|password|forgot-password|reset-password)/)) {
                            candidate = refUrl.pathname + refUrl.search;
                        }
                    } catch (e) {}
                }

                if (candidate && redirectInput) {
                    redirectInput.value = candidate;
                    resolvedRedirect = candidate;
                }
            }

            // Controle de abas
            const tabBtnWa = document.getElementById('tabBtnWhatsapp');
            const tabBtnEmail = document.getElementById('tabBtnEmail');
            const panelWa = document.getElementById('panelWhatsapp');
            const panelEmail = document.getElementById('panelEmail');

            function switchTab(tab) {
                if (tab === 'email') {
                    panelWa.classList.add('hidden');
                    panelEmail.classList.remove('hidden');
                    tabBtnEmail.className = 'py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white text-gray-900 shadow-sm';
                    tabBtnWa.className = 'py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900';
                } else {
                    panelEmail.classList.add('hidden');
                    panelWa.classList.remove('hidden');
                    tabBtnWa.className = 'py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 bg-white text-gray-900 shadow-sm';
                    tabBtnEmail.className = 'py-2.5 px-3 rounded-2xl text-xs sm:text-sm font-bold transition flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900';
                }
            }

            tabBtnWa.addEventListener('click', () => switchTab('wa'));
            tabBtnEmail.addEventListener('click', () => switchTab('email'));

            // Máscara simples de telefone BR
            const waPhone = document.getElementById('waPhone');
            waPhone.addEventListener('input', function () {
                let v = this.value.replace(/\D/g, '').slice(0, 11);
                if (v.length > 6) {
                    this.value = `(${v.slice(0, 2)}) ${v.slice(2, v.length - 4)}-${v.slice(v.length - 4)}`;
                } else if (v.length > 2) {
                    this.value = `(${v.slice(0, 2)}) ${v.slice(2)}`;
                } else {
                    this.value = v;
                }
            });

            const waAlert = document.getElementById('waAlert');
            const waRequestForm = document.getElementById('waRequestForm');
            const waVerifyForm = document.getElementById('waVerifyForm');
            const waNewClientFields = document.getElementById('waNewClientFields');
            const waName = document.getElementById('waName');
            const waRequestBtn = document.getElementById('waRequestBtn');
            const waVerifyBtn = document.getElementById('waVerifyBtn');
            const waCode = document.getElementById('waCode');
            const waSentText = document.getElementById('waSentText');
            const waChangePhoneBtn = document.getElementById('waChangePhoneBtn');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function showWaAlert(msg, isError) {
                waAlert.textContent = msg;
                waAlert.className = isError
                    ? 'mb-4 rounded-xl px-4 py-3 text-xs font-semibold bg-red-50 border border-red-300 text-red-900'
                    : 'mb-4 rounded-xl px-4 py-3 text-xs font-semibold bg-emerald-50 border border-emerald-300 text-emerald-900';
                waAlert.classList.remove('hidden');
            }

            waRequestForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                waAlert.classList.add('hidden');
                waRequestBtn.disabled = true;
                const prevHtml = waRequestBtn.innerHTML;
                waRequestBtn.textContent = 'Enviando...';

                try {
                    const resp = await fetch(@json(route('loja.auth.request-code')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            whatsapp: waPhone.value,
                            name: waName.value
                        })
                    });
                    const data = await resp.json().catch(() => ({}));

                    if (resp.ok && data.needs_name) {
                        waNewClientFields.classList.remove('hidden');
                        waName.required = true;
                        waName.focus();
                        return;
                    }

                    if (resp.ok && data.success) {
                        waSentText.textContent = data.message || 'Código enviado no seu WhatsApp!';
                        waRequestForm.classList.add('hidden');
                        waVerifyForm.classList.remove('hidden');
                        waCode.value = '';
                        waCode.focus();
                        return;
                    }

                    showWaAlert(data.message || 'Não foi possível enviar o código. Tente entrar com e-mail e senha.', true);
                } catch (err) {
                    showWaAlert('Erro de conexão. Tente novamente ou entre com e-mail e senha.', true);
                } finally {
                    waRequestBtn.disabled = false;
                    waRequestBtn.innerHTML = prevHtml;
                }
            });

            waChangePhoneBtn.addEventListener('click', function () {
                waVerifyForm.classList.add('hidden');
                waRequestForm.classList.remove('hidden');
                waAlert.classList.add('hidden');
                waPhone.focus();
            });

            waVerifyForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                waAlert.classList.add('hidden');
                waVerifyBtn.disabled = true;
                waVerifyBtn.textContent = 'Verificando...';

                try {
                    const resp = await fetch(@json(route('loja.auth.verify-code')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            whatsapp: waPhone.value,
                            code: waCode.value.replace(/\D/g, ''),
                            redirect_to: resolvedRedirect || ''
                        })
                    });
                    const data = await resp.json().catch(() => ({}));

                    if (resp.ok && data.success) {
                        window.location.href = data.redirect_url || @json(route('loja.index'));
                        return;
                    }

                    showWaAlert(data.message || 'Código inválido. Tente novamente.', true);
                } catch (err) {
                    showWaAlert('Erro de conexão ao verificar código.', true);
                } finally {
                    waVerifyBtn.disabled = false;
                    waVerifyBtn.textContent = 'Confirmar e Entrar';
                }
            });

            // Registrar Service Worker PWA
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function() {});
                });
            }
        });
    </script>
</body>
</html>
