

#!/bin/bash

# --- Configurações ---
# Caminho do projeto (ajuste se necessário)
PROJECT_DIR="/var/www/sacolinhas"
ENV_FILE="$PROJECT_DIR/.env"
BACKUP_DIR="/var/backups/mysql"

# --- Função para ler .env (Limpa e Segura) ---
get_env() {
    grep "^$1=" "$ENV_FILE" | cut -d '=' -f2- | tr -d '"' | tr -d "'" | tr -d '\r'
}

# --- Validações Iniciais ---
if [ ! -f "$ENV_FILE" ]; then
    echo "ERRO: Arquivo .env não encontrado em $ENV_FILE"
    exit 1
fi

# --- Lendo Variáveis ---
DB_DATABASE=$(get_env DB_DATABASE)
DB_USERNAME=$(get_env DB_USERNAME)
DB_PASSWORD=$(get_env DB_PASSWORD)
# Se usar Docker, coloque o nome do container abaixo. Se não, deixe vazio ("")
CONTAINER_NAME="mysql-db"

# --- Nome do Arquivo ---
DATE=$(date +%Y-%m-%d_%H-%M-%S)
FILENAME="backup_${DB_DATABASE}_${DATE}.sql.gz"

# --- Garante que o diretório existe ---
# O comando tr -d '\r' garante que não há lixo no nome da pasta
mkdir -p "$BACKUP_DIR"

# --- Executa o Backup ---
echo "Iniciando backup de $DB_DATABASE..."

if [ -n "$CONTAINER_NAME" ]; then
    # MODO DOCKER
    docker exec "$CONTAINER_NAME" /usr/bin/mysqldump --no-tablespaces -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$BACKUP_DIR/$FILENAME"
else
    # MODO NATIVO (Sem Docker)
    /usr/bin/mysqldump --no-tablespaces -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$BACKUP_DIR/$FILENAME"
fi

# --- Verificação ---
if [ ${PIPESTATUS[0]} -eq 0 ]; then
    echo "✅ Sucesso! Arquivo criado:"
    ls -lh "$BACKUP_DIR/$FILENAME"
else
    echo "❌ Falha ao criar backup."
    rm -f "$BACKUP_DIR/$FILENAME"
    exit 1
fi

# --- Limpeza (Mantém últimos 7 dias) ---
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +7 -delete
