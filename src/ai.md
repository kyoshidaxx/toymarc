DMARCレポート可視化アプリケーション要件
アプリケーション基盤
フレームワーク: Laravel (最新の安定版を推奨)
Webサーバー: FrankenPHP
データベース: MySQL 8.4
コンテナ化: Docker (Docker Composeを使用し、アプリケーション、DB、FrankenPHPを連携させる)
開発言語: PHP (Laravelバックエンド), TypeScript (Reactフロントエンド)
開発環境・品質管理
静的解析ツール: PHPStan (レベル max を目指し、コード品質を維持)
Composer経由でインストールし、composer.json にスクリプトとして追加。
CI/CDパイプラインに組み込むことを検討。
PHPStanの実行コマンド例: vendor/bin/phpstan analyse --memory-limit=2G (必要に応じてメモリ制限を調整)
設定ファイル: phpstan.neon または phpstan.neon.dist をプロジェクトルートに配置し、解析対象ディレクトリ、除外パス、ルールセットなどを定義する。
環境設定:
.env ファイルによる環境変数の管理 (DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD など)
Laravelの各種設定ファイル (config/app.php, config/database.php など) の適切化。
Laravel日本語設定:
config/app.php の locale を 'ja' に設定。
必要に応じて fallback_locale を 'en' に設定。
バリデーションメッセージなどの日本語化リソースを導入 (laravel-lang/lang パッケージなど)。
タイムゾーン設定:
config/app.php の timezone を 'Asia/Tokyo' に設定。
データベースのタイムゾーン設定も 'Asia/Tokyo' または UTC を考慮し、整合性を保つ。
DMARCレポート取り込み機能
レポート配置ディレクトリ: 特定のディレクトリ (storage/app/dmarc_reports など、Laravelのストレージパス以下に配置することを推奨) にDMARC XMLレポートファイルを配置する。
取り込みトリガー: Artisanコマンド (php artisan dmarc:import など) でレポートを取り込む。
二重取り込み防止:
取り込み済みのレポートファイル名（またはハッシュ値）をデータベースに記録し、既に処理済みのファイルはスキップする。
レポート内の report_metadata 部分にある date_range と org_name などから、重複するレポートを識別するロジックを検討する。（同じ組織から同じ期間のレポートが複数回送られてくる可能性も考慮）
エラーハンドリング:
不正なXML形式のレポートや、破損したレポートに対するエラーハンドリングを実装する。
取り込み中に発生したエラーはログに出力する。
DMARCレポート解析・保存機能
XMLパース: DMARC XMLレポートをパースし、必要な情報を抽出する。
データモデル:
レポート全体を管理する DmarcReport モデル
org_name, email, extra_contact_info, report_id, begin_date, end_date, policy_domain, policy_adkim, policy_aspf, policy_p, policy_sp, policy_pct などの情報を保持。
個々のレコード（認証結果）を管理する DmarcRecord モデル
source_ip, count, disposition, dkim_aligned, dkim_result, spf_aligned, spf_result などの情報を保持。
DmarcReport とのリレーションを持つ。
失敗したDKIM/SPFレコードの詳細を管理するモデル（必要に応じて）
データベース保存: 解析したデータを正規化してMySQLデータベースに保存する。
ダッシュボード表示機能 (フロントエンド: React/TypeScript)
概要サマリー:
指定期間内のレポート総数、受信メール総数、認証成功/失敗数。
ポリシー（quarantine, reject, none）ごとのメール数。
ドメイン別統計:
送信ドメインごとの認証結果（DKIM/SPFアライメント成功率、認証結果）をグラフで表示。
IPアドレスごとの認証結果と送信量。
結果フィルタリング・検索:
期間指定（日、週、月、カスタム範囲）。
送信元IPアドレス、送信ドメイン、認証結果（pass, fail, softfail, neutral）などでのフィルタリング。
フリーテキスト検索機能。
詳細レポート表示:
個々のDMARCレポートの詳細情報を表示する機能。
rawData (XMLの内容) の表示オプション。
グラフ・チャート:
時間経過に伴う認証結果の変化（棒グラフ、折れ線グラフ）。
認証結果の内訳（円グラフ）。
トップNの不正送信元IPアドレス。
UX/UI:
直感的で分かりやすいUI。
レスポンシブデザイン（モバイル対応）。
データのロード中表示、エラーメッセージ表示。
Docker環境構築
Dockerfile: Laravelアプリケーション、FrankenPHP、および必要なPHPエクステンション（XML処理用など）を含むDockerfileを作成する。
docker-compose.yml:
app サービス (Laravelアプリケーション + FrankenPHP)
db サービス (MySQL 8.4)
volumes: DBデータ永続化用、DMARCレポート配置ディレクトリのマウント
.env ファイルの管理
開発環境と本番環境: 開発用と本番用のDocker Compose設定を分けるか、環境変数で切り替えられるようにする。
その他考慮事項
認証・認可:
アプリケーションへのアクセス制御（ログイン機能）。
複数ユーザーでの利用を想定する場合、ユーザーごとのDMARCレポート表示範囲の制御。
ログ:
アプリケーションログ（Laravelの標準ログ機能）。
DMARCレポート取り込み処理のログ。
セキュリティ:
データベースへの接続情報などの機密情報の管理（環境変数、Docker Secretsなど）。
XSS、CSRF対策（Laravelの標準機能を利用）。
入力値のサニタイズ。
テスト:
ユニットテスト（Artisanコマンド、DMARCパースロジックなど）。
E2Eテスト（Cypressなど、フロントエンドとバックエンドの連携）。
パフォーマンス:
大量のレポートデータを取り扱う場合のデータベースインデックスの最適化。
ダッシュボードのデータ取得クエリの最適化。
キャッシュ戦略の検討（フロントエンド、バックエンド）。
進捗確認のスタイル
各要件に対して、以下のような形で進捗を管理できると良いでしょう。
未着手: まだ作業を開始していない状態。
着手済: 作業を開始したが、完了していない状態。
完了: 作業が完了し、要件を満たしている状態。
レビュー中: 完了したが、第三者によるレビューを待っている状態。
保留: 何らかの理由で作業が一時的に中断している状態。
