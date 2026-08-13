---
name: Feature Planner
description: Guia de planejamento e design de novas funcionalidades para o sistema de Sacolinhas.
---

# 🧠 Guia do Planejador de Features (Feature Planner)

Sempre que uma nova funcionalidade for solicitada, **pare e analise** a arquitetura do projeto (focado em vendas de Lives, Sacolinhas e Logística/Financeiro) antes de gerar qualquer código. Siga este fluxo:

## 1. Entendimento do Domínio e Entidades Envolvidas
Identifique o contexto. Este é um sistema onde uma `Live` gera `Sacolinhas` que se tornam `Pedidos`. Entenda se a alteração afeta:
- **Catálogo / Estoque:** Model `Item`, `Categoria`.
- **Transacional de Vendas:** Model `Live`, `Sacolinhas`, `Pedido`.
- **Financeiro:** Model `TransacaoExtrato`, `Movimentacao`, `Lancamento`, `ClassificacaoFinanceira`.
- **Logística / Cliente:** Model `User`, `Pessoa`, Integrações (Melhor Envio).

## 2. Mapeamento de Modificações no Banco de Dados (Migrations e Models)
*   Quais tabelas sofrerão alteração? Planeje a criação de uma **Migration** (`make:migration add_coluna_to_tabela`). Não altere migrations passadas caso já estejam em produção.
*   Os Models afetados (`app/Models/`) precisarão de alterações em:
    *   `$fillable` (adicionou campos novos?).
    *   `$casts` (datas, decimais, booleanos).
    *   Relacionamentos Eloquent (`belongsTo`, `hasMany`).
    *   `Scopes` para manter a lógica de consulta reaproveitável.

## 3. Mapeamento das Rotas e Controllers
*   As rotas ficarão em `routes/web.php` ou `routes/api.php`?
*   O novo método exigirá um Controller existente ou um novo?
*   **Regra de Ouro:** O Controller deve ser magro. Ele apenas recebe a requisição, passa para a validação (Form Request), delega ao `Service` e devolve a resposta/view.

## 4. Delegação de Lógica de Negócio (Services)
*   Se a nova feature envolver regras complexas, cálculos ou integrações (ex: Cálculo de cubagem, Conciliação, Geração de PIX), **proponha a criação ou alteração em um Service** (`app/Services/`). Não coloque regras de negócio pesadas ou lógicas iterativas nos Controllers.

## 5. Integrações Externas (APIs)
*   A feature mexe com Mercado Pago, Banco Inter, Melhor Envio ou Twilio?
*   Mapeie os possíveis cenários de erro da API: timeouts, retornos não previstos e rate limits.
*   As chamadas a APIs lentas, ou processamentos em massa (ex: disparo de WhatsApp), **devem** ser alocadas em Jobs (`app/Jobs/`) para serem executadas assincronamente pelo `laravel-worker`.

## 6. Revisão do Plano (Output Esperado)
Sempre termine o planejamento listando de forma clara para o usuário:
1. Arquivos a serem alterados.
2. Migrations a serem criadas.
3. Possíveis gargalos de performance a mitigar no desenvolvimento.
4. Pontos cegos ou dúvidas a validar com o usuário antes da execução.
