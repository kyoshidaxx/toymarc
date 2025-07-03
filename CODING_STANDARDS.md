# DMARC レポート可視化アプリケーション コーディング規約

## 1. プロジェクト概要

### 技術スタック

- **バックエンド**: Laravel (最新安定版)
- **フロントエンド**: React + TypeScript
- **Web サーバー**: FrankenPHP
- **データベース**: MySQL 8.4
- **コンテナ化**: Docker + Docker Compose
- **静的解析**: PHPStan (レベル max)

### 開発環境設定

- **言語**: 日本語 (locale: 'ja', timezone: 'Asia/Tokyo')
- **フォールバック**: 英語 (fallback_locale: 'en')

## 2. PHP/Laravel コーディング規約

### 2.1 基本原則

- PSR-12 コーディング規約に準拠
- PHPStan レベル max での静的解析を必須とする
- 型安全性を重視したコード記述

### 2.2 ファイル・ディレクトリ命名規則

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── DmarcReportController.php
│   │   └── DashboardController.php
│   └── Requests/
│       └── DmarcReportImportRequest.php
├── Models/
│   ├── DmarcReport.php
│   └── DmarcRecord.php
├── Services/
│   ├── DmarcReportParserService.php
│   └── DmarcReportImportService.php
└── Console/
    └── Commands/
        └── ImportDmarcReportsCommand.php
```

### 2.3 クラス命名規則

- **コントローラー**: `{機能名}Controller` (例: `DmarcReportController`)
- **モデル**: 単数形、パスカルケース (例: `DmarcReport`, `DmarcRecord`)
- **サービス**: `{機能名}Service` (例: `DmarcReportParserService`)
- **コマンド**: `{動詞}{対象}Command` (例: `ImportDmarcReportsCommand`)

### 2.4 メソッド命名規則

```php
// コントローラー
public function index(): JsonResponse
public function show(int $id): JsonResponse
public function store(StoreRequest $request): JsonResponse
public function update(UpdateRequest $request, int $id): JsonResponse
public function destroy(int $id): JsonResponse

// サービス
public function parseXmlReport(string $xmlContent): DmarcReport
public function importReportsFromDirectory(string $directory): void
public function validateReportMetadata(array $metadata): bool

// モデル
public function records(): HasMany
public function getAuthSuccessRateAttribute(): float
public function scopeByDateRange($query, string $startDate, string $endDate): Builder
```

### 2.5 データベース設計規約

```php
// マイグレーションファイル命名
// {timestamp}_create_{table_name}_table.php

// テーブル命名: スネークケース、複数形
// dmarc_reports, dmarc_records

// カラム命名: スネークケース
Schema::create('dmarc_reports', function (Blueprint $table) {
    $table->id();
    $table->string('org_name')->comment('組織名');
    $table->string('email')->comment('レポート送信元メールアドレス');
    $table->string('report_id')->unique()->comment('レポートID');
    $table->dateTime('begin_date')->comment('レポート期間開始日時');
    $table->dateTime('end_date')->comment('レポート期間終了日時');
    $table->string('policy_domain')->comment('ポリシードメイン');
    $table->enum('policy_p', ['none', 'quarantine', 'reject'])->comment('ポリシー設定');
    $table->integer('policy_pct')->comment('ポリシー適用率');
    $table->json('raw_data')->comment('生XMLデータ');
    $table->string('file_hash')->unique()->comment('ファイルハッシュ値（重複防止用）');
    $table->timestamps();

    $table->index(['begin_date', 'end_date']);
    $table->index('org_name');
    $table->index('policy_domain');
});
```

### 2.6 エラーハンドリング

```php
// 例外クラス
class DmarcReportParseException extends Exception
class DmarcReportImportException extends Exception

