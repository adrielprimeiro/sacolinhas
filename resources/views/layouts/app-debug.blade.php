<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Debug</title>

    <!-- TESTE 1: Sem Vite Assets -->
    <!-- @vite(['resources/css/app.css', 'resources/js/app.js']) -->
    
    <!-- Bootstrap temporário ao invés do Vite -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- TESTE 2: Sem Font externa -->
    <!-- <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /> -->
</head>
<body>
    <div class="container-fluid">
        <!-- TESTE 3: Sem Navigation include -->
        <!-- @include('layouts.navigation') -->
        
        <nav class="navbar navbar-light bg-light">
            <div class="container">
                <span class="navbar-brand">🧪 Teste Debug - Sem Navigation</span>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-4">
            @yield('content')
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>