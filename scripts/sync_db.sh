#!/bin/bash
# Script para sincronizar o banco de dados remoto com o local usando Git Bash / Linux / WSL.
# Salve este arquivo como sync_db.sh e execute: ./sync_db.sh

VPS_IP="191.252.178.250"
VPS_USER="root"
REMOTE_PATH="/var/www/sacolinhas"
DB_USER="sacolinhas_user"
DB_PASS="SenhaSuperSegura123!"
DB_NAME="sacolinhas_db"

DATE=$(date +%d%m%y)
BACKUP_FILE="backup_db_${DATE}.sql"

echo "===================================================================="
echo "      SINCRONIZAÇÃO DO BANCO DE DADOS: REMOTO -> LOCAL (GIT BASH)   "
echo "===================================================================="

# 1. Limpar SSH key cache
echo -e "\n1. Limpando cache SSH antigo para o IP $VPS_IP..."
ssh-keygen -R $VPS_IP 2>/dev/null

# 2. Executar dump remoto e salvar direto localmente
echo -e "\n2. Executando dump remoto via SSH e salvando localmente..."
echo "Se solicitado, digite a senha do servidor VPS (Gr@nesigo#184)."
ssh "${VPS_USER}@${VPS_IP}" "docker exec mysql-db mysqldump --no-tablespaces -u $DB_USER -p'$DB_PASS' $DB_NAME" > "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "❌ Erro ao realizar o dump do banco de dados remoto via SSH!"
    exit 1
fi
echo "✅ Backup salvo localmente como: $BACKUP_FILE"

# 3. Baixar regras_pontuacao.sql via SCP
echo -e "\n3. Baixando regras_pontuacao.sql via SCP..."
scp "${VPS_USER}@${VPS_IP}:${REMOTE_PATH}/regras_pontuacao.sql" ./regras_pontuacao.sql

if [ $? -ne 0 ]; then
    echo "⚠️ Aviso: regras_pontuacao.sql não pôde ser baixado. O script prosseguirá."
else
    echo "✅ regras_pontuacao.sql baixado com sucesso!"
fi

# 4. Verificar se o Docker local está rodando e iniciar se necessário
echo -e "\n4. Verificando o container local mysql-db..."
if [ "$(docker inspect -f '{{.State.Running}}' mysql-db 2>/dev/null)" != "true" ]; then
    echo "Container local mysql-db não está rodando. Iniciando docker-compose..."
    docker-compose up -d db
    echo "Aguardando 8 segundos para o banco inicializar totalmente..."
    sleep 8
else
    echo "✅ Container local mysql-db já está rodando."
fi

# 5. Restaurar o banco localmente
echo -e "\n5. Restaurando o dump no container local mysql-db..."
docker exec -i mysql-db mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_FILE"

if [ $? -ne 0 ]; then
    echo "❌ Erro ao restaurar o banco de dados no container local!"
    exit 1
fi
echo "✅ Banco restaurado com sucesso!"

# 6. Restaurar regras de pontuação localmente
if [ -f "./regras_pontuacao.sql" ]; then
    echo -e "\n6. Restaurando regras_pontuacao.sql no container local..."
    docker exec -i mysql-db mysql -f -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < ./regras_pontuacao.sql
    if [ $? -ne 0 ]; then
        echo "⚠️ Algumas regras ou triggers já existiam no banco, mas o restante foi aplicado."
    else
        echo "✅ Regras de pontuação aplicadas com sucesso!"
    fi
fi

echo -e "\n===================================================================="
echo "                  PROCESSO CONCLUÍDO COM SUCESSO!                   "
echo "===================================================================="
