# DMARC レポート可視化アプリケーション

Laravel + FrankenPHP + MySQL Docker 環境で動作する DMARC レポート可視化アプリケーションです。DMARC XML レポートをインポートし、ダッシュボードで分析結果を可視化できます。

## 🚀 セットアップ

### 前提条件

- Docker
- Docker Compose

### クイックスタート

1. リポジトリをクローン：

```bash
git clone [repository-url]
cd toymarc
```

2. Docker コンテナを起動：

```bash
docker-compose up -d --build
```

3. 依存関係をインストール：

```bash
# PHP依存関係
docker-compose exec app composer install

# Node.js依存関係
cd src && npm install
```

4. 環境設定ファイルを作成：

```bash
docker-compose exec app cp .env.example .env
```

5. アプリケーションキーを生成：

```bash
docker-compose exec app php artisan key:generate
```

6. マイグレーションを実行：

```bash
docker-compose exec app php artisan migrate
```

7. フロントエンドをビルド：

```bash
cd src && npm run build
```

### 手動セットアップ（詳細）

1. **Docker コンテナを起動**：

```bash
docker-compose up -d --build
```

2. **PHP 依存関係をインストール**：

```bash
docker-compose exec app composer install
```

3. **Node.js 依存関係をインストール**：

```bash
cd src && npm install
```

4. **環境設定ファイルを作成**：

```bash
docker-compose exec app cp .env.example .env
```

5. **アプリケーションキーを生成**：

```bash
docker-compose exec app php artisan key:generate
```

6. **権限を設定**：

```bash
docker-compose exec app chmod -R 777 storage bootstrap/cache
```

7. **マイグレーションを実行**：

```bash
docker-compose exec app php artisan migrate
```

8. **フロントエンドをビルド**：

```bash
cd src && npm run build
```

## 📊 アプリケーションの使い方

### 1. ダッシュボード

**URL**: http://localhost:8000/dashboard

DMARC レポートの概要を表示します：

- 総レポート数
- 総レコード数
- 最近のインポート状況
- 月別レポート数グラフ

### 2. DMARC レポート一覧

**URL**: http://localhost:8000/dmarc-reports

インポートされた DMARC レポートの一覧を表示します：

- レポートの詳細情報
- 送信者ドメイン
- レポート期間
- 処理結果の統計

### 3. DMARC レポート詳細

**URL**: http://localhost:8000/dmarc-reports/{id}

個別の DMARC レポートの詳細を表示します：

- レポートの基本情報
- 送信者ドメイン別の統計
- 結果別の統計（pass/fail/quarantine/reject）
- グラフによる可視化

### 4. 分析画面

**URL**: http://localhost:8000/analytics

DMARC レポートの詳細分析を表示します：

- 期間別の統計
- 送信者ドメイン別の分析
- 結果別の傾向分析
- インタラクティブなグラフ

### 5. 設定画面

**URL**: http://localhost:8000/settings

アプリケーションの設定情報を表示します：

- システム設定
- DMARC 設定
- ストレージ設定
- キャッシュ設定
- ストレージ情報

## 📁 DMARC レポートのインポート

### サンプルデータのインポート

1. サンプル DMARC レポートをインポート：

```bash
docker-compose exec app php artisan dmarc:import test_data/sample_dmarc_report_1.xml
docker-compose exec app php artisan dmarc:import test_data/sample_dmarc_report_2.xml
docker-compose exec app php artisan dmarc:import test_data/sample_dmarc_report_3.xml
```

### 独自の DMARC レポートをインポート

1. DMARC XML ファイルを`src/storage/app/dmarc_reports/`に配置
2. インポートコマンドを実行：

```bash
docker-compose exec app php artisan dmarc:import storage/app/dmarc_reports/your_report.xml
```

### バッチインポート

複数のファイルを一度にインポート：

```bash
docker-compose exec app php artisan dmarc:import storage/app/dmarc_reports/ --batch
```

## 🎨 フロントエンド開発

### 開発サーバーの起動

```bash
cd src && npm run dev
```

### ビルド

```bash
npm run build
```

### 使用技術

