<?php

class GoogleSheetsImporter
{
    private $accessToken;
    private $pdo;

    public function __construct($serviceAccountPath, $dbConfig)
    {
        $this->accessToken = $this->getAccessToken($serviceAccountPath);
        
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function getAccessToken($serviceAccountPath)
    {
        if (!file_exists($serviceAccountPath)) {
            throw new Exception("Arquivo service account não encontrado: {$serviceAccountPath}");
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        
        if (!$serviceAccount) {
            throw new Exception("Erro ao ler arquivo service account");
        }

        // Criar JWT
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/spreadsheets.readonly',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

        // Trocar JWT por access token
        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 30
            ]
        ]);

        $response = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
        
        if ($response === false) {
            throw new Exception('Erro ao obter access token');
        }

        $tokenData = json_decode($response, true);
        
        if (isset($tokenData['error'])) {
            throw new Exception('Erro OAuth: ' . $tokenData['error_description']);
        }

        return $tokenData['access_token'];
    }

    public function getSpreadsheetData($spreadsheetId, $range = 'Sheet1')
    {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . urlencode($range);
        $url .= "?majorDimension=ROWS&valueRenderOption=FORMATTED_VALUE";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$this->accessToken}\r\n" .
                           "User-Agent: Sacolinhas-Importer/1.0\r\n",
                'timeout' => 30
            ]
        ]);

        echo "🔍 Buscando dados: {$range}\n";
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Erro ao buscar dados da planilha');
        }

        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception('Erro da API Google: ' . $data['error']['message']);
        }

        return $data['values'] ?? [];
    }

    public function getAllSheets($spreadsheetId)
    {
        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}?fields=sheets.properties";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$this->accessToken}\r\n",
                'timeout' => 30
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Erro ao buscar informações da planilha');
        }

        $data = json_decode($response, true);
        
        if (isset($data['error'])) {
            throw new Exception('Erro da API Google: ' . $data['error']['message']);
        }

        $sheets = [];
        if (isset($data['sheets'])) {
            foreach ($data['sheets'] as $sheet) {
                $sheets[] = [
                    'title' => $sheet['properties']['title'],
                    'id' => $sheet['properties']['sheetId'],
                    'index' => $sheet['properties']['index']
                ];
            }
        }

        return $sheets;
    }

    public function testConnection()
    {
        echo "🔐 Testando conexão com Service Account...\n";
        
        try {
            // Testar com planilha pública
            $testId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
            $url = "https://sheets.googleapis.com/v4/spreadsheets/{$testId}?fields=properties.title";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer {$this->accessToken}\r\n",
                    'timeout' => 10
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            $data = json_decode($response, true);
            
            if (isset($data['error'])) {
                echo "❌ Erro: " . $data['error']['message'] . "\n";
                return false;
            }
            
            echo "✅ Conexão OK! Planilha teste: " . $data['properties']['title'] . "\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Erro: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function importToTable($data, $tableName, $mapping = [], $dryRun = false)
    {
        if (empty($data)) {
            throw new Exception('Nenhum dado para importar');
        }

        echo "📊 Processando " . count($data) . " linhas...\n";

        $headers = array_shift($data); // Primeira linha como cabeçalho
        echo "📋 Colunas encontradas: " . implode(', ', $headers) . "\n";

        // Criar mapeamento se fornecido
        $columnMapping = [];
        if (!empty($mapping)) {
            foreach ($mapping as $map) {
                if (strpos($map, ':') !== false) {
                    list($source, $target) = explode(':', $map, 2);
                    $columnMapping[trim($source)] = trim($target);
                }
            }
        }

        $processedData = [];

        foreach ($data as $rowIndex => $row) {
            $rowData = [];
            $hasData = false;

            foreach ($headers as $index => $header) {
                $value = isset($row[$index]) ? trim($row[$index]) : null;
                
                if ($value === '' || $value === '-' || $value === 'N/A') {
                    $value = null;
                }
                
                // Usar mapeamento se disponível
                if (isset($columnMapping[$header])) {
                    $columnName = $columnMapping[$header];
                } else {
                    $columnName = $this->sanitizeColumnName($header);
                }
                
                $rowData[$columnName] = $value;
                
                if (!empty($value)) {
                    $hasData = true;
                }
            }

            if ($hasData) {
                $rowData['created_at'] = date('Y-m-d H:i:s');
                $rowData['updated_at'] = date('Y-m-d H:i:s');
                $processedData[] = $rowData;
            }
        }

        if (empty($processedData)) {
            throw new Exception('Nenhum dado válido encontrado');
        }

        echo "✅ " . count($processedData) . " linhas válidas processadas\n";

        if ($dryRun) {
            echo "\n🧪 MODO DRY-RUN - Dados não serão salvos\n";
            echo "📋 Amostra dos dados (primeiras 3 linhas):\n";
            
            $sample = array_slice($processedData, 0, 3);
            foreach ($sample as $i => $row) {
                echo "\nLinha " . ($i + 1) . ":\n";
                foreach ($row as $col => $val) {
                    echo "  {$col}: " . ($val ?? 'NULL') . "\n";
                }
            }
            return count($processedData);
        }

        // Verificar se tabela existe
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        if (!$stmt->fetch()) {
            throw new Exception("Tabela '{$tableName}' não existe!");
        }

        // Inserir dados
        $columns = array_keys($processedData[0]);
        $placeholders = ':' . implode(', :', $columns);
        
        $sql = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ") VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE ";
        
        $updateClauses = [];
        foreach ($columns as $col) {
            if (!in_array($col, ['created_at'])) {
                $updateClauses[] = "{$col} = VALUES({$col})";
            }
        }
        $sql .= implode(', ', $updateClauses);

        $stmt = $this->pdo->prepare($sql);

        echo "💾 Inserindo dados na tabela {$tableName}...\n";

        $imported = 0;
        $errors = 0;

        foreach ($processedData as $i => $row) {
            try {
                $stmt->execute($row);
                $imported++;
                
                if ($imported % 100 == 0) {
                    echo "  Processadas: {$imported} linhas\n";
                }
            } catch (PDOException $e) {
                $errors++;
                echo "❌ Erro linha " . ($i + 1) . ": " . $e->getMessage() . "\n";
                
                if ($errors > 5) {
                    echo "⚠️ Muitos erros, parando importação...\n";
                    break;
                }
            }
        }

        return $imported;
    }

    private function sanitizeColumnName($header)
    {
        $name = strtolower(trim($header));
        $name = preg_replace('/[^a-z0-9]+/', '_', $name);
        $name = trim($name, '_');
        
        return $name ?: 'coluna_sem_nome';
    }
}

// Script principal
echo "🛍️ Sacolinhas - Importador Google Sheets\n";
echo "==========================================\n\n";

try {
    $config = [
        'service_account' => '/var/www/storage/app/google/service-account.json',
        'db' => [
            'host' => 'db',  // Nome do container MySQL
            'database' => 'sacolinhas_db',
            'username' => 'sacolinhas_user',
            'password' => 'SenhaSuperSegura123!'
        ]
    ];
    
    $importer = new GoogleSheetsImporter($config['service_account'], $config['db']);
    
    // Verificar argumentos
    if ($argc < 2) {
        echo "Uso:\n";
        echo "  php import_sheets.php test                           # Testar conexão\n";
        echo "  php import_sheets.php list SPREADSHEET_ID           # Listar abas\n";
        echo "  php import_sheets.php import SPREADSHEET_ID TABELA [RANGE] [--mapping] [--dry-run]\n\n";
        echo "Exemplos:\n";
        echo "  php import_sheets.php test\n";
        echo "  php import_sheets.php list 1ABC123...\n";
        echo "  php import_sheets.php import 1ABC123... items Estoque --dry-run\n";
        echo "  php import_sheets.php import 1ABC123... items Estoque --mapping 'Código:codigo,Nome:nome'\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    if ($command === 'test') {
        $importer->testConnection();
        
    } elseif ($command === 'list') {
        if ($argc < 3) {
            echo "❌ ID da planilha é obrigatório\n";
            exit(1);
        }
        
        $spreadsheetId = $argv[2];
        echo "📋 Listando abas da planilha...\n";
        
        $sheets = $importer->getAllSheets($spreadsheetId);
        
        echo "\nAbas encontradas:\n";
        foreach ($sheets as $sheet) {
            echo "  - {$sheet['title']} (ID: {$sheet['id']})\n";
        }
        
    } elseif ($command === 'import') {
        if ($argc < 4) {
            echo "❌ ID da planilha e nome da tabela são obrigatórios\n";
            exit(1);
        }
        
        $spreadsheetId = $argv[2];
        $tableName = $argv[3];
        $range = $argv[4] ?? 'Sheet1';
        $dryRun = in_array('--dry-run', $argv);
        
        // Processar mapeamento
        $mapping = [];
        $mappingIndex = array_search('--mapping', $argv);
        if ($mappingIndex !== false && isset($argv[$mappingIndex + 1])) {
            $mappingStr = $argv[$mappingIndex + 1];
            $mapping = explode(',', $mappingStr);
        }
        
        echo "📊 Importando planilha...\n";
        echo "  ID: {$spreadsheetId}\n";
        echo "  Range: {$range}\n";
        echo "  Tabela: {$tableName}\n";
        echo "  Modo: " . ($dryRun ? 'DRY-RUN' : 'PRODUÇÃO') . "\n\n";
        
        $data = $importer->getSpreadsheetData($spreadsheetId, $range);
        $imported = $importer->importToTable($data, $tableName, $mapping, $dryRun);
        
        echo "\n✅ Processo concluído!\n";
        echo "📊 Total processado: {$imported} registros\n";
        
    } else {
        echo "❌ Comando inválido: {$command}\n";
        echo "💡 Use: test, list ou import\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>