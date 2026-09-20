# ÁREA 3: CLIENTES & ATENDIMENTO (O RELACIONAMENTO)
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. O Fluxo de Atendimento e Conta-Corrente da Cliente
A cliente da Minha Mania é o centro das operações comerciais e financeiras:

```
[ CADASTRO DA CLIENTE ] ──(Instagram / TikTok / WhatsApp)──> [ PERFIL UNIFICADO ]
                                                                     │
                    ┌────────────────────────────────────────────────┼──────────────────────────────────┐
                    ▼                                                ▼                                  ▼
           [ SACAS & COMPRAS ]                            [ CARTEIRA / CONTA-CORRENTE ]              [ ATENDIMENTO WHATSAPP ]
           (sacolinhas, pedidos)                          (crédito de desapegos avaliados)           (mensagens, lembretes de
           (limite de crédito)                            (débitos de compras, estornos)             vencimento, comprovantes)
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 3.1: Cadastro & Perfil da Cliente
* **Controllers:** `ClienteController` (`app/Http/Controllers/ClienteController.php`), `AdminUserController`.
* **Tabela Principal:** `users` (Atenção: NUNCA existe tabela `clientes` ou `enderecos`; a tabela é SEMPRE `users`).
  * `id`: Identificador numérico único da cliente.
  * `name`: Nome completo visível da cliente (ex: *"Aline Martins Silva"*).
  * `email`: E-mail de login e notificações.
  * `cpf`: Documento CPF (usado na emissão de etiquetas Melhor Envio e conciliação).
  * `role`: Papel no sistema (`'client'` para clientes, `'admin'` ou `'admin_master'` para operadores).
  * `bloqueado`: `1` se a cliente estiver inadimplente ou impedida de comprar, `0` se liberada.
  * `apelido`: Apelido carinhoso usado em lives e suporte.
  * `instagram`: Usuário do Instagram sem @ (ex: `'alinemartins'`).
  * `tiktok`: Usuário do TikTok (também mapeado em `nome_cliente` em formulários legados).
  * `phone` / `whatsapp`: Telefone com DDD e dígitos (ex: `'11999998888'`).
  * `telefone_principal`: Telefone alternativo ou fixo.
  
#### Dados de Entrega / Endereço (Diretamente em `users`):
* `endereco`: Logradouro (rua, avenida, travessa).
* `numero_endereco`: Número da residência.
* `complemento`: Apartamento, bloco, casa 2.
* `bairro`: Bairro da residência.
* `cidade`: Cidade (ex: *"São Paulo"*, *"Santo André"*).
* `estado`: UF de 2 letras (ex: `'SP'`, `'RJ'`, `'MG'`).
* `cep`: Código postal formatado ou numérico (ex: `'01310-100'`).

---

### Subárea 3.2: Carteira da Cliente & Conta-Corrente (Créditos e Débitos)
* **Controllers:** `ContaCorrenteController` (`app/Http/Controllers/ContaCorrenteController.php`).
* **Tabela Principal:** `conta_corrente`
  * `id`: ID sequencial da movimentação na carteira.
  * `user_id`: Chave estrangeira para `users.id`.
  * `tipo_movimentacao`: `'credito'` (saldo a favor da cliente) ou `'debito'` (saldo contra a cliente).
  * `valor`: Valor da transação (positivo, decimal 10,2).
  * `descricao`: Motivo da movimentação (ex: *"Crédito Avaliação Desapego #51"*, *"Abatimento Pedido #1204"*, *"Estorno"*).
  * `referencia_tipo`: Origem (`'avaliacao'`, `'pedido'`, `'movimentacao'`, `'desconto'`, etc.).
  * `referencia_id`: ID do registro de origem (ex: ID da avaliação ou pedido).
  * `saldo_anterior`: Fotografia do saldo imediatamente antes da transação.
  * `saldo_atual`: **Fotografia do saldo da cliente logo após esta transação específica.**
  * `data_movimentacao`: Timestamp exato da ocorrência.

#### ⚠️ REGRA DE OURO CRÍTICA DE CONTA-CORRENTE (LEIA COM ATENÇÃO):
1. A tabela `conta_corrente` é um **LIVRO-RAZÃO HISTÓRICO DE AUDITORIA** (um extrato bancário). Cada cliente tem dezenas de linhas.
2. **NUNCA faça `SUM(saldo_atual)` em `conta_corrente`!** Fazer isso somará todas as fotografias passadas da vida do cliente e resultará em milhões de reais inexistentes!
3. **O Saldo Atual Real de um Cliente** é SEMPRE o `saldo_atual` da sua movimentação **MAIS RECENTE**:
   ```sql
   SELECT saldo_atual 
   FROM conta_corrente 
   WHERE user_id = :user_id 
   ORDER BY data_movimentacao DESC, id DESC 
   LIMIT 1;
   ```
4. Se o cliente não tiver nenhuma linha em `conta_corrente`, seu saldo é `R$ 0,00`.
5. Se `saldo_atual > 0`, o cliente tem **CRÉDITO** na loja (o brechó deve para ele ou ele adiantou).
6. Se `saldo_atual < 0`, o cliente está **DEVEDOR** (pegou peças ou frete fiado).

---

### Subárea 3.3: Limites de Sacolinha da Cliente
* **Controller:** `ClienteController` (métodos de limites).
* **Tabela Principal:** `cliente_limites`
  * `id`: ID do registro.
  * `user_id`: Chave estrangeira para `users.id`.
  * `limite_credito`: Valor total máximo autorizado para a cliente acumular em sacolinhas sem fechar pedido (ex: `R$ 500,00`).
  * `limite_utilizado`: Valor atualmente consumido pelas peças que estão na sacolinha aberta da cliente.
  * `limite_disponivel`: Margem restante para a cliente continuar comprando: `(limite_credito - limite_utilizado)`.
  * `ativo`: `1` se o limite especial estiver ativo, `0` se desativado.

---

### Subárea 3.4: Chat & Mensagens de WhatsApp
* **Controllers:** `ChatController` (`app/Http/Controllers/Admin/ChatController.php`), `Admin/LiveChatController`.
* **Tabela Principal:** `whatsapp_messages`
  * `id`: ID da mensagem.
  * `user_id`: Cliente vinculada (`users.id`).
  * `direction`: `'inbound'` (mensagem recebida da cliente) ou `'outbound'` (mensagem enviada pela loja).
  * `body`: Texto da mensagem ou template enviado.
  * `status`: Status do envio (`'sent'`, `'delivered'`, `'read'`, `'failed'`).
  * `from` / `to`: Número remetente e destinatário.
  * `media_url`: Link de imagem, comprovante ou PDF anexo.
  * `created_at`: Data e hora da mensagem.

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Buscar Cadastro Completo do Cliente por Qualquer Termo
```sql
SELECT id, name, apelido, instagram, tiktok, whatsapp, phone, email, cidade, estado, bairro, endereco, cep
FROM users 
WHERE name LIKE :termo 
   OR apelido LIKE :termo 
   OR instagram LIKE :termo 
   OR tiktok LIKE :termo 
   OR phone LIKE :termo 
   OR whatsapp LIKE :termo 
   OR email LIKE :termo
