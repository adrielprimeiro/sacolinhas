<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Sheets;
use PDO;

class ImportItemsCommand extends Command
{
    protected $signature = 'import:items {spreadsheetId? : ID da planilha (default: 1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0)}';
    protected $description = 'Importa items da planilha Google Sheets para BD';

    public function handle()
    {
        $spreadsheetId = $this->argument('spreadsheetId') ?: '1-yyHZsaA3rfwpfC_gCXCV7952z4qKgE_LcBjKNV1ZG0';
        $range = 'Estoque';

        $this->info("🚀 Iniciando importação para {$spreadsheetId}...");

        // Google Sheets (seu código exato)
        $client = new Client();
        $client->setAuthConfig('/var/www/storage/app/google/service-account.json');
        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $service = new Sheets($client);
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        $values = $response->getValues();

        // MySQL (seu código exato)
        $pdo = new PDO('mysql:host=db;dbname=sacolinhas_db', 'sacolinhas_user', 'SenhaSuperSegura123!');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "INSERT INTO items (codigo, nome_do_produto, custo, codigo_da_categoria, marca, modelo, estado, cor, tamanho, preco, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE nome_do_produto = VALUES(nome_do_produto), custo = VALUES(custo), codigo_da_categoria = VALUES(codigo_da_categoria), marca = VALUES(marca), modelo = VALUES(modelo), estado = VALUES(estado), cor = VALUES(cor), tamanho = VALUES(tamanho), preco = VALUES(preco), updated_at = NOW()";

        $stmt = $pdo->prepare($sql);

        $headers = $values[0];
        $imported = 0;
        $errors = 0;

        $this->info("📊 Total de linhas: " . (count($values) - 1));
        $this->info("📋 Colunas: " . implode(', ', $headers));

        for ($i = 1; $i < count($values); $i++) {
            $row = $values[$i];
            
            try {
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
                    $this->info("✅ Processados: $imported");
                }
                
            } catch (\Exception $e) {
                $errors++;
                if ($errors < 5) {
                    $this->error("❌ Erro linha $i: " . $e->getMessage());
                }
            }
        }

        $this->info("\n🎉 Importação concluída!");
        $this->info("✅ Importados: $imported");
        $this->info("❌ Erros: $errors");

        // Retorna para API
        return [
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}