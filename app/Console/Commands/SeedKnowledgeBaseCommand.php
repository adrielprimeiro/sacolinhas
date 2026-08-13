<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\Ai\GeminiService;

class SeedKnowledgeBaseCommand extends Command
{
    protected $signature = 'rag:seed';
    protected $description = 'Popula a base de conhecimento inicial com regras de negócio e gera os embeddings no Gemini';

    public function handle(GeminiService $gemini)
    {
        $this->info('Iniciando inclusão de regras de negócio e geração de embeddings...');

        $initialRules = [
            [
                'title' => 'Regras de Funcionamento da Sacolinha',
                'category' => 'sacolinha',
                'content' => "A sacolinha é um serviço que permite guardar itens comprados durante nossas lives e loja por até 30 dias sem cobrar frete imediato. Durante o prazo da sacolinha, você pode ir acumulando novos itens. Ao finalizar o prazo ou quando desejar receber as peças, você faz o fechamento da sacolinha escolhendo a opção de entrega ou retirada."
            ],
            [
                'title' => 'Prazos de Vencimento e Fechamento de Sacolinha',
                'category' => 'sacolinha',
                'content' => "O prazo padrão de permanência dos produtos na sacolinha é de 30 dias corridos a contar da primeira adição de item. Sacolinhas vencidas devem ser encerradas realizando o pagamento do frete e finalização do pedido. O sistema envia notificações automáticas no WhatsApp informando sobre o vencimento próximo."
            ],
            [
                'title' => 'Formas de Pagamento Aceitas',
                'category' => 'pagamento',
                'content' => "Aceitamos pagamentos via PIX (com baixa e confirmação automática através do Banco Inter ou Mercado Pago), Cartão de Crédito em até 6x, e saldo/créditos em Conta Corrente da loja. Pagamentos por PIX possuem aprovação imediata no portal do cliente."
            ],
            [
                'title' => 'Opções e Regras de Frete e Envio',
                'category' => 'envio',
                'content' => "Oferecemos envios via Correios (SEDEX e PAC) e transportadoras parceiras calculadas via Melhor Envio. Também disponibilizamos retirada presencial em nossa loja física mediante agendamento prévio. O frete é calculado com base no peso total e dimensões dos itens acumulados na sacolinha."
            ],
            [
                'title' => 'Trocas e Devoluções',
                'category' => 'devolucao',
                'content' => "Solicitações de troca ou devolução por arrependimento podem ser feitas em até 7 dias corridos após o recebimento dos produtos, conforme o Código de Defesa do Consumidor. A peça deve estar em perfeito estado, com etiqueta fixada e sem sinais de uso."
            ],
            [
                'title' => 'Clube de Pontos e Desafios',
                'category' => 'pontos',
                'content' => "Compras e participações em lives geram pontos no Clube Mania. Os pontos acumulados podem ser trocados por cupons de desconto, mimos e frete grátis diretamente na área de Desafios do Portal do Cliente."
            ],
            [
                'title' => 'Como Comprar durante as Lives (TikTok / Instagram)',
                'category' => 'lives',
                'content' => "Durante a transmissão ao vivo, cada produto recebe um número/código específico. Para reservar o item na sua sacolinha, basta digitar o código do produto no chat da live ou enviar mensagem. Nosso robô captura seu comentário e vincula a peça diretamente ao seu cadastro."
            ]
        ];

        foreach ($initialRules as $data) {
            $this->line("Processando: {$data['title']}...");

            $kb = KnowledgeBase::updateOrCreate(
                ['title' => $data['title']],
                [
                    'category' => $data['category'],
                    'content' => $data['content'],
                    'is_active' => true,
                ]
            );

            $this->line("Geração de embedding para '{$data['title']}'...");
            $embedding = $gemini->generateEmbedding($data['title'] . "\n" . $data['content']);

            if ($embedding) {
                $kb->embedding = $embedding;
                $kb->save();
                $this->info("✅ Embeddings salvos para: {$data['title']}");
            } else {
                $this->warn("⚠️ Não foi possível gerar embedding via Gemini API para: {$data['title']}. (Uso de busca por palavras-chave estará ativo como fallback).");
            }
        }

        $this->info("Processamento concluído com sucesso!");
        return 0;
    }
}
