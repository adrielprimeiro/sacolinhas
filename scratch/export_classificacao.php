<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::table('classificacao_financeira')->orderBy('id')->get();

$lines = [];
foreach ($rows as $r) {
    $nome   = addslashes($r->nome);
    $desc   = addslashes($r->descricao ?? '');
    $pai    = $r->id_pai ? $r->id_pai : 'NULL';
    $freq   = $r->frequencia ? "'{$r->frequencia}'" : 'NULL';
    $area   = $r->area_finalidade ?? 'geral';
    $lines[] = "({$r->id}, {$r->user_id}, '{$nome}', '{$r->codigo_contabil}', '{$r->tipo_natureza}', '{$r->nivel}', {$pai}, '{$area}', {$freq}, '{$desc}', '{$r->created_at}', '{$r->updated_at}')";
}

$sql  = "-- Classificação Financeira\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "TRUNCATE TABLE classificacao_financeira;\n";
$sql .= "SET FOREIGN_KEY_CHECKS=1;\n\n";
$sql .= "INSERT INTO classificacao_financeira (id, user_id, nome, codigo_contabil, tipo_natureza, nivel, id_pai, area_finalidade, frequencia, descricao, created_at, updated_at) VALUES\n";
$sql .= implode(",\n", $lines) . ";\n";

file_put_contents(__DIR__ . '/classificacao_financeira.sql', $sql);
echo "Arquivo gerado: scratch/classificacao_financeira.sql\n";
echo "Total de registros: " . count($lines) . "\n";
