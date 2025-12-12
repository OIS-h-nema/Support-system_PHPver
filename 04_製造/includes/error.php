<?php
/**
 * ファイル名: error.php
 * 機能概要: エラーハンドリングモジュール
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

//---------------------------------------------------
// エラーメッセージ定義
//---------------------------------------------------
$ERROR_MESSAGES = array(
    // 認証エラー
    'AUTH_001' => 'セッションが切れました。再度ログインしてください。',
    'AUTH_002' => 'ログインに失敗しました。ID・パスワードを確認してください。',
    'AUTH_003' => 'このページへのアクセス権限がありません。',
    'AUTH_004' => 'アカウントがロックされています。管理者に連絡してください。',
    
    // バリデーションエラー
    'VALID_001' => '{field}は必須項目です。',
    'VALID_002' => '{field}の形式が正しくありません。',
    'VALID_003' => '{field}は{max}文字以内で入力してください。',
    'VALID_004' => '{field}は{min}〜{max}の範囲で入力してください。',
    'VALID_005' => '{field}は数値で入力してください。',
    'VALID_006' => '日付の形式が正しくありません。（YYYY-MM-DD）',
    'VALID_007' => '開始日は終了日より前の日付を指定してください。',
    'VALID_008' => '選択された{field}は無効です。',
    
    // データベースエラー
    'DB_001' => 'データベース接続に失敗しました。',
    'DB_002' => 'データの取得に失敗しました。',
    'DB_003' => 'データの保存に失敗しました。',
    'DB_004' => 'データの削除に失敗しました。',
    'DB_005' => '指定されたデータが見つかりません。',
    'DB_006' => 'データが他のユーザーにより更新されています。',
    'DB_007' => 'このデータは使用中のため削除できません。',
    'DB_008' => 'トランザクションエラーが発生しました。',
    
    // ビジネスロジックエラー
    'BIZ_001' => 'この操作は実行できません。',
    'BIZ_002' => '既に登録されているコードです。',
    'BIZ_003' => '対応終了日時は開始日時より後を指定してください。',
    'BIZ_004' => '報告内容を入力してください。',
    
    // システムエラー
    'SYS_001' => '不正なリクエストです。',
    'SYS_002' => 'ファイルのアップロードに失敗しました。',
    'SYS_003' => 'システムエラーが発生しました。管理者に連絡してください。',
    'SYS_004' => '処理がタイムアウトしました。'
);

//---------------------------------------------------
// エラーメッセージ取得関数
//---------------------------------------------------

/**
 * エラーコードからメッセージを取得
 * 
 * @param string $code エラーコード
 * @param array $params 置換パラメータ
 * @return string エラーメッセージ
 */
