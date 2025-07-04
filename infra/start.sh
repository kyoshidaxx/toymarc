#!/bin/sh

# データベースの準備ができるまで待機
echo "Waiting for database to be ready..."
while ! php -r "
try {
    \$pdo = new PDO('mysql:host=db;dbname=toymarc', 'root', 'toymarc_password');
    echo 'Connected successfully';
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
" > /dev/null 2>&1; do
    echo "Database not ready yet, waiting..."
    sleep 2
done
echo "Database is ready!"

# マイグレーションを実行
echo "Running migrations..."
php artisan migrate --force

# 初回のみシードを実行（usersテーブルが空の場合のみ）
if [ $(php -r "
try {
    \$pdo = new PDO('mysql:host=db;dbname=toymarc', 'root', 'toymarc_password');
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM users');
    echo \$stmt->fetchColumn();
} catch (PDOException \$e) {
    echo '0';
}
") -eq 0 ]; then
    echo "Running seeds (first time setup)..."
    php artisan db:seed --force
else
    echo "Skipping seeds (users already exist)"
fi

# FrankenPHPを起動
echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile 