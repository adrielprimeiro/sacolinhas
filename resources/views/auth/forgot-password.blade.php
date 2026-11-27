<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acesso - Minha Mania Brechó</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex items-center justify-center px-4 py-10 antialiased">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <a href="{{ route('loja.index') }}" class="inline-flex items-center justify-center gap-2.5">
                <img src="{{ asset('favicon-32x32.png') }}" alt="Ícone Minha Mania" class="w-9 h-9 object-contain">
                <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania Brechó" class="h-11 max-w-[210px] object-contain">
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/70 border border-gray-200 p-6 sm:p-7 space-y-5">
            <div>
                <h1 class="text-xl font-extrabold text-gray-900">Esqueceu sua senha?</h1>
                <p class="mt-2 text-sm text-gray-600 leading-relaxed">
                    Você <strong>não precisa de senha</strong> para entrar na sua sacolinha ou comprar na loja!
                </p>
            </div>

            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 space-y-3">
                <p class="text-xs font-bold text-emerald-900">
                    <i class="fab fa-whatsapp text-emerald-600 mr-1"></i> Jeito mais rápido: entre com seu WhatsApp
                </p>
                <p class="text-xs text-emerald-800 leading-relaxed">
                    Enviamos um código de 6 dígitos direto no seu WhatsApp cadastrado. Depois de entrar, você pode definir uma nova senha em <strong>Meu Perfil</strong>.
                </p>
                <a href="{{ route('login') }}"
                   class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-sm py-3 px-4 transition">
                    <i class="fab fa-whatsapp"></i>
                    <span>Entrar com Código no WhatsApp</span>
                </a>
            </div>

            @if(\Illuminate\Support\Facades\Route::has('password.email'))
                <div class="pt-4 border-t border-gray-200">
                    <p class="text-xs font-bold text-gray-700 mb-3">Ou receba o link de redefinição por e-mail:</p>

                    @if (session('status'))
                        <div class="mb-3 rounded-xl bg-emerald-50 border border-emerald-300 px-3 py-2 text-xs text-emerald-900 font-semibold">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
                        @csrf
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="Digite seu e-mail cadastrado"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-pink-500 focus:ring-2 focus:ring-pink-200 outline-none">
                        <button type="submit"
                                class="w-full rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-xs py-2.5 px-4 transition cursor-pointer">
                            Enviar link por e-mail
                        </button>
                    </form>
                </div>
            @endif

            <div class="text-center pt-2">
                <a href="{{ route('login', ['tab' => 'email']) }}" class="text-xs font-bold text-gray-600 hover:text-gray-900">
                    ← Voltar para o Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>
