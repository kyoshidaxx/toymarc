# DMARC Reports アプリケーション コーディングガイド

## 概要

このドキュメントは、DMARC Reports 可視化アプリケーションの開発におけるコーディング規約とデザインシステムを定義します。

## 技術スタック

- **バックエンド**: Laravel 11 (PHP)
- **フロントエンド**: React 18 + TypeScript
- **UI フレームワーク**: Tailwind CSS
- **グラフライブラリ**: Recharts
- **フルスタック**: Inertia.js
- **ビルドツール**: Vite

## デザインシステム

### 1. レイアウト構造

#### 基本レイアウト

```jsx
import Layout from "@/components/Layout";

export default function PageComponent({ data }) {
  return (
    <Layout>
      <Head title="ページタイトル" />

      <div className="container mx-auto px-4 py-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-8">
          ページタイトル
        </h1>

        {/* コンテンツ */}
      </div>
    </Layout>
  );
}
```

#### 必須要素

- **Layout コンポーネント**: 全ページで必須
- **Head コンポーネント**: ページタイトル設定
- **container**: 中央寄せレイアウト
- **px-4 py-8**: 統一されたパディング
- **text-3xl font-bold text-gray-900 mb-8**: 統一されたページタイトル

### 2. 統計カード

#### 基本構造

```jsx
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

<Card>
  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
    <CardTitle className="text-sm font-medium">カードタイトル</CardTitle>
  </CardHeader>
  <CardContent>
    <div className="text-2xl font-bold">メイン数値</div>
    <p className="text-xs text-muted-foreground">補足説明</p>
  </CardContent>
</Card>;
```

#### 統計カードのグリッドレイアウト

```jsx
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  {/* 統計カード */}
</div>
```

#### カードタイトルのスタイル

- **フォントサイズ**: `text-sm`
- **フォントウェイト**: `font-medium`
- **色**: デフォルト（`text-gray-900`）

#### メイン数値のスタイル

- **フォントサイズ**: `text-2xl`
- **フォントウェイト**: `font-bold`
- **色**: デフォルト（`text-gray-900`）
- **数値フォーマット**: `toLocaleString()` を使用

#### 補足説明のスタイル

- **フォントサイズ**: `text-xs`
- **色**: `text-muted-foreground`
- **内容**: 簡潔で分かりやすい説明

### 3. グラフセクション

#### 基本構造

```jsx
<div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <Card>
    <CardHeader>
      <CardTitle>グラフタイトル</CardTitle>
    </CardHeader>
    <CardContent>
      <ResponsiveContainer width="100%" height={300}>
        {/* グラフコンポーネント */}
      </ResponsiveContainer>
    </CardContent>
  </Card>
</div>
```

#### グラフの設定

- **高さ**: `height={300}` で統一
- **レスポンシブ**: `ResponsiveContainer` を使用
- **カード内配置**: Card コンポーネントで囲む

#### グラフの色設定

```jsx
const COLORS = ["#0088FE", "#00C49F", "#FFBB28", "#FF8042", "#8884D8"];
```

### 4. テーブルセクション

#### 基本構造

```jsx
<Card>
  <CardHeader>
    <CardTitle>テーブルタイトル</CardTitle>
  </CardHeader>
  <CardContent>
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              ヘッダー
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          <tr className="hover:bg-gray-50">
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              データ
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </CardContent>
</Card>
```

#### テーブルのスタイル

- **ヘッダー**: `bg-gray-50`, `text-xs font-medium text-gray-500 uppercase`
- **セル**: `px-6 py-4`, `text-sm`
- **ホバー効果**: `hover:bg-gray-50`
- **境界線**: `divide-y divide-gray-200`

### 5. ナビゲーション

#### ナビゲーション構造

```jsx
<nav className="bg-white shadow-sm border-b">
  <div className="container mx-auto px-4">
    <div className="flex justify-between items-center h-16">
      {/* ロゴ */}
      <div className="flex items-center">
        <Link href="/dashboard" className="flex items-center space-x-2">
          <span className="text-2xl">🛡️</span>
          <span className="text-xl font-bold text-gray-900">DMARC Reports</span>
        </Link>
      </div>

      {/* ナビゲーションリンク */}
      <div className="hidden md:flex space-x-8">
        {navigation.map((item) => (
          <Link
            key={item.name}
            href={item.href}
            className={`flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
              isActive
                ? "bg-blue-100 text-blue-700"
                : "text-gray-600 hover:text-gray-900 hover:bg-gray-100"
            }`}
          >
            <span>{item.icon}</span>
            <span>{item.name}</span>
          </Link>
        ))}
      </div>
    </div>
  </div>
</nav>
```