- **React 18** - UI フレームワーク
- **TypeScript** - 型安全性
- **Tailwind CSS** - スタイリング
- **Recharts** - グラフ描画
- **Inertia.js** - Laravel と React の連携

## 🌐 アクセス

- **アプリケーション**: http://localhost:8000
- **ダッシュボード**: http://localhost:8000/dashboard
- **DMARC レポート**: http://localhost:8000/dmarc-reports
- **分析**: http://localhost:8000/analytics
- **設定**: http://localhost:8000/settings
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

# DMARCレポートインポート
docker-compose exec app php artisan dmarc:import [file_path]
```

### フロントエンドコマンド

```bash
# 開発サーバー起動
cd src && npm run dev

# ビルド
npm run build

# 依存関係インストール
npm install
```

## 🗂️ プロジェクト構造

```
toymarc/
├── docker-compose.yml      # Docker Compose設定
├── Dockerfile              # Docker設定
├── infra/
│   ├── Dockerfile          # アプリケーション用Dockerfile
│   └── Caddyfile           # Webサーバー設定
├── src/                    # Laravelプロジェクト
│   ├── app/
│   │   ├── Console/Commands/
│   │   │   └── ImportDmarcReportsCommand.php
│   │   ├── Http/Controllers/
│   │   │   ├── AnalyticsController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DmarcDashboardController.php
│   │   │   ├── DmarcReportController.php
│   │   │   └── SettingsController.php
│   │   ├── Models/
│   │   │   ├── DmarcRecord.php
│   │   │   └── DmarcReport.php
│   │   └── Services/
│   │       ├── DmarcReportImportService.php
│   │       └── DmarcReportParserService.php
│   ├── resources/js/
│   │   ├── components/
│   │   │   ├── Layout.jsx
│   │   │   ├── Navigation.jsx
│   │   │   └── ui/
│   │   │       └── card.jsx
│   │   └── Pages/
│   │       ├── Analytics/Index.jsx
│   │       ├── Dashboard/Index.jsx
│   │       ├── DmarcReports/
│   │       │   ├── Index.jsx
│   │       │   └── Show.jsx
│   │       └── Settings/Index.jsx
│   └── storage/app/dmarc_reports/  # DMARCレポート保存先
├── test_data/              # サンプルDMARCレポート
├── document/               # ドキュメント
│   └── coding_guide.md
└── README.md
```

## 🔧 環境設定

### データベース設定

- **ホスト**: db
- **データベース名**: toymarc
- **ユーザー名**: root
- **パスワード**: toymarc_password
- **ポート**: 3306

### PHP 設定

- **PHP バージョン**: 8.2+
- **Web サーバー**: FrankenPHP
- **ポート**: 8000

### DMARC 設定

- **レポートディレクトリ**: `storage/app/dmarc_reports/`
- **1 ページあたりの最大件数**: 50
- **インポートバッチサイズ**: 100

## 🐛 トラブルシューティング

### 権限エラーが発生する場合

```bash
docker-compose exec app chmod -R 777 storage bootstrap/cache
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

### フロントエンドが表示されない場合

1. ビルドを実行：

```bash
cd src && npm run build
```

2. キャッシュをクリア：

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
```

### DMARC レポートのインポートが失敗する場合

1. XML ファイルの権限を確認：

```bash
docker-compose exec app ls -la storage/app/dmarc_reports/
```

2. XML ファイルの形式を確認：

```bash
docker-compose exec app php artisan dmarc:import --help
```

## 📚 関連ドキュメント

- [コーディングガイド](document/coding_guide.md) - フロントエンド開発のガイドライン
- [API ドキュメント](src/docs/api.md) - API エンドポイントの詳細
- [実装状況](IMPLEMENTATION_STATUS.md) - 機能の実装状況

## 🤝 貢献

1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/amazing-feature`)
3. 変更をコミット (`git commit -m 'Add some amazing feature'`)
4. ブランチにプッシュ (`git push origin feature/amazing-feature`)
5. プルリクエストを作成

## 📄 ライセンス

このプロジェクトは MIT ライセンスの下で公開されています。
