<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GeminiBatchImageEditService;
use App\Models\ItemMedia; // Certifique-se de importar o seu Model correto

class ImagemBatchController extends Controller
{
    public function enviarParaEdicao(Request $request)
    {
        // Aumenta o tempo limite do próprio PHP para essa requisição não cair
        set_time_limit(300);		
        // 1. Buscar as imagens órfãs
        // Filtramos onde item_id é nulo, media_type é imagem, e que ainda não foram editadas
        $imagensOrfas = ItemMedia::whereNull('item_id')
            ->where('media_type', 'image')
            ->where(function ($query) {
                $query->where('is_edited', 0)
                      ->orWhereNull('is_edited');
            })
			->limit(10) 
            ->get();

        // Se não tiver nenhuma imagem órfã, já retornamos
        if ($imagensOrfas->isEmpty()) {
            return response()->json(['message' => 'Nenhuma imagem órfã pendente de edição encontrada.']);
        }

        // 2. Extrair os caminhos das imagens
        // Estou assumindo que a coluna 'url' guarda o caminho do arquivo (ex: 'uploads/foto.jpg')
        $caminhosParaEditar = $imagensOrfas->pluck('url')->toArray();

        // 3. Instanciar o serviço e iniciar o Job no Gemini
        $geminiService = new GeminiBatchImageEditService();
        $resultado = $geminiService->startBatchJob($caminhosParaEditar);

        // 4. Verificar se o envio deu certo
        if ($resultado['success']) {
            
            $jobName = $resultado['job_name']; // Ex: "batches/123456789"
            
            // 5. Salvar o Job Name no banco de dados
            // Como você tem um campo JSON 'metadata', podemos guardar o job_name nele!
            foreach ($imagensOrfas as $imagem) {
                $metadata = $imagem->metadata ?? [];
                $metadata['gemini_job_name'] = $jobName;
                $metadata['status_gemini'] = 'processando';
                
                $imagem->metadata = $metadata;
                $imagem->save();
            }

            return response()->json([
                'mensagem' => count($caminhosParaEditar) . ' imagens órfãs enviadas para edição com sucesso!',
                'job_id' => $jobName
            ]);

        } else {
            // Se deu erro na comunicação com a API do Google
            return response()->json([
                'erro' => 'Falha ao enviar o lote para o Gemini',
                'detalhes' => $resultado['error']
            ], 500);
        }
    }
}