# ÁREA 4: FINANCEIRO & CONCILIAÇÃO (A CONTROLADORIA)
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Fluxo Financeiro na Prática
O sistema financeiro da Minha Mania opera a gestão por **Competência** (DRE), por **Caixa** (Fluxo de Caixa) e **Bancária** (Conciliação):

```
[ EXTRATO BANCÁRIO ] ──(Importação/API)──> [ TRANSAÇÃO PENDENTE ]
(Banco Inter / Mercado Pago)               (transacoes_extrato: 'pendente')
                                                          │
                                         ┌────────────────┴────────────────┐
                                         ▼                                 ▼
                             [ VINCULAR LANÇAMENTO ]            [ NOVO LANÇAMENTO & BAIXA ]
                             (conciliar c/ conta existente)     (cria lancamento + movimentacao)
                                         │                                 │
                                         └────────────────┬────────────────┘
                                                          ▼
                                            [ TRANSAÇÃO CONCILIADA ]
                                            (transacoes_extrato: 'conciliado')
                                                          │
                                                          ▼
                                            [ BAIXA EM MOVIMENTAÇÕES ]
                                            (impacta saldo real da conta)
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 4.1: DRE (Demonstração do Resultado do Exercício)
* **Controller:** `DreController` (`app/Http/Controllers/Financeiro/DreController.php`).
* **Princípio:** Regime de **Competência** (pela data do pedido / data de emissão).
* **Fórmula Oficial do DRE da Minha Mania:**
  1. **Receita de Vendas:** `SUM(pedidos.valor_total)` onde `status_pedido != 'cancelado'` e `data_pedido` no mês.
  2. **Outras Receitas:** `SUM(lancamentos.valor_total)` de receita onde `referencia_tipo != 'pedido'` e sem recargas de carteira (categoria 84).
  3. **Receita Bruta:** `Receita de Vendas + Outras Receitas`.
  4. **Deduções:** `SUM(pedidos.valor_desconto)` + Devoluções de Vendas (`lancamentos` de despesa na categoria 81).
  5. **Receita Líquida:** `Receita Bruta - Deduções`.
  6. **CMV (Custo das Mercadorias Vendidas):** Soma de `items_pedido.quantidade * COALESCE(items.custo, 0)` dos pedidos válidos do mês.
  7. **Lucro Bruto:** `Receita Líquida - CMV`.
  8. **Despesas Operacionais:** Lançamentos de despesa do mês (excluindo categoria 19 [fornecedores/CMV], 81 [devoluções] e transferências), agrupados pelas categorias pai de nível 1 (ex: *2.04 Administrativo, 2.05 Pessoal*).
  9. **Lucro Líquido do Exercício (LLE):** `Lucro Bruto - Total Despesas Operacionais`.

---

### Subárea 4.2: Orçamento (Previsto x Realizado)
* **Controller:** `OrcamentoController` (`app/Http/Controllers/Financeiro/OrcamentoController.php`).
* **Tabelas Envolvidas:** `orcamentos`, `classificacao_financeira`, `lancamentos`.

#### Tabela `orcamentos`:
* `id`: ID do orçamento.
* `classificacao_financeira_id`: Categoria orçada.
* `periodo`: Primeiro dia do mês (`YYYY-MM-01`, ex: `'2026-09-01'`).
* `valor_previsto`: Meta ou teto financeiro orçado para aquele mês.

#### Como é apurado o REALIZADO?
* O valor Realizado **NÃO** é uma coluna estática da tabela `orcamentos`!
* O Realizado é calculado dinamicamente somando `lancamentos`:
  ```sql
  SELECT COALESCE(SUM(
      CASE 
          WHEN l.tipo = cf.tipo_natureza THEN l.valor_total 
          ELSE -l.valor_total 
      END
  ), 0) AS realizado
  FROM lancamentos l
  WHERE l.classificacao_financeira_id = cf.id
    AND l.status = 'pago'
    AND l.data_vencimento BETWEEN :inicio_mes AND :fim_mes;
  ```
* **Atalho do Pró-labore:** Quando perguntado sobre o pró-labore, calcula-se o gasto proporcional até o dia atual do mês contra a meta orçada da categoria *'Pro labore'*.

---

### Subárea 4.3: Conciliação Bancária & Extratos
* **Controller:** `ConciliacaoController` (`app/Http/Controllers/Financeiro/ConciliacaoController.php`).
* **Tabela Principal:** `transacoes_extrato`
  * `id`: ID da transação no extrato.
  * `fitid`: Identificador bancário único fornecido pelo banco (evita duplicidade).
  * `data`: Data da movimentação bancária (`YYYY-MM-DD`).
  * `descricao`: Descrição original do banco (ex: *"PIX RECEBIDO - FULANO"*, *"TARIFA MENSAL"*).
  * `valor`: Valor monetário decimal.
  * `tipo`: `'entrada'` ou `'saida'`.
  * `status`: `'pendente'`, `'conciliado'`, `'ignorado'`.
  * `origem`: Banco de origem (`'inter'`, `'mercadopago'`).
  * `conta_bancaria_id`: FK para `contas_bancarias.id`.
  * `movimentacao_id`: FK para `movimentacoes.id` (quando conciliado).

#### ⚠️ Distinção Conceitual Obrigatória (Transações de Extrato vs Lançamentos):
* **Transações de Extrato (`transacoes_extrato`):** São os registros brutos importados das contas bancárias (Banco Inter / Mercado Pago / OFX). Possuem status:
  * `'pendente'`: Chegou do banco mas ainda NÃO foi vinculada a um lançamento financeiro no sistema.
  * `'conciliado'`: Já foi casada com um lançamento financeiro (criando ou associando a `movimentacoes`).
  * `'ignorado'`: Movimentação ignorada (ex: estorno duplicado ou desconsiderada manualmente).
  * **NUNCA chame transação de extrato de "lançamento"!**
* **Lançamentos Financeiros (`lancamentos`):** São os títulos a pagar ou a receber cadastrados no sistema financeiro.
* **Sincronização vs Conciliação:**
  * **Última Sincronização:** Data/hora em que o sistema buscou novas transações bancárias nas APIs (Inter / Mercado Pago) ou importou arquivo OFX (`Cache::get('last_extrato_auto_synced_at')` ou `MAX(created_at)` em `transacoes_extrato`).
  * **Última Conciliação:** Data/hora em que a última transação foi efetivamente vinculada a um lançamento (`MAX(updated_at)` em `transacoes_extrato` WHERE `status = 'conciliado'`).
* **Transferências entre contas próprias (ex: Inter -> Mercado Pago):** Têm duas pontas (saída no Inter e entrada no Mercado Pago). Ambas precisam ser conciliadas.

---

### Subárea 4.4: Contas Bancárias da Empresa
* **Controller:** `ContaBancariaController`.
* **Tabela Principal:** `contas_bancarias`
  * `id`: ID da conta:
    * `1`: **Caixinha** (dinheiro físico / gaveta).
    * `2`: **Mercado Pago** (recebimentos online, cartões, gateway).
    * `3`: **Carteira Cliente** (conta virtual que reflete os saldos das clientes).
    * `4`: **Banco Inter** (conta corrente jurídica principal).
  * `nome`: Nome da conta.
  * `tipo`: `'corrente'`, `'carteira'`, etc.
  * `saldo_inicial`: Saldo de abertura da conta.

#### ⚠️ PEGADINHA CRÍTICA DE CONTAS BANCÁRIAS:
* **NÃO EXISTE a coluna `saldo_atual` na tabela `contas_bancarias`!**
* O saldo real de uma conta é calculado dinamicamente:
  $$\text{Saldo Real} = \text{saldo\_inicial} + \sum(\text{entradas em movimentacoes}) - \sum(\text{saidas em movimentacoes})$$

---

### Subárea 4.5: Lançamentos & Movimentações
* **Tabela `lancamentos` (O compromisso/título):**
  * `id`: ID do título.
  * `tipo`: `'receita'` ou `'despesa'`.
  * `status`: `'pendente'`, `'pago_parcial'`, `'pago'`, `'cancelado'`.
  * `pessoa_id`: FK para `pessoas.id` (fornecedor, prestador, parceiro).
  * `classificacao_financeira_id`: FK para categoria contábil.
  * `data_emissao` / `data_vencimento`: Datas de controle de prazo.
  * `valor_total`: Valor contratado/devido.
  * `descricao`: Histórico textual do lançamento.

* **Tabela `movimentacoes` (A baixa bancária real):**
  * `id`: ID da baixa de caixa.
  * `lancamento_id`: FK para `lancamentos.id`.
  * `conta_bancaria_id`: FK para `contas_bancarias.id`.
  * `data_pagamento`: Data efetiva em que o dinheiro entrou ou saiu da conta.
  * `valor_pago`: Valor real desembolsado/recebido.
  * `transacao_extrato_id`: FK para `transacoes_extrato.id` quando conciliado.

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Saldo Real das Contas Bancárias (Sem Alucinar `saldo_atual`)
```sql
SELECT cb.id, cb.nome, cb.saldo_inicial,
       COALESCE(SUM(CASE WHEN l.tipo = 'receita' THEN m.valor_pago ELSE 0 END), 0) AS total_entradas,
       COALESCE(SUM(CASE WHEN l.tipo = 'despesa' THEN m.valor_pago ELSE 0 END), 0) AS total_saidas,
       (cb.saldo_inicial 
        + COALESCE(SUM(CASE WHEN l.tipo = 'receita' THEN m.valor_pago ELSE 0 END), 0) 
        - COALESCE(SUM(CASE WHEN l.tipo = 'despesa' THEN m.valor_pago ELSE 0 END), 0)
       ) AS saldo_real
