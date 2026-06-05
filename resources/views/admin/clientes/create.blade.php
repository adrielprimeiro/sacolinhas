@extends('layouts.app')
@section('title', 'Novo Cliente')
@section('brand_route', 'admin.clientes.index')
@section('brand_icon', 'fas fa-users')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Novo Cliente</h1>
        <p class="text-sm text-gray-400 mt-0.5">Cadastre um cliente rapidamente com geração automática de e-mail e limite de crédito</p>
    </div>
    <div>
        <a href="{{ route('admin.clientes.index') }}" 
           class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 text-sm font-bold px-4 py-2.5 rounded-xl transition">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

{{-- Alertas de Validação --}}
@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6" role="alert">
        <h6 class="font-bold flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Erros encontrados:</h6>
        <ul class="list-disc pl-5 mt-1.5 text-xs font-semibold">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Card do Formulário --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 max-w-2xl mx-auto">
    <form action="{{ route('admin.clientes.store') }}" method="POST" id="form-novo-cliente">
        @csrf
        
        <div class="space-y-4">
            {{-- Nome --}}
            <div>
                <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nome Completo</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       placeholder="Ex: João Silva"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('name') border-red-500 @enderror">
                <p class="text-[10px] text-gray-400 mt-1">
                    <i class="fas fa-info-circle"></i> Se deixado em branco, usaremos o usuário do Instagram ou TikTok como nome.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Instagram --}}
                <div>
                    <label for="instagram" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                        <i class="fab fa-instagram text-pink-500 mr-1"></i> Instagram
                    </label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">@</span>
                        <input type="text" name="instagram" id="instagram" value="{{ old('instagram') }}"
                               placeholder="usuario"
                               class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">Apenas o nome de usuário (ex: maria_silva)</p>
                </div>

                {{-- TikTok --}}
                <div>
                    <label for="tiktok" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                        <i class="fab fa-tiktok text-black mr-1"></i> TikTok
                    </label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">@</span>
                        <input type="text" name="tiktok" id="tiktok" value="{{ old('tiktok') }}"
                               placeholder="usuario"
                               class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1">Apenas o nome de usuário (ex: maria_silva)</p>
                </div>
            </div>

            {{-- Limite de Crédito --}}
            <div>
                <label for="limite_credito" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                    <i class="fas fa-wallet text-indigo-500 mr-1"></i> Limite de Crédito (R$)
                </label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">R$</span>
                    <input type="number" step="0.01" min="0" name="limite_credito" id="limite_credito" 
                           value="{{ old('limite_credito', '300.00') }}"
                           placeholder="300,00"
                           class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none @error('limite_credito') border-red-500 @enderror">
                </div>
                <p class="text-[10px] text-gray-400 mt-1">Limite inicial disponível para compras em sacolas ou lives (Padrão: R$ 300,00)</p>
            </div>

            {{-- Caixa de Preview em Tempo Real --}}
            <div id="preview-box" class="bg-gray-50 border border-gray-150 rounded-xl p-4 hidden">
                <h6 class="text-xs font-bold text-gray-500 flex items-center gap-1.5 mb-2"><i class="fas fa-eye"></i> Prévia do Cliente:</h6>
                <div class="text-xs text-gray-600 space-y-1">
                    <p><i class="fas fa-user text-gray-400 w-4"></i> <strong>Nome Usado:</strong> <span id="preview-nome">—</span></p>
                    <p><i class="fas fa-envelope text-gray-400 w-4"></i> <strong>E-mail Gerado:</strong> <span id="preview-email">—</span></p>
                </div>
            </div>
        </div>

        {{-- Botões --}}
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
            <a href="{{ route('admin.clientes.index') }}" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-200 font-bold px-5 py-2.5 rounded-xl text-sm transition">
                Cancelar
            </a>
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Salvar Cliente
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputName = document.getElementById('name');
        const inputInsta = document.getElementById('instagram');
        const inputTiktok = document.getElementById('tiktok');
        const previewBox = document.getElementById('preview-box');
        const previewNome = document.getElementById('preview-nome');
        const previewEmail = document.getElementById('preview-email');

        function updatePreview() {
            const nameVal = inputName.value.trim();
            const instaVal = inputInsta.value.trim();
            const tiktokVal = inputTiktok.value.trim();

            let finalName = '';
            
            if (nameVal) {
                finalName = nameVal;
            } else if (instaVal) {
                finalName = instaVal;
            } else if (tiktokVal) {
                finalName = tiktokVal;
            }

            if (finalName) {
                const safeEmailName = finalName.toLowerCase().replace(/[^a-z0-9]/g, '');
                previewNome.textContent = finalName;
                previewEmail.textContent = safeEmailName ? `${safeEmailName}@mania.com` : '—';
                previewBox.classList.remove('hidden');
            } else {
                previewBox.classList.add('hidden');
            }
        }

        [inputName, inputInsta, inputTiktok].forEach(el => {
            el.addEventListener('input', updatePreview);
        });

        // Executar prévia inicial caso haja valores preenchidos (ex: com old_input)
        updatePreview();
    });
</script>
@endsection
