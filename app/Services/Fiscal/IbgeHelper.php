<?php

namespace App\Services\Fiscal;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IbgeHelper
{
    public static array $ufCodes = [
        'RO' => '11', 'AC' => '12', 'AM' => '13', 'RR' => '14', 'PA' => '15', 'AP' => '16', 'TO' => '17',
        'MA' => '21', 'PI' => '22', 'CE' => '23', 'RN' => '24', 'PB' => '25', 'PE' => '26', 'AL' => '27',
        'SE' => '28', 'BA' => '29', 'MG' => '31', 'ES' => '32', 'RJ' => '33', 'SP' => '35', 'PR' => '41',
        'SC' => '42', 'RS' => '43', 'MS' => '50', 'MT' => '51', 'GO' => '52', 'DF' => '53',
    ];

    /**
     * Retorna o código IBGE de 2 dígitos do estado (ex: SC -> 42, SP -> 35).
     */
    public static function getUfCode(?string $uf): string
    {
        $uf = strtoupper(trim($uf ?? ''));
        return self::$ufCodes[$uf] ?? '42'; // Default SC
    }

    /**
     * Busca o código IBGE do município de 7 dígitos pelo CEP ou Nome/UF.
     */
    public static function getCodigoMunicipio(?string $cep, ?string $cidade = null, ?string $uf = null): string
    {
        $cleanCep = preg_replace('/[^0-9]/', '', $cep ?? '');

        if (strlen($cleanCep) === 8) {
            $cacheKey = "ibge_cep_{$cleanCep}";
            $cached = Cache::get($cacheKey);
            if ($cached) {
                return $cached;
            }

            try {
                $response = Http::timeout(3)->get("https://viacep.com.br/ws/{$cleanCep}/json/");
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['ibge'])) {
                        Cache::put($cacheKey, $data['ibge'], now()->addDays(30));
                        return (string) $data['ibge'];
                    }
                }
            } catch (\Exception $e) {
                // Silently fallback
            }
        }

        // Fallbacks conhecidos ou padrão Biguaçu/SC
        if (strtoupper(trim($uf ?? '')) === 'SC' && str_contains(strtolower($cidade ?? ''), 'biguacu')) {
            return '4202305';
        }

        // Padrão do Brechó (Biguaçu - SC: 4202305)
        return '4202305';
    }
}