FROM contas_bancarias cb
LEFT JOIN movimentacoes m ON m.conta_bancaria_id = cb.id
LEFT JOIN lancamentos l ON l.id = m.lancamento_id
WHERE cb.id != 3 -- Exclui conta virtual da carteira de clientes
GROUP BY cb.id, cb.nome, cb.saldo_inicial;
```

### 2. Transações Bancárias Pendentes de Conciliação
```sql
SELECT id, data, origem, tipo, valor, descricao 
FROM transacoes_extrato 
WHERE status = 'pendente' 
ORDER BY data DESC 
LIMIT 10;
```

### 3. Contas a Pagar Atrasadas / Vencidas
```sql
SELECT l.id, l.data_vencimento, l.valor_total, l.descricao, p.nome AS fornecedor
FROM lancamentos l
LEFT JOIN pessoas p ON p.id = l.pessoa_id
WHERE l.tipo = 'despesa' 
  AND l.status = 'pendente' 
  AND l.data_vencimento < CURDATE()
ORDER BY l.data_vencimento ASC;
```

### 4. Orçamento Previsto x Realizado do Mês Atual
```sql
SELECT cf.nome, o.valor_previsto,
       COALESCE(SUM(l.valor_total), 0) AS valor_realizado,
       (o.valor_previsto - COALESCE(SUM(l.valor_total), 0)) AS diferenca
