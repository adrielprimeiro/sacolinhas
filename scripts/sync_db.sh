#!/bin/bash
# Script para sincronizar o banco de dados remoto com o local usando Git Bash / Linux / WSL.
# Salve este arquivo como sync_db.sh e execute: ./sync_db.sh

VPS_IP="191.252.178.250"
VPS_USER="root"
REMOTE_PATH="/var/www/sacolinhas"
ENV_FILE="$(cd "$(dirname "$0")/.." && pwd)/.env"

get_env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" "$ENV_FILE" | tail -n 1 | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_USER="$(get_env_value DB_USERNAME)"
DB_PASS="$(get_env_value DB_PASSWORD)"
DB_NAME="$(get_env_value DB_DATABASE)"

if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_NAME" ]; then
    echo "Erro: DB_USERNAME, DB_PASSWORD e DB_DATABASE devem estar definidos no .env."
    exit 1
fi

DATE=$(date +%d%m%y)
BACKUP_FILE="backup_db_${DATE}.sql"

echo "===================================================================="
echo "      SINCRONIZAÇÃO DO BANCO DE DADOS: REMOTO -> LOCAL (GIT BASH)   "
echo "===================================================================="

# 1. Executar dump remoto e salvar diretamente localmente
echo -e "\n1. Executando dump remoto via SSH e salvando localmente..."
ssh -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_IP}" "docker exec mysql-db mysqldump --no-tablespaces -u $DB_USER -p'$DB_PASS' $DB_NAME" > "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "❌ Erro ao realizar o dump do banco de dados remoto via SSH!"
    exit 1
fi
echo "✅ Backup salvo localmente como: $BACKUP_FILE"

# 2. Baixar regras_pontuacao.sql via SCP
echo -e "\n2. Baixando regras_pontuacao.sql via SCP..."
scp -o StrictHostKeyChecking=accept-new "${VPS_USER}@${VPS_IP}:${REMOTE_PATH}/regras_pontuacao.sql" ./regras_pontuacao.sql

if [ $? -ne 0 ]; then
    echo "⚠️ Aviso: regras_pontuacao.sql não pôde ser baixado. O script prosseguirá."
else
    echo "✅ regras_pontuacao.sql baixado com sucesso!"
fi

# 4. Verificar se o Docker local está rodando e iniciar se necessário
echo -e "\n4. Verificando o container local mysql-db..."
if [ "$(docker inspect -f '{{.State.Running}}' mysql-db 2>/dev/null)" != "true" ]; then
    echo "Container local mysql-db não está rodando. Iniciando docker compose..."
    docker compose up -d db
    echo "Aguardando 8 segundos para o banco inicializar totalmente..."
    sleep 8
else
    echo "✅ Container local mysql-db já está rodando."
fi

# 5. Restaurar o banco localmente
echo -e "\n5. Restaurando o dump no container local mysql-db..."
MYSQL_PWD="$DB_PASS" docker exec -e MYSQL_PWD -i mysql-db mysql -u "$DB_USER" "$DB_NAME" < "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "❌ Erro ao restaurar o banco de dados no container local!"
    exit 1
fi
echo "✅ Banco restaurado com sucesso!"

# 6. Aplicar migrations que ainda existam somente no código local
echo -e "\n6. Aplicando migrations fiscais pendentes..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "Erro ao aplicar migrations após a restauração!"
    exit 1
fi
echo "✅ Schema local atualizado!"

# 7. Restaurar regras de pontuação localmente
if [ -f "./regras_pontuacao.sql" ]; then
    echo -e "\n7. Restaurando regras_pontuacao.sql no container local..."
    MYSQL_PWD="$DB_PASS" docker exec -e MYSQL_PWD -i mysql-db mysql -f -u "$DB_USER" "$DB_NAME" < ./regras_pontuacao.sql
    if [ $? -ne 0 ]; then
        echo "⚠️ Algumas regras ou triggers já existiam no banco, mas o restante foi aplicado."
    else
        echo "✅ Regras de pontuação aplicadas com sucesso!"
    fi
fi

echo -e "\n===================================================================="
echo "                  PROCESSO CONCLUÍDO COM SUCESSO!                   "
echo "===================================================================="
