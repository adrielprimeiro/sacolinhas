@extends('layouts.portal-cliente')

@section('title', 'Meu Perfil - Portal do Cliente')

@section('content')
<div class="space-y-6">

    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-sm p-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Meu Perfil</h1>
            <p class="text-gray-600 text-sm">Atualize seus dados e sua senha</p>
        </div>
    </div>

    <!-- Mensagens (para fallbacks) -->
    <div id="messages-container" class="hidden"></div>
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">
            <p class="font-semibold mb-1">Verifique os campos:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form id="profile-form" action="{{ route('portal.perfil.atualizar') }}" method="POST" enctype="multipart/form-data" class="space-y-4" autocomplete="off" onsubmit="event.preventDefault();">
        @csrf
        @method('PUT')
        
        @if($returnTo)
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @endif

        <!-- Card 1: Identificação -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md">
            <button type="button" class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50/80 transition duration-150 accordion-toggle" data-target="section-identificacao">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-user-circle text-xl"></i>
                    </div>
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-gray-800">Identificação</h3>
                        <p class="text-xs text-gray-500">Sua foto, nome completo, apelido, e-mail e WhatsApp</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 accordion-icon"></i>
                </div>
            </button>
            <div id="section-identificacao" class="border-t border-gray-100 p-4 space-y-4 accordion-content hidden">
                <!-- Foto de Perfil -->
                <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 border overflow-hidden flex items-center justify-center flex-shrink-0 relative">
                        @if($user->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" alt="Foto do Perfil" class="w-full h-full object-cover">
                        @else
                            <i class="fas fa-user text-gray-300 text-2xl sm:text-3xl"></i>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Foto de Perfil</label>
                        <label class="cursor-pointer bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold py-2 px-4 rounded-md transition duration-150 inline-block select-none">
                            <span id="photo-button-text">Alterar Foto</span>
                            <input type="file" name="photo" id="photo-input" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Como quer ser chamado? (Apelido)</label>
                        <input type="text" name="apelido" value="{{ old('apelido', $user->apelido) }}" placeholder="Ex: Adriel"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp</label>
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $user->whatsapp) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Endereço -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md">
            <button type="button" class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50/80 transition duration-150 accordion-toggle" data-target="section-endereco">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-map-marker-alt text-xl"></i>
                    </div>
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-gray-800">Endereço</h3>
                        <p class="text-xs text-gray-500">Seu endereço de entrega e CEP</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 accordion-icon"></i>
                </div>
            </button>
            <div id="section-endereco" class="border-t border-gray-100 p-4 space-y-4 accordion-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" value="{{ old('cep', $user->cep) }}" placeholder="00000-000"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2 flex items-end">
                        <p class="text-xs text-gray-500 mb-2">Digite o CEP para preencher o endereço automaticamente.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro (Rua/Av)</label>
                        <input type="text" name="endereco" id="endereco" value="{{ old('endereco', $user->endereco) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                        <input type="text" name="numero_endereco" id="numero_endereco" value="{{ old('numero_endereco', $user->numero_endereco) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $user->bairro) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $user->complemento) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="{{ old('cidade', $user->cidade) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado (UF)</label>
                        <select name="estado" id="estado" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" {{ old('estado', $user->estado) == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Login -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md">
            <button type="button" class="w-full flex items-center justify-between p-4 focus:outline-none hover:bg-gray-50/80 transition duration-150 accordion-toggle" data-target="section-login">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                        <i class="fas fa-lock text-xl"></i>
                    </div>
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-gray-800">Login</h3>
                        <p class="text-xs text-gray-500">Alterar sua senha de acesso ao portal</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 accordion-icon"></i>
                </div>
            </button>
            <div id="section-login" class="border-t border-gray-100 p-4 space-y-4 accordion-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <p class="text-xs text-gray-500">Deixe em branco para manter a senha atual.</p>
            </div>
        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Accordion Logic
    const toggles = document.querySelectorAll('.accordion-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const icon = this.querySelector('.accordion-icon');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                this.classList.add('active');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                this.classList.remove('active');
                icon.classList.remove('rotate-180');
            }
        });
    });

    // Autosave Logic
    const form = document.getElementById('profile-form');
    
    // Status indicator DOM element
    const indicator = document.createElement('div');
    indicator.className = 'fixed bottom-4 right-4 bg-gray-900/95 text-white px-4 py-2.5 rounded-lg shadow-xl flex items-center gap-2.5 text-xs font-semibold z-50 transition-all duration-300 opacity-0 pointer-events-none transform translate-y-2';
    indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-400"></i><span id="indicator-text">Salvando...</span>';
    document.body.appendChild(indicator);
    
    let hideTimeout;
    
    function showStatus(text, iconHtml, duration = null) {
        clearTimeout(hideTimeout);
        indicator.querySelector('#indicator-text').innerHTML = text;
        
        const currentIcon = indicator.querySelector('i');
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = iconHtml;
        const newIcon = tempDiv.firstChild;
        indicator.replaceChild(newIcon, currentIcon);
        
        indicator.classList.remove('opacity-0', 'translate-y-2');
        indicator.classList.add('opacity-100', 'translate-y-0');
        
        if (duration) {
            hideTimeout = setTimeout(() => {
                indicator.classList.remove('opacity-100', 'translate-y-0');
                indicator.classList.add('opacity-0', 'translate-y-2');
            }, duration);
        }
    }
    
    function saveForm() {
        showStatus('Salvando...', '<i class="fas fa-spinner fa-spin text-blue-400"></i>');
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw data;
                });
            }
            return response.json();
        })
        .then(data => {
            showStatus('Salvo com sucesso!', '<i class="fas fa-check-circle text-green-400"></i>', 2500);
            
            // Update avatars if photo changed
            if (data.photo_url) {
                const avatars = document.querySelectorAll('img[alt="Foto"], img[alt="Foto do Perfil"]');
                avatars.forEach(img => {
                    img.src = data.photo_url;
                });
            }
            
            // Update brand name
            const brandSpan = document.querySelector('.brand-text-span');
            if (brandSpan && data.brand_name) {
                brandSpan.innerText = data.brand_name;
            }
        })
        .catch(err => {
            console.error(err);
            let errMsg = 'Erro ao salvar alterações';
            if (err && err.errors) {
                errMsg = Object.values(err.errors).flat().join(', ');
            } else if (err && err.message) {
                errMsg = err.message;
            }
            showStatus(errMsg, '<i class="fas fa-exclamation-circle text-red-400"></i>', 5000);
        });
    }

    // Attach listeners to all inputs and selects
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            // Check password validation triggers
            const password = form.querySelector('input[name="password"]');
            const confirmation = form.querySelector('input[name="password_confirmation"]');
            
            if (this.type === 'password') {
                if (password.value && !confirmation.value) {
                    // Wait until confirmation is filled
                    return;
                }
                if (password.value !== confirmation.value) {
                    showStatus('As senhas não coincidem', '<i class="fas fa-exclamation-circle text-yellow-400"></i>', 3000);
                    return;
                }
            }
            
            saveForm();
        });
    });

    // Special event listener for file photo input change
    const photoInput = document.getElementById('photo-input');
    if (photoInput) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                document.getElementById('photo-button-text').innerText = 'Foto selecionada!';
                saveForm();
            }
        });
    }

    // CEP Logic
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                            
                            // Save form since address details changed automatically
                            saveForm();
                        }
                    })
                    .catch(error => console.error('Erro ao buscar CEP:', error));
            }
        });

        // Máscara básica de CEP
        cepInput.addEventListener('input', function() {
            let v = this.value.replace(/\D/g, '');
            if (v.length > 5) v = v.substring(0, 5) + '-' + v.substring(5, 8);
            this.value = v;
        });
    }
});
</script>
@endsection