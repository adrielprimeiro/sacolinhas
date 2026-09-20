# ÁREA 7: GOVERNANÇA, EQUIPE & IA
> **Manual de Regras de Negócio, Arquitetura e Tabelas para o Severino AI**  
> *Arquivo de Zoom Detalhado | Referência: docs/MAPA_GERAL_SISTEMA.md*

---

## 🎯 1. A Estrutura de Governança e Inteligência
A camada de governança do sistema controla o modelo multi-tenant (franquias/parceiros), os níveis de permissão da equipe e a inteligência artificial autônoma (Severino AI):

```
                        [ GOVERNANÇA DO SISTEMA ]
                                    │
       ┌────────────────────────────┼────────────────────────────┐
       ▼                            ▼                            ▼
[ BRECHÓS PARCEIROS ]       [ CONTROLE DE ACESSO ]       [ SEVERINO AI & LATM ]
(modelo multi-tenant)       (admin_master, admin,        (memória permanente,
(isolamento de dados)        brecho_admin, client)        ferramentas dinâmicas)
```

---

## 📂 2. As Subáreas Detalhadas e Tabelas Reais

### Subárea 7.1: Brechós Parceiros (Multi-tenant)
* **Controller:** `BrechoController` (`app/Http/Controllers/Admin/BrechoController.php`).
* **Tabela Principal:** `brechos`
  * `id`: ID do brechó (ex: `1` = Minha Mania Matriz, `2` = Brechó Parceiro).
  * `nome`: Razão social ou nome fantasia do brechó.
  * `slug`: Identificador em URL.
  * `documento`: CNPJ ou CPF do parceiro.
  * `chave_pix`: Chave PIX oficial para recebimento das vendas.
  * `tipo_chave_pix`: `'cpf'`, `'cnpj'`, `'email'`, `'telefone'`, `'aleatoria'`.
  * `ativo`: `1` se o brechó estiver em operação, `0` se suspenso.
  * `configuracoes`: JSON com parâmetros e taxas da parceria.

#### Tabela Pivot `brecho_clientes`:
* `brecho_id`: FK para `brechos.id`.
* `user_id`: FK para `users.id` (cliente que comprou ou se cadastrou no parceiro).
* `origem`: Como a cliente conheceu o parceiro (live, indicação, etc.).

#### Regra de Isolamento Multi-tenant:
* Se um usuário logado for `brecho_admin` (`isBrechoParceiro() == true`), o sistema filtra automaticamente os registros de `items`, `sacolinhas` e `lives` onde `brecho_id = auth()->user()->brecho_id`.
* Os administradores master (`role = 'admin_master'`) possuem visão consolidada de todos os brechós.

---

### Subárea 7.2: Gestão de Equipe & Níveis de Acesso
* **Controller:** `AdminUserController` (`app/Http/Controllers/Admin/AdminUserController.php`).
* **Tabela Principal:** `users`
  * `id`: ID do usuário.
  * `name`: Nome do colaborador.
  * `email`: E-mail de login.
  * `role`: Papel atribuído no sistema:
    * `'admin_master'`: Proprietário / Super Administrador (acesso irrestrito a configurações, banco, financeiro e equipe).
    * `'admin'`: Gerente / Operador Geral da Minha Mania (opera lives, sacolinhas, financeiro e estoque).
    * `'brecho_admin'`: Administrador de brechó parceiro (opera apenas sua unidade).
    * `'client'`: Cliente compradora.
  * `bloqueado`: `1` se o acesso estiver revogado/bloqueado, `0` se ativo.

---

### Subárea 7.3: Severino AI, Memória Permanente & Ferramentas Autônomas (LATM)
* **Controller:** `SeverinoController` (`app/Http/Controllers/Admin/SeverinoController.php`).
* **Service:** `SeverinoService` (`app/Services/Ai/SeverinoService.php`).

#### 1. Tabela `knowledge_bases` (Memória de Longo Prazo):
* Guarda regras de negócio, atalhos e preferências ensinadas diretamente ao Severino pelo chat (via ferramenta `memorizar_regra_ou_preferencia`).
* **Campos:** `id`, `title`, `category`, `content`, `is_active`.
* **Injeção Contínua:** Todas as regras ativas (`is_active = 1`) são injetadas automaticamente no topo do System Prompt em todas as conversas futuras.

#### 2. Tabela `severino_dynamic_tools` (Motor LATM - Tool Maker):
* Guarda novas ferramentas SQL criadas de forma 100% autônoma pelo próprio Severino para responder a perguntas novas do gestor.
* **Campos:** `id`, `nome`, `descricao`, `modulo_area`, `parametros` (JSON), `sql_template`, `ativo`, `created_by`.
* **Mecanismo de Segurança:** Antes de gravar qualquer ferramenta nova, o sistema executa um **Dry-Run** com rollback em transação no MySQL. Se a query contiver erro de sintaxe ou coluna inexistente, a ferramenta é bloqueada.
* **Carregamento Dinâmico:** Ferramentas salvas tornam-se ferramentas nativas disponíveis no catálogo da IA nas próximas chamadas de API.

#### 3. Base Modular de Conhecimento (`docs/areas/`):
* O Severino consulta a ferramenta `mapear_modulo_sistema` que carrega cirurgicamente os arquivos de zoom de cada uma das 7 áreas:
  * `01_COMERCIAL_SACOLINHAS.md`
  * `02_PRODUTOS_ESTOQUE.md`
  * `03_CLIENTES_ATENDIMENTO.md`
  * `04_FINANCEIRO_CONCILIACAO.md`
  * `05_CLUBE_MANIA.md`
  * `06_RELATORIOS_AUDITORIA.md`
  * `07_GOVERNANCA_IA.md`

---

## 📊 3. Métricas e Fórmulas Essenciais para o Severino

### 1. Resumo de Brechós Parceiros Ativos
```sql
SELECT id, nome, slug, documento, chave_pix, ativo, created_at 
FROM brechos 
ORDER BY id ASC;
```

### 2. Composição da Equipe Interna (Usuários Administrativos)
```sql
SELECT id, name, email, role, bloqueado, created_at 
FROM users 
WHERE role IN ('admin_master', 'admin', 'brecho_admin')
ORDER BY role ASC, name ASC;
```

### 3. Regras Ativas na Base de Conhecimento do Severino
```sql
SELECT id, title, category, content, created_at 
FROM knowledge_bases 
WHERE is_active = 1 
ORDER BY id DESC;
```

### 4. Ferramentas Autônomas Criadas pelo Severino (LATM)
```sql
SELECT id, nome, descricao, modulo_area, sql_template, ativo, created_at 
FROM severino_dynamic_tools 
WHERE ativo = 1 
ORDER BY id DESC;
```

---

## 🚫 4. Guia Anti-Alucinação para o Severino (Pegadinhas do Módulo)

1. ❌ **Identificação de Parceiros:** A tabela multi-tenant chama-se `brechos` (não `lojas` ou `filiais`).
2. ❌ **Gravação de Memória Permanente:** NUNCA diga que "gravou na memória" sem chamar de verdade a ferramenta `memorizar_regra_ou_preferencia`. Apenas essa ferramenta grava no MySQL na tabela `knowledge_bases`.
3. ❌ **Criação de Novas Ferramentas:** NUNCA invente nomes com espaços ou caracteres especiais ao chamar `criar_ferramenta_dinamica`. Use sempre snake_case (ex: `relatorio_devolucoes_por_marca`) e queries SELECT parametrizadas.
