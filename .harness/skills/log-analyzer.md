---
name: Log Analyzer
description: Manual para depuração e análise de erros de infraestrutura, código e APIs externas do sistema.
---

# 🕵️ Guia de Diagnóstico de Erros (Log Analyzer)

Sua missão é ir direto na causa raiz do erro e evitar soluções do tipo "tentativa e erro" sem embasamento. Quando reportado um problema na aplicação (Erro 500, inconsistência de dados, falha de filas), siga este script:

## 1. Localização e Leitura de Logs do Laravel
O primeiro passo na depuração de erros silenciosos ou 500 Server Errors é ler o arquivo de log principal:
`storage/logs/laravel.log`
*   **Busque pela stack trace mais recente:** Identifique o arquivo, classe e a linha exata que disparou a exceção.
*   **Atenção ao contexto:** Verifique os blocos `[YYYY-MM-DD HH:MM:SS] production.ERROR: ...` e atente-se às mensagens de "context" salvas pelos `Log::error(...)` injetadas no código.

## 2. Erros de Banco de Dados e Docker
*   **Deadlocks e Locks:** O sistema usa transações concorrentes (Mercado Pago Webhooks vs Conciliação OFX vs Checkout do usuário). Procure por erros como *Deadlock found when trying to get lock* ou *Lock wait timeout exceeded*.
*   **Campos Not Null / Chaves Estrangeiras:** Caso haja falhas de `Integrity constraint violation`, analise se uma dependência foi criada corretamente ou se falta Eager Loading / relacionamento antes do `.save()`.
*   Verifique se o banco via docker-compose (serviço `db` rodando MySQL 8.0) está operacional.

## 3. Diagnóstico de Integrações (APIs Externas)
Este sistema consome APIs sensíveis. Ao debugar erros relacionados a pagamentos, envio e notificação:
*   **Banco Inter (PIX):** Problemas comuns são expiração do Access Token mTLS ou caminhos inválidos de certificado (`.crt` / `.key`). Verifique o arquivo `BancoInterPixService`.
*   **Mercado Pago:** Erros 400 ou 401 indicam credenciais inválidas ou que os retornos do Extrato não possuem os cabeçalhos reconhecidos no CSV da `ConciliacaoService`.
*   **Twilio (WhatsApp) e Jobs:** Mensagens não enviadas costumam cair na tabela `failed_jobs` e não no erro HTTP convencional. Use o artisan se possível (`php artisan queue:failed`) para inspecionar, ou olhe o status retornado no `TwilioOutController` / Webhook.
*   **Melhor Envio:** A sincronização de eventos de rastreio (`checkAndSyncTracking`) pode sofrer *rate limits* e responder HTTP 429. Verifique os logs.

## 4. Como Reportar a Correção
Após analisar as causas, nunca proponha a correção "às cegas". Sua resposta deve conter:
1. **Causa Raiz Exata:** "O erro ocorre pois o método `getPedidoId()` tentou acessar índice não existente no array JSON".
2. **Contexto de API/DB:** Se a falha ocorreu por timeout do banco ou resposta inválida de uma API de terceiro.
3. **Solução Embasada:** Forneça a correção de código garantindo tratamento robusto (`isset`, `try/catch`, validação de request) e diga qual será o impacto.
