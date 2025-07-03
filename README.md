# Laravel + FrankenPHP + MySQL Docker 環境

FrankenPHP を使用した Laravel アプリケーションと MySQL データベースの Docker 環境です。

## 🚀 セットアップ

### 前提条件

- Docker
- Docker Compose

### クイックスタート

1. セットアップスクリプトを実行：

```bash
./setup-laravel.sh
```

このスクリプトは以下を自動で実行します：

- Laravel ディレクトリの作成
- Docker コンテナのビルドと起動
- Laravel プロジェクトの作成
- データベース設定の更新
- マイグレーションの実行

### 手動セットアップ

1. Laravel ディレクトリを作成：

```bash
mkdir src
```

2. Docker コンテナを起動：

```bash
docker-compose up -d --build
```

3. Laravel プロジェクトを作成：

```bash
cd src && docker-compose run --rm app composer create-project laravel/laravel . --prefer-dist
```

4. 権限を設定：

```bash
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

5. 環境設定を更新：

```bash
docker-compose exec app sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/g' .env
docker-compose exec app sed -i 's/DB_USERNAME=root/DB_USERNAME=laravel/g' .env
docker-compose exec app sed -i 's/DB_PASSWORD=/DB_PASSWORD=laravel_password/g' .env
docker-compose exec app sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env
```

6. アプリケーションキーを生成：

```bash
docker-compose exec app php artisan key:generate
```

7. マイグレーションを実行：

```bash
docker-compose exec app php artisan migrate
```

## 🌐 アクセス

- **Laravel アプリケーション**: http://localhost
- **MySQL データベース**: localhost:3306

## 📋 便利なコマンド

### コンテナ管理

```bash
# コンテナ停止
docker-compose down

# コンテナ再起動
docker-compose restart

# ログ確認
docker-compose logs -f

# コンテナ内でコマンド実行
docker-compose exec app php artisan [command]
```

### Laravel コマンド

```bash
# Artisanコマンド実行
docker-compose exec app php artisan [command]

# Composerコマンド実行
docker-compose exec app composer [command]

# マイグレーション実行
docker-compose exec app php artisan migrate

# キャッシュクリア
docker-compose exec app php artisan cache:clear
```

## 🗂️ プロジェクト構造

```
toymarc/
├── infra/
│   ├── docker-compose.yml
│   ├── Dockerfile
│   └── Caddyfile
├── src/              # Laravelプロジェクト（マウントポイント）
├── setup-laravel.sh  # セットアップスクリプト
└── README.md
```

## 🔧 環境設定

### データベース設定

- **ホスト**: db
- **データベース名**: laravel
- **ユーザー名**: laravel
- **パスワード**: laravel_password
- **ポート**: 3306

### PHP 設定

- **PHP バージョン**: 8.2+
- **Web サーバー**: FrankenPHP
- **ポート**: 80

## 🐛 トラブルシューティング

### 権限エラーが発生する場合

```bash
cd infra && docker-compose exec app chmod -R 777 storage bootstrap/cache
```

### データベース接続エラーが発生する場合

1. MySQL コンテナが起動しているか確認：

```bash
docker-compose ps
```

2. データベース設定を確認：

```bash
docker-compose exec app cat .env | grep DB_
```

### コンテナが起動しない場合

1. ログを確認：

```bash
docker-compose logs
```

2. コンテナを再ビルド：

```bash
docker-compose down
docker-compose up -d --build
```
