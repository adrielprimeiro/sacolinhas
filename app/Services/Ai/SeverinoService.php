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
        $systemInstruction = "Seu nome é Severino, um assistente de IA focado na administração do sistema Mania de Melissa.\n" .
            "Você ajuda os administradores consultando informações internas através de suas ferramentas.\n" .
            "Seja direto, técnico e proativo. Nunca execute nenhuma alteração, apenas consulte e informe.\n" .
            "Responda em formato Markdown, com listas ou negrito quando ajudar na visualização.";

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
                        "name" => "consultar_esquema_banco",
                        "description" => "Retorna a estrutura (tabelas e colunas) do banco de dados da empresa para você saber como montar suas queries SQL.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => (object)[]
                        ]
                    ],
                    [
                        "name" => "executar_query_select",
                        "description" => "Executa uma query SQL SELECT no banco de dados da empresa. Útil para consultar vendas, lives, totalizadores, cadastros, etc. ATENÇÃO: Nunca limite a resposta se precisar somar (ex: use SUM).",
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

        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                "role" => $msg["role"] === "assistant" ? "model" : "user",
                "parts" => [["text" => $msg["text"] ?? $msg["message"] ?? ""]]
            ];
        }
        $contents[] = [
            "role" => "user",
            "parts" => [["text" => $userPrompt]]
        ];

        $payload = [
            "contents" => $contents,
            "tools" => $tools,
            "system_instruction" => ["parts" => [["text" => $systemInstruction]]],
            "generationConfig" => [
                "temperature" => 0.2
            ]
        ];

        $modelsToTry = ["gemini-3-flash-preview", "gemini-3.1-flash-lite", "gemini-3.5-flash"];

        for ($i = 0; $i < 5; $i++) {
            $response = null;
            foreach ($modelsToTry as $modelName) {
                try {
                    $response = Http::timeout(30)->post("{$this->baseUrl}/models/{$modelName}:generateContent?key={$this->apiKey}", $payload);
                    if ($response->successful()) {
                        break;
                    }
                    if ($response->status() == 429) {
                        sleep(2); // Rate limit, aguarda 2s antes de tentar de novo
                    }
                    Log::warning("Severino falhou no modelo {$modelName} ({$response->status()}): {$response->body()}");
                } catch (\Exception $e) {
                    Log::warning("Severino timeout/erro no modelo {$modelName}: " . $e->getMessage());
                    $response = null;
                }
            }

            if (!$response || !$response->successful()) {
                return "Erro na API do Gemini. Todos os modelos falharam. Verifique os logs.";
            }

            $data = $response->json();
            $candidate = $data["candidates"][0] ?? null;

            if (!$candidate) {
                return "Não consegui formular uma resposta.";
            }

            $parts = $candidate["content"]["parts"] ?? [];
            $hasFunctionCall = false;

            if (isset($parts[0]["text"]) && count($parts) === 1) {
                return $parts[0]["text"];
            }

            $toolResponses = [];
            foreach ($parts as $part) {
                if (isset($part["functionCall"])) {
                    $hasFunctionCall = true;
                    $call = $part["functionCall"];
                    $name = $call["name"];
                    $args = $call["args"] ?? [];
                    
                    Log::info("Severino chamando ferramenta: {$name}", $args);
                    $result = $this->executeTool($name, $args);

                    $toolResponses[] = [
                        "functionResponse" => [
                            "name" => $name,
                            "response" => ["name" => $name, "content" => $result]
                        ]
                    ];
                }
            }

            if ($hasFunctionCall) {
                // Fix: empty args [] becomes a list when json_encoded. Gemini expects an object.
                foreach ($candidate["content"]["parts"] as &$p) {
                    if (isset($p["functionCall"]) && isset($p["functionCall"]["args"])) {
                        if (empty($p["functionCall"]["args"])) {
                            $p["functionCall"]["args"] = (object)[];
                        }
                    }
                }
                unset($p);

                $payload["contents"][] = $candidate["content"];
                $payload["contents"][] = [
                    "role" => "user",
                    "parts" => $toolResponses
                ];
            } else {
                return $parts[0]["text"] ?? "Resposta processada mas sem texto legível.";
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

                case "consultar_esquema_banco":
                    $tabelas = DB::select("SHOW TABLES");
                    $esquema = [];
                    foreach($tabelas as $t) {
                        $tabela = array_values((array)$t)[0];
                        // Ignora algumas tabelas grandes demais ou de sistema
                        if (in_array($tabela, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_reset_tokens'])) continue;
                        $colunas = DB::select("SHOW COLUMNS FROM `{$tabela}`");
                        $esquema[$tabela] = array_map(function($c) { return $c->Field . " (" . $c->Type . ")"; }, $colunas);
                    }
                    return $esquema;

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