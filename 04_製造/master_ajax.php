<?php
/**
 * ファイル名: master_ajax.php
 * 機能概要: マスタ設定用Ajax処理
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 08）
 * 2025-12-10 エイリアス対応（文字化け対策）
 * 2025-12-11 権限チェック撤廃、使用区分バリデーション、DELDATA移動削除
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// Content-Type設定
header('Content-Type: application/json; charset=utf-8');

// ログインチェック
checkSessionForAjax();

// アクション取得
$action = isset($_POST['action']) ? $_POST['action'] : '';
$master_type = isset($_POST['master_type']) ? $_POST['master_type'] : '';

// マスタ種別のバリデーション
$allowed_types = array('product', 'template', 'category', 'content');
if (!in_array($master_type, $allowed_types) && $action !== '') {
    jsonError('不正なマスタ種別です。', 'MASTER_003');
    exit;
}

// 処理分岐
switch ($action) {
    case 'list':
        getMasterList($master_type);
        break;
        
    case 'get':
        getMasterData($master_type);
        break;
        
    case 'save':
        saveMaster($master_type);
        break;
        
    case 'delete':
        deleteMaster($master_type);
        break;
        
    default:
        jsonError('不正なリクエストです。', 'MASTER_002');
        break;
}

/**
 * マスタ一覧取得（エイリアス対応で文字化け対策）
 * 
 * @param string $master_type マスタ種別
 */
function getMasterList($master_type) {
    global $pdo_conn;
    
    try {
        $data = array();
        
        switch ($master_type) {
            case 'product':
                // 商品マスタ
                $filter_bumon = isset($_POST['filter_bumon']) ? (int)$_POST['filter_bumon'] : 0;
                $filter_use = isset($_POST['filter_use']) ? $_POST['filter_use'] : '';
                
                $sql = "SELECT 
                            s.部門コード AS bumon_code,
                            b.部門名 AS bumon_name,
                            s.商品コード AS shohin_code,
                            s.商品名 AS shohin_name,
                            s.使用区分 AS use_flag,
                            s.更新日時 AS update_datetime
                        FROM M_商品 s
                        LEFT JOIN SQL_部門 b ON s.部門コード = b.部門コード
                        WHERE 1=1";
                $params = array();
                
                if ($filter_bumon > 0) {
                    $sql .= " AND s.部門コード = ?";
                    $params[] = $filter_bumon;
                }
                if ($filter_use !== '') {
                    $sql .= " AND s.使用区分 = ?";
                    $params[] = (int)$filter_use;
                }
                
                $sql .= " ORDER BY s.部門コード, s.商品コード";
                
                $stmt = $pdo_conn->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = array(
                        'bumon_code' => $row['bumon_code'],
                        'bumon_name' => $row['bumon_name'],
                        'code' => $row['shohin_code'],
                        'name' => $row['shohin_name'],
                        'use_flag' => $row['use_flag'],
                        'update_date' => formatDateTime($row['update_datetime'])
                    );
                }
                break;
                
            case 'template':
                // 定型文マスタ
                $filter_bumon = isset($_POST['filter_bumon']) ? (int)$_POST['filter_bumon'] : 0;
                
                $sql = "SELECT 
                            t.部門コード AS bumon_code,
                            b.部門名 AS bumon_name,
                            t.定型文コード AS teikei_code,
                            t.定型文 AS teikei_text,
                            t.更新日時 AS update_datetime
                        FROM M_定型文 t
                        LEFT JOIN SQL_部門 b ON t.部門コード = b.部門コード
                        WHERE t.削除日時 IS NULL";
                $params = array();
                
                if ($filter_bumon > 0) {
                    $sql .= " AND t.部門コード = ?";
                    $params[] = $filter_bumon;
                }
                
                $sql .= " ORDER BY t.部門コード, t.定型文コード";
                
                $stmt = $pdo_conn->prepare($sql);
                $stmt->execute($params);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // 一覧表示用に80文字でカット
                    $teikeiText = Nz($row['teikei_text'], '');
                    $displayText = mb_substr($teikeiText, 0, 80, 'UTF-8');
                    if (mb_strlen($teikeiText, 'UTF-8') > 80) {
                        $displayText .= '...';
                    }
                    
                    $data[] = array(
                        'bumon_code' => $row['bumon_code'],
                        'bumon_name' => $row['bumon_name'],
                        'code' => $row['teikei_code'],
                        'text' => $teikeiText,
                        'display_text' => $displayText,
                        'update_date' => formatDateTime($row['update_datetime'])
                    );
                }
                break;
                
            case 'category':
                // 対応区分マスタ
                $sql = "SELECT 
                            対応区分コード AS kubun_code,
                            対応区分名 AS kubun_name,
                            更新日時 AS update_datetime
                        FROM M_対応区分
                        WHERE 削除日時 IS NULL
                        ORDER BY 対応区分コード";
                
                $stmt = $pdo_conn->query($sql);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = array(
                        'code' => $row['kubun_code'],
                        'name' => $row['kubun_name'],
                        'update_date' => formatDateTime($row['update_datetime'])
                    );
                }
                break;
                
            case 'content':
                // 対応内容項目マスタ
                $sql = "SELECT 
                            項目コード AS koumoku_code,
                            項目名 AS koumoku_name,
                            更新日時 AS update_datetime
                        FROM M_対応内容項目
                        WHERE 削除日時 IS NULL
                        ORDER BY 項目コード";
                
                $stmt = $pdo_conn->query($sql);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = array(
                        'code' => $row['koumoku_code'],
                        'name' => $row['koumoku_name'],
                        'update_date' => formatDateTime($row['update_datetime'])
                    );
                }
                break;
        }
        
        jsonSuccess($data);
        
    } catch (PDOException $e) {
        error_log('Master list error: ' . $e->getMessage());
        jsonError('データの取得に失敗しました。', 'MASTER_010');
    }
}

