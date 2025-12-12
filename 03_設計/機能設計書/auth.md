# 認証機能設計書

**作成日**: 2025-11-25  
**更新日**: 2025-12-04  
**バージョン**: 1.1  
**Phase**: 03  
**機能ID**: auth  
**関連ファイル**: login.php, logout.php, includes/auth.php

---

## 1. 概要

### 1.1 機能の目的
ユーザーの認証とセッション管理を行い、システムへのアクセス制御を実現する。

### 1.2 機能範囲
- ログイン認証
- ログアウト処理
- セッション管理（開始・維持・終了）
- 権限制御
- セッションタイムアウト処理

---

## 2. 機能一覧

| No | 機能名 | 機能ID | 説明 |
|----|-------|--------|------|
| 1 | ログイン | auth_login | ユーザー認証とセッション開始 |
| 2 | ログアウト | auth_logout | セッション終了 |
| 3 | セッション確認 | auth_check | 認証状態の確認 |
| 4 | 権限確認 | auth_level | 権限レベルの確認 |
| 5 | タイムアウト | auth_timeout | セッションタイムアウト処理 |

---

## 3. ログイン処理（auth_login）

### 3.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│入力値取得           │
│・ユーザーコード     │
│・パスワード         │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│入力値サニタイズ      │
│htmlspecialchars()   │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│入力値バリデーション   │
│・必須チェック        │
│・形式チェック        │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │      （処理終了）
    │
    ↓
┌─────────────────────┐
│DB認証処理           │
│SEL_SQL_作業担当     │
│WHERE ユーザーコード │
│AND パスワード       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  認証成功  認証失敗
    │         │
    ↓         ↓
┌─────────┐  エラー返却
│セッション │  「認証失敗」
│開始処理   │
└────┬────┘
     │
     ↓
┌─────────────────────┐
│セッション変数設定    │
│・LOGIN = TRUE       │
│・USER_CODE          │
│・USER_NAME          │
│・BUMON_CODE         │
│・AUTH_LEVEL         │
│・LOGIN_TIME         │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│セッションID再生成    │
│session_regenerate_id│
└────────┬────────────┘
         │
         ↓
   認証成功（リダイレクト）
         │
        END
```

### 3.2 認証SQL

> **変更 (2025-12-04)**: SQL_作業担当テーブルはSUPPORTDB内のローカルテーブルに変更。
> 権限レベルカラムは存在しないため、取得対象から削除。

```sql
-- SEL_SQL_作業担当_認証
-- 参照先: DEV-SE02\SQL22 / SUPPORTDB / SQL_作業担当
SELECT 
    担当者コード,
    担当者名,
    部門コード
FROM 
    SQL_作業担当
WHERE 
    担当者コード = @user_code
    AND パスワード = @password
```

**備考**:
- 削除日時カラムはテーブルに存在しないため、条件から削除
- 権限レベルカラムはテーブルに存在しないため、WEB版では全ユーザーを同等権限として扱う

### 3.3 セッション変数

> **変更 (2025-12-04)**: AUTH_LEVELはDBから取得できないため、固定値を設定

| 変数名 | 型 | 説明 |
|--------|-----|------|
| LOGIN | bool | ログイン状態（TRUE/FALSE） |
| USER_CODE | int | ユーザーコード（担当者コード） |
| USER_NAME | string | ユーザー名（担当者名） |
| BUMON_CODE | int | 部門コード |
| AUTH_LEVEL | int | 権限レベル（固定値: 2）※全ユーザー管理者権限として扱う |
| LOGIN_TIME | int | ログイン時刻（Unix timestamp） |
| LAST_ACCESS | int | 最終アクセス時刻（Unix timestamp） |

### 3.4 実装コード

> **変更 (2025-12-04)**: カラム名を実際のテーブル定義に合わせて修正、AUTH_LEVELは固定値設定

```php
/**
 * ログイン処理
 * 
 * @param int $user_code ユーザーコード
 * @param string $password パスワード
 * @return array 処理結果
 */
