#!/bin/bash

echo "🚀 Laravel + FrankenPHP + MySQL 環境をセットアップします..."

# Laravelディレクトリを作成
echo "📁 Laravelディレクトリを作成中..."
mkdir -p src

# Dockerコンテナをビルドして起動
echo "🐳 Dockerコンテナをビルドして起動中..."
docker-compose up -d --build

# コンテナが起動するまで待機
echo "⏳ コンテナの起動を待機中..."
sleep 10

# Laravelプロジェクトを作成
echo "🎨 Laravelプロジェクトを作成中..."
cd src && docker-compose run --rm app composer create-project laravel/laravel . --prefer-dist

# 権限を設定
echo "🔐 権限を設定中..."
docker-compose exec app chmod -R 777 storage bootstrap/cache

# .envファイルを設定
echo "⚙️ 環境設定を更新中..."
docker-compose exec app sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/g' .env
docker-compose exec app sed -i 's/DB_DATABASE=laravel/DB_DATABASE=laravel/g' .env
docker-compose exec app sed -i 's/DB_USERNAME=root/DB_USERNAME=laravel/g' .env
docker-compose exec app sed -i 's/DB_PASSWORD=/DB_PASSWORD=laravel_password/g' .env
docker-compose exec app sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env

# アプリケーションキーを生成
echo "🔑 アプリケーションキーを生成中..."
docker-compose exec app php artisan key:generate

# マイグレーションを実行
echo "🗄️ データベースマイグレーションを実行中..."
docker-compose exec app php artisan migrate

echo "✅ セットアップが完了しました！"
echo "🌐 アプリケーションは http://localhost でアクセスできます"
echo "🗄️ MySQLは localhost:3306 でアクセスできます"
echo ""
echo "📋 便利なコマンド:"
echo "  - コンテナ停止: docker-compose down"
echo "  - コンテナ再起動: docker-compose restart"
echo "  - ログ確認: docker-compose logs -f" 