<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações Fiscais Padrão (NFePHP)
    |--------------------------------------------------------------------------
    |
    | Definições padrão para emissão de NF-e (Modelo 55) e NFC-e (Modelo 65)
    | no sistema Brechó / Sacolinhas.
    |
    */

    // 1 = Produção, 2 = Homologação (Testes)
    'ambiente' => env('NFE_AMBIENTE', 2),

    // Versão do leiaute da NF-e
    'versao' => '4.00',

    // UF padrão do emitente caso não definido no Brechó
    'uf' => env('STORE_STATE', 'SC'),

    // Dados de contingência e timeouts
    'timeout' => 30,

    // Diretório base para armazenar certificados e XMLs (em storage/app)
    'storage' => [
        'certificados' => 'fiscal/certificados',
        'xmls' => 'fiscal/xmls',
        'danfes' => 'fiscal/danfes',
    ],

    // Parâmetros fiscais padrão para peças de brechó
    'padroes' => [
        'ncm_vestuario' => '61091000',
        'cfop_interno' => '5102',     // Venda de mercadoria adquirida de terceiros no estado
        'cfop_interestadual' => '6102', // Venda de mercadoria para outro estado
        'csosn' => '102',             // Simples Nacional - Tributada sem permissão de crédito (ou 400 - Não tributada)
        'unidade' => 'UN',
        'origem' => 0,                // 0 = Nacional
    ],
];
