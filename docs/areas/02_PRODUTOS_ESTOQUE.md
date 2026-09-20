# ÁREA 2: PRODUTOS & ESTOQUE (O ACERVO FÍSICO E VIRTUAL)
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Ciclo de Vida da Peça no Brechó
O produto físico percorre os seguintes estados no sistema:

```
[ ENTRADA / DESAPEGO ] ──(Curadoria & Preço)──> [ ITEM CADASTRADO ] 
(AV-Item aprovado ou Lote)                      (status: 'disponivel')
                                                        │
                    ┌───────────────────────────────────┼──────────────────────────────────┐
                    ▼                                   ▼                                  ▼
             [ NA LOJA ]                         [ NA LIVE ]                        [ NO ESTOQUE ]
         (status: 'loja')                     (status: 'live')                   (status: 'estoque')
         (local: 'Arara X')                   (separada p/ live)                 (local: 'Caixa Y')
                    │                                   │                                  │
                    └───────────────────┬───────────────┴──────────────────────────────────┘
                                        ▼
                            [ VENDIDO / SACOLINHA ]
                         (status: 'em_sacolinha')
                         (local: 'Sacolinha')
                                        │
                                        ▼
                                [ PEDIDO FECHADO ]
                              (status: 'vendido')
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 2.1: Cadastro de Peças & Catálogo
* **Controller:** `ItemController` (`app/Http/Controllers/ItemController.php`).
* **Tabela Principal:** `items` (Atenção: a tabela chama-se `items`, NUNCA `produtos`).
  * `id`: Identificador único no banco.
  * `codigo`: Código alfa-numérico da etiqueta/peça (ex: `'001A'`, `'Z8K2'`). É o SKU visível na etiqueta e no QR Code.
  * `nome_do_produto`: Nome/descrição da peça (ex: *"Vestido Longo Floral Farm"*). **Atenção:** a coluna NÃO se chama `nome`, chama-se `nome_do_produto`.
  * `descricao`: Detalhes adicionais, tecido, medidas.
  * `custo`: Custo de aquisição/payout da peça (decimal).
  * `preco`: Preço de venda ao público (decimal).
  * `estado`: Condição da peça (`'Novo'`, `'Seminovo'`, `'Excelente'`, etc.).
  * `cor`: Cor predominante da peça.
  * `tamanho`: Tamanho físico (ex: `'P'`, `'M'`, `'G'`, `'38'`, `'42'`).
  * `marca`: Nome da marca da peça (string direta, ex: `'Farm'`, `'Zara'`, `'Animale'`).
  * `modelo`: Modelo da peça (opcional).
  * `status`: Status atual da peça no fluxo comercial.
  * `localizacao`: Endereço físico na loja/galpão (ex: `'Arara 1'`, `'Gaveta B'`, `'Caixa 12'`, `'Sacolinha'`).
  * `image`: URL ou caminho da imagem de capa principal.
  * `brecho_id`: Identificador do brechó (multi-tenant, padrão: `1`).

#### Status Válidos da Peça (`status`):
1. `'disponivel'`: Peça pronta para ser vendida.
2. `'em_sacolinha'` / `'sacolinha'`: Peça reservada no carrinho acumulado de uma cliente.
3. `'vendido'`: Peça faturada e entregue em pedido fechado.
4. `'reservado'`: Peça temporariamente retida para cliente.
5. `'loja'`: Peça exposta na loja física.
6. `'estoque'`: Peça guardada no acervo/galpão.
7. `'live'`: Peça separada fisicamente para transmissão de live.
8. `'indisponivel'`: Peça inativa, perdida ou danificada.

> ⚡ **Gatilho Automático de Localização:** Sempre que o status da peça for alterado para `'em_sacolinha'` ou `'vendido'`, o sistema atualiza automaticamente a coluna `localizacao` para `'Sacolinha'` via model observer.

---

### Subárea 2.2: Categorias & Árvore Hierárquica
* **Controller:** `CategoriaController`.
* **Tabelas Envolvidas:** `categorias` e tabela pivot `categoria_item`.

#### Tabela `categorias`:
* `id`: ID numérico da categoria.
* `name`: Nome da categoria (ex: *"Vestidos"*, *"Feminino"*, *"Calçados"*).
* `slug`: Identificador em URL (ex: `'vestidos'`).
* `parent_id`: ID da categoria pai (permite estrutura em árvore: *Feminino > Roupas > Vestidos*).
* `preco_base`: Preço médio de referência da categoria.
* `valor_desconto`: Desconto padrão associado à categoria.
* `tipo_desconto`: `'porcentagem'` ou `'fixo'`.

#### Tabela Pivot `categoria_item`:
* `item_id`: FK para `items.id`.
* `categoria_id`: FK para `categorias.id`.
* Uma peça pode pertencer a mais de uma categoria.

---

### Subárea 2.3: Marcas & Curadoria de Valor
* **Controller:** `MarcaController` (`app/Http/Controllers/Admin/MarcaController.php`).
* **Tabela Principal:** `marcas`
  * `id`: ID da marca.
  * `nome`: Nome oficial da marca (ex: *"Farm"*, *"Zara"*, *"Animale"*, *"Schutz"*).
  * `porcentagem_valor`: Multiplicador de valor da marca para curadoria e precificação de desapegos (ex: `350.00` = 350% do valor base).

---

### Subárea 2.4: Inventário Físico, Scanner & Conferências
* **Controller:** `ItemController` (métodos `inventarioScanner`, `inventarioProcessar`, `inventarioConferenciasIndex`, `inventarioConferenciaShow`).
* **Tabela Principal:** `conferencias_inventario`
  * `id`: ID da auditoria realizada.
  * `user_id`: ID do usuário/conferente que realizou a contagem com o leitor.
  * `localizacao`: O local físico auditado (ex: `'Arara 1'`, `'Arara 2'`, `'Caixa Calçados'`).
  * `total_esperado`: Quantidade de peças que o banco de dados esperava encontrar naquele local.
  * `total_lido`: Total de códigos bipados pelo operador.
  * `total_encontrados`: Peças que estavam cadastradas naquele local e foram confirmadas.
  * `total_faltantes`: Peças que deveriam estar no local mas NÃO foram bipadas (suspeita de extravio).
  * `total_sobrando`: Peças bipadas no local que estavam cadastradas em outro local.
  * `acuracia_percentual`: Taxa de precisão da arara: `(total_encontrados / total_esperado) * 100`.
  * `detalhes_json`: Array JSON com a listagem completa de itens encontrados, faltantes e sobrando.

---

### Subárea 2.5: Fotos & Mídias dos Produtos
* **Controller:** `ItemMediaController`.
* **Tabela Principal:** `item_media`
  * `id`: ID da mídia.
  * `item_id`: FK para `items.id`.
  * `url`: Link da foto de alta resolução.
  * `thumbnail_url`: Link da miniatura otimizada.
  * `position`: Ordem de exibição na galeria da peça.
  * `is_cover`: `1` se for a foto principal da capa, `0` para secundárias.

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Peças Disponíveis para Venda (Estoque Real)
```sql
SELECT COUNT(*) AS total_pecas, 
       SUM(preco) AS valor_total_venda, 
       SUM(custo) AS custo_total
