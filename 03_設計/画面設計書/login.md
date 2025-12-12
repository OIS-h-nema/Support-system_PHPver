# ログイン画面設計書

**作成日**: 2025-11-25  
**バージョン**: 1.0  
**Phase**: 03  
**画面ID**: login  
**ファイル名**: login.php

---

## 1. 概要

### 1.1 画面の目的
システムへのアクセスを認証し、正当なユーザーのみがサポート報告書システムを利用できるようにする。

### 1.2 画面の役割
- ユーザーコード（担当者コード）とパスワードによる認証
- 認証成功時のセッション開始
- メイン画面への遷移制御

### 1.3 アクセス条件
- 未認証ユーザー: アクセス可能
- 認証済みユーザー: メイン画面へリダイレクト

---

## 2. レイアウト設計

### 2.1 PC版レイアウト（1025px以上）

```
┌────────────────────────────────────────────────────────────────────┐
│                                                                    │
│                                                                    │
│                                                                    │
│                    ┌────────────────────────────┐                  │
│                    │   サポート報告書システム     │                  │
│                    │        ログイン             │                  │
│                    ├────────────────────────────┤                  │
│                    │                            │                  │
│                    │  ユーザーコード *           │                  │
│                    │  ┌────────────────────┐    │                  │
│                    │  │                    │    │                  │
│                    │  └────────────────────┘    │                  │
│                    │                            │                  │
│                    │  パスワード *              │                  │
│                    │  ┌────────────────────┐    │                  │
│                    │  │ ●●●●●●●●          │    │                  │
│                    │  └────────────────────┘    │                  │
│                    │                            │                  │
│                    │  ┌──────────────────────┐  │                  │
│                    │  │        ログイン       │  │                  │
│                    │  └──────────────────────┘  │                  │
│                    │                            │                  │
│                    └────────────────────────────┘                  │
│                                                                    │
│                          © 2025 OIS                                │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

**レイアウト仕様**:
- **ログインボックス**: 画面中央に配置
- **ログインボックス幅**: 400px
- **ログインボックス背景**: #ffffff
- **ログインボックス影**: box-shadow: 0 2px 10px rgba(0,0,0,0.1)
- **ページ背景**: #f5f5f5

### 2.2 Tablet版レイアウト（768px〜1024px）

```
┌──────────────────────────────────────────┐
│                                          │
│        ┌────────────────────────┐        │
│        │サポート報告書システム    │        │
│        │      ログイン           │        │
│        ├────────────────────────┤        │
│        │                        │        │
│        │  ユーザーコード *       │        │
│        │  ┌──────────────────┐  │        │
│        │  │                  │  │        │
│        │  └──────────────────┘  │        │
│        │                        │        │
│        │  パスワード *          │        │
│        │  ┌──────────────────┐  │        │
│        │  │                  │  │        │
│        │  └──────────────────┘  │        │
│        │                        │        │
│        │  ┌──────────────────┐  │        │
│        │  │     ログイン      │  │        │
│        │  └──────────────────┘  │        │
│        │                        │        │
│        └────────────────────────┘        │
│                                          │
│                © 2025 OIS                │
└──────────────────────────────────────────┘
```

**レイアウト仕様**:
- **ログインボックス幅**: 90%（最大400px）
- **マージン**: 上下中央、左右auto

### 2.3 SP版レイアウト（767px以下）

```
┌──────────────────────┐
│                      │
│ ┌──────────────────┐ │
│ │サポート報告書     │ │
│ │システム          │ │
│ │    ログイン      │ │
│ ├──────────────────┤ │
│ │                  │ │
│ │ユーザーコード *   │ │
│ │┌────────────────┐│ │
│ ││                ││ │
│ │└────────────────┘│ │
│ │                  │ │
│ │パスワード *      │ │
│ │┌────────────────┐│ │
│ ││                ││ │
│ │└────────────────┘│ │
│ │                  │ │
│ │┌────────────────┐│ │
│ ││    ログイン     ││ │
│ │└────────────────┘│ │
│ │                  │ │
│ └──────────────────┘ │
│                      │
│      © 2025 OIS      │
└──────────────────────┘
```

**レイアウト仕様**:
- **ログインボックス幅**: 95%
- **入力欄幅**: 100%
- **パディング**: 15px
- **フォントサイズ**: 16px（入力欄は16px以上でズーム防止）

---

## 3. 項目定義

### 3.1 入力項目

| No | 項目名 | 項目ID | name属性 | 種類 | 必須 | 最大桁数 | デフォルト値 |
|----|-------|--------|---------|------|------|---------|------------|
| 1 | ユーザーコード | user-code | user_code | テキスト | ○ | 10 | 空欄 |
| 2 | パスワード | password | password | パスワード | ○ | 50 | 空欄 |

### 3.2 項目詳細

#### 3.2.1 ユーザーコード
- **説明**: 担当者コード（SQL_作業担当の主キー）
- **入力形式**: 半角数字
- **表示形式**: テキストボックス
- **IME制御**: OFF（半角英数モード）
- **プレースホルダー**: 「担当者コードを入力」
- **バリデーション**:
  - 必須チェック
  - 数値形式チェック
  - 最大10桁

#### 3.2.2 パスワード
- **説明**: ログインパスワード
- **入力形式**: 任意の文字列
- **表示形式**: パスワードボックス（●●●表示）
- **IME制御**: OFF（半角英数モード）
- **プレースホルダー**: 「パスワードを入力」
- **バリデーション**:
  - 必須チェック
  - 最大50桁

### 3.3 ボタン

| No | ボタン名 | ボタンID | 種類 | 処理内容 |
|----|---------|---------|------|---------|
| 1 | ログイン | btn-login | submit | 認証処理を実行 |

---

## 4. 処理フロー

### 4.1 画面表示時

```
START
  │
  ↓
