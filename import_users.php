<?php
require_once "/var/www/vendor/autoload.php";

use Google\Client;
use Google\Service\Sheets;

echo "Importacao com MAPEAMENTO CORRETO das colunas\n";

$client = new Client();
$client->setAuthConfig("/var/www/storage/app/google/service-account.json");
$client->addScope(Sheets::SPREADSHEETS_READONLY);
$service = new Sheets($client);

$response = $service->spreadsheets_values->get("1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0", "Clientes");
$values = $response->getValues();

$pdo = new PDO("mysql:host=db;dbname=sacolinhas_db;charset=utf8mb4", "sacolinhas_user", "SenhaSuperSegura123!");
$pdo->exec("SET NAMES utf8mb4");

echo "Atualizando com mapeamento correto...\n";

$stmt = $pdo->prepare("
    UPDATE users SET 
        codigo_cliente = ?,
        data_cadastro = ?,
        name = ?,
        apelido = ?,
        rg = ?,
        cpf = ?,
        endereco = ?,
        numero_endereco = ?,
        complemento = ?,
        bairro = ?,
        cidade = ?,
        estado = ?,
        cep = ?,
        telefone_principal = ?,
        telefone_2 = ?,
        ultima_compra = ?,
        ultima_visita = ?,
        total_pedidos = ?,
        observacao_cliente = ?,
        tipo_cliente = ?,
        data_nascimento = ?,
        pais = ?,
        bloqueado = ?,
        sexo = ?,
        remember_token = ?,
        nome_cliente = ?
    WHERE email = ?
");

function validarData($data) {
    if (empty($data)) return null;
    
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "$year-$month-$day";
    }
    
    return null;
}

function validarDataTimestamp($data) {
    if (empty($data)) return null;
    
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "$year-$month-$day 00:00:00";
    }
    
    return null;
}

$updated = 0;
$errors = 0;

for ($i = 1; $i < count($values); $i++) {
    $row = $values[$i];
    $email = trim($row[10] ?? '');
    
    if ($email) {
        try {
            $codigo_cliente = !empty($row[0]) ? intval($row[0]) : null;
            $data_cadastro = !empty($row[1]) ? validarDataTimestamp($row[1]) : null;
            $name = !empty($row[2]) ? trim($row[2]) : null;
            $apelido = !empty($row[3]) ? trim($row[3]) : null;
            $rg = !empty($row[4]) ? trim($row[4]) : null;
            $cpf = !empty($row[5]) ? preg_replace('/[^0-9]/', '', $row[5]) : null;
            $endereco = !empty($row[6]) ? trim($row[6]) : null;
            $cidade = !empty($row[7]) ? trim($row[7]) : null;
            $estado = !empty($row[8]) ? strtoupper(trim($row[8])) : null;
            $cep = !empty($row[9]) ? preg_replace('/[^0-9]/', '', $row[9]) : null;
            $telefone_principal = !empty($row[11]) ? preg_replace('/[^0-9]/', '', $row[11]) : null;
            $telefone_2 = !empty($row[12]) ? preg_replace('/[^0-9]/', '', $row[12]) : null;
            $ultima_compra = !empty($row[13]) ? validarDataTimestamp($row[13]) : null;
            $ultima_visita = !empty($row[14]) ? validarDataTimestamp($row[14]) : null;
            $total_pedidos = !empty($row[15]) ? intval($row[15]) : 0;
            $observacao_cliente = !empty($row[16]) ? trim($row[16]) : null;
            $tipo_cliente = !empty($row[17]) ? trim($row[17]) : null;
            $data_nascimento = !empty($row[18]) ? validarData($row[18]) : null;
            $numero_endereco = !empty($row[19]) ? trim($row[19]) : null;
            $complemento = !empty($row[20]) ? trim($row[20]) : null;
            $bairro = !empty($row[21]) ? trim($row[21]) : null;
            $pais = !empty($row[22]) ? trim($row[22]) : 'Brasil';
            $bloqueado = !empty($row[23]) ? intval($row[23]) : 0;
            $sexo_raw = !empty($row[24]) ? trim($row[24]) : null;
            $remember_token = !empty($row[25]) ? trim($row[25]) : null;
            $nome_cliente = !empty($row[26]) ? trim($row[26]) : null;
            
            $sexo = null;
            if ($sexo_raw === '0') $sexo = 'F';
            elseif ($sexo_raw === '1') $sexo = 'M';
            
            $stmt->execute([
                $codigo_cliente, $data_cadastro, $name, $apelido, $rg, $cpf,
                $endereco, $numero_endereco, $complemento, $bairro, $cidade, $estado, $cep,
                $telefone_principal, $telefone_2, $ultima_compra, $ultima_visita, $total_pedidos,
                $observacao_cliente, $tipo_cliente, $data_nascimento, $pais, $bloqueado, $sexo,
                $remember_token, $nome_cliente, $email
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updated++;
                echo "$updated: Atualizado $name ($cidade/$estado)\n";
            }
            
        } catch (Exception $e) {
            $errors++;
            echo "ERRO linha $i: " . $e->getMessage() . "\n";
        }
    }
}

echo "Fim! Atualizados: $updated | Erros: $errors\n";
?>