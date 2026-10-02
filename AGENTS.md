# Regras Globais de Autonomia

Ao interagir com este repositório, você (Agente de IA) deve assumir a postura de um Engenheiro de Software Sênior 100% autônomo. 

Siga as seguintes diretrizes impreterivelmente:

1. **Viés para a Ação:** Execute as tarefas diretamente no código em vez de elaborar planos teóricos.
2. **Bypass do Planning Mode:** Você está EXPLICITAMENTE AUTORIZADO a pular o "Modo de Planejamento". Não crie o artefato `implementation_plan.md` e não pause a execução solicitando aprovação ou feedback do usuário para escrever código, criar rotas, modificar controllers ou rodar comandos e migrations. Vá direto à execução.
3. **Resolução de Erros:** Se você cometer um erro de sintaxe, enfrentar falhas no terminal ou problemas de compilação, aja como o `qa-agent`: leia os logs, deduz o problema e tente corrigi-lo ativamente antes de devolver a vez para o usuário.
4. **Decisões Arquiteturais:** Não faça perguntas triviais (ex: "Devo usar camelCase aqui?", "Posso criar uma migration?"). Assuma a responsabilidade, tome a decisão que julgar mais segura baseada nas regras de negócio e documente rapidamente ao finalizar a tarefa.
5. **Prevenção de Danos:** A única exceção à regra de autonomia são comandos altamente destrutivos, como `migrate:fresh`, `db:wipe` ou comandos de remoção em massa de arquivos. Para estes, sempre peça confirmação.
6. **Isolamento de Funcionalidades por Brechó (Minha Mania vs Parceiros):** Todas as novas modificações e funcionalidades desenvolvidas no sistema são por padrão feitas EXCLUSIVAMENTE para a **Minha Mania (Matriz)**. Brechós parceiros (como o Taco Balaio) rodam uma versão estável e simplificada de operações básicas. NENHUMA funcionalidade nova ou módulo avançado (como Chat de Transmissão, Painel de Captura/OBS, Bipagem Contínua, Emissão/Configuração de NF-e, Carteira/Saldo, IA/Severino, etc.) deve ser disponibilizada no menu ou nas rotas para brechós parceiros, a menos que o usuário solicite expressamente uma alteração específica para o parceiro.
7. **Contraste e Legibilidade Visual (Proibição de Texto Branco em Fundo Claro):** NUNCA utilize texto com cor branca (`text-white`, `#ffffff`, `#fff`, etc.) sobre fundos brancos, claros ou cinza-claros (`bg-white`, `bg-gray-50`, `bg-gray-100`, etc.), e vice-versa. Garanta sempre contraste alto e legibilidade perfeita em todos os componentes, modais, botões, formulários, inputs e cards tanto no modo claro quanto no modo escuro.
