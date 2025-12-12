<?php
/**
 * ファイル名: support_ajax01.php
 * 機能概要: サポート報告書Ajax処理
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 * 2025-12-03 カラム名エイリアス対応（文字化け対策）
 * 2025-12-04 SQL_顧客サブクエリ対応（文字化け対策）
 * 2025-12-08 SQL_顧客とのJOINキーをSEQNOに修正
 * 2025-12-10 getData()の作成日時カラム名修正
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
    case 'search':
        doSearch();
        break;
        
    case 'get':
        getData();
        break;
        
    case 'change_display_count':
        changeDisplayCount();
        break;
        
    case 'sort':
        doSort();
        break;
        
    default:
        jsonError('不正なリクエストです。', ERR_SYS_INVALID_REQUEST);
        break;
}

/**
 * 検索処理
 */
function doSearch() {
    global $pdo_conn;
    
    // パラメータ取得
    $bumon = isset($_POST['bumon']) && $_POST['bumon'] !== '' ? (int)$_POST['bumon'] : null;
    $tantou = isset($_POST['tantou']) && $_POST['tantou'] !== '' ? (int)$_POST['tantou'] : null;
    $kokyaku = isset($_POST['kokyaku']) ? trim($_POST['kokyaku']) : '';
    $shohin = isset($_POST['shohin']) && $_POST['shohin'] !== '' ? (int)$_POST['shohin'] : null;
    $kubun = isset($_POST['kubun']) && $_POST['kubun'] !== '' ? (int)$_POST['kubun'] : null;
    $date_from = isset($_POST['date_from']) && $_POST['date_from'] !== '' ? $_POST['date_from'] : null;
    $date_to = isset($_POST['date_to']) && $_POST['date_to'] !== '' ? $_POST['date_to'] : null;
    $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
    $content = isset($_POST['content']) && is_array($_POST['content']) ? $_POST['content'] : array();
    $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : DEFAULT_PAGE_SIZE;
    $sort_column = isset($_POST['sort_column']) ? $_POST['sort_column'] : 'taiou_start';
    $sort_order = isset($_POST['sort_order']) && strtoupper($_POST['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // 表示件数バリデーション
    if (!in_array($limit, PAGE_SIZE_OPTIONS)) {
        $limit = DEFAULT_PAGE_SIZE;
    }
    
    // ソートカラムバリデーション
    $allowed_sort_columns = array('taiou_start', 'tantou_name', 'kokyaku_name', 'seqno');
    if (!in_array($sort_column, $allowed_sort_columns)) {
        $sort_column = 'taiou_start';
    }
    
    // 日付バリデーション
    if ($date_from !== null) {
        $validator = new Validator();
        if (!$validator->date('date_from', $date_from, '対応日（開始）')) {
            $date_from = null;
        }
    }
    if ($date_to !== null) {
        $validator = new Validator();
        if (!$validator->date('date_to', $date_to, '対応日（終了）')) {
            $date_to = null;
        }
    }
    
    // 検索条件をセッションに保存
    saveSearchParams(array(
        'taiou_date_from' => $date_from,
        'taiou_date_to' => $date_to,
        'bumon_code' => $bumon,
        'tantou_code' => $tantou,
        'kokyaku_name' => $kokyaku,
        'shohin_code' => $shohin,
        'kubun_code' => $kubun,
        'keyword' => $keyword,
        'taiou_flags' => $content
    ));
    
    // ソート条件を保存
    setSortParams($sort_column, $sort_order);
    
    // 表示件数を保存
    setDisplayCount($limit);
    
    try {
        // カウント用クエリ
        $count_sql = buildSearchCountSQL($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content);
        $count_params = buildSearchParams($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content);
        
        $stmt = $pdo_conn->prepare($count_sql);
        $stmt->execute($count_params);
        $total = (int)$stmt->fetchColumn();
        
        // 総ページ数計算
        $total_pages = max(1, ceil($total / $limit));
        
        // ページ番号調整
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        
        // 現在ページを保存
        setCurrentPage($page);
        
        // オフセット計算
        $offset = ($page - 1) * $limit;
        
        // データ取得用クエリ
        $data_sql = buildSearchDataSQL($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content, $sort_column, $sort_order, $offset, $limit);
        
        $stmt = $pdo_conn->prepare($data_sql);
        $stmt->execute($count_params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 結果整形
        $data = array();
        foreach ($rows as $row) {
            // 商品名結合（エイリアス名でアクセス）
            $shohin_names = array();
            if (!empty($row['shohin_name1'])) $shohin_names[] = $row['shohin_name1'];
            if (!empty($row['shohin_name2'])) $shohin_names[] = $row['shohin_name2'];
            if (!empty($row['shohin_name3'])) $shohin_names[] = $row['shohin_name3'];
            
            // 対応内容フラグ結合
            $content_names = getContentNames($row);
            
            // 報告内容（全文と短縮版）
            $houkoku_full = Nz($row['houkoku'], '');
            $houkoku_short = $houkoku_full;
            if (mb_strlen($houkoku_full, 'UTF-8') > 50) {
                $houkoku_short = mb_substr($houkoku_full, 0, 50, 'UTF-8') . '...';
            }
            
            // 日時フォーマット（エイリアス名でアクセス）
            $datetime = '';
            if (!empty($row['taiou_start'])) {
                $datetime = date('Y/m/d H:i', strtotime($row['taiou_start']));
            }
            
            $data[] = array(
                'seqno' => (int)$row['seqno'],
                'tantou_name' => Nz($row['tantou_name'], ''),
                'shohin_name' => implode(', ', $shohin_names),
                'taiou_datetime' => $datetime,
                'taiou_content' => implode(', ', $content_names),
                'kokyaku_name' => Nz($row['kokyaku_name'], ''),
                'houkoku' => $houkoku_short,
                'houkoku_short' => $houkoku_short,
                'houkoku_full' => $houkoku_full
            );
        }
        
        jsonSuccess(array(
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $total_pages,
            'data' => $data
        ));
        
    } catch (PDOException $e) {
        error_log('Search error: ' . $e->getMessage());
        jsonError('検索中にエラーが発生しました。', ERR_DB_SELECT);
    }
}

/**
 * 検索件数取得用SQLを構築
 */
function buildSearchCountSQL($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content) {
    $sql = "SELECT COUNT(*) FROM D_作業報告 d
            LEFT JOIN SQL_作業担当 t ON d.作業担当コード = t.担当者コード
            LEFT JOIN (SELECT SEQNO AS kokyaku_code, 顧客名 AS kokyaku_name FROM SQL_顧客) k ON d.顧客コード = k.kokyaku_code
            WHERE d.削除日時 IS NULL";
    
    $sql .= buildWhereClause($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content);
    
    return $sql;
}

/**
 * 検索データ取得用SQLを構築（エイリアス使用で文字化け対策）
 */
function buildSearchDataSQL($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content, $sort_column, $sort_order, $offset, $limit) {
    $sql = "SELECT 
                d.SEQNO AS seqno,
                d.対応開始日時 AS taiou_start,
                d.対応終了日時 AS taiou_end,
                d.顧客コード AS kokyaku_code,
                d.顧客担当者名 AS kokyaku_tantou,
                d.作業担当コード AS tantou_code,
                d.対応区分コード AS kubun_code,
                d.報告内容 AS houkoku,
                d.商品コード1 AS shohin_code1,
                d.商品コード2 AS shohin_code2,
                d.商品コード3 AS shohin_code3,";
    
    // 対応内容フラグを動的に追加（1〜20）
    for ($i = 1; $i <= 20; $i++) {
        $sql .= "d.対応内容フラグ{$i} AS flag{$i},";
    }
    
    $sql .= "
                t.担当者名 AS tantou_name,
                k.kokyaku_name,
                s1.商品名 AS shohin_name1,
                s2.商品名 AS shohin_name2,
                s3.商品名 AS shohin_name3,
                ku.対応区分名 AS kubun_name
            FROM D_作業報告 d
            LEFT JOIN SQL_作業担当 t ON d.作業担当コード = t.担当者コード
            LEFT JOIN (SELECT SEQNO AS kokyaku_code, 顧客名 AS kokyaku_name FROM SQL_顧客) k ON d.顧客コード = k.kokyaku_code
            LEFT JOIN M_商品 s1 ON d.商品コード1 = s1.商品コード
            LEFT JOIN M_商品 s2 ON d.商品コード2 = s2.商品コード
            LEFT JOIN M_商品 s3 ON d.商品コード3 = s3.商品コード
            LEFT JOIN M_対応区分 ku ON d.対応区分コード = ku.対応区分コード
            WHERE d.削除日時 IS NULL";
    
    $sql .= buildWhereClause($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content);
    
    // ソート（エイリアス名を使用）
    $sql .= " ORDER BY ";
    switch ($sort_column) {
        case 'tantou_name':
            $sql .= "t.担当者名";
            break;
        case 'kokyaku_name':
            $sql .= "k.kokyaku_name";
            break;
        case 'seqno':
            $sql .= "d.SEQNO";
            break;
        case 'taiou_start':
        default:
            $sql .= "d.対応開始日時";
            break;
    }
    $sql .= " {$sort_order}";
    
    // ページング
    $sql .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
    
    return $sql;
}

/**
 * WHERE句を構築
 */
function buildWhereClause($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content) {
    $where = '';
    
    // 部門
    if ($bumon !== null) {
        $where .= " AND t.部門コード = ?";
    }
    
    // 担当者
    if ($tantou !== null) {
        $where .= " AND d.作業担当コード = ?";
    }
    
    // 顧客名（部分一致）- サブクエリのエイリアス名を使用
    if (!empty($kokyaku)) {
        $where .= " AND k.kokyaku_name LIKE ?";
    }
    
    // 商品
    if ($shohin !== null) {
        $where .= " AND (d.商品コード1 = ? OR d.商品コード2 = ? OR d.商品コード3 = ?)";
    }
    
    // 対応区分
    if ($kubun !== null) {
        $where .= " AND d.対応区分コード = ?";
    }
    
    // 対応日（開始）
    if ($date_from !== null) {
        $where .= " AND d.対応開始日時 >= ?";
    }
    
    // 対応日（終了）
    if ($date_to !== null) {
        $where .= " AND d.対応開始日時 < DATEADD(day, 1, ?)";
    }
    
    // キーワード（報告内容の部分一致）
    if (!empty($keyword)) {
        $where .= " AND d.報告内容 LIKE ?";
    }
    
    // 対応内容フラグ（-1もTrueとして扱う）
    if (!empty($content) && is_array($content)) {
        $flag_conditions = array();
        foreach ($content as $code) {
            $code = (int)$code;
            if ($code >= 1 && $code <= 20) {
                $flag_conditions[] = "(d.対応内容フラグ{$code} = 1 OR d.対応内容フラグ{$code} = -1)";
            }
        }
        if (!empty($flag_conditions)) {
            $where .= " AND (" . implode(' OR ', $flag_conditions) . ")";
        }
    }
    
    return $where;
}

/**
 * 検索パラメータ配列を構築
 */
function buildSearchParams($bumon, $tantou, $kokyaku, $shohin, $kubun, $date_from, $date_to, $keyword, $content) {
    $params = array();
    
    if ($bumon !== null) {
        $params[] = $bumon;
    }
    
    if ($tantou !== null) {
        $params[] = $tantou;
    }
    
    if (!empty($kokyaku)) {
        $params[] = '%' . $kokyaku . '%';
    }
    
    if ($shohin !== null) {
        $params[] = $shohin;
        $params[] = $shohin;
        $params[] = $shohin;
    }
    
    if ($kubun !== null) {
        $params[] = $kubun;
    }
    
    if ($date_from !== null) {
        $params[] = $date_from;
    }
    
    if ($date_to !== null) {
        $params[] = $date_to;
    }
    
    if (!empty($keyword)) {
        $params[] = '%' . $keyword . '%';
    }
    
    return $params;
}

/**
 * 対応内容名を取得（エイリアス対応）
 */
function getContentNames($row) {
    global $pdo_conn;
    static $content_master = null;
    
    // マスタをキャッシュ（エイリアス使用）
    if ($content_master === null) {
        try {
            $stmt = $pdo_conn->query("SELECT 項目コード AS code, 項目名 AS name FROM M_対応内容項目 ORDER BY 項目コード");
            $content_master = array();
            while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $content_master[(int)$item['code']] = $item['name'];
            }
        } catch (PDOException $e) {
            $content_master = array();
        }
    }
    
    $names = array();
    for ($i = 1; $i <= 20; $i++) {
        $flag_key = "flag{$i}";
        if (isset($row[$flag_key]) && ($row[$flag_key] == 1 || $row[$flag_key] == -1)) {
            if (isset($content_master[$i])) {
                $names[] = $content_master[$i];
            }
        }
    }
    
    return $names;
}

/**
 * データ取得（単一レコード）- エイリアス対応
 */
function getData() {
    global $pdo_conn;
    
    $seqno = isset($_POST['seqno']) ? (int)$_POST['seqno'] : 0;
    
    if ($seqno <= 0) {
        jsonError('データIDが指定されていません。', ERR_VALID_REQUIRED);
        return;
    }
    
    try {
        // 対応内容フラグ部分を生成
        $flag_columns = "";
        for ($i = 1; $i <= 20; $i++) {
            $flag_columns .= "d.[対応内容フラグ{$i}] AS flag{$i},";
        }
        
        $sql = "SELECT 
                    d.SEQNO AS seqno,
                    d.[対応開始日時] AS taiou_start,
                    d.[対応終了日時] AS taiou_end,
                    d.[顧客コード] AS kokyaku_code,
                    d.[顧客担当者名] AS kokyaku_tantou,
                    d.[作業担当コード] AS tantou_code,
                    d.[対応区分コード] AS kubun_code,
                    d.[引継担当コード] AS hikitsugi_code,
                    d.[報告内容] AS houkoku,
                    d.[商品コード1] AS shohin_code1,
                    d.[商品コード2] AS shohin_code2,
                    d.[商品コード3] AS shohin_code3,
                    d.[作成日時] AS input_datetime,
                    d.[更新日時] AS update_datetime,
                    {$flag_columns}
                    t.[担当者名] AS tantou_name,
                    k.kokyaku_name,
                    s1.[商品名] AS shohin_name1,
                    s2.[商品名] AS shohin_name2,
                    s3.[商品名] AS shohin_name3,
                    ku.[対応区分名] AS kubun_name
                FROM D_作業報告 d
                LEFT JOIN SQL_作業担当 t ON d.[作業担当コード] = t.[担当者コード]
                LEFT JOIN (SELECT SEQNO AS kokyaku_code, [顧客名] AS kokyaku_name FROM SQL_顧客) k ON d.[顧客コード] = k.kokyaku_code
                LEFT JOIN M_商品 s1 ON d.[商品コード1] = s1.[商品コード]
                LEFT JOIN M_商品 s2 ON d.[商品コード2] = s2.[商品コード]
                LEFT JOIN M_商品 s3 ON d.[商品コード3] = s3.[商品コード]
                LEFT JOIN M_対応区分 ku ON d.[対応区分コード] = ku.[対応区分コード]
                WHERE d.SEQNO = ? AND d.[削除日時] IS NULL";
        
        $stmt = $pdo_conn->prepare($sql);
        $stmt->execute(array($seqno));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            jsonError('データが見つかりません。', ERR_DB_NOT_FOUND);
            return;
        }
        
        jsonSuccess(array('data' => $row));
        
    } catch (PDOException $e) {
        error_log('Get data error: ' . $e->getMessage());
        jsonError('データ取得中にエラーが発生しました。', ERR_DB_SELECT);
    }
}

/**
 * 表示件数変更
 */
function changeDisplayCount() {
    $count = isset($_POST['count']) ? (int)$_POST['count'] : DEFAULT_PAGE_SIZE;
    
    if (!in_array($count, PAGE_SIZE_OPTIONS)) {
        $count = DEFAULT_PAGE_SIZE;
    }
    
    setDisplayCount($count);
    setCurrentPage(1);
    
    jsonSuccess(array('count' => $count));
}

/**
 * ソート変更
 */
function doSort() {
    $column = isset($_POST['column']) ? $_POST['column'] : 'taiou_start';
    $order = isset($_POST['order']) && strtoupper($_POST['order']) === 'ASC' ? 'ASC' : 'DESC';
    
    $allowed_columns = array('taiou_start', 'tantou_name', 'kokyaku_name', 'seqno');
    if (!in_array($column, $allowed_columns)) {
        $column = 'taiou_start';
    }
    
    setSortParams($column, $order);
    setCurrentPage(1);
    
    jsonSuccess(array('column' => $column, 'order' => $order));
}