FROM orcamentos o
JOIN classificacao_financeira cf ON cf.id = o.classificacao_financeira_id
LEFT JOIN lancamentos l ON l.classificacao_financeira_id = cf.id 
                       AND l.status = 'pago' 
                       AND l.data_vencimento BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
WHERE o.periodo = DATE_FORMAT(NOW(), '%Y-%m-01')
GROUP BY cf.id, cf.nome, o.valor_previsto
ORDER BY o.valor_previsto DESC;
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Nunca busque `saldo_atual` em `contas_bancarias`:** Essa coluna NÃO existe. O saldo real é calculado somando as `movimentacoes` de receita e subtraindo as de despesa ao `saldo_inicial`.
2. ❌ **Lançamento x Movimentação:** Um lançamento pendente NÃO afeta o saldo bancário. O que mexe no saldo da conta bancária são as `movimentacoes` efetivadas.
3. ❌ **Transações de Extrato:** Se um PIX ou transferência ocorreu no banco mas não aparece nos relatórios do sistema, verifique `transacoes_extrato` com `status = 'pendente'`. Ele só vira lançamento oficial quando for conciliado.
4. ❌ **DRE x Fluxo de Caixa:** O DRE mede a competência do mês (pela data do pedido/emissão). O Fluxo de Caixa mede o que realmente entrou ou saiu do banco (pela data de pagamento em `movimentacoes`).
5. ❌ **Pessoas x Usuários:** Fornecedores, parceiros e prestadores de serviço ficam na tabela `pessoas` (`pessoa_id` em `lancamentos`).
6. ❌ **Transação de Extrato NÃO é Lançamento:** A tabela `transacoes_extrato` guarda o que veio do banco. A tabela `lancamentos` guarda os títulos financeiros (contas a pagar e a receber). Se o usuário perguntar "quantos lançamentos?", informe os lançamentos em aberto (`lancamentos WHERE status = 'pendente'`) e diferencie das transações de extrato pendentes (`transacoes_extrato WHERE status = 'pendente'`).
7. ❌ **Sincronização NÃO é Conciliação:** Sincronização é quando novas transações bancárias entraram no sistema (`Cache` ou `MAX(created_at)` em `transacoes_extrato`). Conciliação é quando uma transação foi casada com um lançamento (`MAX(updated_at)` em `transacoes_extrato` com status 'conciliado').

