---
name: Code Developer
description: Diretrizes e padronização de codificação em PHP e Laravel para o projeto Sacolinhas.
---

# 💻 Guia de Escrita de Código (Code Developer)

Ao modificar ou criar arquivos, você deve seguir o padrão imposto neste projeto. O código deve ser seguro, otimizado para as rotinas financeiras e de fácil manutenção.

## 1. Padrões de Qualidade e Estrutura
*   **PSR-12:** Siga as regras de formatação do PHP (visibilidade em métodos, posição de chaves, espaçamento).
*   **Controllers Magros:** Evite usar validações inline longas (`$request->validate([...])`) nos Controllers. Prefira criar classes `FormRequest` em `app/Http/Requests` para isolar as regras de validação.
*   **Service Classes:** Qualquer cálculo (como cálculo de frete), criação de PIX no banco Inter, ou conciliação financeira complexa **DEVE** ocorrer dentro de uma classe em `app/Services/`. O Controller apenas a invoca.

## 2. Transações Financeiras (Crucial!)
Sempre que envolver dinheiro (Baixa de `Movimentacao`, aprovação de `Pedido`, criação de `Lancamento` ou consumo de Saldo de Crédito/Carteira), **sempre** utilize transações do banco de dados para evitar inconsistências em caso de exceções:

```php
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    // 1. Atualiza pedido
    // 2. Lança Movimentação
    // 3. Atualiza Saldo do Cliente
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Tratamento ou throw
}
```
*Dica:* Em conciliações assíncronas (ex: Webhooks), use `lockForUpdate()` ao consultar as transações para evitar *Race Conditions*.

## 3. Otimização e Prevenção de N+1
*   **Sempre** carregue os relacionamentos antecipadamente usando *Eager Loading* quando for iterar sobre coleções de Models em exibições de View ou exportações JSON.
    *   *Errado:* Iterar sobre `$cliente->pedidos` sem ter dado o load prévio.
    *   *Correto:* `$clientes = Cliente::with(['pedidos', 'limite'])->get();`

## 4. Tratamento de Exceções em APIs e Jobs
*   **APIs e Webhooks Externos:** Ao consumir APIs (Inter, MP, Melhor Envio), sempre englobe a chamada em `try-catch`. Grave as respostas de erro detalhadas no Log (`Log::error()`) com contexto.
*   Ao falhar uma chamada externa assíncrona, permita que o Job utilize o mecanismo padrão do Laravel (`--tries`, `delay`) de re-tentativas (Backoff), jogando a `Exception` correspondente no método `handle()`.

## 5. Convenção de Nomenclatura
*   Nomes de Classe: `PascalCase`.
*   Nomes de Método: `camelCase`.
*   Colunas de Banco e Variáveis: `snake_case`.
*   As classes Service devem terminar com "Service" (Ex: `ConciliacaoService`).
*   Jobs devem terminar com "Job" ou verbo imperativo claro (Ex: `RecalcularSaldosJob` ou `SendWhatsAppMessage`).
