@extends('layouts.app')

@section('title', 'Novo Grupo')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Novo Grupo 👥</h1>
            <p class="text-gray-500 text-sm mt-0.5">Preencha os dados abaixo para criar a equipe.</p>
        </div>

        <form method="POST" action="{{ route('admin.grupos.store') }}" class="p-8 space-y-6">
            @csrf
            
            <div>
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nome do Grupo</label>
                <input type="text" name="nome" required 
                       class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold transition @error('nome') border-red-500 @enderror" 
                       value="{{ old('nome') }}"
                       placeholder="Ex: Equipe Alpha">
                @error('nome')
                    <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="relative" id="lider-combobox">
                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Líder (Opcional)</label>
                <div class="relative">
                    <input type="text" id="lider-search" autocomplete="off"
                           placeholder="Buscar por nome ou email..."
                           class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-100 rounded-2xl focus:border-indigo-400 focus:ring-0 outline-none text-gray-900 font-bold transition @error('lider_id') border-red-500 @enderror"
                           oninput="filtrarLideres(this.value)"
                           onfocus="abrirDropdown()">
                    <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 pointer-events-none"></i>
                </div>
                
                <div id="lider-dropdown" 
                     class="hidden absolute left-0 right-0 mt-2 bg-white border-2 border-gray-100 rounded-2xl shadow-xl z-50 max-h-60 overflow-y-auto overflow-x-hidden">
                    <div class="p-2 border-b border-gray-50">
                        <div class="cursor-pointer p-3 hover:bg-indigo-50 rounded-xl transition flex items-center gap-2"
                             onclick="selecionarLider('', 'Sem líder definido')">
                            <i class="fas fa-user-slash text-gray-400 text-xs"></i>
                            <span class="text-sm font-bold text-gray-500 italic">Sem líder definido</span>
                        </div>
                    </div>
                    @foreach($usuarios as $user)
                        <div class="lider-option cursor-pointer p-3 hover:bg-indigo-50 rounded-xl transition flex items-center gap-3 border-b border-gray-50 last:border-0"
                             data-id="{{ $user->id }}"
                             data-nome="{{ $user->name }}"
                             data-email="{{ $user->email }}"
                             onclick="selecionarLider('{{ $user->id }}', '{{ addslashes($user->name) }}')">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-600">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-gray-900 leading-tight">{{ $user->name }}</div>
                                <div class="text-[10px] text-gray-400">{{ $user->email }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <input type="hidden" name="lider_id" id="lider_id_input" value="{{ old('lider_id') }}">
                @error('lider_id')
                    <p class="mt-1 text-xs text-red-600 font-bold">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" 
                        class="flex-1 bg-indigo-600 text-white px-6 py-3.5 rounded-2xl hover:bg-indigo-700 font-black uppercase text-xs tracking-widest transition shadow-lg shadow-indigo-100">
                    <i class="fas fa-check mr-2"></i> Criar Grupo
                </button>
                <a href="{{ route('admin.grupos.index') }}" 
                   class="flex-1 bg-gray-100 text-gray-500 px-6 py-3.5 rounded-2xl hover:bg-gray-200 font-black uppercase text-xs tracking-widest transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function abrirDropdown() {
        document.getElementById('lider-dropdown').classList.remove('hidden');
    }

    function filtrarLideres(query) {
        abrirDropdown();
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.lider-option').forEach(opt => {
            const nome = opt.dataset.nome.toLowerCase();
            const email = opt.dataset.email.toLowerCase();
            opt.style.display = (nome.includes(q) || email.includes(q)) ? '' : 'none';
        });
    }

    function selecionarLider(id, nome) {
        document.getElementById('lider-search').value = nome;
        document.getElementById('lider_id_input').value = id;
        document.getElementById('lider-dropdown').classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        const combobox = document.getElementById('lider-combobox');
        if (combobox && !combobox.contains(e.target)) {
            document.getElementById('lider-dropdown').classList.add('hidden');
        }
    });

    @if(old('lider_id'))
        @php $u = $usuarios->where('id', old('lider_id'))->first(); @endphp
        @if($u) selecionarLider('{{ $u->id }}', '{{ addslashes($u->name) }}'); @endif
    @endif
</script>
<style>
    #lider-dropdown::-webkit-scrollbar { display: none; }
    #lider-dropdown { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endpush
@endsection