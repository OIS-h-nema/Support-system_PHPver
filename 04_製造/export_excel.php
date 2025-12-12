<?php
/**
 * ファイル名: export_excel.php
 * 機能概要: Excel出力処理（CSV形式）
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 08）
 * 2025-12-08 SQL_顧客とのJOINキーを修正（顧客コード→SEQNO）
 * 2025-12-08 英語エイリアス対応（文字化け対策）
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// 認証チェック
requireLogin();

// 検索条件取得
$tantou = isset($_GET['tantou']) ? (int)$_GET['tantou'] : 0;
$kokyaku = isset($_GET['kokyaku']) ? trim($_GET['kokyaku']) : '';
$shohin = isset($_GET['shohin']) ? (int)$_GET['shohin'] : 0;
$kubun = isset($_GET['kubun']) ? (int)$_GET['kubun'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$content = isset($_GET['content']) ? $_GET['content'] : array();

// 対応内容フラグの処理
if (is_string($content) && !empty($content)) {
    $content = explode(',', $content);
}

try {
    global $pdo_conn;
    
    // 動的SQL構築（英語エイリアスを使用して文字化けを回避）
    $sql = "SELECT 
                d.SEQNO AS seqno,
                d.対応開始日時 AS taiou_start,
                d.対応終了日時 AS taiou_end,
                d.顧客コード AS kokyaku_code,
                ISNULL(k.顧客名, '') AS kokyaku_name,
                d.顧客担当者名 AS kokyaku_tantou,
                d.商品コード1 AS shohin_code1,
                d.商品コード2 AS shohin_code2,
                d.商品コード3 AS shohin_code3,
                ISNULL(s1.商品名, '') AS shohin_name1,
                ISNULL(s2.商品名, '') AS shohin_name2,
                ISNULL(s3.商品名, '') AS shohin_name3,
                d.作業担当コード AS tantou_code,
                ISNULL(t.担当者名, '') AS tantou_name,
                d.対応区分コード AS kubun_code,
                ISNULL(kb.対応区分名, '') AS kubun_name,
                d.引継担当コード AS hikitsugi_code,
                ISNULL(ht.担当者名, '') AS hikitsugi_name,
                d.報告内容 AS houkoku,
                d.対応内容フラグ1 AS flag1, d.対応内容フラグ2 AS flag2, d.対応内容フラグ3 AS flag3, d.対応内容フラグ4 AS flag4, d.対応内容フラグ5 AS flag5,
                d.対応内容フラグ6 AS flag6, d.対応内容フラグ7 AS flag7, d.対応内容フラグ8 AS flag8, d.対応内容フラグ9 AS flag9, d.対応内容フラグ10 AS flag10,
                d.対応内容フラグ11 AS flag11, d.対応内容フラグ12 AS flag12, d.対応内容フラグ13 AS flag13, d.対応内容フラグ14 AS flag14, d.対応内容フラグ15 AS flag15,
                d.対応内容フラグ16 AS flag16, d.対応内容フラグ17 AS flag17, d.対応内容フラグ18 AS flag18, d.対応内容フラグ19 AS flag19, d.対応内容フラグ20 AS flag20
            FROM D_作業報告 d
            LEFT JOIN SQL_顧客 k ON d.顧客コード = k.SEQNO
            LEFT JOIN M_商品 s1 ON d.商品コード1 = s1.商品コード AND s1.削除日時 IS NULL
            LEFT JOIN M_商品 s2 ON d.商品コード2 = s2.商品コード AND s2.削除日時 IS NULL
            LEFT JOIN M_商品 s3 ON d.商品コード3 = s3.商品コード AND s3.削除日時 IS NULL
            LEFT JOIN SQL_作業担当 t ON d.作業担当コード = t.担当者コード
            LEFT JOIN M_対応区分 kb ON d.対応区分コード = kb.対応区分コード AND kb.削除日時 IS NULL
            LEFT JOIN SQL_作業担当 ht ON d.引継担当コード = ht.担当者コード
            WHERE d.削除日時 IS NULL";
    
    $params = array();
    
    // 検索条件追加
    if ($tantou > 0) {
        $sql .= " AND d.作業担当コード = ?";
        $params[] = $tantou;
    }
    
    if (!empty($kokyaku)) {
        $sql .= " AND k.顧客名 LIKE ?";
        $params[] = '%' . $kokyaku . '%';
    }
    
    if ($shohin > 0) {
        $sql .= " AND (d.商品コード1 = ? OR d.商品コード2 = ? OR d.商品コード3 = ?)";
        $params[] = $shohin;
        $params[] = $shohin;
        $params[] = $shohin;
    }
    
    if ($kubun > 0) {
        $sql .= " AND d.対応区分コード = ?";
        $params[] = $kubun;
    }
    
    if (!empty($date_from)) {
        $sql .= " AND d.対応開始日時 >= ?";
        $params[] = $date_from . ' 00:00:00';
    }
    
    if (!empty($date_to)) {
        $sql .= " AND d.対応開始日時 <= ?";
        $params[] = $date_to . ' 23:59:59';
    }
    
    if (!empty($keyword)) {
        $sql .= " AND d.報告内容 LIKE ?";
        $params[] = '%' . $keyword . '%';
    }
    
    // 対応内容フラグ（-1もTrueとして扱う - Access互換）
    if (is_array($content) && count($content) > 0) {
        $flagConditions = array();
        foreach ($content as $flagCode) {
            $flagCode = (int)$flagCode;
            if ($flagCode >= 1 && $flagCode <= 20) {
                $flagConditions[] = "(d.対応内容フラグ" . $flagCode . " = 1 OR d.対応内容フラグ" . $flagCode . " = -1)";
            }
        }
        if (count($flagConditions) > 0) {
            $sql .= " AND (" . implode(' OR ', $flagConditions) . ")";
        }
    }
    
    // ソート
    $sql .= " ORDER BY d.対応開始日時 DESC";
    
    $stmt = $pdo_conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 対応内容項目名を取得（英語エイリアス使用）
    $stmtContent = $pdo_conn->query("SELECT 項目コード AS code, 項目名 AS name FROM M_対応内容項目 WHERE 削除日時 IS NULL ORDER BY 項目コード");
    $contentItems = array();
    while ($contentRow = $stmtContent->fetch(PDO::FETCH_ASSOC)) {
        $contentItems[$contentRow['code']] = $contentRow['name'];
    }
    
    // ファイル名生成
    $filename = 'サポート報告書一覧_' . date('Ymd_His') . '.csv';
    
    // HTTPヘッダー設定
    header('Content-Type: text/csv; charset=Shift_JIS');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');
    
    // 出力バッファ
    $output = fopen('php://output', 'w');
    
    // BOM（Excelで文字化けしないように）
    // Shift-JISの場合はBOM不要
    
    // ヘッダー行
    $header = array(
        'No',
        '対応日',
        '開始時刻',
        '終了時刻',
        '顧客コード',
        '顧客名',
        '顧客担当者',
        '商品1',
        '商品2',
        '商品3',
        '作業担当',
        '対応区分',
        '対応内容',
        '報告内容',
        '引継担当'
    );
    
    // ヘッダーをShift-JISに変換して出力
    $headerSjis = array_map(function($item) {
        return mb_convert_encoding($item, 'SJIS-win', 'UTF-8');
    }, $header);
    fputcsv($output, $headerSjis);
    
    // データ行
    $no = 0;
    foreach ($rows as $row) {
        $no++;
        
        // 対応日時の分解（英語エイリアス名でアクセス）
        $startDateTime = $row['taiou_start'];
        $endDateTime = $row['taiou_end'];
        
        $taiouDate = '';
        $startTime = '';
        $endTime = '';
        
        if (!empty($startDateTime)) {
            $dt = new DateTime($startDateTime);
            $taiouDate = $dt->format('Y/m/d');
            $startTime = $dt->format('H:i');
        }
        
        if (!empty($endDateTime)) {
            $dt = new DateTime($endDateTime);
            $endTime = $dt->format('H:i');
        }
        
        // 商品名結合（英語エイリアス名でアクセス）
        $shohinNames = array_filter(array(
            $row['shohin_name1'],
            $row['shohin_name2'],
            $row['shohin_name3']
        ));
        
        // 対応内容フラグを項目名に変換（英語エイリアス名でアクセス）
        // Access互換: -1 または 1 をTrueとして扱う
        $contentNames = array();
        for ($i = 1; $i <= 20; $i++) {
            $flagKey = 'flag' . $i;
            if (isset($row[$flagKey]) && ($row[$flagKey] == 1 || $row[$flagKey] == -1)) {
                if (isset($contentItems[$i])) {
                    $contentNames[] = $contentItems[$i];
                }
            }
        }
        
        // 報告内容の改行を変換（英語エイリアス名でアクセス）
        $houkoku = str_replace(array("\r\n", "\r", "\n"), " ", $row['houkoku']);
        
        // データ行（英語エイリアス名でアクセス）
        $data = array(
            $no,
            $taiouDate,
            $startTime,
            $endTime,
            $row['kokyaku_code'],
            $row['kokyaku_name'],
            $row['kokyaku_tantou'],
            $row['shohin_name1'],
            $row['shohin_name2'],
            $row['shohin_name3'],
            $row['tantou_name'],
            $row['kubun_name'],
            implode('/', $contentNames),
            $houkoku,
            $row['hikitsugi_name']
        );
        
        // Shift-JISに変換して出力
        $dataSjis = array_map(function($item) {
            if ($item === null) {
                return '';
            }
            return mb_convert_encoding($item, 'SJIS-win', 'UTF-8');
        }, $data);
        fputcsv($output, $dataSjis);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    error_log('Excel export error: ' . $e->getMessage());
    
    // エラー時はメイン画面にリダイレクト
    setErrorMessage('Excel出力中にエラーが発生しました。');
    header('Location: support_main.php');
    exit;
}
