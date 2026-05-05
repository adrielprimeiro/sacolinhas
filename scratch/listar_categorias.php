<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cats = DB::table('categorias')->orderBy('id')->get(['id','name','slug','parent_id']);
foreach ($cats as $c) {
    echo $c->id . ' | ' . $c->name . ' => ' . $c->slug . ' (pai: ' . $c->parent_id . ')' . PHP_EOL;
}
