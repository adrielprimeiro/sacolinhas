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
                        "description" => "Busca o ID e dados básicos de um cliente pelo nome, email ou telefone.",
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

        for ($i = 0; $i < 4; $i++) {
            $response = Http::timeout(20)->post("{$this->baseUrl}/models/gemini-2.5-flash:generateContent?key={$this->apiKey}", $payload);

            if (!$response->successful()) {
                Log::error("Severino API Error: " . $response->body());
                return "Erro na API do Gemini: " . $response->status();
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
                        ->select("id", "name", "email", "telefone")
                        ->limit(5)
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

                default:
                    return ["erro" => "Ferramenta {$name} não existe."];
            }
        } catch (\Exception $e) {
            return ["erro" => "Exceção interna: " . $e->getMessage()];
        }
    }
}
