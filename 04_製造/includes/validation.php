<?php
/**
 * ファイル名: validation.php
 * 機能概要: バリデーションモジュール
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 05）
 * 2025-11-26 メソッドのオーバーロード対応（Phase 09）
 */

//---------------------------------------------------
// バリデーションクラス
//---------------------------------------------------

/**
 * バリデーションクラス
 * 
 * 使用方法：
 * 1. コンストラクタでデータを渡す方式
 *    $validator = new Validator($_POST);
 *    $validator->required('user_code', 'ユーザーコード')
 *              ->maxLength('user_code', 'ユーザーコード', 10);
 * 
 * 2. 直接値を渡す方式
 *    $validator = new Validator();
 *    $validator->required('user_code', $user_code, 'ユーザーコード')
 *              ->maxLength('user_code', $user_code, 10, 'ユーザーコード');
 */
class Validator {
    private $errors = array();
    private $messages = array();
    private $data = array();
    
    /**
     * コンストラクタ
     * 
     * @param array $data バリデーション対象データ（オプション）
     */
    public function __construct($data = array()) {
        $this->data = $data;
    }
    
    /**
     * 必須チェック
     * 
     * オーバーロード対応:
     * required($field, $label) - $dataから値を取得
     * required($field, $value, $label) - 直接値を受け取る
     * 
     * @return Validator
     */
    public function required($field, $arg2, $arg3 = null) {
        // 引数の数で呼び出し方式を判定
        if ($arg3 !== null) {
            // 3引数: required($field, $value, $label)
            $value = $arg2;
            $label = $arg3;
        } else {
            // 2引数: required($field, $label) - $dataから値を取得
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            $label = $arg2;
        }
        
        if ($value === null || trim((string)$value) === '') {
            $message = "{$label}を入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 最大文字数チェック
     * 
     * オーバーロード対応:
     * maxLength($field, $label, $max) - $dataから値を取得
     * maxLength($field, $value, $max, $label) - 直接値を受け取る
     * 
     * @return Validator
     */
    public function maxLength($field, $arg2, $arg3, $arg4 = null) {
        // 引数の数で呼び出し方式を判定
        if ($arg4 !== null) {
            // 4引数: maxLength($field, $value, $max, $label)
            $value = $arg2;
            $max = (int)$arg3;
            $label = $arg4;
        } else {
            // 3引数: maxLength($field, $label, $max) - $dataから値を取得
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            $label = $arg2;
            $max = (int)$arg3;
        }
        
        if ($value !== null && $value !== '' && mb_strlen((string)$value, 'UTF-8') > $max) {
            $message = "{$label}は{$max}文字以内で入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 最小文字数チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @param int $min 最小文字数
     * @return Validator
     */
    public function minLength($field, $label, $min) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && mb_strlen((string)$value, 'UTF-8') < $min) {
            $message = "{$label}は{$min}文字以上で入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 数値チェック
     * 
     * オーバーロード対応:
     * numeric($field, $label) - $dataから値を取得
     * numeric($field, $value, $label) - 直接値を受け取る
     * 
     * @return Validator
     */
    public function numeric($field, $arg2, $arg3 = null) {
        // 引数の数で呼び出し方式を判定
        if ($arg3 !== null) {
            // 3引数: numeric($field, $value, $label)
            $value = $arg2;
            $label = $arg3;
        } else {
            // 2引数: numeric($field, $label) - $dataから値を取得
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            $label = $arg2;
        }
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $message = "{$label}は数値で入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 整数チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @return Validator
     */
    public function integer($field, $label) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $message = "{$label}は整数で入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 範囲チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @param mixed $min 最小値
     * @param mixed $max 最大値
     * @return Validator
     */
    public function range($field, $label, $min, $max) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && ($value < $min || $value > $max)) {
            $message = "{$label}は{$min}から{$max}の範囲で入力してください。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 日付形式チェック
     * 
     * オーバーロード対応:
     * date($field, $label) - $dataから値を取得
     * date($field, $value, $label) - 直接値を受け取る
     * 
     * @return bool バリデーション結果
     */
    public function date($field, $arg2, $arg3 = null) {
        // 引数の数で呼び出し方式を判定
        if ($arg3 !== null) {
            // 3引数: date($field, $value, $label)
            $value = $arg2;
            $label = $arg3;
        } else {
            // 2引数: date($field, $label) - $dataから値を取得
            $value = isset($this->data[$field]) ? $this->data[$field] : null;
            $label = $arg2;
        }
        
        if ($value !== null && $value !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                $message = "{$label}の日付形式が正しくありません。";
                $this->errors[$field] = $message;
                $this->messages[] = $message;
                return false;
            }
        }
        return true;
    }
    
    /**
     * 日時形式チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @return Validator
     */
    public function datetime($field, $label) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '') {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if (!$date || $date->format('Y-m-d H:i:s') !== $value) {
                $message = "{$label}の日時形式が正しくありません。";
                $this->errors[$field] = $message;
                $this->messages[] = $message;
            }
        }
        return $this;
    }
    
    /**
     * 日付範囲チェック
     * 
     * @param string $from_field 開始日フィールド名
     * @param string $to_field 終了日フィールド名
     * @return Validator
     */
    public function dateRange($from_field, $to_field) {
        $from_value = isset($this->data[$from_field]) ? $this->data[$from_field] : null;
        $to_value = isset($this->data[$to_field]) ? $this->data[$to_field] : null;
        
        if ($from_value !== null && $to_value !== null && 
            $from_value !== '' && $to_value !== '') {
            if ($from_value > $to_value) {
                $message = "終了日は開始日以降の日付を指定してください。";
                $this->errors[$from_field] = $message;
                $this->messages[] = $message;
            }
        }
        return $this;
    }
    
    /**
     * メールアドレス形式チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @return Validator
     */
    public function email($field, $label) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $message = "{$label}の形式が正しくありません。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 正規表現チェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @param string $pattern 正規表現パターン
     * @return Validator
     */
    public function regex($field, $label, $pattern) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $message = "{$label}の形式が正しくありません。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * 許可リストチェック
     * 
     * @param string $field フィールド名
     * @param string $label 表示名
     * @param array $allowed 許可リスト
     * @return Validator
     */
    public function inList($field, $label, $allowed) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        
        if ($value !== null && $value !== '' && !in_array($value, $allowed)) {
            $message = "{$label}の値が不正です。";
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * カスタムバリデーション
     * 
     * @param string $field フィールド名
     * @param callable $callback バリデーション関数
     * @param string $message エラーメッセージ
     * @return Validator
     */
    public function custom($field, $callback, $message) {
        $value = isset($this->data[$field]) ? $this->data[$field] : null;
        if (!$callback($value, $this->data)) {
            $this->errors[$field] = $message;
            $this->messages[] = $message;
        }
        return $this;
    }
    
    /**
     * エラーを追加
     * 
     * @param string $field フィールド名
     * @param string $message エラーメッセージ
     * @return Validator
     */
    public function addError($field, $message) {
        $this->errors[$field] = $message;
        $this->messages[] = $message;
        return $this;
    }
    
    /**
     * エラー有無チェック
     * 
     * @return bool
     */
    public function hasErrors() {
        return count($this->errors) > 0;
    }
    
    /**
     * エラー取得（フィールドごと）
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * エラーメッセージを配列で取得（フィールド名なし）
     * 
     * @return array
     */
    public function getMessages() {
        return $this->messages;
    }
    
    /**
     * 特定フィールドのエラー取得
     * 
     * @param string $field フィールド名
     * @return string|null
     */
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }
    
    /**
     * エラークリア
     * 
     * @return Validator
     */
    public function clearErrors() {
        $this->errors = array();
        $this->messages = array();
        return $this;
    }
    
    /**
     * バリデーション済みデータを取得
     * 
     * @return array
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * 特定フィールドの値を取得
     * 
     * @param string $field フィールド名
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public function getValue($field, $default = null) {
        return isset($this->data[$field]) ? $this->data[$field] : $default;
    }
}

//---------------------------------------------------
// 単体バリデーション関数
//---------------------------------------------------

/**
 * 必須チェック
 * 
 * @param mixed $value 値
 * @param string $field_name フィールド名
 * @return string|null エラーメッセージ
 */
function validateRequired($value, $field_name) {
    if ($value === null || trim((string)$value) === '') {
        return "{$field_name}を入力してください。";
    }
    return null;
}

/**
 * 最大文字数チェック
 * 
 * @param mixed $value 値
 * @param int $max 最大文字数
 * @param string $field_name フィールド名
 * @return string|null エラーメッセージ
 */
function validateMaxLength($value, $max, $field_name) {
    if ($value !== null && $value !== '' && mb_strlen((string)$value, 'UTF-8') > $max) {
        return "{$field_name}は{$max}文字以内で入力してください。";
    }
    return null;
}

/**
 * 数値チェック
 * 
 * @param mixed $value 値
 * @param string $field_name フィールド名
 * @return string|null エラーメッセージ
 */
function validateNumeric($value, $field_name) {
    if ($value !== null && $value !== '' && !is_numeric($value)) {
        return "{$field_name}は数値で入力してください。";
    }
    return null;
}

/**
 * 日付形式チェック
 * 
 * @param mixed $value 値
 * @param string $field_name フィールド名
 * @return string|null エラーメッセージ
 */
function validateDate($value, $field_name) {
    if ($value !== null && $value !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return "{$field_name}の日付形式が正しくありません。";
        }
    }
    return null;
}

/**
 * 範囲チェック
 * 
 * @param mixed $value 値
 * @param mixed $min 最小値
 * @param mixed $max 最大値
 * @param string $field_name フィールド名
 * @return string|null エラーメッセージ
 */
function validateRange($value, $min, $max, $field_name) {
    if ($value !== null && $value !== '' && ($value < $min || $value > $max)) {
        return "{$field_name}は{$min}から{$max}の範囲で入力してください。";
    }
    return null;
}
