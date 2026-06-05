@extends('layouts.app')
@section('title', 'Editar Cliente')
@section('brand_route', 'admin.clientes.index')
@section('brand_icon', 'fas fa-users')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-black text-gray-800">Editar Cliente</h1>
        <p class="text-sm text-gray-400 mt-0.5">
            {{ $cliente->nome_completo }}
            @if($cliente->codigo_cliente)
                <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2 py-0.5 rounded-lg ml-2">#{{ $cliente->codigo_cliente }}</span>
            @endif
            @if($cliente->bloqueado)
                <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-0.5 rounded-lg ml-1">Bloqueado</span>
            @else
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-lg ml-1">Ativo</span>
            @endif
        </p>
    </div>
    <div>
        <a href="{{ route('admin.clientes.show', $cliente) }}" 
           class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-semibold px-4 py-2.5 rounded-xl transition">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

{{-- Alertas de Validação --}}
@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6" role="alert">
        <h6 class="font-bold flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> Erros encontrados:</h6>
        <ul class="list-disc pl-5 mt-1.5 text-xs font-semibold">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Card do Formulário --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6" x-data="{ activeTab: 'pessoal' }">
    <form action="{{ route('admin.clientes.update', $cliente) }}" method="POST" id="clientForm">
        @csrf
        @method('PUT')

        {{-- Abas (Tabs) --}}
        <div class="flex flex-wrap gap-1 mb-6 border-b border-gray-100">
            @foreach ([
                'pessoal'    => ['icon' => 'user', 'label' => 'Dados Pessoais'],
                'contato'    => ['icon' => 'phone', 'label' => 'Contatos'],
                'redes'      => ['icon' => 'share-alt', 'label' => 'Redes Sociais'],
                'endereco'   => ['icon' => 'map-marker-alt', 'label' => 'Endereço'],
                'documentos' => ['icon' => 'id-card', 'label' => 'Documentos'],
                'comercial'  => ['icon' => 'shopping-cart', 'label' => 'Comercial'],
                'seguranca'  => ['icon' => 'lock', 'label' => 'Segurança'],
                'status'     => ['icon' => 'toggle-on', 'label' => 'Status']
            ] as $tabKey => $tabData)
                <button type="button" 
                        @click="activeTab = '{{ $tabKey }}'"
                        :class="activeTab === '{{ $tabKey }}' 
                            ? 'border-indigo-600 text-indigo-600 bg-indigo-50/20' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="px-4 py-2.5 border-b-2 font-bold text-sm transition flex items-center gap-2 outline-none">
                    <i class="fas fa-{{ $tabData['icon'] }} text-xs"></i>
                    <span>{{ $tabData['label'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Conteúdo das Abas --}}
        <div class="space-y-4">
            
            {{-- TAB DADOS PESSOAIS --}}
            <div x-show="activeTab === 'pessoal'" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nome Completo *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $cliente->name) }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label for="apelido" class="block text-xs font-bold text-gray-500 uppercase mb-1">Apelido</label>
                        <input type="text" name="apelido" id="apelido" value="{{ old('apelido', $cliente->apelido) }}" placeholder="Como prefere ser chamado"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="data_nascimento" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data de Nascimento</label>
                        <input type="date" name="data_nascimento" id="data_nascimento" 
                               value="{{ old('data_nascimento', $cliente->data_nascimento ? $cliente->data_nascimento->format('Y-m-d') : '') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        @if($cliente->idade)
                            <p class="text-[10px] text-gray-400 mt-1">{{ $cliente->idade }} anos</p>
                        @endif
                    </div>
                    <div>
                        <label for="sexo" class="block text-xs font-bold text-gray-500 uppercase mb-1">Sexo</label>
                        <select name="sexo" id="sexo" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <option value="">Selecione...</option>
                            <option value="M" {{ old('sexo', $cliente->sexo) == 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('sexo', $cliente->sexo) == 'F' ? 'selected' : '' }}>Feminino</option>
                            <option value="Outro" {{ old('sexo', $cliente->sexo) == 'Outro' ? 'selected' : '' }}>Outro</option>
                        </select>
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-500 uppercase mb-1">Email *</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- TAB CONTATOS --}}
            <div x-show="activeTab === 'contato'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="telefone_principal" class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefone Principal</label>
                        <input type="text" name="telefone_principal" id="telefone_principal" 
                               value="{{ old('telefone_principal', $cliente->telefone_principal_formatado) }}" placeholder="(00) 00000-0000"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mask-phone">
                    </div>
                    <div>
                        <label for="telefone_2" class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefone Secundário</label>
                        <input type="text" name="telefone_2" id="telefone_2" 
                               value="{{ old('telefone_2', $cliente->telefone_2_formatado) }}" placeholder="(00) 00000-0000"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mask-phone">
                    </div>
                </div>

                <div>
                    <label for="whatsapp" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                        <i class="fab fa-whatsapp text-green-500 mr-1"></i> WhatsApp
                    </label>
                    <input type="text" name="whatsapp" id="whatsapp" 
                           value="{{ old('whatsapp', $cliente->whatsapp_formatado) }}" placeholder="(00) 00000-0000"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mask-phone">
                    @if($cliente->whatsapp_url)
                        <p class="text-xs mt-1">
                            <a href="{{ $cliente->whatsapp_url }}" target="_blank" class="text-green-600 hover:text-green-700 font-semibold inline-flex items-center gap-1">
                                <i class="fas fa-external-link-alt text-[10px]"></i> Abrir no WhatsApp
                            </a>
                        </p>
                    @endif
                </div>
            </div>

            {{-- TAB REDES SOCIAIS --}}
            <div x-show="activeTab === 'redes'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="instagram" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            <i class="fab fa-instagram text-pink-500 mr-1"></i> Instagram
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">@</span>
                            <input type="text" name="instagram" id="instagram" value="{{ old('instagram', $cliente->instagram) }}" placeholder="usuario"
                                   class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        </div>
                        @if($cliente->instagram_url)
                            <p class="text-xs mt-1">
                                <a href="{{ $cliente->instagram_url }}" target="_blank" class="text-gray-600 hover:text-gray-800 font-semibold inline-flex items-center gap-1">
                                    <i class="fas fa-external-link-alt text-[10px]"></i> Ver perfil no Instagram
                                </a>
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="tiktok" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            <i class="fab fa-tiktok text-black mr-1"></i> TikTok
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">@</span>
                            <input type="text" name="tiktok" id="tiktok" value="{{ old('tiktok', $cliente->tiktok) }}" placeholder="usuario"
                                   class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        </div>
                        @if($cliente->tiktok_url)
                            <p class="text-xs mt-1">
                                <a href="{{ $cliente->tiktok_url }}" target="_blank" class="text-gray-600 hover:text-gray-800 font-semibold inline-flex items-center gap-1">
                                    <i class="fas fa-external-link-alt text-[10px]"></i> Ver perfil no TikTok
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- TAB ENDEREÇO --}}
            <div x-show="activeTab === 'endereco'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    <div class="sm:col-span-3">
                        <label for="cep" class="block text-xs font-bold text-gray-500 uppercase mb-1">CEP</label>
                        <input type="text" name="cep" id="cep" value="{{ old('cep', $cliente->cep_formatado) }}" placeholder="00000-000"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mask-cep">
                        <p class="text-[9px] text-gray-400 mt-0.5">Consulta automática ao sair do campo</p>
                    </div>
                    <div class="sm:col-span-7">
                        <label for="endereco" class="block text-xs font-bold text-gray-500 uppercase mb-1">Endereço</label>
                        <input type="text" name="endereco" id="endereco" value="{{ old('endereco', $cliente->endereco) }}" placeholder="Rua, Avenida, etc."
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="numero_endereco" class="block text-xs font-bold text-gray-500 uppercase mb-1">Número</label>
                        <input type="text" name="numero_endereco" id="numero_endereco" value="{{ old('numero_endereco', $cliente->numero_endereco) }}" placeholder="123"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="complemento" class="block text-xs font-bold text-gray-500 uppercase mb-1">Complemento</label>
                        <input type="text" name="complemento" id="complemento" value="{{ old('complemento', $cliente->complemento) }}" placeholder="Apto, Bloco, Casa"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label for="bairro" class="block text-xs font-bold text-gray-500 uppercase mb-1">Bairro</label>
                        <input type="text" name="bairro" id="bairro" value="{{ old('bairro', $cliente->bairro) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="cidade" class="block text-xs font-bold text-gray-500 uppercase mb-1">Cidade</label>
                        <input type="text" name="cidade" id="cidade" value="{{ old('cidade', $cliente->cidade) }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                    <div>
                        <label for="estado" class="block text-xs font-bold text-gray-500 uppercase mb-1">Estado</label>
                        <select name="estado" id="estado" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <option value="">Selecione...</option>
                            @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                <option value="{{ $uf }}" {{ old('estado', $cliente->estado) == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="pais" class="block text-xs font-bold text-gray-500 uppercase mb-1">País</label>
                        <input type="text" name="pais" id="pais" value="{{ old('pais', $cliente->pais ?? 'Brasil') }}"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- TAB DOCUMENTOS --}}
            <div x-show="activeTab === 'documentos'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="cpf" class="block text-xs font-bold text-gray-500 uppercase mb-1">CPF</label>
                        <input type="text" name="cpf" id="cpf" value="{{ old('cpf', $cliente->cpf_formatado) }}" placeholder="000.000.000-00"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none mask-cpf">
                    </div>
                    <div>
                        <label for="rg" class="block text-xs font-bold text-gray-500 uppercase mb-1">RG</label>
                        <input type="text" name="rg" id="rg" value="{{ old('rg', $cliente->rg) }}" placeholder="00.000.000-0"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- TAB COMERCIAL --}}
            <div x-show="activeTab === 'comercial'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="codigo_cliente" class="block text-xs font-bold text-gray-500 uppercase mb-1">Código do Cliente</label>
                        <input type="text" name="codigo_cliente" id="codigo_cliente" value="{{ $cliente->codigo_cliente }}" readonly
                               class="w-full bg-gray-50 border border-gray-200 text-gray-500 rounded-lg px-3 py-2 text-sm cursor-not-allowed">
                        <p class="text-[10px] text-gray-400 mt-1">Gerado automaticamente pelo sistema</p>
                    </div>
                    <div>
                        <label for="tipo_cliente" class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo de Cliente</label>
                        <select name="tipo_cliente" id="tipo_cliente" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <option value="">Selecione...</option>
                            <option value="Premium" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Premium' ? 'selected' : '' }}>Premium</option>
                            <option value="Standard" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Standard' ? 'selected' : '' }}>Standard</option>
                            <option value="VIP" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'VIP' ? 'selected' : '' }}>VIP</option>
                            <option value="Bronze" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Bronze' ? 'selected' : '' }}>Bronze</option>
                            <option value="Prata" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Prata' ? 'selected' : '' }}>Prata</option>
                            <option value="Ouro" {{ old('tipo_cliente', $cliente->tipo_cliente) == 'Ouro' ? 'selected' : '' }}>Ouro</option>
                        </select>
                    </div>
                    <div>
                        <label for="total_pedidos" class="block text-xs font-bold text-gray-500 uppercase mb-1">Total de Pedidos</label>
                        <input type="text" name="total_pedidos" id="total_pedidos" value="{{ $cliente->total_pedidos }}" readonly
                               class="w-full bg-gray-50 border border-gray-200 text-gray-500 rounded-lg px-3 py-2 text-sm cursor-not-allowed">
                        <p class="text-[10px] text-gray-400 mt-1">Atualizado automaticamente</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="data_cadastro" class="block text-xs font-bold text-gray-500 uppercase mb-1">Data de Cadastro</label>
                        <input type="text" value="{{ $cliente->data_cadastro ? $cliente->data_cadastro->format('d/m/Y H:i') : '-' }}" readonly
                               class="w-full bg-gray-50 border border-gray-200 text-gray-500 rounded-lg px-3 py-2 text-sm cursor-not-allowed">
                        <p class="text-[10px] text-gray-400 mt-1">{{ $cliente->tempo_com_cliente }}</p>
                    </div>
                    <div>
                        <label for="ultima_compra" class="block text-xs font-bold text-gray-500 uppercase mb-1">Última Compra</label>
                        <input type="text" value="{{ $cliente->ultima_compra ? $cliente->ultima_compra->format('d/m/Y H:i') : 'Nunca' }}" readonly
                               class="w-full bg-gray-50 border border-gray-200 text-gray-500 rounded-lg px-3 py-2 text-sm cursor-not-allowed">
                        @if($cliente->ultima_compra)
                            <p class="text-[10px] text-gray-400 mt-1">{{ $cliente->ultima_compra->diffForHumans() }}</p>
                        @endif
                    </div>
                    
                    {{-- Campo de Limite de Crédito solicitado! --}}
                    <div>
                        <label for="limite_credito" class="block text-xs font-bold text-gray-500 uppercase mb-1">
                            <i class="fas fa-wallet text-indigo-500 mr-1"></i> Limite de Crédito (R$)
                        </label>
                        <div class="flex">
                            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-sm">R$</span>
                            <input type="number" step="0.01" min="0" name="limite_credito" id="limite_credito" 
                                   value="{{ old('limite_credito', $cliente->limite?->limite_credito ?? '300.00') }}"
                                   placeholder="300,00"
                                   class="w-full border border-gray-200 rounded-r-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">Limite atual disponível: R$ {{ number_format($cliente->limite?->limite_disponivel ?? 300.00, 2, ',', '.') }}</p>
                    </div>
                </div>

                <div>
                    <label for="observacao_cliente" class="block text-xs font-bold text-gray-500 uppercase mb-1">Observações</label>
                    <textarea name="observacao_cliente" id="observacao_cliente" rows="4" placeholder="Informações adicionais sobre o cliente..."
                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">{{ old('observacao_cliente', $cliente->observacao_cliente) }}</textarea>
                </div>
            </div>

            {{-- TAB SEGURANÇA --}}
            <div x-show="activeTab === 'seguranca'" class="space-y-4" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nova Senha</label>
                        <input type="password" name="password" id="password" placeholder="Mínimo 6 caracteres"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                        <p class="text-[10px] text-gray-400 mt-1">Deixe em branco para manter a senha atual</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirmação da senha"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="role" class="block text-xs font-bold text-gray-500 uppercase mb-1">Função/Papel</label>
                        <select name="role" id="role" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <option value="client" {{ old('role', $cliente->role) == 'client' ? 'selected' : '' }}>Cliente</option>
                            <option value="admin" {{ old('role', $cliente->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
                        </select>
                    </div>
                    <div>
                        <label for="is_admin" class="block text-xs font-bold text-gray-500 uppercase mb-1">Status Admin</label>
                        <select name="is_admin" id="is_admin" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                            <option value="0" {{ old('is_admin', $cliente->is_admin) == 0 ? 'selected' : '' }}>Não</option>
                            <option value="1" {{ old('is_admin', $cliente->is_admin) == 1 ? 'selected' : '' }}>Sim</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- TAB STATUS --}}
            <div x-show="activeTab === 'status'" class="space-y-4" x-cloak>
                <div class="flex items-start gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <input type="checkbox" name="bloqueado" id="bloqueado" value="1"
                           {{ old('bloqueado', $cliente->bloqueado) ? 'checked' : '' }}
                           class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500 mt-1">
                    <div>
                        <label for="bloqueado" class="block text-sm font-bold text-gray-700">Bloquear Cliente</label>
                        <p class="text-xs text-gray-400 mt-0.5">Desabilita o acesso do cliente à plataforma, impedindo compras e visualização de sacolinhas.</p>
                    </div>
                </div>
                <div class="p-4 rounded-xl border border-gray-150 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Status Atual do Cliente</p>
                        <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ $cliente->status }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold 
                        {{ $cliente->bloqueado ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        <i class="fas fa-{{ $cliente->bloqueado ? 'lock' : 'check-circle' }}"></i> {{ $cliente->status }}
                    </span>
                </div>
            </div>

        </div>

        {{-- Botões de Envio --}}
        <div class="flex justify-between gap-3 mt-8 pt-4 border-t border-gray-100">
            <a href="{{ route('admin.clientes.show', $cliente) }}" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                Cancelar
            </a>
            <button type="submit" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-sm">
                <i class="fas fa-save mr-1.5"></i> Atualizar Cliente
            </button>
        </div>
    </form>
</div>

{{-- Máscaras e Validação (Preservado) --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const maskCpf = (value) => {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        };

        const maskPhone = (value) => {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(\d{4})-(\d)(\d{4})/, '$1$2-$3')
                .replace(/(-\d{4})\d+?$/, '$1');
        };

        const maskCep = (value) => {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        };

        document.querySelectorAll('.mask-cpf').forEach(input => {
            input.addEventListener('input', e => {
                e.target.value = maskCpf(e.target.value);
            });
        });

        document.querySelectorAll('.mask-phone').forEach(input => {
            input.addEventListener('input', e => {
                e.target.value = maskPhone(e.target.value);
            });
        });

        document.querySelectorAll('.mask-cep').forEach(input => {
            input.addEventListener('input', e => {
                e.target.value = maskCep(e.target.value);
            });
        });

        // Busca CEP
        document.getElementById('cep')?.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('endereco').value = data.logradouro || '';
                            document.getElementById('bairro').value = data.bairro || '';
                            document.getElementById('cidade').value = data.localidade || '';
                            document.getElementById('estado').value = data.uf || '';
                        }
                    })
                    .catch(error => console.error('Erro ao buscar CEP:', error));
            }
        });

        // Remover @ das redes sociais
        document.getElementById('instagram')?.addEventListener('input', function() {
            this.value = this.value.replace('@', '');
        });

        document.getElementById('tiktok')?.addEventListener('input', function() {
            this.value = this.value.replace('@', '');
        });
    });
</script>
@endsection