FROM items 
WHERE status = 'disponivel';
```

### 2. Distribuição das Peças por Status
```sql
SELECT status, 
       COUNT(*) AS quantidade, 
       SUM(preco) AS valor_acumulado
FROM items 
GROUP BY status 
ORDER BY quantidade DESC;
```

### 3. Araras e Locais com Mais Peças Disponíveis
```sql
SELECT localizacao, 
       COUNT(*) AS total_itens
FROM items 
WHERE status = 'disponivel' AND localizacao IS NOT NULL
GROUP BY localizacao 
ORDER BY total_itens DESC 
LIMIT 10;
```

### 4. Ranking de Marcas com Maior Valor em Estoque Disponível
```sql
SELECT marca, 
       COUNT(*) AS total_pecas, 
       SUM(preco) AS valor_total
FROM items 
WHERE status = 'disponivel' AND marca IS NOT NULL AND marca != ''
GROUP BY marca 
ORDER BY valor_total DESC 
LIMIT 10;
```

### 5. Histórico e Acurácia do Inventário Físico
```sql
SELECT id, localizacao, total_esperado, total_lido, total_faltantes, acuracia_percentual, created_at
FROM conferencias_inventario 
ORDER BY created_at DESC 
LIMIT 5;
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Nunca busque a tabela `produtos`:** A tabela correta no MySQL é SEMPRE `items`.
2. ❌ **Nunca busque a coluna `nome` ou `titulo` em `items`:** A coluna de nome da peça chama-se `nome_do_produto`.
3. ❌ **Nunca busque a coluna `sku` em `items`:** O código da etiqueta chama-se `codigo`.
4. ❌ **Não confunda peça em estoque com peça disponível:** Peças com `status = 'em_sacolinha'` continuam na tabela `items`, mas NÃO estão disponíveis para venda (estão presas na sacolinha de uma cliente). O estoque disponível para venda é estritamente `WHERE status = 'disponivel'`.
5. ❌ **Tabela de auditoria de estoque:** Chama-se `conferencias_inventario` (não existe `inventarios` nem `auditorias`).
6. ❌ **Ligação com Desapegos:** Quando um item de avaliação (`avaliacao_items`) é aprovado e vai para o estoque, o campo `avaliacao_items.item_id` recebe o `id` da tabela `items`. Se o código começar com `AV`, refere-se ao ID de avaliação!