function doLogin($user_code, $password) {
    global $pdo_conn;
    
    // 入力値サニタイズ
    $user_code = chkSunitize($user_code);
    $password = chkSunitize($password);
    
    // 入力値バリデーション
    $errors = array();
    
    if (empty($user_code)) {
        $errors[] = 'ユーザーコードを入力してください。';
    } elseif (!is_numeric($user_code)) {
        $errors[] = 'ユーザーコードは数字で入力してください。';
    }
    
    if (empty($password)) {
        $errors[] = 'パスワードを入力してください。';
    }
    
    if (count($errors) > 0) {
        return array('status' => 'error', 'errors' => $errors);
    }
    
    // DB認証
    // 参照先: DEV-SE02\SQL22 / SUPPORTDB / SQL_作業担当
    try {
        // カラム名を英語エイリアスで取得（文字化け対策）
        $sql = "SELECT 
                    担当者コード AS tantou_code,
                    担当者名 AS tantou_name,
                    部門コード AS bumon_code
                FROM SQL_作業担当
                WHERE 担当者コード = :user_code
                  AND パスワード = :password";
        $stmt = $pdo_conn->prepare($sql);
        $stmt->bindValue(':user_code', $user_code, PDO::PARAM_INT);
        $stmt->bindValue(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return array(
                'status' => 'error', 
                'errors' => array('ユーザーコードまたはパスワードが正しくありません。')
            );
        }
        
        // セッション開始・変数設定
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // セッションID再生成（セッション固定攻撃対策）
        session_regenerate_id(true);
        
        // セッション変数設定
        $_SESSION['LOGIN'] = true;
        $_SESSION['USER_CODE'] = $row['tantou_code'];
        $_SESSION['USER_NAME'] = $row['tantou_name'];
        $_SESSION['BUMON_CODE'] = $row['bumon_code'];
        $_SESSION['AUTH_LEVEL'] = 2;  // 固定値: 全ユーザー管理者権限
        $_SESSION['LOGIN_TIME'] = time();
        $_SESSION['LAST_ACCESS'] = time();
        
        return array('status' => 'success');
        
    } catch (PDOException $e) {
        return array(
            'status' => 'error', 
            'errors' => array('データベースエラーが発生しました。')
        );
    }
}
```

---

## 4. ログアウト処理（auth_logout）

### 4.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│セッション変数破棄    │
│$_SESSION = array()  │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│セッションCookie破棄  │
│setcookie()          │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│セッション破棄        │
│session_destroy()    │
└────────┬────────────┘
         │
         ↓
   ログイン画面へリダイレクト
         │
        END
```

### 4.2 実装コード

```php
/**
 * ログアウト処理
 */
function doLogout() {
    // セッション開始（開始済みでない場合）
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // セッション変数を破棄
    $_SESSION = array();
    
    // セッションCookieを破棄
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // セッションを破棄
    session_destroy();
    
    // ログイン画面へリダイレクト
    header('Location: login.php');
    exit;
}
```

---

## 5. セッション確認処理（auth_check）

### 5.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│セッション開始        │
│（開始済みでなければ）│
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│LOGIN変数確認         │
│$_SESSION['LOGIN']   │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  TRUE     FALSE/未設定
    │         │
    ↓         ↓
┌─────────┐  未認証
│タイムアウト│  （ログイン画面へ）
│確認       │
└────┬────┘
     │
     ↓
┌─────────────────────┐
│最終アクセスから      │
│9時間経過？           │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   NO        YES
    │         │
    ↓         ↓
認証有効    タイムアウト
    │       （ログイン画面へ）
    ↓
┌─────────────────────┐
│LAST_ACCESS更新       │
└────────┬────────────┘
         │
        END
```

### 5.2 実装コード

```php
/**
 * セッション確認処理
 * 
 * @param bool $redirect 未認証時にリダイレクトするか
 * @return bool 認証状態
 */
function checkAuth($redirect = true) {
    // セッション開始
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ログイン状態確認
    if (!isset($_SESSION['LOGIN']) || $_SESSION['LOGIN'] !== true) {
        if ($redirect) {
            header('Location: login.php');
            exit;
        }
        return false;
    }
    
    // タイムアウト確認（9時間 = 32400秒）
    $timeout = 32400;
    if (isset($_SESSION['LAST_ACCESS']) && 
        (time() - $_SESSION['LAST_ACCESS']) > $timeout) {
        // タイムアウト：セッション破棄
        doLogout();
        return false;
    }
    
    // 最終アクセス時刻更新
    $_SESSION['LAST_ACCESS'] = time();
    
    return true;
}
```

---

## 6. 権限確認処理（auth_level）

### 6.1 権限レベル定義

| レベル | 名称 | 権限内容 |
|--------|------|---------|
| 1 | 一般ユーザー | 自分のデータの閲覧・編集 |
| 2 | 管理者 | 全データの閲覧・編集、マスタ設定 |

### 6.2 実装コード

```php
/**
 * 権限レベル確認
 * 
 * @param int $required_level 必要な権限レベル
 * @param bool $redirect 権限不足時にリダイレクトするか
 * @return bool 権限有無
 */
