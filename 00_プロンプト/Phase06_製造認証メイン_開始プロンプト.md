# Phase 06 製造（認証・メイン画面）開始プロンプト

**作成日**: 2025-11-25  
**前Phase**: Phase 05（製造 - 基盤構築）  
**次Phase**: Phase 06（製造 - 認証・メイン画面）

---

## プロジェクト概要

### 達成目標
現在Accessで動いてるシステムのWEB版をリリースする

### システム概要
自社の社員向けに、日々の顧客からの問い合わせや、それに対する対応を入力し、記録・分析を行うもの

### 技術スタック
- **DB**: SQL Server 2022（継続）
- **DB接続**: PDO（PHP）
- **データ操作**: ストアドプロシージャ（継続）/（一部PHP）
- **フロント**: PHP 7.4+ / HTML5 / CSS3 / jQuery 1.11.3

---

## 作業ディレクトリ

- **作業ディレクトリ**: `D:\#M\PG_DATA\OIS社内システム\PG_DATA\サポート報告書WEB\`
- **検証ディレクトリ**: `\\LPG-NEMA\C\inetpub\wwwroot\support-system\`
- **本番ディレクトリ**: `\\DEV-SE\C\inetpub\wwwroot\support-system\`

---

## Phase 05 完了成果物

### 04_製造/includes/
1. `config.php` - 設定ファイル（DB接続、セッション設定、定数定義）
2. `auth.php` - 認証モジュール（ログイン、ログアウト、権限チェック）
3. `functions.php` - 共通関数（エスケープ、VBA互換関数、Ajax応答）
4. `error.php` - エラーハンドリング（エラークラス、グローバルハンドラー）
5. `validation.php` - バリデーション（Validatorクラス、検証関数）

### 04_製造/css/
1. `common.css` - 共通CSS（リセット、レイアウト、フォーム、テーブル等）
2. `pc.css` - PC用CSS（769px以上）
3. `tablet.css` - タブレット用CSS（481px〜768px）
4. `sp.css` - スマートフォン用CSS（480px以下）

### 04_製造/js/
1. `common.js` - 共通JavaScript（Ajax、メッセージ、モーダル、ユーティリティ）
2. `support.js` - サポート報告書用JavaScript（検索、CRUD、ダイアログ）

### 04_製造/
- `error_page.php` - エラーページ
- `logs/` - ログディレクトリ

---

## Phase 06 作業内容

### 目的
Phase 05で作成した基盤ファイルを使用して、ログイン画面とメイン画面の基本形を実装する。

### 成果物

#### 1. 認証関連
```
04_製造/
├── login.php           # ログイン画面
├── logout.php          # ログアウト処理
```

#### 2. メイン画面
```
04_製造/
├── support_main.php    # メイン画面（一覧表示）
├── support_ajax01.php  # Ajax処理
```

#### 3. 共通テンプレート
```
04_製造/
├── templates/
│   ├── header.php      # ヘッダーテンプレート
│   └── footer.php      # フッターテンプレート
```

### 実装項目

#### login.php
- ログインフォーム（担当者コード、パスワード）
- 認証処理
- エラー表示
- セッション初期化

#### support_main.php
- ヘッダー（システム名、ユーザー情報、ログアウト）
- 検索エリア（日付範囲、担当者、キーワード等）
- 一覧表示テーブル
- ページネーション
- 新規登録ボタン

#### support_ajax01.php
- 検索処理（action=search）
- データ取得（action=get）
- 表示件数変更（action=change_display_count）
- ソート変更（action=sort）

### 格納先
- `D:\#M\PG_DATA\OIS社内システム\PG_DATA\サポート報告書WEB\04_製造\`

---

## 必須参照ドキュメント

### 規約書
1. `01_規約/開発規約書.md` - 開発の基本方針、技術スタック
2. `01_規約/コーディング規約書.md` - コーディングルール

### 設計書
1. `03_設計/画面設計書/login.md` - ログイン画面設計
2. `03_設計/画面設計書/main.md` - メイン画面設計
3. `03_設計/機能設計書/auth.md` - 認証機能設計
4. `03_設計/機能設計書/search.md` - 検索機能設計

### 基盤ファイル（Phase 05成果物）
1. `04_製造/includes/config.php` - 設定ファイル
2. `04_製造/includes/auth.php` - 認証モジュール
3. `04_製造/includes/functions.php` - 共通関数
4. `04_製造/includes/error.php` - エラーハンドリング
5. `04_製造/includes/validation.php` - バリデーション
6. `04_製造/css/common.css` - 共通CSS
7. `04_製造/js/common.js` - 共通JavaScript

