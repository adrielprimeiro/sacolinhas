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
                
                return "\n[ESTATÍSTICAS BÁSICAS DO SISTEMA (MEMÓRIA IMEDIATA)]\n- Faturamento Aprovado Deste Mês: R$ " . number_format($faturamento, 2, ',', '.') . "\n" .
                       "- Total de Pedidos Aprovados Deste Mês: " . $totalPedidos . "\n" .
                       "- Total de Clientes Cadastrados: " . $totalClientes . "\n" .
                       "- Total de Sacolinhas em Aberto: " . $totalSacolinhas . "\n" .
                       "- Sacolinhas Vencidas (com itens > 31 dias): " . $sacolinhasVencidas . "\n" .
                       "- Sacolinhas Sem Itens Vencidos (Em Dia): " . $sacolinhasEmDia . "\n" .
                       "- Total de Peças/Itens Vencidos nas Sacolinhas: " . (int)$itensVencidos . "\n" .
                       "- Peças Disponíveis em Estoque: " . $itensDisponiveis . "\n";
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
                        "description" => "Busca o ID e dados básicos de um cliente pelo nome, email, apelido, instagram, tiktok ou telefone. Se retornar vários, pergunte ao usuário qual é o correto.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "termo" => ["type" => "STRING", "description" => "Nome, email ou parte do nome do cliente"]
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
            "max_tokens" => 400
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
                    $termo = $args["termo"] ?? "";
                    $users = User::where("name", "like", "%{$termo}%")
                        ->orWhere("email", "like", "%{$termo}%")
                        ->orWhere("apelido", "like", "%{$termo}%")
                        ->orWhere("instagram", "like", "%{$termo}%")
                        ->orWhere("tiktok", "like", "%{$termo}%")
                        ->orWhere("nome_cliente", "like", "%{$termo}%")
                        ->select("id", "name", "email", "phone", "apelido", "instagram", "tiktok")
                        ->limit(10)
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

                case "mapear_modulo_sistema":
                    $modulo = strtolower($args["modulo"] ?? "");
                    switch ($modulo) {
                        case "financeiro":
                            return ["mapa" => "MÓDULO FINANCEIRO:
- Tabelas principais: `contas_bancarias` (id, nome, tipo, saldo_atual), `movimentacoes` (id, conta_bancaria_id, valor_pago, data_pagamento, lancamento_id), `lancamentos` (id, tipo='receita'/'despesa', status='pendente'/'pago', pessoa_id, descricao, valor_total, data_vencimento).
- Regra de Pessoas (Clientes/Fornecedores): Se precisar buscar um lançamento ou movimentação por nome (ex: fornecedor 'Meias' ou 'Leandro'), você DEVE fazer um JOIN com a tabela `pessoas` (id, nome) usando o `pessoa_id` da tabela `lancamentos`.
- Regra de Saldo: O 'saldo_atual' da tabela `contas_bancarias` é o valor oficial e real do dinheiro da empresa (ex: Inter, Carteira Cliente).
- Regra de Movimentações: Tudo que entra ou sai de verdade do banco passa por `movimentacoes`.
- Tabela `transacoes_extrato`: Apenas extrato importado cru, NÃO use para calcular saldo oficial da empresa."];
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
                            return ["mapa" => "MÓDULO CLIENTES:
- Tabelas principais: `users` (id, name, email, instagram, tiktok, telefone), `pessoas` (id, nome, cpf_cnpj, telefone).
- Regra: Usuários do sistema e do app são `users`. Entidades financeiras/fornecedores no financeiro são `pessoas`."];
                        default:
                            return ["erro" => "Módulo não reconhecido. Módulos válidos: financeiro, clube, lives, sacolinhas, estoque, clientes."];
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