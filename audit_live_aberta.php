<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Live;
use App\Models\Sacolinhas;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

echo "=== Auditando Lives Abertas ou Recentes ===\n\n";

$lives = Live::orderByDesc('id')->limit(10)->get();

foreach ($lives as $live) {
    echo "Live ID: {$live->id} | Nome: {$live->title} | Status: {$live->status} | Data: {$live->created_at}\n";
    
    // Sacolinhas vinculadas a esta live
    $sacolinhas = Sacolinhas::where('live_id', $live->id)->get();
    echo "   Total de Sacolinhas: " . $sacolinhas->count() . "\n";

    $somaSacolinhas = 0;
    $hasCents = false;

    foreach ($sacolinhas as $s) {
        $price = (float) $s->price;
        $qty   = (int) $s->quantity;
        $subtotal = $price * $qty;
        $somaSacolinhas += $subtotal;

        // Verificar se o preço ou subtotal tem centavos
        if (fmod($price, 1) != 0 || fmod($subtotal, 1) != 0) {
            echo "   [!] SACALINHA COM CENTAVOS: ID {$s->id} | UserID: {$s->user_id} | Code: {$s->code} | Preço: {$s->price} | Qtd: {$s->quantity} | Status: {$s->status}\n";
            $hasCents = true;
        }
    }

    echo "   Soma Calculada dos Itens das Sacolinhas: R$ " . number_format($somaSacolinhas, 2, ',', '.') . "\n";
    echo "------------------------------------------------------------------\n";
}

echo "\n=== Verificando sacolinhas recentes com centavos em TODO o banco ===\n";
$sacolinhasComCentavos = Sacolinhas::whereRaw("MOD(price, 1) != 0")
    ->orWhereRaw("MOD(price * quantity, 1) != 0")
    ->orderByDesc('id')
    ->limit(20)
    ->get();

echo "Sacolinhas com centavos encontradas: " . $sacolinhasComCentavos->count() . "\n";
foreach ($sacolinhasComCentavos as $sc) {
    echo "ID: {$sc->id} | LiveID: {$sc->live_id} | UserID: {$sc->user_id} | Code: {$sc->code} | Price: {$sc->price} | Qty: {$sc->quantity} | Status: {$sc->status} | CreatedAt: {$sc->created_at}\n";
}
