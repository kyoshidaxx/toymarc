#!/bin/sh

# データベースの準備ができるまで待機
echo "Waiting for database to be ready..."
while ! php artisan migrate:status > /dev/null 2>&1; do
    sleep 2
done

# マイグレーションとシードを実行
echo "Running migrations and seeds..."
php artisan migrate --seed --force

# FrankenPHPを起動
echo "Starting FrankenPHP..."
exec frankenphp run --config /etc/caddy/Caddyfile 