┌─────────────────────┐
│セッション確認       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  認証済み   未認証
    │         │
    ↓         ↓
  メイン画面  ログイン画面
  へリダイレクト  表示
    │         │
   END       END
```

### 4.2 ログインボタン押下時

```
START
  │
  ↓
┌─────────────────────┐
│クライアント側       │
│バリデーション       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  POST送信   エラー表示
    │        （処理終了）
    ↓
┌─────────────────────┐
│サーバー側           │
│バリデーション       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
┌─────────────────────┐  エラー表示
│DB認証処理            │  （処理終了）
│(SQL_作業担当参照)     │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  認証成功   認証失敗
    │         │
    ↓         ↓
┌─────────────────────┐  エラー表示
│セッション開始        │  入力欄クリア
│・担当者コード格納    │  （処理終了）
│・担当者名格納        │
│・部門コード格納      │
│・権限レベル格納      │
│・ログイン日時格納    │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│メイン画面へリダイレクト│
└─────────────────────┘
         │
        END
```

---

## 5. バリデーション

### 5.1 クライアント側バリデーション（JavaScript）

| No | 項目 | チェック内容 | エラーメッセージ |
|----|------|-------------|-----------------|
| 1 | ユーザーコード | 必須チェック | ユーザーコードを入力してください。 |
| 2 | ユーザーコード | 数値形式チェック | ユーザーコードは数字で入力してください。 |
| 3 | パスワード | 必須チェック | パスワードを入力してください。 |

### 5.2 サーバー側バリデーション（PHP）

| No | 項目 | チェック内容 | エラーメッセージ |
|----|------|-------------|-----------------|
| 1 | ユーザーコード | 必須チェック | ユーザーコードを入力してください。 |
| 2 | ユーザーコード | 数値形式チェック | ユーザーコードは数字で入力してください。 |
| 3 | ユーザーコード | 最大桁数チェック | ユーザーコードは10桁以内で入力してください。 |
| 4 | パスワード | 必須チェック | パスワードを入力してください。 |
| 5 | パスワード | 最大桁数チェック | パスワードは50桁以内で入力してください。 |
| 6 | 認証 | DB認証チェック | ユーザーコードまたはパスワードが正しくありません。 |

---

## 6. データベース連携

### 6.1 使用テーブル

| テーブル名 | 用途 | 接続DB |
|-----------|------|--------|
| SQL_作業担当 | 担当者認証、担当者情報取得 | 外部DB |

### 6.2 認証クエリ

```sql
SELECT 
    担当者コード,
    氏名,
    部門コード,
    パスワード,
    権限レベル
FROM SQL_作業担当
WHERE 担当者コード = @user_code
  AND パスワード = @password
  AND 退職日 IS NULL
