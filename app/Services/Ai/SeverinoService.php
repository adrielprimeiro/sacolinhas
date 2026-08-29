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

    public function askSeverino(string $userPrompt, array $history = []): string
    {
        $dataAtual = date('Y-m-d H:i:s');
        $systemInstruction = "Seu nome é Severino, um assistente de IA focado na administração do sistema Mania.\n" .
            "Hoje é: {$dataAtual}\n" .
            "Você ajuda os administradores consultando informações internas através de suas ferramentas.\n" .
            "REGRA DE OURO PARA BANCO DE DADOS: Sempre chame 'consultar_memoria_sql' primeiro para ver se você já tem a query salva para a pergunta. Se não tiver e não houver ferramenta específica, chame 'mapear_modulo_sistema' para aprender o esquema e depois 'executar_query_select'. USE SEMPRE SINTAXE MYSQL.\n" .
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
            $groqTools[] = [
                "type" => "function",
                "function" => [
                    "name" => $func["name"],
                    "description" => $func["description"],
                    "parameters" => [
                        "type" => "object",
                        "properties" => $func["parameters"]["properties"] ?? (object)[],
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

        foreach ($history as $msg) {
            $messages[] = [
                "role" => $msg["role"] === "assistant" || $msg["role"] === "model" ? "assistant" : "user",
                "content" => $msg["text"] ?? $msg["message"] ?? ""
            ];
        }
        $messages[] = [
            "role" => "user",
            "content" => $userPrompt
        ];

        $payload = [
            "model" => "openai/gpt-oss-120b", // GPT OSS 120B on Groq
            "messages" => $messages,
            "tools" => $groqTools,
            "tool_choice" => "auto",
            "temperature" => 0.2
        ];

        for ($i = 0; $i < 12; $i++) { // Loop das ferramentas
            $response = null;
            
            try {
                $response = Http::withToken($groqKey)
                    ->timeout(20)
                    ->post("https://api.groq.com/openai/v1/chat/completions", $payload);

                if (!$response->successful()) {
                    if ($response->status() == 429) {
                        Log::warning("Groq Rate Limit. Aguardando 2s...");
                        sleep(2);
                        continue;
                    }
                    Log::warning("Groq falhou ({$response->status()}): {$response->body()}");
                    return "Erro na API da Groq. Verifique os logs.";
                }
            } catch (\Exception $e) {
                Log::warning("Groq timeout/erro: " . $e->getMessage());
                return "Erro de conexão com a IA Groq.";
            }

            $data = $response->json();
            $choice = $data["choices"][0] ?? null;

            if (!$choice) {
                return "Não consegui formular uma resposta.";
            }

            $message = $choice["message"] ?? [];

            // Adiciona a resposta da IA no histórico para o próximo round
            $payload["messages"][] = $message;

            if (!empty($message["tool_calls"])) {
                foreach ($message["tool_calls"] as $toolCall) {
                    $name = $toolCall["function"]["name"];
                    $args = json_decode($toolCall["function"]["arguments"], true) ?? [];
                    
                    Log::info("Severino chamando ferramenta Groq: {$name}", $args);
                    $result = $this->executeTool($name, $args);

                    $payload["messages"][] = [
                        "role" => "tool",
                        "tool_call_id" => $toolCall["id"],
                        "name" => $name,
                        "content" => is_string($result) ? $result : json_encode($result)
                    ];
                }
            } else {
                return $message["content"] ?? "Resposta processada mas sem texto legível.";
            }
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
                    $disponivel = max(0, $valorLimite + $saldo - $utilizado);

                    return [
                       "saldo_carteira" => $saldo,
                       "limite_concedido_empresa" => $valorLimite,
                       "limite_utilizado" => $utilizado,
                       "limite_sacolinha_disponivel" => $disponivel
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
                        ->where('pago', 1)
                        ->selectRaw('COUNT(*) as total_pedidos, SUM(valor_total) as faturamento, AVG(valor_total) as valor_medio')
                        ->first();

                    return [
                        "mes" => date('m/Y'),
                        "total_pedidos" => (int) $stats->total_pedidos,
                        "faturamento" => (float) $stats->faturamento,
                        "valor_medio" => (float) $stats->valor_medio
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
- Tabelas principais: `contas_bancarias` (id, nome, tipo, saldo_atual), `movimentacoes` (id, conta_bancaria_id, valor_pago, data_pagamento, lancamento_id), `lancamentos` (id, tipo='receita'/'despesa', status='pendente'/'pago', pessoa_id).
- Regra de Saldo: O 'saldo_atual' da tabela `contas_bancarias` é o valor oficial e real do dinheiro da empresa (ex: Inter, Carteira Cliente).
- Regra de Movimentações: Tudo que entra ou sai de verdade do banco passa por `movimentacoes`.
- Tabela `transacoes_extrato`: Apenas extrato importado cru, NÃO use para calcular saldo oficial da empresa."];
                        case "clube":
                            return ["mapa" => "MÓDULO CLUBE MANIA:
- Tabelas principais: `clube_assinaturas` (id, user_id, status), `clube_mensalidades` (id, user_id, mes_referencia, status_pagamento).
- Regra Ativos: Um cliente é assinante ativo se existe em `clube_assinaturas` com `status = 'ativa'`.
- Regra Pagamento: Para saber quem pagou, cruze `clube_assinaturas` com `clube_mensalidades` pelo `user_id`. A coluna `mes_referencia` guarda o mês (ex: 2026-08-01) e `status_pagamento` pode ser 'pago' ou 'pendente'."];
                        case "lives":
                            return ["mapa" => "MÓDULO LIVES E VENDAS:
- Tabelas principais: `lives` (id, data, tipo_live, plataformas, ativo, encerrada_em).
- Tabela de Itens Separados: `sacolinhas` (id, user_id, item_id, live_id, quantity, price, status, add_at).
- Regra Resultado Live: Para saber o faturamento de uma live, faça SUM(price * quantity) na tabela `sacolinhas` filtrando pelo `live_id` correspondente à tabela `lives`.
- Tabela de Pedidos Pagos: `pedidos` (id, user_id, valor_total, live_id, pago)."];
                        case "estoque":
                            return ["mapa" => "MÓDULO ESTOQUE:
- Tabelas principais: `items` (id, codigo, nome_do_produto, custo, preco, status, localizacao).
- Regra de Status: 'disponivel', 'vendido', 'em_sacolinha', 'sacolinha', 'loja'. Se status for 'vendido' ou 'em_sacolinha', a coluna 'localizacao' muda para 'Sacolinha'."];
                        case "clientes":
                            return ["mapa" => "MÓDULO CLIENTES:
- Tabelas principais: `users` (id, name, email, instagram, tiktok, telefone), `pessoas` (id, nome, cpf_cnpj, telefone).
- Regra: Usuários do sistema e do app são `users`. Entidades financeiras/fornecedores no financeiro são `pessoas`."];
                        default:
                            return ["erro" => "Módulo não reconhecido. Módulos válidos: financeiro, clube, lives, estoque, clientes."];
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
}