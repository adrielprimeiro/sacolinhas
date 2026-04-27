<?php
/**
 * Script de Auto-Categorização de Itens
 * 
 * Associa itens às categorias com base em palavras-chave no nome do produto.
 * Usa a tabela pivô `categoria_item` (many-to-many).
 * Só vincula itens que ainda não têm nenhuma categoria associada.
 * 
 * Executar: php scratch/auto_categorizar_itens.php
 * 
 * Para preview (sem salvar): php scratch/auto_categorizar_itens.php --dry-run
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv ?? []);

if ($dryRun) {
    echo "=== MODO DRY-RUN (sem alterações no banco) ===\n\n";
}

// ------------------------------------------------------------------
// MAPEAMENTO: palavra-chave => slug da categoria
// Ordem importa! Coloque os mais específicos PRIMEIRO.
// ------------------------------------------------------------------
$mapeamento = [

    // ══════════════════════════════════════════════════════════════
    // FEMININO / BOLSAS
    // ══════════════════════════════════════════════════════════════
    'shoulder bag'      => 'feminino-bolsas-bolsas-de-ombro',
    'shouder bag'       => 'feminino-bolsas-bolsas-de-ombro',
    'bolsa de ombro'    => 'feminino-bolsas-bolsas-de-ombro',
    'clutch'            => 'feminino-bolsas-clutches',
    'cluthc'            => 'feminino-bolsas-clutches',  // typo frequente
    'mochila'           => 'feminino-bolsas-mochilas',
    'muchila'           => 'feminino-bolsas-mochilas',  // typo frequente
    'satchel'           => 'feminino-bolsas-bolsas-de-ombro',
    'porta documento'   => 'feminino-bolsas',
    'porta tudo'        => 'feminino-bolsas',
    'bolça'             => 'feminino-bolsas',            // typo frequente
    'bolsa'             => 'feminino-bolsas',
    'bag'               => 'feminino-bolsas',

    // ══════════════════════════════════════════════════════════════
    // FEMININO / CALÇADOS
    // ══════════════════════════════════════════════════════════════
    'scarpin'           => 'feminino-calcados-scarpins',
    'sandalia'          => 'feminino-calcados-sandalias',
    'sandália'          => 'feminino-calcados-sandalias',
    'rasteira'          => 'feminino-calcados-sandalias',
    'babouche'          => 'feminino-calcados-sandalias',
    'furadinha'         => 'feminino-calcados-sandalias',
    'slide'             => 'feminino-calcados-sandalias',
    'clog'              => 'feminino-calcados-sandalias',
    'bota'              => 'feminino-calcados-botas',
    'sneaker'           => 'feminino-calcados-tenis',
    'plataforma'        => 'feminino-calcados-scarpins',
    'alpargata'         => 'feminino-calcados-sandalias',
    'sapatilha'         => 'feminino-calcados-sandalias',
    'melissa'           => 'feminino-calcados',
    'ultragirl'         => 'feminino-calcados-tenis',
    'possession'        => 'feminino-calcados',
    'beach slide'       => 'feminino-calcados-sandalias',
    'hotness'           => 'feminino-calcados',
    'sun sunset'        => 'feminino-calcados',
    'sun dowtown'       => 'feminino-calcados',
    'jump'              => 'feminino-calcados',
    'lust'              => 'feminino-calcados',
    'quartz sandal'     => 'feminino-calcados-sandalias',
    'kind'              => 'feminino-calcados',
    'way'               => 'feminino-calcados',
    'queen'             => 'feminino-calcados',

    // ══════════════════════════════════════════════════════════════
    // FEMININO / ROUPAS
    // ══════════════════════════════════════════════════════════════
    'vestido'           => 'feminino-roupas-vestidos',
    'vestdo'            => 'feminino-roupas-vestidos',    // typo frequente
    'vestudo'           => 'feminino-roupas-vestidos',    // typo frequente
    'saia'              => 'feminino-roupas-saias',
    'blusa'             => 'feminino-roupas-blusas',
    'casaco'            => 'feminino-roupas-casacos',
    'kasakova'          => 'feminino-roupas-casacos',
    'sobretudo'         => 'feminino-roupas-casacos',
    'camiseta'          => 'feminino-roupas-camisetas',
    't-shirt'           => 'feminino-roupas-camisetas',
    'tshirt'            => 'feminino-roupas-camisetas',
    'baby look'         => 'feminino-roupas-camisetas',
    'top de amarrar'    => 'feminino-roupas-blusas',
    'top coração'       => 'feminino-roupas-blusas',
    'top'               => 'feminino-roupas-blusas',
    'crooped'           => 'feminino-roupas-blusas',
    'cropped'           => 'feminino-roupas-blusas',
    'bata'              => 'feminino-roupas-blusas',
    'blusa'             => 'feminino-roupas-blusas',
    'chemise'           => 'feminino-roupas-vestidos',
    'tomara que caia'   => 'feminino-roupas-vestidos',
    'macaquinho'        => 'feminino-roupas-vestidos',
    'conjunto short'    => 'feminino-roupas-camisetas',
    'conjunto'          => 'feminino-roupas-camisetas',
    'moletom'           => 'feminino-roupas-casacos',
    'cardigan'          => 'feminino-roupas-casacos',
    'jaqueta'           => 'feminino-roupas-casacos',
    'blazer'            => 'feminino-roupas-casacos',
    'spencer'           => 'feminino-roupas-casacos',
    'kimono'            => 'feminino-roupas-casacos',
    'colete'            => 'feminino-roupas-casacos',
    'parka'             => 'feminino-roupas-casacos',
    'corta vento'       => 'feminino-roupas-casacos',
    'tule'              => 'feminino-roupas-vestidos',
    'legging'           => 'feminino-roupas-calcas',
    'leggin'            => 'feminino-roupas-calcas',
    'pantalona'         => 'feminino-roupas-calcas',
    'pantacur'          => 'feminino-roupas-calcas',
    'calça'             => 'feminino-roupas-calcas',
    'calca'             => 'feminino-roupas-calcas',
    'short jeans'       => 'feminino-roupas-calcas',
    'short sarja'       => 'feminino-roupas-calcas',
    'short tactel'      => 'feminino-roupas-calcas',
    'short'             => 'feminino-roupas-calcas',

    // ══════════════════════════════════════════════════════════════
    // FEMININO / ACESSÓRIOS
    // ══════════════════════════════════════════════════════════════
    'cinto'             => 'feminino-acessorios-cintos',
    'oculos'            => 'feminino-acessorios-oculos-de-sol',
    'óculos'            => 'feminino-acessorios-oculos-de-sol',
    'bijuteria'         => 'feminino-acessorios-bijuterias',
    'colar'             => 'feminino-acessorios-bijuterias',
    'brinco'            => 'feminino-acessorios-bijuterias',
    'pulseira'          => 'feminino-acessorios-bijuterias',
    'lenço'             => 'feminino-acessorios',
    'cachecol'          => 'feminino-acessorios',
    'turbante'          => 'feminino-acessorios',
    'gola cachecol'     => 'feminino-acessorios',
    'touca'             => 'feminino-acessorios',
    'meia'              => 'feminino-acessorios',
    'gravata'           => 'feminino-acessorios',
    'canga'             => 'feminino-acessorios',

    // ══════════════════════════════════════════════════════════════
    // MASCULINO
    // ══════════════════════════════════════════════════════════════
    'camisa'            => 'masculino-roupas-camisas',
    'polo'              => 'masculino-roupas-polos',
    'bermuda'           => 'masculino-roupas-bermudas',
    'calça jeans'       => 'masculino-roupas-calcas-jeans',
    'sapato'            => 'masculino-calcados-sapato-social',
    'sapatenis'         => 'masculino-calcados-sapatenis',
    'sapatênis'         => 'masculino-calcados-sapatenis',
    'chuteira'          => 'masculino-calcados-chuteiras',
    'relogio'           => 'masculino-acessorios-relogios',
    'relógio'           => 'masculino-acessorios-relogios',
    'bone'              => 'masculino-acessorios-bones',
    'boné'              => 'masculino-acessorios-bones',
    'carteira'          => 'masculino-acessorios-carteiras',

    // ══════════════════════════════════════════════════════════════
    // INFANTIL
    // ══════════════════════════════════════════════════════════════
    'welly peppa pig'   => 'infantil-menina-calcados',
    'ultragirl princess'=> 'infantil-menina-calcados',
    'ultragirl sweet'   => 'infantil-menina-calcados',
    'utragirl disney'   => 'infantil-menina-calcados',
    'space love'        => 'infantil-menina-calcados',
    'vestido infantil'  => 'infantil-menina-roupas-vestidos-infantis',
    'pijama'            => 'infantil-menina-roupas-pijamas',
    'macaquinho bebê'   => 'infantil-menino-roupas-macacoes',
    'short bebê'        => 'infantil-menino-roupas-regatas',
    'avental infantil'  => 'infantil-menino-roupas',
    'body'              => 'infantil-menino-roupas-bodys',
    'macacão'           => 'infantil-menino-roupas-macacoes',
    'macacao'           => 'infantil-menino-roupas-macacoes',
    'regata'            => 'infantil-menino-roupas-regatas',
    'papete'            => 'infantil-menino-calcados-papetes',
    'tenis led'         => 'infantil-menina-calcados-tenis-led',
    'tenis de rodinha'  => 'infantil-menino-calcados-tenis-de-rodinha',

    // ══════════════════════════════════════════════════════════════
    // CASA
    // ══════════════════════════════════════════════════════════════
    'lencol'            => 'casa-cama-jogos-de-lencol',
    'lençol'            => 'casa-cama-jogos-de-lencol',
    'edredon'           => 'casa-cama-edredons',
    'travesseiro'       => 'casa-cama-travesseiros',
    'toalha de banho'   => 'casa-banho-toalhas-de-banho',
    'roupao'            => 'casa-banho-roupoes',
    'roupão'            => 'casa-banho-roupoes',
    'almofada'          => 'casa-decoracao-almofadas',
    'tapete'            => 'casa-decoracao-tapetes',
    'vela'              => 'casa-decoracao-velas-aromaticas',
];

// ------------------------------------------------------------------
// Carrega o mapa slug => id das categorias
// ------------------------------------------------------------------
$categoriasPorSlug = DB::table('categorias')
    ->pluck('id', 'slug')
    ->toArray();

// ------------------------------------------------------------------
// Busca todos os itens que AINDA NÃO têm categoria associada
// ------------------------------------------------------------------
$itensSemCategoria = DB::table('items')
    ->whereNotIn('id', function ($query) {
        $query->select('item_id')->from('categoria_item');
    })
    ->get(['id', 'nome_do_produto']);

echo "Itens sem categoria: " . count($itensSemCategoria) . "\n\n";

$associados  = 0;
$naoMapeados = [];

foreach ($itensSemCategoria as $item) {
    $nomeNorm    = mb_strtolower($item->nome_do_produto);
    $categoriaId = null;
    $slugEncontrado = null;

    foreach ($mapeamento as $keyword => $slug) {
        if (str_contains($nomeNorm, mb_strtolower($keyword))) {
            if (isset($categoriasPorSlug[$slug])) {
                $categoriaId    = $categoriasPorSlug[$slug];
                $slugEncontrado = $slug;
                break;
            }
        }
    }

    if ($categoriaId) {
        echo "  ✔ [{$item->id}] {$item->nome_do_produto}  →  {$slugEncontrado}\n";
        if (!$dryRun) {
            DB::table('categoria_item')->insertOrIgnore([
                'categoria_id' => $categoriaId,
                'item_id'      => $item->id,
            ]);
        }
        $associados++;
    } else {
        $naoMapeados[] = "[{$item->id}] {$item->nome_do_produto}";
    }
}

echo "\n--- RESULTADO ---\n";
echo "Associados:     {$associados}\n";
echo "Sem mapeamento: " . count($naoMapeados) . "\n";

if (!empty($naoMapeados)) {
    echo "\n=== Itens SEM categoria (adicione palavras-chave para eles) ===\n";
    foreach ($naoMapeados as $linha) {
        echo "  ? {$linha}\n";
    }
}
