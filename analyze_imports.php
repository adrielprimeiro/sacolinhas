<?php
$lines = file('/var/www/sacolinhas/import_logs.txt');
$codes = [];
$countsByDate = [];

foreach ($lines as $line) {
    if (preg_match('/\[(.*?)\]/', $line, $matches)) {
        $date = substr($matches[1], 0, 10);
        if (!isset($countsByDate[$date])) {
            $countsByDate[$date] = 0;
        }
        $countsByDate[$date]++;
    }
    
    if (preg_match('/"codigo":"(.*?)"/', $line, $matches)) {
        $codes[] = $matches[1];
    }
}

echo "Total imported items found in logs: " . count($codes) . "\n";
echo "Dates:\n";
print_r($countsByDate);