```

**備考**:
- 退職日がNULLの現役社員のみ認証可能
- パスワードは平文で比較（将来的にハッシュ化を検討）

### 6.3 セッション格納情報

| キー名 | 値 | 説明 |
|--------|-----|------|
| LOGIN | true | ログイン状態フラグ |
| USER_CODE | 担当者コード | ユーザー識別子 |
| USER_NAME | 氏名 | 表示用担当者名 |
| BUMON_CODE | 部門コード | 所属部門 |
| AUTH_LEVEL | 権限レベル | 1:一般、2:管理者 |
| LOGIN_TIME | ログイン日時 | 認証日時 |

---

## 7. エラーハンドリング

### 7.1 エラー表示位置

```
┌────────────────────────────┐
│   サポート報告書システム     │
│        ログイン             │
├────────────────────────────┤
│ ┌────────────────────────┐ │
│ │【エラー】ユーザーコード  │ │  ← エラーボックス
│ │またはパスワードが正しく  │ │
│ │ありません。              │ │
│ └────────────────────────┘ │
│                            │
│  ユーザーコード *           │
│  ┌────────────────────┐    │
│  │                    │    │
│  └────────────────────┘    │
│  ...                       │
└────────────────────────────┘
```

### 7.2 エラーボックススタイル

```css
.error-box {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
}
```

### 7.3 エラー時の動作

| エラー種類 | 動作 |
|-----------|------|
| バリデーションエラー | エラーメッセージ表示、入力値は保持 |
| 認証エラー | エラーメッセージ表示、パスワードのみクリア |
| DBエラー | エラーメッセージ表示、ログ記録 |
| セッションエラー | エラーメッセージ表示、再ログイン促す |

---

## 8. 画面遷移

### 8.1 遷移先

| 遷移条件 | 遷移先 | 遷移方法 |
|---------|--------|---------|
| ログイン成功 | メイン画面（support_main.php） | PHPリダイレクト |
| 既にログイン済み | メイン画面（support_main.php） | PHPリダイレクト |

### 8.2 遷移元

| 遷移元 | 遷移条件 |
|--------|---------|
| ログアウト処理 | セッション破棄後 |
| セッションタイムアウト | 9時間経過後 |
| 認証が必要な画面 | 未認証でアクセス時 |

---

## 9. セキュリティ

### 9.1 実装事項

| 項目 | 実装内容 |
|------|---------|
| XSS対策 | 出力時にhtmlspecialchars()でエスケープ |
| SQLインジェクション対策 | PDOプリペアドステートメント使用 |
| セッション固定攻撃対策 | ログイン成功時にsession_regenerate_id()実行 |
| ブルートフォース対策 | 将来対応（ログイン試行回数制限） |

### 9.2 パスワード取り扱い

- **現状**: 平文で保存・比較（既存システム踏襲）
- **将来対応**: password_hash() / password_verify()によるハッシュ化

---

## 10. JavaScript処理

### 10.1 イベント処理

```javascript
$(function() {
    // フォーム送信時のバリデーション
    $('#login-form').on('submit', function(e) {
        var userCode = $('#user-code').val().trim();
        var password = $('#password').val();
        var errors = [];
        
        // バリデーション
        if (userCode === '') {
            errors.push('ユーザーコードを入力してください。');
        } else if (!/^\d+$/.test(userCode)) {
            errors.push('ユーザーコードは数字で入力してください。');
        }
        
        if (password === '') {
            errors.push('パスワードを入力してください。');
        }
        
        // エラーがあれば送信中止
        if (errors.length > 0) {
            e.preventDefault();
            showErrors(errors);
            return false;
        }
        
        return true;
    });
    
    // Enterキーでログイン
    $('#password').on('keydown', function(e) {
        if (e.keyCode === 13) {
            $('#login-form').submit();
        }
    });
    
    // ユーザーコード入力欄にフォーカス
    $('#user-code').focus();
});

