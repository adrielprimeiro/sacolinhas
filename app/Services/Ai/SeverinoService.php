<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class SeverinoService
{
    protected string $apiKey;
    protected string $baseUrl = "https://generativelanguage.googleapis.com/v1beta";

    public function __construct()
    {
        $this->apiKey = config("services.gemini.paid_api_key") ?: (config("services.gemini.api_key") ?: env("GEMINI_API_KEY", ""));
    }

    public function askSeverino(string $userPrompt, array $history = [], ?string $sessionId = null): string
    {
        $dataAtual = date('Y-m-d H:i:s');
        
        // Se o usuário digitou apenas 'continue' ou similar, resgata a pergunta anterior do histórico
        if (in_array(strtolower(trim($userPrompt)), ['continue', 'continuar', 'prossiga', 'retomar'])) {
            $lastUserPrompt = null;
            for ($k = count($history) - 1; $k >= 0; $k--) {
                $role = $history[$k]['role'] ?? '';
                $txt = trim($history[$k]['text'] ?? $history[$k]['message'] ?? '');
                if ($role === 'user' && !in_array(strtolower($txt), ['continue', 'continuar', 'prossiga', 'retomar'])) {
                    $lastUserPrompt = $txt;
                    break;
                }
            }
            if ($lastUserPrompt) {
                $userPrompt = "Por favor, continue a pesquisa e responda de forma direta e completa à minha pergunta anterior: '{$lastUserPrompt}'";
            }
        }
        
        $regrasStr = "";
        try {
            $regras = \App\Models\KnowledgeBase::where('is_active', 1)->get();
            if ($regras->isNotEmpty()) {
                $regrasStr = "\nREGRAS DE NEGÓCIO DA EMPRESA:\n";
                foreach ($regras as $r) {
                    $regrasStr .= "- {$r->title}: {$r->content}\n";
                }
            }
        } catch (\Exception $e) {}
        
        $summaryStr = "";
        $memoriaTrabalhoStr = "";
        if ($sessionId) {
            $summary = \Illuminate\Support\Facades\Cache::get('severino_summary_' . $sessionId);
            if ($summary) {
                $summaryStr = "\nRESUMO DA CONVERSA ATÉ AGORA: " . $summary . "\n";
            }
            
            $memoria = \Illuminate\Support\Facades\Cache::get('severino_scratchpad_' . $sessionId);
            if ($memoria) {
                $memoriaTrabalhoStr = "\n\n[MEMÓRIA DE TRABALHO DA TENTATIVA ANTERIOR]:\nVocê foi interrompido antes de terminar. Aqui estão os dados que você já puxou do banco na tentativa passada para que você NÃO precise rodar essas ferramentas de novo. Continue a partir daqui:\n" . $memoria . "\n";
            }
        }
        $estatisticasBasicas = \Illuminate\Support\Facades\Cache::remember('severino_estatisticas_basicas', 3600, function () {
            try {
                $faturamento = \Illuminate\Support\Facades\DB::table('pedidos')
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->where('status_pagamento', 'aprovado')
                    ->sum('valor_total');
                
                $totalPedidos = \Illuminate\Support\Facades\DB::table('pedidos')
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->where('status_pagamento', 'aprovado')
                    ->count();
                    
                $totalClientes = \Illuminate\Support\Facades\DB::table('users')->count();
                $totalSacolinhas = \Illuminate\Support\Facades\DB::table('sacolinhas as s')
                    ->where('s.status', '!=', 'pedido')
                    ->where(function ($query) {
                        $query->whereNull('s.obs')
                              ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                    })
                    ->distinct('s.user_id')
                    ->count('s.user_id');
                
                // Sacolinhas vencidas (add_at + 31 dias < agora)
                $sacolinhasVencidas = \Illuminate\Support\Facades\DB::table('sacolinhas as s')
                    ->where('s.status', '!=', 'pedido')
                    ->where(function ($query) {
                        $query->whereNull('s.obs')
                              ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                    })
                    ->whereNotNull('s.add_at')
                    ->whereRaw("DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()")
                    ->distinct('s.user_id')
                    ->count('s.user_id');

                $sacolinhasEmDia = max(0, $totalSacolinhas - $sacolinhasVencidas);

                $itensVencidos = \Illuminate\Support\Facades\DB::table('sacolinhas as s')
                    ->where('s.status', '!=', 'pedido')
                    ->where(function ($query) {
                        $query->whereNull('s.obs')
                              ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                    })
                    ->whereNotNull('s.add_at')
                    ->whereRaw("DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()")
                    ->sum('s.quantity');

                $itensDisponiveis = \Illuminate\Support\Facades\DB::table('items')->where('status', 'disponivel')->count();
                
                $contaCarteira = \App\Models\ContaBancaria::where('nome', 'like', '%Carteira%')->first();
                $saldoCarteira = $contaCarteira ? (float) $contaCarteira->saldo_atual : 0.0;
                
                return "\n[ESTATÍSTICAS BÁSICAS DO SISTEMA (MEMÓRIA IMEDIATA)]\n- Faturamento Aprovado Deste Mês: R$ " . number_format($faturamento, 2, ',', '.') . "\n" .
                       "- Total de Pedidos Aprovados Deste Mês: " . $totalPedidos . "\n" .
                       "- Total de Clientes Cadastrados: " . $totalClientes . "\n" .
                       "- Total de Sacolinhas em Aberto: " . $totalSacolinhas . "\n" .
                       "- Sacolinhas Vencidas (com itens > 31 dias): " . $sacolinhasVencidas . "\n" .
                       "- Sacolinhas Sem Itens Vencidos (Em Dia): " . $sacolinhasEmDia . "\n" .
                       "- Total de Peças/Itens Vencidos nas Sacolinhas: " . (int)$itensVencidos . "\n" .
                       "- Peças Disponíveis em Estoque: " . $itensDisponiveis . "\n" .
                       "- Saldo Consolidado da Carteira Cliente (Painel): R$ " . number_format($saldoCarteira, 2, ',', '.') . "\n";
            } catch (\Exception $e) {
                return "";
            }
        });

        $systemInstruction = "Seu nome é Severino, um assistente de IA focado na administração do sistema Mania.\n" .
            "Hoje é: {$dataAtual}{$regrasStr}{$summaryStr}{$memoriaTrabalhoStr}{$estatisticasBasicas}\n" .
            "Você ajuda os administradores consultando informações internas através de suas ferramentas.\n" .
            "DEFINIÇÕES E REGRAS OBRIGATÓRIAS DE SACOLINHA:\n" .
            "- Identificação do Cliente: Ao citar qualquer sacolinha individual ou listar clientes, USE SEMPRE O NOME DO CLIENTE (exemplo: 'A sacolinha da Aline'). NUNCA responda apenas com o ID numérico (`user_id`). Sempre faça JOIN ou busque o `name` na tabela `users`!\n" .
            "- Escopo Padrão: Se o usuário não especificar um período ou status, 'sacolinha' refere-se SEMPRE E EXCLUSIVAMENTE às SACOLINHAS ATIVAS/ABERTAS (sacolinhas que contêm pelo menos 1 item e que ainda NÃO viraram pedido: `status != 'pedido'` e sem `obs` de pedido).\n" .
            "- Sacolinha Aberta / Ativa: Cliente com itens guardados na sacolinha (`sacolinhas`), sem número de pedido gerado (`status != 'pedido'`). Cada cliente único (`user_id`) é UMA sacolinha.\n" .
            "- Sacolinha Vencida: Sacolinha aberta que contém pelo menos um item inserido há mais de 31 dias (`DATE_ADD(add_at, INTERVAL 31 DAY) < NOW()`).\n" .
            "- Sacolinha Sem Itens Vencidos (Em Dia): (Total de Sacolinhas Abertas) - (Sacolinhas Vencidas). NUNCA invente números diferentes!\n" .
            "- Sacolinha Fechada: Pedido finalizado na tabela `pedidos`.\n" .
            "REGRA OBRIGATÓRIA DA CARTEIRA DE CLIENTES (CONTA_CORRENTE):\n" .
            "- A tabela `conta_corrente` é um EXTRATO HISTÓRICO DE AUDITORIA (várias linhas por cliente). A coluna `saldo_atual` em cada linha é apenas uma fotografia do saldo naquela data passada.\n" .
            "- NUNCA faça SUM(saldo_atual) ou COUNT(*) direto em conta_corrente para calcular clientes negativos ou saldos, pois isso somará centenas de linhas antigas do mesmo cliente!\n" .
            "- Para perguntas sobre a Carteira de Clientes (saldo consolidado da carteira, total de clientes com saldo negativo ou positivo, valor total das dívidas ou créditos em carteira), USE SEMPRE a ferramenta dedicada `resumo_carteira_clientes`.\n" .
            "REGRA DE ORÇAMENTO (PREVISTO X REALIZADO):\n" .
            "- O sistema possui o módulo de Orçamento Financeiro (Previsto x Realizado).\n" .
            "- A tabela `orcamentos` guarda o valor previsto (`valor_previsto`) por categoria para cada mês. O valor REALIZADO é apurado a partir dos lançamentos pagos no mês correspondente.\n" .
            "- Para qualquer pergunta sobre itens fora do previsto, orçamento estourado, previsto x realizado ou metas financeiras, USE SEMPRE a ferramenta dedicada `relatorio_orcamento_previsto_realizado`!\n" .
            "REGRA DO ATALHO DO PRÓ-LABORE:\n" .
            "- Quando o usuário perguntar 'Como tá o prolabore?' (ou 'como está o pro-labore', 'ritmo do prolabore', etc.), USE IMEDIATAMENTE a ferramenta `resumo_orcamento_proporcional` com a categoria 'Pro labore'.\n" .
            "- Responda com um resumo direto, dinâmico e básico contendo: o dia e mês atual, pró-labore orçado no mês, proporcional esperado até hoje, valor já gasto realizado, diferença em relação ao ritmo esperado (se está abaixo/economizando ou acima) e a conclusão objetiva.\n" .
            "REGRA DE MEMORIZAÇÃO E ATALHOS:\n" .
            "- Quando o usuário pedir para você 'gravar', 'memorizar', 'salvar na memória' ou criar um atalho ('Grava aí Severino: Quando eu perguntar X responda Y', etc.), você DEVE OBRIGATORIAMENTE chamar a ferramenta `memorizar_regra_ou_preferencia`.\n" .
            "- NUNCA responda que gravou ou registrou apenas em texto se você não chamou a ferramenta `memorizar_regra_ou_preferencia`, pois somente essa ferramenta grava no banco de dados definitivo (`KnowledgeBase`) para ficar ativo para sempre em todas as conversas futuras!\n" .
            "REGRA DE INTROSPECÇÃO DE CONTROLLERS (REGRAS E FÓRMULAS DO SISTEMA):\n" .
            "- Toda a inteligência de negócios do sistema (fórmulas do DRE, Fluxo de Caixa, Orçamento, Avaliações de desapego, Conciliação, etc.) está codificada nos Controllers da pasta `app/Http/Controllers/`.\n" .
            "- Você possui a ferramenta `consultar_codigo_controller`. Sempre que você precisar saber como qualquer tela do sistema calcula um indicador, relatório ou métrica, consulte o código do Controller correspondente para aprender as fórmulas e filtros exatos antes de consultar o banco!\n" .
            "REGRA DE OURO PARA BANCO DE DADOS: Se você estiver começando agora (sem Memória de Trabalho), use as ferramentas dedicadas (como 'resumo_sacolinhas' ou 'resumo_pedidos_mes') ou 'consultar_memoria_sql'. SE JÁ HOUVER MEMÓRIA DE TRABALHO, avance direto para o próximo passo lógico. USE SEMPRE SINTAXE MYSQL.\n" .
            "REGRA FINANCEIRA: O 'Saldo na Carteira' de um cliente é apenas a diferença entre o que ele pagou e recebeu. O valor real que o cliente tem disponível e pode utilizar para comprar ou colocar peças é o 'Limite Disponível'.\n" .
            "ANTI-ALUCINAÇÃO: É ESTIRAMENTE PROIBIDO inventar, chutar ou deduzir valores monetários, saldos, preços, totais ou dados de clientes da própria cabeça. Você é um robô de banco de dados! Sempre chame as ferramentas SQL ou de busca para checar a verdade. Se não achar, diga que não achou.\n" .
            "AUTO-APRENDIZADO: Sempre que você usar o mapa para deduzir uma query SQL inédita e ela funcionar com sucesso, chame 'salvar_memoria_sql' automaticamente ANTES de dar a resposta final ao usuário para guardar esse conhecimento. O 'assunto' deve ser a intenção original do usuário.\n" .
            "Nunca execute nenhuma alteração (INSERT/UPDATE/DELETE), apenas consulte e informe. Responda em Markdown claro e objetivo.";

        $tools = [
            [
                "functionDeclarations" => [
                    [
                        "name" => "buscar_cliente",
                        "description" => "Busca o cadastro completo de um cliente pelo nome, cidade, bairro, endereço, email, apelido, instagram ou telefone. Retorna nome, cidade, endereço e ID para identificar e diferenciar clientes com mesmo nome.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "termo" => ["type" => "STRING", "description" => "Nome, cidade, email ou qualquer parte do cadastro do cliente"]
                            ],
                            "required" => ["termo"]
                        ]
                    ],
                    [
                        "name" => "resumo_financeiro_cliente",
                        "description" => "Traz o saldo na carteira e limites da sacolinha de um cliente específico pelo seu ID numérico.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "user_id" => ["type" => "INTEGER", "description" => "ID numérico do cliente (deve ser o ID, não o nome)"]
                            ],
                            "required" => ["user_id"]
                        ]
                    ],
                    [
                        "name" => "contagem_estoque",
                        "description" => "Retorna a quantidade de peças em um status específico.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "status" => ["type" => "STRING", "description" => "Status: disponivel, loja, sacolinha, vendido, etc"]
                            ]
                        ]
                    ],
                    [
                        "name" => "resumo_live",
                        "description" => "Retorna o resultado final de uma live (total de itens vendidos/separados, faturamento, total de clientes), buscando pela data (Y-m-d) ou pegando a mais recente.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "data" => ["type" => "STRING", "description" => "Opcional. Data no formato YYYY-MM-DD. Se vazio, pega a última live."]
                            ]
                        ]
                    ],
                    [
                        "name" => "status_clube_mensalidades",
                        "description" => "Retorna a lista de assinantes do Clube Mania que já pagaram e os que ainda não pagaram a mensalidade do mês atual.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "itens_sacolinha",
                        "description" => "Lista os itens atualmente na sacolinha de um cliente (pelo ID numérico), incluindo a data que foram adicionados e quantos dias estão parados.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "user_id" => ["type" => "INTEGER", "description" => "ID numérico do cliente"]
                            ],
                            "required" => ["user_id"]
                        ]
                    ],
                    [
                        "name" => "resumo_pedidos_mes",
                        "description" => "Retorna a quantidade de pedidos fechados no mês atual e o valor médio, total, etc.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "resumo_sacolinhas",
                        "description" => "Retorna o resumo completo de sacolinhas do sistema: total de clientes com sacolinhas abertas, quantas estão vencidas (> 31 dias), total de peças nas sacolinhas e total de peças vencidas.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "listar_sacolinhas_em_dia",
                        "description" => "Retorna a lista completa com nome e dados de todos os clientes cujas sacolinhas estão em dia (sem nenhuma peça com mais de 31 dias).",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "resumo_carteira_clientes",
                        "description" => "Retorna os dados consolidados da Carteira de Clientes: o saldo líquido total da carteira (como no painel), quantidade de clientes com saldo negativo (devedores) e a soma total das dívidas, quantidade com saldo positivo (crédito) e soma dos créditos, e clientes zerados.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "relatorio_orcamento_previsto_realizado",
                        "description" => "Retorna o relatório comparativo de Orçamento Financeiro (Previsto x Realizado) por categoria de receita e despesa para um determinado mês (ex: 2026-09 ou o mês atual se omitido). Identifica com precisão quais itens estouraram o orçamento (despesas fora do previsto), despesas não orçadas realizadas, e receitas abaixo ou acima da meta.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "periodo" => [
                                    "type" => "STRING",
                                    "description" => "Opcional. Mês no formato YYYY-MM (ex: 2026-09). Se vazio, usa o mês atual."
                                ]
                            ]
                        ]
                    ],
                    [
                        "name" => "resumo_orcamento_proporcional",
                        "description" => "Calcula instantaneamente o gasto proporcional de uma categoria de despesa (especialmente 'Pro labore' / Pró-labore) em relação ao dia atual do mês. Retorna o valor orçado para o mês, o dia atual, percentual decorrido do mês, valor que deveria ter sido gasto proporcionalmente até hoje, valor real pago até o momento, diferença (se está economizando ou estourando o ritmo) e saldo restante.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "categoria" => [
                                    "type" => "STRING",
                                    "description" => "Nome ou termo da categoria de despesa (ex: 'Pro labore', padrão é 'Pro labore')"
                                ],
                                "periodo" => [
                                    "type" => "STRING",
                                    "description" => "Opcional. Mês no formato YYYY-MM (ex: 2026-09). Padrão é o mês atual."
                                ]
                            ]
                        ]
                    ],
                    [
                        "name" => "memorizar_regra_ou_preferencia",
                        "description" => "Grava permanentemente uma nova regra de negócio, preferência do usuário ou atalho na base de conhecimento (KnowledgeBase) do sistema. O conhecimento salvo aqui fica gravado no banco de dados e ativo para sempre no prompt do sistema em todas as conversas futuras. Chame SEMPRE que o usuário disser 'grava aí', 'memorize', 'quando eu perguntar X responda Y', etc.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "titulo" => [
                                    "type" => "STRING",
                                    "description" => "Título descritivo da regra, preferência ou atalho (ex: 'Atalho: Como tá o prolabore?')"
                                ],
                                "conteudo" => [
                                    "type" => "STRING",
                                    "description" => "O texto ou instrução detalhada que o usuário pediu para memorizar"
                                ],
                                "categoria" => [
                                    "type" => "STRING",
                                    "description" => "Opcional. Categoria (ex: 'atalhos', 'preferencias', 'regras_negocio')"
                                ]
                            ],
                            "required" => ["titulo", "conteudo"]
                        ]
                    ],
                    [
                        "name" => "consultar_regras_memorizadas",
                        "description" => "Consulta todas as regras, atalhos e preferências ativas gravadas na base de conhecimento (KnowledgeBase) permanente do sistema.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "consultar_codigo_controller",
                        "description" => "Lê e analisa o código-fonte PHP real de qualquer Controller ou Service do sistema (ex: 'DreController', 'FluxoCaixaController', 'AvaliacaoController', 'OrcamentoController', 'ConciliacaoController', 'SacolinhaVencidaController', etc.). USE SEMPRE que o usuário perguntar sobre indicadores, relatórios, cálculos ou regras de qualquer tela do sistema, para ver as fórmulas, filtros (WHERE) e regras exatas que o sistema utiliza antes de consultar o banco.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "controller_ou_termo" => [
                                    "type" => "STRING",
                                    "description" => "Nome do controller ou assunto (ex: 'DreController', 'fluxo_caixa', 'avaliacao', 'conciliacao', 'orcamento', 'vencimento', 'carteira', 'pedidos')"
                                ]
                            ],
                            "required" => ["controller_ou_termo"]
                        ]
                    ],
                    [
                        "name" => "consultar_memoria_sql",
                        "description" => "Busca na sua memória de longo prazo se você já aprendeu alguma query SQL para um assunto específico. Sempre chame isso antes de tentar adivinhar tabelas.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "salvar_memoria_sql",
                        "description" => "Salva uma query SQL validada na sua memória para uso futuro.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "assunto" => ["type" => "STRING", "description" => "O que essa query resolve? Ex: ticket medio do mes, total de assinantes, etc"],
                                "query_sql" => ["type" => "STRING", "description" => "A query SQL exata e funcional"]
                            ],
                            "required" => ["assunto", "query_sql"]
                        ]
                    ],
                    [
                        "name" => "mapear_modulo_sistema",
                        "description" => "Quando precisar fazer consultas SQL no banco, chame esta ferramenta primeiro informando o módulo (financeiro, clube, lives, estoque, clientes). Ela retorna as regras de negócio, tabelas principais, colunas e relacionamentos daquele setor para você não errar a query.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "modulo" => ["type" => "STRING", "description" => "Nome do módulo: financeiro, clube, lives, estoque, clientes"]
                            ],
                            "required" => ["modulo"]
                        ]
                    ],
                    [
                        "name" => "executar_query_select",
                        "description" => "Executa uma query SQL SELECT no banco de dados da empresa. IMPORTANTE: Antes de tentar inventar tabelas, use a ferramenta mapear_modulo_sistema para aprender a arquitetura do setor.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "query" => ["type" => "STRING", "description" => "A query SQL (ex: SELECT * FROM lives ORDER BY data DESC LIMIT 1)"]
                            ],
                            "required" => ["query"]
                        ]
                    ]
                ]
            ]
        ];

        $groqKey = env('GROQ_API_KEY');
        if (empty($groqKey)) {
            return "Chave da API da Groq não configurada.";
        }

        // Converte as ferramentas do formato Gemini para o formato OpenAI/Groq
        $groqTools = [];
        foreach ($tools[0]["functionDeclarations"] as $func) {
            $properties = $func["parameters"]["properties"] ?? [];
            $mappedProperties = [];
            
            foreach ($properties as $key => $prop) {
                $mappedProperties[$key] = [
                    "type" => strtolower($prop["type"]),
                    "description" => $prop["description"] ?? ""
                ];
            }
            
            $groqTools[] = [
                "type" => "function",
                "function" => [
                    "name" => $func["name"],
                    "description" => $func["description"],
                    "parameters" => [
                        "type" => "object",
                        "properties" => empty($mappedProperties) ? (object)[] : $mappedProperties,
                        "required" => $func["parameters"]["required"] ?? []
                    ]
                ]
            ];
        }

        $messages = [];
        $messages[] = [
            "role" => "system",
            "content" => $systemInstruction
        ];

        $recent = array_slice($history, max(0, count($history) - 3)); 
        foreach ($recent as $msg) {
            $rawText = $msg["text"] ?? $msg["message"] ?? "";
            
            // FILTRO DE ALUCINAÇÃO: Não envia mensagens de erro sistêmico do próprio Severino para a IA,
            // senão a IA acha que é um padrão e começa a repetir o erro como se fosse a resposta dela!
            if ($msg["role"] === "assistant" || $msg["role"] === "model") {
                if (str_contains($rawText, "Todos os provedores configurados falharam") || 
                    str_contains($rawText, "Operei ferramentas demais. Parando loop.") ||
                    str_contains($rawText, "Erro de conexão com o servidor.") ||
                    str_contains($rawText, "demorou mais que o esperado") ||
                    str_contains($rawText, "não há uma consulta pendente") ||
                    str_contains($rawText, "Pausa técnica!")) {
                    continue; // Pula essa mensagem
                }
            }
            
            $truncatedText = mb_strlen($rawText) > 500 ? mb_substr($rawText, 0, 500) . "..." : $rawText;
            $messages[] = [
                "role" => $msg["role"] === "assistant" || $msg["role"] === "model" ? "assistant" : "user",
                "content" => $truncatedText
            ];
        }
        $messages[] = [
            "role" => "user",
            "content" => $userPrompt
        ];
        $payload = [
            "messages" => $messages,
            "tools" => $groqTools,
            "tool_choice" => "auto",
            "temperature" => 0.2,
            "max_tokens" => 2000
        ];

        $providersToTry = [
            [
                "url" => "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions",
                "key" => env("GEMINI_API_KEY", ""),
                "model" => "gemini-2.5-flash",
                "name" => "Google Gemini 2.5 Flash"
            ],
            [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "openai/gpt-oss-20b",
                "name" => "Groq GPT OSS 20B"
            ],
            [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "qwen/qwen3.8-27b",
                "name" => "Groq Qwen 3.8"
            ],
            [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "openai/gpt-oss-120b",
                "name" => "Groq GPT OSS 120B"
            ],
            [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => env("OPENROUTER_API_KEY", ""),
                "model" => "poolside/laguna-s-2.1:free",
                "name" => "OR Laguna"
            ],
            [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => env("OPENROUTER_API_KEY", ""),
                "model" => "nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free",
                "name" => "OR Nemotron Omni"
            ],
            [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => env("OPENROUTER_API_KEY", ""),
                "model" => "nex-agi/nex-n2.5-mini:free",
                "name" => "OR Nex Mini"
            ],
            [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => env("OPENROUTER_API_KEY", ""),
                "model" => "nex-agi/nex-n2.5-pro:free",
                "name" => "OR Nex Pro"
            ]
        ];

        // Carrega pontuação do cache (inicia em 10)
        foreach ($providersToTry as &$p) {
            $p['score'] = \Illuminate\Support\Facades\Cache::get("ai_score_" . md5($p['name']), 10);
        }
        unset($p);
        
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) { // Loop das ferramentas aumentado para 10 porque agora é super rápido com o cache
            
            // Controle anti-timeout do Nginx (60s). Se já passaram 40 segundos, forçamos a pausa amigável!
            if (microtime(true) - $startTime > 40) {
                Log::warning("Tempo de execução limite atingido (40s). Forçando pausa técnica para evitar Nginx 504.");
                break;
            }

            $choice = null;
            
            for ($attempt = 0; $attempt < 3; $attempt++) {
                
                // Ordena os provedores pelo score (do maior para o menor)
                usort($providersToTry, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                foreach ($providersToTry as &$provider) {
                    
                    // Checa anti-timeout dentro do loop de provedores também!
                    if (microtime(true) - $startTime > 40) {
                        \Illuminate\Support\Facades\Log::warning("Tempo limite 40s atingido dentro do loop de provedores. Forçando pausa amigável.");
                        if ($sessionId && \Illuminate\Support\Facades\Cache::has('severino_scratchpad_' . $sessionId)) {
                            return "Pausa técnica! 😅 Fiz várias consultas pesadas no banco de dados e atingi o limite de segurança do servidor para não deixá-lo lento. Já salvei tudo o que descobri até agora na minha 'Prancheta'. Por favor, apenas digite **'continue'** para eu retomar a pesquisa exatamente de onde parei e te dar a resposta final!";
                        }
                        return "Operei ferramentas demais. Parando loop.";
                    }

                    $payload["model"] = $provider["model"];
                    $cacheKey = "ai_score_" . md5($provider['name']);

                    try {
                        $headers = [
                            "Authorization" => "Bearer " . $provider["key"],
                            "Content-Type" => "application/json",
                            "HTTP-Referer" => "https://minhamania.net",
                            "X-Title" => "Controle Sacolinhas"
                        ];

                        $response = Http::withHeaders($headers)
                            ->timeout(12)
                            ->post($provider["url"], $payload);

                        if ($response->successful()) {
                            // SUCESSO: Aumenta a pontuação em 1 (máximo 10)
                            $provider['score'] = min($provider['score'] + 1, 10);
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                            
                            $data = $response->json();
                            $choice = $data["choices"][0] ?? null;
                            if ($choice) {
                                break 2; // Sucesso, sai do loop provedores e attempts
                            }
                        }

                        if ($response->status() == 429 || $response->status() == 413) {
                            $body = $response->body();
                            // Se for rate limit momentâneo (ex: Gemini pedindo 500ms), esperamos brevemente
                            if (preg_match('/retry in (\d+(?:\.\d+)?)\s*(s|ms)/i', $body, $matches)) {
                                $waitVal = (float) $matches[1];
                                $unit = strtolower($matches[2]);
                                $waitSec = ($unit === 'ms') ? ($waitVal / 1000.0) : $waitVal;
                                if ($waitSec <= 1.5) {
                                    usleep((int)($waitSec * 1000000) + 100000); // espera o tempo exato + 100ms
                                    // Tenta mais uma vez o mesmo provedor
                                    $retryResp = Http::withHeaders($headers)->timeout(12)->post($provider["url"], $payload);
                                    if ($retryResp->successful()) {
                                        $data = $retryResp->json();
                                        $choice = $data["choices"][0] ?? null;
                                        if ($choice) {
                                            break 2;
                                        }
                                    }
                                }
                            }

                            // RATE LIMIT: Punição moderada, perde 5 pontos (mínimo -30)
                            $provider['score'] = max($provider['score'] - 5, -30);
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                            
                            Log::warning("Rate Limit/Too Large no provedor {$provider['name']} ({$response->status()}). Novo score: {$provider['score']} | Erro: {$response->body()}");
                            continue; // Tenta o PRÓXIMO provedor imediatamente
                        }
                        
                        // OUTRO ERRO
                        $provider['score'] = max($provider['score'] - 5, -30);
                        \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                        Log::error("Provedor {$provider['name']} falhou com status {$response->status()}. Novo score: {$provider['score']} | Erro: {$response->body()}");
                    } catch (\Exception $e) {
                        // TIMEOUT OU FALHA DE REDE (Pior cenário, gasta o tempo do usuário!)
                        $provider['score'] = max($provider['score'] - 10, -30);
                        \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                        Log::error("Erro no provedor {$provider['name']}: " . $e->getMessage());
                    }
                }
                unset($provider);
                
                // Se rodou todos os provedores e deram rate limit/erro, esperamos 4s para a janela de tokens (Groq TPM) reabrir
                sleep(4);
            }

            if (!$choice) {
                \Illuminate\Support\Facades\Log::error("SEVERINO DEBUG: Todos os provedores falharam. Score Array: " . json_encode($providersToTry) . " | Payload: " . json_encode($payload));
                return "Todos os provedores configurados falharam ou atingimos o limite de tentativas (Rate Limit).";
            }

            $message = $choice["message"] ?? [];

            // Limpa campos não padrão (como 'reasoning' do Nemotron) para não quebrar outros modelos na próxima iteração
            $sanitizedMessage = [
                "role" => $message["role"] ?? "assistant",
                "content" => $message["content"] ?? ""
            ];
            if (!empty($message["tool_calls"])) {
                $sanitizedMessage["tool_calls"] = $message["tool_calls"];
            }

            // Adiciona a resposta da IA no histórico para o próximo round
            $payload["messages"][] = $sanitizedMessage;

            if (!empty($message["tool_calls"])) {
                foreach ($message["tool_calls"] as $toolCall) {
                    $name = $toolCall["function"]["name"];
                    $args = json_decode($toolCall["function"]["arguments"], true) ?? [];
                    Log::info("Severino chamando ferramenta Groq: {$name}", $args);
                    
                    $resultado = $this->executeTool($name, $args);
                    $resumoDoResultado = $this->prepareToolContent($name, $resultado, $userPrompt);
                    
                    // Salva na memória de rascunho caso o loop seja interrompido (timeout/limite)
                    if ($sessionId) {
                        $scratchpad = \Illuminate\Support\Facades\Cache::get('severino_scratchpad_' . $sessionId, "");
                        $scratchpad .= "\n- Ferramenta '{$name}' chamada com argumentos: " . json_encode($args, JSON_UNESCAPED_UNICODE) . "\nResultado: {$resumoDoResultado}\n";
                        \Illuminate\Support\Facades\Cache::put('severino_scratchpad_' . $sessionId, $scratchpad, now()->addMinutes(60));
                    }

                    $payload["messages"][] = [
                        "role" => "tool",
                        "tool_call_id" => $toolCall["id"],
                        "name" => $name,
                        "content" => $resumoDoResultado
                    ];
                }
                // Como houve chamada de ferramenta, o loop $i continua para enviar o resultado
                continue;
            }

            // Se não chamou ferramenta, é a resposta final.
            $finalText = trim((string) ($message["content"] ?? ""));
            
            // Se o modelo só gerou "reasoning" (pensamento em voz alta) e esqueceu do content, forçamos um turno rápido
            if ($finalText === "" && !empty($message["reasoning"])) {
                if ($i < 8) {
                    $payload["messages"][] = [
                        "role" => "user",
                        "content" => "Agora formule e entregue a resposta final completa e formatada em Markdown com base no que você concluiu."
                    ];
                    continue;
                }
            }

            if ($sessionId) {
                \Illuminate\Support\Facades\Cache::forget('severino_scratchpad_' . $sessionId);
            }
            \Illuminate\Support\Facades\Log::info("Severino Final Response Message:", $message);
            return $finalText !== "" ? $finalText : "Resposta processada mas sem texto legível.";
        }

        if ($sessionId && \Illuminate\Support\Facades\Cache::has('severino_scratchpad_' . $sessionId)) {
            return "Pausa técnica! 😅 Fiz várias consultas pesadas no banco de dados e atingi o limite de segurança do servidor para não deixá-lo lento. Já salvei tudo o que descobri até agora na minha 'Prancheta'. Por favor, apenas digite **'continue'** para eu retomar a pesquisa exatamente de onde parei e te dar a resposta final!";
        }

        return "Operei ferramentas demais. Parando loop.";
    }

    protected function executeTool(string $name, array $args): array
    {
        try {
            switch ($name) {
                case "buscar_cliente":
                    $termo = trim($args["termo"] ?? "");
                    if (empty($termo)) {
                        return ["clientes_encontrados" => []];
                    }
                    $words = array_filter(preg_split('/\s+/', $termo));
                    $query = User::query();
                    foreach ($words as $word) {
                        $query->where(function($q) use ($word) {
                            $q->where("name", "like", "%{$word}%")
                                ->orWhere("cidade", "like", "%{$word}%")
                                ->orWhere("bairro", "like", "%{$word}%")
                                ->orWhere("endereco", "like", "%{$word}%")
                                ->orWhere("email", "like", "%{$word}%")
                                ->orWhere("apelido", "like", "%{$word}%")
                                ->orWhere("instagram", "like", "%{$word}%")
                                ->orWhere("tiktok", "like", "%{$word}%")
                                ->orWhere("nome_cliente", "like", "%{$word}%")
                                ->orWhere("phone", "like", "%{$word}%")
                                ->orWhere("whatsapp", "like", "%{$word}%")
                                ->orWhere("telefone_principal", "like", "%{$word}%");
                        });
                    }
                    $users = $query->select(
                            "id", "name", "email", "cidade", "estado", 
                            "bairro", "endereco", "numero_endereco", 
                            "phone", "whatsapp", "apelido", "instagram"
                        )
                        ->limit(15)
                        ->get();
                    return ["clientes_encontrados" => $users->toArray()];

                case "resumo_financeiro_cliente":
                    $userId = $args["user_id"] ?? 0;
                    
                    $ultima = \App\Models\ContaCorrente::where("user_id", $userId)
                        ->orderByDesc("data_movimentacao")
                        ->orderByDesc("id")
                        ->first();
                    $saldo = $ultima ? (float) $ultima->saldo_atual : 0.0;
                    
                    $limitesRow = DB::table("cliente_limites")->where("user_id", $userId)->first();
                    $valorLimite = $limitesRow ? (float) $limitesRow->limite_credito : 0.0;
                    $utilizado = $limitesRow ? (float) $limitesRow->limite_utilizado : 0.0;
                    $disponivel = $valorLimite + $saldo - $utilizado;

                    return [
                        "saldo_na_carteira" => $saldo,
                        "limite_concedido_empresa" => $valorLimite,
                        "limite_utilizado_na_sacolinha_atualmente" => $utilizado,
                        "limite_disponivel" => $disponivel,
                        "aviso_para_a_ia" => "Atenção IA: Leia e informe exatamente os números acima. O limite utilizado é o valor real (em R$) que o cliente já gastou na sacolinha. Se o limite disponível estiver negativo, significa que a pessoa gastou MAIS do que o limite concedido."
                    ];

                case "resumo_carteira_clientes":
                    $subQueryMaxDate = DB::table('conta_corrente')
                        ->select('user_id', DB::raw('MAX(data_movimentacao) as max_date'))
                        ->groupBy('user_id');

                    $subQueryMaxId = DB::table('conta_corrente as cc')
                        ->joinSub($subQueryMaxDate, 'tm', function($join) {
                            $join->on('cc.user_id', '=', 'tm.user_id')
                                 ->on('cc.data_movimentacao', '=', 'tm.max_date');
                        })
                        ->select('cc.user_id', DB::raw('MAX(cc.id) as max_id'))
                        ->groupBy('cc.user_id');

                    $ultimosSaldos = DB::table('conta_corrente as cc')
                        ->joinSub($subQueryMaxId, 'mi', function($join) {
                            $join->on('cc.id', '=', 'mi.max_id');
                        })
                        ->select('cc.user_id', 'cc.saldo_atual')
                        ->get();

                    $negativos = $ultimosSaldos->where('saldo_atual', '<', 0);
                    $positivos = $ultimosSaldos->where('saldo_atual', '>', 0);
                    $zerados = $ultimosSaldos->where('saldo_atual', '==', 0);

                    return [
                        "saldo_consolidado_carteira_painel" => round($ultimosSaldos->sum('saldo_atual'), 2),
                        "total_clientes_com_carteira" => $ultimosSaldos->count(),
                        "clientes_com_saldo_negativo_devedores" => [
                            "quantidade" => $negativos->count(),
                            "soma_total_dividas" => round($negativos->sum('saldo_atual'), 2)
                        ],
                        "clientes_com_saldo_positivo_credito" => [
                            "quantidade" => $positivos->count(),
                            "soma_total_creditos" => round($positivos->sum('saldo_atual'), 2)
                        ],
                        "clientes_zerados" => $zerados->count(),
                        "explicacao_importante" => "Estes são os saldos REAIS e ATUAIS dos clientes (pegando a última movimentação de cada um). O saldo consolidado bate exatamente com o valor exibido na conta bancária 'Carteira Cliente' no painel."
                    ];

                case "relatorio_orcamento_previsto_realizado":
                    $periodoInput = $args["periodo"] ?? null;
                    $periodo = $periodoInput 
                        ? \Carbon\Carbon::parse($periodoInput)->startOfMonth()
                        : \Carbon\Carbon::now()->startOfMonth();
                    
                    $inicioPeriodo = $periodo->copy()->startOfMonth()->toDateString();
                    $fimPeriodo = $periodo->copy()->endOfMonth()->toDateString();
                    $periodoDate = $periodo->copy()->startOfMonth()->toDateString();

                    $classificacoes = \App\Models\ClassificacaoFinanceira::select(
                            'classificacao_financeira.*',
                            DB::raw("(
                                SELECT COALESCE(SUM(
                                    CASE 
                                        WHEN l.tipo = classificacao_financeira.tipo_natureza COLLATE utf8mb4_unicode_ci THEN l.valor_total 
                                        ELSE -l.valor_total 
                                    END
                                ), 0)
                                FROM lancamentos l
                                WHERE l.classificacao_financeira_id = classificacao_financeira.id
                                  AND l.status = 'pago'
                                  AND l.data_vencimento BETWEEN '{$inicioPeriodo}' AND '{$fimPeriodo}'
                                  AND (classificacao_financeira.id NOT IN (15, 17) OR l.referencia_tipo = 'pedido')
                            ) AS realizado")
                        )
                        ->whereNotIn('classificacao_financeira.nome', ['Recarga de Carteira', 'Aporte de Carteira'])
                        ->with(['orcamentos' => function ($q) use ($periodoDate) {
                            $q->where('periodo', $periodoDate);
                        }])
                        ->orderBy('tipo_natureza')
                        ->orderBy('codigo_contabil')
                        ->get();

                    $despesasEstouradas = [];
                    $despesasNaoOrcadas = [];
                    $despesasEmDia = [];
                    $receitasAbaixoMeta = [];
                    $receitasAcimaMeta = [];
                    $totalPrevistoDespesa = 0;
                    $totalRealizadoDespesa = 0;
                    $totalPrevistoReceita = 0;
                    $totalRealizadoReceita = 0;

                    foreach ($classificacoes as $c) {
                        $orc = $c->orcamentos->first();
                        $previsto = $orc ? (float) $orc->valor_previsto : 0.0;
                        $realizado = (float) $c->realizado;
                        $diferenca = $previsto - $realizado;
                        $percentual = $previsto > 0 ? round(($realizado / $previsto) * 100, 1) : 0;

                        if ($c->tipo_natureza === 'despesa') {
                            $totalPrevistoDespesa += $previsto;
                            $totalRealizadoDespesa += $realizado;

                            if ($previsto > 0 && $realizado > $previsto) {
                                $despesasEstouradas[] = [
                                    "codigo" => $c->codigo_contabil,
                                    "categoria" => $c->nome,
                                    "valor_previsto" => $previsto,
                                    "valor_realizado" => $realizado,
                                    "estouro" => round($realizado - $previsto, 2),
                                    "percentual_gasto" => $percentual . "%"
                                ];
                            } elseif ($previsto == 0 && $realizado > 0) {
                                $despesasNaoOrcadas[] = [
                                    "codigo" => $c->codigo_contabil,
                                    "categoria" => $c->nome,
                                    "valor_realizado" => $realizado
                                ];
                            } elseif ($previsto > 0) {
                                $despesasEmDia[] = [
                                    "codigo" => $c->codigo_contabil,
                                    "categoria" => $c->nome,
                                    "valor_previsto" => $previsto,
                                    "valor_realizado" => $realizado,
                                    "saldo_restante" => round($diferenca, 2),
                                    "percentual_gasto" => $percentual . "%"
                                ];
                            }
                        } else {
                            $totalPrevistoReceita += $previsto;
                            $totalRealizadoReceita += $realizado;

                            if ($previsto > 0 && $realizado < $previsto) {
                                $receitasAbaixoMeta[] = [
                                    "codigo" => $c->codigo_contabil,
                                    "categoria" => $c->nome,
                                    "valor_previsto" => $previsto,
                                    "valor_realizado" => $realizado,
                                    "faltante" => round($previsto - $realizado, 2),
                                    "atingido" => $percentual . "%"
                                ];
                            } elseif ($realizado > 0) {
                                $receitasAcimaMeta[] = [
                                    "codigo" => $c->codigo_contabil,
                                    "categoria" => $c->nome,
                                    "valor_previsto" => $previsto,
                                    "valor_realizado" => $realizado,
                                    "atingido" => $percentual . "%"
                                ];
                            }
                        }
                    }

                    return [
                        "periodo_analisado" => $periodo->format('m/Y'),
                        "resumo_geral" => [
                            "total_despesas_previstas" => round($totalPrevistoDespesa, 2),
                            "total_despesas_realizadas" => round($totalRealizadoDespesa, 2),
                            "total_receitas_previstas" => round($totalPrevistoReceita, 2),
                            "total_receitas_realizadas" => round($totalRealizadoReceita, 2)
                        ],
                        "despesas_estouradas_fora_do_previsto" => $despesasEstouradas,
                        "despesas_nao_orcadas_realizadas" => $despesasNaoOrcadas,
                        "receitas_abaixo_da_meta" => $receitasAbaixoMeta,
                        "despesas_em_dia" => $despesasEmDia
                    ];

                case "resumo_orcamento_proporcional":
                    $categoriaNome = trim($args["categoria"] ?? "Pro labore");
                    $periodoInput = $args["periodo"] ?? null;
                    $agora = \Carbon\Carbon::now();
                    $dataRef = $periodoInput 
                        ? \Carbon\Carbon::parse($periodoInput)->startOfMonth()
                        : $agora->copy();
                    
                    $diasNoMes = $dataRef->daysInMonth;
                    $isMesCorrente = ($dataRef->format('Y-m') === $agora->format('Y-m'));
                    $diaAtual = $isMesCorrente ? $agora->day : $diasNoMes;
                    $percentualMesDecorrido = round(($diaAtual / $diasNoMes) * 100, 1);
                    
                    $queryClass = \App\Models\ClassificacaoFinanceira::query();
                    if (stripos($categoriaNome, 'labore') !== false) {
                        $classificacao = $queryClass->where(function($q) {
                            $q->where('nome', 'like', '%Pro labore%')
                              ->orWhere('id', 40);
                        })->first();
                    } else {
                        $classificacao = $queryClass->where('nome', 'like', "%{$categoriaNome}%")->first();
                    }

                    if (!$classificacao) {
                        return ["erro" => "Categoria financeira '{$categoriaNome}' não foi encontrada."];
                    }

                    $periodoDate = $dataRef->copy()->startOfMonth()->toDateString();
                    $orcamento = \App\Models\Orcamento::where('classificacao_financeira_id', $classificacao->id)
                        ->whereDate('periodo', $periodoDate)
                        ->first();
                    
                    $valorPrevistoMes = $orcamento ? (float) $orcamento->valor_previsto : 0.0;
                    $proporcionalEsperado = round(($valorPrevistoMes * $diaAtual) / $diasNoMes, 2);
                    
                    $inicioMes = $dataRef->copy()->startOfMonth()->toDateString();
                    $fimMes = $dataRef->copy()->endOfMonth()->toDateString();
                    
                    $valorGastoRealizado = (float) \App\Models\Lancamento::where('classificacao_financeira_id', $classificacao->id)
                        ->where('status', 'pago')
                        ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
                        ->sum('valor_total');
                        
                    $diferencaProporcional = round($proporcionalEsperado - $valorGastoRealizado, 2);
                    $saldoRestanteOrcamento = round($valorPrevistoMes - $valorGastoRealizado, 2);
                    $percentualGastoDoTotal = $valorPrevistoMes > 0 ? round(($valorGastoRealizado / $valorPrevistoMes) * 100, 1) : 0;
                    
                    $dentroDoRitmo = $valorGastoRealizado <= $proporcionalEsperado;
                    
                    return [
                        "categoria" => $classificacao->nome . " ({$classificacao->codigo_contabil})",
                        "mes_referencia" => $dataRef->format('m/Y'),
                        "dias_totais_no_mes" => $diasNoMes,
                        "dia_analisado" => $diaAtual,
                        "percentual_mes_decorrido" => "{$percentualMesDecorrido}%",
                        "prolabore_orcado_no_mes" => $valorPrevistoMes,
                        "proporcional_esperado_ate_hoje" => $proporcionalEsperado,
                        "valor_ja_gasto_realizado" => $valorGastoRealizado,
                        "diferenca_em_relacao_ao_proporcional" => abs($diferencaProporcional),
                        "situacao_ritmo" => $dentroDoRitmo 
                            ? "R$ " . number_format(abs($diferencaProporcional), 2, ',', '.') . " abaixo do esperado (ritmo controlado)"
                            : "R$ " . number_format(abs($diferencaProporcional), 2, ',', '.') . " acima do esperado (ritmo acelerado)",
                        "percentual_gasto_do_orcamento_total" => "{$percentualGastoDoTotal}%",
                        "saldo_restante_do_orcamento_mensal" => $saldoRestanteOrcamento,
                        "conclusao" => $dentroDoRitmo
                            ? "O gasto está dentro do ritmo previsto para o período, restando R$ " . number_format($saldoRestanteOrcamento, 2, ',', '.') . " do orçamento mensal."
                            : "O gasto está acima do proporcional previsto para o dia de hoje em R$ " . number_format(abs($diferencaProporcional), 2, ',', '.') . "."
                    ];

                case "consultar_codigo_controller":
                    $termo = trim($args["controller_ou_termo"] ?? "");
                    if (empty($termo)) {
                        return ["erro" => "Informe o nome do controller ou termo a ser consultado."];
                    }

                    $basePaths = [
                        app_path('Http/Controllers'),
                        app_path('Services')
                    ];

                    $arquivosEncontrados = [];
                    foreach ($basePaths as $bp) {
                        if (!is_dir($bp)) continue;
                        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($bp));
                        foreach ($iterator as $file) {
                            if ($file->isFile() && $file->getExtension() === 'php') {
                                $fn = $file->getFilename();
                                if (stripos($fn, $termo) !== false) {
                                    $arquivosEncontrados[] = $file->getPathname();
                                }
                            }
                        }
                    }

                    if (empty($arquivosEncontrados)) {
                        return ["aviso" => "Nenhum controller ou service encontrado com o termo '{$termo}'."];
                    }

                    $arquivoAlvo = $arquivosEncontrados[0];
                    $conteudo = file_get_contents($arquivoAlvo);

                    // Truncamento inteligente se o arquivo for muito longo
                    if (mb_strlen($conteudo) > 7000) {
                        $conteudoTruncado = mb_substr($conteudo, 0, 7000) . "\n... [continuação do arquivo truncada para economizar tokens]";
                    } else {
                        $conteudoTruncado = $conteudo;
                    }

                    return [
                        "controller_identificado" => basename($arquivoAlvo),
                        "caminho" => str_replace(base_path(), '', $arquivoAlvo),
                        "codigo_fonte_regras" => $conteudoTruncado
                    ];

                case "contagem_estoque":
                    $status = $args["status"] ?? null;
                    $query = DB::table("items");
                    if ($status) {
                        $query->where("status", $status);
                    } else {
                        $query->whereIn("status", ["disponivel", "loja", "sacolinha"]);
                    }
                    $count = $query->count();
                    return ["quantidade" => $count, "status_pesquisado" => $status ?? "disponivel, loja e sacolinha"];

                case "itens_sacolinha":
                    $userId = $args["user_id"] ?? 0;
                    $sacolinhas = \App\Models\Sacolinhas::with("item")->where("user_id", $userId)->get();
                    $agora = \Carbon\Carbon::now();
                    $itens = [];
                    foreach ($sacolinhas as $s) {
                        $dias = $s->add_at ? $agora->diffInDays($s->add_at) : 0;
                        $itens[] = [
                            "item_id" => $s->item_id,
                            "nome" => $s->item ? $s->item->nome_do_produto : "Desconhecido",
                            "preco" => $s->price,
                            "adicionado_em" => $s->add_at ? $s->add_at->format("d/m/Y") : "Desconhecida",
                            "dias_na_sacolinha" => $dias
                        ];
                    }
                    return [
                        "total_itens" => count($itens),
                        "itens" => $itens
                    ];

                case "resumo_live":
                    $data = $args["data"] ?? null;
                    if ($data) {
                        $live = \App\Models\Live::whereDate("data", $data)->first();
                    } else {
                        $live = \App\Models\Live::orderBy("data", "desc")->first();
                    }

                    if (!$live) {
                        return ["erro" => "Nenhuma live encontrada na data informada."];
                    }

                    // Calcula o faturamento usando a tabela sacolinhas baseada no live_id
                    $stats = DB::table("sacolinhas")
                        ->where("live_id", $live->id)
                        ->selectRaw("COUNT(id) as total_itens, SUM(price * quantity) as faturamento, COUNT(DISTINCT user_id) as total_clientes")
                        ->first();

                    return [
                        "live_id" => $live->id,
                        "data_live" => $live->data->format("d/m/Y"),
                        "tipo" => $live->tipo_live,
                        "total_itens_separados" => (int)$stats->total_itens,
                        "faturamento_bruto" => (float)$stats->faturamento,
                        "clientes_distintos" => (int)$stats->total_clientes
                    ];

                case "status_clube_mensalidades":
                    // Busca todos os assinantes ativos
                    $assinaturas = DB::table('clube_assinaturas')
                        ->where('status', 'ativa')
                        ->get();

                    $primeiroDiaMesRef = \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d');

                    $pagos = [];
                    $pendentes = [];

                    foreach ($assinaturas as $assinatura) {
                        $user = \App\Models\User::find($assinatura->user_id);
                        $nome = $user ? $user->name : "User ID: " . $assinatura->user_id;

                        // Verifica se pagou a mensalidade do mês atual
                        $mensalidade = DB::table('clube_mensalidades')
                            ->where('user_id', $assinatura->user_id)
                            ->where('mes_referencia', $primeiroDiaMesRef)
                            ->where('status_pagamento', 'pago')
                            ->first();

                        if ($mensalidade) {
                            $pagos[] = $nome;
                        } else {
                            $pendentes[] = $nome;
                        }
                    }

                    return [
                        "total_pagos" => count($pagos),
                        "total_pendentes" => count($pendentes),
                        "pagos" => $pagos,
                        "pendentes" => $pendentes
                    ];

                case "resumo_pedidos_mes":
                    $stats = DB::table('pedidos')
                        ->whereMonth('created_at', date('m'))
                        ->whereYear('created_at', date('Y'))
                        ->where('status_pagamento', 'aprovado')
                        ->selectRaw('COUNT(*) as total_pedidos, SUM(valor_total) as faturamento, AVG(valor_total) as valor_medio')
                        ->first();

                    return [
                        "mes" => date('m/Y'),
                        "total_pedidos" => (int) ($stats->total_pedidos ?? 0),
                        "faturamento" => (float) ($stats->faturamento ?? 0),
                        "valor_medio" => (float) ($stats->valor_medio ?? 0)
                    ];

                case "resumo_sacolinhas":
                    $totalSacolinhas = DB::table('sacolinhas as s')
                        ->where('s.status', '!=', 'pedido')
                        ->where(function ($query) {
                            $query->whereNull('s.obs')
                                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                        })
                        ->distinct('s.user_id')
                        ->count('s.user_id');

                    $sacolinhasVencidas = DB::table('sacolinhas as s')
                        ->where('s.status', '!=', 'pedido')
                        ->where(function ($query) {
                            $query->whereNull('s.obs')
                                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                        })
                        ->whereNotNull('s.add_at')
                        ->whereRaw("DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()")
                        ->distinct('s.user_id')
                        ->count('s.user_id');

                    $itensVencidos = DB::table('sacolinhas as s')
                        ->where('s.status', '!=', 'pedido')
                        ->where(function ($query) {
                            $query->whereNull('s.obs')
                                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                        })
                        ->whereNotNull('s.add_at')
                        ->whereRaw("DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()")
                        ->sum('s.quantity');

                    $totalItens = DB::table('sacolinhas')->sum('quantity');

                    return [
                        "total_sacolinhas_abertas" => (int) $totalSacolinhas,
                        "sacolinhas_vencidas" => (int) $sacolinhasVencidas,
                        "sacolinhas_em_dia" => (int) ($totalSacolinhas - $sacolinhasVencidas),
                        "total_pecas_nas_sacolinhas" => (int) $totalItens,
                        "total_pecas_vencidas" => (int) $itensVencidos,
                        "regra_vencimento" => "Item adicionado ha mais de 31 dias (add_at + 31 dias < agora)"
                    ];

                case "listar_sacolinhas_em_dia":
                    // 1. IDs dos usuários que tem pelo menos um item vencido
                    $usuariosVencidos = DB::table('sacolinhas as s')
                        ->where('s.status', '!=', 'pedido')
                        ->where(function ($query) {
                            $query->whereNull('s.obs')
                                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                        })
                        ->whereNotNull('s.add_at')
                        ->whereRaw("DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()")
                        ->distinct()
                        ->pluck('s.user_id')
                        ->toArray();

                    // 2. Clientes com sacolinhas abertas que NÃO estão na lista dos vencidos
                    $emDia = DB::table('sacolinhas as s')
                        ->join('users as u', 'u.id', '=', 's.user_id')
                        ->where('s.status', '!=', 'pedido')
                        ->where(function ($query) {
                            $query->whereNull('s.obs')
                                  ->orWhereRaw("LOWER(s.obs) NOT LIKE '%ped-%'");
                        })
                        ->whereNotIn('s.user_id', $usuariosVencidos)
                        ->select(
                            'u.name as cliente',
                            DB::raw('COUNT(s.id) as total_itens'),
                            DB::raw('MIN(s.add_at) as item_mais_antigo')
                        )
                        ->groupBy('s.user_id', 'u.name')
                        ->orderBy('u.name', 'asc')
                        ->get();

                    $listaTexto = $emDia->map(function ($c, $idx) {
                        return ($idx + 1) . ". {$c->cliente} ({$c->total_itens} itens, mais antigo em " . date('d/m/Y', strtotime($c->item_mais_antigo)) . ")";
                    })->implode("\n");

                    return [
                        "total_em_dia" => $emDia->count(),
                        "lista_formatada" => $listaTexto
                    ];

                case "consultar_memoria_sql":
                    $filePath = storage_path('app/severino_memoria.json');
                    if (!file_exists($filePath)) {
                        return ["memoria" => "A memória está vazia. Nenhuma query salva ainda."];
                    }
                    $json = file_get_contents($filePath);
                    return ["memoria" => json_decode($json, true)];

                case "salvar_memoria_sql":
                    $assunto = $args["assunto"] ?? "Sem assunto";
                    $query_sql = $args["query_sql"] ?? "";
                    
                    $filePath = storage_path('app/severino_memoria.json');
                    $memoria = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
                    
                    $memoria[] = [
                        "assunto" => $assunto,
                        "query_sql" => $query_sql,
                        "data" => date('Y-m-d H:i:s')
                    ];
                    
                    file_put_contents($filePath, json_encode($memoria, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    return ["sucesso" => "Memória gravada com sucesso! Na próxima vez, você lembrará disso."];

                case "memorizar_regra_ou_preferencia":
                    $titulo = trim($args["titulo"] ?? "Regra / Atalho");
                    $conteudo = trim($args["conteudo"] ?? "");
                    $categoria = trim($args["categoria"] ?? "preferencias_usuario");
                    if (empty($conteudo)) {
                        return ["erro" => "O conteúdo da regra/atalho não pode ser vazio."];
                    }
                    $kb = \App\Models\KnowledgeBase::updateOrCreate(
                        ["title" => $titulo],
                        [
                            "category" => $categoria,
                            "content" => $conteudo,
                            "is_active" => true
                        ]
                    );
                    return [
                        "sucesso" => true,
                        "id" => $kb->id,
                        "titulo" => $kb->title,
                        "mensagem" => "Regra/preferência gravada com sucesso no banco de dados definitivo do sistema (KnowledgeBase)! Agora você lembrará disso em todas as conversas futuras."
                    ];

                case "consultar_regras_memorizadas":
                    $regras = \App\Models\KnowledgeBase::where('is_active', 1)->get(['id', 'title', 'category', 'content']);
                    return [
                        "total_regras" => $regras->count(),
                        "regras" => $regras->toArray()
                    ];

                case "mapear_modulo_sistema":
                    $modulo = strtolower($args["modulo"] ?? "");
                    switch ($modulo) {
                        case "financeiro":
                        case "extrato":
                        case "extratos":
                        case "conciliacao":
                        case "banco":
                        case "bancos":
                        case "orcamento":
                        case "orcamentos":
                        case "previsao":
                        case "previsoes":
                        case "previsto_realizado":
                        case "dre":
                        case "fluxo_caixa":
                        case "fluxocaixa":
                            return ["mapa" => "MÓDULO FINANCEIRO E BANCÁRIO:
- Controllers principais: `DreController`, `FluxoCaixaController`, `OrcamentoController`, `ConciliacaoController`, `ContaBancariaController`, `ContaCorrenteController`, `LancamentoController`, `MovimentacaoController`.
- Tabelas principais:
  1. `contas_bancarias` (id, nome, tipo, saldo_inicial). Contas da empresa: 1='Caixinha', 2='Mercado Pago', 3='Carteira Cliente', 4='Inter'. (Atenção: NÃO existe a coluna 'saldo_atual' nessa tabela).
  2. `transacoes_extrato` (id, fitid, data, descricao, valor, valor_bruto, valor_liquido, tipo ['entrada','saida'], status ['pendente','conciliado','ignorado'], origem, conta_bancaria_id, movimentacao_id). É onde ficam os extratos bancários importados/sincronizados do Inter e Mercado Pago!
  3. `lancamentos` (id, tipo ['receita','despesa'], status ['pendente','pago_parcial','pago','cancelado'], pessoa_id, classificacao_financeira_id, data_emissao, data_vencimento, valor_total, descricao).
  4. `movimentacoes` (id, lancamento_id, conta_bancaria_id, data_pagamento, valor_pago, forma_pagamento, transacao_extrato_id).
  5. `conta_corrente` (id, user_id, valor, tipo_movimentacao, saldo_atual, data_movimentacao).
     ATENÇÃO CRÍTICA: A tabela `conta_corrente` é um EXTRATO HISTÓRICO DE AUDITORIA (muitas linhas por cliente). A coluna `saldo_atual` em cada linha é apenas uma fotografia do saldo naquela data passada.
     NUNCA faça `SUM(saldo_atual)` ou `COUNT(*) WHERE saldo_atual < 0` diretamente em `conta_corrente`! Isso somará dezenas de linhas antigas do mesmo cliente.
     Para perguntas sobre a Carteira de Clientes (saldo consolidado da carteira, quantidade de clientes negativos/devedores ou positivos, soma das dívidas ou créditos), USE SEMPRE a ferramenta dedicada `resumo_carteira_clientes`!
  6. `orcamentos` (id, classificacao_financeira_id, periodo, valor_previsto).
     ATENÇÃO CRÍTICA SOBRE ORÇAMENTO (PREVISTO X REALIZADO):
     A tabela `orcamentos` guarda o valor previsto planejado por categoria em cada mês (dia 1 do mês, ex: '2026-09-01').
     O valor REALIZADO NÃO é uma coluna estática; ele é calculado dinamicamente somando `lancamentos` onde `status = 'pago'` e `data_vencimento` está no mês daquele período para a mesma categoria!
     Para qualquer pergunta sobre itens fora do previsto, orçamento estourado, previsto x realizado ou metas, USE SEMPRE a ferramenta dedicada `relatorio_orcamento_previsto_realizado`!
- Regra de Conciliação e Lançamentos Faltantes:
  * Uma transação bancária no extrato (`transacoes_extrato`) só gera um `lancamento` e `movimentacao` no sistema quando é CONCILIADA (`status = 'conciliado'`).
  * Se o usuário perguntar por que está faltando um lançamento de uma transferência, PIX ou pagamento que ocorreu no banco, consulte `transacoes_extrato`! Se estiver `status = 'pendente'`, o lançamento ainda NÃO existe no financeiro porque a transação ainda está pendente de conciliação bancária na tela de Conciliação.
  * Em transferências entre contas próprias (ex: Inter -> Mercado Pago), existem duas pontas no extrato: saída no Inter e entrada no Mercado Pago. Se uma das pontas foi conciliada individualmente e a outra ficou com status 'pendente', o lançamento da ponta pendente estará faltando no financeiro até que seja conciliado!
- Regra de Pessoas (Clientes/Fornecedores): Se precisar buscar um lançamento ou movimentação por nome (ex: fornecedor 'Meias' ou 'Leandro'), você DEVE fazer um JOIN com a tabela `pessoas` (id, nome) usando o `pessoa_id` da tabela `lancamentos`.
- Regra de Saldo: O saldo real da empresa é a soma do saldo_inicial das contas + movimentações de entrada - saídas.
- Regra de Movimentações: Tudo que entra ou sai de verdade do caixa/banco da empresa passa por `movimentacoes`."];
                        case "clube":
                            return ["mapa" => "MÓDULO CLUBE MANIA:
- Tabelas principais: `clube_assinaturas` (id, user_id, status), `clube_mensalidades` (id, user_id, mes_referencia, status_pagamento).
- Regra Ativos: Um cliente é assinante ativo se existe em `clube_assinaturas` com `status = 'ativa'`.
- Regra Pagamento: Para saber quem pagou, cruze `clube_assinaturas` com `clube_mensalidades` pelo `user_id`. A coluna `mes_referencia` guarda o mês (ex: 2026-08-01) e `status_pagamento` pode ser 'pago' ou 'pendente'."];
                        case "lives":
                        case "sacolinhas":
                        case "sacolinha":
                        case "vendas":
                            return ["mapa" => "MÓDULO LIVES E VENDAS:
- Tabelas principais: `lives` (id, data, tipo_live, plataformas, ativo, encerrada_em).
- Tabela de Itens Separados (Sacolinhas): `sacolinhas` (id, user_id, item_id, live_id, quantity, price, status, add_at, obs).
- Regras de Sacolinhas: 
  1. Identificação do Cliente: NUNCA mostre apenas o ID numérico do cliente! Sempre faça `JOIN users u ON u.id = sacolinhas.user_id` para trazer `u.name` e responder com o nome do cliente (ex: 'A sacolinha da Aline').
  2. Escopo Ativo: Se o usuário não especificar período ou data, filtre SEMPRE apenas sacolinhas abertas/ativas (`sacolinhas.status != 'pedido'` e `(sacolinhas.obs IS NULL OR LOWER(sacolinhas.obs) NOT LIKE '%ped-%')`).
  3. Contagem de Sacolas: 'Uma sacola' = um cliente. Para contar quantas sacolas abertas existem, faça COUNT(DISTINCT user_id) na tabela `sacolinhas`.
  4. Itens x Sacolas: Se a pergunta for sobre 'quantos itens tem', conte as linhas de `sacolinhas`. Se for sobre 'quantas sacolas', conte os `user_id` únicos.
  5. Vencimento: O prazo máximo é 31 dias. Use `DATE_ADD(add_at, INTERVAL 31 DAY) < NOW()` para itens vencidos, e `>= NOW()` para os NÃO vencidos.
- Regra Resultado Live: Para saber o faturamento de uma live, faça SUM(price * quantity) na tabela `sacolinhas` filtrando pelo `live_id` correspondente à tabela `lives`.
- Tabela de Pedidos: `pedidos` (id, user_id, valor_total, live_id, status_pedido, status_pagamento). Para faturamento aprovado, use sempre `status_pagamento = 'aprovado'`. O `status_pedido` reflete a logística (ex: enviado, entregue)."];
                        case "estoque":
                            return ["mapa" => "MÓDULO ESTOQUE:
- Tabelas principais: `items` (id, codigo, nome_do_produto, custo, preco, status, localizacao).
- Regra de Status: 'disponivel', 'vendido', 'em_sacolinha', 'sacolinha', 'loja'. Se status for 'vendido' ou 'em_sacolinha', a coluna 'localizacao' muda para 'Sacolinha'.
- NOTA: Para informações sobre sacolinhas de clientes, use o módulo 'sacolinhas' (tabela `sacolinhas`)."];
                        case "clientes":
                        case "cliente":
                        case "enderecos":
                        case "endereco":
                            return ["mapa" => "MÓDULO CLIENTES E ENDEREÇOS:
- Tabela principal: `users` (id, name, email, cidade, estado, bairro, endereco, numero_endereco, complemento, cep, phone, whatsapp, telefone_principal, apelido, instagram, tiktok).
- Regra de Endereço e Cidade: O endereço, cidade, estado, CEP e bairro ficam DIRETAMENTE nas colunas da tabela `users` (NÃO existe tabela separada de endereços!).
- Tabela `pessoas`: Fornecedores, funcionários e pessoas externas do módulo financeiro. Usuários e clientes do sistema são SEMPRE `users`."];
                        case "avaliacao":
                        case "avaliacoes":
                        case "desapego":
                        case "desapegos":
                            return ["mapa" => "MÓDULO AVALIAÇÕES E DESAPEGOS:
- Controller principal: `AvaliacaoController` (`app/Http/Controllers/Admin/AvaliacaoController.php`).
- Tabelas principais: `avaliacoes` (id, user_id, status, tipo_compra, valor_total_aprovado, valor_frete, created_at), `avaliacao_itens` (id, avaliacao_id, categoria_id, marca_id, tamanho, valor_sugerido, status).
- Regra: Peças enviadas por clientes para desapego. Se aprovadas, podem virar crédito na carteira do cliente (`conta_corrente`) ou pagamento via PIX/banco."];
                        case "controllers":
                        case "controller":
                        case "rotas":
                        case "sistema":
                        case "modulos":
                            return ["mapa" => "ÍNDICE COMPLETO DE CONTROLLERS E TELAS DO SISTEMA:
- MÓDULO FINANCEIRO:
  * `DreController`: DRE Contábil e Gerencial (Receita Bruta, Deduções, Receita Líquida, CMV, Despesas Operacionais, EBITDA, Lucro Líquido).
  * `FluxoCaixaController`: Fluxo de Caixa real (entradas e saídas por contas bancárias).
  * `OrcamentoController`: Orçamento Previsto x Realizado por categoria.
  * `ContaCorrenteController` / `ContaBancariaController`: Carteira de Clientes e Saldos Bancários.
  * `ConciliacaoController`: Extratos bancários importados e conciliação.
  * `LancamentoController` / `MovimentacaoController`: Contas a pagar e a receber.
- MÓDULO SACOLINHAS E PEDIDOS:
  * `SacolinhaController` / `AdminSacolinhaController` / `SacolinhaVencidaController`: Sacolinhas abertas, itens reservados, contagem de clientes e prazo de 31 dias.
  * `RelatorioVencimentosController`: Monitoramento de vencimentos de sacolinhas.
  * `PedidoController` / `AdminPedidoController`: Pedidos finalizados e faturamento.
- MÓDULO VENDAS E LIVES:
  * `LiveController` / `LiveMovimentacaoController`: Lives, vendas e separação em tempo real.
- MÓDULO ESTOQUE E DESAPEGOS:
  * `ItemController` / `InventarioController`: Peças e estoque.
  * `AvaliacaoController`: Avaliação de desapegos de clientes e conversão em crédito.
- MÓDULO CLUBE:
  * `ClubeDashboardController` / `ClubeMensalidadesController`: Assinaturas ativas e mensalidades.
DICA FUNDAMENTAL: Para ver o código-fonte PHP com todas as fórmulas e regras exatas de qualquer controller, chame a ferramenta `consultar_codigo_controller`!"];
                        default:
                            return ["erro" => "Módulo não reconhecido. Módulos válidos: financeiro, orcamento, dre, fluxo_caixa, sacolinhas, lives, avaliacoes, estoque, clube, clientes, controllers."];
                    }

                case "executar_query_select":
                    $query = $args["query"] ?? "";
                    if (!preg_match("/^\s*SELECT/i", $query)) {
                        return ["erro" => "Por questões de segurança, apenas queries SELECT são permitidas."];
                    }
                    try {
                        // Limitar o retorno a 100 linhas para não explodir o token
                        if (!preg_match("/LIMIT/i", $query) && !preg_match("/COUNT\(/i", $query) && !preg_match("/SUM\(/i", $query)) {
                            $query .= " LIMIT 100";
                        }
                        $resultado = DB::select($query);
                        return ["resultados" => $resultado];
                    } catch (\Exception $e) {
                        return ["erro" => "Erro na sintaxe SQL: " . $e->getMessage()];
                    }

                default:
                    return ["erro" => "Ferramenta {$name} não existe."];
            }
        } catch (\Exception $e) {
            return ["erro" => "Exceção interna: " . $e->GetMessage()];
        }
    }

    public function summarizeChat(string $currentSummary, string $userMsg, string $aiMsg): string
    {
        $messages = [
            [
                "role" => "system",
                "content" => "Você é um sumarizador especialista. Você vai receber o Resumo Atual de uma conversa, e a última interação (Pergunta do usuário e Resposta do assistente). Sua única função é atualizar o Resumo Atual, integrando a nova informação de forma ultra-concisa e direta (apenas os fatos relevantes). Não responda à pergunta, apenas devolva o NOVO RESUMO."
            ],
            [
                "role" => "user",
                "content" => "RESUMO ATUAL: " . ($currentSummary ?: "Nenhum") . "\n\nNOVA INTERAÇÃO:\nUsuário: $userMsg\nAssistente: $aiMsg\n\nMe dê apenas o NOVO RESUMO atualizado:"
            ]
        ];

        $payload = [
            "model" => "gemini-2.5-flash",
            "messages" => $messages,
            "temperature" => 0.0,
            "max_tokens" => 500
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                "Authorization" => "Bearer " . env("GEMINI_API_KEY", ""),
                "Content-Type" => "application/json"
            ])->post("https://generativelanguage.googleapis.com/v1beta/openai/chat/completions", $payload);

            $json = $response->json();
            return $json['choices'][0]['message']['content'] ?? $currentSummary;
        } catch (\Exception $e) {
            return $currentSummary;
        }
    }

    protected function prepareToolContent(string $toolName, $result, string $userPrompt): string
    {
        $content = is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE);
        
        // Se a resposta da ferramenta for maior que 4500 caracteres, orquestramos um resumo para não estourar tokens
        if (mb_strlen($content) > 4500) {
            // Cortamos pra 15000 chars pra não explodir o próprio resumidor se for bizarro de grande
            $chunk = mb_substr($content, 0, 15000); 
            
            $sys = "Você é um orquestrador de dados. A ferramenta '$toolName' retornou uma carga de dados gigantesca. " .
                   "Sua tarefa é analisar esses dados crus e extrair/resumir APENAS a informação que responde a intenção do usuário. " .
                   "Devolva um resumo ultra-conciso (fatos, números, contagens). Não explique o que você fez.";
                   
            $userMsg = "Intenção do usuário: '$userPrompt'\n\nDados crus da ferramenta:\n" . $chunk;
            
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    "Authorization" => "Bearer " . env("GEMINI_API_KEY", ""),
                    "Content-Type" => "application/json"
                ])->post("https://generativelanguage.googleapis.com/v1beta/openai/chat/completions", [
                    "model" => "gemini-2.5-flash",
                    "messages" => [
                        ["role" => "system", "content" => $sys],
                        ["role" => "user", "content" => $userMsg]
                    ],
                    "temperature" => 0.0,
                    "max_tokens" => 800
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $resumo = $json['choices'][0]['message']['content'] ?? "";
                    if (!empty($resumo)) {
                        return "[DADOS RESUMIDOS PELO ORQUESTRADOR]: " . $resumo;
                    }
                }
            } catch (\Exception $e) {
                // fallthrough
            }
            
            // Se falhar a sumarização, trunca brutalmente para proteger o loop principal
            return "[DADOS TRUNCADOS POR TAMANHO]: " . mb_substr($content, 0, 1500);
        }
        
        return $content;
    }
}