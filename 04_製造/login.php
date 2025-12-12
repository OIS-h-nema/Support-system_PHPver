<?php
/**
 * ファイル名: login.php
 * 機能概要: ログイン画面
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/validation.php");

// 既にログイン済みの場合はメイン画面へリダイレクト
if (isLoggedIn()) {
    header('Location: support_main.php');
    exit;
}

// 変数初期化
$user_code = '';
$err_msg = array();

// タイムアウトメッセージ
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $err_msg[] = 'セッションがタイムアウトしました。再度ログインしてください。';
}

// ログアウトメッセージ
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // ログアウト完了時は特にメッセージ不要
}

// POSTリクエスト処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 入力値取得
    $user_code = isset($_POST['user_code']) ? trim($_POST['user_code']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // バリデーション
    $validator = new Validator();
    
    // ユーザーコード
    $validator->required('user_code', $user_code, 'ユーザーコード');
    if (!empty($user_code)) {
        $validator->numeric('user_code', $user_code, 'ユーザーコード');
        $validator->maxLength('user_code', $user_code, 10, 'ユーザーコード');
    }
    
    // パスワード
    $validator->required('password', $password, 'パスワード');
    if (!empty($password)) {
        $validator->maxLength('password', $password, 50, 'パスワード');
    }
    
    // バリデーションエラーチェック
    if ($validator->hasErrors()) {
        $err_msg = $validator->getMessages();
    } else {
        // 認証処理
        if (authenticate($user_code, $password)) {
            // 認証成功 - メイン画面へリダイレクト
            header('Location: support_main.php');
            exit;
        } else {
            // 認証失敗
            $err_msg[] = 'ユーザーコードまたはパスワードが正しくありません。';
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo h(SYSTEM_NAME); ?> | ログイン</title>
    <link rel="stylesheet" href="css/common.css" type="text/css" />
    <style type="text/css">
    /* ログイン画面専用スタイル */
    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #1e3a5f 0%, #2c4a6b 100%);
        padding: 20px;
    }
    
    .login-box {
        width: 100%;
        max-width: 400px;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }
    
    .login-header {
        background-color: #1e3a5f;
        color: #ffffff;
        padding: 25px 20px;
        text-align: center;
    }
    
    .login-header h1 {
        font-size: 18px;
        margin: 0 0 8px 0;
        font-weight: 400;
    }
    
    .login-header h2 {
        font-size: 24px;
        margin: 0;
        font-weight: 500;
    }
    
    .login-body {
        padding: 30px;
    }
    
    .login-form .form-group {
        margin-bottom: 20px;
    }
    
    .login-form .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    
    .login-form .form-group label .required {
        color: #dc2626;
        margin-left: 3px;
    }
    
    .login-form .form-control {
        width: 100%;
        height: 44px;
        padding: 0 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .login-form .form-control:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    
    .login-form .form-control.input-error {
        border-color: #dc2626;
        background-color: #fef2f2;
    }
    
    .btn-login {
        width: 100%;
        height: 48px;
        background-color: #2563eb;
        border: none;
        border-radius: 4px;
        color: #ffffff;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .btn-login:hover {
        background-color: #1d4ed8;
    }
    
    .btn-login:active {
        background-color: #1e40af;
    }
    
    .login-footer {
        text-align: center;
        padding: 15px 20px 20px;
        color: #6b7280;
        font-size: 12px;
    }
    
    .login-error-box {
        background-color: #fef2f2;
        border: 1px solid #f87171;
        border-radius: 4px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    
    .login-error-box p {
        color: #dc2626;
        margin: 5px 0;
        font-size: 13px;
    }
    
    .login-error-box p:first-child {
        margin-top: 0;
    }
    
    .login-error-box p:last-child {
        margin-bottom: 0;
    }
    
    /* レスポンシブ対応 */
    @media screen and (max-width: 480px) {
        .login-container {
            padding: 15px;
        }
        
        .login-box {
            max-width: 100%;
        }
        
        .login-body {
            padding: 20px;
        }
        
        .login-form .form-control {
            font-size: 16px; /* iOS ズーム防止 */
        }
    }
    </style>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo h(SYSTEM_NAME); ?></h1>
                <h2>ログイン</h2>
            </div>
            <div class="login-body">
                <?php if (count($err_msg) > 0): ?>
                <div class="login-error-box">
                    <?php foreach ($err_msg as $msg): ?>
                    <p>【エラー】<?php echo h($msg); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <form id="login-form" class="login-form" method="post" action="login.php">
                    <div class="form-group">
                        <label for="user-code">
                            ユーザーコード<span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="user-code" 
                               name="user_code" 
                               class="form-control" 
                               maxlength="10"
                               placeholder="担当者コードを入力"
                               value="<?php echo h($user_code); ?>"
                               autocomplete="off"
                               style="ime-mode: disabled;" />
                    </div>
                    <div class="form-group">
                        <label for="password">
                            パスワード<span class="required">*</span>
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               maxlength="50"
                               placeholder="パスワードを入力"
                               autocomplete="off"
                               style="ime-mode: disabled;" />
                    </div>
                    <div class="form-group">
                        <button type="submit" id="btn-login" class="btn-login">
                            ログイン
                        </button>
                    </div>
                </form>
            </div>
            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> OIS &nbsp;|&nbsp; Version <?php echo h(SYSTEM_VERSION); ?>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    $(function() {
        // フォーム送信時のバリデーション
        $('#login-form').on('submit', function(e) {
            var userCode = $('#user-code').val().trim();
            var password = $('#password').val();
            var errors = [];
            
            // エラー表示クリア
            $('.login-error-box').remove();
            $('.form-control').removeClass('input-error');
            
            // ユーザーコードチェック
            if (userCode === '') {
                errors.push('ユーザーコードを入力してください。');
                $('#user-code').addClass('input-error');
            } else if (!/^\d+$/.test(userCode)) {
                errors.push('ユーザーコードは数字で入力してください。');
                $('#user-code').addClass('input-error');
            }
            
            // パスワードチェック
            if (password === '') {
                errors.push('パスワードを入力してください。');
                $('#password').addClass('input-error');
            }
            
            // エラーがあれば送信中止
            if (errors.length > 0) {
                e.preventDefault();
                
                var html = '<div class="login-error-box">';
                for (var i = 0; i < errors.length; i++) {
                    html += '<p>【エラー】' + errors[i] + '</p>';
                }
                html += '</div>';
                
                $('.login-body').prepend(html);
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
    </script>
</body>
</html>
