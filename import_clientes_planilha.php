<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

require_once "/var/www/vendor/autoload.php";
use Google\Client;
use Google\Service\Sheets;

echo "=== IMPORTACAO COMPLETA COM UPSERT ===\n";
$start_time = microtime(true);

// Conectar Google Sheets
$client = new Client();
$client->setAuthConfig("/var/www/storage/app/google/service-account.json");
$client->addScope(Sheets::SPREADSHEETS_READONLY);
$service = new Sheets($client);

echo "Carregando dados do Google Sheets...\n";
$response = $service->spreadsheets_values->get("1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0", "Clientes");
$values = $response->getValues();
echo "Total de linhas: " . count($values) . "\n";

// Conectar MySQL
$pdo = new PDO("mysql:host=db;dbname=sacolinhas_db;charset=utf8mb4", "sacolinhas_user", "SenhaSuperSegura123!");
$pdo->exec("SET NAMES utf8mb4");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// UPSERT: Insere novo OU atualiza existente
$stmt = $pdo->prepare("
    INSERT INTO users (
        email, name, password, role, created_at, updated_at,
        codigo_cliente, cidade, estado, telefone_principal, cpf, endereco, cep
    ) VALUES (
        ?, ?, ?, 'client', NOW(), NOW(),
        ?, ?, ?, ?, ?, ?, ?
    ) ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        codigo_cliente = VALUES(codigo_cliente),
        cidade = VALUES(cidade),
        estado = VALUES(estado),
        telefone_principal = VALUES(telefone_principal),
        cpf = VALUES(cpf),
        endereco = VALUES(endereco),
        cep = VALUES(cep),
        updated_at = NOW()
");

$processed = 0;
$inserted = 0;
$updated = 0;
$errors = 0;

echo "Processando registros (novos + atualizações)...\n";

for ($i = 1; $i < count($values); $i++) {
    $row = $values[$i];
    $email = trim($row[10] ?? '');
    
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Verificar se já existe
            $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $check->execute([$email]);
            $exists = $check->fetchColumn() > 0;
            
            $nome = trim($row[2] ?? '');
            $codigo = !empty($row[0]) ? intval($row[0]) : null;
            $cidade = !empty($row[7]) ? trim($row[7]) : null;
            $estado = !empty($row[8]) ? strtoupper(trim($row[8])) : null;
            $telefone = !empty($row[11]) ? preg_replace('/[^0-9]/', '', $row[11]) : null;
            $cpf = !empty($row[5]) ? preg_replace('/[^0-9]/', '', $row[5]) : null;
            $endereco = !empty($row[6]) ? trim($row[6]) : null;
            $cep = !empty($row[9]) ? preg_replace('/[^0-9]/', '', $row[9]) : null;
            
            // Validar estado (máximo 2 caracteres)
            if ($estado && strlen($estado) > 2) {
                $estado = substr($estado, 0, 2);
            }
            
            $password = password_hash("123", PASSWORD_DEFAULT);
            
            $stmt->execute([
                $email, $nome, $password,
                $codigo, $cidade, $estado, $telefone, $cpf, $endereco, $cep
            ]);
            
            if ($exists) {
                $updated++;
                $action = "ATUALIZADO";
            } else {
                $inserted++;
                $action = "INSERIDO";
            }
            
            $processed++;
            
            if ($processed % 500 === 0) {
                $percent = round(($i / (count($values) - 1)) * 100, 1);
            }
            
        } catch (Exception $e) {
            $errors++;
            if ($errors <= 10) {
                echo "ERRO linha $i ($nome): " . $e->getMessage() . "\n";
            }
        }
    }
}

$end_time = microtime(true);
$duration = round($end_time - $start_time, 2);

echo "\n=== RESUMO FINAL ===\n";
echo "Total processados: $processed\n";
echo "Novos inseridos: $inserted\n";
echo "Existentes atualizados: $updated\n";
echo "Erros: $errors\n";
echo "Tempo total: {$duration}s\n";
echo "Velocidade: " . round($processed / $duration, 1) . " registros/segundo\n";
?>
