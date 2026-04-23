<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa as tabelas para evitar duplicidade
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Categoria::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categorias = [
            'Feminino' => [
                'config' => ['valor_desconto' => 10, 'tipo_desconto' => 'porcentagem'],
                'subs' => [
                    'Roupas' => ['Vestidos', 'Calças', 'Camisetas', 'Casacos', 'Saias', 'Blusas'],
                    'Calçados' => [
                        'config' => ['valor_desconto' => 0],
                        'items' => ['Tênis', 'Sandálias', 'Botas', 'Scarpins']
                    ],
                    'Bolsas' => ['Mochilas', 'Clutches', 'Bolsas de Ombro'],
                    'Acessórios' => ['Cintos', 'Óculos de Sol', 'Bijuterias']
                ]
            ],
            'Masculino' => [
                'subs' => [
                    'Roupas' => [
                        'config' => ['valor_desconto' => 50, 'tipo_desconto' => 'fixo'],
                        'items' => ['Camisas', 'Polos', 'Bermudas', 'Calças Jeans']
                    ],
                    'Calçados' => ['Sapato Social', 'Sapatênis', 'Chuteiras'],
                    'Acessórios' => ['Relógios', 'Bonés', 'Carteiras']
                ]
            ],
            'Infantil' => [
                'subs' => [
                    'Menina' => [
                        'subs' => [
                            'Roupas' => ['Conjuntos', 'Vestidos Infantis', 'Pijamas'],
                            'Calçados' => ['Sapatilhas', 'Tênis LED']
                        ]
                    ],
                    'Menino' => [
                        'subs' => [
                            'Roupas' => [
                                'config' => ['valor_desconto' => 15, 'tipo_desconto' => 'porcentagem'],
                                'items' => ['Bodys', 'Macacões', 'Regatas']
                            ],
                            'Calçados' => ['Papetes', 'Tênis de Rodinha']
                        ]
                    ]
                ]
            ],
            'Casa' => [
                'subs' => [
                    'Cama' => ['Jogos de Lençol', 'Edredons', 'Travesseiros'],
                    'Banho' => [
                        'config' => ['valor_desconto' => 20, 'tipo_desconto' => 'fixo'],
                        'items' => ['Toalhas de Banho', 'Roupões']
                    ],
                    'Decoração' => ['Almofadas', 'Tapetes', 'Velas Aromáticas']
                ]
            ]
        ];

        foreach ($categorias as $nomePai => $dadosPai) {
            $pai = Categoria::create([
                'name' => $nomePai,
                'slug' => Str::slug($nomePai),
                'valor_desconto' => $dadosPai['config']['valor_desconto'] ?? 0,
                'tipo_desconto' => $dadosPai['config']['tipo_desconto'] ?? 'porcentagem',
            ]);

            if (isset($dadosPai['subs'])) {
                foreach ($dadosPai['subs'] as $nomeFilho => $dadosFilho) {
                    $isSimpleArray = !isset($dadosFilho['subs']) && !isset($dadosFilho['items']);
                    $filhoItems = $isSimpleArray ? $dadosFilho : ($dadosFilho['items'] ?? []);
                    $proximosSubs = $dadosFilho['subs'] ?? null;

                    $filho = Categoria::create([
                        'name' => $nomeFilho,
                        'slug' => Str::slug($nomePai . '-' . $nomeFilho),
                        'parent_id' => $pai->id,
                        'valor_desconto' => $dadosFilho['config']['valor_desconto'] ?? 0,
                        'tipo_desconto' => $dadosFilho['config']['tipo_desconto'] ?? 'porcentagem',
                    ]);

                    foreach ($filhoItems as $nomeNeto) {
                        Categoria::create([
                            'name' => $nomeNeto,
                            'slug' => Str::slug($nomePai . '-' . $nomeFilho . '-' . $nomeNeto),
                            'parent_id' => $filho->id,
                            'valor_desconto' => (rand(0, 10) > 8) ? rand(5, 30) : 0,
                            'tipo_desconto' => (rand(0, 1) == 0) ? 'porcentagem' : 'fixo',
                        ]);
                    }

                    if ($proximosSubs) {
                        foreach ($proximosSubs as $nomeNeto => $dadosNeto) {
                            $netoItems = is_array($dadosNeto) ? ($dadosNeto['items'] ?? $dadosNeto) : [$dadosNeto];
                            $neto = Categoria::create([
                                'name' => $nomeNeto,
                                'slug' => Str::slug($nomePai . '-' . $nomeFilho . '-' . $nomeNeto),
                                'parent_id' => $filho->id,
                                'valor_desconto' => $dadosNeto['config']['valor_desconto'] ?? 0,
                                'tipo_desconto' => $dadosNeto['config']['tipo_desconto'] ?? 'porcentagem',
                            ]);

                            if (is_array($netoItems)) {
                                foreach ($netoItems as $nomeBisneto) {
                                    Categoria::create([
                                        'name' => $nomeBisneto,
                                        'slug' => Str::slug($nomePai . '-' . $nomeFilho . '-' . $nomeNeto . '-' . $nomeBisneto),
                                        'parent_id' => $neto->id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}