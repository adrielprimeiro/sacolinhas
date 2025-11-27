<?php
require_once '/var/www/vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

echo "🚀 Iniciando importação específica para items...\n";

// Configuração
$spreadsheetId = '1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0';
$range = 'Estoque';

// Conectar Google Sheets
$client = new Client();
$client->setAuthConfig('/var/www/storage/app/google/service-account.json');
$client->addScope(Sheets::SPREADSHEETS_READONLY);

$service = new Sheets($client);
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

// Conectar MySQL
$pdo = new PDO('mysql:host=db;dbname=sacolinhas_db', 'sacolinhas_user', 'SenhaSuperSegura123!');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SQL corrigido
$sql = "INSERT INTO items (codigo, nome_do_produto, custo, codigo_da_categoria, marca, modelo, estado, cor, tamanho, preco, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE nome_do_produto = VALUES(nome_do_produto), custo = VALUES(custo), codigo_da_categoria = VALUES(codigo_da_categoria), marca = VALUES(marca), modelo = VALUES(modelo), estado = VALUES(estado), cor = VALUES(cor), tamanho = VALUES(tamanho), preco = VALUES(preco), updated_at = NOW()";

$stmt = $pdo->prepare($sql);

$headers = $values[0];
$imported = 0;
$errors = 0;

echo "📊 Total de linhas: " . (count($values) - 1) . "\n";
echo "📋 Colunas: " . implode(', ', $headers) . "\n\n";

// Processar dados linha por linha
for ($i = 1; $i < count($values); $i++) {
    $row = $values[$i];
    
    try {
        // Mapear dados baseado na posição conhecida
        $codigo = $row[0] ?? '';
        $custo = !empty($row[1]) ? floatval($row[1]) : null;
        $nome = $row[2] ?? '';
        $categoria = $row[3] ?? '';
        $marca = $row[4] ?? '';
        $modelo = $row[5] ?? '';
        $estado = $row[6] ?? 'novo';
        $cor = $row[7] ?? '';
        $tamanho = $row[8] ?? '';
        $preco = !empty($row[9]) ? floatval($row[9]) : 0;
        
        if (empty($codigo) || empty($nome)) {
            continue;
        }
        
        $stmt->execute([
            $codigo, $nome, $custo, $categoria, $marca, 
            $modelo, $estado, $cor, $tamanho, $preco
        ]);
        
        $imported++;
        
        if ($imported % 100 == 0) {
            echo "✅ Processados: $imported\n";
        }
        
    } catch (Exception $e) {
        $errors++;
        if ($errors < 5) {
            echo "❌ Erro linha $i: " . $e->getMessage() . "\n";
        }
        if ($errors > 10) {
            echo "⚠️ Muitos erros, parando...\n";
            break;
        }
    }
}

echo "\n🎉 Importação concluída!\n";
echo "✅ Importados: $imported\n";
echo "❌ Erros: $errors\n";
?>
