# DMARC Reports API Documentation

## 概要

DMARC レポート可視化アプリケーションの RESTful API です。DMARC レポートとレコードの取得、統計情報の取得、フィルタリング機能を提供します。

## ベース URL

```
http://localhost:8000/api
```

## 認証

現在のバージョンでは認証は不要です。

## レスポンス形式

### 成功レスポンス

```json
{
  "success": true,
  "data": {...},
  "pagination": {...}, // ページネーションがある場合
  "meta": {...} // メタデータ
}
```

### エラーレスポンス

```json
{
  "success": false,
  "message": "エラーメッセージ",
  "errors": {...}, // バリデーションエラーの場合
  "error": "詳細エラー" // デバッグモードの場合のみ
}
```

## エンドポイント

### 1. DMARC レポート一覧取得

**GET** `/api/dmarc/reports`

#### クエリパラメータ

| パラメータ      | 型      | 必須 | 説明                                         |
| --------------- | ------- | ---- | -------------------------------------------- |
| `start_date`    | string  | 任意 | 開始日 (YYYY-MM-DD)                          |
| `end_date`      | string  | 任意 | 終了日 (YYYY-MM-DD)                          |
| `org_name`      | string  | 任意 | 組織名でフィルタ                             |
| `policy_domain` | string  | 任意 | ポリシードメインでフィルタ                   |
| `search`        | string  | 任意 | 検索キーワード                               |
| `per_page`      | integer | 任意 | 1 ページあたりの件数 (1-100, デフォルト: 50) |

#### レスポンス例

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "org_name": "example.com",
      "report_id": "abc123",
      "begin_date": "2024-07-01",
      "end_date": "2024-07-02",
      "records": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 50,
    "total": 250,
    "from": 1,
    "to": 50
  },
  "meta": {
    "total_reports": 250,
    "filtered_reports": 50
  }
}
```

### 2. DMARC レポート詳細取得

**GET** `/api/dmarc/reports/{id}`

#### パスパラメータ

| パラメータ | 型      | 必須 | 説明        |
| ---------- | ------- | ---- | ----------- |
| `id`       | integer | 必須 | レポート ID |

#### レスポンス例

```json
{
  "success": true,
  "data": {
    "id": 1,
    "org_name": "example.com",
    "report_id": "abc123",
    "begin_date": "2024-07-01",
    "end_date": "2024-07-02",
    "records": [...]
  },
  "meta": {
    "records_count": 10,
    "total_emails": 1000
  }
}
```

### 3. 統計情報取得

**GET** `/api/dmarc/reports/statistics`

#### クエリパラメータ

| パラメータ   | 型     | 必須 | 説明                |
| ------------ | ------ | ---- | ------------------- |
| `start_date` | string | 任意 | 開始日 (YYYY-MM-DD) |
| `end_date`   | string | 任意 | 終了日 (YYYY-MM-DD) |

#### レスポンス例

```json
{
    "success": true,
    "data": {
        "total_reports": 100,
        "total_emails": 50000,
        "auth_success_count": 45000,
        "auth_failure_count": 5000,
        "auth_success_rate": 90.0,
        "policy_breakdown": {
            "none": 80,
            "quarantine": 15,
            "reject": 5
        },
        "top_source_ips": [
            {
                "source_ip": "192.168.1.1",
                "total_count": 1000,
                "success_count": 950,
                "success_rate": 95.0
            }
        ]
    },
    "meta": {
        "cache_ttl": 3600,
        "generated_at": "2024-07-03T20:00:00.000000Z"
    }
}
```

### 4. DMARC レコード取得

**GET** `/api/dmarc/records`

#### クエリパラメータ

| パラメータ    | 型      | 必須 | 説明                                         |
| ------------- | ------- | ---- | -------------------------------------------- |
| `source_ip`   | string  | 任意 | 送信元 IP アドレスでフィルタ                 |
| `auth_result` | string  | 任意 | 認証結果でフィルタ (`success` or `failure`)  |
| `disposition` | string  | 任意 | 処理結果でフィルタ                           |
| `start_date`  | string  | 任意 | 開始日 (YYYY-MM-DD)                          |
| `end_date`    | string  | 任意 | 終了日 (YYYY-MM-DD)                          |
| `per_page`    | integer | 任意 | 1 ページあたりの件数 (1-100, デフォルト: 50) |

#### レスポンス例

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "source_ip": "192.168.1.1",
      "count": 100,
      "dkim_aligned": true,
      "spf_aligned": false,
      "disposition": "none",
      "reason": null,
      "dmarc_report": {...}
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 50,
    "total": 500,
    "from": 1,
    "to": 50
  },
  "meta": {
    "total_records": 500,
    "filtered_records": 50
  }
}
```

### 5. フィルタオプション取得

**GET** `/api/dmarc/filter-options`

#### レスポンス例

```json
{
    "success": true,
    "data": {
        "organizations": ["example.com", "test.com"],
        "policy_domains": ["example.com", "test.com"],
        "dispositions": ["none", "quarantine", "reject"],
        "auth_results": ["success", "failure"]
    },
    "meta": {
        "cache_ttl": 3600,
        "generated_at": "2024-07-03T20:00:00.000000Z"
    }
}
```

## エラーコード

| HTTP ステータス | 説明                     |
| --------------- | ------------------------ |
| 200             | 成功                     |
| 400             | バリデーションエラー     |
| 404             | リソースが見つかりません |
| 422             | バリデーションエラー     |
| 500             | サーバーエラー           |

## 使用例

### cURL 例

```bash
# レポート一覧取得
curl "http://localhost:8000/api/dmarc/reports?start_date=2024-07-01&end_date=2024-07-31"

# 統計情報取得
curl "http://localhost:8000/api/dmarc/reports/statistics"

# レコード取得（認証失敗のみ）
curl "http://localhost:8000/api/dmarc/records?auth_result=failure"
```

### JavaScript 例

```javascript
// レポート一覧取得
const response = await fetch("/api/dmarc/reports?per_page=20");
const data = await response.json();

if (data.success) {
    console.log("レポート数:", data.meta.total_reports);
    console.log("レポート:", data.data);
}
```

## 注意事項

-   統計情報とフィルタオプションは 1 時間キャッシュされます
-   日付範囲フィルタは `start_date` と `end_date` の両方が必要です
-   ページネーションの最大件数は 100 件です
-   デバッグモードが無効の場合、エラーの詳細は返されません
