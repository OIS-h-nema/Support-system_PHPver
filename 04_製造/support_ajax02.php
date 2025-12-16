<?php
/**
 * ファイル名: support_ajax02.php
 * 機能概要: サポート報告書入力ダイアログAjax処理（CRUD）
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 07）
 * 2025-12-08 顧客検索でSEQNOを返すように修正（D_作業報告.顧客コードとの結合用）
 * 2025-12-16 INSERT時のSET NOCOUNT ON追加、closeCursor()追加（PDOエラー対策）
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");
require_once("includes/validation.php");

// Content-Typeを先に設定
header('Content-Type: application/json; charset=utf-8');

// Ajax用セッションチェック
checkSessionForAjax();

// アクション取得
$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'insert':
        doInsert();
        break;
        
    case 'update':
        doUpdate();
        break;
        
    case 'delete':
        doDelete();
        break;
        
    case 'search_customer':
        searchCustomer();
        break;
        
    case 'search_customer_list':
        searchCustomerList();
        break;
        
    case 'get_templates':
        getTemplates();
        break;
        
    default:
        jsonError('不正なリクエストです。', ERR_SYS_INVALID_REQUEST);
        break;
}

/**
 * 新規登録処理
 */
function doInsert() {
    global $pdo_conn;
    
    // バリデーション
    $errors = validateInput($_POST, 'insert');
    if (count($errors) > 0) {
        jsonError('入力内容に誤りがあります。', ERR_VALID_REQUIRED, $errors);
        return;
    }
    
    try {
        $pdo_conn->beginTransaction();

        // SEQNO採番（SET NOCOUNT ONで余分な結果セットを抑制）
        $seqno = null;
        try {
            $stmt = $pdo_conn->query("SET NOCOUNT ON; EXEC COUNTUP_SYS_SEQNO");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $seqno = isset($result['SEQNO']) ? $result['SEQNO'] : null;
            $stmt->closeCursor();
        } catch (PDOException $e) {
            // ストアドプロシージャが失敗した場合はログ記録
            error_log('COUNTUP_SYS_SEQNO error: ' . $e->getMessage());
        }

        if (!$seqno) {
            // SEQNOが取得できない場合はMAX+1で対応
            $stmt = $pdo_conn->query("SELECT ISNULL(MAX(SEQNO), 0) + 1 AS SEQNO FROM D_作業報告");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $seqno = $result['SEQNO'];
            $stmt->closeCursor();
        }
        
        // 対応日時を結合
        $taiou_start = null;
        $taiou_end = null;
        
        if (!empty($_POST['taiou_date']) && !empty($_POST['taiou_time_start'])) {
            $taiou_start = $_POST['taiou_date'] . ' ' . $_POST['taiou_time_start'] . ':00';
        }
        if (!empty($_POST['taiou_date']) && !empty($_POST['taiou_time_end'])) {
            $taiou_end = $_POST['taiou_date'] . ' ' . $_POST['taiou_time_end'] . ':00';
        }
        
        // 対応内容フラグを展開
        $flags = prepareFlags($_POST);

        // 部門コードとマシン情報（WEB固定）
        $bumon_code = getCurrentBumonCode();
        $machine_name = 'WEB';
        $network_flag = 1;  // WEB版は常にオンライン
        
        // INSERT文（SET NOCOUNT ONで余分な結果セットを抑制）
        $sql = "SET NOCOUNT ON; INSERT INTO D_作業報告 (
                    SEQNO,
                    対応開始日時,
                    対応終了日時,
                    顧客コード,
                    顧客担当者名,
                    部門コード,
                    商品コード1,
                    商品コード2,
                    商品コード3,
                    作業担当コード,
                    対応区分コード,
                    引継担当コード,
                    報告内容,
                    対応内容フラグ1,
                    対応内容フラグ2,
                    対応内容フラグ3,
                    対応内容フラグ4,
                    対応内容フラグ5,
                    対応内容フラグ6,
                    対応内容フラグ7,
                    対応内容フラグ8,
                    対応内容フラグ9,
                    対応内容フラグ10,
                    対応内容フラグ11,
                    対応内容フラグ12,
                    対応内容フラグ13,
                    対応内容フラグ14,
                    対応内容フラグ15,
                    対応内容フラグ16,
                    対応内容フラグ17,
                    対応内容フラグ18,
                    対応内容フラグ19,
                    対応内容フラグ20,
                    作成日時,
                    更新日時,
                    更新マシン,
                    入力マシン,
                    ネットワークFLG
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, GETDATE(), GETDATE(), ?, ?, ?
                )";

        $params = array(
            $seqno,
            $taiou_start,
            $taiou_end,
            (int)$_POST['kokyaku_code'],
            trim($_POST['kokyaku_tanto']),
            $bumon_code,
            Ez($_POST['shohin_code1']),
            Ez($_POST['shohin_code2']),
            Ez($_POST['shohin_code3']),
            (int)$_POST['tantou_code'],
            Ez($_POST['kubun_code']),
            Ez($_POST['hikitsugi_code']),
            trim($_POST['houkoku_naiyo'])
        );
        
        // フラグを追加
        foreach ($flags as $flag) {
            $params[] = $flag;
        }

        // 更新マシン/入力マシン/ネットワークFLG
        $params[] = $machine_name;
        $params[] = $machine_name;
        $params[] = $network_flag;
        
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();  // 未処理の結果セットをクリア

        $pdo_conn->commit();
        
        jsonSuccess(array('seqno' => $seqno), '登録しました。');
        
    } catch (PDOException $e) {
        if ($pdo_conn->inTransaction()) {
            $pdo_conn->rollBack();
        }
        error_log('Insert error: ' . $e->getMessage());
        error_log('Insert SQLSTATE: ' . $e->getCode());
        error_log('Insert params: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        jsonError('登録中にエラーが発生しました。', ERR_DB_INSERT);
    }
}