// エラー表示関数
function showErrors(errors) {
    var html = '<div class="error-box">';
    for (var i = 0; i < errors.length; i++) {
        html += '<p>【エラー】' + errors[i] + '</p>';
    }
    html += '</div>';
    
    $('.error-box').remove();
    $('.login-box').prepend(html);
}
```

### 10.2 IME制御

```javascript
// ユーザーコード入力欄のIMEをOFFに
$('#user-code').css('ime-mode', 'disabled');
$('#password').css('ime-mode', 'disabled');
```

---

## 11. CSS設計

### 11.1 主要クラス

| クラス名 | 用途 |
|---------|------|
| .login-container | ログイン画面全体のコンテナ |
| .login-box | ログインボックス |
| .login-header | ログインボックスのヘッダー |
| .login-body | ログインボックスの本体 |
| .form-group | フォームの各項目グループ |
| .input-text | テキスト入力欄 |
| .btn-login | ログインボタン |
| .error-box | エラーメッセージボックス |

### 11.2 PC用CSS（pc.css）

```css
/* ログイン画面 */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background-color: #f5f5f5;
}

.login-box {
    width: 400px;
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.login-header {
    background-color: #007bff;
    color: #ffffff;
    padding: 20px;
    text-align: center;
}

.login-header h1 {
    font-size: 18px;
    margin: 0 0 5px 0;
}

.login-header h2 {
    font-size: 24px;
    margin: 0;
}

.login-body {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-group label .required {
    color: #dc3545;
}

.input-text {
    width: 100%;
    height: 40px;
    padding: 0 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.input-text:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.btn-login {
    width: 100%;
    height: 44px;
    background-color: #007bff;
    border: none;
    border-radius: 4px;
    color: #ffffff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-login:hover {
    background-color: #0056b3;
}

.btn-login:active {
    background-color: #004085;
}

.login-footer {
    text-align: center;
    padding: 15px;
    color: #666;
    font-size: 12px;
}
```

---

## 12. HTML構造

```html
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>サポート報告書システム | ログイン</title>
    <link rel="stylesheet" href="css/pc.css" type="text/css" 
          media="screen and (min-width:1025px)" />
    <link rel="stylesheet" href="css/tablet.css" type="text/css" 
          media="screen and (min-width:768px) and (max-width:1024px)" />
    <link rel="stylesheet" href="css/sp.css" type="text/css" 
          media="screen and (max-width:767px)" />
    <script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="js/login.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>サポート報告書システム</h1>
                <h2>ログイン</h2>
            </div>
            <div class="login-body">
                <!-- エラーメッセージ表示エリア -->
                <?php if (count($err_msg) > 0): ?>
                <div class="error-box">
                    <?php foreach ($err_msg as $msg): ?>
                    <p>【エラー】<?php echo h($msg); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <form id="login-form" method="post" action="login.php">
                    <div class="form-group">
                        <label for="user-code">
                            ユーザーコード <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="user-code" 
                               name="user_code" 
                               class="input-text" 
                               maxlength="10"
                               placeholder="担当者コードを入力"
                               value="<?php echo h($user_code); ?>" />
                    </div>
                    <div class="form-group">
                        <label for="password">
                            パスワード <span class="required">*</span>
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="input-text" 
                               maxlength="50"
                               placeholder="パスワードを入力" />
                    </div>
                    <div class="form-group">
                        <button type="submit" id="btn-login" class="btn-login">
                            ログイン
                        </button>
                    </div>
                </form>
            </div>
            <div class="login-footer">
                © 2025 OIS
            </div>
        </div>
    </div>
</body>
</html>
```

---

## 13. 備考

### 13.1 既存システムとの違い

| 項目 | Accessシステム | WEBシステム |
|------|--------------|------------|
| 認証方法 | Accessのログインボックス | PHPセッション認証 |
| パスワード保存 | 平文 | 平文（将来ハッシュ化） |
| セッション管理 | Access内部 | PHPセッション（9時間） |
| 画面遷移 | Access内部 | HTTPリダイレクト |

### 13.2 将来の拡張

- パスワードのハッシュ化対応
- ログイン試行回数制限
- パスワード変更機能
- パスワードリマインダー機能
- 二要素認証（将来検討）

### 13.3 テスト観点

- 正常系: 正しいユーザーコード・パスワードでログイン
- 異常系: 空欄入力、数値以外入力、存在しないユーザー、パスワード不一致
- 境界値: 最大桁数入力
- セキュリティ: XSS攻撃文字列、SQLインジェクション攻撃文字列

---

**作成者**: Claude AI  
**レビュー**: Phase 03完了時  
**改訂履歴**:
- v1.0 (2025-11-25): 初版作成
