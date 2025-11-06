<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GOOGLE_SHEETS_API_KEY');
    }

    public function getSpreadsheetData($spreadsheetId, $range = 'Sheet1')
    {
        if (!$this->apiKey) {
            throw new \Exception('GOOGLE_SHEETS_API_KEY não configurada no .env');
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/" . urlencode($range);
        
        try {
            $response = Http::timeout(30)->get($url, [
                'key' => $this->apiKey,
                'majorDimension' => 'ROWS',
                'valueRenderOption' => 'FORMATTED_VALUE'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['values'] ?? [];
            } else {
                $error = $response->json();
                throw new \Exception('Erro na API Google: ' . ($error['error']['message'] ?? $response->body()));
            }
        } catch (\Exception $e) {
            Log::error('Google Sheets API Error', [
                'spreadsheet_id' => $spreadsheetId,
                'range' => $range,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Erro ao buscar dados: " . $e->getMessage());
        }
    }

    public function getSpreadsheetInfo($spreadsheetId)
    {
        if (!$this->apiKey) {
            throw new \Exception('GOOGLE_SHEETS_API_KEY não configurada no .env');
        }

        $url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}";
        
        try {
            $response = Http::timeout(30)->get($url, [
                'key' => $this->apiKey,
                'fields' => 'properties,sheets.properties'
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                $error = $response->json();
                throw new \Exception('Erro na API Google: ' . ($error['error']['message'] ?? $response->body()));
            }
        } catch (\Exception $e) {
            Log::error('Google Sheets Info Error', [
                'spreadsheet_id' => $spreadsheetId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Erro ao buscar informações: " . $e->getMessage());
        }
    }

    public function getAllSheets($spreadsheetId)
    {
        $info = $this->getSpreadsheetInfo($spreadsheetId);
        $sheets = [];
        
        if (isset($info['sheets'])) {
            foreach ($info['sheets'] as $sheet) {
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
        if (!$this->apiKey) {
            return ['success' => false, 'message' => 'API Key não configurada'];
        }

        try {
            // Testar com uma planilha pública do Google
            $testSpreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
            $response = Http::timeout(10)->get(
                "https://sheets.googleapis.com/v4/spreadsheets/{$testSpreadsheetId}",
                ['key' => $this->apiKey, 'fields' => 'properties.title']
            );

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Conexão OK'];
            } else {
                $error = $response->json();
                return [
                    'success' => false, 
                    'message' => $error['error']['message'] ?? 'Erro desconhecido'
                ];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}