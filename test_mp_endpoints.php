<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$token = config('services.mercadopago.access_token');

echo "=== Search Payments by External Ref / Date ===\n\n";

$url1 = "https://api.mercadopago.com/v1/payments/search?external_reference=de8f0d7b518741f6877078a2967cf999";
$res1 = Http::withoutVerifying()->withToken($token)->get($url1);
echo "Ref Search Status: " . $res1->status() . "\n";
print_r($res1->json());

echo "\n===========================================\n";

$begin = Carbon\Carbon::today()->startOfDay()->format('Y-m-d\TH:i:s.000P');
$end = Carbon\Carbon::today()->endOfDay()->format('Y-m-d\TH:i:s.000P');
$url2 = "https://api.mercadopago.com/v1/payments/search?range=date_created&begin_date={$begin}&end_date={$end}&limit=50";
$res2 = Http::withoutVerifying()->withToken($token)->get($url2);
echo "Date Search Status: " . $res2->status() . "\n";
$data2 = $res2->json();
echo "Total Results: " . ($data2['paging']['total'] ?? 0) . "\n";
if (!empty($data2['results'])) {
    foreach ($data2['results'] as $r) {
        echo "ID: {$r['id']} | Status: {$r['status']} | Amount: {$r['transaction_amount']} | Desc: " . ($r['description'] ?? 'N/A') . "\n";
        print_r($r);
        echo "-------------------\n";
    }
}