---

## 作業進行の制約

1. **大容量ファイルの分割処理**
   - ファイル読み込みは必要に応じて分割
   - ファイル書き込みも大きい場合は分割

2. **成果物出力**
   - 作業ディレクトリのみにファイル出力
   - チャットでは出力結果の報告のみ

3. **トークン節約**
   - 長尺タスクは分割処理
   - 不要な出力を控える

4. **チャット容量管理**
   - 容量オーバーになる前に次チャット立ち上げを促す
   - プロンプトファイルを生成して継続性を確保

---

## 実装仕様

### login.php 実装項目

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン | サポート報告書システム</title>
    <!-- CSS読み込み -->
</head>
<body>
    <div class="login-container">
        <h1>サポート報告書システム</h1>
        <form method="post" action="">
            <!-- エラーメッセージ表示 -->
            <div class="form-group">
                <label>担当者コード</label>
                <input type="text" name="user_id" required>
            </div>
            <div class="form-group">
                <label>パスワード</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">ログイン</button>
        </form>
    </div>
</body>
</html>
```

### support_main.php 基本構造

```php
<?php
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// 認証チェック
requireLogin();

// 検索パラメータ取得
$search_params = getSearchParams();
$current_page = getCurrentPage();
$display_count = getDisplayCount();
$sort_params = getSortParams();

// 初期データ取得
// ...
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- 共通ヘッダー -->
</head>
<body>
    <?php include("templates/header.php"); ?>
    
    <div class="main-content">
        <!-- 検索エリア -->
        <!-- 一覧テーブル -->
        <!-- ページネーション -->
    </div>
    
    <?php include("templates/footer.php"); ?>
</body>
</html>
```

### support_ajax01.php 基本構造

```php
<?php
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");

// Ajax用セッションチェック
checkSessionForAjax();

// アクション取得
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'search':
        // 検索処理
        break;
    case 'get':
        // データ取得
        break;
    case 'change_display_count':
        // 表示件数変更
        break;
    case 'sort':
        // ソート変更
        break;
    default:
        jsonError('不正なリクエストです。', ERR_SYS_INVALID_REQUEST);
        break;
}
```

---

## 作業開始指示

1. 上記の必須参照ドキュメントを読み込む
2. templates/ディレクトリを作成
3. login.phpを作成（ログイン画面）
4. logout.phpを作成（ログアウト処理）
5. templates/header.phpを作成
6. templates/footer.phpを作成
7. support_main.phpを作成（メイン画面）
8. support_ajax01.phpを作成（Ajax処理）
9. 動作確認項目を整理
10. Phase 07用プロンプトを生成

---

## 確認事項

Phase 06開始時に以下を確認してください：

- [ ] 04_製造/includes/config.php を読み込んだ
- [ ] 04_製造/includes/auth.php を読み込んだ
- [ ] 04_製造/includes/functions.php を読み込んだ
- [ ] 04_製造/css/common.css を読み込んだ
- [ ] 04_製造/js/common.js を読み込んだ
- [ ] 03_設計/画面設計書/login.md を参照した
- [ ] 03_設計/画面設計書/main.md を参照した

上記確認後、作業を開始してください。

---

## 品質チェックリスト

Phase 06完了時に以下を確認：

- [ ] ログイン画面が正常に表示されるか
- [ ] 認証処理が正常に動作するか
- [ ] 認証失敗時にエラーメッセージが表示されるか
- [ ] ログイン成功後にメイン画面へ遷移するか
- [ ] メイン画面が正常に表示されるか
- [ ] ヘッダーにユーザー情報が表示されるか
- [ ] ログアウトが正常に動作するか
- [ ] 検索条件がセッションに保持されるか
- [ ] レスポンシブ対応（PC/タブレット/SP）ができているか

---

## データベース接続情報

```php
// config.php に定義済み
define('DB_SERVER', 'dev-se02\\SQL22');
define('DB_NAME', 'SUPPORTDB');
define('DB_USER', 'sa');
define('DB_PASS', 'OIS8973113fmv');
```

## 認証用テーブル

```sql
-- SQL_作業担当テーブル
SELECT 担当者コード, 担当者名, 部門コード, パスワード, 権限レベル
FROM SQL_作業担当
WHERE 担当者コード = ? AND パスワード = ?
```

---

**作成者**: Claude AI  
**Phase 05完了日**: 2025-11-25
