@extends('layouts.app')

@section('title', 'Configurações Fiscais (NF-e)')
@section('brand_icon', 'fas fa-file-invoice-dollar')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-file-invoice text-purple-600"></i>
                Configurações Fiscais e Emissão de NF-e
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Configure o Certificado Digital A1 e os dados da sua empresa para emitir Notas Fiscais gratuitamente via NFePHP.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.pedido.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md shadow-sm transition text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar aos Pedidos
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                <span class="text-green-800 font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                <span class="text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                <span class="text-red-800 font-bold">Por favor, corrija os seguintes erros:</span>
            </div>
            <ul class="list-disc pl-5 text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fiscal.salvar') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna 1 & 2: Dados da Empresa e Parâmetros --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Card 1: Dados do Emitente --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-building text-blue-500"></i> Dados da Empresa (Emitente)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Razão Social</label>
                            <input type="text" name="razao_social" value="{{ old('razao_social', $brecho->razao_social ?? $brecho->nome) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">CNPJ</label>
                            <input type="text" name="documento" value="{{ old('documento', $brecho->documento) }}" required placeholder="00.000.000/0000-00"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Inscrição Estadual (IE)</label>
                            <input type="text" name="inscricao_estadual" value="{{ old('inscricao_estadual', $brecho->inscricao_estadual) }}" required placeholder="Ex: 258999888"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Regime Tributário</label>
                            <select name="regime_tributario" class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                                <option value="1" {{ old('regime_tributario', $brecho->regime_tributario ?? 1) == 1 ? 'selected' : '' }}>
                                    1 - Simples Nacional (Microempresa / EPP)
                                </option>
                                <option value="4" {{ old('regime_tributario', $brecho->regime_tributario) == 4 ? 'selected' : '' }}>
                                    4 - MEI (Microempreendedor Individual)
                                </option>
                                <option value="3" {{ old('regime_tributario', $brecho->regime_tributario) == 3 ? 'selected' : '' }}>
                                    3 - Regime Normal (Lucro Presumido / Real)
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Endereço Fiscal --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-red-500"></i> Endereço Fiscal
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Logradouro / Rua</label>
                            <input type="text" name="logradouro" value="{{ old('logradouro', $brecho->logradouro) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Número</label>
                            <input type="text" name="numero_endereco" value="{{ old('numero_endereco', $brecho->numero_endereco) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Complemento</label>
                            <input type="text" name="complemento" value="{{ old('complemento', $brecho->complemento) }}" placeholder="Sala, Bloco..."
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Bairro</label>
                            <input type="text" name="bairro" value="{{ old('bairro', $brecho->bairro) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">CEP</label>
                            <input type="text" name="cep" value="{{ old('cep', $brecho->cep) }}" required placeholder="00000-000"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Município / Cidade</label>
                            <input type="text" name="municipio" value="{{ old('municipio', $brecho->municipio) }}" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">UF (Estado)</label>
                            <input type="text" name="uf" value="{{ old('uf', $brecho->uf ?? 'SC') }}" required maxlength="2"
                                   class="w-full uppercase border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Código IBGE Município</label>
                            <input type="text" name="codigo_municipio" value="{{ old('codigo_municipio', $brecho->codigo_municipio) }}" placeholder="Ex: 4202305"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                            <span class="text-xs text-gray-400">Preenchido auto pelo CEP se vazio.</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Coluna 3: Certificado Digital e Parâmetros de Emissão --}}
            <div class="space-y-6">

                {{-- Card 3: Certificado Digital A1 (.pfx) --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-certificate text-yellow-500"></i> Certificado Digital A1
                    </h2>

                    <div class="space-y-4">
                        @if (!empty($brecho->certificado_path))
                            <div class="bg-green-50 border border-green-200 rounded p-3 text-xs text-green-800 flex items-center gap-2">
                                <i class="fas fa-check-circle text-green-600 text-base"></i>
                                <div>
                                    <span class="font-bold">Certificado A1 Carregado!</span>
                                    <p class="text-gray-500">{{ basename($brecho->certificado_path) }}</p>
                                </div>
                            </div>
                        @else
                            <div class="bg-amber-50 border border-amber-200 rounded p-3 text-xs text-amber-800 flex items-center gap-2">
                                <i class="fas fa-info-circle text-amber-600 text-base"></i>
                                <span>Nenhum certificado A1 configurado ainda.</span>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                {{ !empty($brecho->certificado_path) ? 'Substituir Certificado (.pfx)' : 'Arquivo do Certificado (.pfx)' }}
                            </label>
                            <input type="file" name="certificado_file" accept=".pfx,.bin"
                                   class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Senha do Certificado</label>
                            <input type="password" name="certificado_senha" value="{{ old('certificado_senha', $brecho->certificado_senha) }}" placeholder="Senha do arquivo .pfx"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                        </div>
                    </div>
                </div>

                {{-- Card 4: Parâmetros da NF-e --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                        <i class="fas fa-cog text-gray-600"></i> Parâmetros da NF-e
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Ambiente de Emissão</label>
                            <select name="nfe_ambiente" class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                                <option value="2" {{ old('nfe_ambiente', $brecho->nfe_ambiente ?? 2) == 2 ? 'selected' : '' }}>
                                    2 - Homologação (Ambiente de Testes da SEFAZ)
                                </option>
                                <option value="1" {{ old('nfe_ambiente', $brecho->nfe_ambiente) == 1 ? 'selected' : '' }}>
                                    1 - Produção (Notas Reais com Validade Jurídica)
                                </option>
                            </select>
                            <span class="text-xs text-gray-400 mt-1 block">Mantenha em Homologação até validar seu primeiro teste.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Série</label>
                                <input type="text" name="nfe_serie" value="{{ old('nfe_serie', $brecho->nfe_serie ?? '1') }}" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Último Número</label>
                                <input type="number" name="nfe_ultimo_numero" value="{{ old('nfe_ultimo_numero', $brecho->nfe_ultimo_numero ?? 0) }}" required min="0"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-purple-500 focus:ring focus:ring-purple-200 text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botão Salvar --}}
                <div class="pt-2">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-md shadow-md transition duration-300 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Salvar Configurações Fiscais
                    </button>
                </div>

            </div>

        </div>
    </form>
</div>
@endsection
