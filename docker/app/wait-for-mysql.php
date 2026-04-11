<?php
$maxAttempts = 30;
$attempt = 0;

echo "🔄 Aguardando banco de dados mysql-db:3306...\n";

while ($attempt < $maxAttempts) {
    try {
        $pdo = new PDO(
            'mysql:host=mysql-db;port=3306',
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD'),
            [PDO::ATTR_TIMEOUT => 2]
        );
        echo "✅ Banco de dados disponível!\n";
        exit(0);
    } catch (PDOException $e) {
        $attempt++;
        echo "⏳ Tentativa $attempt/$maxAttempts: " . $e->getMessage() . "\n";
        sleep(2);
    }
}

echo "❌ Timeout: Banco não ficou disponível após " . ($maxAttempts * 2) . " segundos\n";
exit(1);
