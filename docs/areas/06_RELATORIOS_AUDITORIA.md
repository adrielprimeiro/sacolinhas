# ÁREA 6: RELATÓRIOS & AUDITORIA
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Papel da Auditoria e Métricas no Negócio
A esteira de relatórios fornece inteligência operacional para evitar perdas de estoque, monitorar engajamento e fiscalizar prazos:

```
[ SACAS PARADAS ] ───────> [ RELATÓRIO DE VENCIMENTOS ] ───> [ COBRANÇA AUTOMATIZADA ]
(s.add_at > 31 dias)       (ranking por valor atrasado)      (liberar peças para live)

[ PORTAL DA SACOLA ] ────> [ PORTAL ACESSOS ] ────────────> [ ENGAJAMENTO DA CLIENTE ]
(link da cliente)          (IP, rota, data/hora)             (rastreio de visualizações)

[ PEDIDOS ENVIADOS ] ────> [ PEDIDO RASTREAMENTOS ] ──────> [ MONITORAMENTO LOGÍSTICO ]
(Melhor Envio/Correios)    (status, histórico de trânsito)   (alertas de atraso de entrega)
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 6.1: Relatório de Sacolas Vencidas
* **Controller:** `RelatorioVencimentosController` (`app/Http/Controllers/Admin/RelatorioVencimentosController.php`).
* **Regra Fundamental dos 31 Dias:**
  ```sql
  WHERE s.status != 'pedido' 
    AND (s.obs IS NULL OR LOWER(s.obs) NOT LIKE '%ped-%')
    AND s.add_at IS NOT NULL
    AND DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()
  ```
* **Métricas Globais de Vencimento:**
  * **Clientes com Peças Vencidas:** `COUNT(DISTINCT s.user_id)`
  * **Total de Peças Vencidas:** `SUM(s.quantity)`
  * **Valor Total Retido Vencido:** `SUM(s.quantity * s.price)`

---

### Subárea 6.2: Acessos ao Portal da Sacolinha (`portal_acessos`)
* **Controller:** `PortalAcessosController` (`app/Http/Controllers/Admin/PortalAcessosController.php`).
* **Tabela Principal:** `portal_acessos`
  * `id`: ID do log.
  * `user_id`: Cliente que visualizou a sacolinha (`users.id`).
  * `ip_address`: IP de origem da conexão.
  * `user_agent`: Navegador/dispositivo (celular, desktop).
  * `url`: URL exata acessada.
  * `route_name`: Rota Laravel (ex: `'portal.sacolinha'`).
  * `created_at`: Data e hora do acesso.

---

### Subárea 6.3: Rastreamento Logístico de Pedidos (`pedido_rastreamentos`)
* **Tabela Principal:** `pedido_rastreamentos`
  * `id`: ID do evento.
  * `pedido_id`: FK para `pedidos.id`.
  * `status`: Status da transportadora (ex: `'postado'`, `'em_transito'`, `'saiu_para_entrega'`, `'entregue'`).
  * `descricao`: Detalhe da movimentação dos Correios ou Melhor Envio.
  * `data_hora`: Timestamp do evento informado pela transportadora.

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Resumo Consolidado de Sacolinhas Vencidas
```sql
SELECT 
    COUNT(DISTINCT s.user_id) AS clientes_vencidos,
    COALESCE(SUM(s.quantity), 0) AS total_pecas_vencidas,
    COALESCE(SUM(s.quantity * s.price), 0) AS valor_total_vencido
FROM sacolinhas s
WHERE s.status != 'pedido'
  AND (s.obs IS NULL OR LOWER(s.obs) NOT LIKE '%ped-%')
  AND s.add_at IS NOT NULL
  AND DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW();
```

### 2. Ranking Top 10 Clientes com Maior Valor em Sacolinhas Vencidas
```sql
SELECT u.name, 
       COUNT(s.id) AS total_pecas, 
       SUM(s.quantity * s.price) AS valor_vencido,
       DATEDIFF(NOW(), MIN(s.add_at)) AS dias_da_peca_mais_antiga
FROM sacolinhas s
JOIN users u ON u.id = s.user_id
WHERE s.status != 'pedido'
  AND (s.obs IS NULL OR LOWER(s.obs) NOT LIKE '%ped-%')
  AND s.add_at IS NOT NULL
  AND DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()
GROUP BY u.id, u.name
ORDER BY valor_vencido DESC
LIMIT 10;
```

### 3. Estatísticas de Acessos Recentes ao Portal da Sacolinha
```sql
SELECT COUNT(*) AS total_visualizacoes,
       COUNT(DISTINCT user_id) AS clientes_unicos_ativos
FROM portal_acessos
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY);
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Tabela de Acessos:** O nome no MySQL é `portal_acessos` (no plural).
2. ❌ **Tabela de Rastreamento:** Chama-se `pedido_rastreamentos`.
3. ❌ **Cálculo de Atraso:** Nunca compare apenas com o dia 1 do mês; o prazo de vencimento é individual por peça: `DATE_ADD(s.add_at, INTERVAL 31 DAY) < NOW()`.
