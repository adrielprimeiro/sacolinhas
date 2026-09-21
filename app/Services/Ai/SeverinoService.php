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
        $isContinue = in_array(strtolower(trim($userPrompt)), ['continue', 'continuar', 'prossiga', 'retomar']);
        $lastUserPrompt = null;
        if ($isContinue) {
            for ($k = count($history) - 1; $k >= 0; $k--) {
                $role = $history[$k]['role'] ?? '';
                $txt = trim($history[$k]['text'] ?? $history[$k]['message'] ?? '');
                if ($role === 'user' && !in_array(strtolower($txt), ['continue', 'continuar', 'prossiga', 'retomar'])) {
                    $lastUserPrompt = $txt;
                    break;
                }
            }
            if ($lastUserPrompt) {
                $userPrompt = "Você já consultou dados na tentativa anterior e eles estão registrados abaixo na sua prancheta. NÃO continue chamando ferramentas indefinidamente! Sintetize os dados já coletados e ENTREGUE A RESPOSTA FINAL COMPLETA E FORMATADA à pergunta: '{$lastUserPrompt}'";
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
            "AVALIAÇÃO DO FEEDBACK DO USUÁRIO E AUTO-CORREÇÃO (REGRA FUNDAMENTAL):\n" .
            "- Analise SEMPRE a mensagem do usuário em relação à sua resposta anterior no histórico:\n" .
            "  1. Se o usuário contestar, demonstrar dúvida, estranheza ou disser algo como 'você já falou isso!', 'tá falando de X ou Y?', 'não foi isso que perguntei', 'quantos lançamentos?', RECONHEÇA IMEDIATAMENTE que a resposta anterior não foi a esperada ou foi ambígua!\n" .
            "  2. NUNCA, sob hipótese alguma, repita a mesma resposta ou frase anterior! Isso é inaceitável.\n" .
            "  3. Investigue ativamente via código (`consultar_codigo_controller`), mapa (`mapear_modulo_sistema`) ou banco (`executar_query_select`) para entender as opções.\n" .
            "  4. SE MESMO APÓS INVESTIGAR VOCÊ NÃO SOUBER OU SE A PERGUNTA ENVOLVER CRITÉRIOS AMBÍGUOS DO NEGÓCIO:\n" .
            "     👉 PERGUNTE DE FORMA DIRETA E OBJETIVA AO USUÁRIO o que ele realmente gostaria que você procurasse! Seja humilde e transparente, mostrando o que você encontrou no sistema e perguntando qual das opções reflete a necessidade real dele.\n" .
            "AUTONOMIA E RESOLUÇÃO DOS PRÓPRIOS PROBLEMAS (LATM):\n" .
            "- O Severino deve resolver seus próprios problemas e evoluir sozinho a cada conversa!\n" .
            "- Sempre que o usuário te explicar o que ele realmente procura (ou quando você descobrir a query correta no banco para uma pergunta nova):\n" .
            "  1. Entregue a resposta imediata ao usuário em português claro com os números apurados.\n" .
            "  2. Chame `memorizar_regra_ou_preferencia` para gravar a regra/definição na sua base de conhecimento permanente (`KnowledgeBase`).\n" .
            "  3. Chame `criar_ferramenta_dinamica` para registrar a ferramenta no catálogo do banco (`severino_dynamic_tools`), para que você mesmo a consulte no futuro sem precisar errar novamente!\n" .
            "REGRA FUNDAMENTAL: SEU TRABALHO É PESQUISAR NO BANCO DE DADOS, NUNCA PEDIR DADOS AO USUÁRIO!\n" .
            "- É TERMINANTEMENTE PROIBIDO perguntar ao usuário valores, custos, despesas de compra, faturamento, saldos ou quaisquer números que pertencem ao banco de dados (é inaceitável perguntar 'qual custo devemos considerar?', 'qual foi o custo das peças?'). Busque sempre no banco!\n" .
            "- CÁLCULO DE LUCRO E RESULTADO DE LIVES:\n" .
            "  1. Na tabela `items`, a coluna `custo` guarda o preço de compra (custo) de cada peça.\n" .
            "  2. Na tabela `sacolinhas`, a coluna `price` guarda o preço de venda de cada peça vinculada à live (`live_id`).\n" .
            "  3. Faturamento Bruto da live = SUM(sacolinhas.price * sacolinhas.quantity).\n" .
            "  4. Custo Total das Peças = SUM(COALESCE(items.custo, 0) * sacolinhas.quantity).\n" .
            "  5. Lucro Bruto da live = Faturamento Bruto - Custo Total das Peças (Preço de Venda menos Preço de Compra/Custo).\n" .
            "  6. Sempre que o usuário perguntar pelo LUCRO de uma live, USE A FERRAMENTA `resumo_live` (que já entrega faturamento bruto, custo total das peças e lucro bruto apurado) e apresente esses números com clareza!\n" .
            "REGRAS CONCEITUAIS DO MÓDULO FINANCEIRO E CONCILIAÇÃO:\n" .
            "- DISTINÇÃO OBRIGATÓRIA ENTRE TRANSAÇÃO DE EXTRATO E LANÇAMENTO FINANCEIRO:\n" .
            "  1. 'Transação de Extrato' (tabela `transacoes_extrato`): São as movimentações importadas diretamente do banco (Banco Inter / Mercado Pago). Possuem status 'pendente' (aguardando conciliação), 'conciliado' ou 'ignorado'. NUNCA as chame de 'lançamentos'!\n" .
            "  2. 'Lançamento Financeiro' (tabela `lancamentos`): São os títulos financeiros em aberto no sistema (contas a pagar e a receber). Se o usuário perguntar 'quantos lançamentos?', informe os títulos em aberto (tabela `lancamentos`) E diferencie das transações pendentes no extrato (`transacoes_extrato`)!\n" .
            "- DISTINÇÃO OBRIGATÓRIA ENTRE SINCRONIZAÇÃO E CONCILIAÇÃO:\n" .
            "  1. 'Última Sincronização': Momento em que o sistema buscou e baixou novas transações da API bancária ou OFX para o sistema.\n" .
            "  2. 'Última Conciliação': Momento em que uma transação bancária foi efetivamente vinculada/casada a um lançamento financeiro no sistema.\n" .
            "  3. Se o usuário perguntar 'qual foi a última atualização do extrato?', informe as duas informações (da última sincronização bancária e da última conciliação realizada) para evitar ambiguidade!\n" .
            "  4. Se o usuário perguntar 'Você tá falando da sincronização ou da última conciliação?', responda diretamente esclarecendo a data de cada uma!\n" .
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
                        "description" => "Retorna o panorama financeiro e operacional completo de lives: faturamento bruto, custo total das peças vendidas (preço de compra), lucro bruto apurado (faturamento - custo), margem de lucro (%), total de peças separadas, clientes distintos e sacolinhas. Use SEMPRE que perguntarem sobre faturamento, lucro, resultado, custo ou desempenho de live(s).",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "data" => ["type" => "STRING", "description" => "Opcional. Data no formato YYYY-MM-DD. Se vazio, analisa a live mais recente."],
                                "quantidade_lives" => ["type" => "INTEGER", "description" => "Opcional. Quantidade de últimas lives para analisar e calcular médias (ex: 5, 10, 20). Padrão é 1."]
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
                        "name" => "listar_sacolinhas_vencidas",
                        "description" => "Retorna a lista dos clientes com sacolinhas vencidas (com peças paradas há mais de 31 dias), ordenadas pelo maior valor vencido, com quantidade de peças, valor total e tempo de atraso.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "limite" => ["type" => "INTEGER", "description" => "Quantidade de clientes para retornar (padrão: 10, máximo: 30)"]
                            ]
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
                    ],
                    [
                        "name" => "criar_ferramenta_dinamica",
                        "description" => "Registra permanentemente uma nova ferramenta autônoma no banco de dados. USE SEMPRE que você deduzir ou validar uma nova query SQL para responder a uma pergunta do usuário que não tinha ferramenta pronta. A query deve ser SELECT parametrizada com :nome_parametro.",
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => [
                                "nome" => ["type" => "STRING", "description" => "Identificador único em snake_case (ex: listar_desapegos_aprovados, ranking_vendas_por_marca)"],
                                "descricao" => ["type" => "STRING", "description" => "Explicação clara do que a ferramenta faz e quando deve ser chamada"],
                                "modulo_area" => ["type" => "STRING", "description" => "Área do sistema (comercial, estoque, financeiro, clube, clientes)"],
                                "parametros_json" => ["type" => "STRING", "description" => "JSON com os parâmetros opcionais (ex: {\"limite\": {\"type\": \"integer\", \"description\": \"Quantidade máxima\"}}). Se não tiver, envie '{}'"],
                                "sql_template" => ["type" => "STRING", "description" => "Query SQL SELECT exata, usando binds :nome_parametro para filtros"],
                                "exemplo_pergunta" => ["type" => "STRING", "description" => "Exemplo de pergunta do usuário que essa ferramenta resolve"]
                            ],
                            "required" => ["nome", "descricao", "sql_template"]
                        ]
                    ]
                ]
            ]
        ];

        // Carrega Ferramentas Dinâmicas Autônomas criadas pelo próprio Severino (LATM)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('severino_dynamic_tools')) {
                $dynamicTools = \App\Models\SeverinoDynamicTool::where('ativo', true)->get();
                foreach ($dynamicTools as $dTool) {
                    $dParams = $dTool->parametros ?? [];
                    $props = [];
                    $required = [];
                    foreach ($dParams as $pKey => $pDef) {
                        $props[$pKey] = [
                            "type" => strtoupper($pDef["type"] ?? "STRING"),
                            "description" => $pDef["description"] ?? ""
                        ];
                        if (!empty($pDef["required"])) {
                            $required[] = $pKey;
                        }
                    }
                    $tools[0]["functionDeclarations"][] = [
                        "name" => $dTool->nome,
                        "description" => $dTool->descricao,
                        "parameters" => [
                            "type" => "OBJECT",
                            "properties" => empty($props) ? (object)[] : $props,
                            "required" => $required
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Erro ao carregar ferramentas dinâmicas: " . $e->getMessage());
        }

        $groqKey = config('services.groq.api_key') ?: env('GROQ_API_KEY', '');
        $geminiKey = config('services.gemini.paid_api_key') ?: (config('services.gemini.api_key') ?: env('GEMINI_API_KEY', ''));

        if (empty($groqKey) && empty($geminiKey)) {
            return "Chave da API de IA (Gemini ou Groq) não configurada.";
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
            
            $maxLen = ($msg["role"] === "assistant" || $msg["role"] === "model") ? 300 : 400;
            $truncatedText = mb_strlen($rawText) > $maxLen ? mb_substr($rawText, 0, $maxLen) . "..." : $rawText;
            $messages[] = [
                "role" => $msg["role"] === "assistant" || $msg["role"] === "model" ? "assistant" : "user",
                "content" => $truncatedText
            ];
        }

        // Resgata a última mensagem do assistente para análise de feedback e anti-repetição
        $lastAssistantMsg = null;
        for ($k = count($history) - 1; $k >= 0; $k--) {
            $r = $history[$k]['role'] ?? '';
            if ($r === 'assistant' || $r === 'model') {
                $txtCandidate = trim($history[$k]['text'] ?? $history[$k]['message'] ?? '');
                if ($txtCandidate !== "") {
                    $lastAssistantMsg = $txtCandidate;
                    break;
                }
            }
        }

        // Análise de feedback corretivo ou de dúvida do usuário
        $feedbackInstruction = null;
        if ($lastAssistantMsg) {
            $isComplaintOrClarification = preg_match('/(já falou|ja falou|não foi isso|nao foi isso|tá falando de|ta falando de|você disse|voce disse|errado|não é isso|nao e isso|quero saber é|quero saber e|quantos lançamentos|quantos lancamentos)/iu', $userPrompt);
            $isFormulaOrRule = preg_match('/(faça o calculo|faca o calculo|calcule|menos o preço|menos o preco|preço de venda|preco de venda|custo de compra|preço de compra|preco de compra|fórmula|formula)/iu', $userPrompt);

            if ($isComplaintOrClarification) {
                $feedbackInstruction = "[AVALIAÇÃO DO FEEDBACK DO USUÁRIO]: O usuário contestou ou pediu esclarecimento sobre sua resposta anterior ('" . mb_substr($lastAssistantMsg, 0, 150) . "...'). NUNCA repita a mesma resposta anterior! Se a pergunta envolver conceitos distintos (ex: sincronização de extrato bancário vs conciliação de lançamentos), consulte as duas coisas no banco ou explique as opções e pergunte ao usuário exatamente o que ele deseja que você procure.";
            } elseif ($isFormulaOrRule) {
                $feedbackInstruction = "[ENSINAMENTO DE REGRA PELO USUÁRIO]: O usuário está te ensinando como calcular ou onde buscar a informação! É TERMINANTEMENTE PROIBIDO pedir números ou valores ao usuário. Use 'mapear_modulo_sistema' e 'executar_query_select' para consultar o banco e calcular o resultado. Em seguida, chame 'criar_ferramenta_dinamica' para gravar este aprendizado no catálogo de ferramentas autônomas.";
            }
        }

        if ($feedbackInstruction) {
            $messages[] = [
                "role" => "system",
                "content" => $feedbackInstruction
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
            "max_tokens" => 800
        ];

        $providersToTry = [];

        // 1. Groq (Prioridade 1: Ultrarrápido ~250ms a 900ms via LPU)
        if (!empty($groqKey)) {
            $providersToTry[] = [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "openai/gpt-oss-20b",
                "name" => "Groq GPT OSS 20B",
                "default_score" => 19,
                "timeout" => 7
            ];
            $providersToTry[] = [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "qwen/qwen3.8-27b",
                "name" => "Groq Qwen 27B",
                "default_score" => 18,
                "timeout" => 7
            ];
            $providersToTry[] = [
                "url" => "https://api.groq.com/openai/v1/chat/completions",
                "key" => $groqKey,
                "model" => "openai/gpt-oss-120b",
                "name" => "Groq GPT OSS 120B",
                "default_score" => 16,
                "timeout" => 9
            ];
        }

        // 2. Google Gemini (Prioridade 2: Modelos modernos de alta precisão e cotas generosas)
        if (!empty($geminiKey)) {
            if (!\Illuminate\Support\Facades\Cache::has('gemini_model_exhausted_' . md5('gemini-3.1-flash-lite'))) {
                $providersToTry[] = [
                    "url" => "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions",
                    "key" => $geminiKey,
                    "model" => "gemini-3.1-flash-lite",
                    "name" => "Google Gemini 3.1 Flash Lite",
                    "default_score" => 14,
                    "timeout" => 8
                ];
            }
            if (!\Illuminate\Support\Facades\Cache::has('gemini_model_exhausted_' . md5('gemini-3.5-flash'))) {
                $providersToTry[] = [
                    "url" => "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions",
                    "key" => $geminiKey,
                    "model" => "gemini-3.5-flash",
                    "name" => "Google Gemini 3.5 Flash",
                    "default_score" => 13,
                    "timeout" => 8
                ];
            }
        }

        // 3. OpenRouter (Apenas se tiver saldo e não estiver desativado por 402)
        $orKey = config('services.openrouter.api_key') ?: env('OPENROUTER_API_KEY', '');
        if (!empty($orKey) && !\Illuminate\Support\Facades\Cache::has('openrouter_disabled_402')) {
            $providersToTry[] = [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => $orKey,
                "model" => "meta-llama/llama-3.3-70b-instruct",
                "name" => "OpenRouter Llama 3.3 70B",
                "default_score" => 5
            ];
            $providersToTry[] = [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => $orKey,
                "model" => "mistralai/mistral-large-2407",
                "name" => "OpenRouter Mistral Large",
                "default_score" => 4
            ];
            $providersToTry[] = [
                "url" => "https://openrouter.ai/api/v1/chat/completions",
                "key" => $orKey,
                "model" => "deepseek/deepseek-chat",
                "name" => "OpenRouter DeepSeek Chat",
                "default_score" => 4
            ];
        }

        // Carrega pontuação do cache (inicia com default_score de cada provedor)
        foreach ($providersToTry as &$p) {
            $p['score'] = \Illuminate\Support\Facades\Cache::get("ai_score_" . md5($p['name']), $p['default_score'] ?? 10);
        }
        unset($p);
        
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) { // Loop das ferramentas aumentado para 10 porque agora é super rápido com o cache
            
            // Se estiver em modo continue e já rodou 2 ferramentas, force tool_choice = 'none' para obrigar a síntese final
            if ($isContinue && $i >= 2) {
                $payload["tool_choice"] = "none";
            }

            // Controle anti-timeout do Nginx (60s). Se já passaram 40 segundos, forçamos a pausa amigável!
            if (microtime(true) - $startTime > 40) {
                Log::warning("Tempo de execução limite atingido (40s). Forçando pausa técnica para evitar Nginx 504.");
                break;
            }

            $choice = null;
            
            for ($attempt = 0; $attempt < 3; $attempt++) {
                
                // Se todos os provedores estiverem com score negativo (penalizados), reseta para evitar paralisia
                $maxScore = !empty($providersToTry) ? max(array_column($providersToTry, 'score')) : -1;
                if ($maxScore < 0) {
                    foreach ($providersToTry as &$p) {
                        $p['score'] = $p['default_score'] ?? 10;
                        \Illuminate\Support\Facades\Cache::forget("ai_score_" . md5($p['name']));
                    }
                    unset($p);
                }

                // Ordena os provedores pelo score (do maior para o menor)
                usort($providersToTry, function ($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                foreach ($providersToTry as &$provider) {
                    
                    // Checa anti-timeout dentro do loop de provedores também!
                    if (microtime(true) - $startTime > 40) {
                        \Illuminate\Support\Facades\Log::warning("Tempo limite 40s atingido dentro do loop de provedores. Forçando pausa amigável.");
                        if ($sessionId && \Illuminate\Support\Facades\Cache::has('severino_scratchpad_' . $sessionId)) {
                            $pauseCount = (int) \Illuminate\Support\Facades\Cache::get('severino_pause_count_' . $sessionId, 0) + 1;
                            if ($pauseCount >= 2 || $isContinue) {
                                $scratch = \Illuminate\Support\Facades\Cache::get('severino_scratchpad_' . $sessionId, '');
                                \Illuminate\Support\Facades\Cache::forget('severino_scratchpad_' . $sessionId);
                                \Illuminate\Support\Facades\Cache::forget('severino_pause_count_' . $sessionId);
                                return $this->forceFinalAnswerFromScratchpad($scratch, $lastUserPrompt ?? $userPrompt);
                            }
                            \Illuminate\Support\Facades\Cache::put('severino_pause_count_' . $sessionId, $pauseCount, now()->addMinutes(15));
                            return "Pausa técnica! 😅 Fiz várias consultas pesadas no banco de dados e atingi o limite de segurança do servidor para não deixá-lo lento. Já salvei tudo o que descobri até agora na minha 'Prancheta'. Por favor, apenas digite **'continue'** para eu retomar a pesquisa exatamente de onde parei e te dar a resposta final!";
                        }
                        return "Operei ferramentas demais. Parando loop.";
                    }

                    $payloadToSend = $payload;
                    $payloadToSend["model"] = $provider["model"];
                    $cacheKey = "ai_score_" . md5($provider['name']);

                    // Se for Google Gemini e já temos resultados de ferramentas no histórico,
                    // converte para texto padrão para evitar o erro 400 "thought_signature" do Gemini!
                    if (str_contains($provider["url"], "generativelanguage.googleapis.com")) {
                        $hasTool = false;
                        foreach ($payloadToSend["messages"] as $m) {
                            if (($m["role"] ?? "") === "tool" || !empty($m["tool_calls"])) {
                                $hasTool = true;
                                break;
                            }
                        }
                        if ($hasTool) {
                            $sanitizedGeminiMessages = [];
                            foreach ($payloadToSend["messages"] as $m) {
                                if (($m["role"] ?? "") === "tool") {
                                    $sanitizedGeminiMessages[] = [
                                        "role" => "user",
                                        "content" => "[DADOS DA CONSULTA NO BANCO DE DADOS - FERRAMENTA '{$m['name']}']:\n{$m['content']}\n\nCom base nesses dados apurados, elabore e entregue a resposta final completa e formatada em Markdown para o usuário."
                                    ];
                                } elseif (!empty($m["tool_calls"])) {
                                    $sanitizedGeminiMessages[] = [
                                        "role" => "assistant",
                                        "content" => "Vou consultar as ferramentas no sistema."
                                    ];
                                } else {
                                    $sanitizedGeminiMessages[] = $m;
                                }
                            }
                            $payloadToSend["messages"] = $sanitizedGeminiMessages;
                            unset($payloadToSend["tools"]);
                            unset($payloadToSend["tool_choice"]);
                        }
                    }

                    try {
                        $headers = [
                            "Authorization" => "Bearer " . $provider["key"],
                            "Content-Type" => "application/json",
                            "HTTP-Referer" => "https://minhamania.net",
                            "X-Title" => "Controle Sacolinhas"
                        ];

                        $response = Http::withHeaders($headers)
                            ->timeout($provider['timeout'] ?? 10)
                            ->post($provider["url"], $payloadToSend);

                        if ($response->successful()) {
                            $data = $response->json();
                            $choiceCandidate = $data["choices"][0] ?? null;
                            $msgCandidate = $choiceCandidate["message"] ?? [];
                            $hasTools = !empty($msgCandidate["tool_calls"]);
                            $hasTxt = trim((string)($msgCandidate["content"] ?? "")) !== "" || trim((string)($msgCandidate["reasoning"] ?? "")) !== "";

                            if ($choiceCandidate && ($hasTools || $hasTxt)) {
                                // SUCESSO: Aumenta a pontuação em 1 (máximo 10)
                                $provider['score'] = min($provider['score'] + 1, 10);
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                                $choice = $choiceCandidate;
                                break 2; // Sucesso, sai do loop provedores e attempts
                            }

                            // 200 OK mas retornou content vazio e sem ferramentas:
                            $provider['score'] = max($provider['score'] - 5, -30);
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $provider['score'], now()->addMinutes(15));
                            Log::warning("Provedor {$provider['name']} retornou 200 OK mas resposta vazia (sem content e sem tool_calls). Tentando próximo.");
                            continue;
                        }

                        if ($response->status() == 402) {
                            // Saldo insuficiente no OpenRouter
                            \Illuminate\Support\Facades\Cache::put('openrouter_disabled_402', true, now()->addHours(1));
                            $provider['score'] = -50;
                            \Illuminate\Support\Facades\Cache::put($cacheKey, -50, now()->addHours(1));
                            Log::warning("Provedor {$provider['name']} sem créditos (402). Desativando por 1 hora.");
                            continue;
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
                                        $retryCandidate = $data["choices"][0] ?? null;
                                        $retryMsg = $retryCandidate["message"] ?? [];
                                        $retryTools = !empty($retryMsg["tool_calls"]);
                                        $retryTxt = trim((string)($retryMsg["content"] ?? "")) !== "" || trim((string)($retryMsg["reasoning"] ?? "")) !== "";
                                        if ($retryCandidate && ($retryTools || $retryTxt)) {
                                            $choice = $retryCandidate;
                                            break 2;
                                        }
                                    }
                                }
                            }

                            // Se for esgotamento de cota diária do modelo (ex: Free Tier do Google)
                            if (str_contains($body, 'RESOURCE_EXHAUSTED') || str_contains($body, 'GenerateRequestsPerDay') || str_contains($body, 'exceeded your current quota')) {
                                \Illuminate\Support\Facades\Cache::put('gemini_model_exhausted_' . md5($provider['model']), true, now()->addMinutes(30));
                                $provider['score'] = -50;
                                \Illuminate\Support\Facades\Cache::put($cacheKey, -50, now()->addMinutes(30));
                                Log::warning("Provedor {$provider['name']} esgotou a cota do modelo ({$provider['model']}). Desativando por 30 minutos.");
                                continue;
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

                    // Incentivo LATM: se rodou SQL SELECT com sucesso, estimula o registro da ferramenta dinâmica
                    if ($name === 'executar_query_select' && empty($resultado['erro'])) {
                        $resumoDoResultado .= "\n\n[INSTRUÇÃO DE AUTONOMIA LATM]: Query executada com sucesso! Para consolidar este aprendizado e não precisar rodar SQL cru no futuro, você DEVE chamar a ferramenta `criar_ferramenta_dinamica` registrando este template SQL com nome em snake_case, descrição clara e parâmetros se houver, e em seguida entregar a resposta final ao usuário.";
                    }
                    
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

            // 1. TRAVA DE AUTONOMIA: Impede o modelo de pedir dados de negócio/banco ao usuário
            if ($finalText !== "" && $i < 7) {
                $pedeDadosAoUsuario = preg_match('/(\b(me informe|precisamos saber|qual|informe|me diga|qual o|qual é o)\s+(custo|preço de compra|faturamento|saldo|valor gasto|despesa)|não (tenho|possuo) (acesso aos?|os?) (custos?|preços?|dados?)|custo associado a ela|se você souber[,\s]+por exemplo[,\s]+o custo)/iu', $finalText);
                
                if ($pedeDadosAoUsuario) {
                    \Illuminate\Support\Facades\Log::warning("Severino tentou pedir dados ao usuário ('{$finalText}'). Interceptando e forçando ReAct autônomo na iteração {$i}.");
                    $payload["messages"][] = [
                        "role" => "user",
                        "content" => "[SISTEMA - TRAVA DE AUTONOMIA]: É TERMINANTEMENTE PROIBIDO pedir dados, custos, preços de compra, despesas ou faturamento ao usuário! O usuário é o operador e esses dados devem ser apurados no banco de dados.\n" .
                                     "1. O custo das peças está na tabela `items` (coluna `custo`), e os preços na tabela `sacolinhas` (coluna `price`).\n" .
                                     "2. Use 'mapear_modulo_sistema' ou 'executar_query_select' AGORA para consultar diretamente os dados e calcular o que foi pedido.\n" .
                                     "3. Se você não souber onde encontrar a informação ou se houver critérios ambíguos, pergunte ao usuário exatamente o que ele deseja que você procure dentre as opções reais do sistema, mas NUNCA peça para ele calcular ou te fornecer números de banco!"
                    ];
                    continue;
                }

                // 2. TRAVA ANTI-REPETIÇÃO: Impede de repetir a mesma resposta anterior
                if (!empty($lastAssistantMsg) && mb_strlen($lastAssistantMsg) > 20) {
                    similar_text($finalText, $lastAssistantMsg, $similarity);
                    if ($similarity > 65) {
                        \Illuminate\Support\Facades\Log::warning("Severino tentou repetir a resposta anterior ({$similarity}% similar). Interceptando na iteração {$i}.");
                        $payload["messages"][] = [
                            "role" => "user",
                            "content" => "[SISTEMA - ANTI-REPETIÇÃO]: Você está repetindo a mesma resposta da mensagem anterior ('{$lastAssistantMsg}'). O usuário já indicou que isso não atende! Não repita essa frase. Investigue o banco de dados via 'executar_query_select' ou responda explicando claramente as distinções ou perguntando ao usuário o que ele deseja que você procure."
                        ];
                        continue;
                    }
                }

                // 3. AUTO-EXECUÇÃO DE SQL GERADO NO TEXTO: Se o modelo gerou bloco de SQL SELECT no markdown em vez de tool_call
                if (preg_match('/```sql\s*(SELECT\s+[\s\S]+?)\s*```/i', $finalText, $sqlMatch)) {
                    $extractedSql = trim($sqlMatch[1]);
                    \Illuminate\Support\Facades\Log::info("Severino gerou SQL no texto em vez de tool_call. Executando query automaticamente no banco: " . $extractedSql);
                    $sqlResult = $this->executeTool("executar_query_select", ["query" => $extractedSql]);
                    $payload["messages"][] = [
                        "role" => "user",
                        "content" => "[SISTEMA - DADOS DA QUERY SQL EXECUTADA]:\n" . json_encode($sqlResult, JSON_UNESCAPED_UNICODE) . "\n\nCom base nesses dados reais apurados no banco, apresente agora a resposta final completa e formatada em Markdown ao usuário (com os dados e nomes reais, NUNCA com placeholders como [NOME] ou [X]) e invoque a função 'criar_ferramenta_dinamica' para salvar essa ferramenta no seu catálogo oficial."
                    ];
                    continue;
                }

                // 4. DETECÇÃO DE PSEUDO TOOL CALL EM TEXTO: Se escreveu criar_ferramenta_dinamica(...) como texto
                if (preg_match('/criar_ferramenta_dinamica\s*\((.*?)\)/s', $finalText)) {
                    \Illuminate\Support\Facades\Log::info("Severino escreveu criar_ferramenta_dinamica em texto em vez de function call. Forçando chamada real.");
                    $payload["messages"][] = [
                        "role" => "user",
                        "content" => "[SISTEMA - FUNCTION CALL OBRIGATÓRIA]: Você escreveu 'criar_ferramenta_dinamica(...)' em texto markdown. Você DEVE disparar a chamada de função (tool call) oficial 'criar_ferramenta_dinamica' com os parâmetros (nome, descricao, sql_template) para que ela seja salva no banco de dados e entregue a resposta ao usuário."
                    ];
                    continue;
                }

                // 5. BLOQUEIO DE PLACEHOLDERS HALLUCINADOS: Se gerou [NOME DA CLIENTE], [X peças], etc.
                if (preg_match('/\[(NOME|VALOR|DATA|QUANTIDADE|X|TOTAL)[^\]]*\]/i', $finalText)) {
                    \Illuminate\Support\Facades\Log::warning("Severino gerou placeholders no texto ('{$finalText}'). Interceptando.");
                    $payload["messages"][] = [
                        "role" => "user",
                        "content" => "[SISTEMA - ERRO DE PLACEHOLDER]: Você gerou placeholders com colchetes (ex: [NOME], [X]). Isso não é permitido! Chame a ferramenta 'executar_query_select' ou 'mapear_modulo_sistema' para buscar os dados verdadeiros e entregue os nomes e números reais."
                    ];
                    continue;
                }
            }

            if ($finalText !== "") {
                if ($sessionId) {
                    \Illuminate\Support\Facades\Cache::forget('severino_scratchpad_' . $sessionId);
                    \Illuminate\Support\Facades\Cache::forget('severino_pause_count_' . $sessionId);
                }
                \Illuminate\Support\Facades\Log::info("Severino Final Response Message:", $message);
                return $finalText;
            }

            // Fallback de segurança: se o modelo encerrou sem texto, tenta síntese a partir do histórico/scratchpad
            \Illuminate\Support\Facades\Log::warning("Severino recebeu resposta vazia no round final. Tentando síntese de recuperação.");
            if ($sessionId && \Illuminate\Support\Facades\Cache::has('severino_scratchpad_' . $sessionId)) {
                $scratch = \Illuminate\Support\Facades\Cache::get('severino_scratchpad_' . $sessionId, '');
                \Illuminate\Support\Facades\Cache::forget('severino_scratchpad_' . $sessionId);
                \Illuminate\Support\Facades\Cache::forget('severino_pause_count_' . $sessionId);
                return $this->forceFinalAnswerFromScratchpad($scratch, $lastUserPrompt ?? $userPrompt);
            }

            if (!empty($geminiKey)) {
                try {
                    $synthPayload = [
                        "model" => "gemini-3.1-flash-lite",
                        "messages" => array_merge(
                            [["role" => "system", "content" => "Você é o Severino. Formule uma resposta objetiva, completa e em Markdown para o usuário com base no histórico de dados apurados."]],
                            array_slice($payload["messages"], -6),
                            [["role" => "user", "content" => "Por favor, entregue a resposta final formatada."]]
                        ),
                        "temperature" => 0.2,
                        "max_tokens" => 1500
                    ];
                    $synthResp = Http::withHeaders([
                        "Authorization" => "Bearer " . $geminiKey,
                        "Content-Type" => "application/json"
                    ])->timeout(12)->post("https://generativelanguage.googleapis.com/v1beta/openai/chat/completions", $synthPayload);

                    if ($synthResp->successful()) {
                        $st = trim((string)($synthResp->json()["choices"][0]["message"]["content"] ?? ""));
                        if ($st !== "") {
                            return $st;
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erro no fallback de síntese final: " . $e->getMessage());
                }
            }

            return "Não consegui formular uma resposta legível no momento. Por favor, reformule sua pergunta ou digite 'continue'.";
        }

        if ($sessionId && \Illuminate\Support\Facades\Cache::has('severino_scratchpad_' . $sessionId)) {
            $pauseCount = (int) \Illuminate\Support\Facades\Cache::get('severino_pause_count_' . $sessionId, 0) + 1;
            if ($pauseCount >= 2 || $isContinue) {
                $scratch = \Illuminate\Support\Facades\Cache::get('severino_scratchpad_' . $sessionId, '');
                \Illuminate\Support\Facades\Cache::forget('severino_scratchpad_' . $sessionId);
                \Illuminate\Support\Facades\Cache::forget('severino_pause_count_' . $sessionId);
                return $this->forceFinalAnswerFromScratchpad($scratch, $lastUserPrompt ?? $userPrompt);
            }
            \Illuminate\Support\Facades\Cache::put('severino_pause_count_' . $sessionId, $pauseCount, now()->addMinutes(15));
            return "Pausa técnica! 😅 Fiz várias consultas pesadas no banco de dados e atingi o limite de segurança do servidor para não deixá-lo lento. Já salvei tudo o que descobri até agora na minha 'Prancheta'. Por favor, apenas digite **'continue'** para eu retomar a pesquisa exatamente de onde parei e te dar a resposta final!";
        }

        return "Operei ferramentas demais. Parando loop.";
    }

    protected function forceFinalAnswerFromScratchpad(string $scratchpad, string $userPrompt): string
    {
        $sys = "Você é o Severino, assistente de IA da empresa. " .
               "O servidor atingiu o tempo limite de consultas pesadas, mas você já reuniu os dados na sua prancheta. " .
               "Sua tarefa é formular e entregar uma resposta final direta, clara e formatada em Markdown com base no que foi apurado na prancheta. " .
               "Não diga que precisa pesquisar mais: responda com as informações disponíveis de forma objetiva.";
               
        $messages = [
            ["role" => "system", "content" => $sys],
            ["role" => "user", "content" => "Pergunta original: '{$userPrompt}'\n\nDados da prancheta:\n" . mb_substr($scratchpad, 0, 8000)]
        ];

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                "Authorization" => "Bearer " . ($this->apiKey ?: env("GEMINI_API_KEY", "")),
                "Content-Type" => "application/json"
            ])->timeout(15)->post("https://generativelanguage.googleapis.com/v1beta/openai/chat/completions", [
                "model" => "gemini-3.1-flash-lite",
                "messages" => $messages,
                "temperature" => 0.1,
                "max_tokens" => 1000
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $text = trim($json['choices'][0]['message']['content'] ?? "");
                if (!empty($text)) {
                    return $text;
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro na finalização forçada do scratchpad: " . $e->getMessage());
        }

        return "Aqui está o resumo do que foi apurado na consulta:\n" . mb_substr($scratchpad, 0, 1500);
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
                    $qtdLives = max(1, min(30, (int)($args["quantidade_lives"] ?? 1)));

                    // Se solicitou analisar mais de 1 live (para médias ou comparativo)
                    if ($qtdLives > 1 && !$data) {
                        $rows = DB::select("
                            SELECT 
                                l.id as live_id, 
                                l.data, 
                                l.tipo_live,
                                COUNT(DISTINCT s.user_id) as total_sacolinhas,
                                COUNT(s.id) as total_itens,
                                SUM(s.quantity) as total_pecas,
                                COALESCE(SUM(s.price * s.quantity), 0) as faturamento,
                                COALESCE(SUM(COALESCE(i.custo, 0) * s.quantity), 0) as custo_total,
                                COALESCE(SUM((s.price - COALESCE(i.custo, 0)) * s.quantity), 0) as lucro_bruto
                            FROM lives l
                            LEFT JOIN sacolinhas s ON s.live_id = l.id
                            LEFT JOIN items i ON s.item_id = i.id
                            WHERE l.data <= CURDATE()
                            GROUP BY l.id, l.data, l.tipo_live
                            HAVING total_itens > 0
                            ORDER BY l.data DESC
                            LIMIT ?
                        ", [$qtdLives]);

                        if (empty($rows)) {
                            return ["erro" => "Nenhuma live com sacolinhas encontrada."];
                        }

                        $totSacs = 0;
                        $totItens = 0;
                        $totFat = 0;
                        $totCusto = 0;
                        $totLucro = 0;
                        $detalhes = [];

                        foreach ($rows as $r) {
                            $totSacs += (int)$r->total_sacolinhas;
                            $totItens += (int)$r->total_itens;
                            $totFat += (float)$r->faturamento;
                            $totCusto += (float)$r->custo_total;
                            $totLucro += (float)$r->lucro_bruto;
                            $detalhes[] = [
                                "live_id" => $r->live_id,
                                "data" => \Carbon\Carbon::parse($r->data)->format("d/m/Y"),
                                "tipo" => $r->tipo_live,
                                "sacolinhas" => (int)$r->total_sacolinhas,
                                "itens" => (int)$r->total_itens,
                                "faturamento" => (float)$r->faturamento,
                                "custo_itens" => (float)$r->custo_total,
                                "lucro_bruto" => (float)$r->lucro_bruto,
                                "margem" => (float)$r->faturamento > 0 ? round(((float)$r->lucro_bruto / (float)$r->faturamento) * 100, 2) . "%" : "0%"
                            ];
                        }

                        $countLives = count($rows);
                        return [
                            "amostra_lives_analisadas" => $countLives,
                            "media_sacolinhas_por_live" => round($totSacs / $countLives, 1),
                            "media_itens_por_live" => round($totItens / $countLives, 1),
                            "media_faturamento_por_live" => round($totFat / $countLives, 2),
                            "media_lucro_por_live" => round($totLucro / $countLives, 2),
                            "total_geral_faturamento" => round($totFat, 2),
                            "total_geral_custo" => round($totCusto, 2),
                            "total_geral_lucro" => round($totLucro, 2),
                            "total_geral_itens" => $totItens,
                            "detalhes_ultimas_lives" => $detalhes
                        ];
                    }

                    if ($data) {
                        $live = \App\Models\Live::whereDate("data", $data)->first();
                    } else {
                        $live = \App\Models\Live::orderBy("data", "desc")->first();
                    }

                    if (!$live) {
                        return ["erro" => "Nenhuma live encontrada na data informada."];
                    }

                    // Calcula faturamento, custo das peças e lucro bruto usando sacolinhas e items
                    $stats = DB::table("sacolinhas as s")
                        ->leftJoin("items as i", "s.item_id", "=", "i.id")
                        ->where("s.live_id", $live->id)
                        ->selectRaw("
                            COUNT(s.id) as total_itens,
                            SUM(s.quantity) as total_pecas,
                            COUNT(DISTINCT s.user_id) as total_clientes,
                            COALESCE(SUM(s.price * s.quantity), 0) as faturamento,
                            COALESCE(SUM(COALESCE(i.custo, 0) * s.quantity), 0) as custo_total,
                            COALESCE(SUM((s.price - COALESCE(i.custo, 0)) * s.quantity), 0) as lucro_bruto,
                            COUNT(CASE WHEN i.custo IS NULL OR i.custo = 0 THEN 1 END) as itens_sem_custo
                        ")
                        ->first();

                    $faturamento = (float) $stats->faturamento;
                    $custoTotal = (float) $stats->custo_total;
                    $lucroBruto = (float) $stats->lucro_bruto;
                    $margemPercentual = $faturamento > 0 ? round(($lucroBruto / $faturamento) * 100, 2) : 0;

                    return [
                        "live_id" => $live->id,
                        "data_live" => $live->data->format("d/m/Y"),
                        "tipo" => $live->tipo_live,
                        "total_itens_separados" => (int)$stats->total_itens,
                        "quantidade_pecas" => (int)$stats->total_pecas,
                        "faturamento_bruto" => $faturamento,
                        "custo_total_pecas" => $custoTotal,
                        "lucro_bruto" => $lucroBruto,
                        "margem_lucro_percentual" => "{$margemPercentual}%",
                        "itens_sem_custo_cadastrado" => (int)$stats->itens_sem_custo,
                        "clientes_distintos" => (int)$stats->total_clientes,
                        "sacolinhas" => (int)$stats->total_clientes,
                        "formula_utilizada" => "Faturamento Bruto = soma(sacolinhas.price * quantity) | Custo Total = soma(items.custo * quantity) | Lucro Bruto = Faturamento Bruto - Custo Total"
                    ];

                case "status_clube_mensalidades":
                    // Busca todos os assinantes ativos
                    $assinaturas = DB::table('clube_assinaturas')
                        ->where('status', 'ativa')
                        ->get();

                    $anoAtualNum = (int) date('Y');
                    $mesAtualNum = (int) date('n');

                    $pagos = [];
                    $pendentes = [];

                    foreach ($assinaturas as $assinatura) {
                        $user = \App\Models\User::find($assinatura->user_id);
                        $nome = $user ? $user->name : "User ID: " . $assinatura->user_id;

                        // Verifica se pagou a mensalidade do mês atual
                        $mensalidade = DB::table('clube_mensalidades')
                            ->where('user_id', $assinatura->user_id)
                            ->where('competencia_ano', $anoAtualNum)
                            ->where('competencia_mes', $mesAtualNum)
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

                case "listar_sacolinhas_vencidas":
                    $limite = max(1, min(30, (int)($args["limite"] ?? 10)));
                    $vencidas = DB::select("
                        SELECT 
                            u.id as user_id,
                            COALESCE(NULLIF(u.nome_cliente, ''), u.name) as cliente,
                            COUNT(s.id) as itens_vencidos,
                            ROUND(SUM(s.quantity * s.price), 2) as valor_vencido,
                            DATEDIFF(CURDATE(), MIN(s.add_at)) as dias_mais_antigo
                        FROM sacolinhas s
                        JOIN users u ON u.id = s.user_id
                        WHERE s.add_at IS NOT NULL
                          AND u.role = 'client'
                          AND s.status != 'pedido'
                          AND (s.obs IS NULL OR LOWER(s.obs) NOT LIKE '%ped-%')
                          AND DATE(DATE_ADD(s.add_at, INTERVAL 31 DAY)) <= CURDATE()
                        GROUP BY u.id, u.nome_cliente, u.name
                        ORDER BY valor_vencido DESC
                        LIMIT ?
                    ", [$limite]);

                    if (empty($vencidas)) {
                        return ["mensagem" => "Nenhuma sacolinha vencida encontrada no momento! Todas estão em dia."];
                    }

                    $totalClientes = count($vencidas);
                    $totalValor = array_sum(array_column($vencidas, 'valor_vencido'));
                    $totalItens = array_sum(array_column($vencidas, 'itens_vencidos'));

                    $lista = [];
                    foreach ($vencidas as $idx => $v) {
                        $lista[] = [
                            "posicao" => $idx + 1,
                            "cliente" => $v->cliente,
                            "itens_vencidos" => (int)$v->itens_vencidos,
                            "valor_vencido" => (float)$v->valor_vencido,
                            "dias_parado" => (int)$v->dias_mais_antigo
                        ];
                    }

                    return [
                        "total_clientes_listados" => $totalClientes,
                        "valor_total_amostra" => round($totalValor, 2),
                        "total_itens_amostra" => $totalItens,
                        "ranking_clientes_vencidos" => $lista
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
                        case "lancamentos":
                        case "lancamento":
                        case "movimentacoes":
                        case "movimentacao":
                            $zoomFile = base_path('docs/areas/04_FINANCEIRO_CONCILIACAO.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['dre'])) {
                                    if (preg_match('/### Subárea 4\.1:.*?(?=### Subárea 4\.2|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO DRE (RESULTADO DO EXERCÍCIO):\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['orcamento', 'orcamentos', 'previsao', 'previsoes', 'previsto_realizado'])) {
                                    if (preg_match('/### Subárea 4\.2:.*?(?=### Subárea 4\.3|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO ORÇAMENTO (PREVISTO X REALIZADO):\n" . trim($m[0]) . "\n\nREGRA: Use sempre a ferramenta relatorio_orcamento_previsto_realizado para apurar metas vs realizado do mês!"];
                                    }
                                } elseif (in_array($modulo, ['conciliacao', 'extrato', 'extratos'])) {
                                    if (preg_match('/### Subárea 4\.3:.*?(?=### Subárea 4\.4|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CONCILIAÇÃO BANCÁRIA:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['banco', 'bancos', 'contas_bancarias'])) {
                                    if (preg_match('/### Subárea 4\.4:.*?(?=### Subárea 4\.5|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CONTAS BANCÁRIAS:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['lancamento', 'lancamentos', 'movimentacao', 'movimentacoes'])) {
                                    if (preg_match('/### Subárea 4\.5:.*?(?=## 📊|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO LANÇAMENTOS E MOVIMENTAÇÕES:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO FINANCEIRO E BANCÁRIO:
- Controllers principais: `DreController`, `FluxoCaixaController`, `OrcamentoController`, `ConciliacaoController`, `ContaBancariaController`, `LancamentoController`, `MovimentacaoController`.
- TABELAS E COLUNAS REAIS (USE EXATAMENTE ESTAS):
  1. `contas_bancarias` (id, nome, tipo, saldo_inicial).
     * Coluna de identificação da conta é `nome` (ex: 'Inter', 'Mercado Pago', 'Caixinha'). NUNCA use 'instituicao'!
     * ATENÇÃO CRÍTICA: NÃO existe a coluna 'saldo_atual' nessa tabela!
  2. `movimentacoes` (id, lancamento_id, conta_bancaria_id, data_pagamento, valor_pago, transacao_extrato_id).
     * A coluna de valor é `valor_pago` (NUNCA use 'valor'!).
     * NÃO existe coluna 'tipo' em movimentacoes! Para saber se é entrada (receita) ou saída (despesa), faça SEMPRE `LEFT JOIN lancamentos l ON l.id = movimentacoes.lancamento_id`.
  3. `lancamentos` (id, tipo ['receita','despesa'], status ['pendente','pago_parcial','pago','cancelado'], pessoa_id, classificacao_financeira_id, data_emissao, data_vencimento, valor_total, descricao).
  4. `transacoes_extrato` (id, fitid, data, descricao, valor, tipo ['entrada','saida'], status ['pendente','conciliado','ignorado'], origem ['inter','mercadopago'], conta_bancaria_id).
     * Transações com `status = 'pendente'` são as que estão no extrato do banco mas ainda NÃO foram conciliadas no sistema.
- FÓRMULA DO SALDO REAL DE CADA CONTA BANCÁRIA:
  SELECT cb.id, cb.nome, (cb.saldo_inicial + COALESCE(SUM(CASE WHEN l.tipo = 'receita' THEN m.valor_pago ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN l.tipo = 'despesa' THEN m.valor_pago ELSE 0 END), 0)) AS saldo_real FROM contas_bancarias cb LEFT JOIN movimentacoes m ON m.conta_bancaria_id = cb.id LEFT JOIN lancamentos l ON l.id = m.lancamento_id WHERE cb.id != 3 GROUP BY cb.id, cb.nome, cb.saldo_inicial;
- FÓRMULA DE TRANSAÇÕES PENDENTES NO EXTRATO:
  SELECT id, data, origem, tipo, valor, descricao FROM transacoes_extrato WHERE status = 'pendente' ORDER BY data DESC LIMIT 10;"];
                        case "clube":
                        case "assinatura":
                        case "assinaturas":
                        case "mensalidade":
                        case "mensalidades":
                        case "desafio":
                        case "desafios":
                        case "grupos":
                        case "grupo":
                        case "pontos":
                        case "pontuacoes":
                            $zoomFile = base_path('docs/areas/05_CLUBE_MANIA.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['mensalidade', 'mensalidades'])) {
                                    if (preg_match('/### Subárea 5\.2:.*?(?=### Subárea 5\.3|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CLUBE MENSALIDADES:\n" . trim($m[0]) . "\n\nATENÇÃO: As colunas são competencia_ano (ex: 2026) e competencia_mes (1..12). NÃO existe mes_referencia!"];
                                    }
                                } elseif (in_array($modulo, ['desafio', 'desafios'])) {
                                    if (preg_match('/### Subárea 5\.4:.*?(?=### Subárea 5\.5|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CLUBE DESAFIOS:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['grupos', 'grupo'])) {
                                    if (preg_match('/### Subárea 5\.5:.*?(?=## 📊|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CLUBE GRUPOS / TRIBOS:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['pontos', 'pontuacoes'])) {
                                    if (preg_match('/### Subárea 5\.3:.*?(?=### Subárea 5\.4|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CLUBE PONTUAÇÃO:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO CLUBE MANIA:
- Controller principal: `ClubeDashboardController`, `ClubeMensalidadesController`.
- Tabelas principais:
  1. `clube_assinaturas` (id, user_id, status ['ativa','cancelada','suspensa']).
     * Assinantes ativos: `WHERE status = 'ativa'`.
  2. `clube_mensalidades` (id, user_id, assinatura_id, competencia_ano, competencia_mes, status_pagamento ['pago','pendente'], valor, pago_em).
     * ATENÇÃO CRÍTICA: O mês e ano de referência são `competencia_mes` (1..12) e `competencia_ano` (2026). NÃO use 'mes_referencia'!
  3. `pontuacoes_clientes` (user_id, mes_ano ['YYYY-MM'], total, pontos_mensalidade, pontos_itens, pontos_desafios).
  4. `desafios` (id, nome, descricao, pontos, status) e `pontos_desafio`.
  5. `grupos` (id, nome, lider_id) e `grupo_membros` (grupo_id, user_id)."];
                        case "comercial":
                        case "lives":
                        case "live":
                        case "sacolinhas":
                        case "sacolinha":
                        case "vendas":
                        case "pedidos":
                        case "pedido":
                        case "vencimentos":
                        case "vencimento":
                        case "avaliacao":
                        case "avaliacoes":
                        case "desapego":
                        case "desapegos":
                            $zoomFile = base_path('docs/areas/01_COMERCIAL_SACOLINHAS.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['avaliacao', 'avaliacoes', 'desapego', 'desapegos'])) {
                                    if (preg_match('/### Subárea 1\.5:.*?(?=## 🚫|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO AVALIAÇÃO DE DESAPEGOS:\n" . trim($m[0]) . "\n\nANTI-ALUCINAÇÃO:\n- Tabela: avaliacao_items (com 'items' em inglês)\n- Colunas reais de valor: total_venda e total_payout (NÃO use valor_total_aprovado)"];
                                    }
                                } elseif (in_array($modulo, ['pedidos', 'pedido'])) {
                                    if (preg_match('/### Subárea 1\.4:.*?(?=### Subárea 1\.5|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO PEDIDOS:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['lives', 'live'])) {
                                    if (preg_match('/### Subárea 1\.1:.*?(?=### Subárea 1\.2|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO LIVES:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['sacolinhas', 'sacolinha', 'vencimentos', 'vencimento'])) {
                                    if (preg_match('/### Subárea 1\.2:.*?(?=### Subárea 1\.4|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO SACOLINHAS E VENCIMENTOS:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO COMERCIAL, LIVES, SACOLINHAS E DESAPEGOS:
- Controllers principais: `LiveController`, `SacolinhaController`, `AdminSacolinhaController`, `SacolinhaVencidaController`, `PedidoController`, `AvaliacaoController`.
- Tabelas principais:
  1. `lives` (id, data, tipo_live, plataformas, ativo, encerrada_em).
  2. `sacolinhas` (id, user_id, item_id, live_id, quantity, price, status, add_at, obs).
     * Sacolinha Aberta: `status != 'pedido' AND (obs IS NULL OR LOWER(obs) NOT LIKE '%ped-%')`.
     * Total Sacolinhas Abertas: `COUNT(DISTINCT user_id)`.
     * Vencimento: Mais de 31 dias corridos (`DATE_ADD(add_at, INTERVAL 31 DAY) <= CURDATE()`).
  3. `pedidos` (id, numero_pedido, user_id, status_pedido, status_pagamento, valor_total, valor_frete, forma_pagamento).
     * IMPORTANTE: Não existe tabela 'pedido_items'. As peças continuam em `sacolinhas` com `obs = 'ped-{id}'` e `status = 'pedido'`.
  4. `avaliacoes` (id, user_id, tipo_compra, total_venda, total_payout, pagamento_escolhido, status).
  5. `avaliacao_items` (id, avaliacao_id, nome, preco_venda, payout_credito, payout_dinheiro, status).
     * ATENÇÃO: Nome da tabela é `avaliacao_items` (com 'items' em inglês). Valores são `total_venda` e `total_payout`."];
                        case "estoque":
                        case "produtos":
                        case "produto":
                        case "itens":
                        case "item":
                        case "inventario":
                        case "conferencia":
                        case "conferencias":
                        case "categorias":
                        case "categoria":
                        case "marcas":
                        case "marca":
                        case "araras":
                        case "localizacao":
                            $zoomFile = base_path('docs/areas/02_PRODUTOS_ESTOQUE.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['inventario', 'conferencia', 'conferencias', 'araras', 'localizacao'])) {
                                    if (preg_match('/### Subárea 2\.4:.*?(?=### Subárea 2\.5|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO INVENTÁRIO & CONFERÊNCIAS:\n" . trim($m[0]) . "\n\nFÓRMULA:\nSELECT id, localizacao, total_esperado, total_lido, total_faltantes, acuracia_percentual FROM conferencias_inventario ORDER BY created_at DESC LIMIT 5;"];
                                    }
                                } elseif (in_array($modulo, ['marcas', 'marca'])) {
                                    if (preg_match('/### Subárea 2\.3:.*?(?=### Subárea 2\.4|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO MARCAS:\n" . trim($m[0]) . "\n\nREGRA: Na tabela `items`, a marca é uma string na coluna `marca` (ex: 'Farm', 'Zara')."];
                                    }
                                } elseif (in_array($modulo, ['categorias', 'categoria'])) {
                                    if (preg_match('/### Subárea 2\.2:.*?(?=### Subárea 2\.3|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CATEGORIAS:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO ESTOQUE & PRODUTOS:
- Controller principal: `ItemController` (`app/Http/Controllers/ItemController.php`).
- Tabela principal: `items` (id, codigo, nome_do_produto, preco, custo, status, localizacao, marca, cor, tamanho, estado, brecho_id).
- REGRAS CRÍTICAS:
  1. A tabela chama-se `items` (NÃO existe tabela 'produtos').
  2. A coluna de nome chama-se `nome_do_produto` (NÃO 'nome' nem 'titulo'). O código da peça é `codigo` (NÃO 'sku').
  3. Peças disponíveis para venda: `WHERE status = 'disponivel'` (peças com status 'em_sacolinha' ou 'vendido' NÃO estão disponíveis!).
  4. Auditorias físicas de araras/caixas: tabela `conferencias_inventario` (colunas: `localizacao`, `total_esperado`, `total_lido`, `total_faltantes`, `acuracia_percentual`).
  5. Marcas: coluna `marca` em `items` ou tabela `marcas` (id, nome, porcentagem_valor)."];
                        case "clientes":
                        case "cliente":
                        case "enderecos":
                        case "endereco":
                        case "carteira":
                        case "conta_corrente":
                        case "contacorrente":
                        case "whatsapp":
                        case "chat":
                        case "limites":
                        case "limite":
                            $zoomFile = base_path('docs/areas/03_CLIENTES_ATENDIMENTO.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['carteira', 'conta_corrente', 'contacorrente'])) {
                                    if (preg_match('/### Subárea 3\.2:.*?(?=### Subárea 3\.3|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CARTEIRA & CONTA-CORRENTE:\n" . trim($m[0]) . "\n\nREGRA: NUNCA faça SUM(saldo_atual). O saldo atual do cliente é o último registro (ORDER BY id DESC LIMIT 1). Para o saldo consolidado do painel, chame resumo_carteira_clientes!"];
                                    }
                                } elseif (in_array($modulo, ['limites', 'limite'])) {
                                    if (preg_match('/### Subárea 3\.3:.*?(?=### Subárea 3\.4|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO LIMITES DE SACOLINHA:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['whatsapp', 'chat'])) {
                                    if (preg_match('/### Subárea 3\.4:.*?(?=## 📊|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO CHAT & WHATSAPP:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO CLIENTES, ATENDIMENTO E CARTEIRA:
- Controller principal: `ClienteController` (`app/Http/Controllers/ClienteController.php`), `ContaCorrenteController`.
- Tabela principal: `users` (id, name, email, role, phone, whatsapp, instagram, tiktok, apelido, cidade, estado, bairro, endereco, numero_endereco, complemento, cep, cpf).
- REGRAS CRÍTICAS:
  1. A tabela chama-se `users` com `WHERE role = 'client'` (NÃO existe tabela 'clientes' nem 'enderecos').
  2. Endereço, cidade, estado e CEP ficam DIRETAMENTE nas colunas da tabela `users`.
  3. Carteira do Cliente (`conta_corrente`): é um extrato histórico. O saldo atual do cliente é SEMPRE a linha mais recente (`ORDER BY data_movimentacao DESC, id DESC LIMIT 1`). NUNCA faça `SUM(saldo_atual)` em `conta_corrente`!
  4. Limite de sacolinha: tabela `cliente_limites` (`limite_credito`, `limite_utilizado`, `limite_disponivel`).
  5. Contato e identificação: Para responder ao usuário sobre uma sacolinha ou pedido, SEMPRE use o `name` do cliente (JOIN com `users`), nunca o ID numérico isolado."];
                        case "relatorios":
                        case "relatorio":
                        case "auditoria":
                        case "portal":
                        case "portal_acessos":
                        case "rastreamento":
                        case "rastreamentos":
                            $zoomFile = base_path('docs/areas/06_RELATORIOS_AUDITORIA.md');
                            if (file_exists($zoomFile)) {
                                $content = file_get_contents($zoomFile);
                                if (in_array($modulo, ['portal', 'portal_acessos'])) {
                                    if (preg_match('/### Subárea 6\.2:.*?(?=### Subárea 6\.3|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO PORTAL ACESSOS:\n" . trim($m[0])];
                                    }
                                } elseif (in_array($modulo, ['rastreamento', 'rastreamentos'])) {
                                    if (preg_match('/### Subárea 6\.3:.*?(?=## 📊|$)/s', $content, $m)) {
                                        return ["mapa" => "MÓDULO RASTREAMENTO DE PEDIDOS:\n" . trim($m[0])];
                                    }
                                }
                            }
                            return ["mapa" => "MÓDULO RELATÓRIOS E AUDITORIA:
- Controllers principais: `RelatorioVencimentosController`, `PortalAcessosController`.
- Tabelas principais:
  1. `sacolinhas`: Peças retidas acima de 31 dias (`DATE_ADD(add_at, INTERVAL 31 DAY) < NOW()` e `status != 'pedido'`).
  2. `portal_acessos` (id, user_id, ip_address, user_agent, url, route_name, created_at): Histórico de visualizações do portal da sacolinha por cliente.
  3. `pedido_rastreamentos` (id, pedido_id, status, descricao, data_hora): Histórico logístico dos envios de encomendas."];
                        case "governanca":
                        case "brechos":
                        case "brecho":
                        case "equipe":
                        case "permissoes":
                        case "ia":
                        case "latm":
                        case "regras":
                            $zoomFile = base_path('docs/areas/07_GOVERNANCA_IA.md');
                            if (file_exists($zoomFile)) {
                                return ["mapa" => "MÓDULO GOVERNANÇA, EQUIPE & IA:\n" . file_get_contents($zoomFile)];
                            }
                            return ["mapa" => "MÓDULO GOVERNANÇA, EQUIPE & IA:
- Controllers principais: `BrechoController`, `AdminUserController`, `SeverinoController`.
- Tabelas principais:
  1. `brechos` (id, nome, slug, documento, chave_pix, ativo): Gestão multi-tenant de parceiros.
  2. `users` (role in ['admin_master', 'admin', 'brecho_admin', 'client']): Controle de acesso e equipe.
  3. `knowledge_bases` (id, title, category, content, is_active): Memória permanente de longo prazo da IA.
  4. `severino_dynamic_tools` (id, nome, descricao, modulo_area, parametros, sql_template, ativo): Catálogo de ferramentas autônomas LATM criadas pela IA."];
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
                            return ["erro" => "Módulo não reconhecido. Módulos válidos: comercial (lives, sacolinhas, desapegos, pedidos), estoque (produtos, marcas, categorias, inventario), clientes (carteira, limites, whatsapp), financeiro (dre, orcamento, conciliacao, bancos), clube (assinaturas, mensalidades, desafios, grupos), relatorios (vencimentos, portal), governanca (brechos, equipe, ia)."];
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

                case "criar_ferramenta_dinamica":
                    $nome = trim(preg_replace('/[^a-z0-9_]/', '_', strtolower($args["nome"] ?? "")));
                    $descricao = trim($args["descricao"] ?? "");
                    $modulo = trim($args["modulo_area"] ?? "geral");
                    $sql = trim($args["sql_template"] ?? "");
                    $exemplo = trim($args["exemplo_pergunta"] ?? "");
                    $paramsJson = $args["parametros_json"] ?? "{}";
                    $params = is_array($paramsJson) ? $paramsJson : (json_decode($paramsJson, true) ?? []);

                    if (empty($nome) || empty($descricao) || empty($sql)) {
                        return ["erro" => "Nome, descrição e sql_template são obrigatórios para criar uma ferramenta."];
                    }

                    // Segurança: apenas SELECT
                    if (!preg_match("/^\s*SELECT/i", $sql) || preg_match("/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|GRANT|EXEC)\b/i", $sql)) {
                        return ["erro" => "Apenas queries SELECT seguras de leitura são permitidas para criação de ferramentas."];
                    }

                    // Auto-teste (Dry Run) com validação antes de salvar
                    try {
                        $testSql = preg_replace('/LIMIT\s+\d+/i', '', $sql);
                        $testSql = "SELECT * FROM (" . rtrim($testSql, ';') . ") AS __dry_run LIMIT 1";
                        $testBinds = [];
                        if (preg_match_all('/:([a-zA-Z0-9_]+)/', $testSql, $matches)) {
                            foreach ($matches[1] as $pName) {
                                $pType = strtolower($params[$pName]['type'] ?? 'string');
                                if ($pType === 'integer' || $pType === 'int') {
                                    $testBinds[$pName] = 10;
                                } elseif ($pType === 'date') {
                                    $testBinds[$pName] = date('Y-m-d');
                                } else {
                                    $testBinds[$pName] = 'teste';
                                }
                            }
                        }
                        DB::select($testSql, $testBinds);
                    } catch (\Exception $e) {
                        return [
                            "erro" => "A query SQL falhou no teste de validação e NÃO foi salva: " . $e->getMessage() . ". Por favor, corrija os nomes das colunas/tabelas e tente novamente."
                        ];
                    }

                    \App\Models\SeverinoDynamicTool::updateOrCreate(
                        ["nome" => $nome],
                        [
                            "descricao" => $descricao,
                            "modulo_area" => $modulo,
                            "parametros" => $params,
                            "sql_template" => $sql,
                            "created_by" => "severino_latm",
                            "ativo" => true,
                            "exemplos_uso" => $exemplo
                        ]
                    );

                    return [
                        "sucesso" => true,
                        "mensagem" => "Ferramenta dinâmica '{$nome}' foi testada, validada e salva com sucesso no banco de dados! Ela já faz parte do seu catálogo oficial permanente."
                    ];

                default:
                    // Verifica se é uma ferramenta dinâmica criada pelo próprio Severino
                    if (\Illuminate\Support\Facades\Schema::hasTable('severino_dynamic_tools')) {
                        $dyn = \App\Models\SeverinoDynamicTool::where('nome', $name)->where('ativo', true)->first();
                        if ($dyn) {
                            $dynSql = $dyn->sql_template;
                            $dynBinds = [];
                            if (preg_match_all('/:([a-zA-Z0-9_]+)/', $dynSql, $matches)) {
                                foreach ($matches[1] as $paramName) {
                                    if (isset($args[$paramName])) {
                                        $dynBinds[$paramName] = $args[$paramName];
                                    } else {
                                        $default = $dyn->parametros[$paramName]['default'] ?? 10;
                                        $dynBinds[$paramName] = $default;
                                    }
                                }
                            }
                            $results = DB::select($dynSql, $dynBinds);
                            return [
                                "ferramenta_dinamica" => $name,
                                "total_registros" => count($results),
                                "dados" => $results
                            ];
                        }
                    }
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
                "Authorization" => "Bearer " . ($this->apiKey ?: env("GEMINI_API_KEY", "")),
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
                    "Authorization" => "Bearer " . ($this->apiKey ?: env("GEMINI_API_KEY", "")),
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