#### ナビゲーションアイテム

```jsx
const navigation = [
  { name: "Dashboard", href: "/dashboard", icon: "📊" },
  { name: "DMARC Reports", href: "/dmarc-reports", icon: "📋" },
  { name: "Analytics", href: "/analytics", icon: "📈" },
  { name: "Settings", href: "/settings", icon: "⚙️" },
];
```

### 6. 色システム

#### 基本色

- **プライマリ**: `text-blue-600`, `bg-blue-100`
- **成功**: `text-green-600`, `bg-green-100`
- **警告**: `text-yellow-600`, `bg-yellow-100`
- **エラー**: `text-red-600`, `bg-red-100`
- **情報**: `text-purple-600`, `bg-purple-100`

#### テキスト色

- **メインテキスト**: `text-gray-900`
- **セカンダリテキスト**: `text-gray-500`
- **補助テキスト**: `text-muted-foreground`
- **リンク**: `text-blue-600`

#### 背景色

- **ページ背景**: `bg-gray-50`
- **カード背景**: `bg-white`
- **テーブルヘッダー**: `bg-gray-50`

### 7. タイポグラフィ

#### 見出し

- **ページタイトル**: `text-3xl font-bold text-gray-900 mb-8`
- **セクションタイトル**: `text-xl font-semibold mb-4`
- **カードタイトル**: `text-sm font-medium`

#### テキスト

- **メインテキスト**: `text-sm text-gray-900`
- **補足テキスト**: `text-xs text-muted-foreground`
- **数値**: `text-2xl font-bold`

### 8. スペーシング

#### マージン

- **ページタイトル下**: `mb-8`
- **セクション間**: `mb-8`
- **カード間**: `gap-6`

#### パディング

- **ページ全体**: `px-4 py-8`
- **カード内**: デフォルト（Card コンポーネント）
- **テーブルセル**: `px-6 py-4`

### 9. レスポンシブデザイン

#### ブレークポイント

- **モバイル**: `grid-cols-1`
- **タブレット**: `md:grid-cols-2`
- **デスクトップ**: `lg:grid-cols-4`

#### グリッドレイアウト

```jsx
// 統計カード
<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

// グラフセクション
<div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
```

### 10. コンポーネント設計原則

#### 再利用性

- **Card コンポーネント**: 全セクションで統一使用
- **Layout コンポーネント**: 全ページで必須
- **Navigation コンポーネント**: 全ページで共通

#### 一貫性

- **命名規則**: 統一されたクラス名
- **構造**: 統一された HTML 構造
- **スタイル**: 統一された CSS クラス

#### 保守性

- **モジュラー設計**: 小さなコンポーネントに分割
- **明確な責任**: 各コンポーネントの役割を明確化
- **ドキュメント化**: 重要な決定事項を記録

### 11. パフォーマンス最適化

#### キャッシュ戦略

- **統計データ**: 5 分間キャッシュ
- **アクティビティ**: 1 分間キャッシュ
- **分析データ**: 日付範囲別キャッシュ

#### コード分割

- **動的インポート**: 大きなコンポーネントは遅延読み込み
- **バンドル最適化**: Vite の自動最適化を活用

### 12. アクセシビリティ

#### セマンティック HTML

- **適切な見出し階層**: h1 → h2 → h3
- **テーブル**: 適切な th, td の使用
- **フォーム**: label と input の関連付け

#### キーボードナビゲーション

- **フォーカス可能**: 全てのインタラクティブ要素
- **フォーカス表示**: 明確なフォーカスインジケーター

### 13. テスト戦略

#### コンポーネントテスト

- **レンダリングテスト**: 正しく表示されるか
- **インタラクションテスト**: クリックやフォーム送信
- **レスポンシブテスト**: 各ブレークポイントでの表示

#### 統合テスト

- **ページ遷移**: ナビゲーションの動作
- **データ表示**: API からのデータ表示
- **エラーハンドリング**: エラー時の表示

## まとめ

このコーディングガイドに従うことで、一貫性のある、保守しやすい、ユーザーフレンドリーなアプリケーションを構築できます。新しい機能を追加する際は、必ずこのガイドラインに従ってください。
