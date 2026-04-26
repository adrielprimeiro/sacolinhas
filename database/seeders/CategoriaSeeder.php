<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        // INSERT IGNORE: insere apenas categorias que não existem ainda.
        // Preserva os vínculos de categoria_item no servidor.
        $categorias = [
            ['id' => 1, 'name' => 'Feminino', 'slug' => 'feminino', 'parent_id' => null, 'valor_desconto' => 10.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:26', 'updated_at' => '2026-04-15 22:10:26'],
            ['id' => 2, 'name' => 'Roupas', 'slug' => 'feminino-roupas', 'parent_id' => 1, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:26', 'updated_at' => '2026-04-15 22:10:26'],
            ['id' => 3, 'name' => 'Vestidos', 'slug' => 'feminino-roupas-vestidos', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:26', 'updated_at' => '2026-04-15 22:10:26'],
            ['id' => 4, 'name' => 'Calças', 'slug' => 'feminino-roupas-calcas', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 5, 'name' => 'Camisetas', 'slug' => 'feminino-roupas-camisetas', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 6, 'name' => 'Casacos', 'slug' => 'feminino-roupas-casacos', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 7, 'name' => 'Saias', 'slug' => 'feminino-roupas-saias', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 8, 'name' => 'Blusas', 'slug' => 'feminino-roupas-blusas', 'parent_id' => 2, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 9, 'name' => 'Calçados', 'slug' => 'feminino-calcados', 'parent_id' => 1, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 10, 'name' => 'Tênis', 'slug' => 'feminino-calcados-tenis', 'parent_id' => 9, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 11, 'name' => 'Sandálias', 'slug' => 'feminino-calcados-sandalias', 'parent_id' => 9, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 12, 'name' => 'Botas', 'slug' => 'feminino-calcados-botas', 'parent_id' => 9, 'valor_desconto' => 7.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 13, 'name' => 'Scarpins', 'slug' => 'feminino-calcados-scarpins', 'parent_id' => 9, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 14, 'name' => 'Bolsas', 'slug' => 'feminino-bolsas', 'parent_id' => 1, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 15, 'name' => 'Mochilas', 'slug' => 'feminino-bolsas-mochilas', 'parent_id' => 14, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 16, 'name' => 'Clutches', 'slug' => 'feminino-bolsas-clutches', 'parent_id' => 14, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 17, 'name' => 'Bolsas de Ombro', 'slug' => 'feminino-bolsas-bolsas-de-ombro', 'parent_id' => 14, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 18, 'name' => 'Acessórios', 'slug' => 'feminino-acessorios', 'parent_id' => 1, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 19, 'name' => 'Cintos', 'slug' => 'feminino-acessorios-cintos', 'parent_id' => 18, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 20, 'name' => 'Óculos de Sol', 'slug' => 'feminino-acessorios-oculos-de-sol', 'parent_id' => 18, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 21, 'name' => 'Bijuterias', 'slug' => 'feminino-acessorios-bijuterias', 'parent_id' => 18, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 22, 'name' => 'Masculino', 'slug' => 'masculino', 'parent_id' => null, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 23, 'name' => 'Roupas', 'slug' => 'masculino-roupas', 'parent_id' => 22, 'valor_desconto' => 50.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 24, 'name' => 'Camisas', 'slug' => 'masculino-roupas-camisas', 'parent_id' => 23, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 25, 'name' => 'Polos', 'slug' => 'masculino-roupas-polos', 'parent_id' => 23, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 26, 'name' => 'Bermudas', 'slug' => 'masculino-roupas-bermudas', 'parent_id' => 23, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 27, 'name' => 'Calças Jeans', 'slug' => 'masculino-roupas-calcas-jeans', 'parent_id' => 23, 'valor_desconto' => 5.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 28, 'name' => 'Calçados', 'slug' => 'masculino-calcados', 'parent_id' => 22, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 29, 'name' => 'Sapato Social', 'slug' => 'masculino-calcados-sapato-social', 'parent_id' => 28, 'valor_desconto' => 25.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 30, 'name' => 'Sapatênis', 'slug' => 'masculino-calcados-sapatenis', 'parent_id' => 28, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 31, 'name' => 'Chuteiras', 'slug' => 'masculino-calcados-chuteiras', 'parent_id' => 28, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 32, 'name' => 'Acessórios', 'slug' => 'masculino-acessorios', 'parent_id' => 22, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 33, 'name' => 'Relógios', 'slug' => 'masculino-acessorios-relogios', 'parent_id' => 32, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 34, 'name' => 'Bonés', 'slug' => 'masculino-acessorios-bones', 'parent_id' => 32, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 35, 'name' => 'Carteiras', 'slug' => 'masculino-acessorios-carteiras', 'parent_id' => 32, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 36, 'name' => 'Infantil', 'slug' => 'infantil', 'parent_id' => null, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 37, 'name' => 'Menina', 'slug' => 'infantil-menina', 'parent_id' => 36, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 38, 'name' => 'Roupas', 'slug' => 'infantil-menina-roupas', 'parent_id' => 37, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 39, 'name' => 'Conjuntos', 'slug' => 'infantil-menina-roupas-conjuntos', 'parent_id' => 38, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 40, 'name' => 'Vestidos Infantis', 'slug' => 'infantil-menina-roupas-vestidos-infantis', 'parent_id' => 38, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 41, 'name' => 'Pijamas', 'slug' => 'infantil-menina-roupas-pijamas', 'parent_id' => 38, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 42, 'name' => 'Calçados', 'slug' => 'infantil-menina-calcados', 'parent_id' => 37, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 43, 'name' => 'Sapatilhas', 'slug' => 'infantil-menina-calcados-sapatilhas', 'parent_id' => 42, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 44, 'name' => 'Tênis LED', 'slug' => 'infantil-menina-calcados-tenis-led', 'parent_id' => 42, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 45, 'name' => 'Menino', 'slug' => 'infantil-menino', 'parent_id' => 36, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 46, 'name' => 'Roupas', 'slug' => 'infantil-menino-roupas', 'parent_id' => 45, 'valor_desconto' => 15.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 47, 'name' => 'Bodys', 'slug' => 'infantil-menino-roupas-bodys', 'parent_id' => 46, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 48, 'name' => 'Macacões', 'slug' => 'infantil-menino-roupas-macacoes', 'parent_id' => 46, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 49, 'name' => 'Regatas', 'slug' => 'infantil-menino-roupas-regatas', 'parent_id' => 46, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 50, 'name' => 'Calçados', 'slug' => 'infantil-menino-calcados', 'parent_id' => 45, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 51, 'name' => 'Papetes', 'slug' => 'infantil-menino-calcados-papetes', 'parent_id' => 50, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 52, 'name' => 'Tênis de Rodinha', 'slug' => 'infantil-menino-calcados-tenis-de-rodinha', 'parent_id' => 50, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 53, 'name' => 'Casa', 'slug' => 'casa', 'parent_id' => null, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 54, 'name' => 'Cama', 'slug' => 'casa-cama', 'parent_id' => 53, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 55, 'name' => 'Jogos de Lençol', 'slug' => 'casa-cama-jogos-de-lencol', 'parent_id' => 54, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 56, 'name' => 'Edredons', 'slug' => 'casa-cama-edredons', 'parent_id' => 54, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 57, 'name' => 'Travesseiros', 'slug' => 'casa-cama-travesseiros', 'parent_id' => 54, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:27', 'updated_at' => '2026-04-15 22:10:27'],
            ['id' => 58, 'name' => 'Banho', 'slug' => 'casa-banho', 'parent_id' => 53, 'valor_desconto' => 20.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 59, 'name' => 'Toalhas de Banho', 'slug' => 'casa-banho-toalhas-de-banho', 'parent_id' => 58, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 60, 'name' => 'Roupões', 'slug' => 'casa-banho-roupoes', 'parent_id' => 58, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 61, 'name' => 'Decoração', 'slug' => 'casa-decoracao', 'parent_id' => 53, 'valor_desconto' => 0.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 62, 'name' => 'Almofadas', 'slug' => 'casa-decoracao-almofadas', 'parent_id' => 61, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 63, 'name' => 'Tapetes', 'slug' => 'casa-decoracao-tapetes', 'parent_id' => 61, 'valor_desconto' => 23.00, 'tipo_desconto' => 'porcentagem', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
            ['id' => 64, 'name' => 'Velas Aromáticas', 'slug' => 'casa-decoracao-velas-aromaticas', 'parent_id' => 61, 'valor_desconto' => 0.00, 'tipo_desconto' => 'fixo', 'created_at' => '2026-04-15 22:10:28', 'updated_at' => '2026-04-15 22:10:28'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->insertOrIgnore($categoria);
        }
    }
}