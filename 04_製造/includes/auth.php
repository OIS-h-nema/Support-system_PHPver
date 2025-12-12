<?php
/**
 * ファイル名: auth.php
 * 機能概要: 認証モジュール
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

//---------------------------------------------------
// 認証処理
//---------------------------------------------------

/**
 * ログイン認証処理
 * 
 * @param int $user_id 担当者コード
 * @param string $password パスワード
 * @return bool 認証結果
 */
function authenticate($user_id, $password) {
    global $pdo_conn;
    
    // 入力値のバリデーション
    if (empty($user_id) || !is_numeric($user_id)) {
        return false;
    }
    if (empty($password)) {
        return false;
    }
    
    try {
        // 認証クエリ（SQL_作業担当テーブルを使用）
        // 日本語カラム名はPDOで文字化けするためエイリアス使用
        // 注意: 権限レベルカラムは存在しないため、デフォルト値1を使用
        $sql = "SELECT 
                    担当者コード AS tantou_code, 
                    担当者名 AS tantou_name, 
                    部門コード AS bumon_code
                FROM SQL_作業担当
                WHERE 担当者コード = ? AND パスワード = ?";
        
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute(array((int)$user_id, $password));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // セッション固定攻撃対策
            session_regenerate_id(true);
            
            // セッション変数設定（エイリアス名でアクセス）
            $_SESSION['LOGIN'] = true;
            $_SESSION['USER_ID'] = (int)$user['tantou_code'];
            $_SESSION['USER_NAME'] = $user['tantou_name'];
            $_SESSION['BUMON_CODE'] = (int)$user['bumon_code'];
            $_SESSION['AUTH_LEVEL'] = AUTH_LEVEL_USER;  // デフォルトは一般ユーザー
            $_SESSION['LOGIN_TIME'] = date('Y-m-d H:i:s');
            
            // 部門名取得（エイリアス使用）
            $stmt2 = $pdo_conn->prepare("SELECT 部門名 AS bumon_name FROM SQL_部門 WHERE 部門コード = ?");
            $stmt2->execute(array((int)$user['bumon_code']));
            $bumon = $stmt2->fetch(PDO::FETCH_ASSOC);
            $_SESSION['BUMON_NAME'] = $bumon ? $bumon['bumon_name'] : '';
            
            // 検索条件初期化
            initializeSearchSession();
            
            // ログ記録
            logAuth('LOGIN', $user_id, true);
            
            return true;
        }
        
        // 認証失敗ログ
        logAuth('LOGIN', $user_id, false);
        
    } catch (PDOException $e) {
        error_log('Authentication error: ' . $e->getMessage());
    }
    
    return false;
}

/**
 * ログアウト処理
 */
function logout() {
    $user_id = isset($_SESSION['USER_ID']) ? $_SESSION['USER_ID'] : 0;
    
    // ログ記録
    logAuth('LOGOUT', $user_id, true);
    
    // セッション変数をクリア
    $_SESSION = array();
    
    // セッションCookieを削除
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    
    // セッションを破棄
    session_destroy();
}

/**
 * ログイン状態チェック
 * 
 * @return bool 認証済みかどうか
 */
function isLoggedIn() {
    return isset($_SESSION['LOGIN']) && $_SESSION['LOGIN'] === true;
}

/**
 * 管理者権限チェック
 * 
 * @return bool 管理者かどうか
 */
function isAdmin() {
    return isLoggedIn() && 
           isset($_SESSION['AUTH_LEVEL']) && 
           $_SESSION['AUTH_LEVEL'] >= AUTH_LEVEL_ADMIN;
}

/**
 * 編集権限チェック
 * 
 * @param int $owner_id データ所有者の担当者コード
 * @return bool 編集可能かどうか
 */
function canEdit($owner_id) {
    // 管理者は全て編集可能
    if (isAdmin()) {
        return true;
    }
    // 一般ユーザーは自分のデータのみ
    return isset($_SESSION['USER_ID']) && $_SESSION['USER_ID'] === (int)$owner_id;
}

/**
 * 削除権限チェック
 * 
 * @return bool 削除可能かどうか
 */
function canDelete() {
    return isAdmin();
}

