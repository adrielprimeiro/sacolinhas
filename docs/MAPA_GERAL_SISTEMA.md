# MAPA GERAL DO SISTEMA — CONTROLE SACOLINHAS (MINHA MANIA)
> **Visão Macro de Arquitetura e Negócios para a Base de Conhecimento do Severino AI**  
> *Data de Criação: 20/09/2026*

---

## 🧭 1. O Modelo de Negócio em uma Frase
A Minha Mania opera um modelo de **Social Commerce e Brechó Circular**: os produtos são captados (avaliação de desapegos) ou comprados, cadastrados no estoque, vendidos em transmissões ao vivo (**Lives**), acumulados pelas clientes em **Sacolinhas** temporárias por até 31 dias, convertidos em **Pedidos**, faturados e conciliados nas contas bancárias (**Financeiro**), com um programa de fidelidade e recorrência (**Clube Mania**).

---

## 🗺️ 2. O Mapa das Grandes Áreas e Subáreas

```
                                  [ SISTEMA MINHA MANIA ]
                                             │
      ┌──────────────────┬───────────────────┼───────────────────┬──────────────────┐
      ▼                  ▼                   ▼                   ▼                  ▼
1. COMERCIAL       2. PRODUTOS         3. CLIENTES         4. FINANCEIRO       5. CLUBE &
 & CAPTAÇÃO         & ESTOQUE          & ATENDIMENTO                            FIDELIDADE
      │                  │                   │                   │                  │
      ├─ Lives           ├─ Produtos         ├─ Clientes         ├─ Análises (DRE,  ├─ Painel Clube
      ├─ Sacolas Live    ├─ Categorias       ├─ Chat / WhatsApp     Fluxo, Orçam.)  ├─ Desafios
      ├─ Sacolas Cliente ├─ Marcas           └─ Carteira         ├─ Operações (Conc.,└─ Grupos
      ├─ Vencimentos     ├─ Inventário          (Conta-Corrente)    Lançam., Mov.)
      ├─ Bipar QR Code   ├─ Fotos / Lotes                        └─ Cadastros
      ├─ Pedidos         └─ Conferências
      └─ Avaliações
```

---

### 🛍️ ÁREA 1: COMERCIAL & CAPTAÇÃO (O Motor de Vendas)
> 🔍 **Detalhamento Completo:** [`docs/areas/01_COMERCIAL_SACOLINHAS.md`](areas/01_COMERCIAL_SACOLINHAS.md) *(regras de 31 dias, faturamento de live, pedidos e schemas reais)*

É onde as vendas acontecem, as peças são disputadas e as sacolinhas são criadas e controladas.

1. **Live (`bags.index`):**
   * Interface de operação durante a transmissão (TikTok/Instagram).
   * Captura automática dos comentários/compras e alocação imediata da peça para a cliente vencedora.
2. **Sacolas da Live (`admin.sacolinhas.index`):**
   * Visão de fechamento da transmissão. Quantas peças foram vendidas, faturamento bruto da live e separação física por cliente.
3. **Sacolas por Cliente (`admin.sacolinha.gestao`):**
   * A "sacolinha aberta". Permite ver todas as peças que uma cliente acumulou ao longo de várias lives antes de pedir o envio.
4. **Sacolas Vencidas (`admin.vencimentos`):**
   * **Regra Crítica do Negócio:** Peças com mais de **31 dias** paradas na sacolinha sem fechar pedido. Gera alertas de cobrança para liberar o estoque ou obrigar o envio.
5. **Bipar Sacolinha (`admin.sacolinhas.qrcode.scanner`):**
   * Logística interna física: bipar códigos QR/etiquetas para conferir peças dentro de cada sacola.
6. **Pedidos (`admin.pedido.index`):**
   * Quando a sacolinha é fechada (ou compra direta), gera um Pedido oficial com frete (Melhor Envio), cálculo de pagamento e baixa de estoque.
7. **Avaliação de Desapegos (`admin.avaliacoes.index`):**
   * Captação de peças de clientes: a cliente traz roupas para vender; o brechó avalia e gera crédito na carteira ou pagamento em dinheiro.

---

### 📦 ÁREA 2: PRODUTOS & ESTOQUE (O Acervo Físico e Virtual)
> 🔍 **Detalhamento Completo:** [`docs/areas/02_PRODUTOS_ESTOQUE.md`](areas/02_PRODUTOS_ESTOQUE.md) *(status da peça, tabela items, conferências de inventário e formulas)*

Controla o ciclo de vida da peça desde a entrada até a entrega final.

1. **Lista de Produtos (`items.index`):**
   * Cadastro completo de cada peça: código único, descrição, preço de venda, tamanho, cor, fornecedor e status (`disponivel`, `sacolinha`, `vendido`, `devolvido`).
2. **Categorias & Marcas (`admin.categorias.index`, `admin.marcas.index`):**
   * Taxonomia de moda (ex: Vestidos, Casacos, Jeans; Zara, Farm, Animale).
3. **Inventário Físico & Conferências (`inventario`, `inventario.conferencias.index`):**
   * Bipagem em massa nas araras e caixas para auditar se o que está no armazém bate 100% com o sistema.
4. **Vínculo de Fotos e Lotes (`image-groups.index`, `upload.batch.form`):**
   * Tratamento de imagens (Gemini IA / batches) e associação automática de fotos aos itens cadastrados.

---

### 👥 ÁREA 3: CLIENTES & ATENDIMENTO (O Relacionamento)
> 🔍 **Detalhamento Completo:** [`docs/areas/03_CLIENTES_ATENDIMENTO.md`](areas/03_CLIENTES_ATENDIMENTO.md) *(tabela users, conta_corrente, carteira de clientes, limites e whatsapp)*