ORDER BY name ASC 
LIMIT 5;
```

### 2. Saldo e Últimas Movimentações da Carteira do Cliente
```sql
SELECT id, data_movimentacao, tipo_movimentacao, valor, descricao, saldo_anterior, saldo_atual
FROM conta_corrente 
WHERE user_id = :user_id 
ORDER BY data_movimentacao DESC, id DESC 
LIMIT 5;
```

### 3. Consolidado da Carteira de Clientes (Créditos vs Dívidas Totais)
```sql
SELECT 
    COUNT(DISTINCT ultimos.user_id) AS total_clientes,
    SUM(CASE WHEN c.saldo_atual > 0 THEN 1 ELSE 0 END) AS total_com_credito,
    SUM(CASE WHEN c.saldo_atual > 0 THEN c.saldo_atual ELSE 0 END) AS montante_creditos,
    SUM(CASE WHEN c.saldo_atual < 0 THEN 1 ELSE 0 END) AS total_devedores,
    SUM(CASE WHEN c.saldo_atual < 0 THEN ABS(c.saldo_atual) ELSE 0 END) AS montante_dividas
FROM conta_corrente c
INNER JOIN (
    SELECT user_id, MAX(id) AS max_id 
    FROM conta_corrente 
    GROUP BY user_id
) ultimos ON c.id = ultimos.max_id;
```

### 4. Ranking de Cidades com Mais Clientes Cadastrados
```sql
SELECT cidade, estado, COUNT(*) AS total_clientes
FROM users 
WHERE role = 'client' AND cidade IS NOT NULL AND cidade != ''
GROUP BY cidade, estado 
ORDER BY total_clientes DESC 
LIMIT 10;
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Nunca busque a tabela `clientes`:** Ela NÃO existe no MySQL. Todas as clientes ficam na tabela `users` (`WHERE role = 'client'`).
2. ❌ **Nunca busque a tabela `enderecos`:** Endereço, cidade, estado, bairro e CEP são colunas diretas da tabela `users`!
3. ❌ **Nunca faça `SUM(saldo_atual)` em `conta_corrente`:** A tabela `conta_corrente` é um log. O saldo de cada cliente é apenas a linha mais recente (`ORDER BY id DESC LIMIT 1`). Para o saldo consolidado do painel, use a ferramenta dedicada `resumo_carteira_clientes`!
4. ❌ **Identificação do Cliente:** Ao citar clientes em sacolinhas, pedidos ou desapegos, NUNCA responda apenas o `user_id` numérico. Faça SEMPRE `JOIN users ON users.id = sacolinhas.user_id` para trazer o `name` e responder: *"A sacolinha da Aline"*!
5. ❌ **Não confunda `pessoas` com `users`:** A tabela `pessoas` pertence ao módulo financeiro (fornecedores de compras, funcionários, terceirizados). Clientes compram na loja através da tabela `users`.