/**
 * セッションチェック（ページ用）
 * 未認証の場合はログイン画面にリダイレクト
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?timeout=1');
        exit;
    }
}

/**
 * 管理者権限チェック（ページ用）
 * 管理者でない場合はメイン画面にリダイレクト
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: support_main.php?error=permission');
        exit;
    }
}

/**
 * Ajax用セッションチェック
 * 未認証の場合はJSONでエラーを返す
 */
function checkSessionForAjax() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'status' => 'error',
            'code' => ERR_AUTH_SESSION,
            'message' => 'セッションが切れました。再度ログインしてください。',
            'redirect' => 'login.php'
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Ajax用管理者権限チェック
 */
function checkAdminForAjax() {
    checkSessionForAjax();
    if (!isAdmin()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'status' => 'error',
            'code' => ERR_AUTH_PERMISSION,
            'message' => '権限がありません。'
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

//---------------------------------------------------
// セッション管理
//---------------------------------------------------

/**
 * 検索条件セッションの初期化
 */
function initializeSearchSession() {
    $_SESSION['SEARCH_PARAMS'] = array();
    $_SESSION['CURRENT_PAGE'] = 1;
    $_SESSION['SORT_COLUMN'] = '対応開始日時';
    $_SESSION['SORT_ORDER'] = 'DESC';
    $_SESSION['DISPLAY_COUNT'] = DEFAULT_PAGE_SIZE;
}

/**
 * 検索条件をセッションに保存
 * 
 * @param array $params 検索パラメータ
 */
function saveSearchParams($params) {
    $_SESSION['SEARCH_PARAMS'] = array(
        'taiou_date_from' => isset($params['taiou_date_from']) ? $params['taiou_date_from'] : '',
        'taiou_date_to' => isset($params['taiou_date_to']) ? $params['taiou_date_to'] : '',
        'tantou_code' => isset($params['tantou_code']) ? (int)$params['tantou_code'] : '',
        'kokyaku_name' => isset($params['kokyaku_name']) ? $params['kokyaku_name'] : '',
        'shohin_code' => isset($params['shohin_code']) ? (int)$params['shohin_code'] : '',
        'kubun_code' => isset($params['kubun_code']) ? (int)$params['kubun_code'] : '',
        'keyword' => isset($params['keyword']) ? $params['keyword'] : '',
        'taiou_flags' => isset($params['taiou_flags']) ? $params['taiou_flags'] : array()
    );
    // 検索時はページを1に戻す
    $_SESSION['CURRENT_PAGE'] = 1;
}

/**
 * 検索条件をセッションから取得
 * 
 * @return array 検索パラメータ
 */
function getSearchParams() {
    return isset($_SESSION['SEARCH_PARAMS']) ? $_SESSION['SEARCH_PARAMS'] : array();
}

/**
 * 検索条件をクリア
 */
function clearSearchParams() {
    $_SESSION['SEARCH_PARAMS'] = array();
    $_SESSION['CURRENT_PAGE'] = 1;
}

/**
 * 現在ページを保存
 * 
 * @param int $page ページ番号
 */
function setCurrentPage($page) {
    $_SESSION['CURRENT_PAGE'] = max(1, (int)$page);
}

/**
 * 現在ページを取得
 * 
 * @return int ページ番号
 */
function getCurrentPage() {
    return isset($_SESSION['CURRENT_PAGE']) ? (int)$_SESSION['CURRENT_PAGE'] : 1;
}

/**
 * ソート条件を保存
 * 
 * @param string $column カラム名
 * @param string $order 並び順（ASC/DESC）
 */
function setSortParams($column, $order) {
    $allowed_columns = array('対応開始日時', 'SEQNO', '顧客名', '作業担当名');
    $allowed_orders = array('ASC', 'DESC');
    
    if (in_array($column, $allowed_columns)) {
        $_SESSION['SORT_COLUMN'] = $column;
    }
    if (in_array(strtoupper($order), $allowed_orders)) {
        $_SESSION['SORT_ORDER'] = strtoupper($order);
    }
}

/**
 * ソート条件を取得
 * 
 * @return array ['column' => カラム名, 'order' => 並び順]
 */
function getSortParams() {
    return array(
        'column' => isset($_SESSION['SORT_COLUMN']) ? $_SESSION['SORT_COLUMN'] : '対応開始日時',
        'order' => isset($_SESSION['SORT_ORDER']) ? $_SESSION['SORT_ORDER'] : 'DESC'
    );
}

/**
 * 表示件数を保存
 * 
 * @param int $count 表示件数
 */
function setDisplayCount($count) {
    if (in_array((int)$count, PAGE_SIZE_OPTIONS)) {
        $_SESSION['DISPLAY_COUNT'] = (int)$count;
    }
}

/**
 * 表示件数を取得
 * 
 * @return int 表示件数
 */
function getDisplayCount() {
    return isset($_SESSION['DISPLAY_COUNT']) ? (int)$_SESSION['DISPLAY_COUNT'] : DEFAULT_PAGE_SIZE;
}

//---------------------------------------------------
// フラッシュメッセージ
//---------------------------------------------------

/**
 * 成功メッセージを設定
 * 
 * @param string $message メッセージ
 */
function setSuccessMessage($message) {
    $_SESSION['MESSAGE'] = array(
        'type' => 'success',
        'text' => $message
    );
}

/**
 * エラーメッセージを設定
 * 
 * @param string $message メッセージ
 */
function setErrorMessage($message) {
    $_SESSION['ERROR'] = array($message);
}

/**
 * エラーメッセージを追加
 * 
 * @param string $message メッセージ
 */
function addErrorMessage($message) {
    if (!isset($_SESSION['ERROR'])) {
        $_SESSION['ERROR'] = array();
    }
    $_SESSION['ERROR'][] = $message;
}

/**
 * メッセージを取得して削除（フラッシュ）
 * 
 * @return array|null メッセージ配列
 */
function getFlashMessage() {
    $message = isset($_SESSION['MESSAGE']) ? $_SESSION['MESSAGE'] : null;
    unset($_SESSION['MESSAGE']);
    return $message;
}

/**
 * エラーメッセージを取得して削除（フラッシュ）
 * 
 * @return array エラーメッセージ配列
 */
function getFlashErrors() {
    $errors = isset($_SESSION['ERROR']) ? $_SESSION['ERROR'] : array();
    unset($_SESSION['ERROR']);
    return $errors;
}

//---------------------------------------------------
// ログ関数
//---------------------------------------------------

/**
 * 認証ログを記録
 * 
 * @param string $action アクション（LOGIN/LOGOUT）
 * @param int $user_id ユーザーID
 * @param bool $result 結果
 */
function logAuth($action, $user_id, $result) {
    $log_message = sprintf(
        "[%s] %s - User: %d - Result: %s - IP: %s\n",
        date('Y-m-d H:i:s'),
        $action,
        $user_id,
        $result ? 'SUCCESS' : 'FAILED',
        $_SERVER['REMOTE_ADDR']
    );
    
    $log_file = defined('LOG_PATH') ? LOG_PATH . 'auth.log' : dirname(__FILE__) . '/../logs/auth.log';
    error_log($log_message, 3, $log_file);
}

//---------------------------------------------------
// ユーザー情報取得
//---------------------------------------------------

/**
 * 現在のユーザーID取得
 * 
 * @return int|null ユーザーID
 */
function getCurrentUserId() {
    return isset($_SESSION['USER_ID']) ? (int)$_SESSION['USER_ID'] : null;
}

/**
 * 現在のユーザー名取得
 * 
 * @return string|null ユーザー名
 */
function getCurrentUserName() {
    return isset($_SESSION['USER_NAME']) ? $_SESSION['USER_NAME'] : null;
}

/**
 * 現在の部門コード取得
 * 
 * @return int|null 部門コード
 */
function getCurrentBumonCode() {
    return isset($_SESSION['BUMON_CODE']) ? (int)$_SESSION['BUMON_CODE'] : null;
}

/**
 * 現在の部門名取得
 * 
 * @return string|null 部門名
 */
function getCurrentBumonName() {
    return isset($_SESSION['BUMON_NAME']) ? $_SESSION['BUMON_NAME'] : null;
}

/**
 * 現在の権限レベル取得
 * 
 * @return int|null 権限レベル
 */
function getCurrentAuthLevel() {
    return isset($_SESSION['AUTH_LEVEL']) ? (int)$_SESSION['AUTH_LEVEL'] : null;
}