/**
 * 更新処理
 */
function doUpdate() {
    global $pdo_conn;
    
    // SEQNO必須チェック
    $seqno = isset($_POST['seqno']) ? (int)$_POST['seqno'] : 0;
    if ($seqno <= 0) {
        jsonError('データIDが指定されていません。', ERR_VALID_REQUIRED);
        return;
    }
    
    // 存在チェック
    try {
        $stmt = $pdo_conn->prepare("SELECT SEQNO, 作業担当コード FROM D_作業報告 WHERE SEQNO = ? AND 削除日時 IS NULL");
        $stmt->execute(array($seqno));
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            jsonError('データが見つかりません。', ERR_DB_NOT_FOUND);
            return;
        }
        
        // 編集権限チェック
        if (!canEdit($existing['作業担当コード'])) {
            jsonError('このデータを編集する権限がありません。', ERR_AUTH_PERMISSION);
            return;
        }
        
    } catch (PDOException $e) {
        error_log('Check existence error: ' . $e->getMessage());
        jsonError('データの確認中にエラーが発生しました。', ERR_DB_SELECT);
        return;
    }
    
    // バリデーション
    $errors = validateInput($_POST, 'update');
    if (count($errors) > 0) {
        jsonError('入力内容に誤りがあります。', ERR_VALID_REQUIRED, $errors);
        return;
    }
    
    try {
        // 対応日時を結合
        $taiou_start = null;
        $taiou_end = null;
        
        if (!empty($_POST['taiou_date']) && !empty($_POST['taiou_time_start'])) {
            $taiou_start = $_POST['taiou_date'] . ' ' . $_POST['taiou_time_start'] . ':00';
        }
        if (!empty($_POST['taiou_date']) && !empty($_POST['taiou_time_end'])) {
            $taiou_end = $_POST['taiou_date'] . ' ' . $_POST['taiou_time_end'] . ':00';
        }
        
        // 対応内容フラグを展開
        $flags = prepareFlags($_POST);

        // 部門コードとマシン情報（WEB固定）
        $bumon_code = getCurrentBumonCode();
        $machine_name = 'WEB';
        $network_flag = 1;

        // UPDATE文（SET NOCOUNT ONで余分な結果セットを抑制）
        $sql = "SET NOCOUNT ON; UPDATE D_作業報告 SET
                    対応開始日時 = ?,
                    対応終了日時 = ?,
                    顧客コード = ?,
                    顧客担当者名 = ?,
                    部門コード = ?,
                    商品コード1 = ?,
                    商品コード2 = ?,
                    商品コード3 = ?,
                    作業担当コード = ?,
                    対応区分コード = ?,
                    引継担当コード = ?,
                    報告内容 = ?,
                    対応内容フラグ1 = ?,
                    対応内容フラグ2 = ?,
                    対応内容フラグ3 = ?,
                    対応内容フラグ4 = ?,
                    対応内容フラグ5 = ?,
                    対応内容フラグ6 = ?,
                    対応内容フラグ7 = ?,
                    対応内容フラグ8 = ?,
                    対応内容フラグ9 = ?,
                    対応内容フラグ10 = ?,
                    対応内容フラグ11 = ?,
                    対応内容フラグ12 = ?,
                    対応内容フラグ13 = ?,
                    対応内容フラグ14 = ?,
                    対応内容フラグ15 = ?,
                    対応内容フラグ16 = ?,
                    対応内容フラグ17 = ?,
                    対応内容フラグ18 = ?,
                    対応内容フラグ19 = ?,
                    対応内容フラグ20 = ?,
                    更新日時 = GETDATE(),
                    更新マシン = ?,
                    入力マシン = ?,
                    ネットワークFLG = ?
                WHERE SEQNO = ? AND 削除日時 IS NULL";

        $params = array(
            $taiou_start,
            $taiou_end,
            (int)$_POST['kokyaku_code'],
            trim($_POST['kokyaku_tanto']),
            $bumon_code,
            Ez($_POST['shohin_code1']),
            Ez($_POST['shohin_code2']),
            Ez($_POST['shohin_code3']),
            (int)$_POST['tantou_code'],
            Ez($_POST['kubun_code']),
            Ez($_POST['hikitsugi_code']),
            trim($_POST['houkoku_naiyo'])
        );
        
        // フラグを追加
        foreach ($flags as $flag) {
            $params[] = $flag;
        }

        // マシン情報とSEQNO
        $params[] = $machine_name;
        $params[] = $machine_name;
        $params[] = $network_flag;
        $params[] = $seqno;
        
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute($params);
        $stmt->closeCursor();  // 未処理の結果セットをクリア
        
        jsonSuccess(array('seqno' => $seqno), '更新しました。');
        
    } catch (PDOException $e) {
        error_log('Update error: ' . $e->getMessage());
        jsonError('更新中にエラーが発生しました。', ERR_DB_INSERT);
    }
}

