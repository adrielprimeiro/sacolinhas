#!/bin/bash
# Script para rodar diretamente no servidor VPS para gerar o backup local do container.
# Salve este arquivo como backup_on_server.sh na pasta /var/www/sacolinhas do servidor.
# Dê permissão de execução: chmod +x backup_on_server.sh

DB_USER="sacolinhas_user"
DB_PASS="SenhaSuperSegura123!"
DB_NAME="sacolinhas_db"
PROJECT_DIR="/var/www/sacolinhas"

# Gera o nome com a data atual no formato DDMMAA (ex: 130626)
DATE=$(date +%d%m%y)
BACKUP_FILE="${PROJECT_DIR}/backup_db_${DATE}.sql"

echo "=== INICIANDO BACKUP DO BANCO NO SERVIDOR VPS ==="

# 1. Exporta o dump do banco de dados diretamente do container para a pasta do projeto
echo "1. Gerando backup_db_${DATE}.sql..."
docker exec mysql-db mysqldump --no-tablespaces -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Backup do banco gerado em: $BACKUP_FILE"
    ls -lh "$BACKUP_FILE"
else
    echo "❌ Erro ao gerar backup do banco de dados!"
    exit 1
fi

# 2. Exporta regras_pontuacao.sql (estrutura de rotinas e regras de pontuação)
echo "2. Gerando regras_pontuacao.sql..."
docker exec mysql-db /usr/bin/mysqldump --no-tablespaces --routines --no-create-info --no-data --skip-opt --add-drop-trigger -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "${PROJECT_DIR}/regras_pontuacao.sql"

if [ $? -eq 0 ]; then
    echo "✅ regras_pontuacao.sql gerado em: ${PROJECT_DIR}/regras_pontuacao.sql"
    ls -lh "${PROJECT_DIR}/regras_pontuacao.sql"
else
    echo "❌ Erro ao gerar regras_pontuacao.sql!"
    exit 1
fi

echo "=== BACKUP CONCLUÍDO COM SUCESSO NO SERVIDOR! ==="
