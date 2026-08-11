<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Lancamento;

$l5786 = Lancamento::find(5786);
$l5787 = Lancamento::find(5787);

if ($l5786) {
    echo "Lanc 5786: Class={$l5786->classificacao_financeira_id} | PessoaID={$l5786->pessoa_id} | PessoaName={$l5786->pessoa?->nome} | UserID={$l5786->pessoa?->user_id} | Desc={$l5786->descricao}\n";
}
if ($l5787) {
    echo "Lanc 5787: Class={$l5787->classificacao_financeira_id} | PessoaID={$l5787->pessoa_id} | PessoaName={$l5787->pessoa?->nome} | UserID={$l5787->pessoa?->user_id} | Desc={$l5787->descricao}\n";
}
