<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiBatchImageEditService;
use App\Models\ItemMedia;
use Illuminate\Support\Facades\Storage;

class CheckGeminiBatchJobs extends Command
{
    // O nome do comando que vai rodar no terminal
    protected $signature = 'gemini:check-batches';
    
    // Descrição do comando
    protected $description = 'Verifica o status dos jobs de edição de imagem no Gemini e atualiza o banco';

    public function handle(GeminiBatchImageEditService $geminiService)
    {
        $this->info('Iniciando verificação de Jobs do Gemini...');

        // 1. Busca todas as imagens que estão com status 'processando' dentro do JSON metadata
        $imagensPendentes = ItemMedia::where('metadata->status_gemini', 'processando')->get();

        if ($imagensPendentes->isEmpty()) {
            $this->info('Nenhum job pendente no momento.');
            return;
        }

        // 2. Agrupa as imagens pelo nome do Job (já que mandamos várias fotos no mesmo Job)
        $jobsAgrupados = $imagensPendentes->groupBy(function ($item) {
            return $item->metadata['gemini_job_name'] ?? 'desconhecido';
        });

        // 3. Verifica cada Job no Google
        foreach ($jobsAgrupados as $jobName => $imagensDoJob) {
            if ($jobName === 'desconhecido') continue;

            $this->line("Verificando Job: {$jobName} (" . $imagensDoJob->count() . " imagens)");
            
            $resultado = $geminiService->checkAndProcessResults($jobName);

            if ($resultado['success']) {
                if ($resultado['state'] === 'JOB_STATE_SUCCEEDED') {
                    $this->info("Job {$jobName} CONCLUÍDO! Atualizando banco de dados...");
                    
                    $novosCaminhos = $resultado['images']; // Array com os caminhos das novas imagens salvas

                    // 4. Atualiza cada registro no banco de dados
                    // Como a API devolve na mesma ordem que enviamos, usamos o índice ($index)
                    foreach ($imagensDoJob as $index => $imagem) {
                        if (isset($novosCaminhos[$index])) {
                            
                            // Opcional: Deletar a imagem original antiga do disco para economizar espaço
                            // Se quiser manter a original, basta comentar a linha abaixo
                            if (Storage::disk('public')->exists($imagem->url)) {
                                Storage::disk('public')->delete($imagem->url);
                            }

                            // Atualiza a URL para a nova imagem editada
                            $imagem->url = $novosCaminhos[$index];
                            
                            // Marca como editada para não processar de novo
                            $imagem->is_edited = 1;

                            // Atualiza o status no metadata
                            $metadata = $imagem->metadata;
                            $metadata['status_gemini'] = 'concluido';
                            $imagem->metadata = $metadata;

                            $imagem->save();
                        }
                    }
                    
                    $this->info("Imagens do Job {$jobName} atualizadas com sucesso!");

                } elseif ($resultado['state'] === 'JOB_STATE_FAILED') {
                    $this->error("Job {$jobName} FALHOU na API do Gemini.");
                    
                    // Atualiza o status para erro no banco
                    foreach ($imagensDoJob as $imagem) {
                        $metadata = $imagem->metadata;
                        $metadata['status_gemini'] = 'erro';
                        $imagem->metadata = $metadata;
                        $imagem->save();
                    }
                } else {
                    // JOB_STATE_RUNNING ou JOB_STATE_PENDING
                    $this->line("Job {$jobName} ainda está em andamento (Status: {$resultado['state']}).");
                }
            } else {
                $this->error("Erro ao consultar a API para o Job {$jobName}: " . $resultado['error']);
            }
        }

        $this->info('Verificação finalizada.');
    }
}