/**
 * 削除処理（論理削除）
 */
function doDelete() {
    global $pdo_conn;
    
    // 管理者権限チェック
    if (!canDelete()) {
        jsonError('削除する権限がありません。', ERR_AUTH_PERMISSION);
        return;
    }
    
    // SEQNO必須チェック
    $seqno = isset($_POST['seqno']) ? (int)$_POST['seqno'] : 0;
    if ($seqno <= 0) {
        jsonError('データIDが指定されていません。', ERR_VALID_REQUIRED);
        return;
    }
    
    try {
        // 存在チェック
        $stmt = $pdo_conn->prepare("SELECT SEQNO FROM D_作業報告 WHERE SEQNO = ? AND 削除日時 IS NULL");
        $stmt->execute(array($seqno));

        if (!$stmt->fetch()) {
            $stmt->closeCursor();
            jsonError('データが見つかりません。', ERR_DB_NOT_FOUND);
            return;
        }

        $stmt->closeCursor();

        // 論理削除（削除日時をセット）
        $sql = "SET NOCOUNT ON; UPDATE D_作業報告 SET 削除日時 = GETDATE() WHERE SEQNO = ?";
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute(array($seqno));
        $stmt->closeCursor();
        
        jsonSuccess(null, '削除しました。');
        
    } catch (PDOException $e) {
        error_log('Delete error: ' . $e->getMessage());
        jsonError('削除中にエラーが発生しました。', ERR_DB_DELETE);
    }
}

/**
 * 顧客情報取得（単一）
 * 文字化け対策として英語エイリアスを使用
 * ※重要: D_作業報告.顧客コードにはSQL_顧客.SEQNOの値が格納される
 */
function searchCustomer() {
    global $pdo_conn;
    
    $code = isset($_POST['kokyaku_code']) ? (int)$_POST['kokyaku_code'] : 0;
    
    if ($code <= 0) {
        jsonError('顧客コードを入力してください。', ERR_VALID_REQUIRED);
        return;
    }
    
    try {
        // SEQNOで検索（D_作業報告.顧客コードにはSEQNOが格納される）
        $stmt = $pdo_conn->prepare("SELECT SEQNO AS kokyaku_code, 顧客名 AS kokyaku_name FROM SQL_顧客 WHERE SEQNO = ?");
        $stmt->execute(array($code));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            jsonSuccess(array(
                'kokyaku_code' => $row['kokyaku_code'],  // SEQNOを返す
                'kokyaku_name' => $row['kokyaku_name']
            ));
        } else {
            jsonError('顧客が見つかりません。', ERR_DB_NOT_FOUND);
        }
        
    } catch (PDOException $e) {
        error_log('Search customer error: ' . $e->getMessage());
        jsonError('顧客検索中にエラーが発生しました。', ERR_DB_SELECT);
    }
}

/**
 * 顧客リスト検索
 * 文字化け対策として英語エイリアスを使用
 * ※重要: D_作業報告.顧客コードにはSQL_顧客.SEQNOの値が格納される
 */
