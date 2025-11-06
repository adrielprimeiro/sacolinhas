<?php
require_once "/var/www/vendor/autoload.php";

use Google\Client;
use Google\Service\Sheets;

echo "👥 Iniciando importação de clientes para users...\n";

// Configuração
$spreadsheetId = "1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0";
$range = "Clientes";

// Conectar Google Sheets
$client = new Client();
$client->setAuthConfig("/var/www/storage/app/google/service-account.json");
$client->addScope(Sheets::SPREADSHEETS_READONLY);

$service = new Sheets($client);
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

// Conectar MySQL
$pdo = new PDO("mysql:host=db;dbname=sacolinhas_db", "sacolinhas_user", "SenhaSuperSegura123!");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SQL simplificado com campos essenciais primeiro
$sql = "INSERT INTO users (
    name, email, password, phone, role,
    codigo_cliente, nome_cliente, cpf, endereco, cidade, estado, cep,
    telefone_principal, total_pedidos, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    phone = VALUES(phone),
    endereco = VALUES(endereco),
    cidade = VALUES(cidade),
    estado = VALUES(estado),
    cep = VALUES(cep),
    telefone_principal = VALUES(telefone_principal),
    total_pedidos = VALUES(total_pedidos),
    updated_at = NOW()";

$stmt = $pdo->prepare($sql);

$headers = $values[0];
$imported = 0;
$errors = 0;

echo "📊 Total de linhas: " . (count($values) - 1) . "\n";
echo "📋 Colunas: " . implode(", ", $headers) . "\n\n";

// Processar dados linha por linha
for ($i = 1; $i < count($values); $i++) {
    $row = $values[$i];
    
    try {
        // Mapear dados essenciais
        $codigo_cliente = !empty($row[0]) ? intval($row[0]) : null;
        $nome_cliente = $row[2] ?? "";
        $cpf = !empty($row[5]) ? str_replace("'", "", $row[5]) : null;
        $endereco = $row[6] ?? "";
        $cidade = $row[7] ?? "";
        $estado = $row[8] ?? "";
        $cep = $row[9] ?? "";
        $email = $row[10] ?? "";
        $telefone_principal = !empty($row[11]) ? $row[11] : null;
        $total_pedidos = !empty($row[15]) ? intval($row[15]) : 0;
        
        // Validações básicas
        if (empty($nome_cliente)) {
            echo "⚠️ Linha $i: Nome vazio, pulando...\n";
            continue;
        }
        
        if (empty($email)) {
            echo "⚠️ Linha $i: Email vazio para $nome_cliente, pulando...\n";
            continue;
        }
        
        // Preparar dados para inserção
        $name = $nome_cliente;
        $password = password_hash("123456", PASSWORD_DEFAULT);
        $phone = $telefone_principal;
        $role = "client";
        
        // Executar com exatamente 14 parâmetros
        $stmt->execute([
            $name,              // 1
            $email,             // 2
            $password,          // 3
            $phone,             // 4
            $role,              // 5
            $codigo_cliente,    // 6
            $nome_cliente,      // 7
            $cpf,               // 8
            $endereco,          // 9
            $cidade,            // 10
            $estado,            // 11
            $cep,               // 12
            $telefone_principal,// 13
            $total_pedidos      // 14
        ]);
        
        $imported++;
        echo "✅ Cliente $imported: $nome_cliente ($email)\n";
        
    } catch (Exception $e) {
        $errors++;
        echo "❌ Erro linha $i ($nome_cliente): " . $e->getMessage() . "\n";
        if ($errors > 5) {
            echo "⚠️ Muitos erros, parando...\n";
            break;
        }
    }
}

echo "\n🎉 Importação de clientes concluída!\n";
echo "✅ Importados: $imported\n";
echo "❌ Erros: $errors\n";
echo "🔑 Senha padrão para todos: 123456\n";
?>