#!/bin/bash
set -e

mkdir -p /app/storage/certs
if [ ! -f /app/storage/certs/private.pem ]; then
    echo "Generating JWT keypair..."
    openssl genrsa -out /app/storage/certs/private.pem 2048
    openssl rsa -in /app/storage/certs/private.pem -pubout -out /app/storage/certs/public.pem
fi

mkdir -p /app/public/assets/uploads/avatars /app/public/assets/uploads/proofs

# ── Database Migration ──────────────────────────────────────────
echo "Running database migration..."
php -r "
    \$dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('DB_HOST') ?: '127.0.0.1',
        getenv('DB_PORT') ?: '3306',
        getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: ''
    );
    try {
        \$pdo = new PDO(
            \$dsn,
            getenv('DB_USER') ?: getenv('DB_USERNAME') ?: '',
            getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        \$sql = file_get_contents('/app/database/schema.sql');
        // CREATE DATABASE ও USE লাইন skip করো (Railway এ ক্ষতিকর)
        \$sql = preg_replace('/^\s*CREATE\s+DATABASE\b.+$/mi', '', \$sql);
        \$sql = preg_replace('/^\s*USE\s+\S+\s*;/mi', '', \$sql);
        // Statement আলাদা করে execute করো
        foreach (array_filter(array_map('trim', explode(';', \$sql))) as \$stmt) {
            \$pdo->exec(\$stmt);
        }
        echo 'Migration OK' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Migration FAILED: ' . \$e->getMessage() . PHP_EOL;
    }
"
# ────────────────────────────────────────────────────────────────

docker-php-entrypoint --config /Caddyfile --adapter caddyfile 2>&1