function searchCustomerList() {
    global $pdo_conn;
    
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    
    if ($keyword === '') {
        jsonSuccess(array());
        return;
    }
    
    try {
        // 顧客名の部分一致検索（最大50件）
        // SEQNOをkokyaku_codeとして返す（D_作業報告.顧客コードに格納する値）
        $stmt = $pdo_conn->prepare("
            SELECT TOP 50 SEQNO AS kokyaku_code, 顧客名 AS kokyaku_name 
            FROM SQL_顧客 
            WHERE 顧客名 LIKE ?
            ORDER BY SEQNO
        ");
        $stmt->execute(array('%' . $keyword . '%'));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                'kokyaku_code' => $row['kokyaku_code'],  // SEQNOを返す
                'kokyaku_name' => $row['kokyaku_name']
            );
        }
        
        jsonSuccess($result);
        
    } catch (PDOException $e) {
        error_log('Search customer list error: ' . $e->getMessage());
        jsonError('顧客検索中にエラーが発生しました。', ERR_DB_SELECT);
    }
}

/**
 * 定型文リスト取得（エイリアス対応で文字化け対策）
 */
function getTemplates() {
    global $pdo_conn;
    
    try {
        // ログインユーザーの部門コードを取得
        $bumon_code = getCurrentBumonCode();
        
        // エイリアス使用で文字化け対策
        $sql = "SELECT 
                    部門コード AS bumon_code,
                    定型文コード AS teikei_code, 
                    定型文 AS teikei_text
                FROM M_定型文
                WHERE 削除日時 IS NULL";
        
        // 部門コードが取得できた場合はその部門の定型文のみ取得
        $params = array();
        if ($bumon_code) {
            $sql .= " AND 部門コード = ?";
            $params[] = $bumon_code;
        }
        
        $sql .= " ORDER BY 部門コード, 定型文コード";
        
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = array(
                'bumon_code' => $row['bumon_code'],
                'teikei_code' => $row['teikei_code'],
                'teikei_text' => $row['teikei_text']
            );
        }
        
        jsonSuccess($result);
        
    } catch (PDOException $e) {
        error_log('Get templates error: ' . $e->getMessage());
        jsonError('定型文の取得中にエラーが発生しました。', ERR_DB_SELECT);
    }
}

/**
 * 入力バリデーション
 * 
 * @param array $data 入力データ
 * @param string $mode 'insert' or 'update'
 * @return array エラーメッセージ配列
 */
function validateInput($data, $mode = 'insert') {
    $errors = array();
    
    // 顧客コード（※実際にはSQL_顧客.SEQNOの値）
    if (empty($data['kokyaku_code'])) {
        $errors[] = '顧客コードを入力してください。';
    } else if (!is_numeric($data['kokyaku_code'])) {
        $errors[] = '顧客コードは数値で入力してください。';
    } else {
        // 存在チェック（SEQNOで検索）
        global $pdo_conn;
        try {
            $stmt = $pdo_conn->prepare("SELECT SEQNO AS kokyaku_code FROM SQL_顧客 WHERE SEQNO = ?");
            $stmt->execute(array((int)$data['kokyaku_code']));
            if (!$stmt->fetch()) {
                $errors[] = '指定された顧客コードは存在しません。';
            }
        } catch (PDOException $e) {
            error_log('Validate customer error: ' . $e->getMessage());
        }
    }
    
    // 対応日
    if (empty($data['taiou_date'])) {
        $errors[] = '対応日を入力してください。';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $data['taiou_date']);
        if (!$date || $date->format('Y-m-d') !== $data['taiou_date']) {
            $errors[] = '対応日の形式が正しくありません。';
        }
    }
    
    // 開始時刻
    if (empty($data['taiou_time_start'])) {
        $errors[] = '開始時刻を入力してください。';
    }
    
    // 終了時刻が開始時刻より前
    if (!empty($data['taiou_time_start']) && !empty($data['taiou_time_end'])) {
        if ($data['taiou_time_start'] > $data['taiou_time_end']) {
            $errors[] = '終了時刻は開始時刻以降を指定してください。';
        }
    }
    
    // 作業担当
    if (empty($data['tantou_code'])) {
        $errors[] = '作業担当を選択してください。';
    }
    
    // 報告内容
    if (empty(trim($data['houkoku_naiyo']))) {
        $errors[] = '報告内容を入力してください。';
    }
    
    // 商品重複チェック
    $products = array_filter(array(
        Ez($data['shohin_code1'], null),
        Ez($data['shohin_code2'], null),
        Ez($data['shohin_code3'], null)
    ));
    if (count($products) !== count(array_unique($products))) {
        $errors[] = '同じ商品が複数選択されています。';
    }
    
    return $errors;
}

/**
 * 対応内容フラグを配列に展開
 * 
 * @param array $data POSTデータ
 * @return array フラグ配列（20要素）
 */
function prepareFlags($data) {
    $flags = array_fill(0, 20, 0);
    
    if (isset($data['taiou_flag']) && is_array($data['taiou_flag'])) {
        foreach ($data['taiou_flag'] as $code) {
            $code = (int)$code;
            if ($code >= 1 && $code <= 20) {
                $flags[$code - 1] = 1;
            }
        }
    }
    
    return $flags;
}
