<?php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

require_once "/var/www/vendor/autoload.php";

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Google\Service\Sheets\ClearValuesRequest;

// ID da live deve ser passado como argumento
if ($argc < 2) {
    echo "Uso: php export_sacolas_google_sheets.php <id_live>\n";
    echo "Exemplo: php export_sacolas_google_sheets.php 10\n";
    exit(1);
}

$id_live = intval($argv[1]);

echo "=== EXPORTACAO DE SACOLAS PARA GOOGLE SHEETS ===\n";
echo "ID da Live: $id_live\n";

// Conectar MySQL
$pdo = new PDO("mysql:host=db;dbname=sacolinhas_db;charset=utf8mb4", "sacolinhas_user", "SenhaSuperSegura123!");
$pdo->exec("SET NAMES utf8mb4");

// 1. Buscar sacolas da live com dados dos itens e clientes
echo "Buscando sacolas da live $id_live...\n";

$query = "
    SELECT 
        i.codigo as id_item,
        i.tamanho,
        i.nome_do_produto,
        i.cor,
        s.price as valor,
        u.remember_token as instagram,
        u.nome_cliente as tiktok,
		u.name
    FROM sacolinhas s
    JOIN items i ON s.item_id = i.id
    JOIN users u ON s.user_id = u.id
    WHERE s.live_id = ?
    ORDER BY s.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$id_live]);
$data_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total de itens encontrados: " . count($data_rows) . "\n";

if (empty($data_rows)) {
    echo "Nenhuma sacola encontrada para esta live!\n";
    exit(1);
}

// 2. Formatar dados para Google Sheets
$formatted_data = [];
foreach ($data_rows as $row) {
    $formatted_data[] = [
        $row['id_item'],              // ColA: ID do Item
        $row['tamanho'] ?? '',         // ColB: Tamanho do item
        $row['nome_do_produto'] ?? '', // ColC: Nome do item
        $row['cor'] ?? '',             // ColD: Cor do item
        $row['valor'] ?? '',           // ColE: Valor do item
        $row['instagram'] ?? '',       // ColF: instagram
        $row['tiktok'] ?? '',           // ColG: tiktok,
		$row['name'] ?? ''           // ColG: tiktok
    ];
}

echo "Total de itens para exportar: " . count($formatted_data) . "\n";

// 3. Conectar Google Sheets
echo "Conectando ao Google Sheets...\n";

$client = new Client();
$client->setAuthConfig("/var/www/storage/app/google/service-account.json");
$client->addScope(Sheets::SPREADSHEETS);
$service = new Sheets($client);

// ID da planilha "Estoque"
$spreadsheetId = "1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0";
$sheetName = "Enviar";

// 4. Limpar dados existentes na aba "Enviar"
echo "Limpando dados existentes na aba '$sheetName'...\n";

try {
    $clearRequest = new ClearValuesRequest();
    $service->spreadsheets_values->clear(
        $spreadsheetId,
        "$sheetName!A:H",
        $clearRequest
    );
    echo "Aba limpa com sucesso!\n";
} catch (Exception $e) {
    echo "Aviso ao limpar: " . $e->getMessage() . "\n";
}

// 5. Inserir cabeçalhos
$headers = [
    ['Código', 'Tamanho', 'Nome Item', 'Cor', 'Valor', 'Instagram', 'Tiktok', 'Nome Cliente']
];

$headerRange = new ValueRange([
    'values' => $headers
]);

$service->spreadsheets_values->update(
    $spreadsheetId,
    "$sheetName!A1:H1",
    $headerRange,
    ['valueInputOption' => 'RAW']
);

echo "Cabeçalhos inseridos!\n";

// 6. Inserir dados em lotes
echo "Inserindo " . count($formatted_data) . " itens na planilha...\n";

$batch_size = 500;
for ($i = 0; $i < count($formatted_data); $i += $batch_size) {
    $batch = array_slice($formatted_data, $i, $batch_size);
    
    $dataRange = new ValueRange([
        'values' => $batch
    ]);
    
    $start_row = $i + 2; // +1 por cabeçalho, +1 porque começa em 1
    $end_row = $start_row + count($batch) - 1;
    
    $service->spreadsheets_values->update(
        $spreadsheetId,
        "$sheetName!A$start_row:H$end_row", // ✅ CORRIGIDO: G → H
        $dataRange,
        ['valueInputOption' => 'RAW']
    );
    
    $percent = round((min($i + $batch_size, count($formatted_data)) / count($formatted_data)) * 100, 1);
    echo "Inseridos: " . min($i + $batch_size, count($formatted_data)) . " [$percent%]\n";
}

echo "\n=== EXPORTACAO CONCLUIDA COM SUCESSO! ===\n";
echo "Total de itens exportados: " . count($formatted_data) . "\n";
echo "Aba: $sheetName\n";
echo "Planilha: Estoque\n";
echo "Live ID: $id_live\n";
?>