function checkAuthLevel($required_level, $redirect = true) {
    // セッション確認
    if (!checkAuth($redirect)) {
        return false;
    }
    
    // 権限レベル確認
    $user_level = isset($_SESSION['AUTH_LEVEL']) ? $_SESSION['AUTH_LEVEL'] : 0;
    
    if ($user_level < $required_level) {
        if ($redirect) {
            // 権限不足：メイン画面へリダイレクト
            header('Location: support_main.php');
            exit;
        }
        return false;
    }
    
    return true;
}

/**
 * 管理者権限確認
 * 
 * @return bool 管理者権限の有無
 */
function isAdmin() {
    return checkAuthLevel(2, false);
}
```

---

## 7. 各画面での使用方法

### 7.1 認証必須ページの先頭

```php
<?php
// 共通インクルード
require_once("../../func/inc.php");
require_once("includes/auth.php");

// 認証確認（未認証ならログイン画面へリダイレクト）
checkAuth();

// 以降は認証済みの場合のみ実行
?>
```

### 7.2 管理者専用ページの先頭

```php
<?php
// 共通インクルード
require_once("../../func/inc.php");
require_once("includes/auth.php");

// 管理者権限確認（権限不足ならメイン画面へリダイレクト）
checkAuthLevel(2);

// 以降は管理者権限がある場合のみ実行
?>
```

### 7.3 権限による表示制御

```php
<?php if (isAdmin()): ?>
    <!-- 管理者のみ表示されるボタン -->
    <button id="btn-master">マスタ設定</button>
<?php endif; ?>
```

---

## 8. セキュリティ対策

### 8.1 セッション固定攻撃対策

- ログイン成功時に `session_regenerate_id(true)` を実行
- 古いセッションIDを無効化

### 8.2 ブルートフォース攻撃対策

- 今回は社内ネットワーク限定のため省略
- 将来の外部公開時には実装を検討

### 8.3 セッションハイジャック対策

- セッションCookieにhttponly属性を設定
- HTTPS環境ではsecure属性を設定

### 8.4 XSS対策

- 入力値は全てサニタイズ
- 出力時にhtmlspecialchars()でエスケープ

---

## 9. inc.phpでの設定

### 9.1 セッション設定

```php
// セッション設定
ini_set('session.gc_maxlifetime', 32400);    // 9時間
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.cookie_httponly', 1);

// セッション開始
session_start();
```

### 9.2 タイムゾーン設定

```php
// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');
```

### 9.3 キャッシュ制御

```php
// キャッシュ無効化
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
```

---

## 10. エラーメッセージ

### 10.1 ログイン時エラー

| コード | メッセージ |
|--------|-----------|
| AUTH_001 | ユーザーコードを入力してください。 |
| AUTH_002 | ユーザーコードは数字で入力してください。 |
| AUTH_003 | パスワードを入力してください。 |
| AUTH_004 | ユーザーコードまたはパスワードが正しくありません。 |
| AUTH_005 | データベースエラーが発生しました。 |

### 10.2 セッションエラー

| コード | メッセージ |
|--------|-----------|
| AUTH_010 | セッションが切れました。再度ログインしてください。 |
| AUTH_011 | 権限がありません。 |

---

## 11. 備考

### 11.1 既存システムとの互換性

> **変更 (2025-12-04)**: SQL_作業担当テーブルはSUPPORTDB内のローカルテーブルに変更

- SQL_作業担当テーブルはDEV-SE02\SQL22 / SUPPORTDB内に構築
- パスワードは平文保存（既存システムに準拠）
- 権限レベルカラムは存在しないため、全ユーザーを管理者権限として扱う
- 将来的にはハッシュ化と権限管理の実装を検討

### 11.2 テスト観点

- 正常ログイン：正しい認証情報での認証成功
- 認証失敗：誤った認証情報での認証失敗
- セッション維持：画面遷移後もセッション維持
- タイムアウト：9時間経過後のセッション切れ
- 権限制御：一般ユーザーの管理者ページアクセス拒否
- ログアウト：セッション完全破棄

---

**作成者**: Claude AI  
**レビュー**: Phase 03完了時  
**改訂履歴**:
- v1.0 (2025-11-25): 初版作成
- v1.1 (2025-12-04): SQL_作業担当テーブルをローカルテーブルに変更、認証SQLとセッション変数設定を更新、権限レベルを固定値に変更