/**
 * マスタデータ取得（1件）- エイリアス対応
 * 
 * @param string $master_type マスタ種別
 */
function getMasterData($master_type) {
    global $pdo_conn;
    
    $code = isset($_POST['code']) ? (int)$_POST['code'] : 0;
    $bumon_code = isset($_POST['bumon_code']) ? (int)$_POST['bumon_code'] : 0;
    
    try {
        $data = null;
        
        switch ($master_type) {
            case 'product':
                $stmt = $pdo_conn->prepare("
                    SELECT 
                        部門コード AS bumon_code, 
                        商品コード AS shohin_code, 
                        商品名 AS shohin_name, 
                        使用区分 AS use_flag
                    FROM M_商品
                    WHERE 部門コード = ? AND 商品コード = ?
                ");
                $stmt->execute(array($bumon_code, $code));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $data = array(
                        'bumon_code' => $row['bumon_code'],
                        'code' => $row['shohin_code'],
                        'name' => $row['shohin_name'],
                        'use_flag' => $row['use_flag']
                    );
                }
                break;
                
            case 'template':
                $stmt = $pdo_conn->prepare("
                    SELECT 
                        部門コード AS bumon_code, 
                        定型文コード AS teikei_code, 
                        定型文 AS teikei_text
                    FROM M_定型文
                    WHERE 部門コード = ? AND 定型文コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($bumon_code, $code));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $data = array(
                        'bumon_code' => $row['bumon_code'],
                        'code' => $row['teikei_code'],
                        'text' => $row['teikei_text']
                    );
                }
                break;
                
            case 'category':
                $stmt = $pdo_conn->prepare("
                    SELECT 
                        対応区分コード AS kubun_code, 
                        対応区分名 AS kubun_name
                    FROM M_対応区分
                    WHERE 対応区分コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($code));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $data = array(
                        'code' => $row['kubun_code'],
                        'name' => $row['kubun_name']
                    );
                }
                break;
                
            case 'content':
                $stmt = $pdo_conn->prepare("
                    SELECT 
                        項目コード AS koumoku_code, 
                        項目名 AS koumoku_name
                    FROM M_対応内容項目
                    WHERE 項目コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($code));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    $data = array(
                        'code' => $row['koumoku_code'],
                        'name' => $row['koumoku_name']
                    );
                }
                break;
        }
        
        if ($data) {
            jsonSuccess($data);
        } else {
            jsonError('データが見つかりません。', 'MASTER_006');
        }
        
    } catch (PDOException $e) {
        error_log('Master get error: ' . $e->getMessage());
        jsonError('データの取得に失敗しました。', 'MASTER_010');
    }
}