// エラーハンドリング例
try {
    $report = $this->parserService->parseXmlReport($xmlContent);
    $this->reportService->saveReport($report);
} catch (DmarcReportParseException $e) {
    Log::error('DMARCレポート解析エラー', [
        'file' => $filename,
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

### 2.7 ログ出力規約

```php
// ログレベル使用基準
Log::emergency() // システム停止レベルの重大エラー
Log::alert()     // 即座に対応が必要なエラー
Log::critical()  // 重要なエラー
Log::error()     // エラー
Log::warning()   // 警告
Log::notice()    // 注意
Log::info()      // 情報
Log::debug()     // デバッグ情報

// ログ出力例
Log::info('DMARCレポート取り込み開始', [
    'directory' => $directory,
    'file_count' => count($files)
]);

Log::error('DMARCレポート解析失敗', [
    'file' => $filename,
    'error' => $e->getMessage(),
    'line' => $e->getLine()
]);
```

## 3. TypeScript/React コーディング規約

### 3.1 ファイル・ディレクトリ構造

```
src/
├── components/
│   ├── Dashboard/
│   │   ├── Dashboard.tsx
│   │   ├── SummaryCard.tsx
│   │   └── ChartContainer.tsx
│   ├── Reports/
│   │   ├── ReportList.tsx
│   │   └── ReportDetail.tsx
│   └── common/
│       ├── LoadingSpinner.tsx
│       └── ErrorMessage.tsx
├── hooks/
│   ├── useDmarcReports.ts
│   └── useReportFilters.ts
├── services/
│   ├── api.ts
│   └── dmarcReportService.ts
├── types/
│   └── dmarc.ts
└── utils/
    ├── dateUtils.ts
    └── chartUtils.ts
```

### 3.2 コンポーネント命名規則

```typescript
// ファイル名: パスカルケース
// Dashboard.tsx, ReportList.tsx

// コンポーネント名: パスカルケース
export const Dashboard: React.FC<DashboardProps> = ({ ... }) => { ... }

// インターフェース命名: パスカルケース + Props
interface DashboardProps {
  dateRange: DateRange;
  onDateRangeChange: (range: DateRange) => void;
}

// 型定義: パスカルケース
type DateRange = {
  startDate: Date;
  endDate: Date;
};
```

### 3.3 型定義規約

```typescript
// types/dmarc.ts
export interface DmarcReport {
  id: number;
  orgName: string;
  email: string;
  reportId: string;
  beginDate: string;
  endDate: string;
  policyDomain: string;
  policyP: "none" | "quarantine" | "reject";
  policyPct: number;
  records: DmarcRecord[];
  createdAt: string;
  updatedAt: string;
}

export interface DmarcRecord {
  id: number;
  sourceIp: string;
  count: number;
  disposition: string;
  dkimAligned: boolean;
  dkimResult: string;
  spfAligned: boolean;
  spfResult: string;
}

export interface DashboardSummary {
  totalReports: number;
  totalEmails: number;
  authSuccessCount: number;
  authFailureCount: number;
  policyBreakdown: {
    none: number;
    quarantine: number;
    reject: number;
  };
}
```

### 3.4 フック命名規則

```typescript
// カスタムフック: use + 機能名
export const useDmarcReports = (filters: ReportFilters) => {
  const [reports, setReports] = useState<DmarcReport[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // ...

  return { reports, loading, error, refetch };
};

export const useReportFilters = () => {
  const [filters, setFilters] = useState<ReportFilters>({
    dateRange: { startDate: new Date(), endDate: new Date() },
    sourceIp: "",
    domain: "",
    authResult: "all",
  });

  return { filters, setFilters, resetFilters };
};
```

### 3.5 API 呼び出し規約

```typescript
// services/api.ts
const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || "/api";

export const apiClient = {
  async get<T>(endpoint: string, params?: Record<string, any>): Promise<T> {
    const url = new URL(`${API_BASE_URL}${endpoint}`);
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          url.searchParams.append(key, String(value));
        }
      });
    }

    const response = await fetch(url.toString(), {
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`);
    }

    return response.json();
  },

  async post<T>(endpoint: string, data: any): Promise<T> {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`);
    }

    return response.json();
  },
};
```

## 4. セキュリティ規約

### 4.1 入力値検証

```php
// Laravel Request クラスでの検証
class DmarcReportImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'directory' => 'required|string|max:255',
            'files' => 'array',
            'files.*' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'directory.required' => 'ディレクトリパスは必須です。',
            'directory.max' => 'ディレクトリパスは255文字以内で入力してください。',
        ];
    }
}
```

### 4.2 SQL インジェクション対策

```php
// プリペアドステートメントの使用
$reports = DmarcReport::where('org_name', $orgName)
    ->whereBetween('begin_date', [$startDate, $endDate])
    ->get();

// 生SQL使用時は必ずバインドパラメータを使用
$reports = DB::select(
    'SELECT * FROM dmarc_reports WHERE org_name = ? AND begin_date >= ?',
    [$orgName, $startDate]
);
```

### 4.3 XSS 対策

```php
// Blade テンプレートでの自動エスケープ
{{ $report->orgName }}

// JavaScript でのエスケープ
// React では自動的にエスケープされる
```

## 5. パフォーマンス規約

### 5.1 データベース最適化

```php
// N+1問題の回避
$reports = DmarcReport::with(['records'])->get();

// インデックスの活用
// マイグレーションで適切なインデックスを設定

// クエリの最適化
$summary = DmarcReport::selectRaw('
    COUNT(*) as total_reports,
    SUM(JSON_LENGTH(records)) as total_emails,
    SUM(CASE WHEN policy_p = "none" THEN 1 ELSE 0 END) as policy_none_count
')->first();
```

### 5.2 キャッシュ戦略

```php
// キャッシュの活用
$summary = Cache::remember('dashboard_summary', 3600, function () {
    return DmarcReport::getSummaryData();
});

// フロントエンドでのキャッシュ
const { data: reports } = useQuery(['reports', filters],
  () => fetchReports(filters),
  { staleTime: 5 * 60 * 1000 } // 5分間キャッシュ
);
```

## 6. テスト規約

### 6.1 PHPUnit テスト

```php
// テストクラス命名: {クラス名}Test
class DmarcReportParserServiceTest extends TestCase
{
    public function test_parse_valid_xml_report(): void
    {
        // Arrange
        $xmlContent = $this->getValidXmlContent();
        $service = new DmarcReportParserService();

        // Act
        $report = $service->parseXmlReport($xmlContent);

        // Assert
        $this->assertInstanceOf(DmarcReport::class, $report);
        $this->assertEquals('example.com', $report->org_name);
    }

    public function test_parse_invalid_xml_throws_exception(): void
    {
        // Arrange
        $invalidXml = '<invalid>xml</content>';
        $service = new DmarcReportParserService();

        // Act & Assert
        $this->expectException(DmarcReportParseException::class);
        $service->parseXmlReport($invalidXml);
    }
}
```

### 6.2 Jest テスト

```typescript
// テストファイル命名: {ファイル名}.test.tsx
describe("Dashboard", () => {
  it("should render summary cards", () => {
    render(<Dashboard />);

    expect(screen.getByText("総レポート数")).toBeInTheDocument();
    expect(screen.getByText("総メール数")).toBeInTheDocument();
  });

  it("should display loading state", () => {
    render(<Dashboard />);

    expect(screen.getByTestId("loading-spinner")).toBeInTheDocument();
  });
});
```

## 7. コミットメッセージ規約

### 7.1 コミットメッセージ形式

```
<type>(<scope>): <subject>

<body>

<footer>
```

### 7.2 タイプ一覧

- `feat`: 新機能
- `fix`: バグ修正
- `docs`: ドキュメント更新
- `style`: コードスタイル修正
- `refactor`: リファクタリング
- `test`: テスト追加・修正
- `chore`: その他の変更

### 7.3 コミット例

```
feat(dmarc): DMARCレポート取り込み機能を実装

- XMLパーサーサービスの追加
- データベースマイグレーションの作成
- Artisanコマンドの実装

Closes #123
```

## 8. 環境設定規約

### 8.1 .env ファイル

```env
# アプリケーション設定
APP_NAME="DMARCレポート可視化"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# データベース設定
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dmarc_reports
DB_USERNAME=dmarc_user
DB_PASSWORD=secure_password

# ロケール設定
APP_LOCALE=ja
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Tokyo

# DMARC設定
DMARC_REPORTS_DIRECTORY=/app/storage/app/dmarc_reports
DMARC_MAX_FILE_SIZE=10485760
```

### 8.2 Docker 設定

```yaml
# docker-compose.yml
version: "3.8"
services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - ./src:/app
      - dmarc_reports:/app/storage/app/dmarc_reports
    environment:
      - DB_HOST=db
    depends_on:
      - db

  db:
    image: mysql:8.4
    environment:
      MYSQL_DATABASE: dmarc_reports
      MYSQL_USER: dmarc_user
      MYSQL_PASSWORD: secure_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

volumes:
  mysql_data:
  dmarc_reports:
```

## 9. 品質管理規約

### 9.1 PHPStan 設定

```neon
# phpstan.neon
parameters:
  level: max
  paths:
    - app
    - tests
  excludePaths:
    - app/Console/Kernel.php
  checkMissingIterableValueType: false
  checkGenericClassInNonGenericObjectType: false
```

### 9.2 コードレビュー基準

- [ ] コーディング規約に準拠しているか
- [ ] 適切なエラーハンドリングが実装されているか
- [ ] セキュリティ上の問題がないか
- [ ] パフォーマンスに配慮されているか
- [ ] テストが適切に書かれているか
- [ ] ドキュメントが更新されているか

## 10. 進捗管理

### 10.1 進捗ステータス

- **未着手**: 作業開始前
- **着手済**: 作業開始済み（完了前）
- **完了**: 作業完了
- **レビュー中**: レビュー待ち
- **保留**: 一時中断

### 10.2 進捗追跡例

```markdown
## DMARC レポート取り込み機能

- [x] XML パーサー実装
- [x] データベース設計
- [ ] Artisan コマンド実装
- [ ] エラーハンドリング
- [ ] テスト作成
```

---

このコーディング規約は、プロジェクトの進行に合わせて随時更新・改善を行います。
