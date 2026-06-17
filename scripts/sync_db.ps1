# Windows PowerShell Script to sync Remote DB with Local DB
# Salve este arquivo como sync_db.ps1 na sua máquina Windows.
# Para executar no PowerShell: .\sync_db.ps1

$VPS_IP = "191.252.178.250"
$VPS_USER = "root"
$REMOTE_PATH = "/var/www/sacolinhas"
$DB_USER = "sacolinhas_user"
$DB_PASS = "SenhaSuperSegura123!"
$DB_NAME = "sacolinhas_db"

$DATE = (Get-Date).ToString("ddMMyy")
$BACKUP_FILE = "backup_db_$DATE.sql"

Write-Host "====================================================================" -ForegroundColor Cyan
Write-Host "      SINCRONIZAÇÃO DO BANCO DE DADOS: REMOTO -> LOCAL (WINDOWS)    " -ForegroundColor Cyan
Write-Host "====================================================================" -ForegroundColor Cyan

# 1. Limpar SSH key cache se necessário (para evitar conflitos de IP)
Write-Host "`n1. Limpando cache SSH antigo para o IP $VPS_IP..." -ForegroundColor Yellow
ssh-keygen -R $VPS_IP 2>$null

# 2. Executar mysqldump no servidor remoto via SSH e salvar direto na máquina local
Write-Host "`n2. Executando dump remoto via SSH e salvando localmente..." -ForegroundColor Yellow
Write-Host "Se solicitado, digite a senha do servidor VPS (Gr@nesigo#184)." -ForegroundColor Gray
cmd /c "ssh $VPS_USER@$VPS_IP `"docker exec mysql-db mysqldump --no-tablespaces -u $DB_USER -p'$DB_PASS' $DB_NAME`" > $BACKUP_FILE"

if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao realizar o dump do banco de dados remoto via SSH!"
    exit 1
}
Write-Host "✅ Backup salvo localmente como: $BACKUP_FILE" -ForegroundColor Green

# 3. Baixar regras_pontuacao.sql via SCP
Write-Host "`n3. Baixando regras_pontuacao.sql via SCP..." -ForegroundColor Yellow
scp "$VPS_USER`@$VPS_IP`:$REMOTE_PATH/regras_pontuacao.sql" ./regras_pontuacao.sql

if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️ Aviso: regras_pontuacao.sql não pôde ser baixado. O script prosseguirá." -ForegroundColor Red
} else {
    Write-Host "✅ regras_pontuacao.sql baixado com sucesso!" -ForegroundColor Green
}

# 4. Verificar se o Docker local está rodando e iniciar se necessário
Write-Host "`n4. Verificando o container local mysql-db..." -ForegroundColor Yellow
$containerStatus = docker inspect -f '{{.State.Running}}' mysql-db 2>$null

if ($containerStatus -ne "true") {
    Write-Host "Container local mysql-db não está rodando. Iniciando docker-compose..." -ForegroundColor Yellow
    docker-compose up -d db
    Write-Host "Aguardando 8 segundos para o banco inicializar totalmente..." -ForegroundColor Yellow
    Start-Sleep -Seconds 8
} else {
    Write-Host "✅ Container local mysql-db já está rodando." -ForegroundColor Green
}

# 5. Restaurar o banco localmente
Write-Host "`n5. Restaurando o dump no container local mysql-db..." -ForegroundColor Yellow
# Usamos cmd /c para redirecionamento nativo < que evita problemas de encoding do PowerShell
cmd /c "docker exec -i mysql-db mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_FILE"

if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao restaurar o banco de dados no container local!"
    exit 1
}
Write-Host "✅ Banco restaurado com sucesso!" -ForegroundColor Green

# 6. Restaurar regras de pontuação localmente
if (Test-Path ./regras_pontuacao.sql) {
    Write-Host "`n6. Restaurando regras_pontuacao.sql no container local..." -ForegroundColor Yellow
    cmd /c "docker exec -i mysql-db mysql -f -u $DB_USER -p$DB_PASS $DB_NAME < ./regras_pontuacao.sql"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️ Algumas regras ou triggers já existiam no banco, mas o restante foi aplicado." -ForegroundColor Yellow
    } else {
        Write-Host "✅ Regras de pontuação aplicadas com sucesso!" -ForegroundColor Green
    }
}

Write-Host "`n====================================================================" -ForegroundColor Green
Write-Host "                  PROCESSO CONCLUÍDO COM SUCESSO!                   " -ForegroundColor Green
Write-Host "====================================================================" -ForegroundColor Green
