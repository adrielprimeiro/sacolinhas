<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Minha Mania Brechó</title>

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F5148C">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
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
            <div class="text-center mb-6">
                <a href="{{ route('loja.index') }}" class="inline-flex items-center justify-center gap-2.5">
                    <img src="{{ asset('favicon-32x32.png') }}" alt="Ícone Minha Mania" class="w-9 h-9 object-contain">
                    <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-11 max-w-[210px] object-contain">
                </a>
                <p class="mt-2 text-sm text-gray-600 font-medium">Crie sua conta em 30 segundos e monte sua sacolinha</p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/70 border border-gray-200 p-6 sm:p-7">
                <div class="mb-5">
                    <h1 class="text-xl font-extrabold text-gray-900">Criar Conta Grátis</h1>
                    <p class="text-xs text-gray-600 mt-1">
                        Junte peças das lives e da loja online na mesma sacolinha para pagar um frete só.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-300 px-4 py-3 text-xs text-red-900">
                        <ul class="list-disc list-inside space-y-1 font-medium">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ old('redirect_to', $redirectTo ?? request('redirect_to')) }}">

                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">Nome completo *</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"
                               placeholder="Seu nome completo"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">WhatsApp com DDD *</label>
                        <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required autocomplete="tel"
                               placeholder="(48) 99999-9999"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">E-mail *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                               placeholder="seuemail@exemplo.com"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">Senha *</label>
                            <input type="password" id="password" name="password" required autocomplete="new-password"
                                   placeholder="Mín. 6 caracteres"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wide text-gray-700 mb-1.5">Confirmar senha *</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                                   placeholder="Repita a senha"
                                   class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-extrabold text-sm py-3.5 px-4 shadow-sm transition active:scale-[0.99] cursor-pointer">
                        Criar Minha Conta
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <a href="{{ route('login', ['redirect_to' => old('redirect_to', $redirectTo ?? request('redirect_to'))]) }}"
                       class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline">
                        Já compra nas lives? Entrar pelo WhatsApp →
                    </a>
                    <a href="{{ route('loja.index') }}" class="font-semibold text-gray-600 hover:text-gray-900">
                        ← Voltar para a Loja
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-6 px-4 text-xs text-gray-500">
        <a href="{{ route('legal.privacidade') }}" class="hover:text-gray-900 underline">Política de Privacidade</a>
        <span class="mx-2">&bull;</span>
        <a href="{{ route('legal.termos') }}" class="hover:text-gray-900 underline">Termos de Serviço</a>
        <p class="mt-2">&copy; {{ date('Y') }} Minha Mania Brechó</p>
    </footer>

    <script>
        const waInput = document.getElementById('whatsapp');
        if (waInput) {
            waInput.addEventListener('input', function () {
                let v = this.value.replace(/\D/g, '').slice(0, 11);
                if (v.length > 6) {
                    this.value = `(${v.slice(0, 2)}) ${v.slice(2, v.length - 4)}-${v.slice(v.length - 4)}`;
                } else if (v.length > 2) {
                    this.value = `(${v.slice(0, 2)}) ${v.slice(2)}`;
                } else {
                    this.value = v;
                }
            });
        }
    </script>
</body>
</html>
