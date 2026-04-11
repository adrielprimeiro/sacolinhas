@csrf

<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Usuário (user_id)</label>
        <input type="number" name="user_id"
               value="{{ old('user_id', $classificacao_financeira->user_id ?? auth()->id()) }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('user_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
        <input type="text" name="nome"
               value="{{ old('nome', $classificacao_financeira->nome ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('nome') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Código Contábil</label>
        <input type="text" name="codigo_contabil"
               value="{{ old('codigo_contabil', $classificacao_financeira->codigo_contabil ?? '') }}"
               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('codigo_contabil') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Natureza</label>
            <select name="tipo_natureza"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="receita" @selected(old('tipo_natureza', $classificacao_financeira->tipo_natureza ?? '')==='receita')>Receita</option>
                <option value="despesa" @selected(old('tipo_natureza', $classificacao_financeira->tipo_natureza ?? '')==='despesa')>Despesa</option>
            </select>
            @error('tipo_natureza') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nível</label>
            <select name="nivel"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="sintetico" @selected(old('nivel', $classificacao_financeira->nivel ?? '')==='sintetico')>Sintético</option>
                <option value="analitico" @selected(old('nivel', $classificacao_financeira->nivel ?? '')==='analitico')>Analítico</option>
            </select>
            @error('nivel') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Pai (id_pai)</label>
        <select name="id_pai"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">(sem pai)</option>

            @foreach($pais as $p)
                @php
                    $selectedPai = old('id_pai', $classificacao_financeira->id_pai ?? '');
                @endphp

                <option value="{{ $p->id }}" @selected((string)$selectedPai === (string)$p->id)>
                    {{ $p->codigo_contabil }} - {{ $p->nome }} (user {{ $p->user_id }})
                </option>
            @endforeach
        </select>
        @error('id_pai') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Área Finalidade</label>
            <select name="area_finalidade"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach(['marketing','vendas','producao','administrativo','rh','financeiro','geral','outros'] as $a)
                    <option value="{{ $a }}" @selected(old('area_finalidade', $classificacao_financeira->area_finalidade ?? 'geral')===$a)>
                        {{ ucfirst($a) }}
                    </option>
                @endforeach
            </select>
            @error('area_finalidade') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Frequência</label>
            <select name="frequencia"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">(vazio)</option>
                <option value="regular" @selected(old('frequencia', $classificacao_financeira->frequencia ?? '')==='regular')>Regular</option>
                <option value="extraordinaria" @selected(old('frequencia', $classificacao_financeira->frequencia ?? '')==='extraordinaria')>Extraordinária</option>
            </select>
            @error('frequencia') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
        <textarea name="descricao" rows="4"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('descricao', $classificacao_financeira->descricao ?? '') }}</textarea>
        @error('descricao') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>
</div>