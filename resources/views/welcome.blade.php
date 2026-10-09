<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Minha Mania') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] antialiased min-h-screen flex flex-col font-['Instrument_Sans']">
        
        <!-- Navegação Superior (Botões menos ovalados) -->
        <header class="absolute top-0 right-0 p-6 lg:p-10 w-full flex justify-end">
            <nav class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="px-5 py-2 text-[13px] font-medium bg-white border border-[#e3e3e0] text-[#706f6c] rounded-lg shadow-sm hover:text-black hover:border-neutral-300 transition-all active:scale-95 dark:bg-[#161615] dark:border-[#3E3E3A] dark:text-[#A1A09A] dark:hover:text-white">
                    ADM
                </a>
                <a href="https://minhamania.net/login" target="_blank" class="px-5 py-2 text-[13px] font-medium bg-white border border-[#e3e3e0] text-[#706f6c] rounded-lg shadow-sm hover:text-black hover:border-neutral-300 transition-all active:scale-95 dark:bg-[#161615] dark:border-[#3E3E3A] dark:text-[#A1A09A] dark:hover:text-white">
                    Clube Mania 
                </a>
                <a href="https://minhamania.net/loja" target="_blank" class="px-5 py-2 text-[13px] font-medium bg-white border border-[#e3e3e0] text-[#706f6c] rounded-lg shadow-sm hover:text-black hover:border-neutral-300 transition-all active:scale-95 dark:bg-[#161615] dark:border-[#3E3E3A] dark:text-[#A1A09A] dark:hover:text-white">
                    Loja 
                </a>
            </nav>
        </header>

        <!-- Conteúdo Centralizado -->
        <main class="flex-grow flex flex-col items-center justify-center px-6 text-center">
            
            <!-- Logo Central -->
            <div class="mb-10">
				<!-- Logo Escura (Aparece no modo claro, some no escuro) -->
				<img 
					src="{{ asset('images/Logo_Grande_Preto.png') }}" 
					class="h-36 lg:h-52 w-auto object-contain dark:hidden"
				>

				<!-- Logo Branca (Some no modo claro, aparece no escuro) -->
				<img 
			src="{{ asset('images/Logo_Grande_Branco.png') }}" 
			class="h-36 lg:h-52 w-auto object-contain hidden dark:block"
>
            </div>

        </main>

        <!-- Rodapé -->
        <footer class="p-8 text-center text-[12px] text-neutral-500 space-y-2">
            <div class="flex flex-wrap items-center justify-center gap-4 text-xs font-semibold">
                <a href="{{ route('legal.privacidade') }}" class="hover:text-pink-600 transition">Política de Privacidade</a>
                <span class="text-neutral-300">&bull;</span>
                <a href="{{ route('legal.termos') }}" class="hover:text-pink-600 transition">Termos de Serviço</a>
                <span class="text-neutral-300">&bull;</span>
                <span class="text-neutral-400">YouTube API Services</span>
            </div>
            <p class="text-[11px] text-neutral-400">&copy; {{ date('Y') }} Minha Mania Brechó &bull; Todos os direitos reservados.</p>
        </footer>
    </body>
</html>