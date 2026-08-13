# Stack Tecnológica e Ambiência

*   **Linguagem Principal:** PHP 8.2
*   **Framework Backend:** Laravel 12.0
*   **Banco de Dados:** MySQL 8.0
*   **Ferramentas de Frontend:** Vite 7.0 e TailwindCSS 4.1
*   **Conteinerização (Docker):** A aplicação utiliza `docker-compose` para gerenciar os seguintes serviços:
    *   `app`: Contêiner baseado em `php:8.2-fpm` (definido no Dockerfile local).
    *   `db`: Contêiner com a imagem `mysql:8.0`.
    *   `nginx`: Servidor web.
    *   `laravel-worker`: Contêiner replicado de `app` que roda exclusivamente o serviço de filas (queue workers).
*   **Ambiente de Desenvolvimento:** Uso de bibliotecas de desenvolvimento como Laravel Pint (para formatação), Sail, e Kitloong Migrations Generator.

---

# Estrutura do Repositório

O projeto segue a árvore típica do ecossistema Laravel com algumas adições pontuais para isolar regras de negócio densas:

*   **`app/Models/`**: Entidades e mapeamento ORM (Eloquent). Regras de relacionamento e Local Scopes (`scopeAtivos`, `scopeComPedidos`) ficam aqui.
*   **`app/Http/Controllers/`**: Responsáveis por gerenciar requisições e retornos HTTP. Exemplo: `ClienteController`, `PedidoController`.
*   **`app/Http/Requests/`**: Classes Form Requests, onde parte das validações de payload de entrada estão encapsuladas (ex: `ClassificacaoFinanceiraRequest`).
*   **`app/Services/`**: Camada que isola a lógica de negócios pesada e integrações de terceiros. Aqui residem conectores (`BancoInterPixService`, `MelhorEnvioService`) e calculadoras de domínio (`ShippingCalculatorService`, `ConciliacaoService`).
*   **`app/Jobs/`**: Tarefas que rodam de forma assíncrona para não travar requisições de clientes. Exemplo: Disparo em massa do WhatsApp (`SendWhatsAppMessage.php`), recálculos em batch.
*   **`app/Domains/`**: Diretório adicional, sinalizando um encapsulamento por módulos de domínio (ex: módulo `Clube`).
*   **`database/migrations/`**: Onde fica o versionamento do esquema do banco de dados relacional.
*   **`resources/views/`**: Camada de visualização (Blade Templates) que o frontend (Vite/Tailwind) renderiza.

---

# Convenções de Código e Padronização

*   **Padrão de Código PHP:** O projeto segue implicitamente os padrões da comunidade (PSR-12) via Laravel, mas **não** utiliza a diretiva `declare(strict_types=1);` obrigatoriamente no início de todos os arquivos. Recomenda-se utilizar o Laravel Pint para formatação contínua.
*   **Nomenclatura:**
    *   **Classes, Models, Controllers:** PascalCase (Ex: `ClienteController`).
    *   **Métodos e Propriedades:** camelCase (Ex: `calcularFrete()`).
    *   **Tabelas e Colunas no BD:** snake_case e nomes intuitivos (Ex: `limite_credito`).
    *   **Rotas:** snake_case com hífens ou pontos (Ex: `admin.clientes.index`).
*   **Padrões de Arquitetura:**
    *   *Service Classes:* Usadas para regras pesadas.
    *   *Form Requests vs Controller Validation:* O uso atual é misto. Algumas validações ocorrem em Form Requests separados (`app/Http/Requests`), mas vários controllers (ex: `ClienteController`) usam validações inline (`$request->validate()`). Recomenda-se migrar todas as validações pesadas para Form Requests para desacoplar os Controllers.
    *   *DTOs:* Não há forte indício de uso padronizado de DTOs (Data Transfer Objects), os arrays associativos ou objetos Request fluem diretamente entre as camadas.

---

# Padrão de Mensagens de Commit

Embora o repositório não force ganchos (Git Hooks/Husky), o projeto deverá adotar o padrão **Conventional Commits**:
*   `feat:` para novas funcionalidades (ex: `feat: add inter pix webhook`).
*   `fix:` para correção de bugs (ex: `fix: corrige calculo de cubagem em roupas`).
*   `refactor:` para melhorias de código que não alteram o comportamento final.
*   `docs:` para atualizações na documentação.
*   `chore:` para tarefas de configuração de ambiente e atualizações de pacotes.

---

# Diretrizes de Segurança e Desempenho

*   **Tratamento de Dados Sensíveis:** Configurações (tokens de Mercado Pago, Twilio e senhas de banco) estão devidamente retiradas do código via padrão `.env`. Certificados mTLS (Banco Inter) devem permanecer seguros nos diretórios `storage/` sem vazamento via Git.
*   **Consultas Eficientes (N+1):** Controllers usam *Eager Loading* do Eloquent (ex: `Cliente::with(['limite'])`) o que é a prática recomendada para evitar o problema das consultas excessivas de N+1.
*   **Filas / Jobs Assíncronos:** Toda lógica demorada ou arriscada (consumo de APIs lentas, envios em massa de e-mail/WhatsApp, processamento de imagens local com Python) DEVE ser tratada no `app/Jobs/` e rodada no contêiner `laravel-worker`. Evita-se travar as requisições PHP-FPM pelo usuário final.
*   **Comunicação de Banco de Dados:** O Docker MySQL foi configurado com autenticação flexível `--default-authentication-plugin=mysql_native_password`, facilitando a comunicação legada do framework e clientes de banco.
