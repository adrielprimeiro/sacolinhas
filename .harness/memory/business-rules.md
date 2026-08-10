# Visão Geral do Negócio e Conceitos-Chave

O sistema é uma plataforma de e-commerce com forte foco em vendas via transmissões ao vivo (Lives), utilizando o modelo de negócio baseado em "Sacolinhas".

*   **Sacolinhas:** Durante uma Live, os produtos que os clientes reservam ou manifestam interesse são adicionados às suas "sacolinhas". Trata-se de um carrinho de compras atrelado a uma sessão ao vivo.
*   **Live:** Evento de venda (transmissão) onde as sacolinhas são formadas. Os clientes (`User` / `Cliente`) são vinculados à live e seus itens.
*   **Pedido:** Após ou durante o fechamento da Live, as sacolinhas são consolidadas em Pedidos (ordens de compra), onde ocorrem o pagamento e o cálculo logístico.

---

# Fluxo e Status de Pedidos

Os pedidos possuem um ciclo de vida intimamente ligado ao processamento financeiro e, principalmente, ao fluxo logístico.

*   **Status de Pagamento:** Principalmente gerenciado como `pendente` e `aprovado`.
*   **Status de Rastreamento / Logístico:** O model `Pedido` sincroniza eventos logísticos (majoritariamente com o Melhor Envio) com os seguintes status permitidos / previstos em código:
    *   **Pendente:** Aguardando pagamento ou liberação da etiqueta.
    *   **Liberado para envio:** A etiqueta foi liberada/paga e está pronta para postagem.
    *   **Postado:** O pacote foi entregue na agência ou transportadora.
    *   **Em trânsito:** Pacote viajando para a próxima unidade de distribuição.
    *   **Saiu para entrega:** O entregador está com o pacote.
    *   **Entregue:** Pacote recebido no destino.
    *   **Cancelado:** O envio foi cancelado.

---

# Validade e Cancelamento de Pedidos do Portal

*   **Prazo de Pagamento (24 Horas):** Pedidos gerados via portal do cliente ou site (`origem_pedido` igual a `portal` ou `site`) possuem uma janela limite de 24 horas a partir da sua criação para a confirmação/aprovação do pagamento.
*   **Cancelamento Automático:** Caso o pagamento não seja efetuado/aprovado no prazo de 24h:
    1. O pedido tem seu status alterado para `cancelado` (`status_pedido = 'cancelado'`, `status_pagamento = 'rejeitado'`).
    2. Os lançamentos contábeis e débitos da carteira associados ao pedido são removidos via `PedidoObserver`.
    3. **Retorno dos Produtos para a Sacolinha:** Todos os itens vinculados ao pedido voltam para a sacolinha ativa do cliente (`sacolinhas.status = 'sacolinha'`, `items.status = 'sacolinha'`), ficando disponíveis para o cliente gerenciar ou fechar um novo pedido.
*   **Agendamento:** A verificação e cancelamento automático é executada de forma recorrente (hourly) pelo comando `php artisan portal:cancelar-expirados`.

---

# Regras Financeiras

O coração financeiro do sistema reside na sua capacidade de conciliação e no tratamento de taxas:

*   **Conciliação Automatizada:** O `ConciliacaoService` processa arquivos OFX (extratos bancários genéricos) e comunica-se com a API (e arquivos CSV) do Mercado Pago.
*   **Auto-conciliação e Baixa de Lançamentos:** Quando o webhook ou a sincronização identifica um pagamento aprovado no Mercado Pago (buscando pela `external_reference` do pedido), o sistema auto-vincula a transação. Cria-se a `Movimentacao` de baixa de forma automatizada.
*   **Tratamento de Taxas (Split Contábil):** Para origens como Mercado Pago, o sistema separa o `valor_bruto`, `valor_taxa` e o `valor_liquido`. Ele gera automaticamente uma Despesa Financeira vinculada a uma `ClassificacaoFinanceira` ("Taxas e Tarifas Bancárias") para manter a contabilidade correta.

---

# Cálculo de Frete e Cubagem

As regras de dimensionamento para frete estão encapsuladas no `ShippingCalculatorService` e dependem fortemente das `Categorias` de cada produto. As medidas dos itens herdam as medidas configuradas na categoria. Se houver mais de uma categoria, usa-se a que tem as maiores medidas.

*   **Regra 1 (Peso):** O peso total da caixa é a *soma* dos pesos de todos os itens.
*   **Regra 2 (Comprimento):** O comprimento da caixa é o *maior* comprimento entre os itens do pedido.
*   **Regra 3 (Largura):** A largura da caixa é a *maior* largura entre os itens.
*   **Regra 4 (Altura):** A altura total da caixa é a *soma* das alturas dos itens.
*   **Regra 5 (Fator de Compactação Textil):** Se a categoria do item contém "roupa" ou "vestuário", aplica-se um redutor de 20% na sua altura (multiplica por `0.8`), simulando o fato de que tecidos podem ser amassados/compactados na caixa.
*   **Regra 6 (Limites Mínimos):** Independente das regras anteriores, as dimensões nunca serão menores que as exigências mínimas padrão dos Correios/Transportadoras: Comprimento: 16cm, Largura: 11cm, Altura: 2cm e Peso: 0.1kg (100g).

---

# Integrações Externas

1.  **Melhor Envio (`MelhorEnvioService`):** Responsável por cotações de frete, geração de etiquetas e sincronização constante de status de rastreio dos pedidos (`checkAndSyncTracking`).
2.  **Mercado Pago:** Atua no processamento de cartões e PIX, com forte integração de conciliação financeira assíncrona (via busca de relatórios e extratos em API).
3.  **Banco Inter (`BancoInterPixService`):** Comunicação segura via mTLS (certificados) para gerar cobranças PIX diretas (Pix Cob) e escutar confirmações via Webhooks.
4.  **Twilio / WhatsApp (`LiveWhatsAppService`):** Sistema de notificação. Após uma live, filas em background (`SendWhatsAppMessage`) disparam notificações via WhatsApp aos clientes contendo o resumo dos seus carrinhos (total de itens e valores), aplicando rate limit (ex: 1 mensagem por segundo).
5.  **Processamento de Imagem:** Apesar de haver um `GeminiImageEditService`, ele invoca localmente um script Python (`scripts/image_processor.py`) via terminal para realizar edição nas imagens.

---

## Dúvidas a Validar

*   **Fluxo Sacolinha -> Pedido:** O agrupamento da Sacolinha em um Pedido é um processo feito manualmente pela administradora (backoffice) após o fim da live, ou os próprios clientes acessam um portal para "fechar o carrinho"?
*   **Pontuações e Fidelidade:** O banco possui Models como `PontuacoesCliente` e `Desafio`. Qual é a regra exata de cashback/gamificação implementada no `PontuacoesService`?
*   **Status Hardcoded:** Atualmente os status (logísticos e financeiros) parecem ser strings *hardcoded* (`'pendente'`, `'entregue'`). Devemos migrar isso para Enums formais no banco (ou PHP 8.1 Enums) para maior robustez na validação das transações?
*   **Integração IA Gemini:** O Python chamado (`image_processor.py`) faz uso de alguma IA hospedada (Google Gemini via API) para edição das fotos das peças ou faz apenas edições simples/filtros usando bibliotecas como Pillow/OpenCV?
