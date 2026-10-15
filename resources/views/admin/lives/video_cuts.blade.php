@extends('layouts.app')

@section('title', 'Cortes de Vídeo da Live')

@section('content')
<div class="container mx-auto p-4 sm:p-6 max-w-7xl">
    
    @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-check-circle text-emerald-600 text-xl shrink-0"></i>
            <div class="font-bold text-sm leading-snug">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
            <i class="fas fa-exclamation-triangle text-red-600 text-xl shrink-0"></i>
            <div class="font-bold text-sm leading-snug">{{ session('error') }}</div>
        </div>
    @endif

    <!-- Cálculo dos Status das Etapas (Passos 1 a 5) -->
    @php
        $step1Done = !empty($recordingUrl);
        $step2Done = !empty($transcriptionData) && count($transcriptionData) > 0;
        $step3Done = ($stats['total_items'] > 0 && $stats['items_with_cuts'] >= $stats['total_items']);
        $step3Partial = ($stats['items_with_cuts'] > 0 && $stats['items_with_cuts'] < $stats['total_items']);
        $step4Done = ($stats['total_items'] > 0 && $stats['items_rendered'] >= $stats['total_items']);
        $step4Partial = ($stats['items_rendered'] > 0 && $stats['items_rendered'] < $stats['total_items']);
        $step5Done = ($stats['total_items'] > 0 && ($stats['items_youtube'] ?? 0) >= $stats['total_items']);
        $step5Partial = (($stats['items_youtube'] ?? 0) > 0);
    @endphp

    <!-- Top Header Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <a href="{{ route('admin.live-chat.dashboard', ['live_id' => $live->id]) }}" class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-700 transition shadow-xs shrink-0" title="Voltar ao Painel da Live">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-800 border border-indigo-200">
                        Cortes & Vídeos
                    </span>
                    <span class="text-xs text-gray-500 font-bold">
                        {{ $live->data ? $live->data->format('d/m/Y') : 'Data n/d' }} &bull; {{ $live->tipo_live_formatado }}
                    </span>
                    @if($live->ativo)
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse flex items-center gap-1 shadow-xs">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> AO VIVO
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-1.5">
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 flex items-center gap-2">
                        <i class="fas fa-film text-indigo-600"></i>
                        <span>Fatiador de Vídeos</span>
                    </h1>

                    <!-- Seletor de Live -->
                    @if(isset($lives) && count($lives) > 0)
                        <div class="flex items-center gap-2 bg-indigo-50/70 border border-indigo-200 rounded-xl px-2.5 py-1 shadow-xs">
                            <label for="live-selector-cuts" class="text-xs font-black text-indigo-900 flex items-center gap-1">
                                <i class="fas fa-video text-indigo-600"></i> Live:
                            </label>
                            <select 
                                id="live-selector-cuts" 
                                onchange="if(this.value) window.location.href = '/admin/lives/' + this.value + '/cortes'"
                                class="text-xs font-black text-gray-900 bg-white border border-indigo-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none cursor-pointer max-w-[200px] sm:max-w-[260px] truncate"
                            >
                                @foreach($lives as $l)
                                    <option value="{{ $l->id }}" {{ $l->id == $live->id ? 'selected' : '' }}>
                                        #{{ $l->id }} - {{ $l->nome ?: 'Live de ' . date('d/m', strtotime($l->created_at)) }} {{ $l->ativo ? '🔴' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Badges / Stats & Ação Principal -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="bg-gray-50 border border-gray-200 px-3.5 py-1.5 rounded-xl flex items-center gap-3 shadow-xs">
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-gray-500">Itens</div>
                    <div id="stat-total-items" class="text-sm font-black text-gray-900">{{ $stats['total_items'] }}</div>
                </div>
                <div class="w-px h-5 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-teal-700">Minutados (IA)</div>
                    <div id="stat-items-with-cuts" class="text-sm font-black text-teal-800">{{ $stats['items_with_cuts'] }}</div>
                </div>
                <div class="w-px h-5 bg-gray-200"></div>
                <div class="text-center">
                    <div class="text-[10px] uppercase font-bold text-green-700">Vídeos Prontos</div>
                    <div id="stat-items-rendered" class="text-sm font-black text-green-800">{{ $stats['items_rendered'] }}</div>
                </div>
            </div>

            <!-- Filtro de Início por Código -->
            <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 shadow-xs" title="Defina um código para processar apenas dele em diante (ex: 20)">
                <span class="text-[10px] uppercase font-extrabold text-gray-500 whitespace-nowrap">A partir do #:</span>
                <input type="number" id="global-start-code" placeholder="Tudo" min="1" class="w-14 px-1 py-0.5 text-xs font-black text-center bg-gray-50 border border-gray-300 rounded-lg focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-gray-900">
            </div>

            <!-- PASSOS INDIVIDUAIS (1 A 4) -->
            <div class="inline-flex rounded-xl shadow-xs border border-gray-200 bg-gray-100 p-1 gap-1 flex-wrap">
                <!-- 1. Baixar Vídeo -->
                <button type="button" onclick="openStep1Modal()" id="btn-step1-header" class="px-2.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs" style="{{ $step1Done ? 'background-color: #16a34a; color: #ffffff !important;' : 'background-color: #d97706; color: #ffffff !important;' }}" title="Passo 1: Baixar ou carregar gravação da live">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black" style="background-color: rgba(255,255,255,0.25); color: #ffffff !important;">1</span>
                    <span style="color: #ffffff !important; font-weight: 800;">Baixar Vídeo</span>
                    @if($step1Done) <i class="fas fa-check text-white text-[10px]"></i> @endif
                </button>

                <!-- 2. Transcrever -->
                <button type="button" onclick="openStep2Modal()" id="btn-step2-header" class="px-2.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs" style="{{ $step2Done ? 'background-color: #16a34a; color: #ffffff !important;' : 'background-color: #0d9488; color: #ffffff !important;' }}" title="Passo 2: Transcrever áudio com Whisper IA">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black" style="background-color: rgba(255,255,255,0.25); color: #ffffff !important;">2</span>
                    <span style="color: #ffffff !important; font-weight: 800;">Transcrever</span>
                    @if($step2Done) <i class="fas fa-check text-white text-[10px]"></i> @endif
                </button>

                <!-- 3. Minutar IA -->
                <button type="button" onclick="openStep3Modal()" id="btn-step3-header" class="px-2.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs" style="{{ $step3Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step3Partial ? 'background-color: #9333ea; color: #ffffff !important;' : 'background-color: #6b7280; color: #ffffff !important;') }}" title="Passo 3: Minutar peças com Severino IA">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black" style="background-color: rgba(255,255,255,0.25); color: #ffffff !important;">3</span>
                    <span style="color: #ffffff !important; font-weight: 800;">Minutar IA</span>
                    @if($step3Done) <i class="fas fa-check text-white text-[10px]"></i> @endif
                </button>

                <!-- 4. Gerar Cortes -->
                <button type="button" onclick="openStep4Modal()" id="btn-step4-header" class="px-2.5 py-1.5 rounded-lg text-xs font-extrabold flex items-center gap-1.5 transition active:scale-95 cursor-pointer shadow-xs" style="{{ $step4Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step4Partial ? 'background-color: #4f46e5; color: #ffffff !important;' : 'background-color: #6b7280; color: #ffffff !important;') }}" title="Passo 4: Gerar cortes MP4 com FFmpeg">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-black" style="background-color: rgba(255,255,255,0.25); color: #ffffff !important;">4</span>
                    <span style="color: #ffffff !important; font-weight: 800;">Gerar Cortes</span>
                    @if($step4Done) <i class="fas fa-check text-white text-[10px]"></i> @endif
                </button>
            </div>

            @if(!empty($youtubeAccount))
                <button type="button" onclick="batchUploadToYouTube()" id="btn-youtube-batch" class="bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #dc2626; color: #ffffff !important;" title="Publicar todas as peças não-vendidas no canal '{{ $youtubeAccount->channel_name }}'">
                    <i class="fab fa-youtube text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Publicar Shorts</span>
                </button>
            @else
                <a href="{{ route('admin.youtube.connect') }}" class="bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #dc2626; color: #ffffff !important;" title="Conectar Canal do YouTube para postar Shorts automaticamente">
                    <i class="fab fa-youtube text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Conectar YouTube</span>
                </a>
            @endif

            @if(!empty($tiktokAccount))
                <button type="button" onclick="batchUploadToTikTok()" id="btn-tiktok-batch" class="bg-slate-900 hover:bg-black text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #0f172a; color: #ffffff !important;" title="Publicar todas as peças não-vendidas no TikTok '{{ $tiktokAccount->channel_name }}'">
                    <i class="fab fa-tiktok text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Publicar TikTok</span>
                </button>
            @else
                <a href="{{ route('admin.tiktok.connect') }}" class="bg-slate-900 hover:bg-black text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #0f172a; color: #ffffff !important;" title="Conectar Conta do TikTok para postar vídeos automaticamente">
                    <i class="fab fa-tiktok text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Conectar TikTok</span>
                </a>
            @endif

            @if(!empty($instagramAccount))
                <button type="button" onclick="batchUploadToInstagram()" id="btn-instagram-batch" class="hover:opacity-95 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #ffffff !important;" title="Publicar todas as peças não-vendidas no Instagram Reels '{{ $instagramAccount->channel_name }}'">
                    <i class="fab fa-instagram text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Publicar Instagram</span>
                </button>
            @else
                <a href="{{ route('admin.instagram.connect') }}" class="hover:opacity-95 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #ffffff !important;" title="Conectar Conta do Instagram para postar Reels automaticamente">
                    <i class="fab fa-instagram text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Conectar Instagram</span>
                </a>
            @endif

            <button type="button" onclick="triggerAutoProcess()" id="btn-auto-process" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold px-3 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" title="Executar todo o fluxo de 1 a 4 automaticamente">
                <i class="fas fa-magic text-white"></i>
                <span class="text-white font-black">Auto-Processar Tudo</span>
            </button>
        </div>
    </div>

    <!-- LINHA DO TEMPO COMPACTA (TIMELINE DE PONTOS) -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-3 sm:p-4 mb-6" style="background-color: #ffffff;">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            
            <!-- TRACK DA LINHA DO TEMPO -->
            <div class="flex-1 flex flex-wrap sm:flex-nowrap items-center justify-between gap-2 sm:gap-3">
                
                <!-- PONTO 1: VÍDEO -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="openStep1Modal()" title="Passo 1: Baixar ou carregar gravação da live">
                    <div id="stepper-badge-1" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step1Done ? 'background-color: #16a34a; color: #ffffff !important;' : 'background-color: #d97706; color: #ffffff !important;' }}">
                        {!! $step1Done ? '<i class="fas fa-check"></i>' : '1' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">1. Vídeo da Live</div>
                        <div id="stepper-status-1" class="text-[11px] font-semibold" style="{{ $step1Done ? 'color: #15803d;' : 'color: #b45309;' }}">
                            {{ $step1Done ? '✓ Gravado' : 'Pendente' }}
                        </div>
                    </div>
                </div>

                <!-- LINHA CONECTORA 1-2 -->
                <div id="stepper-line-1" class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step1Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 2: TRANSCRIÇÃO -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="openStep2Modal()" title="Passo 2: Transcrever áudio com Whisper IA">
                    <div id="stepper-badge-2" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step2Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step1Done ? 'background-color: #0d9488; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step2Done ? '<i class="fas fa-check"></i>' : '2' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">2. Transcrição</div>
                        <div id="stepper-status-2" class="text-[11px] font-semibold" style="{{ $step2Done ? 'color: #15803d;' : 'color: #0f766e;' }}">
                            {{ $step2Done ? '✓ ' . count($transcriptionData) . ' falas' : 'Pendente' }}
                        </div>
                    </div>
                </div>

                <!-- LINHA CONECTORA 2-3 -->
                <div id="stepper-line-2" class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step2Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 3: MINUTAGEM IA -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="openStep3Modal()" title="Passo 3: Minutar peças com Severino IA">
                    <div id="stepper-badge-3" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step3Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step3Partial ? 'background-color: #9333ea; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step3Done ? '<i class="fas fa-check"></i>' : '3' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">3. Minutagem IA</div>
                        <div id="stepper-status-3" class="text-[11px] font-semibold" style="{{ $step3Done ? 'color: #15803d;' : ($step3Partial ? 'color: #7e22ce;' : 'color: #4b5563;') }}">
                            {{ $stats['items_with_cuts'] }}/{{ $stats['total_items'] }} minutados
                        </div>
                        @if(($stats['items_without_cuts'] ?? 0) > 0)
                            <button type="button" onclick="event.stopPropagation(); setItemsFilter('unminuted')" class="text-[10px] font-black px-1.5 py-0.5 rounded-md mt-0.5 inline-flex items-center gap-1 cursor-pointer transition active:scale-95 shadow-2xs" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;" title="Filtrar apenas os itens que não foram minutados">
                                <i class="fas fa-exclamation-circle text-amber-600"></i> Ver {{ $stats['items_without_cuts'] }} pendente(s)
                            </button>
                        @endif
                    </div>
                </div>

                <!-- LINHA CONECTORA 3-4 -->
                <div id="stepper-line-3" class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step3Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 4: FATIAMENTO FFmpeg -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="openStep4Modal()" title="Passo 4: Gerar cortes MP4 com FFmpeg">
                    <div id="stepper-badge-4" class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step4Done ? 'background-color: #16a34a; color: #ffffff !important;' : ($step4Partial ? 'background-color: #4f46e5; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        {!! $step4Done ? '<i class="fas fa-check"></i>' : '4' !!}
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">4. Fatiamento</div>
                        <div id="stepper-status-4" class="text-[11px] font-semibold" style="{{ $step4Done ? 'color: #15803d;' : ($step4Partial ? 'color: #4338ca;' : 'color: #4b5563;') }}">
                            {{ $stats['items_rendered'] }}/{{ $stats['total_items'] }} prontos
                        </div>
                    </div>
                </div>

                <!-- LINHA CONECTORA 4-5 -->
                <div id="stepper-line-4" class="hidden sm:block flex-1 h-0.5 mx-1" style="{{ $step4Done ? 'background-color: #16a34a;' : 'background-color: #e5e7eb;' }}"></div>

                <!-- PONTO 5: YOUTUBE SHORTS -->
                <div class="flex items-center gap-2 cursor-pointer group p-1.5 rounded-xl hover:bg-gray-50 transition" onclick="batchUploadToYouTube()" title="Clique para gerenciar publicações no YouTube Shorts">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black shrink-0 shadow-xs"
                         style="{{ $step5Done ? 'background-color: #dc2626; color: #ffffff !important;' : ($step5Partial ? 'background-color: #ef4444; color: #ffffff !important;' : 'background-color: #9ca3af; color: #ffffff !important;') }}">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div>
                        <div class="text-xs font-bold leading-tight" style="color: #111827;">5. YouTube Shorts</div>
                        <div class="text-[11px] font-semibold" style="{{ $step5Partial ? 'color: #b91c1c;' : 'color: #4b5563;' }}">
                            {{ $stats['items_youtube'] ?? 0 }}/{{ $stats['total_items'] }} no ar
                        </div>
                    </div>
                </div>

            </div>

            <!-- AÇÕES RÁPIDAS NA PONTA DIREITA -->
            <div class="flex items-center gap-2 pt-2 lg:pt-0 lg:pl-3 lg:border-l lg:border-gray-200 shrink-0">
                @if(!empty($youtubeAccount))
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold border" style="background-color: #fef2f2; color: #991b1b; border-color: #fecaca;" title="Canal YouTube conectado: {{ $youtubeAccount->channel_name }}">
                        <i class="fab fa-youtube text-red-600"></i>
                        <span class="max-w-[120px] truncate">{{ $youtubeAccount->channel_name }}</span>
                    </div>
                @endif

                @if(!empty($tiktokAccount))
                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-bold border" style="background-color: #f1f5f9; color: #0f172a; border-color: #cbd5e1;" title="Conta TikTok conectada: {{ $tiktokAccount->channel_name }}">
                        <i class="fab fa-tiktok text-slate-900"></i>
                        <span class="max-w-[120px] truncate">{{ $tiktokAccount->channel_name }}</span>
                    </div>
                @endif

                <a href="{{ route('admin.lives.cortes.social.live', ['liveId' => $live->id]) }}" 
                   class="inline-flex items-center gap-1.5 font-bold px-3 py-1.5 rounded-xl text-xs transition shadow-xs"
                   style="background-color: #fdf2f8; color: #be185d !important; border: 1px solid #fbcfe8;">
                    <i class="fas fa-film text-pink-600"></i>
                    <span>Feed Redes</span>
                </a>
            </div>

        </div>
    </div>

    <!-- BANNER DE PROGRESSO IA ASSÍNCRONO -->
    <div id="ai-process-progress-box" class="hidden mb-6 bg-indigo-50 text-indigo-950 rounded-2xl p-5 shadow-xl border border-indigo-200">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-3">
                <i class="fas fa-robot text-2xl text-indigo-600 animate-pulse"></i>
                <div>
                    <h4 class="text-sm font-black text-indigo-900">Processamento Automático com IA</h4>
                    <p id="ai-process-text" class="text-xs text-indigo-700 mt-0.5">Iniciando extração e transcrição do áudio...</p>
                </div>
            </div>
            <span id="ai-process-pct" class="text-lg font-black text-indigo-600 font-mono">0%</span>
        </div>
        <div class="w-full bg-indigo-100 rounded-full h-3.5 overflow-hidden border border-indigo-200">
            <div id="ai-process-bar" class="h-full w-0 transition-all duration-300 rounded-full" style="background: linear-gradient(90deg, #4f46e5 0%, #06b6d4 100%);"></div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">


        <!-- COLUNA ESQUERDA: PLAYER DE VÍDEO & TRANSCRIÇÃO (LARGURA 5) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Card Player de Vídeo -->
            <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-100">
                    <h3 class="font-black text-sm text-gray-800 flex items-center gap-2">
                        <i class="fas fa-play-circle text-indigo-600"></i>
                        Gravação Completa da Live
                    </h3>
                    <button type="button" onclick="toggleUploadModal()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-upload"></i> {{ $recordingUrl ? 'Trocar Vídeo' : 'Enviar Vídeo' }}
                    </button>
                </div>

                <!-- Video Container (Adaptado para Vídeos Verticais e Horizontais) -->
                <div class="relative bg-black rounded-xl overflow-hidden flex items-center justify-center border border-gray-300 group" style="max-height: 42vh; height: 360px;">
                    @if($recordingUrl)
                        <video id="live-main-player" src="{{ $recordingUrl }}" controls class="w-full h-full object-contain max-h-[42vh]" preload="metadata"></video>
                        <!-- Legendas Flutuantes Dinâmicas (Ao vivo enquanto o vídeo toca) -->
                        <div id="live-player-captions" class="absolute bottom-12 inset-x-3 pointer-events-none text-center z-20 hidden transition-all duration-200">
                            <span id="caption-text-pill" class="inline-block font-extrabold text-xs sm:text-sm px-3.5 py-1.5 rounded-xl shadow-2xl tracking-wide max-w-[95%] leading-snug" style="background-color: rgba(0, 0, 0, 0.88); color: #ffffff !important; border: 1px solid rgba(255, 255, 255, 0.25);">
                            </span>
                        </div>
                    @else
                        <div id="no-video-placeholder" class="text-center p-6 text-gray-400">
                            <i class="fas fa-video-slash text-4xl mb-3 text-gray-300"></i>
                            <p class="text-xs font-bold text-gray-700">Nenhum arquivo de vídeo carregado.</p>
                            <p class="text-[11px] text-gray-500 mt-1">Faça o upload do .mp4 ou defina o caminho da gravação do OBS.</p>
                            <button type="button" onclick="toggleUploadModal()" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-xl transition shadow" style="background-color: #4f46e5; color: #ffffff !important;">
                                <i class="fas fa-cloud-upload-alt mr-1"></i> Carregar Gravação
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Controles Avançados do Player -->
                <div class="mt-3 bg-gray-50 p-3 rounded-xl border border-gray-200 space-y-2.5">
                    
                    <!-- Linha 1: Display de Tempo & Pulos Rápidos -->
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <!-- Play/Pause, CC & Tempo -->
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="togglePlayPause()" id="btn-play-pause" title="Play / Pause (Espaço)" class="w-8 h-8 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center text-xs shadow-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-play" id="icon-play-pause"></i>
                            </button>
                            <button type="button" onclick="togglePlayerCaptions()" id="btn-toggle-captions" title="Ativar/Desativar Legendas no Player" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-[10px] rounded-lg shadow-2xs transition active:scale-95 cursor-pointer flex items-center gap-1" style="background-color: #f59e0b; color: #ffffff !important;">
                                <i class="fas fa-closed-captioning"></i> CC
                            </button>
                            <div class="flex items-center gap-1 font-mono text-xs font-black">
                                <span class="text-indigo-700" id="player-current-time">00:00:00</span>
                                <span class="text-gray-400">/</span>
                                <span class="text-gray-500 text-[11px]" id="player-duration">00:00:00</span>
                            </div>
                        </div>

                        <!-- Pulos de Tempo -->
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="jumpVideo(-10)" title="Voltar 10s" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -10s
                            </button>
                            <button type="button" onclick="jumpVideo(-5)" title="Voltar 5s (←)" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -5s
                            </button>
                            <button type="button" onclick="jumpVideo(-1)" title="Voltar 1s (Shift+←)" class="px-1.5 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                -1s
                            </button>
                            <button type="button" onclick="jumpVideo(1)" title="Avançar 1s (Shift+→)" class="px-1.5 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +1s
                            </button>
                            <button type="button" onclick="jumpVideo(5)" title="Avançar 5s (→)" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +5s
                            </button>
                            <button type="button" onclick="jumpVideo(10)" title="Avançar 10s" class="px-2 py-1 bg-white hover:bg-gray-200 text-gray-700 font-extrabold text-[10px] rounded-lg border border-gray-300 transition active:scale-95 cursor-pointer shadow-2xs">
                                +10s
                            </button>
                        </div>
                    </div>

                    <!-- Linha 2: Velocidade de Reprodução & Botões de Marcação -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-gray-200">
                        <!-- Seletor de Velocidade -->
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-bold text-gray-500 uppercase mr-0.5">Velocidade:</span>
                            @foreach([0.5, 0.75, 1.0, 1.25, 1.5, 2.0] as $speed)
                                <button type="button" onclick="setPlaybackRate({{ $speed }})" id="btn-speed-{{ str_replace('.', '_', $speed) }}" class="speed-btn px-1.5 py-0.5 rounded text-[10px] font-mono font-bold border transition cursor-pointer {{ $speed == 1.0 ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                                    {{ $speed }}x
                                </button>
                            @endforeach
                        </div>

                        <!-- Botões de Marcação Rápida -->
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="markCurrentTimeToActiveItem('start')" title="Marcar tempo atual como Início (I)" class="bg-green-100 hover:bg-green-200 text-green-800 border border-green-300 text-xs font-extrabold px-2.5 py-1 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer shadow-2xs">
                                <i class="fas fa-step-forward text-[10px]"></i> Início
                            </button>
                            <button type="button" onclick="markCurrentTimeToActiveItem('end')" title="Marcar tempo atual como Fim (O)" class="bg-amber-100 hover:bg-amber-200 text-amber-800 border border-amber-300 text-xs font-extrabold px-2.5 py-1 rounded-lg transition flex items-center gap-1 active:scale-95 cursor-pointer shadow-2xs">
                                <i class="fas fa-stop text-[10px]"></i> Fim
                            </button>
                        </div>
                    </div>

                    <!-- Linha 3: Dica de Atalhos de Teclado -->
                    <div class="pt-1.5 border-t border-gray-200 flex items-center justify-between text-[10px] text-gray-500 font-medium">
                        <span class="flex items-center gap-1 font-semibold text-gray-600">
                            <i class="fas fa-keyboard text-indigo-500"></i> Atalhos:
                        </span>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">Espaço</kbd> Play/Pause</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">← / →</kbd> ±5s</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">I</kbd> Início</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">O</kbd> Fim</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">P</kbd> Prévia</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-gray-300 rounded text-[9px] font-mono shadow-2xs">C</kbd> Foto Capa</span>
                        </div>
                    </div>

                </div>

                <!-- Ações IA de Transcrição -->
                <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                    <button type="button" onclick="autoDetectWithAI()" id="btn-auto-detect" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-extrabold py-2 px-3 rounded-xl text-xs transition shadow flex items-center justify-center gap-1.5 cursor-pointer active:scale-95" style="background-color: #0d9488; color: #ffffff !important;">
                        <i class="fas fa-wand-magic-sparkles"></i>
                        <span style="color: #ffffff !important; font-weight: 800;">Auto-Detectar com IA</span>
                    </button>
                    <button type="button" onclick="toggleTranscriptionModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer border border-gray-300">
                        <i class="fas fa-file-alt text-gray-600"></i>
                        <span>Transcrição</span>
                    </button>
                </div>
            </div>

            <!-- Card de Dicas de Padrão de Fala -->
            <div class="bg-indigo-50 rounded-2xl border border-indigo-200 p-4 text-xs text-indigo-900 leading-relaxed shadow-xs">
                <div class="font-black text-indigo-950 flex items-center gap-1.5 mb-1.5 text-sm">
                    <i class="fas fa-lightbulb text-amber-500"></i>
                    Como funciona o corte inteligente:
                </div>
                <p class="text-xs text-indigo-800 leading-normal">
                    O algoritmo localiza a frase em que o <b>código</b> foi falado. Ele varre para trás até o início da apresentação da peça (gatilhos como <i>"Olha essa peça..."</i>) e varre para frente até o fechamento (<i>"Passando..."</i> ou entrega aos bastidores).
                </p>
            </div>

        </div>

        <!-- COLUNA DIREITA: LISTA DE ITENS & MINUTAGEM (LARGURA 7) -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- TOOLBAR DE FILTROS E BUSCA DOS ITENS -->
            <div class="bg-white p-3.5 rounded-2xl border border-gray-200 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="text-xs font-black text-gray-900 flex items-center gap-2">
                        <i class="fas fa-tshirt text-indigo-600"></i>
                        <span>Itens da Live (<span id="items-visible-counter">{{ count($liveItems) }}</span> / {{ count($liveItems) }})</span>
                    </div>
                    
                    <!-- Busca rápida -->
                    <div class="relative w-full sm:w-56">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                            <i class="fas fa-search text-[11px]"></i>
                        </div>
                        <input type="text" 
                               id="items-filter-search-input"
                               oninput="handleItemSearch(this.value)"
                               placeholder="Buscar #cód, produto..." 
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 block pl-7 p-1.5 font-medium shadow-2xs">
                    </div>
                </div>

                <!-- Abas de Filtro de Itens -->
                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-gray-100">
                    <button type="button" 
                            onclick="setItemsFilter('all')"
                            data-filter="all"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #1f2937; color: #ffffff;">
                        <span>Todos</span>
                        <span class="px-1 py-0.2 rounded-full text-[10px] opacity-80">{{ count($liveItems) }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('unminuted')"
                            data-filter="unminuted"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs {{ ($stats['items_without_cuts'] ?? 0) > 0 ? 'animate-pulse' : '' }}"
                            style="background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                        <span>Não Minutados</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black" style="background-color: #f59e0b; color: #ffffff;">{{ $stats['items_without_cuts'] ?? 0 }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('minuted')"
                            data-filter="minuted"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #f3f4f6; color: #374151;">
                        <i class="fas fa-check-circle text-teal-600"></i>
                        <span>Minutados</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 text-gray-700">{{ $stats['items_with_cuts'] }}</span>
                    </button>

                    <button type="button" 
                            onclick="setItemsFilter('rendered')"
                            data-filter="rendered"
                            class="item-filter-tab px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            style="background-color: #f3f4f6; color: #374151;">
                        <i class="fas fa-video text-green-600"></i>
                        <span>Vídeos Prontos</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-gray-200 text-gray-700">{{ $stats['items_rendered'] }}</span>
                    </button>
                </div>
            </div>

            <!-- Banner Informativo quando filtrado por Não Minutados -->
            <div id="filter-unminuted-banner" class="hidden p-3.5 bg-amber-50 border border-amber-300 rounded-2xl text-xs text-amber-950 shadow-xs">
                <div class="font-black flex items-center gap-2 text-amber-900 text-xs">
                    <i class="fas fa-info-circle text-amber-600"></i>
                    <span>Exibindo itens sem minutagem detectada pela IA</span>
                </div>
                <p class="text-[11px] text-amber-900 mt-1 leading-relaxed font-medium">
                    Estes itens não tiveram correspondência direta na fala do áudio. Clique no item para carregar o vídeo, navegue até o trecho em que a peça aparece e clique em <b>Início (I)</b> e <b>Fim (O)</b> para salvar.
                </p>
            </div>

            <!-- Notice quando a busca/filtro não encontra nenhum item -->
            <div id="items-filter-empty-notice" class="hidden bg-white p-8 rounded-2xl border border-gray-200 text-center shadow-xs">
                <i class="fas fa-search text-gray-300 text-3xl mb-2"></i>
                <h4 class="text-xs font-bold text-gray-800">Nenhum item encontrado com este filtro</h4>
                <p class="text-[11px] text-gray-500 mt-0.5">Tente selecionar outra aba ou limpar o campo de busca.</p>
                <button type="button" onclick="setItemsFilter('all'); document.getElementById('items-filter-search-input').value = ''; handleItemSearch('');" class="mt-3 px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs transition border border-gray-300">
                    Restaurar Todos os Itens
                </button>
            </div>

            <!-- Lista de Cards de Itens -->
            <div class="space-y-3" id="live-items-list-container">
                @forelse($liveItems as $item)
                    @php
                        $hasCutTimes = $item['cut_start_sec'] !== null && $item['cut_end_sec'] !== null;
                        $isRendered = $item['video_cut_status'] === 'recorded' && !empty($item['video_cut_url']);
                    @endphp
                    <div id="item-card-{{ $item['live_item_id'] }}" 
                         data-has-cut="{{ $hasCutTimes ? '1' : '0' }}"
                         data-is-rendered="{{ $isRendered ? '1' : '0' }}"
                         data-code="{{ $item['codigo_live'] }}"
                         data-search="{{ strtolower($item['codigo_live'] . ' ' . $item['item_codigo'] . ' ' . $item['item_name'] . ' ' . ($item['buyer_name'] ?? '')) }}"
                         onclick="selectActiveItem({{ $item['live_item_id'] }}, '{{ $item['cut_start_formatted'] ?: ($item['cut_start_sec'] ?? 0) }}')"
                         class="item-card bg-white hover:bg-gray-50 rounded-2xl border {{ $isRendered ? 'border-green-400 bg-green-50/20' : ($hasCutTimes ? 'border-indigo-300' : 'border-amber-300 bg-amber-50/30') }} p-4 transition duration-150 shadow-sm cursor-pointer relative group">
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            
                            <!-- Foto & Infos do Produto -->
                            <div class="flex items-center gap-3 min-w-0">
                                <img id="item-avatar-{{ $item['live_item_id'] }}" src="{{ $item['item_image'] }}" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-gray-200 shrink-0" onerror="this.src='https://placehold.co/100x100?text=Sem+Foto'" />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shadow-xs" style="background-color: #4f46e5; color: #ffffff !important;">
                                             #{{ $item['codigo_live'] }}
                                        </span>
                                        @if(!$hasCutTimes)
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 shadow-xs">
                                                <i class="fas fa-exclamation-triangle text-amber-600"></i> Não Minutado
                                            </span>
                                        @endif
                                        @php
                                            $isGold = !empty($item['review_quality']) && $item['review_quality'] === 'gold';
                                            $isAdjusted = !empty($item['is_reviewed']) && !$isGold;
                                        @endphp
                                        <span id="badge-gold-{{ $item['live_item_id'] }}" class="{{ $isGold ? '' : 'hidden' }} px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 shadow-xs" title="Corte memorizado como padrão-ouro para o aprendizado do Severino">
                                            <i class="fas fa-crown text-amber-600"></i> Padrão-Ouro
                                        </span>
                                        <span id="badge-adj-{{ $item['live_item_id'] }}" class="{{ $isAdjusted ? '' : 'hidden' }} px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1 shadow-xs" title="Corte ajustado manualmente">
                                            <i class="fas fa-user-check text-blue-600"></i> Revisado
                                        </span>
                                        @if($isRendered)
                                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-green-600 text-white flex items-center gap-1 shadow-xs" style="background-color: #16a34a; color: #ffffff !important;">
                                                <i class="fas fa-check-circle"></i> Vídeo Pronto
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-extrabold text-gray-900 truncate mt-1">{{ $item['item_name'] }}</h4>
                                    <div class="text-xs text-gray-500 font-semibold">Cód: {{ $item['item_codigo'] }} &bull; R$ {{ $item['item_price'] }}</div>
                                </div>
                            </div>

                            <!-- Minutagem & Controles -->
                            <div class="flex flex-wrap items-center gap-2 shrink-0 justify-end" onclick="event.stopPropagation();">
                                
                                <div class="flex items-center gap-1.5 bg-gray-100 p-1.5 rounded-xl border border-gray-200">
                                    <!-- Início -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-gray-500">Início</label>
                                        <input type="text"
                                               id="input-start-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_start_formatted'] ?? '' }}" 
                                               placeholder="00:00:00"
                                               class="w-20 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-green-700 rounded-lg border border-gray-300 focus:border-green-500 focus:ring-1 focus:ring-green-500 shadow-2xs">
                                    </div>

                                    <span class="text-gray-400 font-bold text-xs mt-3">➔</span>

                                    <!-- Fim -->
                                    <div class="text-center">
                                        <label class="block text-[9px] font-bold uppercase text-gray-500">Fim</label>
                                        <input type="text"
                                               id="input-end-{{ $item['live_item_id'] }}"
                                               value="{{ $item['cut_end_formatted'] ?? '' }}" 
                                               placeholder="00:00:00"
                                               class="w-20 px-1.5 py-1 text-xs font-mono font-bold text-center bg-white text-amber-700 rounded-lg border border-gray-300 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 shadow-2xs">
                                    </div>

                                    <!-- Salvar Tempo -->
                                    <button type="button" onclick="saveItemCutTime({{ $item['live_item_id'] }})" title="Salvar Minutagem & Ensinar Severino (Enter)" class="mt-3 p-1.5 text-gray-600 hover:text-gray-900 bg-white hover:bg-gray-200 rounded-lg transition border border-gray-200 shadow-xs cursor-pointer">
                                        <i class="fas fa-save text-xs"></i>
                                    </button>

                                    <!-- Buscar na Transcrição -->
                                    <button type="button" 
                                            onclick="openTranscriptionSearchModalFromButton(this)"
                                            data-item-id="{{ $item['live_item_id'] }}"
                                            data-live-code="{{ $item['codigo_live'] }}"
                                            data-item-name="{{ $item['item_name'] }}"
                                            data-item-codigo="{{ $item['item_codigo'] ?? '' }}"
                                            title="Buscar trechos na Transcrição para o item #{{ $item['codigo_live'] }}" 
                                            class="mt-3 p-1.5 text-purple-700 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 rounded-lg transition border border-purple-200 shadow-xs cursor-pointer">
                                        <i class="fas fa-search text-xs"></i>
                                    </button>
                                </div>


                                <!-- Ações de Vídeo & Severino -->
                                <div class="flex items-center gap-1.5">
                                    <!-- Botão Aprovar Padrão Ouro para Treinar o Severino -->
                                    <button type="button" 
                                            onclick="approveItemCut({{ $item['live_item_id'] }})" 
                                            id="btn-approve-{{ $item['live_item_id'] }}" 
                                            title="{{ $isGold ? 'Corte já memorizado como Padrão-Ouro pelo Severino' : 'Aprovar corte como Padrão-Ouro (Severino aprende este modelo)' }}" 
                                            class="p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs flex items-center justify-center {{ $isGold ? 'bg-amber-500 text-white font-black' : 'bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-300' }}">
                                        <i class="fas {{ $isGold ? 'fa-crown' : 'fa-thumbs-up' }}"></i>
                                    </button>

                                    <button type="button" onclick="previewItemClip({{ $item['live_item_id'] }})" title="Reproduzir Trecho Marcado no Player" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs">
                                        <i class="fas fa-play"></i>
                                    </button>

                                    <button type="button" onclick="captureThumbnailFromPlayer({{ $item['live_item_id'] }})" id="btn-thumb-{{ $item['live_item_id'] }}" title="Capturar Frame Atual do Player como Capa/Thumbnail (Atalho C)" class="bg-amber-500 hover:bg-amber-600 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95" style="background-color: #f59e0b; color: #ffffff !important;">
                                        <i class="fas fa-camera"></i>
                                    </button>

                                    <button type="button" onclick="generateSingleClip({{ $item['live_item_id'] }})" id="btn-cut-{{ $item['live_item_id'] }}" title="Gerar Corte Individual & Thumbnail com FFmpeg" class="bg-teal-600 hover:bg-teal-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer active:scale-95" style="background-color: #0d9488; color: #ffffff !important;">
                                        <i class="fas fa-scissors"></i>
                                    </button>

                                    @if($isRendered)
                                        <a href="{{ $item['video_cut_url'] }}" target="_blank" title="Assistir / Baixar Vídeo Cortado" class="bg-green-600 hover:bg-green-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer" style="background-color: #16a34a; color: #ffffff !important;">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>

                                        @if(!empty($item['youtube_video_id']))
                                            <a href="{{ $item['youtube_url'] ?: ('https://youtube.com/shorts/' . $item['youtube_video_id']) }}" 
                                               target="_blank" 
                                               title="{{ $item['youtube_status'] === 'sold_updated' ? 'Short no YouTube (Carimbado como VENDIDO 🔴)' : 'Assistir Short no YouTube' }}" 
                                               class="bg-red-600 hover:bg-red-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" 
                                               style="background-color: #dc2626; color: #ffffff !important;">
                                                <i class="fab fa-youtube text-white"></i>
                                                @if($item['youtube_status'] === 'sold_updated')
                                                    <span class="text-[9px] bg-red-950 px-1 py-0.5 rounded font-black text-white">VENDIDO</span>
                                                @endif
                                            </a>
                                        @else
                                            <button type="button" 
                                                    onclick="uploadCutToYouTube({{ $item['live_item_id'] }})" 
                                                    id="btn-yt-{{ $item['live_item_id'] }}" 
                                                    title="Publicar este corte no YouTube Shorts" 
                                                    class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 p-2 rounded-xl text-xs transition shadow-xs cursor-pointer active:scale-95">
                                                <i class="fab fa-youtube text-red-600"></i>
                                            </button>
                                        @endif

                                        @if(!empty($item['tiktok_publish_id']))
                                            <a href="{{ $item['tiktok_url'] ?: 'https://www.tiktok.com' }}" 
                                               target="_blank" 
                                               title="Publicado no TikTok" 
                                               class="bg-slate-900 hover:bg-black text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" 
                                               style="background-color: #0f172a; color: #ffffff !important;">
                                                <i class="fab fa-tiktok text-white"></i>
                                            </a>
                                        @else
                                            <button type="button" 
                                                    onclick="uploadCutToTikTok({{ $item['live_item_id'] }})" 
                                                    id="btn-tiktok-{{ $item['live_item_id'] }}" 
                                                    title="Publicar este corte no TikTok" 
                                                    class="bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 p-2 rounded-xl text-xs transition shadow-xs cursor-pointer active:scale-95">
                                                <i class="fab fa-tiktok text-slate-900"></i>
                                            </button>
                                        @endif

                                        @if(!empty($item['instagram_media_id']))
                                            <a href="{{ $item['instagram_url'] ?: 'https://www.instagram.com' }}" 
                                               target="_blank" 
                                               title="Publicado no Instagram Reels" 
                                               class="hover:opacity-95 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" 
                                               style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #ffffff !important;">
                                                <i class="fab fa-instagram text-white"></i>
                                            </a>
                                        @else
                                            <button type="button" 
                                                    onclick="uploadCutToInstagram({{ $item['live_item_id'] }})" 
                                                    id="btn-instagram-{{ $item['live_item_id'] }}" 
                                                    title="Publicar este corte no Instagram Reels" 
                                                    class="bg-pink-50 hover:bg-pink-100 text-pink-700 border border-pink-200 p-2 rounded-xl text-xs transition shadow-xs cursor-pointer active:scale-95">
                                                <i class="fab fa-instagram text-pink-600"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>

                            </div>

                        </div>

                        <!-- Galeria de Miniaturas Inteligentes (3 Opções ou Capturas Manuais) -->
                        <div class="mt-3 pt-2.5 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2" onclick="event.stopPropagation();">
                            <div class="flex items-center gap-1.5 flex-wrap" id="candidates-container-{{ $item['live_item_id'] }}">
                                <span class="text-[10px] font-bold text-gray-400 uppercase mr-1 flex items-center gap-1">
                                    <i class="fas fa-images text-indigo-500"></i> Capa:
                                </span>
                                @if(!empty($item['thumbnail_candidates']))
                                    @foreach($item['thumbnail_candidates'] as $cIdx => $cand)
                                        @php
                                            $isCandActive = !empty($cand['path']) && ($cand['path'] === $item['raw_image_path'] || str_contains($item['item_image'], basename($cand['path'])));
                                        @endphp
                                        <button type="button" 
                                                onclick="selectItemCandidateThumbnail({{ $item['live_item_id'] }}, '{{ $cand['path'] }}', '{{ $cand['url'] }}', this)"
                                                title="Clique para definir como capa oficial ({{ $cand['label'] ?? 'Opção' }})"
                                                class="candidate-thumb-btn relative rounded-lg overflow-hidden border-2 transition active:scale-95 cursor-pointer shadow-2xs group/thumb {{ $isCandActive ? 'border-green-500 ring-2 ring-green-400/50' : 'border-gray-200 hover:border-indigo-400 opacity-75 hover:opacity-100' }}"
                                                style="width: 52px; height: 52px;">
                                            <img src="{{ $cand['url'] }}" class="w-full h-full object-cover" />
                                            <span class="absolute bottom-0 inset-x-0 bg-black/75 text-[8px] font-black text-white text-center py-0.5 truncate px-0.5">
                                                {{ $cand['label'] ?? ('Opção ' . ($cIdx + 1)) }}
                                            </span>
                                            <span class="cand-badge-active {{ $isCandActive ? '' : 'hidden' }} absolute top-0.5 right-0.5 bg-green-600 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold shadow-xs">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        </button>
                                    @endforeach
                                @else
                                    <span class="text-[11px] text-gray-400 italic">Miniaturas ainda não geradas</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" 
                                        onclick="generateSmartThumbnails({{ $item['live_item_id'] }})" 
                                        id="btn-gen-thumbs-{{ $item['live_item_id'] }}"
                                        title="Extrair 3 miniaturas inteligentes de alta nitidez com IA/FFmpeg" 
                                        class="text-[10px] font-extrabold text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2 py-1 rounded-lg transition active:scale-95 flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-wand-magic-sparkles text-indigo-600"></i>
                                    <span>3 Miniaturas IA</span>
                                </button>
                            </div>
                        </div>

                        <!-- Snippet de Transcrição -->
                        <div id="snippet-container-{{ $item['live_item_id'] }}" class="mt-2.5 pt-2 border-t border-gray-100 text-[11px] text-gray-600 italic bg-gray-50/70 p-2 rounded-lg {{ empty($item['transcription_snippet']) ? 'hidden' : '' }}">
                            <i class="fas fa-quote-left text-[9px] text-indigo-500 mr-1"></i>
                            <span id="snippet-text-{{ $item['live_item_id'] }}">{{ $item['transcription_snippet'] ?? '' }}</span>
                        </div>

                    </div>
                @empty
                    <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center text-gray-500 shadow-sm">
                        <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                        <p class="text-sm font-bold text-gray-700">Nenhum produto vinculado a esta live ainda.</p>
                        <p class="text-xs text-gray-400 mt-1">Os produtos bipados na live aparecerão automaticamente nesta lista.</p>
                    </div>
                @endforelse
            </div>

        </div>

    </div>

</div>

<!-- ==================================================== -->
<!-- PASSO 1: MODAL BAIXAR / CARREGAR VÍDEO DA LIVE       -->
<!-- ==================================================== -->
<div id="modal-upload-video" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-200">Passo 1 de 4</span>
                <h3 class="text-lg font-black text-gray-900 flex items-center gap-2 mt-1">
                    <i class="fas fa-video text-amber-600"></i> Passo 1: Baixar / Carregar Vídeo
                </h3>
            </div>
            <button type="button" onclick="toggleUploadModal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold cursor-pointer leading-none">&times;</button>
        </div>

        <div class="mb-4 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-950 font-medium">
            <p class="font-bold flex items-center gap-1.5 text-amber-900">
                <i class="fas fa-info-circle text-amber-600"></i> Somente Gravação do Vídeo:
            </p>
            <p class="mt-1 text-amber-950 text-[11px] leading-relaxed">
                Este passo realiza <strong>APENAS</strong> o download do link ou upload do arquivo de gravação completa para o servidor. 
                <strong>Não executa transcrição nem gera cortes automaticamente.</strong>
            </p>
        </div>

        @if(!empty($recordingUrl))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs text-emerald-950">
                <div class="flex items-center gap-2 min-w-0 pr-2">
                    <i class="fas fa-check-circle text-emerald-600 text-base shrink-0"></i>
                    <div class="truncate">
                        <div class="font-bold text-emerald-900">Vídeo atualmente salvo no servidor:</div>
                        <div class="text-[11px] text-emerald-800 font-mono truncate">{{ $live->recording_filename ?: basename($live->recording_path) }}</div>
                    </div>
                </div>
                <button type="button" onclick="seekPlayer(0)" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg shrink-0 transition cursor-pointer" style="background-color: #059669; color: #ffffff !important;">
                    Ver no Player
                </button>
            </div>
        @endif

        <form id="form-upload-video" onsubmit="handleVideoUpload(event)" class="space-y-4">
            <div>
                <label for="video-path-manual-input" class="block text-xs font-bold text-gray-900 mb-1 flex items-center justify-between">
                    <span>Link do Instagram (Reel/Live), YouTube ou TikTok:</span>
                    <button type="button" onclick="pasteClipboardToManualPath()" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-paste"></i> Colar do Teclado
                    </button>
                </label>
                <input 
                    type="text" 
                    id="video-path-manual-input" 
                    name="video_path_manual" 
                    onblur="this.value = sanitizeInstagramUrl(this.value)"
                    onpaste="setTimeout(() => this.value = sanitizeInstagramUrl(this.value), 20)"
                    placeholder="Ex: https://www.instagram.com/reel/DefqY4pEZug/..." 
                    class="w-full px-3.5 py-2 text-xs bg-white text-gray-900 rounded-xl border border-gray-300 focus:border-indigo-600 font-medium outline-none transition"
                >
                <p class="text-[11px] text-gray-600 mt-1">Cole a URL pública do post/vídeo postado no Instagram para baixar via yt-dlp.</p>
            </div>

            <div class="relative flex py-1 items-center">
                <div class="flex-grow border-t border-gray-200"></div>
                <span class="flex-shrink mx-3 text-gray-500 text-[10px] uppercase font-bold">OU ENVIE UM ARQUIVO DO COMPUTADOR</span>
                <div class="flex-grow border-t border-gray-200"></div>
            </div>

            <div>
                <label for="video-file-input" class="block text-xs font-bold text-gray-900 mb-1">Arquivo de Vídeo (.mp4, .mov, .mkv):</label>
                <input type="file" id="video-file-input" name="video_file" accept="video/*" class="w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer bg-gray-50 p-2 rounded-xl border border-gray-300">
            </div>

            <div id="upload-progress-box" class="hidden space-y-1.5 p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
                <div class="flex justify-between text-xs text-indigo-950 font-bold">
                    <span id="upload-progress-text">Processando download...</span>
                    <span id="upload-progress-pct" class="font-mono text-indigo-700">0%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                    <div id="upload-progress-bar" class="bg-indigo-600 h-full w-0 transition-all duration-150"></div>
                </div>
            </div>

            <div class="pt-3 flex justify-end gap-2 border-t border-gray-200">
                <button type="button" onclick="toggleUploadModal(false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">Cancelar</button>
                <button type="submit" id="btn-submit-upload" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl text-xs font-extrabold transition shadow flex items-center gap-1.5 cursor-pointer" style="background-color: #4f46e5; color: #ffffff !important;">
                    <i class="fas fa-download text-white"></i>
                    <span class="text-white font-black">Baixar / Salvar Vídeo (Passo 1)</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================================================== -->
<!-- PASSO 2: MODAL TRANSCREVER ÁUDIO COM WHISPER IA      -->
<!-- ==================================================== -->
<div id="modal-step2-transcription" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-teal-100 text-teal-900 border border-teal-200">Passo 2 de 4</span>
                <h3 class="text-lg font-black text-gray-900 flex items-center gap-2 mt-1">
                    <i class="fas fa-microphone-lines text-teal-600"></i> Passo 2: Transcrever Áudio com IA (Whisper)
                </h3>
            </div>
            <button type="button" onclick="toggleStep2Modal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold cursor-pointer leading-none">&times;</button>
        </div>

        <div class="space-y-4">
            <div class="p-3.5 bg-teal-50 border border-teal-200 rounded-xl text-xs text-teal-950 font-medium">
                <p class="font-bold flex items-center gap-1.5 text-teal-900">
                    <i class="fas fa-info-circle text-teal-600"></i> Somente Transcrição:
                </p>
                <p class="mt-1 text-teal-950 text-[11px] leading-relaxed">
                    Extrai a faixa de áudio do vídeo salvo no <strong>Passo 1</strong> e utiliza IA Whisper para transcrever tudo o que foi falado na live com timestamps exatos. 
                    <strong>Não altera minutagens de peças nem gera arquivos de corte.</strong>
                </p>
            </div>

            @if(empty($recordingUrl))
                <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-950 font-medium flex items-start gap-2.5">
                    <i class="fas fa-exclamation-triangle text-red-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <div class="font-bold text-red-900">Nenhum vídeo salvo no Passo 1!</div>
                        <div class="text-[11px] text-red-800 mt-0.5">Você precisa primeiro baixar ou carregar a gravação da live no Passo 1 antes de iniciar a transcrição de áudio.</div>
                        <button type="button" onclick="toggleStep2Modal(false); openStep1Modal();" class="mt-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shadow-xs" style="background-color: #dc2626; color: #ffffff !important;">
                            <i class="fas fa-arrow-left mr-1"></i> Ir para Passo 1: Baixar Vídeo
                        </button>
                    </div>
                </div>
            @else
                <div class="p-3.5 {{ $step2Done ? 'bg-emerald-50 border-emerald-200 text-emerald-950' : 'bg-amber-50 border-amber-200 text-amber-950' }} border rounded-xl text-xs font-medium">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="fas {{ $step2Done ? 'fa-check-circle text-emerald-600' : 'fa-clock text-amber-600' }} text-base shrink-0"></i>
                            <div>
                                <div class="font-bold {{ $step2Done ? 'text-emerald-900' : 'text-amber-900' }}">
                                    {{ $step2Done ? '✓ Transcrição ativa' : '⏳ Transcrição ainda não realizada' }}
                                </div>
                                <div class="text-[11px] {{ $step2Done ? 'text-emerald-800' : 'text-amber-800' }}">
                                    {{ $step2Done ? count($transcriptionData) . ' frases transcritas e indexadas.' : 'O vídeo gravado está pronto para ser transcrito.' }}
                                </div>
                            </div>
                        </div>
                        @if($step2Done)
                            <button type="button" onclick="toggleStep2Modal(false); toggleTranscriptionModal();" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded-lg shrink-0 transition cursor-pointer shadow-xs" style="background-color: #059669; color: #ffffff !important;">
                                Ver Frases
                            </button>
                        @endif
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-2 border-t border-gray-200">
                    <button type="button" onclick="promptPasteTranscription()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-paste"></i> Colar JSON Manualmente
                    </button>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button type="button" onclick="toggleStep2Modal(false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                            Fechar
                        </button>
                        <button type="button" onclick="executeStep2Transcription()" id="btn-execute-step2" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold px-5 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #0d9488; color: #ffffff !important;">
                            <i class="fas fa-play text-white"></i>
                            <span class="text-white font-black">{{ $step2Done ? 'Retranscrever Áudio' : 'Iniciar Transcrição IA (Passo 2)' }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- PASSO 3: MODAL MINUTAR PEÇAS COM SEVERINO IA         -->
<!-- ==================================================== -->
<div id="modal-step3-minutagem" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-purple-100 text-purple-900 border border-purple-200">Passo 3 de 4</span>
                <h3 class="text-lg font-black text-gray-900 flex items-center gap-2 mt-1">
                    <i class="fas fa-wand-magic-sparkles text-purple-600"></i> Passo 3: Minutar Peças com Severino IA
                </h3>
            </div>
            <button type="button" onclick="toggleStep3Modal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold cursor-pointer leading-none">&times;</button>
        </div>

        <div class="space-y-4">
            <div class="p-3.5 bg-purple-50 border border-purple-200 rounded-xl text-xs text-purple-950 font-medium">
                <p class="font-bold flex items-center gap-1.5 text-purple-900">
                    <i class="fas fa-info-circle text-purple-600"></i> Somente Minutagem:
                </p>
                <p class="mt-1 text-purple-950 text-[11px] leading-relaxed">
                    O Severino IA analisa o texto da transcrição (Passo 2) e cruza com a lista de peças cadastradas na live para definir o início e fim de cada produto. 
                    <strong>Não renderiza arquivos de vídeo nem altera o áudio.</strong>
                </p>
            </div>

            @if(empty($transcriptionData) || count($transcriptionData) == 0)
                <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-950 font-medium flex items-start gap-2.5">
                    <i class="fas fa-exclamation-triangle text-red-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <div class="font-bold text-red-900">Transcrição não encontrada!</div>
                        <div class="text-[11px] text-red-800 mt-0.5">Você precisa primeiro transcrever o áudio no Passo 2 antes de iniciar a minutagem inteligente.</div>
                        <button type="button" onclick="toggleStep3Modal(false); openStep2Modal();" class="mt-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shadow-xs" style="background-color: #dc2626; color: #ffffff !important;">
                            <i class="fas fa-arrow-left mr-1"></i> Ir para Passo 2: Transcrever Áudio
                        </button>
                    </div>
                </div>
            @else
                <div class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-950">
                    <div class="flex items-center justify-between font-bold text-indigo-900 mb-1">
                        <span>Progresso Atual das Peças:</span>
                        <span class="font-mono text-indigo-950 font-extrabold">{{ $stats['items_with_cuts'] }} / {{ $stats['total_items'] }} minutadas</span>
                    </div>
                    <div class="text-[11px] text-indigo-900">
                        @if(($stats['items_without_cuts'] ?? 0) > 0)
                            Restam <strong>{{ $stats['items_without_cuts'] }} peça(s)</strong> sem minutagem definida.
                        @else
                            ✓ Todas as peças desta live já possuem minutagem!
                        @endif
                    </div>
                </div>

                <div class="space-y-2.5 border border-gray-200 rounded-xl p-3 bg-gray-50">
                    <div class="text-xs font-bold text-gray-900 mb-1">Escolha o modo de minutagem:</div>
                    
                    <label class="flex items-start gap-2 text-xs text-gray-900 font-medium cursor-pointer p-2 rounded-lg bg-white border border-gray-200 hover:border-purple-300 transition">
                        <input type="radio" name="step3_mode" value="unreviewed" checked class="mt-0.5 text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="font-bold text-gray-900">Apenas peças pendentes / sem minutagem (Recomendado)</span>
                            <p class="text-[11px] text-gray-600">Preserva todas as peças já minutadas ou revisadas manualmente e processa só o que falta.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2 text-xs text-gray-900 font-medium cursor-pointer p-2 rounded-lg bg-white border border-gray-200 hover:border-purple-300 transition">
                        <input type="radio" name="step3_mode" value="all" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                        <div>
                            <span class="font-bold text-gray-900">Todas as peças do zero</span>
                            <p class="text-[11px] text-gray-600">Recalcula a minutagem de todos os itens da live com base na transcrição.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2 text-xs text-gray-900 font-medium cursor-pointer p-2 rounded-lg bg-white border border-gray-200 hover:border-purple-300 transition">
                        <input type="radio" name="step3_mode" value="from_code" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                        <div class="flex-1">
                            <span class="font-bold text-gray-900">A partir de um código específico</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] text-gray-600">Código da peça inicial: #</span>
                                <input type="number" id="step3-start-code" placeholder="Ex: 50" class="w-20 px-2 py-1 text-xs bg-white text-gray-900 rounded-lg border border-gray-300 font-bold">
                            </div>
                        </div>
                    </label>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-gray-200">
                    <button type="button" onclick="toggleStep3Modal(false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="button" onclick="executeStep3Minutagem()" id="btn-execute-step3" class="bg-purple-600 hover:bg-purple-700 text-white font-extrabold px-5 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer" style="background-color: #9333ea; color: #ffffff !important;">
                        <i class="fas fa-wand-magic-sparkles text-white"></i>
                        <span class="text-white font-black">Iniciar Minutagem IA (Passo 3)</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- ==================================================== -->
<!-- PASSO 4: MODAL FATIAMENTO DE VÍDEO COM FFmpeg        -->
<!-- ==================================================== -->
<div id="modal-step4-fatiamento" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-900 border border-indigo-200">Passo 4 de 4</span>
                <h3 class="text-lg font-black text-gray-900 flex items-center gap-2 mt-1">
                    <i class="fas fa-scissors text-indigo-600"></i> Passo 4: Gerar Cortes de Vídeo (FFmpeg)
                </h3>
            </div>
            <button type="button" onclick="toggleStep4Modal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold cursor-pointer leading-none">&times;</button>
        </div>

        <div class="space-y-4">
            <div class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl text-xs text-indigo-950 font-medium">
                <p class="font-bold flex items-center gap-1.5 text-indigo-900">
                    <i class="fas fa-info-circle text-indigo-600"></i> Somente Renderização de Cortes:
                </p>
                <p class="mt-1 text-indigo-950 text-[11px] leading-relaxed">
                    Renderiza os vídeos individuais (.mp4) de cada peça em alta resolução com legendas automáticas e capas de abertura. 
                    <strong>Não altera transcrições nem minutagens.</strong>
                </p>
            </div>

            @if($stats['items_with_cuts'] == 0)
                <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-950 font-medium flex items-start gap-2.5">
                    <i class="fas fa-exclamation-triangle text-red-600 text-base shrink-0 mt-0.5"></i>
                    <div>
                        <div class="font-bold text-red-900">Nenhuma peça minutada ainda!</div>
                        <div class="text-[11px] text-red-800 mt-0.5">Você precisa definir os tempos de início e fim no Passo 3 antes de renderizar os vídeos.</div>
                        <button type="button" onclick="toggleStep4Modal(false); openStep3Modal();" class="mt-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold px-3 py-1.5 rounded-lg text-xs transition cursor-pointer shadow-xs" style="background-color: #dc2626; color: #ffffff !important;">
                            <i class="fas fa-arrow-left mr-1"></i> Ir para Passo 3: Minutar Peças
                        </button>
                    </div>
                </div>
            @else
                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 space-y-1.5">
                    <div class="flex items-center justify-between font-bold text-gray-900">
                        <span>Vídeos Renderizados:</span>
                        <span class="font-mono text-emerald-800 font-extrabold">{{ $stats['items_rendered'] }} / {{ $stats['total_items'] }} prontos</span>
                    </div>
                    @php
                        $pendingCuts = max(0, $stats['total_items'] - $stats['items_rendered']);
                    @endphp
                    <div class="text-[11px] text-gray-600">
                        @if($pendingCuts > 0)
                            Restam <strong>{{ $pendingCuts }} vídeo(s)</strong> pendentes de renderização.
                        @else
                            ✓ Todos os vídeos desta live já foram fatiados e salvos em disco!
                        @endif
                    </div>
                </div>

                <div class="pt-3 flex flex-col gap-2.5 border-t border-gray-200">
                    @if($pendingCuts > 0)
                        <button type="button" onclick="toggleStep4Modal(false); generateAllClips(false);" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs shadow-md flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer" style="background-color: #4f46e5; color: #ffffff !important;">
                            <i class="fas fa-play text-white"></i>
                            <span class="text-white font-black">Fatiar Apenas os {{ $pendingCuts }} Pendentes</span>
                        </button>
                    @endif

                    <button type="button" onclick="toggleStep4Modal(false); generateAllClips(true);" class="w-full {{ $pendingCuts > 0 ? 'bg-gray-100 hover:bg-gray-200 text-gray-800' : 'bg-indigo-600 hover:bg-indigo-700 text-white' }} font-extrabold px-5 py-2.5 rounded-xl text-xs border border-gray-300 shadow-xs flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer" style="{{ $pendingCuts > 0 ? 'color: #1f2937 !important;' : 'background-color: #4f46e5; color: #ffffff !important;' }}">
                        <i class="fas fa-rotate {{ $pendingCuts > 0 ? 'text-gray-600' : 'text-white' }}"></i>
                        <span>Forçar Refatiar Todos os {{ $stats['total_items'] }} Vídeos</span>
                    </button>

                    <button type="button" onclick="toggleStep4Modal(false)" class="w-full px-4 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 transition text-center cursor-pointer">
                        Cancelar
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL DE PROGRESSO DE FATIAMENTO EM TEMPO REAL (PASSO 4) -->
<div id="modal-batch-render-progress" class="fixed inset-0 bg-gray-900/80 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl text-gray-900 animate-in fade-in zoom-in-95 duration-150">
        
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 shrink-0">
                    <i class="fas fa-scissors text-base animate-pulse"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-900" id="batch-render-modal-title">Fatiando Vídeos com FFmpeg</h3>
                    <p class="text-xs text-gray-500 font-medium" id="batch-render-modal-subtitle">Passo 4 de 4 • Processando cortes individuais</p>
                </div>
            </div>
            <span id="batch-render-pct-badge" class="px-2.5 py-1 rounded-xl text-xs font-mono font-black bg-indigo-600 text-white shadow-xs" style="background-color: #4f46e5; color: #ffffff !important;">0%</span>
        </div>

        <!-- Card do Item Atual Sendo Cortado -->
        <div id="batch-render-current-card" class="bg-indigo-50/70 rounded-2xl p-4 border border-indigo-200 mb-4 space-y-2">
            <div class="flex items-center justify-between text-xs font-bold text-indigo-950">
                <span class="flex items-center gap-1.5">
                    <i class="fas fa-film text-indigo-600"></i> Cortando agora:
                </span>
                <span id="batch-render-counter" class="font-mono text-indigo-800">0 / 0</span>
            </div>
            <div class="flex items-center gap-2.5">
                <span id="batch-render-item-code" class="px-2 py-0.5 rounded-lg text-xs font-black bg-indigo-600 text-white shrink-0 shadow-2xs" style="background-color: #4f46e5; color: #ffffff !important;">#-</span>
                <h4 id="batch-render-item-name" class="text-sm font-extrabold text-gray-900 truncate">Iniciando...</h4>
            </div>
            <div class="text-[11px] text-gray-600 flex items-center justify-between pt-1 font-medium">
                <span id="batch-render-time-range" class="font-mono text-gray-700 font-bold">00:00:00 ➔ 00:00:00</span>
                <span id="batch-render-item-spinner" class="flex items-center gap-1 text-indigo-800 font-bold">
                    <i class="fas fa-circle-notch fa-spin text-[10px]"></i> Processando FFmpeg...
                </span>
            </div>
        </div>

        <!-- Barra de Progresso Geral -->
        <div class="space-y-1.5 mb-4">
            <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                <span>Progresso Geral</span>
                <span id="batch-render-pct-text">0% concluído</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-3.5 overflow-hidden border border-gray-300">
                <div id="batch-render-progress-bar" class="h-full bg-indigo-600 transition-all duration-300 rounded-full" style="width: 0%; background-color: #4f46e5;"></div>
            </div>
        </div>

        <!-- Estatísticas em Tempo Real -->
        <div class="grid grid-cols-3 gap-2 mb-4 text-center">
            <div class="bg-green-50 border border-green-200 rounded-xl p-2.5 shadow-2xs">
                <div id="batch-render-success-count" class="text-base font-black text-green-800 font-mono">0</div>
                <div class="text-[10px] font-bold text-green-950 uppercase">Prontos</div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-2.5 shadow-2xs">
                <div id="batch-render-pending-count" class="text-base font-black text-amber-800 font-mono">0</div>
                <div class="text-[10px] font-bold text-amber-950 uppercase">Restantes</div>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-2.5 shadow-2xs">
                <div id="batch-render-failed-count" class="text-base font-black text-gray-800 font-mono">0</div>
                <div class="text-[10px] font-bold text-gray-600 uppercase">Falhas</div>
            </div>
        </div>

        <!-- Rodapé do Modal -->
        <div class="pt-3 border-t border-gray-200 flex items-center justify-between">
            <p id="batch-render-footer-tip" class="text-[11px] text-gray-500 font-medium">
                <i class="fas fa-info-circle text-indigo-600 mr-1"></i> Mantenha esta aba aberta enquanto corta.
            </p>
            <div id="batch-render-actions">
                <button type="button" id="btn-cancel-batch-render" onclick="cancelBatchRender()" class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 transition cursor-pointer shadow-xs">
                    Parar Fatiamento
                </button>
            </div>
        </div>

    </div>
</div>

<!-- MODAL TRANSCRIÇÃO -->
<div id="modal-transcription" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-2xl w-full p-6 shadow-2xl flex flex-col max-h-[85vh] text-gray-800">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4 shrink-0">
            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <i class="fas fa-closed-captioning text-teal-600"></i> Transcrição da Live com Timestamps
            </h3>
            <button onclick="toggleTranscriptionModal()" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2 pr-1" id="transcription-sentences-list">
            @forelse($transcriptionData as $idx => $st)
                <div onclick="seekPlayer({{ $st['start'] ?? 0 }})" class="p-2.5 rounded-xl bg-gray-50 hover:bg-indigo-50 border border-gray-200 cursor-pointer transition flex items-start gap-2.5">
                    <span class="text-[10px] font-mono font-bold bg-indigo-100 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded-lg shrink-0 mt-0.5">
                        {{ gmdate(($st['start'] ?? 0) >= 3600 ? 'H:i:s' : 'i:s', (int) ($st['start'] ?? 0)) }}
                    </span>
                    <p class="text-xs text-gray-800 leading-relaxed font-medium">{{ $st['text'] ?? '' }}</p>
                </div>
            @empty
                <div class="text-center py-10 text-gray-400">
                    <i class="fas fa-comment-slash text-3xl mb-2 text-gray-300"></i>
                    <p class="text-xs font-bold text-gray-700">Nenhuma frase transcrita ainda.</p>
                    <p class="text-[11px] text-gray-500 mt-1">Cole a transcrição abaixo em formato JSON ou utilize a captura em tempo real.</p>
                </div>
            @endforelse
        </div>

        <div class="pt-4 mt-3 border-t border-gray-200 shrink-0 flex flex-wrap justify-between items-center gap-2">
            <div class="flex items-center gap-2">
                <button type="button" onclick="transcribeAudioWithAI()" id="btn-transcribe-ai" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs px-3 py-1.5 rounded-xl transition shadow-xs flex items-center gap-1.5 cursor-pointer" style="background-color: #0d9488; color: #ffffff !important;">
                    <i class="fas fa-microphone-lines text-white"></i>
                    <span style="color: #ffffff !important; font-weight: 800;">Transcrever Áudio com IA</span>
                </button>
                <button type="button" onclick="promptPasteTranscription()" class="text-xs text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                    <i class="fas fa-paste"></i> Colar JSON
                </button>
            </div>
            <button type="button" onclick="toggleTranscriptionModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-4 py-2 rounded-xl transition border border-gray-200">Fechar</button>
        </div>
    </div>
</div>

<!-- MODAL DE BUSCA NA TRANSCRIÇÃO POR ITEM -->
<div id="modal-item-transcription-search" class="fixed inset-0 bg-gray-900/80 backdrop-blur-xs z-50 flex items-center justify-center hidden p-3 sm:p-5">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-2xl w-full p-4 sm:p-5 shadow-2xl flex flex-col h-[85vh] max-h-[85vh] min-h-0 text-gray-900 overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-2.5 border-b border-gray-200 mb-2.5 shrink-0">
            <div class="min-w-0 pr-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-lg text-xs font-black bg-purple-700 text-white shadow-xs" style="background-color: #6b21a8; color: #ffffff !important;">
                        Item #<span id="trans-modal-item-code">-</span>
                    </span>
                    <h3 class="text-sm sm:text-base font-extrabold text-gray-900 truncate max-w-md" id="trans-modal-item-name">Buscar na Transcrição</h3>
                    <span id="trans-modal-item-sku" class="text-xs text-gray-500 font-bold"></span>
                </div>
            </div>
            <button type="button" onclick="closeTranscriptionSearchModal()" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none cursor-pointer p-1" title="Fechar">&times;</button>
        </div>

        <!-- Barra de Busca e Sugestões -->
        <div class="space-y-1.5 mb-2 shrink-0">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-purple-700">
                    <i class="fas fa-search text-xs"></i>
                </div>
                <input type="text" 
                       id="trans-modal-search-input" 
                       oninput="handleTranscriptionModalSearchInput(this.value)"
                       placeholder="Filtrar falas deste item ou digitar palavra..." 
                       class="w-full pl-8 pr-16 py-1.5 text-xs bg-gray-50 text-gray-900 rounded-xl border border-gray-300 focus:bg-white focus:border-purple-600 focus:ring-1 focus:ring-purple-200 font-bold transition shadow-2xs">
                <div class="absolute inset-y-0 right-0 flex items-center pr-1.5">
                    <button type="button" 
                            onclick="clearTranscriptionModalSearch()" 
                            class="text-[11px] text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 px-2 py-0.5 rounded-lg font-bold cursor-pointer transition border border-gray-300" 
                            title="Limpar busca">
                        Limpar
                    </button>
                </div>
            </div>

            <!-- Sugestões / Pílulas de Termos do Item -->
            <div class="flex items-center gap-1 flex-wrap text-xs font-semibold text-gray-700">
                <span class="text-[10px] text-gray-500 uppercase font-bold mr-0.5 flex items-center gap-1">
                    <i class="fas fa-tags text-purple-700"></i> Termos:
                </span>
                <div id="trans-modal-pills-container" class="flex items-center gap-1 flex-wrap">
                    <!-- Geradas dinamicamente via JS -->
                </div>
            </div>

            <!-- Banner de Alerta quando Minutagem for Aplicada -->
            <div id="trans-modal-applied-alert" class="hidden p-2 rounded-xl bg-green-50 border border-green-300 text-green-950 text-xs font-bold flex items-center justify-between transition shadow-2xs">
                <span id="trans-modal-applied-text" class="flex items-center gap-1 text-[11px]">
                    <i class="fas fa-check-circle text-green-600 text-xs"></i> Minutagem aplicada e salva com sucesso!
                </span>
                <button type="button" onclick="previewCurrentAppliedCut()" class="text-xs text-green-800 hover:text-green-950 font-black underline flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-play text-[9px]"></i> Ouvir Corte
                </button>
            </div>
        </div>

        <!-- Abas de Filtro por Categoria -->
        <div id="trans-modal-tabs-wrapper" class="flex items-center gap-1.5 pb-2 mb-2 border-b border-gray-200 shrink-0 overflow-x-auto">
            <!-- Abas geradas dinamicamente via JS (Código | Descrição | Todas) -->
        </div>

        <!-- Contador de Resultados & Instruções -->
        <div class="flex items-center justify-between text-xs text-gray-600 mb-1.5 px-0.5 shrink-0 font-medium">
            <span>Resultados: <strong id="trans-modal-results-count" class="text-purple-800 font-black">0</strong> trecho(s)</span>
            <span class="text-[11px] text-gray-400">Clique em <strong>Usar no Corte</strong> para salvar</span>
        </div>

        <!-- Lista de Resultados (Scrollable & Strict Flex Containment) -->
        <div class="flex-1 min-h-0 overflow-y-auto space-y-2 pr-1.5 border border-gray-200 rounded-xl p-2 bg-gray-50/70" id="trans-modal-results-list">
            <!-- Renderizado dinamicamente via JS -->
        </div>

        <!-- Footer -->
        <div class="pt-2.5 mt-2.5 border-t border-gray-200 shrink-0 flex items-center justify-between">
            <div class="text-[11px] text-gray-500 flex items-center gap-1 font-medium truncate pr-2">
                <i class="fas fa-info-circle text-indigo-500 shrink-0"></i>
                <span class="truncate">O corte inclui 5s de introdução e dura ~45s.</span>
            </div>
            <button type="button" onclick="closeTranscriptionSearchModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold px-4 py-1.5 rounded-xl transition border border-gray-300 cursor-pointer shadow-xs shrink-0">
                Fechar
            </button>
        </div>

    </div>
</div>

<!-- MODAL AUTO-PROCESSAR INSTAGRAM -->
<div id="modal-auto-process-instagram" class="fixed inset-0 bg-gray-900/75 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white border border-gray-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl text-gray-800 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-4">
            <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                <i class="fab fa-instagram text-pink-600 text-xl"></i>
                <span>Auto-Processar Gravação da Live</span>
            </h3>
            <button type="button" onclick="toggleAutoProcessModal(false)" class="text-gray-400 hover:text-gray-700 text-2xl font-bold leading-none cursor-pointer">&times;</button>
        </div>

        <form id="form-auto-process" onsubmit="submitAutoProcess(event)" class="space-y-4">
            <div class="bg-indigo-50/70 border border-indigo-200 rounded-xl p-3.5 text-xs text-indigo-950">
                <p class="font-bold flex items-center gap-1.5 text-indigo-900">
                    <i class="fas fa-magic text-indigo-600"></i> Fluxo 100% Automático:
                </p>
                <div class="mt-1.5 text-indigo-900 space-y-1 text-[11px] leading-relaxed">
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Baixa o vídeo do Instagram (ou usa o vídeo já carregado)</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Extrai o áudio e transcreve com IA Whisper</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Detecta minutagens e códigos com Severino IA</div>
                    <div><i class="fas fa-check-circle text-indigo-600 mr-1"></i> Gera os cortes (.mp4) e capas para todos os itens</div>
                </div>
            </div>

            <div>
                <label for="auto-process-url-input" class="block text-xs font-bold text-gray-900 mb-1.5 flex items-center justify-between">
                    <span>Link da Publicação / Reel no Instagram:</span>
                    <button type="button" onclick="pasteClipboardToUrlInput()" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-extrabold flex items-center gap-1 cursor-pointer">
                        <i class="fas fa-paste"></i> Colar do Teclado
                    </button>
                </label>
                <input 
                    type="url" 
                    id="auto-process-url-input" 
                    name="url" 
                    onblur="this.value = sanitizeInstagramUrl(this.value)"
                    onpaste="setTimeout(() => this.value = sanitizeInstagramUrl(this.value), 20)"
                    placeholder="Ex: https://www.instagram.com/reel/C... ou https://www.instagram.com/p/..." 
                    class="w-full px-3.5 py-2.5 text-xs bg-white text-gray-900 rounded-xl border border-gray-300 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200 font-medium placeholder-gray-400 outline-none transition"
                >
                <p class="text-[11px] text-gray-600 mt-1.5">
                    Cole o link do Reel/Live postado no Instagram. Se o vídeo já estiver carregado nesta página, pode deixar em branco.
                </p>
            </div>

            <div class="pt-3 flex justify-end gap-2.5 border-t border-gray-200">
                <button type="button" onclick="toggleAutoProcessModal(false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-700 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 transition cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-auto-process" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold px-5 py-2 rounded-xl text-xs shadow-md flex items-center gap-1.5 transition active:scale-95 cursor-pointer">
                    <i class="fas fa-play text-white"></i>
                    <span class="text-white font-black">Iniciar Auto-Processamento</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const liveId = {{ $live->id }};
    const csrfToken = '{{ csrf_token() }}';
    let transcriptionData = @json($transcriptionData ?? []);
    let captionsEnabled = true;
    let activeLiveItemId = null;
    let previewTimer = null;
    let currentItemFilter = 'all';
    let currentItemSearch = '';

    function setItemsFilter(filterType) {
        currentItemFilter = filterType;

        // Atualiza estilo das abas
        document.querySelectorAll('.item-filter-tab').forEach(btn => {
            const f = btn.getAttribute('data-filter');
            if (f === filterType) {
                btn.style.backgroundColor = '#1f2937';
                btn.style.color = '#ffffff';
            } else {
                if (f === 'unminuted') {
                    btn.style.backgroundColor = '#fef3c7';
                    btn.style.color = '#92400e';
                } else {
                    btn.style.backgroundColor = '#f3f4f6';
                    btn.style.color = '#374151';
                }
            }
        });

        // Banner informativo de Não Minutados
        const banner = document.getElementById('filter-unminuted-banner');
        if (banner) {
            if (filterType === 'unminuted') {
                banner.classList.remove('hidden');
            } else {
                banner.classList.add('hidden');
            }
        }

        applyItemCardFilters();
    }

    function handleItemSearch(term) {
        currentItemSearch = String(term || '').toLowerCase().trim().replace(/^#/, '');
        applyItemCardFilters();
    }

    function applyItemCardFilters() {
        const cards = document.querySelectorAll('.item-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const hasCut = card.getAttribute('data-has-cut') === '1';
            const isRendered = card.getAttribute('data-is-rendered') === '1';
            const searchData = (card.getAttribute('data-search') || '').toLowerCase();
            const code = (card.getAttribute('data-code') || '').toLowerCase();

            let matchesTab = true;
            if (currentItemFilter === 'unminuted') {
                matchesTab = !hasCut;
            } else if (currentItemFilter === 'minuted') {
                matchesTab = hasCut;
            } else if (currentItemFilter === 'rendered') {
                matchesTab = isRendered;
            }

            let matchesSearch = true;
            if (currentItemSearch !== '') {
                matchesSearch = searchData.includes(currentItemSearch) || code === currentItemSearch;
            }

            if (matchesTab && matchesSearch) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const emptyNotice = document.getElementById('items-filter-empty-notice');
        if (emptyNotice) {
            if (visibleCount === 0) {
                emptyNotice.classList.remove('hidden');
            } else {
                emptyNotice.classList.add('hidden');
            }
        }

        const countEl = document.getElementById('items-visible-counter');
        if (countEl) countEl.textContent = visibleCount;
    }

    const player = document.getElementById("live-main-player");

    // Sincronizar display de tempo do player e legendas em tempo real
    if (player) {
        player.addEventListener('timeupdate', () => {
            const cur = document.getElementById("player-current-time");
            if (cur) cur.textContent = formatTime(player.currentTime);

            updatePlayerCaptionText(player.currentTime);
        });

        player.addEventListener('loadedmetadata', () => {
            const dur = document.getElementById("player-duration");
            if (dur) dur.textContent = formatTime(player.duration);
        });

        player.addEventListener('play', updatePlayPauseIcon);
        player.addEventListener('pause', updatePlayPauseIcon);
    }

    function togglePlayerCaptions() {
        captionsEnabled = !captionsEnabled;
        const btn = document.getElementById("btn-toggle-captions");
        const container = document.getElementById("live-player-captions");

        if (btn) {
            if (captionsEnabled) {
                btn.style.backgroundColor = '#f59e0b';
                btn.classList.add('shadow-2xs');
                btn.title = "Legendas Ativadas (Clique para desativar)";
            } else {
                btn.style.backgroundColor = '#6b7280';
                btn.classList.remove('shadow-2xs');
                btn.title = "Legendas Desativadas (Clique para ativar)";
            }
        }

        if (!captionsEnabled && container) {
            container.classList.add('hidden');
        } else if (player) {
            updatePlayerCaptionText(player.currentTime);
        }
    }

    function updatePlayerCaptionText(currentTime) {
        const container = document.getElementById("live-player-captions");
        const textPill = document.getElementById("caption-text-pill");
        if (!container || !textPill) return;

        if (!captionsEnabled || !Array.isArray(transcriptionData) || transcriptionData.length === 0) {
            container.classList.add('hidden');
            return;
        }

        // Procura fala correspondente ao tempo atual
        const match = transcriptionData.find(item => {
            const st = parseFloat(item.start ?? item.start_sec ?? 0);
            const en = parseFloat(item.end ?? item.end_sec ?? (st + 3));
            return currentTime >= st && currentTime <= en;
        });

        if (match && match.text && match.text.trim().length > 0) {
            textPill.textContent = match.text.trim();
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }

    function formatTime(sec) {
        if (!sec || isNaN(sec)) return "00:00:00";
        const s = Math.floor(sec);
        const h = Math.floor(s / 3600);
        const m = Math.floor((s % 3600) / 60);
        const rem = s % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(rem).padStart(2, '0')}`;
    }

    function parseTimeToSec(val) {
        if (val === null || val === undefined || val === '') return null;
        if (typeof val === 'number') return val;
        val = String(val).trim().replace(',', '.');
        if (!val.includes(':')) {
            const num = parseFloat(val);
            return isNaN(num) ? null : num;
        }
        const parts = val.split(':').map(p => parseFloat(p.trim()) || 0);
        if (parts.length === 3) {
            return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
        } else if (parts.length === 2) {
            return (parts[0] * 60) + parts[1];
        }
        return null;
    }

    function togglePlayPause() {
        if (!player) return;
        if (player.paused) {
            player.play().catch(() => {});
        } else {
            player.pause();
        }
    }

    function updatePlayPauseIcon() {
        const icon = document.getElementById("icon-play-pause");
        if (!icon || !player) return;
        if (player.paused) {
            icon.className = "fas fa-play";
        } else {
            icon.className = "fas fa-pause";
        }
    }

    function jumpVideo(deltaSec) {
        if (!player) return;
        player.currentTime = Math.max(0, Math.min(player.duration || 999999, player.currentTime + deltaSec));
    }

    function setPlaybackRate(rate) {
        if (!player) return;
        player.playbackRate = rate;
        document.querySelectorAll('.speed-btn').forEach(btn => {
            btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
            btn.classList.add('bg-white', 'text-gray-700', 'border-gray-300');
        });
        const activeBtn = document.getElementById(`btn-speed-${String(rate).replace('.', '_')}`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-white', 'text-gray-700', 'border-gray-300');
            activeBtn.classList.add('bg-indigo-600', 'text-white', 'border-indigo-600', 'shadow-2xs');
        }
    }

    function selectActiveItem(itemId, startTime) {
        activeLiveItemId = itemId;
        document.querySelectorAll('.item-card').forEach(el => {
            el.classList.remove('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
        });
        const activeCard = document.getElementById(`item-card-${itemId}`);
        if (activeCard) {
            activeCard.classList.add('ring-2', 'ring-indigo-500', 'bg-indigo-50/40');
        }

        if (player && startTime !== null && startTime !== undefined && startTime !== '') {
            const sec = parseTimeToSec(startTime);
            if (sec !== null) player.currentTime = sec;
        }
    }

    function markCurrentTimeToActiveItem(type) {
        if (!activeLiveItemId) {
            alert("Selecione um item na lista ao lado primeiro!");
            return;
        }
        if (!player) return;

        const formatted = formatTime(player.currentTime);
        const input = document.getElementById(`input-${type}-${activeLiveItemId}`);
        if (input) {
            input.value = formatted;
            saveItemCutTime(activeLiveItemId);
        }
    }

    function seekPlayer(sec) {
        if (player) {
            player.currentTime = parseFloat(sec);
            player.play().catch(() => {});
            toggleTranscriptionModal();
        }
    }

    function previewItemClip(itemId) {
        if (!player) {
            alert("Carregue o vídeo da live para visualizar o preview.");
            return;
        }

        const startInput = document.getElementById(`input-start-${itemId}`);
        const endInput = document.getElementById(`input-end-${itemId}`);
        const start = parseTimeToSec(startInput ? startInput.value : '');
        const end = parseTimeToSec(endInput ? endInput.value : '');

        if (start === null || end === null || end <= start) {
            alert("Defina os tempos de início e fim válidos no formato HH:MM:SS ou MM:SS (ex: 01:15:20 ou 10:30).");
            return;
        }

        if (previewTimer) clearTimeout(previewTimer);

        player.currentTime = start;
        player.play();

        const durationMs = (end - start) * 1000;
        previewTimer = setTimeout(() => {
            player.pause();
        }, durationMs);
    }

    async function saveItemCutTime(itemId) {
        const startInput = document.getElementById(`input-start-${itemId}`);
        const endInput = document.getElementById(`input-end-${itemId}`);
        const startVal = startInput ? startInput.value.trim() : '';
        const endVal = endInput ? endInput.value.trim() : '';

        const start = parseTimeToSec(startVal);
        const end = parseTimeToSec(endVal);

        if (start === null || end === null || end <= start) {
            return;
        }

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/save-timestamp/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    cut_start_sec: startVal,
                    cut_end_sec: endVal
                })
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById(`item-card-${itemId}`);
                if (card) card.classList.add('border-indigo-400');
                if (startInput && data.cut_start_formatted) startInput.value = data.cut_start_formatted;
                if (endInput && data.cut_end_formatted) endInput.value = data.cut_end_formatted;

                const snipBox = document.getElementById(`snippet-container-${itemId}`);
                const snipText = document.getElementById(`snippet-text-${itemId}`);
                if (snipText && data.snippet !== undefined) snipText.textContent = data.snippet;
                if (snipBox) {
                    if (data.snippet && data.snippet.trim() !== '') {
                        snipBox.classList.remove('hidden');
                    } else {
                        snipBox.classList.add('hidden');
                    }
                }

                const badgeAdj = document.getElementById(`badge-adj-${itemId}`);
                if (badgeAdj) badgeAdj.classList.remove('hidden');
            }
        } catch (e) {
            console.error("Erro ao salvar minutagem:", e);
        }
    }

    async function approveItemCut(itemId) {
        const btn = document.getElementById(`btn-approve-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/approve/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                if (btn) {
                    btn.className = 'p-2 rounded-xl text-xs transition cursor-pointer active:scale-95 shadow-xs flex items-center justify-center bg-amber-500 text-white font-black';
                    btn.innerHTML = '<i class="fas fa-crown"></i>';
                    btn.title = 'Corte memorizado como Padrão-Ouro pelo Severino';
                }
                const badgeGold = document.getElementById(`badge-gold-${itemId}`);
                if (badgeGold) badgeGold.classList.remove('hidden');
                const badgeAdj = document.getElementById(`badge-adj-${itemId}`);
                if (badgeAdj) badgeAdj.classList.add('hidden');
                
                alert('🌟 ' + data.message);
            } else {
                if (btn) btn.innerHTML = oldHtml;
                alert('Atenção: ' + (data.message || 'Não foi possível aprovar o corte.'));
            }
        } catch (e) {
            if (btn) btn.innerHTML = oldHtml;
            alert('Falha na conexão ao aprovar corte.');
        }
    }

    // Atalhos globais de teclado para edição rápida
    document.addEventListener('keydown', (e) => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            if (e.key === 'Enter' && activeLiveItemId) {
                saveItemCutTime(activeLiveItemId);
            }
            return;
        }

        if (!player) return;

        if (e.code === 'Space' || e.key === 'k') {
            e.preventDefault();
            togglePlayPause();
        } else if (e.key === 'ArrowLeft' || e.key === 'j') {
            e.preventDefault();
            jumpVideo(e.shiftKey ? -1 : -5);
        } else if (e.key === 'ArrowRight' || e.key === 'l') {
            e.preventDefault();
            jumpVideo(e.shiftKey ? 1 : 5);
        } else if (e.key.toLowerCase() === 'i' || e.key === '[') {
            e.preventDefault();
            markCurrentTimeToActiveItem('start');
        } else if (e.key.toLowerCase() === 'o' || e.key === ']') {
            e.preventDefault();
            markCurrentTimeToActiveItem('end');
        } else if (e.key.toLowerCase() === 'p' && activeLiveItemId) {
            e.preventDefault();
            previewItemClip(activeLiveItemId);
        } else if (e.key.toLowerCase() === 'c' && activeLiveItemId) {
            e.preventDefault();
            captureThumbnailFromPlayer(activeLiveItemId);
        } else if (e.key === '1') {
            setPlaybackRate(1.0);
        } else if (e.key === '2') {
            setPlaybackRate(1.25);
        } else if (e.key === '3') {
            setPlaybackRate(1.5);
        } else if (e.key === '4') {
            setPlaybackRate(2.0);
        } else if (e.key === '5') {
            setPlaybackRate(0.75);
        }
    });

    let transcribePollTimer = null;


    function startTranscriptionPolling() {
        const progressBox = document.getElementById("ai-process-progress-box");
        if (progressBox) {
            progressBox.classList.remove("hidden");
            progressBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        const btns = [
            document.getElementById("btn-auto-detect-header"),
            document.getElementById("btn-auto-detect"),
            document.getElementById("btn-step2-header"),
            document.getElementById("btn-step3-header"),
            document.getElementById("btn-transcribe-ai"),
            document.getElementById("btn-execute-step2"),
            document.getElementById("btn-execute-step3")
        ].filter(Boolean);

        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Processando IA...`;
        });

        if (transcribePollTimer) clearInterval(transcribePollTimer);

        transcribePollTimer = setInterval(async () => {
            try {
                const res = await fetch(`/admin/lives/${liveId}/cortes/transcribe-status`);
                if (!res.ok) return;
                const data = await res.json();

                if (data.status === 'processing') {
                    const textEl = document.getElementById("ai-process-text");
                    const pctEl = document.getElementById("ai-process-pct");
                    const barEl = document.getElementById("ai-process-bar");
                    const progress = data.progress || 0;
                    if (textEl) textEl.textContent = data.message || 'Processando com IA...';
                    if (pctEl) pctEl.textContent = `${progress}%`;
                    if (barEl) barEl.style.width = `${progress}%`;

                    // Atualiza visualmente o Stepper no topo (Passo 2 ou 3)
                    const msg = (data.message || '').toLowerCase();
                    const isStep2 = msg.includes('transcri') || msg.includes('whisper') || msg.includes('áudio') || msg.includes('audio');
                    if (isStep2) {
                        const b2 = document.getElementById("stepper-badge-2");
                        const s2 = document.getElementById("stepper-status-2");
                        if (b2) b2.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        if (s2) {
                            s2.innerHTML = `⚡ Transcrevendo ${progress}%`;
                            s2.style.color = '#0f766e';
                        }
                    } else {
                        const b3 = document.getElementById("stepper-badge-3");
                        const s3 = document.getElementById("stepper-status-3");
                        if (b3) b3.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        if (s3) {
                            s3.innerHTML = `⚡ Minutando ${progress}%`;
                            s3.style.color = '#7e22ce';
                        }
                    }
                } else if (data.status === 'completed') {
                    clearInterval(transcribePollTimer);
                    const barEl = document.getElementById("ai-process-bar");
                    if (barEl) barEl.style.width = '100%';
                    const textEl = document.getElementById("ai-process-text");
                    if (textEl) textEl.textContent = '✅ ' + (data.message || 'Processamento IA concluído com sucesso! Recarregando...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else if (data.status === 'error') {
                    clearInterval(transcribePollTimer);
                    alert("Atenção: " + (data.message || 'Erro durante o processamento com IA.'));
                    if (progressBox) progressBox.classList.add("hidden");
                    window.location.reload();
                }
            } catch (e) {
                console.error("Erro ao consultar status:", e);
            }
        }, 2000);
    }

    async function transcribeAudioWithAI(skipConfirm = false) {
        if (!skipConfirm && !confirm("Deseja extrair o áudio e gerar a transcrição completa com IA em segundo plano?")) return;

        const btn = document.getElementById("btn-transcribe-ai") || document.getElementById("btn-execute-step2");
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Iniciando Whisper...`;
        }

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/transcribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.is_async) {
                const transModal = document.getElementById("modal-transcription");
                if (transModal && !transModal.classList.contains("hidden")) {
                    toggleTranscriptionModal(false);
                }
                startTranscriptionPolling();
            } else if (data.success) {
                alert("✅ " + data.message);
                window.location.reload();
            } else {
                alert("Atenção: " + data.message);
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao comunicar com o servidor para transcrição de áudio.");
        }
    }

    async function autoDetectWithAI(customStartCode = null, customOnlyUnreviewed = null) {
        const startCodeInput = document.getElementById("global-start-code");
        const startCode = customStartCode !== null 
            ? customStartCode 
            : (startCodeInput && startCodeInput.value.trim() ? parseInt(startCodeInput.value.trim()) : null);
        const onlyUnreviewed = customOnlyUnreviewed !== null 
            ? customOnlyUnreviewed 
            : (startCode ? false : true);

        if (customStartCode === null && customOnlyUnreviewed === null) {
            let confirmMsg = "Deseja que o Severino IA analise a transcrição e detecte automaticamente os cortes de todas as peças?";
            if (startCode) {
                confirmMsg = `Deseja que o Severino IA analise e detecte os cortes a partir da peça #${startCode} em diante? (Os cortes anteriores serão preservados)`;
            }
            if (!confirm(confirmMsg)) return;
        }

        const btns = [
            document.getElementById("btn-auto-detect-header"),
            document.getElementById("btn-auto-detect"),
            document.getElementById("btn-step3-header"),
            document.getElementById("btn-execute-step3")
        ].filter(Boolean);

        btns.forEach(b => {
            b.disabled = true;
            b.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Iniciando Severino...`;
        });

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/auto-detect`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    start_code: startCode,
                    only_unreviewed: onlyUnreviewed
                })
            });

            let data = null;
            try {
                data = await res.json();
            } catch (jsonErr) {
                throw new Error(`Servidor retornou resposta inesperada (${res.status} ${res.statusText})`);
            }

            if (data.is_async) {
                startTranscriptionPolling();
            } else if (data.success) {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
                });
                alert("✅ " + data.message);
                window.location.reload();
            } else {
                btns.forEach(b => {
                    b.disabled = false;
                    b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
                });
                alert("Atenção: " + data.message);
            }
        } catch (e) {
            btns.forEach(b => {
                b.disabled = false;
                b.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i> Auto-Detectar com IA`;
            });
            alert("Erro ao disparar detecção automática: " + e.message);
        }
    }



    async function generateSingleClip(itemId) {
        const btn = document.getElementById(`btn-cut-${itemId}`);
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/generate-single/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            btn.disabled = false;
            btn.innerHTML = oldHtml;

            if (data.success) {
                alert("✅ Corte gerado com sucesso!");
                window.location.reload();
            } else {
                alert("Erro ao cortar: " + data.message);
            }
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            alert("Erro ao comunicar com o servidor para fatiamento de vídeo.");
        }
    }

    // 1. Seleciona uma miniatura candidata e define como capa oficial instantaneamente
    async function selectItemCandidateThumbnail(itemId, thumbPath, thumbUrl, btnElement) {
        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/select-thumbnail/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ thumbnail_path: thumbPath })
            });
            const data = await resp.json();
            if (data.success) {
                // Atualiza o avatar principal do produto no card
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar) {
                    avatar.src = data.thumbnail_url || thumbUrl;
                }

                // Atualiza visualmente o highlight de borda e checkmark no container
                const container = document.getElementById(`candidates-container-${itemId}`);
                if (container) {
                    container.querySelectorAll('.candidate-thumb-btn').forEach(btn => {
                        btn.classList.remove('border-green-500', 'ring-2', 'ring-green-400/50', 'opacity-100');
                        btn.classList.add('border-gray-200', 'opacity-75');
                        const badge = btn.querySelector('.cand-badge-active');
                        if (badge) badge.classList.add('hidden');
                    });
                }

                if (btnElement) {
                    btnElement.classList.remove('border-gray-200', 'opacity-75');
                    btnElement.classList.add('border-green-500', 'ring-2', 'ring-green-400/50', 'opacity-100');
                    const badge = btnElement.querySelector('.cand-badge-active');
                    if (badge) badge.classList.remove('hidden');
                }
            } else {
                alert("Erro ao selecionar miniatura: " + (data.message || 'Falha na requisição.'));
            }
        } catch (e) {
            console.error("Erro ao selecionar miniatura:", e);
            alert("Erro de conexão ao definir capa.");
        }
    }

    // 2. Extrai sob demanda 3 miniaturas inteligentes de alta nitidez com IA/FFmpeg
    async function generateSmartThumbnails(itemId) {
        const btn = document.getElementById(`btn-gen-thumbs-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin text-indigo-600"></i> <span class="text-[10px]">Gerando...</span>`;
        }

        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/generate-thumbnails/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await resp.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success && data.candidates) {
                renderCandidateButtons(itemId, data.candidates, data.primary_url);
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar && data.primary_url) {
                    avatar.src = data.primary_url;
                }
            } else {
                alert("Atenção: " + (data.message || 'Não foi possível gerar as 3 miniaturas.'));
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao comunicar com o servidor para geração de miniaturas.");
        }
    }

    // 3. Renderiza os botões das miniaturas candidatas no DOM
    function renderCandidateButtons(itemId, candidates, activeUrlOrPath) {
        const container = document.getElementById(`candidates-container-${itemId}`);
        if (!container) return;

        let html = `
            <span class="text-[10px] font-bold text-gray-400 uppercase mr-1 flex items-center gap-1">
                <i class="fas fa-images text-indigo-500"></i> Capa:
            </span>
        `;

        candidates.forEach((cand, idx) => {
            const isActive = activeUrlOrPath && (cand.url === activeUrlOrPath || cand.path === activeUrlOrPath || (cand.path && activeUrlOrPath.includes(cand.path.split('/').pop())));
            html += `
                <button type="button" 
                        onclick="selectItemCandidateThumbnail(${itemId}, '${cand.path}', '${cand.url}', this)"
                        title="Clique para definir como capa oficial (${cand.label || 'Opção ' + (idx + 1)})"
                        class="candidate-thumb-btn relative rounded-lg overflow-hidden border-2 transition active:scale-95 cursor-pointer shadow-2xs group/thumb ${isActive ? 'border-green-500 ring-2 ring-green-400/50 opacity-100' : 'border-gray-200 hover:border-indigo-400 opacity-75 hover:opacity-100'}"
                        style="width: 52px; height: 52px;">
                    <img src="${cand.url}" class="w-full h-full object-cover" />
                    <span class="absolute bottom-0 inset-x-0 bg-black/75 text-[8px] font-black text-white text-center py-0.5 truncate px-0.5">
                        ${cand.label || ('Opção ' + (idx + 1))}
                    </span>
                    <span class="cand-badge-active ${isActive ? '' : 'hidden'} absolute top-0.5 right-0.5 bg-green-600 text-white rounded-full w-3.5 h-3.5 flex items-center justify-center text-[8px] font-bold shadow-xs">
                        <i class="fas fa-check"></i>
                    </span>
                </button>
            `;
        });

        container.innerHTML = html;
    }

    // 4. Captura manual de frame do player (com atalho C ou botão da câmera)
    async function captureThumbnailFromPlayer(itemId) {
        const player = document.getElementById('live-main-player');
        const currentTime = player ? player.currentTime : null;
        const btn = document.getElementById(`btn-thumb-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '<i class="fas fa-camera"></i>';
        
        let targetTime = currentTime;
        if (!targetTime || targetTime <= 0) {
            const startInput = document.getElementById(`input-start-${itemId}`);
            targetTime = startInput ? startInput.value : 0;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        try {
            const resp = await fetch(`/admin/lives/${liveId}/cortes/capture-frame/${itemId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ timestamp: targetTime, set_as_cover: true })
            });
            const data = await resp.json();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success) {
                // Atualiza avatar do item
                const avatar = document.getElementById(`item-avatar-${itemId}`);
                if (avatar && data.thumbnail_url) {
                    avatar.src = data.thumbnail_url;
                }

                // Atualiza a galeria de candidatas adicionando a nova captura
                if (data.candidates && data.candidates.length > 0) {
                    renderCandidateButtons(itemId, data.candidates, data.thumbnail_url);
                }

                // Feedback sonoro/visual discreto
                alert(`📸 Foto de capa capturada e definida com sucesso no segundo ${data.timestamp_formatted || targetTime}!`);
            } else {
                alert("Erro ao capturar frame: " + data.message);
            }
        } catch (e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Falha na conexão ao capturar frame do vídeo.");
        }
    }

    let batchRenderCancelled = false;

    function cancelBatchRender() {
        batchRenderCancelled = true;
        const btn = document.getElementById("btn-cancel-batch-render");
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Finalizando corte atual...`;
        }
        const spinner = document.getElementById("batch-render-item-spinner");
        if (spinner) {
            spinner.innerHTML = `<span class="text-amber-800 font-bold"><i class="fas fa-pause-circle"></i> Cancelamento solicitado...</span>`;
        }
    }

    function closeBatchRenderModalAndReload() {
        const modal = document.getElementById("modal-batch-render-progress");
        if (modal) modal.classList.add("hidden");
        window.location.reload();
    }

    async function generateAllClips(forceAll = false) {
        const startCodeInput = document.getElementById("global-start-code");
        const startCode = startCodeInput && startCodeInput.value.trim() ? parseInt(startCodeInput.value.trim()) : null;

        const itemCards = document.querySelectorAll('.item-card');
        const allMinuted = [];
        const pendingOnly = [];

        itemCards.forEach(card => {
            const idMatch = card.id ? card.id.match(/^item-card-(\d+)$/) : null;
            if (!idMatch) return;
            const itemId = idMatch[1];
            const startInput = document.getElementById(`input-start-${itemId}`);
            const endInput = document.getElementById(`input-end-${itemId}`);
            const isRendered = card.getAttribute('data-is-rendered') === '1';
            const codeBadge = card.querySelector('.bg-indigo-600')?.textContent?.trim() || (`#${itemId}`);
            const rawCode = parseInt(codeBadge.replace(/[^0-9]/g, '')) || 0;
            const titleEl = card.querySelector('h4');
            const itemName = titleEl ? titleEl.textContent.trim() : `Item ${codeBadge}`;

            if (startCode && rawCode > 0 && rawCode < startCode) {
                return; // Pula os anteriores ao startCode
            }

            if (startInput && endInput && startInput.value.trim() !== '' && endInput.value.trim() !== '') {
                const itemObj = { 
                    id: itemId, 
                    code: codeBadge, 
                    name: itemName,
                    start: startInput.value.trim(),
                    end: endInput.value.trim(),
                    isRendered: isRendered 
                };
                allMinuted.push(itemObj);
                if (!isRendered) {
                    pendingOnly.push(itemObj);
                }
            }
        });

        if (allMinuted.length === 0) {
            alert(startCode ? `Nenhum item a partir da peça #${startCode} com minutagem definida para cortar.` : "Nenhum item com minutagem definida para cortar.");
            return;
        }

        let itemsToProcess = [];
        if (forceAll) {
            itemsToProcess = allMinuted;
        } else if (pendingOnly.length > 0) {
            itemsToProcess = pendingOnly;
        } else {
            itemsToProcess = allMinuted;
        }

        if (itemsToProcess.length === 0) {
            alert("Nenhum item pendente para fatiar.");
            return;
        }

        // Abre modal de progresso em tempo real
        const modal = document.getElementById("modal-batch-render-progress");
        if (modal) {
            modal.classList.remove("hidden");
        }

        // Reseta estados do modal
        batchRenderCancelled = false;
        const modalTitle = document.getElementById("batch-render-modal-title");
        const modalSubtitle = document.getElementById("batch-render-modal-subtitle");
        const pctBadge = document.getElementById("batch-render-pct-badge");
        const pctText = document.getElementById("batch-render-pct-text");
        const progressBar = document.getElementById("batch-render-progress-bar");
        const successCountEl = document.getElementById("batch-render-success-count");
        const pendingCountEl = document.getElementById("batch-render-pending-count");
        const failedCountEl = document.getElementById("batch-render-failed-count");
        const actionsContainer = document.getElementById("batch-render-actions");
        const itemSpinner = document.getElementById("batch-render-item-spinner");
        const itemCodeEl = document.getElementById("batch-render-item-code");
        const itemNameEl = document.getElementById("batch-render-item-name");
        const timeRangeEl = document.getElementById("batch-render-time-range");
        const counterEl = document.getElementById("batch-render-counter");

        if (modalTitle) modalTitle.textContent = "Fatiando Vídeos com FFmpeg";
        if (modalSubtitle) modalSubtitle.textContent = `Passo 4 de 4 • Processando ${itemsToProcess.length} cortes individuais`;
        if (pctBadge) {
            pctBadge.textContent = "0%";
            pctBadge.style.backgroundColor = "#4f46e5";
        }
        if (pctText) pctText.textContent = `0 de ${itemsToProcess.length} concluídos (0%)`;
        if (progressBar) {
            progressBar.style.width = "0%";
            progressBar.style.backgroundColor = "#4f46e5";
        }
        if (successCountEl) successCountEl.textContent = "0";
        if (pendingCountEl) pendingCountEl.textContent = String(itemsToProcess.length);
        if (failedCountEl) failedCountEl.textContent = "0";
        if (actionsContainer) {
            actionsContainer.innerHTML = `
                <button type="button" id="btn-cancel-batch-render" onclick="cancelBatchRender()" class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 transition cursor-pointer shadow-xs">
                    Parar Fatiamento
                </button>
            `;
        }

        // Atualiza Stepper 4 no topo
        const stepperBadge4 = document.getElementById("stepper-badge-4");
        const stepperStatus4 = document.getElementById("stepper-status-4");
        if (stepperBadge4) stepperBadge4.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
        if (stepperStatus4) {
            stepperStatus4.innerHTML = `⚡ 0/${itemsToProcess.length} (0%)`;
            stepperStatus4.style.color = '#4338ca';
        }

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < itemsToProcess.length; i++) {
            if (batchRenderCancelled) {
                break;
            }

            const item = itemsToProcess[i];
            const currentIdx = i + 1;
            const pct = Math.round(((i) / itemsToProcess.length) * 100);

            // Atualiza Card do Item Atual
            if (counterEl) counterEl.textContent = `${currentIdx} / ${itemsToProcess.length}`;
            if (itemCodeEl) itemCodeEl.textContent = item.code;
            if (itemNameEl) itemNameEl.textContent = item.name;
            if (timeRangeEl) timeRangeEl.textContent = `${item.start} ➔ ${item.end}`;
            if (itemSpinner) {
                itemSpinner.innerHTML = `<i class="fas fa-circle-notch fa-spin text-[10px]"></i> Processando corte com FFmpeg...`;
            }

            try {
                const res = await fetch(`/admin/lives/${liveId}/cortes/generate-single/${item.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                
                if (data.success) {
                    successCount++;
                    const card = document.getElementById(`item-card-${item.id}`);
                    if (card) {
                        card.setAttribute('data-is-rendered', '1');
                        card.classList.remove('border-gray-200', 'border-indigo-300');
                        card.classList.add('border-green-400', 'bg-green-50/20');
                    }
                } else {
                    failCount++;
                    console.warn(`Falha ao fatiar item ${item.id}:`, data.message);
                }
            } catch (e) {
                failCount++;
                console.error(`Erro de conexão ao fatiar item ${item.id}:`, e);
            }

            const completedPct = Math.round(((currentIdx) / itemsToProcess.length) * 100);
            const remainingCount = Math.max(0, itemsToProcess.length - (successCount + failCount));

            if (pctBadge) pctBadge.textContent = `${completedPct}%`;
            if (pctText) pctText.textContent = `${currentIdx} de ${itemsToProcess.length} concluídos (${completedPct}%)`;
            if (progressBar) progressBar.style.width = `${completedPct}%`;
            if (successCountEl) successCountEl.textContent = String(successCount);
            if (pendingCountEl) pendingCountEl.textContent = String(remainingCount);
            if (failedCountEl) failedCountEl.textContent = String(failCount);

            if (stepperStatus4) {
                stepperStatus4.innerHTML = `⚡ ${currentIdx}/${itemsToProcess.length} (${completedPct}%)`;
            }
        }

        // Finalização
        if (batchRenderCancelled) {
            if (modalTitle) modalTitle.textContent = "Fatiamento Pausado";
            if (modalSubtitle) modalSubtitle.textContent = `${successCount} vídeo(s) gerado(s) com sucesso antes da pausa.`;
            if (itemSpinner) {
                itemSpinner.innerHTML = `<span class="text-amber-800 font-bold"><i class="fas fa-pause-circle"></i> Fatiamento pausado</span>`;
            }
            if (actionsContainer) {
                actionsContainer.innerHTML = `
                    <button type="button" onclick="closeBatchRenderModalAndReload()" class="px-5 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-700 text-white shadow-md cursor-pointer flex items-center gap-1.5 transition active:scale-95" style="background-color: #4f46e5; color: #ffffff !important;">
                        <i class="fas fa-check text-white"></i>
                        <span>Fechar e Atualizar Lista</span>
                    </button>
                `;
            }
            if (stepperStatus4) {
                stepperStatus4.innerHTML = `Pausado (${successCount} prontos)`;
            }
        } else {
            if (modalTitle) modalTitle.textContent = "🎉 Fatiamento Concluído!";
            if (modalSubtitle) modalSubtitle.textContent = `Todos os ${successCount} cortes foram gerados com sucesso!`;
            if (progressBar) {
                progressBar.style.width = "100%";
                progressBar.style.backgroundColor = "#059669";
            }
            if (pctBadge) {
                pctBadge.textContent = "100%";
                pctBadge.style.backgroundColor = "#059669";
            }
            if (pctText) pctText.textContent = `100% concluído (${successCount} vídeos prontos)`;
            if (itemSpinner) {
                itemSpinner.innerHTML = `<span class="text-emerald-700 font-black"><i class="fas fa-check-circle"></i> Concluído com sucesso</span>`;
            }
            if (actionsContainer) {
                actionsContainer.innerHTML = `
                    <button type="button" onclick="closeBatchRenderModalAndReload()" class="px-5 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md cursor-pointer flex items-center gap-1.5 transition active:scale-95" style="background-color: #059669; color: #ffffff !important;">
                        <i class="fas fa-check text-white"></i>
                        <span>Concluir e Ver Cortes</span>
                    </button>
                `;
            }
            if (stepperBadge4) stepperBadge4.innerHTML = `<i class="fas fa-check"></i>`;
            if (stepperStatus4) {
                stepperStatus4.innerHTML = `✓ ${successCount} prontos`;
                stepperStatus4.style.color = '#15803d';
            }
        }
    }

    // ==========================================
    // CONTROLE DOS MODAIS DE CADA PASSO (1 A 4)
    // ==========================================
    function openStep1Modal() {
        toggleUploadModal(true);
    }

    function toggleUploadModal(show = null) {
        const modal = document.getElementById("modal-upload-video");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }

    async function pasteClipboardToManualPath() {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                const input = document.getElementById("video-path-manual-input");
                if (input) {
                    input.value = sanitizeInstagramUrl(text);
                    input.focus();
                }
            }
        } catch (e) {
            console.warn("Clipboard access not available or denied:", e);
        }
    }

    function openStep2Modal() {
        toggleStep2Modal(true);
    }

    function toggleStep2Modal(show = null) {
        const modal = document.getElementById("modal-step2-transcription");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }

    async function executeStep2Transcription() {
        toggleStep2Modal(false);
        await transcribeAudioWithAI(true);
    }

    function openStep3Modal() {
        toggleStep3Modal(true);
    }

    function toggleStep3Modal(show = null) {
        const modal = document.getElementById("modal-step3-minutagem");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }

    async function executeStep3Minutagem() {
        const modeRadio = document.querySelector('input[name="step3_mode"]:checked');
        const mode = modeRadio ? modeRadio.value : 'unreviewed';
        let startCode = null;
        let onlyUnreviewed = true;

        if (mode === 'all') {
            onlyUnreviewed = false;
        } else if (mode === 'from_code') {
            const codeInput = document.getElementById("step3-start-code");
            startCode = codeInput && codeInput.value ? parseInt(codeInput.value) : null;
            onlyUnreviewed = false;
        }

        toggleStep3Modal(false);
        await autoDetectWithAI(startCode, onlyUnreviewed);
    }

    function openStep4Modal() {
        toggleStep4Modal(true);
    }

    function toggleStep4Modal(show = null) {
        const modal = document.getElementById("modal-step4-fatiamento");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }

    function toggleTranscriptionModal(show = null) {
        const modal = document.getElementById("modal-transcription");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
    }

    // ==========================================
    // BUSCA INTELIGENTE NA TRANSCRIÇÃO POR ITEM
    // ==========================================
    let currentSearchItemId = null;
    let currentSearchItemCode = '';
    let currentSearchItemName = '';
    let currentSearchItemCodigo = '';
    let currentSearchActiveTab = 'all'; // 'all' | 'code' | 'product' | 'custom'
    let currentSearchCachedMatches = []; // Armazena os matches do item atual
    let currentCustomMatches = null; // Armazena matches de busca manual customizada
    let transcriptionSearchDebounce = null;
    let currentAppliedCutStart = null;
    let currentAppliedCutEnd = null;

    const PORTUGUESE_STOPWORDS = new Set([
        'de', 'da', 'do', 'das', 'dos', 'em', 'no', 'na', 'nos', 'nas',
        'com', 'sem', 'para', 'por', 'e', 'ou', 'um', 'uma', 'uns', 'umas',
        'tam', 'tamanho', 'tamanhos', 'cor', 'marca', 'novo', 'nova', 'seminovo', 'seminova',
        'usado', 'usada', 'peca', 'pecas', 'item', 'codigo', 'cod', 'ref',
        'live', 'unidade', 'pronta', 'entrega', 'frete', 'oferta', 'promocao',
        'lindo', 'linda', 'maravilhoso', 'maravilhosa', 'super', 'muito', 'mais',
        'original', 'estilo', 'colecao', 'modelo', 'tipo', 'sobre', 'esse', 'essa',
        'aqui', 'olha', 'gente', 'meninas', 'menina', 'bora', 'quem', 'quer'
    ]);

    function numberToPortugueseWords(val) {
        const num = parseInt(val, 10);
        if (isNaN(num) || num <= 0 || num > 999) return '';
        if (num === 100) return 'cem';

        const units = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
        const teens = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        const tens = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        const hundreds = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

        const h = Math.floor(num / 100);
        const rem = num % 100;
        const t = Math.floor(rem / 10);
        const u = rem % 10;
        let parts = [];
        if (h > 0) parts.push(hundreds[h]);
        if (rem >= 10 && rem < 20) {
            parts.push(teens[rem - 10]);
        } else {
            if (t > 1) parts.push(tens[t]);
            if (u > 0) parts.push(units[u]);
        }
        return parts.join(' e ');
    }

    function normalizeSearchText(str) {
        if (!str) return '';
        return String(str)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function extractProductKeywords(name) {
        if (!name) return [];
        const normalized = normalizeSearchText(name);
        const words = normalized.split(/\s+/);
        const valid = [];
        for (const w of words) {
            if (w.length >= 3 && !/^\d+$/.test(w) && !PORTUGUESE_STOPWORDS.has(w)) {
                if (!valid.includes(w)) {
                    valid.push(w);
                }
            }
        }
        return valid;
    }

    function hasTokenMatch(normSentence, token) {
        if (!token) return false;
        const clean = normalizeSearchText(token);
        if (!clean) return false;
        const regex = new RegExp(`(?:^|\\s)${escapeRegex(clean)}(?:\\s|$)`);
        return regex.test(normSentence);
    }

    function openTranscriptionSearchModalFromButton(btn) {
        if (!btn) return;
        const itemId = btn.getAttribute('data-item-id');
        const liveCode = btn.getAttribute('data-live-code');
        const itemName = btn.getAttribute('data-item-name');
        const itemCodigo = btn.getAttribute('data-item-codigo');
        openTranscriptionSearchModal(itemId, liveCode, itemName, itemCodigo);
    }

    function openTranscriptionSearchModal(itemId, liveCode, itemName, itemCodigo) {
        currentSearchItemId = itemId;
        currentSearchItemCode = String(liveCode || '').trim();
        currentSearchItemName = String(itemName || '').trim();
        currentSearchItemCodigo = String(itemCodigo || '').trim();
        currentCustomMatches = null;

        // Atualiza cabeçalho
        const codeEl = document.getElementById("trans-modal-item-code");
        const nameEl = document.getElementById("trans-modal-item-name");
        const skuEl = document.getElementById("trans-modal-item-sku");
        if (codeEl) codeEl.textContent = currentSearchItemCode || '-';
        if (nameEl) nameEl.textContent = currentSearchItemName || 'Produto da Live';
        if (skuEl) skuEl.textContent = currentSearchItemCodigo ? `• Cód: ${currentSearchItemCodigo}` : '';

        // Esconde alerta anterior
        const alertBox = document.getElementById("trans-modal-applied-alert");
        if (alertBox) alertBox.classList.add("hidden");

        // Limpa campo de busca livre
        const searchInput = document.getElementById("trans-modal-search-input");
        if (searchInput) searchInput.value = '';

        // Calcula os trechos correspondentes ao item
        currentSearchCachedMatches = findItemMatchesInTranscription();

        // Se houver correspondência com o código da peça, prioriza a aba do código por padrão
        const codeMatchesCount = currentSearchCachedMatches.filter(m => m.categories.includes('code') || m.categories.includes('words')).length;
        currentSearchActiveTab = codeMatchesCount > 0 ? 'code' : 'all';

        // Renderiza pílulas de termos
        renderTranscriptionPills();

        // Renderiza abas de filtro
        renderTranscriptionTabs();

        // Renderiza lista filtrada de resultados
        renderTranscriptionResults();

        // Abre modal
        const modal = document.getElementById("modal-item-transcription-search");
        if (modal) modal.classList.remove("hidden");
    }

    function findItemMatchesInTranscription() {
        if (!Array.isArray(transcriptionData) || transcriptionData.length === 0) {
            return [];
        }

        const code = currentSearchItemCode;
        const numberWords = code ? numberToPortugueseWords(code) : '';
        const normNumberWords = numberWords ? normalizeSearchText(numberWords) : '';
        const productKeywords = extractProductKeywords(currentSearchItemName);
        const sku = currentSearchItemCodigo;

        const matches = [];

        for (let i = 0; i < transcriptionData.length; i++) {
            const item = transcriptionData[i];
            const sentenceText = item.text || '';
            const normSentence = normalizeSearchText(sentenceText);

            const reasons = [];
            const categories = new Set();
            const highlightTerms = new Set();

            // 1. Código exato do item (token isolado)
            if (code && hasTokenMatch(normSentence, code)) {
                categories.add('code');
                reasons.push({ type: 'code', label: `🎯 Código #${code}`, term: code });
                highlightTerms.add(code);
            }

            // 2. Número por extenso (ex: "cento e trinta")
            if (normNumberWords && normSentence.includes(normNumberWords)) {
                categories.add('words');
                reasons.push({ type: 'words', label: `🗣️ Por Extenso: "${numberWords}"`, term: numberWords });
                highlightTerms.add(numberWords);
            }

            // 3. Palavras do produto/descrição (ex: "colar", "elos")
            productKeywords.forEach(kw => {
                if (hasTokenMatch(normSentence, kw)) {
                    categories.add('product');
                    reasons.push({ type: 'product', label: `🏷️ Peça: "${kw}"`, term: kw });
                    highlightTerms.add(kw);
                }
            });

            // 4. SKU se existir
            if (sku && sku !== code && normSentence.includes(normalizeSearchText(sku))) {
                categories.add('sku');
                reasons.push({ type: 'sku', label: `🔖 SKU: ${sku}`, term: sku });
                highlightTerms.add(sku);
            }

            if (categories.size > 0) {
                matches.push({
                    index: i,
                    start: parseFloat(item.start ?? item.start_sec ?? 0),
                    end: parseFloat(item.end ?? item.end_sec ?? 0),
                    text: sentenceText,
                    categories: Array.from(categories),
                    reasons: reasons,
                    highlightTerms: Array.from(highlightTerms),
                    hasDirectCode: categories.has('code') || categories.has('words'),
                    prevText: i > 0 ? transcriptionData[i - 1]?.text : null,
                    nextText: i < transcriptionData.length - 1 ? transcriptionData[i + 1]?.text : null
                });
            }
        }

        return matches;
    }

    function renderTranscriptionPills() {
        const pillsContainer = document.getElementById("trans-modal-pills-container");
        if (!pillsContainer) return;
        pillsContainer.innerHTML = '';

        const numberWords = currentSearchItemCode ? numberToPortugueseWords(currentSearchItemCode) : '';
        const productKeywords = extractProductKeywords(currentSearchItemName);

        const pills = [];
        if (currentSearchItemCode) {
            pills.push({ label: `#${currentSearchItemCode}`, query: currentSearchItemCode });
        }
        if (numberWords && numberWords !== currentSearchItemCode) {
            pills.push({ label: numberWords, query: numberWords });
        }
        productKeywords.forEach(kw => {
            pills.push({ label: kw, query: kw });
        });

        pills.forEach(p => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "px-2 py-0.5 rounded-md text-[11px] font-bold bg-white hover:bg-purple-50 text-purple-900 border border-purple-200 transition cursor-pointer active:scale-95 shadow-2xs";
            btn.textContent = p.label;
            btn.onclick = () => {
                const input = document.getElementById("trans-modal-search-input");
                if (input) input.value = p.query;
                executeCustomSearch(p.query);
            };
            pillsContainer.appendChild(btn);
        });

        const resetBtn = document.createElement("button");
        resetBtn.type = "button";
        resetBtn.className = "px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-50 hover:bg-purple-100 text-purple-950 border border-purple-300 transition cursor-pointer active:scale-95";
        resetBtn.innerHTML = '<i class="fas fa-rotate-left mr-0.5 text-[10px]"></i> Ver todas';
        resetBtn.onclick = () => {
            clearTranscriptionModalSearch();
        };
        pillsContainer.appendChild(resetBtn);
    }

    function renderTranscriptionTabs() {
        const wrapper = document.getElementById("trans-modal-tabs-wrapper");
        if (!wrapper) return;

        if (currentCustomMatches !== null) {
            wrapper.innerHTML = `
                <div class="flex items-center gap-1.5">
                    <span class="px-2.5 py-1 rounded-lg text-xs bg-purple-700 text-white font-black shadow-2xs" style="background-color: #6b21a8; color: #ffffff !important;">
                        🔍 Busca: "${escapeHtml(document.getElementById("trans-modal-search-input")?.value || '')}"
                    </span>
                    <button type="button" onclick="clearTranscriptionModalSearch()" class="px-2 py-1 rounded-lg text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300 cursor-pointer">
                        ✕ Limpar
                    </button>
                </div>
            `;
            return;
        }

        const countAll = currentSearchCachedMatches.length;
        const countCode = currentSearchCachedMatches.filter(m => m.categories.includes('code') || m.categories.includes('words')).length;
        const countProduct = currentSearchCachedMatches.filter(m => m.categories.includes('product')).length;

        const tabs = [];
        if (countCode > 0) {
            tabs.push({ id: 'code', label: `🎯 Código #${currentSearchItemCode} (${countCode})`, show: true });
        }
        tabs.push({ id: 'all', label: `⭐ Todas (${countAll})`, show: true });
        if (countProduct > 0 && countProduct !== countAll) {
            tabs.push({ id: 'product', label: `🏷️ Descrição / Peça (${countProduct})`, show: true });
        }

        let html = '';
        tabs.forEach(tab => {
            const isActive = currentSearchActiveTab === tab.id;
            const activeClass = isActive 
                ? 'bg-purple-700 text-white shadow-2xs font-black' 
                : 'bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold border border-gray-200';
            const inlineStyle = isActive ? 'background-color: #6b21a8; color: #ffffff !important;' : '';

            html += `
                <button type="button" 
                        onclick="switchTranscriptionTab('${tab.id}')"
                        class="px-2.5 py-1 rounded-lg text-xs whitespace-nowrap transition cursor-pointer active:scale-95 ${activeClass}"
                        style="${inlineStyle}">
                    ${tab.label}
                </button>
            `;
        });

        wrapper.innerHTML = html;
    }

    function switchTranscriptionTab(tabId) {
        currentSearchActiveTab = tabId;
        currentCustomMatches = null;
        const searchInput = document.getElementById("trans-modal-search-input");
        if (searchInput) searchInput.value = '';
        renderTranscriptionTabs();
        renderTranscriptionResults();
    }

    function closeTranscriptionSearchModal() {
        const modal = document.getElementById("modal-item-transcription-search");
        if (modal) modal.classList.add("hidden");
    }

    function clearTranscriptionModalSearch() {
        const searchInput = document.getElementById("trans-modal-search-input");
        if (searchInput) {
            searchInput.value = '';
        }
        currentCustomMatches = null;
        currentSearchActiveTab = 'all';
        renderTranscriptionTabs();
        renderTranscriptionResults();
    }

    function handleTranscriptionModalSearchInput(val) {
        clearTimeout(transcriptionSearchDebounce);
        transcriptionSearchDebounce = setTimeout(() => {
            const raw = String(val || '').trim();
            if (!raw) {
                clearTranscriptionModalSearch();
            } else {
                executeCustomSearch(raw);
            }
        }, 180);
    }

    function executeCustomSearch(term) {
        const raw = String(term || '').trim();
        if (!raw) {
            clearTranscriptionModalSearch();
            return;
        }

        const normQuery = normalizeSearchText(raw);
        const queryTerms = normQuery.split(' ').filter(Boolean);
        const numberWords = numberToPortugueseWords(raw);
        const normNumberWords = numberWords ? normalizeSearchText(numberWords) : '';

        const matches = [];

        for (let i = 0; i < transcriptionData.length; i++) {
            const item = transcriptionData[i];
            const sentenceText = item.text || '';
            const normSentence = normalizeSearchText(sentenceText);

            let isMatch = false;
            let matchedTerm = raw;

            if (hasTokenMatch(normSentence, normQuery) || normSentence.includes(normQuery)) {
                isMatch = true;
                matchedTerm = raw;
            } else if (normNumberWords && normSentence.includes(normNumberWords)) {
                isMatch = true;
                matchedTerm = numberWords;
            } else if (queryTerms.length > 1 && queryTerms.every(t => normSentence.includes(t))) {
                isMatch = true;
                matchedTerm = queryTerms.join(' ');
            }

            if (isMatch) {
                matches.push({
                    index: i,
                    start: parseFloat(item.start ?? item.start_sec ?? 0),
                    end: parseFloat(item.end ?? item.end_sec ?? 0),
                    text: sentenceText,
                    categories: ['custom'],
                    reasons: [{ type: 'custom', label: `🔍 "${raw}"`, term: matchedTerm }],
                    highlightTerms: [matchedTerm, raw],
                    hasDirectCode: false,
                    prevText: i > 0 ? transcriptionData[i - 1]?.text : null,
                    nextText: i < transcriptionData.length - 1 ? transcriptionData[i + 1]?.text : null
                });
            }
        }

        currentCustomMatches = matches;
        currentSearchActiveTab = 'custom';
        renderTranscriptionTabs();
        renderTranscriptionResults();
    }

    function renderTranscriptionResults() {
        const resultsList = document.getElementById("trans-modal-results-list");
        const countEl = document.getElementById("trans-modal-results-count");
        if (!resultsList) return;

        if (!Array.isArray(transcriptionData) || transcriptionData.length === 0) {
            resultsList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-file-circle-exclamation text-3xl text-amber-500 mb-2"></i>
                    <p class="font-bold text-gray-800 text-sm">Nenhuma transcrição disponível para esta live.</p>
                    <p class="text-xs text-gray-500 mt-1">Transcreva o áudio primeiro para pesquisar os trechos.</p>
                </div>
            `;
            if (countEl) countEl.textContent = '0';
            return;
        }

        let displayMatches = [];
        if (currentCustomMatches !== null) {
            displayMatches = currentCustomMatches;
        } else if (currentSearchActiveTab === 'code') {
            displayMatches = currentSearchCachedMatches.filter(m => m.categories.includes('code') || m.categories.includes('words'));
        } else if (currentSearchActiveTab === 'product') {
            displayMatches = currentSearchCachedMatches.filter(m => m.categories.includes('product'));
        } else {
            displayMatches = currentSearchCachedMatches;
        }

        if (countEl) countEl.textContent = String(displayMatches.length);

        if (displayMatches.length === 0) {
            if (currentCustomMatches !== null) {
                resultsList.innerHTML = `
                    <div class="text-center py-8 text-gray-500 bg-white rounded-xl border border-dashed border-gray-300 p-6">
                        <i class="fas fa-comment-slash text-3xl mb-2 text-gray-400"></i>
                        <p class="text-xs font-bold text-gray-800">Nenhum trecho encontrado com a busca manual.</p>
                        <button type="button" onclick="clearTranscriptionModalSearch()" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-purple-50 text-purple-900 border border-purple-200 hover:bg-purple-100 cursor-pointer">
                            <i class="fas fa-rotate-left"></i> Voltar às falas do item #${currentSearchItemCode}
                        </button>
                    </div>
                `;
            } else {
                resultsList.innerHTML = `
                    <div class="text-center py-8 bg-amber-50/60 rounded-xl border border-dashed border-amber-300 p-6">
                        <i class="fas fa-search text-3xl mb-2 text-amber-600"></i>
                        <p class="text-xs font-bold text-gray-900">Nenhuma fala identificada automaticamente com o código #${currentSearchItemCode} ou com os termos de "${escapeHtml(currentSearchItemName)}".</p>
                        <p class="text-[11px] text-gray-600 mt-1 max-w-md mx-auto">
                            Tente digitar palavras-chave no campo de busca acima (ex: tipo de peça, detalhes, cor) ou confira a transcrição completa.
                        </p>
                    </div>
                `;
            }
            return;
        }

        // Ordena priorizando correspondência de código e depois por ordem cronológica
        displayMatches.sort((a, b) => {
            if (a.hasDirectCode && !b.hasDirectCode) return -1;
            if (!a.hasDirectCode && b.hasDirectCode) return 1;
            return a.start - b.start;
        });

        // Renderiza cards compactos dos trechos correspondentes
        let html = '';
        displayMatches.forEach((m) => {
            const startFmt = formatTime(m.start);
            const durationSec = Math.max(1, Math.round(m.end - m.start));

            const hours = Math.floor(m.start / 3600);
            const mins = Math.floor((m.start % 3600) / 60);
            const relativeTimeStr = hours > 0 ? `${hours}h ${mins}m` : `${mins} min`;

            // Destaque de todos os termos encontrados
            let highlighted = escapeHtml(m.text);
            try {
                const allTerms = (m.highlightTerms || []).filter(Boolean);
                if (allTerms.length > 0) {
                    const sortedTerms = [...allTerms].sort((a, b) => b.length - a.length);
                    const regex = new RegExp(`(${sortedTerms.map(escapeRegex).join('|')})`, 'gi');
                    highlighted = highlighted.replace(regex, '<mark class="bg-amber-200 text-amber-950 font-black px-1 py-0.5 rounded shadow-2xs">$1</mark>');
                }
            } catch(e) {}

            // Monta badges de motivo
            const badgesHtml = (m.reasons || []).map(r => {
                if (r.type === 'code') {
                    return `<span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-purple-700 text-white shadow-2xs" style="background-color: #6b21a8; color: #ffffff !important;">${escapeHtml(r.label)}</span>`;
                } else if (r.type === 'words') {
                    return `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-950 border border-amber-300 shadow-2xs">${escapeHtml(r.label)}</span>`;
                } else if (r.type === 'product') {
                    return `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-900 border border-indigo-200 shadow-2xs">${escapeHtml(r.label)}</span>`;
                } else {
                    return `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 border border-gray-300 shadow-2xs">${escapeHtml(r.label)}</span>`;
                }
            }).join(' ');

            html += `
                <div class="bg-white border ${m.hasDirectCode ? 'border-purple-300 ring-1 ring-purple-200 bg-purple-50/15' : 'border-gray-200'} hover:border-purple-400 rounded-xl p-2.5 transition shadow-2xs space-y-1.5">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-[11px] font-mono font-bold bg-purple-100 text-purple-950 border border-purple-200 px-1.5 py-0.5 rounded shadow-2xs">
                                <i class="fas fa-clock text-purple-700 mr-1 text-[9px]"></i>${startFmt}
                            </span>
                            ${badgesHtml}
                            <span class="text-[10px] text-gray-500 font-medium">(${relativeTimeStr})</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <button type="button" 
                                    onclick="seekPlayerFromModal(${m.start})" 
                                    class="bg-indigo-50 hover:bg-indigo-100 text-indigo-900 border border-indigo-200 px-2.5 py-1 rounded-lg text-[11px] font-bold flex items-center gap-1 transition cursor-pointer active:scale-95 shadow-2xs" 
                                    title="Ouvir no player de vídeo">
                                <i class="fas fa-play text-[9px] text-indigo-700"></i>
                                <span>Ouvir</span>
                            </button>
                            <button type="button" 
                                    onclick="applySnippetCutTime(${currentSearchItemId}, ${m.start}, ${m.end}, 45)" 
                                    class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold px-3 py-1 rounded-lg text-[11px] flex items-center gap-1 transition cursor-pointer shadow-2xs active:scale-95" 
                                    style="background-color: #0d9488; color: #ffffff !important;" 
                                    title="Definir minutagem deste item a partir deste segundo">
                                <i class="fas fa-check text-[9px]"></i>
                                <span>Usar no Corte</span>
                            </button>
                        </div>
                    </div>

                    <!-- Frase Principal com Termo Destacado -->
                    <div class="text-xs font-semibold text-gray-900 leading-snug pl-2 border-l-2 ${m.hasDirectCode ? 'border-purple-500 bg-purple-50/40' : 'border-gray-300 bg-gray-50/50'} py-1.5 px-2 rounded-r-lg">
                        ${highlighted}
                    </div>

                    ${(m.prevText || m.nextText) ? `
                        <div class="text-[10px] text-gray-500 truncate italic pl-2 border-l-2 border-transparent">
                            <span class="font-bold text-gray-400 not-italic">Contexto:</span> 
                            ${m.prevText ? `"${escapeHtml(m.prevText)}" ` : ''}
                            ${m.nextText ? `&rarr; "${escapeHtml(m.nextText)}"` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        });

        resultsList.innerHTML = html;
    }

    function seekPlayerFromModal(sec) {
        if (!player) {
            alert("Vídeo da live ainda não carregado.");
            return;
        }
        player.currentTime = Math.max(0, parseFloat(sec) - 3);
        player.play().catch(() => {});
    }

    function setModalTimeField(itemId, field, sec) {
        if (!itemId) return;
        let adjustedSec = parseFloat(sec);
        if (field === 'start') {
            adjustedSec = Math.max(0, adjustedSec - 2);
        } else {
            adjustedSec = adjustedSec + 2;
        }

        const formatted = formatTime(adjustedSec);
        const input = document.getElementById(`input-${field}-${itemId}`);
        if (input) {
            input.value = formatted;
            saveItemCutTime(itemId);
            showTranscriptionAppliedAlert(`Campo "${field === 'start' ? 'Início' : 'Fim'}" atualizado para ${formatted} e salvo!`);
        }
    }

    async function applySnippetCutTime(itemId, startSec, endSec, duration = 45) {
        if (!itemId) return;
        const start = Math.max(0, Math.floor(parseFloat(startSec) - 5));
        const end = Math.floor(parseFloat(startSec) + duration);
        const startFormatted = formatTime(start);
        const endFormatted = formatTime(end);

        currentAppliedCutStart = start;
        currentAppliedCutEnd = end;

        const startInput = document.getElementById(`input-start-${itemId}`);
        const endInput = document.getElementById(`input-end-${itemId}`);
        if (startInput) startInput.value = startFormatted;
        if (endInput) endInput.value = endFormatted;

        // Seleciona card ativo
        selectActiveItem(itemId, startFormatted);

        // Salva timestamp no servidor
        await saveItemCutTime(itemId);

        // Posiciona o player no início
        if (player) {
            player.currentTime = start;
            player.pause();
        }

        showTranscriptionAppliedAlert(`Minutagem do item #${currentSearchItemCode} salva com sucesso! (${startFormatted} ➔ ${endFormatted})`);
    }

    function showTranscriptionAppliedAlert(msg) {
        const alertBox = document.getElementById("trans-modal-applied-alert");
        const alertText = document.getElementById("trans-modal-applied-text");
        if (alertBox && alertText) {
            alertText.innerHTML = `<i class="fas fa-check-circle text-green-600 text-sm"></i> ${msg}`;
            alertBox.classList.remove("hidden");
        }
    }

    function previewCurrentAppliedCut() {
        if (currentSearchItemId) {
            previewItemClip(currentSearchItemId);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function escapeRegex(string) {
        if (!string) return '';
        return String(string).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    async function handleVideoUpload(e) {
        e.preventDefault();
        const fileInput = document.getElementById("video-file-input");
        const pathInput = document.getElementById("video-path-manual-input");
        const btn = document.getElementById("btn-submit-upload");

        const formData = new FormData();
        if (fileInput.files.length > 0) {
            formData.append('video_file', fileInput.files[0]);
        }
        if (pathInput.value.trim()) {
            formData.append('video_path_manual', pathInput.value.trim());
        }

        const isLink = /^https?:\/\//i.test(pathInput.value.trim());
        btn.disabled = true;
        btn.innerHTML = isLink 
            ? `<i class="fas fa-spinner fa-spin mr-1"></i> Baixando do link...`
            : `<i class="fas fa-spinner fa-spin mr-1"></i> Salvando...`;

        const progressBox = document.getElementById("upload-progress-box");
        const progressBar = document.getElementById("upload-progress-bar");
        const progressPct = document.getElementById("upload-progress-pct");
        const progressText = document.getElementById("upload-progress-text");
        if (progressBox) progressBox.classList.remove("hidden");
        if (progressText) {
            progressText.textContent = isLink 
                ? "Baixando e processando vídeo do Instagram/Link... (aguarde alguns instantes)"
                : "Enviando arquivo de vídeo...";
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', `/admin/lives/${liveId}/cortes/upload-video`, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(event) {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                if (progressBar) progressBar.style.width = percent + '%';
                if (progressPct) progressPct.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            try {
                const data = JSON.parse(xhr.responseText);
                if (data.is_async && data.status_url) {
                    // Download em segundo plano iniciado - monitorar progresso
                    pollVideoDownloadProgress(data.status_url);
                    return;
                }
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert("Erro: " + (data.message || 'Falha ao salvar vídeo'));
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                alert("Resposta inválida do servidor.");
            }
        };

        xhr.onerror = function() {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
            alert("Erro de conexão ao enviar vídeo.");
        };

        xhr.send(formData);
    }

    function pollVideoDownloadProgress(statusUrl) {
        const progressBox = document.getElementById("upload-progress-box");
        const progressBar = document.getElementById("upload-progress-bar");
        const progressPct = document.getElementById("upload-progress-pct");
        const progressText = document.getElementById("upload-progress-text");
        const btn = document.getElementById("btn-submit-upload");

        if (progressBox) progressBox.classList.remove("hidden");

        const timer = setInterval(async () => {
            try {
                const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();

                if (data.status === 'downloading') {
                    const pct = Math.min(99, Math.round(data.progress || 0));
                    if (progressBar) progressBar.style.width = Math.max(5, pct) + '%';
                    if (progressPct) progressPct.textContent = pct + '%';
                    if (progressText) progressText.textContent = data.message || "Baixando vídeo...";

                    // Atualiza Stepper 1 no topo
                    const b1 = document.getElementById("stepper-badge-1");
                    const s1 = document.getElementById("stepper-status-1");
                    if (b1) b1.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    if (s1) {
                        s1.innerHTML = `⚡ Baixando ${pct}%`;
                        s1.style.color = '#b45309';
                    }
                } else if (data.status === 'completed') {
                    clearInterval(timer);
                    if (progressBar) progressBar.style.width = '100%';
                    if (progressPct) progressPct.textContent = '100%';
                    if (progressText) progressText.textContent = 'Download concluído! Atualizando player...';
                    setTimeout(() => {
                        alert("✅ " + (data.message || "Vídeo baixado e vinculado com sucesso! Agora você pode avançar para o Passo 2: Transcrever Áudio."));
                        window.location.reload();
                    }, 1000);
                } else if (data.status === 'error') {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.innerHTML = `<i class="fas fa-check mr-1"></i> Salvar Vídeo`;
                    alert("Erro ao baixar vídeo: " + (data.message || 'Falha desconhecida.'));
                }
            } catch (e) {
                console.warn("Erro ao consultar progresso:", e);
            }
        }, 2500);
    }

    async function promptPasteTranscription() {
        const raw = prompt("Cole o JSON com o array de frases da transcrição:\nEx: [{\"start\": 10.5, \"end\": 15.0, \"text\": \"Olha essa peça...\"}]");
        if (!raw) return;

        try {
            const parsed = JSON.parse(raw);
            const res = await fetch(`/admin/lives/${liveId}/cortes/save-transcription`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ sentences: parsed })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert("Erro: " + data.message);
            }
        } catch(e) {
            alert("JSON inválido: " + e.message);
        }
    }

    function toggleAutoProcessModal(show = null) {
        const modal = document.getElementById("modal-auto-process-instagram");
        if (!modal) return;
        if (show === null) {
            modal.classList.toggle("hidden");
        } else if (show) {
            modal.classList.remove("hidden");
        } else {
            modal.classList.add("hidden");
        }
        if (!modal.classList.contains("hidden")) {
            setTimeout(() => {
                const input = document.getElementById("auto-process-url-input");
                if (input) input.focus();
            }, 100);
        }
    }

    function sanitizeInstagramUrl(raw) {
        if (!raw) return '';
        let cleaned = String(raw).trim();
        const match = cleaned.match(/https?:\/\/(?:www\.)?instagram\.com\/(reel|p|tv)\/([a-zA-Z0-9_-]+)/i);
        if (match) {
            return `https://www.instagram.com/${match[1]}/${match[2]}/`;
        }
        return cleaned;
    }

    async function pasteClipboardToUrlInput() {
        try {
            const text = await navigator.clipboard.readText();
            if (text) {
                const input = document.getElementById("auto-process-url-input");
                if (input) {
                    input.value = sanitizeInstagramUrl(text);
                    input.focus();
                }
            }
        } catch (e) {
            console.warn("Clipboard access not available or denied:", e);
        }
    }

    function triggerAutoProcess() {
        toggleAutoProcessModal(true);
    }

    async function submitAutoProcess(event) {
        if (event) event.preventDefault();

        const inputUrl = document.getElementById("auto-process-url-input");
        let videoUrl = inputUrl ? sanitizeInstagramUrl(inputUrl.value) : '';
        if (inputUrl && videoUrl) inputUrl.value = videoUrl;

        const btnSubmit = document.getElementById("btn-submit-auto-process");
        const btnTop = document.getElementById("btn-auto-process");
        const oldSubmitHtml = btnSubmit ? btnSubmit.innerHTML : '';
        const oldTopHtml = btnTop ? btnTop.innerHTML : '';

        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin text-white mr-1"></i> Iniciando...`;
        }
        if (btnTop) {
            btnTop.disabled = true;
            btnTop.innerHTML = `<i class="fas fa-spinner fa-spin text-white mr-1"></i> Iniciando...`;
        }

        // Exibir banner de progresso imediatamente
        const progressBox = document.getElementById("ai-process-progress-box");
        const progressText = document.getElementById("ai-process-text");
        const progressPct = document.getElementById("ai-process-pct");
        const progressBar = document.getElementById("ai-process-bar");
        if (progressBox) {
            progressBox.classList.remove("hidden");
            if (progressText) progressText.textContent = videoUrl ? "Baixando vídeo da publicação do Instagram..." : "Verificando gravação e áudio...";
            if (progressPct) progressPct.textContent = "5%";
            if (progressBar) progressBar.style.width = "5%";
        }

        // Fecha o modal
        toggleAutoProcessModal(false);

        try {
            const res = await fetch(`/admin/lives/${liveId}/cortes/auto-process`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    username: 'de_minha_mania',
                    url: videoUrl || undefined
                })
            });
            const data = await res.json();
            
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = oldSubmitHtml;
            }
            if (btnTop) {
                btnTop.disabled = false;
                btnTop.innerHTML = oldTopHtml;
            }

            if (data.is_async) {
                startTranscriptionPolling();
            } else if (data.success) {
                if (progressText) progressText.textContent = "✅ " + data.message;
                if (progressPct) progressPct.textContent = "100%";
                if (progressBar) progressBar.style.width = "100%";
                setTimeout(() => {
                    alert("✅ " + data.message);
                    window.location.reload();
                }, 1000);
            } else {
                if (progressBox) progressBox.classList.add("hidden");
                alert("⚠️ " + (data.message || 'Falha no processamento automático.'));
            }
        } catch(e) {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = oldSubmitHtml;
            }
            if (btnTop) {
                btnTop.disabled = false;
                btnTop.innerHTML = oldTopHtml;
            }
            if (progressBox) progressBox.classList.add("hidden");
            alert("Erro de comunicação ao disparar o processamento: " + e.message);
        }
    }

    // ===== INTEGRAÇÃO YOUTUBE SHORTS =====
    async function uploadCutToYouTube(itemId) {
        const btn = document.getElementById(`btn-yt-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-red-600"></i>';
        }

        try {
            const res = await fetch(`{{ url('admin/youtube/upload-cut') }}/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ privacy: 'public' })
            });
            const data = await res.json();

            if (data.success) {
                if (btn) {
                    btn.outerHTML = `
                        <a href="${data.url}" target="_blank" title="Assistir Short no YouTube" class="bg-red-600 hover:bg-red-700 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" style="background-color: #dc2626; color: #ffffff !important;">
                            <i class="fab fa-youtube text-white"></i>
                        </a>
                    `;
                }
                alert("✅ " + data.message + "\nLink: " + data.url);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = oldHtml;
                }
                alert("⚠️ " + (data.message || 'Falha no upload para o YouTube.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao enviar para o YouTube: " + e.message);
        }
    }

    async function batchUploadToYouTube() {
        const isConnected = {{ !empty($youtubeAccount) ? 'true' : 'false' }};
        if (!isConnected) {
            if (confirm("Você precisa conectar o Canal do YouTube primeiro. Deseja conectar agora?")) {
                window.location.href = "{{ route('admin.youtube.connect') }}";
            }
            return;
        }

        if (!confirm("Deseja publicar automaticamente todas as peças NÃO VENDIDAS no YouTube Shorts?")) {
            return;
        }

        const btn = document.getElementById('btn-youtube-batch');
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white"></i> Publicando...';
        }

        try {
            const res = await fetch(`{{ url('admin/lives/' . $live->id . '/youtube/batch-upload') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success) {
                alert("🚀 " + data.message);
            } else {
                alert("⚠️ " + (data.message || 'Falha ao iniciar publicação em lote.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro de comunicação: " + e.message);
        }
    }

    async function uploadCutToTikTok(itemId) {
        const isConnected = {{ !empty($tiktokAccount) ? 'true' : 'false' }};
        if (!isConnected) {
            if (confirm("Você precisa conectar a conta do TikTok primeiro. Deseja conectar agora?")) {
                window.location.href = "{{ route('admin.tiktok.connect') }}";
            }
            return;
        }

        const btn = document.getElementById(`btn-tiktok-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-slate-900"></i>';
        }

        try {
            const res = await fetch(`{{ url('admin/tiktok/upload-cut') }}/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ privacy: 'PUBLIC_TO_EVERYONE' })
            });
            const data = await res.json();

            if (data.success) {
                if (btn) {
                    btn.outerHTML = `
                        <a href="https://www.tiktok.com" target="_blank" title="Vídeo Publicado no TikTok" class="bg-slate-900 hover:bg-black text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" style="background-color: #0f172a; color: #ffffff !important;">
                            <i class="fab fa-tiktok text-white"></i>
                        </a>
                    `;
                }
                alert("✅ " + data.message);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = oldHtml;
                }
                alert("⚠️ " + (data.message || 'Falha no upload para o TikTok.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao enviar para o TikTok: " + e.message);
        }
    }

    async function batchUploadToTikTok() {
        const isConnected = {{ !empty($tiktokAccount) ? 'true' : 'false' }};
        if (!isConnected) {
            if (confirm("Você precisa conectar a conta do TikTok primeiro. Deseja conectar agora?")) {
                window.location.href = "{{ route('admin.tiktok.connect') }}";
            }
            return;
        }

        if (!confirm("Deseja publicar automaticamente todas as peças NÃO VENDIDAS no TikTok?")) {
            return;
        }

        const btn = document.getElementById('btn-tiktok-batch');
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white"></i> Publicando...';
        }

        try {
            const res = await fetch(`{{ url('admin/lives/' . $live->id . '/tiktok/batch-upload') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success) {
                alert("🚀 " + data.message);
            } else {
                alert("⚠️ " + (data.message || 'Falha ao iniciar publicação em lote no TikTok.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro de comunicação: " + e.message);
        }
    }

    async function uploadCutToInstagram(itemId) {
        const isConnected = {{ !empty($instagramAccount) ? 'true' : 'false' }};
        if (!isConnected) {
            if (confirm("Você precisa conectar a conta do Instagram primeiro. Deseja conectar agora?")) {
                window.location.href = "{{ route('admin.instagram.connect') }}";
            }
            return;
        }

        const btn = document.getElementById(`btn-instagram-${itemId}`);
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-pink-600"></i>';
        }

        try {
            const res = await fetch(`{{ url('admin/instagram/upload-cut') }}/${itemId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (data.success) {
                if (btn) {
                    btn.outerHTML = `
                        <a href="${data.url || 'https://www.instagram.com'}" target="_blank" title="Reel Publicado no Instagram" class="hover:opacity-95 text-white font-bold p-2 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center gap-1" style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #ffffff !important;">
                            <i class="fab fa-instagram text-white"></i>
                        </a>
                    `;
                }
                alert("🚀 " + data.message);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = oldHtml;
                }
                alert("⚠️ " + (data.message || 'Falha no upload para o Instagram.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro ao enviar para o Instagram: " + e.message);
        }
    }

    async function batchUploadToInstagram() {
        const isConnected = {{ !empty($instagramAccount) ? 'true' : 'false' }};
        if (!isConnected) {
            if (confirm("Você precisa conectar a conta do Instagram primeiro. Deseja conectar agora?")) {
                window.location.href = "{{ route('admin.instagram.connect') }}";
            }
            return;
        }

        if (!confirm("Deseja publicar automaticamente todas as peças NÃO VENDIDAS no Instagram Reels?")) {
            return;
        }

        const btn = document.getElementById('btn-instagram-batch');
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-white"></i> Publicando...';
        }

        try {
            const res = await fetch(`{{ url('admin/lives/' . $live->id . '/instagram/batch-upload') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();

            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }

            if (data.success) {
                alert("🚀 " + data.message);
                window.location.reload();
            } else {
                alert("⚠️ " + (data.message || 'Falha ao iniciar publicação em lote no Instagram.'));
            }
        } catch(e) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = oldHtml;
            }
            alert("Erro de comunicação: " + e.message);
        }
    }

    // ========================================================
    // AUTO-RECUPERAÇÃO DE ESTADO AO RECARREGAR A PÁGINA
    // ========================================================
    document.addEventListener("DOMContentLoaded", async () => {
        // 1. Verifica se há download de vídeo em andamento no Passo 1
        try {
            const dlRes = await fetch(`/admin/lives/${liveId}/cortes/video-download-status`, { headers: { 'Accept': 'application/json' } });
            if (dlRes.ok) {
                const dlData = await dlRes.json();
                if (dlData.status === 'downloading') {
                    const b1 = document.getElementById("stepper-badge-1");
                    const s1 = document.getElementById("stepper-status-1");
                    if (b1) b1.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    if (s1) {
                        s1.innerHTML = `⚡ Baixando ${Math.round(dlData.progress || 0)}%`;
                        s1.style.color = '#b45309';
                    }
                    pollVideoDownloadProgress(`/admin/lives/${liveId}/cortes/video-download-status`);
                }
            }
        } catch (e) {
            console.warn("Verificação de download falhou:", e);
        }

        // 2. Verifica se há transcrição (Passo 2) ou minutagem (Passo 3) rodando em background
        try {
            const transRes = await fetch(`/admin/lives/${liveId}/cortes/transcribe-status`, { headers: { 'Accept': 'application/json' } });
            if (transRes.ok) {
                const transData = await transRes.json();
                if (transData.status === 'processing') {
                    startTranscriptionPolling();
                }
            }
        } catch (e) {
            console.warn("Verificação de IA falhou:", e);
        }
    });
</script>
@endpush
@endsection
