<?php
/**
 * ファイル名: functions.php
 * 機能概要: 共通関数ライブラリ
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 */

//---------------------------------------------------
// 文字列処理関数
//---------------------------------------------------

/**
 * HTMLエスケープ処理（XSS対策）
 * 
 * @param string $str 対象文字列
 * @return string エスケープ済み文字列
 */
function chkSunitize($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * HTMLエスケープ処理（エイリアス）
 * 
 * @param string $str 対象文字列
 * @return string エスケープ済み文字列
 */
function h($str) {
    return chkSunitize($str);
}

/**
 * JavaScript出力用エスケープ
 * 
 * @param string $str 対象文字列
 * @return string JSON形式の文字列
 */
function jsEscape($str) {
    if ($str === null) {
        return 'null';
    }
    return json_encode($str, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
}

/**
 * 属性値用エスケープ
 * 
 * @param string $str 対象文字列
 * @return string エスケープ済み文字列
 */
function attrEscape($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * 改行を<br />に変換
 * 
 * @param string $str 対象文字列
 * @return string 変換後文字列
 */
function br_convert($str) {
    if ($str === null) {
        return '';
    }
    $str = str_replace("\r\n", "\r", $str);
    $str = str_replace("\r", "\n", $str);
    $str = str_replace("\n", "<br />", $str);
    return $str;
}

/**
 * <br />を改行に変換
 * 
 * @param string $str 対象文字列
 * @return string 変換後文字列
 */
function nr_convert($str) {
    if ($str === null) {
        return '';
    }
    $str = str_replace("<br />", "\n", $str);
    $str = str_replace("<br>", "\n", $str);
    $str = str_replace("\n", "\r\n", $str);
    return $str;
}

//---------------------------------------------------
// VBA互換関数（文字列）
//---------------------------------------------------

/**
 * 左からn文字取得（VBA Left関数相当）
 * 
 * @param string $str 対象文字列
 * @param int $length 取得文字数
 * @return string
 */
function left($str, $length) {
    if ($str === null) {
        return '';
    }
    return mb_substr($str, 0, $length, 'UTF-8');
}

/**
 * 右からn文字取得（VBA Right関数相当）
 * 
 * @param string $str 対象文字列
 * @param int $length 取得文字数
 * @return string
 */
function right($str, $length) {
    if ($str === null) {
        return '';
    }
    return mb_substr($str, -$length, $length, 'UTF-8');
}

/**
 * 中間n文字取得（VBA Mid関数相当）
 * 
 * @param string $str 対象文字列
 * @param int $start 開始位置（1始まり）
 * @param int|null $length 取得文字数
 * @return string
 */
function mid($str, $start, $length = null) {
    if ($str === null) {
        return '';
    }
    $start = $start - 1; // 0始まりに変換
    if ($length === null) {
        return mb_substr($str, $start, null, 'UTF-8');
    }
    return mb_substr($str, $start, $length, 'UTF-8');
}

/**
 * 文字列の長さを取得（VBA Len関数相当）
 * 
 * @param string $str 対象文字列
 * @return int
 */
function len($str) {
    if ($str === null) {
        return 0;
    }
    return mb_strlen($str, 'UTF-8');
}

/**
 * トリム（VBA Trim関数相当）
 * 
 * @param string $str 対象文字列
 * @return string
 */
function trimStr($str) {
    if ($str === null) {
        return '';
    }
    return trim($str);
}

/**
 * Null値の置換（VBA Nz関数相当）
 * 
 * @param mixed $value 対象値
 * @param mixed $default デフォルト値
 * @return mixed
 */
function Nz($value, $default = '') {
    return $value === null ? $default : $value;
}

/**
 * 空文字の置換
 * 
 * @param mixed $value 対象値
 * @param mixed $default デフォルト値
 * @return mixed
 */
function Ez($value, $default = null) {
    if (is_null($value) || $value === '') {
        return $default;
    }
    return $value;
}

//---------------------------------------------------
// VBA互換関数（日付）
//---------------------------------------------------

/**
 * 現在の日付を取得（VBA Date関数相当）
 * 
 * @return string 'Y-m-d' 形式の日付
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * 現在の日時を取得（VBA Now関数相当）
 * 
 * @return string 'Y-m-d H:i:s' 形式の日時
 */
function getCurrentDateTime() {
    return date('Y-m-d H:i:s');
}

/**
 * 日付の差分を計算（VBA DateDiff相当）
 * 
 * @param string $date1 開始日
 * @param string $date2 終了日
 * @return int 日数の差
 */
function dateDiff($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * 日付の加算（VBA DateAdd相当）
 * 
 * @param string $date 基準日
 * @param int $days 加算日数
 * @return string 'Y-m-d' 形式の日付
 */
function dateAdd($date, $days) {
    $datetime = new DateTime($date);
    $datetime->modify("+{$days} days");
    return $datetime->format('Y-m-d');
}

/**
 * 年を取得（VBA Year関数相当）
 * 
 * @param string $date 日付
 * @return int 年
 */
function getYear($date) {
    if (empty($date)) {
        return 0;
    }
    return (int)date('Y', strtotime($date));
}

/**
 * 月を取得（VBA Month関数相当）
 * 
 * @param string $date 日付
 * @return int 月
 */
function getMonth($date) {
    if (empty($date)) {
        return 0;
    }
    return (int)date('m', strtotime($date));
}

/**
 * 日を取得（VBA Day関数相当）
 * 
 * @param string $date 日付
 * @return int 日
 */
function getDay($date) {
    if (empty($date)) {
        return 0;
    }
    return (int)date('d', strtotime($date));
}

/**
 * 西暦→和暦変換
 * 
 * @param string $format フォーマット文字列
 * @param string $time 変換対象の日付（西暦）
 * @return string 変換後の日付（和暦）
 */
function to_wareki($format, $time = 'now') {
    $era_list = [
        ['jp' => '令和', 'jp_abbr' => '令', 'en' => 'Reiwa', 'en_abbr' => 'R', 'time' => '20190501'],
        ['jp' => '平成', 'jp_abbr' => '平', 'en' => 'Heisei', 'en_abbr' => 'H', 'time' => '19890108'],
        ['jp' => '昭和', 'jp_abbr' => '昭', 'en' => 'Showa', 'en_abbr' => 'S', 'time' => '19261225'],
        ['jp' => '大正', 'jp_abbr' => '大', 'en' => 'Taisho', 'en_abbr' => 'T', 'time' => '19120730'],
        ['jp' => '明治', 'jp_abbr' => '明', 'en' => 'Meiji', 'en_abbr' => 'M', 'time' => '18730101'],
    ];

    $dt = new DateTime($time);

    $format_K = '';
    $format_k = '';
    $format_Q = '';
    $format_q = '';
    $format_X = $dt->format('Y');
    $format_x = $dt->format('y');

    foreach ($era_list as $era) {
        $dt_era = new DateTime($era['time']);
        if ($dt->format('Ymd') >= $dt_era->format('Ymd')) {
            $format_K = $era['jp'];
            $format_k = $era['jp_abbr'];
            $format_Q = $era['en'];
            $format_q = $era['en_abbr'];
            $format_X = sprintf('%02d', $format_x = $dt->format('Y') - $dt_era->format('Y') + 1);
            break;
        }
    }

    $result = '';
    foreach (str_split($format) as $val) {
        if (isset(${"format_{$val}"})) {
            $result .= ${"format_{$val}"};
        } else {
            $result .= $dt->format($val);
        }
    }

    return $result;
}

//---------------------------------------------------
// VBA互換関数（数値）
//---------------------------------------------------

/**
 * 四捨五入
 * 
 * @param float $val 対象値
 * @param int $pos 小数点以下の桁数
 * @return float
 */
function myRound($val, $pos) {
    $aa = pow(10, $pos);
    
    if ($val == 0 || is_null($val)) {
        return 0;
    }
    
    if ($val > 0) {
        return floor(strval(($val * $aa) + 0.5)) / $aa;
    } else {
        return ceil(strval(($val * $aa) - 0.5)) / $aa;
    }
}

/**
 * 切り捨て
 * 
 * @param float $val 対象値
 * @param int $pos 小数点以下の桁数
 * @return float
 */
function myRoundDown($val, $pos) {
    $aa = pow(10, $pos);
    
    if ($val == 0 || is_null($val)) {
        return 0;
    }
    
    if ($val > 0) {
        return floor(strval($val * $aa)) / $aa;
    } else {
        return ceil(strval($val * $aa)) / $aa;
    }
}

/**
 * 切り上げ
 * 
 * @param float $val 対象値
 * @param int $pos 小数点以下の桁数
 * @return float
 */
function myRoundUp($val, $pos) {
    $aa = pow(10, $pos);
    
    if ($val == 0 || is_null($val)) {
        return 0;
    }
    
    if ($val > 0) {
        return ceil(strval($val * $aa)) / $aa;
    } else {
        return floor(strval($val * $aa)) / $aa;
    }
}

/**
 * 数値フォーマット（カンマ区切り）
 * 
 * @param mixed $value 数値
 * @param int $decimals 小数点以下の桁数
 * @return string
 */
function formatNumber($value, $decimals = 0) {
    if ($value === null || $value === '') {
        return '';
    }
    return number_format($value, $decimals);
}

//---------------------------------------------------
// 文字コード変換関数
//---------------------------------------------------

/**
 * 全角カタカナを半角カタカナに変換
 * 
 * @param string $str 対象文字列
 * @return string
 */
function zenkakuToHankaku($str) {
    if ($str === null) {
        return '';
    }
    return mb_convert_kana($str, 'k', 'UTF-8');
}

/**
 * 半角カタカナを全角カタカナに変換
 * 
 * @param string $str 対象文字列
 * @return string
 */
function hankakuToZenkaku($str) {
    if ($str === null) {
        return '';
    }
    return mb_convert_kana($str, 'K', 'UTF-8');
}

/**
 * カナ検索用 拗音の変換
 * 
 * @param string $str 対象文字列
 * @return string
 */
function kana_convert($str) {
    if ($str === null) {
        return '';
    }
    $pattern = array('ァ', 'ィ', 'ゥ', 'ェ', 'ォ', 'ヵ', 'ッ', 'ャ', 'ュ', 'ョ', 'ヮ', 'ヶ');
    $replacement = array('ア', 'イ', 'ウ', 'エ', 'オ', 'カ', 'ツ', 'ヤ', 'ユ', 'ヨ', 'ワ', 'ケ');
    return str_replace($pattern, $replacement, $str);
}

//---------------------------------------------------
// Ajax応答関数
//---------------------------------------------------

/**
 * JSON形式でAjax応答を返す
 * 
 * @param string $status ステータス（success/error）
 * @param mixed $data データ
 * @param string $message メッセージ
 */
function jsonResponse($status, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array(
        'status' => $status,
        'message' => $message
    );
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 成功応答を返す
 * 
 * @param mixed $data データ
 * @param string $message メッセージ
 */
function jsonSuccess($data = null, $message = '') {
    jsonResponse('success', $data, $message);
}

/**
 * エラー応答を返す
 * 
 * @param string $message エラーメッセージ
 * @param string $code エラーコード
 * @param array $errors フィールドエラー
 */
function jsonError($message, $code = '', $errors = array()) {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array(
        'status' => 'error',
        'message' => $message
    );
    
    if (!empty($code)) {
        $response['code'] = $code;
    }
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

//---------------------------------------------------
// 入力サニタイズ関数
//---------------------------------------------------

/**
 * 入力値をサニタイズ
 * 
 * @param mixed $value 入力値
 * @param string $type 型（string/int/float/email/date/datetime）
 * @return mixed サニタイズ済みの値
 */
function sanitizeInput($value, $type = 'string') {
    if ($value === null) {
        return null;
    }
    
    switch ($type) {
        case 'int':
            return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : null;
        
        case 'float':
            return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : null;
        
        case 'email':
            return filter_var($value, FILTER_VALIDATE_EMAIL) ?: null;
        
        case 'date':
            $date = DateTime::createFromFormat('Y-m-d', $value);
            return ($date && $date->format('Y-m-d') === $value) ? $value : null;
        
        case 'datetime':
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
            return ($date && $date->format('Y-m-d H:i:s') === $value) ? $value : null;
        
        case 'string':
        default:
            // 制御文字を除去
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            return trim($value);
    }
}

//---------------------------------------------------
// Ajax判定関数
//---------------------------------------------------

/**
 * Ajaxリクエストかどうかを判定
 * 
 * @return bool
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

//---------------------------------------------------
// ページング関数
//---------------------------------------------------

/**
 * ページングパラメータを計算
 * 
 * @param int $total 総件数
 * @param int $page 現在ページ
 * @param int $limit 1ページあたりの件数
 * @return array ページング情報
 */
function calculatePaging($total, $page, $limit) {
    $total_pages = ceil($total / $limit);
    $page = max(1, min($page, $total_pages));
    $offset = ($page - 1) * $limit;
    
    return array(
        'total' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    );
}

/**
 * ページリンクを生成
 * 
 * @param int $current_page 現在ページ
 * @param int $total_pages 総ページ数
 * @param int $range 表示するページリンクの範囲
 * @return array ページ番号の配列
 */
function generatePageLinks($current_page, $total_pages, $range = 5) {
    $pages = array();
    
    if ($total_pages <= 0) {
        return $pages;
    }
    
    $start = max(1, $current_page - floor($range / 2));
    $end = min($total_pages, $start + $range - 1);
    
    if ($end - $start < $range - 1) {
        $start = max(1, $end - $range + 1);
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }
    
    return $pages;
}
