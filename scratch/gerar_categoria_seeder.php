<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::table('categorias')->orderBy('id')->get();

$lines = '';
foreach ($rows as $r) {
    $name      = addslashes($r->name);
    $slug      = addslashes($r->slug ?? '');
    $parent_id = $r->parent_id ? $r->parent_id : 'null';
    $val_desc  = $r->valor_desconto ?? 0;
    $tipo_desc = addslashes($r->tipo_desconto ?? 'porcentagem');
    $descricao = addslashes($r->descricao ?? '');
    $created   = $r->created_at ?? date('Y-m-d H:i:s');
    $updated   = $r->updated_at ?? date('Y-m-d H:i:s');

    $lines .= "            ['id' => {$r->id}, 'name' => '{$name}', 'slug' => '{$slug}', 'parent_id' => {$parent_id}, 'valor_desconto' => {$val_desc}, 'tipo_desconto' => '{$tipo_desc}', 'created_at' => '{$created}', 'updated_at' => '{$updated}'],\n";
}

$seeder = <<<SEEDER
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
        \$categorias = [
{$lines}        ];

        foreach (\$categorias as \$categoria) {
            DB::table('categorias')->insertOrIgnore(\$categoria);
        }
    }
}
SEEDER;

file_put_contents(__DIR__ . '/../database/seeders/CategoriaSeeder.php', $seeder);
echo "CategoriaSeeder.php gerado com " . count($rows) . " categorias (INSERT IGNORE)!\n";