Toda a inteligência e suporte focado no cliente final.

1. **Lista de Clientes (`clientes.index`):**
   * Base de usuárias com dados de entrega (endereço, CEP), contatos (WhatsApp, e-mail) e identificadores sociais (Instagram, TikTok).
2. **Chat ao Vivo & WhatsApp Dashboard (`admin.chat.index`, `admin.whatsapp.dashboard`):**
   * Canal direto de atendimento, avisos de peças na sacolinha, links de pagamento e suporte pós-venda via Twilio/Z-API.
3. **Carteira do Cliente / Conta-Corrente (`admin.conta_corrente.index`):**
   * **Regra Financeira do Cliente:** Registra créditos (gerados por desapegos, devoluções ou estornos) e débitos.
   * Calcula o **Limite de Compra Disponível** que a cliente tem para reservar peças nas lives.

---

### 💰 ÁREA 4: FINANCEIRO (A Tesouraria e Controladoria)
> 🔍 **Detalhamento Completo:** [`docs/areas/04_FINANCEIRO_CONCILIACAO.md`](areas/04_FINANCEIRO_CONCILIACAO.md) *(DRE, Orçamento, Conciliação Inter/MP, saldo real e lançamentos)*

O coração monetário da empresa, operando em regime de caixa e competência.

#### A. Análises e Inteligência Estratégica:
1. **Dashboard Financeiro (`financeiro.dashboard`):** Visão geral de receitas, despesas, saldo atual e projeções do mês.
2. **Fluxo de Caixa (`financeiro.fluxodecaixa`):** Entradas e saídas diárias reais.
3. **DRE Contábil (`financeiro.dre`):** Demonstrativo de Resultado (Receita Bruta - Custos - Despesas Fixas/Variáveis = Lucro Líquido).
4. **Relatório Gerencial (`financeiro.relatoriogerencial`):** Consolidação analítica por centro de custo e categorias.
5. **Orçamento / Previsto x Realizado (`financeiro.orcamento.index`):** Metas mensais de gastos (ex: Pró-labore, Marketing, Embalagens) confrontadas com o gasto real no mês.

#### B. Operações do Dia a Dia:
1. **Lançamentos (`financeiro.lancamentos.index`):** Contas a pagar e a receber com status (`pendente`, `pago`, `parcial`, `cancelado`).
2. **Conciliação Bancária (`financeiro.conciliacao.index`):** Cruzamento automático entre o extrato bancário importado (Banco Inter via API e Mercado Pago) e os lançamentos do sistema.
3. **Movimentações (`financeiro.movimentacoes.index`):** O histórico real de tudo o que entrou e saiu das contas bancárias.

#### C. Cadastros Financeiros:
1. **Contas Bancárias (`financeiro.contas.index`):** Inter, Mercado Pago, Caixa Físico, etc.
2. **Plano de Contas (`classificacao_financeira.index`):** Estrutura contábil hierárquica (Receitas de Live, Clube, Despesas Administrativas, etc.).
3. **Contatos / Pessoas (`financeiro.pessoas.index`):** Fornecedores, parceiros e prestadores de serviço.

---

### 👑 ÁREA 5: CLUBE & ENGAJAMENTO (Fidelização e Recorrência)
> 🔍 **Detalhamento Completo:** [`docs/areas/05_CLUBE_MANIA.md`](areas/05_CLUBE_MANIA.md) *(clube_assinaturas, clube_mensalidades, pontuações, desafios e grupos)*

Modelo de comunidade e assinatura mensal para compradoras recorrentes.

1. **Painel do Clube (`admin.clube.dashboard`):**
   * Assinantes ativas do Clube Mania, receita mensal recorrente, controle de adimplência (quem pagou e quem atrasou a mensalidade).
2. **Desafios & Gamificação (`admin.clube.desafios.index`):**
   * Campanhas de pontuação (ex: "comprar 3 peças na semana", "indicar amiga") que acumulam pontos para troca de brindes ou descontos.
3. **Grupos (`admin.grupos.index`):**
   * Segmentação de clientes VIPs em grupos do WhatsApp.

---

### 📊 ÁREA 6: RELATÓRIOS & AUDITORIA
> 🔍 **Detalhamento Completo:** [`docs/areas/06_RELATORIOS_AUDITORIA.md`](areas/06_RELATORIOS_AUDITORIA.md) *(sacolinhas vencidas, portal_acessos, rastreamentos de pedidos)*

1. **Relatório de Clientes (`admin.clientes.relatorios`):** Frequência de compra, ticket médio por cliente, clientes inativas.
2. **Acessos ao Portal (`admin.portal-acessos.index`):** Rastreia quando as clientes abriram o link do Portal da Sacolinha para ver suas peças.
3. **Relatório de Vencimentos (`admin.relatorios.vencimentos`):** Monitoramento e cobrança de peças acima de 31 dias.

---

### ⚙️ ÁREA 7: GOVERNANÇA & IA (Administração Master)
> 🔍 **Detalhamento Completo:** [`docs/areas/07_GOVERNANCA_IA.md`](areas/07_GOVERNANCA_IA.md) *(brechós parceiros, controle de equipe, knowledge_bases e severino_dynamic_tools)*

1. **Brechós Parceiros (`admin.brechos.index`):** Gestão de estoque e vendas para modelo de parceiros.
2. **Gestão de Equipe (`admin.equipe.index`):** Usuários internos, vendedoras, operadores de embalagem e permissões.
3. **Severino AI & RAG (`severino.index`, `admin.knowledge-base.index`):** Central de inteligência, base de memória permanente e histórico de auditoria do assistente.
