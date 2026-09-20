# ÁREA 5: CLUBE & FIDELIDADE (CLUBE MANIA)
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Fluxo de Assinatura e Gamificação do Clube Mania
O Clube Mania é o programa de fidelidade recorrente e comunidade do brechó:

```
[ CLIENTE ] ──(Adesão)──> [ ASSINATURA ATIVA ] ──(Mensalidade)──> [ CLUBE MENSALIDADES ]
(users)                   (clube_assinaturas)                     (competencia_ano / mes: 'pago')
                                  │                                              │
         ┌────────────────────────┼────────────────────────┐                     ▼
         ▼                        ▼                        ▼            [ GANHO DE PONTOS ]
  [ PARTICIPANTE ]         [ GRUPOS / TRIBOS ]      [ DESAFIOS ATIVOS ]  (pontuacoes_clientes)
(benefícios, frete)        (grupos, membros)        (metas de compras)           │
                                                                                 ▼
                                                                        [ RANKING DO MÊS ]
                                                                        (prêmios e destaques)
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 5.1: Assinaturas do Clube (`clube_assinaturas`)
* **Controller:** `ClubeDashboardController` (`app/Http/Controllers/Admin/ClubeDashboardController.php`).
* **Tabela Principal:** `clube_assinaturas`
  * `id`: ID numérico da assinatura.
  * `user_id`: Cliente assinante (`users.id`).
  * `status`: `'ativa'`, `'cancelada'`, `'suspensa'`.
  * `inicio_em`: Data de início da adesão (`YYYY-MM-DD`).
  * `fim_em`: Data de cancelamento ou término (nulo se ativa).
* **Fórmula de Assinantes Ativos:**
  ```sql
  SELECT COUNT(*) FROM clube_assinaturas WHERE status = 'ativa';
  ```

---

### Subárea 5.2: Mensalidades Recorrentes (`clube_mensalidades`)
* **Controller:** `ClubeMensalidadesController` (`app/Http/Controllers/Admin/ClubeMensalidadesController.php`).
* **Tabela Principal:** `clube_mensalidades`
  * `id`: ID da mensalidade.
  * `user_id`: Cliente devedor (`users.id`).
  * `assinatura_id`: FK para `clube_assinaturas.id`.
  * `competencia_ano`: Ano de referência (ex: `2026`).
  * `competencia_mes`: Mês de referência de 1 a 12 (ex: `9` para setembro).
  * `status_pagamento`: Enum `'pago'`, `'pendente'`, `'cancelado'`.
  * `valor`: Valor da mensalidade (decimal, ex: `29.90`).
  * `pago_em`: Timestamp em que o pagamento foi registrado.

#### Como saber quem pagou no mês atual?
```sql
SELECT u.id, u.name, cm.valor, cm.pago_em
FROM clube_mensalidades cm
JOIN users u ON u.id = cm.user_id
JOIN clube_assinaturas ca ON ca.user_id = cm.user_id AND ca.status = 'ativa'
WHERE cm.competencia_ano = YEAR(CURDATE()) 
  AND cm.competencia_mes = MONTH(CURDATE())
  AND cm.status_pagamento = 'pago';
```

#### Como saber quem está inadimplente / pendente no mês?
```sql
SELECT u.id, u.name, u.whatsapp
FROM clube_assinaturas ca
JOIN users u ON u.id = ca.user_id
WHERE ca.status = 'ativa'
  AND NOT EXISTS (
      SELECT 1 FROM clube_mensalidades cm 
      WHERE cm.user_id = ca.user_id 
        AND cm.competencia_ano = YEAR(CURDATE()) 
        AND cm.competencia_mes = MONTH(CURDATE()) 
        AND cm.status_pagamento = 'pago'
  );
