<?php
/**
 * ファイル名: config.php
 * 機能概要: システム設定ファイル
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

//---------------------------------------------------
// 環境設定
//---------------------------------------------------
// 開発環境: true / 本番環境: false
define('DEBUG_MODE', true);

//---------------------------------------------------
// エラー出力設定
//---------------------------------------------------
if (DEBUG_MODE) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 'On');
} else {
    error_reporting(0);
    ini_set('display_errors', 'Off');
}
ini_set('log_errors', 'On');
ini_set('error_log', dirname(__FILE__) . '/../logs/php_error.log');

//---------------------------------------------------
// タイムゾーン・文字コード設定
//---------------------------------------------------
date_default_timezone_set('Asia/Tokyo');
mb_internal_encoding('UTF-8');

//---------------------------------------------------
// セッション設定（session_start前に設定）
//---------------------------------------------------
// 9時間（32400秒）
define('SESSION_LIFETIME', 32400);

ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0);  // 社内HTTP通信のため
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// セッションCookie設定
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// セッション名設定
session_name('support_system');

// セッション開始
session_start();

//---------------------------------------------------
// HTTPヘッダー設定
//---------------------------------------------------
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: private, no-store, must-revalidate, max-age=' . SESSION_LIFETIME);
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

//---------------------------------------------------
// データベース設定
//---------------------------------------------------
// サーバー設定
define('DB_SERVER', 'dev-se02\\SQL22');
define('DB_NAME', 'SUPPORTDB');
define('DB_USER', 'sa');
define('DB_PASS', 'OIS8973113fmv');

// PDO DSN
define('DB_DSN', 'sqlsrv:server=' . DB_SERVER . ';Database=' . DB_NAME);

//---------------------------------------------------
// パス設定
//---------------------------------------------------
// 基準パス（04_製造ディレクトリ）
define('BASE_PATH', dirname(__FILE__) . '/../');

// 既存共通関数パス
define('FUNC_PATH', 'C:/inetpub/func/');

// ログディレクトリ
define('LOG_PATH', BASE_PATH . 'logs/');

//---------------------------------------------------
// アプリケーション設定
//---------------------------------------------------
// システム名
define('SYSTEM_NAME', 'サポート報告書システム');

// バージョン
define('SYSTEM_VERSION', '1.0.0');

// ページング設定
define('DEFAULT_PAGE_SIZE', 50);
define('PAGE_SIZE_OPTIONS', [25, 50, 100]);

// アップロード設定（将来用）
define('MAX_FILE_SIZE', 10 * 1024 * 1024);  // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'gif']);

//---------------------------------------------------
// 権限レベル定数
//---------------------------------------------------
define('AUTH_LEVEL_USER', 1);    // 一般ユーザー
define('AUTH_LEVEL_ADMIN', 2);   // 管理者

//---------------------------------------------------
// エラーコード定数
//---------------------------------------------------
// 認証エラー
define('ERR_AUTH_SESSION', 'AUTH_001');
define('ERR_AUTH_LOGIN', 'AUTH_002');
define('ERR_AUTH_PERMISSION', 'AUTH_003');
define('ERR_AUTH_LOCKED', 'AUTH_004');

// バリデーションエラー
define('ERR_VALID_REQUIRED', 'VALID_001');
define('ERR_VALID_FORMAT', 'VALID_002');
define('ERR_VALID_LENGTH', 'VALID_003');
define('ERR_VALID_RANGE', 'VALID_004');
define('ERR_VALID_NUMERIC', 'VALID_005');
define('ERR_VALID_DATE', 'VALID_006');
define('ERR_VALID_DATE_RANGE', 'VALID_007');
define('ERR_VALID_INVALID', 'VALID_008');

// データベースエラー
define('ERR_DB_CONNECT', 'DB_001');
define('ERR_DB_SELECT', 'DB_002');
define('ERR_DB_INSERT', 'DB_003');
define('ERR_DB_DELETE', 'DB_004');
define('ERR_DB_NOT_FOUND', 'DB_005');
define('ERR_DB_CONFLICT', 'DB_006');
define('ERR_DB_REFERENCE', 'DB_007');
define('ERR_DB_TRANSACTION', 'DB_008');

// ビジネスロジックエラー
define('ERR_BIZ_INVALID_OP', 'BIZ_001');
define('ERR_BIZ_DUPLICATE', 'BIZ_002');
define('ERR_BIZ_DATE_ORDER', 'BIZ_003');
define('ERR_BIZ_NO_CONTENT', 'BIZ_004');

// システムエラー
define('ERR_SYS_INVALID_REQUEST', 'SYS_001');
define('ERR_SYS_FILE_UPLOAD', 'SYS_002');
define('ERR_SYS_GENERAL', 'SYS_003');
define('ERR_SYS_TIMEOUT', 'SYS_004');

//---------------------------------------------------
// データベース接続（PDO）
//---------------------------------------------------
$pdo_conn = null;

try {
    $pdo_conn = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die('データベース接続エラー: ' . $e->getMessage());
    } else {
        error_log('DB Connection Error: ' . $e->getMessage());
        die('システムエラーが発生しました。管理者にお問い合わせください。');
    }
}

//---------------------------------------------------
// 既存共通関数の読み込み（オプション）
//---------------------------------------------------
// 参考システムの関数を使用する場合はコメント解除
// if (file_exists(FUNC_PATH . 'myFunction.php')) {
//     require_once(FUNC_PATH . 'myFunction.php');
// }
// if (file_exists(FUNC_PATH . 'myConvert.php')) {
//     require_once(FUNC_PATH . 'myConvert.php');
// }
