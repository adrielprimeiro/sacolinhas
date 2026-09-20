# ÁREA 1: COMERCIAL & CAPTAÇÃO (O MOTOR DE VENDAS)
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Fluxo Comercial na Prática
O ciclo comercial da Minha Mania segue esta esteira contínua:
```
[ LIVE ] ──(Reserva de Peça)──> [ SACOLINHA ABERTA ] ──(Até 31 dias)──> [ PEDIDO FECHADO ]
(TikTok/Insta)                  (Acúmulo de Peças)                        (Frete + Pagto)
      │                                │                                        │
      │                                ├── Se > 31 dias ➔ [ SACOLA VENCIDA ]    │
      │                                │   (Cobrança WhatsApp / Devolução)      │
      │                                                                         │
[ DESAPEGO CLIENTE ] ──(Avaliação)──> [ CRÉDITO NA CARTEIRA ] ──────────────────┘
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 1.1: Lives & Transmissões
* **Controllers:** `LiveController`, `LiveMovimentacaoController`.
* **Tabela Principal:** `lives`
  * `id`: Identificador numérico da live.
  * `data`: Data da realização (`YYYY-MM-DD`).
  * `tipo_live`: Enum `'loja-aberta'`, `'leilao'`, `'precinho'`.
  * `plataformas`: Redes de transmissão (ex: TikTok, Instagram).
  * `ativo`: 1 se a live estiver transmitindo agora, 0 se encerrada.
  * `encerrada_em`: Timestamp do fechamento.
* **Fórmulas e Métricas de Live:**
  * **Faturamento Bruto da Live:** `SUM(s.price * s.quantity)` na tabela `sacolinhas` onde `s.live_id = lives.id`.
  * **Peças Vendidas na Live:** `COUNT(s.id)` ou `SUM(s.quantity)` na tabela `sacolinhas` com `s.live_id = lives.id`.
  * **Clientes da Live:** `COUNT(DISTINCT s.user_id)` na tabela `sacolinhas` com `s.live_id = lives.id`.

---

### Subárea 1.2: Sacolinhas Abertas (O Carrinho Acumulado)
* **Controllers:** `SacolinhaController`, `AdminSacolinhaController`.
* **Tabela Principal:** `sacolinhas` (Atenção: o nome é `sacolinhas`, no diminutivo e plural).
  * `id`: ID da linha/peça na sacola.
  * `user_id`: ID da cliente dona da sacola (chave estrangeira para `users.id`).
  * `item_id`: ID da peça física no estoque (chave estrangeira para `items.id`).
  * `live_id`: ID da live onde a peça foi comprada.
  * `quantity`: Quantidade (padrão: 1).
  * `price`: Preço unitário da peça vendida naquela live.
  * `add_at`: Timestamp exato de quando a peça entrou na sacola da cliente.
  * `status`: Status do item.
  * `obs`: Observações internas (quando vira pedido, recebe a tag `'ped-{pedido_id}'`).

#### Regras Fundamentais de Sacolinha Aberta:
1. **O que é Sacolinha Aberta/Ativa:** Peças que ainda estão guardadas com a cliente e **NÃO** viraram pedido:
   ```sql
   WHERE s.status != 'pedido' AND (s.obs IS NULL OR LOWER(s.obs) NOT LIKE '%ped-%')
   ```
2. **Definição de "Uma Sacola":** Cada cliente único (`user_id`) com itens abertos representa **uma sacola**.
   * *Total de Sacolinhas Abertas:* `COUNT(DISTINCT s.user_id)`.
   * *Total de Peças nas Sacolinhas:* `COUNT(s.id)` ou `SUM(s.quantity)`.
   * *Valor Total nas Sacolinhas:* `SUM(s.price * s.quantity)`.

---

### Subárea 1.3: Sacolas Vencidas (Prazo Limite de 31 Dias)
* **Controller:** `SacolinhaVencidaController`, `RelatorioVencimentosController`.
* **Regra de Ouro dos 31 Dias:**
  Uma peça está vencida quando seu tempo na sacola ultrapassa 31 dias corridos:
  ```sql
  WHERE DATE(DATE_ADD(s.add_at, INTERVAL 31 DAY)) <= CURDATE()
  ```
* **Classificação do Cliente:**
  * **Cliente Vencido:** Cliente que possui **pelo menos uma peça** com mais de 31 dias na sacola aberta.
  * **Cliente em Dia:** Cliente que tem sacola aberta mas **nenhuma** peça passou dos 31 dias.
* **Operação de Cobrança:**
  O sistema gera PDFs e envia avisos automatizados via WhatsApp (`SendWhatsAppVencidosTemplate`) para que a cliente feche o pedido ou devolva as peças para a arara.

---

### Subárea 1.4: Pedidos (Conversão e Fechamento)
* **Controllers:** `PedidoController`, `AdminPedidoController`.
* **Tabela Principal:** `pedidos`
  * `id`: ID do pedido.
  * `numero_pedido`: Código único visível (ex: `'PED-12345'`).
  * `user_id`: Cliente compradora (`users.id`).
  * `status_pedido`: Enum `'pendente'`, `'confirmado'`, `'embalado'`, `'enviado'`, `'entregue'`, `'pago'`, `'concluido'`, `'cancelado'`.
  * `status_pagamento`: Enum `'pendente'`, `'aprovado'`, `'rejeitado'`, `'estornado'`.
  * `valor_total`: Valor final cobrado da cliente (peças + frete - descontos).
  * `valor_frete`: Valor do frete cobrado.
  * `valor_desconto`: Descontos aplicados (cupons, etc.).
  * `valor_saldo_utilizado`: Valor abatido da carteira de crédito da cliente.
  * `forma_pagamento`: `'pix'`, `'cartao_credito'`, `'cartao_debito'`, `'saldo_carteira'`, etc.
  * `codigo_rastreamento`: Código dos Correios / Jadlog / Melhor Envio.
  * `melhor_envio_id`: ID da etiqueta gerada no Melhor Envio.

#### Onde ficam as peças de um Pedido? (Pegadinha Técnica Comum):
* **Não existe tabela `pedido_items` ou `itens_pedido`!**
* As peças do pedido continuam na tabela `sacolinhas`, mas têm seu `status` alterado para `'pedido'` e a coluna `obs` preenchida com `'ped-{id_do_pedido}'`.
* Para saber quais peças compõem o Pedido #500:
  ```sql
  SELECT * FROM sacolinhas WHERE obs LIKE '%ped-500%' OR (status = 'pedido' AND obs = 'ped-500');
  ```

---

### Subárea 1.5: Avaliação de Desapegos (Captação de Peças)
* **Controller:** `AvaliacaoController` (`app/Http/Controllers/Admin/AvaliacaoController.php`).
* **Tabelas Envolvidas:** `avaliacoes` e `avaliacao_items` (com 'items' no plural em inglês!).

#### Tabela `avaliacoes`:
* `id`: ID da avaliação do lote de roupas trazido pela cliente.
* `user_id`: Cliente dona das peças desapegadas.
* `tipo_compra`: Ex: `'avaliados'`, `'consignado'`.
* `total_venda`: Valor total estimado de venda das peças na loja.
* `total_payout`: **Valor líquido a pagar à cliente** pelas peças aprovadas.
* `pagamento_escolhido`: Forma escolhida pela cliente (Crédito na Carteira com bônus ou Dinheiro/PIX).
* `status`: `'rascunho'`, `'concluida'`, etc.

#### Tabela `avaliacao_items`:
* `id`: ID de cada peça avaliada individualmente.
* `avaliacao_id`: Chave estrangeira para `avaliacoes.id`.
* `nome`: Descrição da peça (ex: "Vestido Floral Farm").
* `preco_venda`: Preço sugerido para venda.
* `payout_credito`: Valor que a cliente recebe se optar por crédito na loja.
* `payout_dinheiro`: Valor que a cliente recebe se optar por PIX/dinheiro (geralmente menor que o crédito).
* `status`: Status da peça individual (aprovada, reprovada, etc.).

---

## 🚫 3. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Nunca busque a tabela `sacolas`:** A tabela correta no MySQL é `sacolinhas`.
2. ❌ **Nunca busque a tabela `pedido_items`:** As peças do pedido estão em `sacolinhas` com `obs LIKE '%ped-%'`.
3. ❌ **Nunca busque a tabela `avaliacao_itens`:** O nome correto da tabela no banco é `avaliacao_items` (com "items").
4. ❌ **Nunca confunda linhas com clientes:** Se houver 300 linhas em `sacolinhas`, NÃO significa que existem 300 clientes. Significa que existem 300 peças. O número de sacolas/clientes é sempre `COUNT(DISTINCT user_id)`.
5. ❌ **Na tabela `avaliacoes`, o valor não é `valor_total_aprovado`:** As colunas reais de valor são `total_venda` e `total_payout`.
