#!/bin/bash

# ==============================================================================
# Script de Deploy e Correção - Wallet AutoPay
# Executa git pull, limpa caches e roda os comandos corretivos do financeiro.
# ==============================================================================

set -e # Interrompe o script imediatamente se qualquer comando falhar

echo "🚀 Iniciando deploy e aplicação das correções..."

echo "📦 1. Atualizando repositório (git pull)..."
git pull origin main

echo "⚙️ 2. Atualizando dependências (se houver)..."
composer install --no-dev --optimize-autoloader

echo "🧹 3. Limpando caches e otimizando o Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🔄 4. Reiniciando os workers da fila (para pegarem o novo WalletAutoPayService)..."
php artisan queue:restart

echo "🛠️ 5. Aplicando a correção pontual da Marta (Pedido 756)..."
php fix_pedido_756.php

echo "🧹 6. Removendo débitos em duplicidade antigos na carteira..."
php artisan fix:autopay-debitos

echo "🧾 7. Recriando Movimentações (recibos) que ficaram ausentes no sistema..."
php artisan fix:missing-movimentacoes

echo "✅ Deploy e correções finalizados com sucesso!"
