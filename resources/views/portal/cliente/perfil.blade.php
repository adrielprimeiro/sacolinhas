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

    <!-- Mensagens -->
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
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Dados do Perfil</h2>
        </div>

        <form action="{{ route('portal.perfil.atualizar') }}" method="POST" enctype="multipart/form-data" class="p-4 space-y-4" autocomplete="off">
            @csrf
            @method('PUT')
            
            @if($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @endif

            <!-- Foto de Perfil -->
            <div class="flex flex-col sm:flex-row items-center gap-4 pb-4 border-b border-gray-100">
                <div class="w-20 h-20 rounded-full bg-gray-100 border overflow-hidden flex items-center justify-center flex-shrink-0 relative">
                    @if($user->photo)
                        <img src="{{ asset('storage/' . $user->photo) }}" alt="Foto do Perfil" class="w-full h-full object-cover">
                    @else
                        <i class="fas fa-user text-gray-300 text-3xl"></i>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Foto de Perfil</label>
                    <input type="file" name="photo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-400 mt-1">Formatos aceitos: JPG, PNG, GIF. Tamanho máx: 2MB.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? $user->telefone_principal) }}"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Seção de Endereço -->
            <div class="pt-4 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Endereço de Entrega</h3>
                
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

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro (Rua/Av)</label>
                        <input type="text" name="endereco" id="endereco" value="{{ old('endereco', $user->endereco) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                        <input type="text" name="numero_endereco" value="{{ old('numero_endereco', $user->numero_endereco) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $user->bairro) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                        <input type="text" name="complemento" value="{{ old('complemento', $user->complemento) }}"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
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

            <div class="pt-2 border-t border-gray-200">
                <p class="text-sm font-semibold text-gray-800 mb-2">Alterar senha (opcional)</p>

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

                <p class="text-xs text-gray-500 mt-2">Deixe em branco para manter a senha atual.</p>
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-md transition duration-200">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>

</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
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