/**
 * マスタ保存（登録・更新）
 * 
 * @param string $master_type マスタ種別
 */
function saveMaster($master_type) {
    global $pdo_conn;
    
    $mode = isset($_POST['mode']) ? $_POST['mode'] : 'new';
    
    // バリデーション
    $errors = validateMasterInput($master_type, $_POST, $mode);
    if (count($errors) > 0) {
        jsonError('入力内容に誤りがあります。', 'VALID_001', $errors);
        return;
    }
    
    try {
        switch ($master_type) {
            case 'product':
                saveProduct($mode);
                break;
                
            case 'template':
                saveTemplate($mode);
                break;
                
            case 'category':
                saveCategory($mode);
                break;
                
            case 'content':
                saveContent($mode);
                break;
        }
        
    } catch (PDOException $e) {
        error_log('Master save error: ' . $e->getMessage());
        jsonError('保存に失敗しました。', 'MASTER_004');
    }
}

/**
 * 商品マスタ保存
 */
function saveProduct($mode) {
    global $pdo_conn;
    
    $bumon_code = (int)$_POST['bumon_code'];
    $name = trim($_POST['name']);
    $use_flag = isset($_POST['use_flag']) ? (int)$_POST['use_flag'] : 1;
    
    if ($mode === 'new') {
        // 新規登録 - コード採番（エイリアス使用）
        $stmt = $pdo_conn->prepare("
            SELECT ISNULL(MAX(商品コード), 0) + 1 AS new_code
            FROM M_商品
            WHERE 部門コード = ?
        ");
        $stmt->execute(array($bumon_code));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $code = $result['new_code'];
        
        $stmt = $pdo_conn->prepare("
            INSERT INTO M_商品 (部門コード, 商品コード, 商品名, 使用区分, 入力日時, 更新日時)
            VALUES (?, ?, ?, ?, GETDATE(), GETDATE())
        ");
        $stmt->execute(array($bumon_code, $code, $name, $use_flag));
        
        jsonSuccess(array('bumon_code' => $bumon_code, 'code' => $code), '登録しました。');
        
    } else {
        // 更新
        $code = (int)$_POST['code'];
        
        $stmt = $pdo_conn->prepare("
            UPDATE M_商品 SET 商品名 = ?, 使用区分 = ?, 更新日時 = GETDATE()
            WHERE 部門コード = ? AND 商品コード = ?
        ");
        $stmt->execute(array($name, $use_flag, $bumon_code, $code));
        
        jsonSuccess(array('bumon_code' => $bumon_code, 'code' => $code), '更新しました。');
    }
}

/**
 * 定型文マスタ保存
 */
function saveTemplate($mode) {
    global $pdo_conn;
    
    $bumon_code = (int)$_POST['bumon_code'];
    $text = trim($_POST['text']);
    
    if ($mode === 'new') {
        // 新規登録 - コード採番（エイリアス使用）
        $stmt = $pdo_conn->prepare("
            SELECT ISNULL(MAX(定型文コード), 0) + 1 AS new_code
            FROM M_定型文
            WHERE 部門コード = ?
        ");
        $stmt->execute(array($bumon_code));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $code = $result['new_code'];
        
        $stmt = $pdo_conn->prepare("
            INSERT INTO M_定型文 (部門コード, 定型文コード, 定型文, 入力日時, 更新日時)
            VALUES (?, ?, ?, GETDATE(), GETDATE())
        ");
        $stmt->execute(array($bumon_code, $code, $text));
        
        jsonSuccess(array('bumon_code' => $bumon_code, 'code' => $code), '登録しました。');
        
    } else {
        // 更新
        $code = (int)$_POST['code'];
        
        $stmt = $pdo_conn->prepare("
            UPDATE M_定型文 SET 定型文 = ?, 更新日時 = GETDATE()
            WHERE 部門コード = ? AND 定型文コード = ? AND 削除日時 IS NULL
        ");
        $stmt->execute(array($text, $bumon_code, $code));
        
        jsonSuccess(array('bumon_code' => $bumon_code, 'code' => $code), '更新しました。');
    }
}

/**
 * 対応区分マスタ保存
 */
function saveCategory($mode) {
    global $pdo_conn;
    
    $name = trim($_POST['name']);
    
    if ($mode === 'new') {
        // 新規登録 - コード採番（エイリアス使用）
        $stmt = $pdo_conn->query("
            SELECT ISNULL(MAX(対応区分コード), 0) + 1 AS new_code
            FROM M_対応区分
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $code = $result['new_code'];
        
        $stmt = $pdo_conn->prepare("
            INSERT INTO M_対応区分 (対応区分コード, 対応区分名, 入力日時, 更新日時)
            VALUES (?, ?, GETDATE(), GETDATE())
        ");
        $stmt->execute(array($code, $name));
        
        jsonSuccess(array('code' => $code), '登録しました。');
        
    } else {
        // 更新
        $code = (int)$_POST['code'];
        
        $stmt = $pdo_conn->prepare("
            UPDATE M_対応区分 SET 対応区分名 = ?, 更新日時 = GETDATE()
            WHERE 対応区分コード = ? AND 削除日時 IS NULL
        ");
        $stmt->execute(array($name, $code));
        
        jsonSuccess(array('code' => $code), '更新しました。');
    }
}

/**
 * 対応内容項目マスタ保存
 */
function saveContent($mode) {
    global $pdo_conn;
    
    $code = (int)$_POST['code'];
    $name = trim($_POST['name']);
    
    if ($mode === 'new') {
        // 重複チェック（エイリアス使用）
        $stmt = $pdo_conn->prepare("
            SELECT 項目コード AS koumoku_code FROM M_対応内容項目
            WHERE 項目コード = ? AND 削除日時 IS NULL
        ");
        $stmt->execute(array($code));
        if ($stmt->fetch()) {
            jsonError('この項目コードは既に使用されています。', 'VALID_001', 
                      array('この項目コードは既に使用されています。'));
            return;
        }
        
        $stmt = $pdo_conn->prepare("
            INSERT INTO M_対応内容項目 (項目コード, 項目名, 入力日時, 更新日時)
            VALUES (?, ?, GETDATE(), GETDATE())
        ");
        $stmt->execute(array($code, $name));
        
        jsonSuccess(array('code' => $code), '登録しました。');
        
    } else {
        // 更新
        $stmt = $pdo_conn->prepare("
            UPDATE M_対応内容項目 SET 項目名 = ?, 更新日時 = GETDATE()
            WHERE 項目コード = ? AND 削除日時 IS NULL
        ");
        $stmt->execute(array($name, $code));
        
        jsonSuccess(array('code' => $code), '更新しました。');
    }
}

/**
 * マスタ削除（論理削除）
 * 
 * @param string $master_type マスタ種別
 */
function deleteMaster($master_type) {
    global $pdo_conn;
    
    $code = isset($_POST['code']) ? (int)$_POST['code'] : 0;
    $bumon_code = isset($_POST['bumon_code']) ? (int)$_POST['bumon_code'] : 0;
    
    try {
        switch ($master_type) {
            case 'product':
                // DELDATA_商品テーブルへの移動処理
                $pdo_conn->beginTransaction();
                try {
                    // 1. 対象データ取得
                    $stmt = $pdo_conn->prepare("
                        SELECT 部門コード, 商品コード, 商品名, 使用区分, 入力日時, 更新日時
                        FROM M_商品
                        WHERE 部門コード = ? AND 商品コード = ?
                    ");
                    $stmt->execute(array($bumon_code, $code));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$row) {
                        throw new Exception('対象データが見つかりません。');
                    }
                    
                    // 2. DELDATA_商品へインサート
                    $stmt = $pdo_conn->prepare("
                        INSERT INTO DELDATA_商品 (部門コード, 商品コード, 商品名, 使用区分, 入力日時, 更新日時, 削除日時)
                        VALUES (?, ?, ?, ?, ?, ?, GETDATE())
                    ");
                    $stmt->execute(array(
                        $row['部門コード'],
                        $row['商品コード'],
                        $row['商品名'],
                        $row['使用区分'],
                        $row['入力日時'],
                        $row['更新日時']
                    ));
                    
                    // 3. M_商品から削除
                    $stmt = $pdo_conn->prepare("
                        DELETE FROM M_商品
                        WHERE 部門コード = ? AND 商品コード = ?
                    ");
                    $stmt->execute(array($bumon_code, $code));
                    
                    $pdo_conn->commit();
                } catch (Exception $e) {
                    $pdo_conn->rollBack();
                    throw $e;
                }
                break;
                
            case 'template':
                $stmt = $pdo_conn->prepare("
                    UPDATE M_定型文 SET 削除日時 = GETDATE()
                    WHERE 部門コード = ? AND 定型文コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($bumon_code, $code));
                break;
                
            case 'category':
                $stmt = $pdo_conn->prepare("
                    UPDATE M_対応区分 SET 削除日時 = GETDATE()
                    WHERE 対応区分コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($code));
                break;
                
            case 'content':
                $stmt = $pdo_conn->prepare("
                    UPDATE M_対応内容項目 SET 削除日時 = GETDATE()
                    WHERE 項目コード = ? AND 削除日時 IS NULL
                ");
                $stmt->execute(array($code));
                break;
        }
        
        jsonSuccess(null, '削除しました。');
        
    } catch (PDOException $e) {
        error_log('Master delete error: ' . $e->getMessage());
        jsonError('削除に失敗しました。', 'MASTER_005');
    }
}

/**
 * マスタ入力バリデーション
 * 
 * @param string $master_type マスタ種別
 * @param array $data 入力データ
 * @param string $mode 'new' or 'edit'
 * @return array エラーメッセージ配列
 */
function validateMasterInput($master_type, $data, $mode) {
    $errors = array();
    
    switch ($master_type) {
        case 'product':
            if (empty($data['bumon_code'])) {
                $errors[] = '部門を選択してください。';
            }
            if (empty(trim($data['name']))) {
                $errors[] = '商品名を入力してください。';
            } else if (mb_strlen($data['name'], 'UTF-8') > 255) {
                $errors[] = '商品名は255文字以内で入力してください。';
            }
            if (!isset($data['use_flag']) || $data['use_flag'] === '') {
                $errors[] = '使用区分を選択してください。';
            }
            break;
            
        case 'template':
            if (empty($data['bumon_code'])) {
                $errors[] = '部門を選択してください。';
            }
            if (empty(trim($data['text']))) {
                $errors[] = '定型文を入力してください。';
            }
            break;
            
        case 'category':
            if (empty(trim($data['name']))) {
                $errors[] = '対応区分名を入力してください。';
            } else if (mb_strlen($data['name'], 'UTF-8') > 255) {
                $errors[] = '対応区分名は255文字以内で入力してください。';
            }
            break;
            
        case 'content':
            $code = isset($data['code']) ? (int)$data['code'] : 0;
            if ($code < 1 || $code > 20) {
                $errors[] = '項目コードは1〜20の範囲で入力してください。';
            }
            if (empty(trim($data['name']))) {
                $errors[] = '項目名を入力してください。';
            } else if (mb_strlen($data['name'], 'UTF-8') > 255) {
                $errors[] = '項目名は255文字以内で入力してください。';
            }
            break;
    }
    
    return $errors;
}

/**
 * 日時フォーマット
 * 
 * @param string $datetime 日時文字列
 * @return string フォーマット済み日時
 */
function formatDateTime($datetime) {
    if (empty($datetime)) {
        return '';
    }
    $dt = new DateTime($datetime);
    return $dt->format('Y/m/d H:i');
}
