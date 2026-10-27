<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Minha Mania</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center mt-5 mb-3">
                    <img src="{{ asset('favicon-32x32.png') }}" alt="Ícone Minha Mania" style="width: 42px; height: 42px; vertical-align: middle; margin-right: 8px;">
                    <img src="{{ asset('images/Logo_Grande_Preto.png') }}" alt="Minha Mania" style="height: 48px; max-width: 200px; object-fit: contain; vertical-align: middle;">
                </div>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h4 class="mb-0">Acessar Conta</h4> <!-- Cabeçalho correto para login -->
                    </div>
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-warning text-dark">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('login') }}"> <!-- Formulário apontando para a rota de login -->
                            @csrf
                            <input type="hidden" name="redirect_to" id="redirect_to" value="{{ old('redirect_to', $redirectTo ?? request('redirect_to')) }}">

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       id="password"
                                       name="password"
                                       required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Entrar</button> <!-- Botão correto para login -->
                            </div>
                        </form>
                    </div>
                </div>

                <div class="text-center mt-4 text-muted small">
                    <a href="{{ route('legal.privacidade') }}" class="text-muted text-decoration-none me-2">Política de Privacidade</a> &bull; 
                    <a href="{{ route('legal.termos') }}" class="text-muted text-decoration-none ms-2 me-2">Termos de Serviço</a> &bull;
                    <span class="ms-1">YouTube API</span>
                    <p class="mt-2 text-muted">&copy; {{ date('Y') }} Minha Mania Brechó</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var redirectInput = document.getElementById('redirect_to');
            if (!redirectInput) return;

            var currentVal = (redirectInput.value || '').trim();
            if (currentVal) {
                try { sessionStorage.setItem('sacolinhas_redirect_to', currentVal); } catch (e) {}
                return;
            }

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

            if (candidate) {
                redirectInput.value = candidate;
            }
        });
    </script>
</body>
</html>