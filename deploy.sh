#!/bin/bash
# ==============================================================================
# Script Automático de Deploy (Produção)
# ==============================================================================

# Encerrar a execução em caso de falha em qualquer comando
set -e

echo "🚀 Iniciando o deploy em produção..."

# 1. Atualizar repositório (Git)
echo "📦 Baixando as últimas alterações (git pull)..."
git pull origin main

# 2. Instalar dependências PHP
echo "🐘 Instalando dependências do Composer..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# 3. Instalar dependências Node e build do Frontend
echo "📦 Instalando dependências do Node e buildando os assets..."
npm install
npm run build

# 4. Executar Migrations (se houver alterações no banco)
echo "🗄️ Rodando migrations..."
php artisan migrate --force

# 5. Otimizações de Cache do Laravel
echo "⚡ Limpando e recriando cache (Rotas, Config, Views)..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Reiniciar os Workers da Fila
echo "⚙️ Reiniciando as filas (queue workers)..."
# Isso envia um sinal para que o daemon (ex: Supervisor ou Docker) reinicie o worker de forma segura
php artisan queue:restart

# 7. Reiniciar o PHP-FPM (Necessário para limpar cache OPcache, se aplicável)
echo "🔄 Reiniciando o serviço PHP-FPM..."
# Nota: O nome do serviço pode variar conforme a versão no servidor (php8.2-fpm, php8.1-fpm, etc)
# Caso esteja rodando via Docker em produção, adapte este comando para: docker-compose restart app
sudo systemctl restart php8.2-fpm || sudo service php8.2-fpm restart || echo "Aviso: Não foi possível reiniciar o PHP-FPM via systemctl/service."

echo "✅ Deploy concluído com sucesso!"
