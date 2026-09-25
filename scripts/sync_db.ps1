[Console]::OutputEncoding = New-Object System.Text.UTF8Encoding($false)
$OutputEncoding = [Console]::OutputEncoding

# Windows PowerShell Script to sync Remote DB with Local DB
# Salve este arquivo como sync_db.ps1 na sua máquina Windows.
# Para executar no PowerShell: .\sync_db.ps1

$VPS_IP = "191.252.178.250"
$VPS_USER = "root"
$REMOTE_PATH = "/var/www/sacolinhas"

function Get-DotEnvValue {
    param([Parameter(Mandatory = $true)][string]$Name)

    $value = [Environment]::GetEnvironmentVariable($Name)
    if (-not [string]::IsNullOrWhiteSpace($value)) {
        return $value
    }

    $envFile = Join-Path $PSScriptRoot "..\.env"
    if (Test-Path $envFile) {
        $line = Get-Content $envFile |
            Where-Object { $_ -match "^\s*$([regex]::Escape($Name))\s*=" } |
            Select-Object -Last 1

        if ($line) {
            return (($line -split '=', 2)[1]).Trim().Trim('"').Trim("'")
        }
    }

    return $null
}

$DB_USER = Get-DotEnvValue "DB_USERNAME"
$DB_PASS = Get-DotEnvValue "DB_PASSWORD"
$DB_NAME = Get-DotEnvValue "DB_DATABASE"

if ([string]::IsNullOrWhiteSpace($DB_USER) -or
    [string]::IsNullOrWhiteSpace($DB_PASS) -or
    [string]::IsNullOrWhiteSpace($DB_NAME)) {
    Write-Error "DB_USERNAME, DB_PASSWORD e DB_DATABASE devem estar definidos no ambiente ou no arquivo .env."
    exit 1
}

$DATE = (Get-Date).ToString("ddMMyy")
$BACKUP_FILE = "backup_db_$DATE.sql"

Write-Host "====================================================================" -ForegroundColor Cyan
Write-Host "      SINCRONIZAÇÃO DO BANCO DE DADOS: REMOTO -> LOCAL (WINDOWS)    " -ForegroundColor Cyan
Write-Host "====================================================================" -ForegroundColor Cyan

# 1. Criar dump compactado no servidor e transferir
Write-Host "`n1. Criando dump compactado no servidor e baixando via SCP..." -ForegroundColor Yellow

# Executar mysqldump e criar o tar.gz no servidor. O arquivo remoto anterior
# é removido antes de começar; a limpeza final fica para a próxima execução.
cmd /c "ssh -o StrictHostKeyChecking=accept-new $VPS_USER@$VPS_IP `"rm -f /tmp/backup_sync.tar.gz /tmp/backup_sync.sql && docker exec mysql-db mysqldump --no-tablespaces -u $DB_USER -p'$DB_PASS' $DB_NAME > /tmp/backup_sync.sql && tar -czf /tmp/backup_sync.tar.gz -C /tmp backup_sync.sql && rm -f /tmp/backup_sync.sql`""

if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao gerar o dump compactado no servidor remoto!"
    exit 1
}

# Baixar o arquivo tar.gz. Não abrimos uma segunda conexão SSH só para apagá-lo:
# isso fazia o script ficar parado pedindo a senha novamente após o 100%.
cmd /c "scp -o StrictHostKeyChecking=accept-new ${VPS_USER}@${VPS_IP}:/tmp/backup_sync.tar.gz ./backup_sync.tar.gz"
if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao baixar o dump do banco de dados via SCP!"
    exit 1
}

# Extrair localmente
Write-Host "`n2. Extraindo o backup localmente..." -ForegroundColor Yellow
cmd /c "tar -xzf ./backup_sync.tar.gz"
if (Test-Path ./backup_sync.sql) {
    if (Test-Path $BACKUP_FILE) { Remove-Item $BACKUP_FILE }
    Rename-Item ./backup_sync.sql $BACKUP_FILE
    Remove-Item ./backup_sync.tar.gz
} else {
    Write-Error "Erro ao extrair o backup localmente!"
    exit 1
}

Write-Host "✅ Backup salvo localmente como: $BACKUP_FILE" -ForegroundColor Green

# 3. Baixar regras_pontuacao.sql via SCP
Write-Host "`n3. Baixando regras_pontuacao.sql via SCP..." -ForegroundColor Yellow
scp -o StrictHostKeyChecking=accept-new "$VPS_USER`@$VPS_IP`:$REMOTE_PATH/regras_pontuacao.sql" ./regras_pontuacao.sql

if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️ Aviso: regras_pontuacao.sql não pôde ser baixado. O script prosseguirá." -ForegroundColor Red
} else {
    Write-Host "✅ regras_pontuacao.sql baixado com sucesso!" -ForegroundColor Green
}

# 4. Verificar se o Docker local está rodando e iniciar se necessário
Write-Host "`n4. Verificando o container local mysql-db..." -ForegroundColor Yellow
$containerStatus = docker inspect -f '{{.State.Running}}' mysql-db 2>$null

if ($containerStatus -ne "true") {
    Write-Host "Container local mysql-db não está rodando. Iniciando docker compose..." -ForegroundColor Yellow
    docker compose up -d db
    Write-Host "Aguardando 8 segundos para o banco inicializar totalmente..." -ForegroundColor Yellow
    Start-Sleep -Seconds 8
} else {
    Write-Host "✅ Container local mysql-db já está rodando." -ForegroundColor Green
}

# 5. Restaurar o banco localmente
Write-Host "`n5. Restaurando o dump no container local mysql-db..." -ForegroundColor Yellow
# Usamos cmd /c para redirecionamento nativo < que evita problemas de encoding do PowerShell
$previousMysqlPwd = $env:MYSQL_PWD
$env:MYSQL_PWD = $DB_PASS
try {
    cmd /c "docker exec -e MYSQL_PWD -i mysql-db mysql -u $DB_USER $DB_NAME < $BACKUP_FILE"
} finally {
    $env:MYSQL_PWD = $previousMysqlPwd
}

if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao restaurar o banco de dados no container local!"
    exit 1
}
Write-Host "✅ Banco restaurado com sucesso!" -ForegroundColor Green

# 6. Aplicar migrations que ainda existam somente no código local
Write-Host "`n6. Aplicando migrations fiscais pendentes..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Error "Erro ao aplicar migrations após a restauração!"
    exit 1
}
Write-Host "✅ Schema local atualizado!" -ForegroundColor Green

# 7. Restaurar regras de pontuação localmente
if (Test-Path ./regras_pontuacao.sql) {
    Write-Host "`n7. Restaurando regras_pontuacao.sql no container local..." -ForegroundColor Yellow
    $previousMysqlPwd = $env:MYSQL_PWD
    $env:MYSQL_PWD = $DB_PASS
    try {
        cmd /c "docker exec -e MYSQL_PWD -i mysql-db mysql -f -u $DB_USER $DB_NAME < ./regras_pontuacao.sql"
    } finally {
        $env:MYSQL_PWD = $previousMysqlPwd
    }

    if ($LASTEXITCODE -ne 0) {
        Write-Host "⚠️ Algumas regras ou triggers já existiam no banco, mas o restante foi aplicado." -ForegroundColor Yellow
    } else {
        Write-Host "✅ Regras de pontuação aplicadas com sucesso!" -ForegroundColor Green
    }
}

Write-Host "`n====================================================================" -ForegroundColor Green
Write-Host "                  PROCESSO CONCLUÍDO COM SUCESSO!                   " -ForegroundColor Green
Write-Host "====================================================================" -ForegroundColor Green