function getErrorMessage($code, $params = array()) {
    global $ERROR_MESSAGES;
    
    if (!isset($ERROR_MESSAGES[$code])) {
        return 'エラーが発生しました。';
    }
    
    $message = $ERROR_MESSAGES[$code];
    
    // プレースホルダー置換
    foreach ($params as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    return $message;
}

//---------------------------------------------------
// アプリケーションエラークラス
//---------------------------------------------------

/**
 * アプリケーションエラークラス
 */
class AppError extends Exception {
    private $error_code;
    private $severity;
    private $details;
    
    /**
     * コンストラクタ
     * 
     * @param string $code エラーコード
     * @param string $message エラーメッセージ
     * @param int $severity 重大度（1-4）
     * @param array $details 詳細情報
     */
    public function __construct($code, $message = '', $severity = 3, $details = array()) {
        if (empty($message)) {
            $message = getErrorMessage($code);
        }
        parent::__construct($message);
        $this->error_code = $code;
        $this->severity = $severity;
        $this->details = $details;
    }
    
    public function getErrorCode() { return $this->error_code; }
    public function getSeverity() { return $this->severity; }
    public function getDetails() { return $this->details; }
}

/**
 * バリデーションエラークラス
 */
class ValidationError extends AppError {
    private $errors = array();
    
    public function __construct($errors = array()) {
        parent::__construct('VALID_000', 'バリデーションエラーが発生しました。', 2);
        $this->errors = $errors;
    }
    
    public function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    public function getErrors() { return $this->errors; }
    public function hasErrors() { return count($this->errors) > 0; }
}

//---------------------------------------------------
// グローバルエラーハンドラー
//---------------------------------------------------

/**
 * 例外ハンドラー
 */
function globalExceptionHandler($exception) {
    // ログ記録
    $log_message = sprintf(
        "[%s] EXCEPTION: %s in %s:%d\nStack trace:\n%s",
        date('Y-m-d H:i:s'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );
    
    $log_file = defined('LOG_PATH') ? LOG_PATH . 'exception.log' : dirname(__FILE__) . '/../logs/exception.log';
    error_log($log_message . "\n", 3, $log_file);
    
    // Ajax通信の場合
    if (isAjaxRequest()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(array(
            'status' => 'error',
            'message' => 'システムエラーが発生しました。'
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 通常リクエストの場合
    http_response_code(500);
    
    // デバッグモードの場合は詳細を表示
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<h1>例外が発生しました</h1>';
        echo '<p><strong>メッセージ:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p><strong>ファイル:</strong> ' . htmlspecialchars($exception->getFile()) . ':' . $exception->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
    } else {
        include(dirname(__FILE__) . '/../error_page.php');
    }
    exit;
}

/**
 * エラーハンドラー
 */
function globalErrorHandler($errno, $errstr, $errfile, $errline) {
    // E_NOTICEは無視
    if ($errno === E_NOTICE) {
        return false;
    }
    
    // ログ記録
    $log_message = sprintf(
        "[%s] PHP ERROR (%d): %s in %s:%d",
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    
    $log_file = defined('LOG_PATH') ? LOG_PATH . 'php_error.log' : dirname(__FILE__) . '/../logs/php_error.log';
    error_log($log_message . "\n", 3, $log_file);
    
    // E_WARNING以上はExceptionとして再throw
    if ($errno === E_WARNING || $errno === E_USER_ERROR) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    return true;
}

// ハンドラー登録
set_exception_handler('globalExceptionHandler');
set_error_handler('globalErrorHandler');

//---------------------------------------------------
// ログ関数
//---------------------------------------------------

/**
 * アプリケーションログ出力
 * 
 * @param string $level ログレベル（info/warning/error）
 * @param string $message メッセージ
 * @param array $context コンテキスト情報
 */
function logApp($level, $message, $context = array()) {
    $user_id = isset($_SESSION['USER_ID']) ? $_SESSION['USER_ID'] : 'guest';
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    
    $log_message = sprintf(
        "[%s] %s: %s - User: %s - IP: %s",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $user_id,
        $ip
    );
    
    if (!empty($context)) {
        $log_message .= " - Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    
    $log_file = defined('LOG_PATH') ? LOG_PATH . 'app.log' : dirname(__FILE__) . '/../logs/app.log';
    error_log($log_message . "\n", 3, $log_file);
}

/**
 * 情報ログ
 * 
 * @param string $message メッセージ
 * @param array $context コンテキスト情報
 */
function logInfo($message, $context = array()) {
    logApp('info', $message, $context);
}

/**
 * 警告ログ
 * 
 * @param string $message メッセージ
 * @param array $context コンテキスト情報
 */
function logWarning($message, $context = array()) {
    logApp('warning', $message, $context);
}

/**
 * エラーログ
 * 
 * @param string $message メッセージ
 * @param array $context コンテキスト情報
 */
function logError($message, $context = array()) {
    logApp('error', $message, $context);
}

//---------------------------------------------------
// エラー表示用関数
//---------------------------------------------------

/**
 * エラーボックスHTMLを生成
 * 
 * @param array $errors エラーメッセージ配列
 * @return string HTML
 */
function renderErrorBox($errors) {
    if (empty($errors)) {
        return '';
    }
    
    $html = '<div class="error-box">';
    foreach ($errors as $error) {
        $html .= '<p class="error-message"><span class="icon-error">！</span>' . 
                 htmlspecialchars($error) . '</p>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * 成功メッセージHTMLを生成
 * 
 * @param string $message メッセージ
 * @return string HTML
 */
function renderSuccessBox($message) {
    if (empty($message)) {
        return '';
    }
    
    return '<div class="success-box"><p class="success-message">' . 
           htmlspecialchars($message) . '</p></div>';
}