```

---

### Subárea 5.3: Gamificação & Pontuações (`pontuacoes_clientes`)
* **Tabela Principal:** `pontuacoes_clientes`
  * `id`: ID do registro.
  * `user_id`: Cliente (`users.id`).
  * `mes_ano`: Mês de referência no formato `'YYYY-MM'` (ex: `'2026-09'`).
  * `total`: Pontuação total acumulada no mês.
  * `pontos_mensalidade`: Pontos creditados pelo pagamento da mensalidade em dia.
  * `pontos_itens`: Pontos ganhos por peças compradas nas lives.
  * `pontos_desafios`: Pontos conquistados em desafios especiais.
  * `pontos_bonus_grupo`: Bônus por desempenho do seu grupo/tribo.
  * `pontos_retirados`: Penalidades ou resgates.

---

### Subárea 5.4: Desafios do Clube (`desafios` e `pontos_desafio`)
* **Controller:** `DesafiosController` (`app/Http/Controllers/Admin/DesafiosController.php`).
* **Tabela `desafios`:**
  * `id`: ID do desafio.
  * `nome`: Nome do desafio (ex: *"Compre 3 peças na Live de Terça"*).
  * `descricao`: Regras para conquistar a pontuação.
  * `pontos`: Quantidade de pontos concedidos (inteiro).
  * `inicio_em` / `fim_em`: Vigência do desafio.
  * `status`: `'ativo'` ou `'inativo'`.
* **Tabela `pontos_desafio`:**
  * `desafio_id`: FK para `desafios.id`.
  * `user_id`: Cliente contemplada.
  * `pontos`: Pontos registrados.

---

### Subárea 5.5: Grupos de Clientes / Tribos (`grupos` e `grupo_membros`)
* **Controller:** `GruposController` (`app/Http/Controllers/Admin/GruposController.php`).
* **Tabela `grupos`:**
  * `id`: ID do grupo.
  * `nome`: Nome da tribo (ex: *"Loucas por Farm"*, *"Garimpeiras Vip"*).
  * `lider_id`: Cliente líder do grupo (`users.id`).
* **Tabela `grupo_membros`:**
  * `grupo_id`: FK para `grupos.id`.
  * `user_id`: FK para `users.id`.
* **Tabela `pontuacoes_grupos`:**
  * `grupo_id`: Grupo.
  * `mes_ano`: Período `'YYYY-MM'`.
  * `total`: Soma total dos pontos de todos os membros da tribo no mês.

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Resumo do Clube no Mês Atual
```sql
SELECT 
    (SELECT COUNT(*) FROM clube_assinaturas WHERE status = 'ativa') AS total_assinantes_ativos,
    (SELECT COUNT(*) 
     FROM clube_mensalidades cm 
     JOIN clube_assinaturas ca ON ca.user_id = cm.user_id AND ca.status = 'ativa'
     WHERE cm.competencia_ano = YEAR(CURDATE()) 
       AND cm.competencia_mes = MONTH(CURDATE()) 
       AND cm.status_pagamento = 'pago') AS mensalidades_pagas_mes,
    (SELECT COALESCE(SUM(total), 0) 
     FROM pontuacoes_clientes 
     WHERE mes_ano = DATE_FORMAT(NOW(), '%Y-%m')) AS total_pontos_distribuidos;
```

### 2. Ranking Top 10 Clientes com Mais Pontos no Mês
```sql
SELECT u.name, pc.total AS pontos_total, pc.pontos_itens, pc.pontos_desafios, g.nome AS grupo
FROM pontuacoes_clientes pc
JOIN users u ON u.id = pc.user_id
LEFT JOIN grupo_membros gm ON gm.user_id = u.id
LEFT JOIN grupos g ON g.id = gm.grupo_id
WHERE pc.mes_ano = DATE_FORMAT(NOW(), '%Y-%m')
ORDER BY pc.total DESC 
LIMIT 10;
```

### 3. Ranking de Grupos / Tribos no Mês
```sql
SELECT g.nome, COUNT(DISTINCT gm.user_id) AS total_membros, COALESCE(pg.total, 0) AS total_pontos
FROM grupos g
LEFT JOIN grupo_membros gm ON gm.grupo_id = g.id
LEFT JOIN pontuacoes_grupos pg ON pg.grupo_id = g.id AND pg.mes_ano = DATE_FORMAT(NOW(), '%Y-%m')
GROUP BY g.id, g.nome, pg.total
ORDER BY total_pontos DESC;
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Competência da Mensalidade:** A competência da mensalidade é separada em duas colunas numéricas: `competencia_ano` (ex: 2026) e `competencia_mes` (ex: 9). NÃO existe coluna `mes_referencia`!
2. ❌ **Assinantes Ativos:** Só conte quem tem `status = 'ativa'` na tabela `clube_assinaturas`. Clientes inativos ou que cancelaram não devem ser contados como assinantes.
3. ❌ **Pontuação no Mês:** A coluna de filtro na tabela `pontuacoes_clientes` chama-se `mes_ano` e usa formato textual `'YYYY-MM'` (ex: `'2026-09'`).
4. ❌ **Ferramenta Nativa:** Para responder "quem pagou a mensalidade do clube?", você pode usar diretamente a ferramenta dedicada `status_clube_mensalidades`.
