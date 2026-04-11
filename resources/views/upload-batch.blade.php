{{-- resources/views/upload-batch.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-6">Upload em Lote de Fotos</h1>
    
    <!-- Abas -->
    <div class="flex gap-2 mb-6">
        <button id="tabUpload" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium">
            Upload
        </button>
        <button id="tabOrphan" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300">
            Fotos Órfãs (<span id="orphanCount">0</span>)
        </button>
    </div>

    <!-- Aba Upload -->
    <div id="uploadSection">
        <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer hover:border-blue-500 transition">
            <input type="file" id="photos" name="photos[]" multiple accept="image/*" class="hidden">
            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
            <p class="text-gray-600">Arraste fotos aqui ou clique para selecionar</p>
            <p class="text-sm text-gray-400 mt-2">Suporta: JPG, PNG, HEIC, WEBP (máx. 50MB cada)</p>
        </div>

        <div id="summary" class="mt-6 p-4 bg-gray-100 rounded-lg hidden">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium">Progresso do Upload</span>
                <span id="progressText" class="text-sm">0 / 0</span>
            </div>
            <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-500">
                <span id="successCount">✅ 0 enviadas</span>
                <span id="errorCount">❌ 0 erros</span>
            </div>
        </div>

        <button id="btnUpload" class="mt-6 bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hidden hover:bg-blue-700 transition">
            Enviar <span id="count">0</span> Fotos
        </button>
    </div>

    <!-- Aba Fotos Órfãs -->
    <div id="orphanSection" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <p class="text-gray-600">Fotos aguardando catalogação</p>
            <button id="btnRefresh" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-sync-alt"></i> Atualizar
            </button>
        </div>
        
        <div id="orphanList" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Fotos carregadas via AJAX -->
        </div>
        
        <div id="noOrphans" class="text-center py-12 text-gray-500 hidden">
            <i class="fas fa-images text-4xl mb-2"></i>
            <p>Nenhuma foto órfã</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos
    const tabUpload = document.getElementById('tabUpload');
    const tabOrphan = document.getElementById('tabOrphan');
    const uploadSection = document.getElementById('uploadSection');
    const orphanSection = document.getElementById('orphanSection');
    const dropzone = document.getElementById('dropzone');
    const input = document.getElementById('photos');
    const summary = document.getElementById('summary');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const successCount = document.getElementById('successCount');
    const errorCountEl = document.getElementById('errorCount');
    const btnUpload = document.getElementById('btnUpload');
    const countSpan = document.getElementById('count');
    const orphanCount = document.getElementById('orphanCount');
    const orphanList = document.getElementById('orphanList');
    const noOrphans = document.getElementById('noOrphans');
    const btnRefresh = document.getElementById('btnRefresh');

    let fileList = null;
    let totalFiles = 0;
    let uploadedCount = 0;
    let errorCount = 0;

    // 
    // NAVEGAÇÃO POR ABAS
    // 
    tabUpload.addEventListener('click', () => {
        tabUpload.classList.add('bg-blue-600', 'text-white');
        tabUpload.classList.remove('bg-gray-200', 'text-gray-700');
        tabOrphan.classList.remove('bg-blue-600', 'text-white');
        tabOrphan.classList.add('bg-gray-200', 'text-gray-700');
        uploadSection.classList.remove('hidden');
        orphanSection.classList.add('hidden');
    });

    tabOrphan.addEventListener('click', () => {
        tabOrphan.classList.add('bg-blue-600', 'text-white');
        tabOrphan.classList.remove('bg-gray-200', 'text-gray-700');
        tabUpload.classList.remove('bg-blue-600', 'text-white');
        tabUpload.classList.add('bg-gray-200', 'text-gray-700');
        orphanSection.classList.remove('hidden');
        uploadSection.classList.add('hidden');
        loadOrphanPhotos();
    });

    btnRefresh.addEventListener('click', loadOrphanPhotos);

    // 
    // CARREGAR FOTOS ÓRFÃS
    // 
    async function loadOrphanPhotos() {
        try {
            const resp = await fetch('{{ route("orphan.photos") }}');
            const data = await resp.json();
            
            orphanCount.textContent = data.count;
            
            if (data.photos.length === 0) {
                orphanList.classList.add('hidden');
                noOrphans.classList.remove('hidden');
                return;
            }
            
            orphanList.classList.remove('hidden');
            noOrphans.classList.add('hidden');
            
            orphanList.innerHTML = data.photos.map(photo => `
                <div class="relative group">
                    <img src="/storage/${photo.thumbnail_url || photo.url}" 
                         alt="${photo.alt_text || ''}"
                         class="w-full h-32 object-cover rounded-lg border">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition rounded-lg flex items-center justify-center">
                        <button onclick="deletePhoto(${photo.id})" 
                                class="opacity-0 group-hover:opacity-100 text-white bg-red-600 px-2 py-1 rounded text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1 truncate">ID: ${photo.id} - ${photo.metadata?.original_name || ''}</p>
                </div>
            `).join('');
            
        } catch (e) {
            console.error('Erro ao carregar fotos:', e);
        }
    }

    // Deletar foto
    window.deletePhoto = async function(id) {
        if (!confirm('Excluir esta foto?')) return;
        
        try {
            const resp = await fetch(`/orphan-photos/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            
            if (resp.ok) {
                loadOrphanPhotos();
            }
        } catch (e) {
            alert('Erro ao excluir foto');
        }
    };

    // 
    // UPLOAD
    // 
    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-500', 'bg-blue-50');
        handleFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', () => handleFiles(input.files));

    function handleFiles(files) {
        fileList = files;
        totalFiles = files.length;
        uploadedCount = 0;
        errorCount = 0;
        
        summary.classList.remove('hidden');
        btnUpload.classList.remove('hidden');
        
        countSpan.textContent = totalFiles;
        updateProgress();
    }

    function updateProgress() {
        const done = uploadedCount + errorCount;
        const percent = totalFiles > 0 ? (done / totalFiles) * 100 : 0;
        
        progressBar.style.width = percent + '%';
        progressText.textContent = `${done} / ${totalFiles}`;
        successCount.textContent = `✅ ${uploadedCount} enviadas`;
        errorCountEl.textContent = `❌ ${errorCount} erros`;
    }

    async function uploadFile(file) {
        const fd = new FormData();
        fd.append('photos[]', file);
        
        const resp = await fetch('{{ route("upload.batch") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: fd,
        });
        
        return resp.json();
    }

    async function processQueue() {
        btnUpload.disabled = true;
        btnUpload.textContent = 'Enviando...';
        progressBar.classList.add('animate-pulse');
        
        for (let i = 0; i < totalFiles; i++) {
            try {
                const data = await uploadFile(fileList[i]);
                
                if (data.uploaded > 0) {
                    uploadedCount++;
                } else {
                    errorCount++;
                }
            } catch (e) {
                errorCount++;
            }
            
            updateProgress();
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        
        progressBar.classList.remove('animate-pulse');
        
        if (errorCount === 0) {
            progressBar.classList.remove('from-blue-500', 'to-blue-600');
            progressBar.classList.add('bg-green-500');
            alert(`✅ Todas as ${uploadedCount} fotos foram enviadas!`);
        } else {
            alert(`Envio concluído:\n✅ ${uploadedCount} sucessos\n❌ ${errorCount} erros`);
        }
        
        // Atualizar contador de órfãs
        orphanCount.textContent = parseInt(orphanCount.textContent) + uploadedCount;
        
        btnUpload.disabled = false;
        btnUpload.textContent = 'Enviar Novamente';
    }

    btnUpload.addEventListener('click', processQueue);

    // Carregar contador inicial
    loadOrphanPhotos();
});
</script>
@endsection