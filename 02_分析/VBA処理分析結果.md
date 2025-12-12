# VBA処理分析結果

## 文書情報
- **作成日**: 2025-11-20
- **Phase**: Phase 02 - 要件分析
- **目的**: 既存AccessシステムのVBA処理を分析し、PHP化の方針を策定する

---

## 目次
1. [VBAモジュール構成](#1-vbaモジュール構成)
2. [主要処理の分類](#2-主要処理の分類)
3. [詳細分析](#3-詳細分析)
4. [PHP化方針](#4-php化方針)
5. [実装優先度](#5-実装優先度)

---

## 1. VBAモジュール構成

### 1.1 標準モジュール（14個）

| No | モジュール名 | 役割 | 重要度 |
|----|------------|------|--------|
| 1 | $標準モジュール | 文字列処理、日付処理などの汎用関数 | ★★★ |
| 2 | $標準ライブラリ | フォーム操作、ログ出力、マスタ取得 | ★★★ |
| 3 | $テーブル操作 | DAO操作の共通関数 | ★★★ |
| 4 | $グローバル定義 | グローバル変数定義 | ★★ |
| 5 | $スタートアップ処理 | アプリケーション起動処理 | ★★★ |
| 6 | SQL_顧客 | 顧客情報のSQL Server連携 | ★★★ |
| 7 | SQL_作業担当 | 担当者情報のSQL Server連携 | ★★★ |
| 8 | SQL_部門 | 部門情報のSQL Server連携 | ★★★ |
| 9 | $Getログイン情報 | ログイン情報取得 | ★★ |
| 10 | $マスタ取込み処理 | マスタデータ取込 | ★★ |
| 11 | $作業報告データ取込処理 | 作業報告データ取込 | ★★ |
| 12 | $サーバー同期 | サーバーとの同期処理 | ★ |
| 13 | $ネットワーク環境チェック | ネットワーク状態確認 | ★ |
| 14 | $SVRファイルコピー | サーバーファイル操作 | ★ |

### 1.2 クラスモジュール（3個）

| No | モジュール名 | 役割 | 重要度 |
|----|------------|------|--------|
| 1 | clsEnviron | 環境変数管理クラス | ★★★ |
| 2 | cls処理中 | 処理中表示クラス | ★★ |
| 3 | @API / @Display_API | Windows API定義 | ★ |

---

## 2. 主要処理の分類

### 2.1 文字列処理系

#### $標準モジュール.module

**主要関数**:
```vba
' 文字列から指定文字を削除
Public Function CutAny(vStr As Variant, vAny As String) As String

' カナ変換処理（半角→全角、濁音・半濁音の正規化）
Function KANAConv(ByVal vStr As String) As String

' 文字列内の全空白を削除
Function ATrim(ByVal vStr As String) As String

' バイト数取得
Public Function GetByte(v As Variant) As Byte
```

**PHP化方針**:
- `CutAny()` → `str_replace()` で実装
- `KANAConv()` → `mb_convert_kana()` で実装
- `ATrim()` → `preg_replace('/\s+/', '', $str)` で実装
- `GetByte()` → `strlen()` で実装

---

### 2.2 日付処理系

#### $標準モジュール.module

**主要関数**:
```vba
' 月初日取得
Public Function Get月初(v基準日 As Date, Optional v前月 As Long = 0) As Date

' 月末日取得
Public Function Get月末(v基準日 As Date, Optional v前月 As Long = 0) As Date

' 締日を考慮した年月取得
Public Function Get締日年月(v日付 As Date, v締日 As Long) As Long
```

**PHP化方針**:
- `Get月初()` → `date('Y-m-01', strtotime(...))` で実装
- `Get月末()` → `date('Y-m-t', strtotime(...))` で実装
- `Get締日年月()` → カスタム関数として実装

---

### 2.3 データベース操作系

#### $テーブル操作.module

**主要関数**:
```vba
' プライマリキーでレコード検索
Public Function ReadPK(vRS As Recordset, vKey1, ...) As Boolean

' プライマリキーでレコード削除
Public Function DelPK(vTableName As String, vKey1, ...) As Boolean

' プライマリキーでレコードセット取得
Public Function RSReadPK(vRS As Recordset, vTableName, vKey1, ...) As Boolean

' フィールド値取得
Public Function GetField(vTableName, vFieldName, vKey1, ...) As Variant

' DAOテーブルオープン
Public Function OpenDAOTable(vTableName As String) As DAO.Recordset

' レコード間コピー
Public Sub KRecToRec(rs1, rs2)
```

**PHP化方針**:
- **DAO操作は全てPDO + ストアドプロシージャに置き換え**
- ReadPK系 → WHERE条件のSELECTクエリまたはストアドプロシージャ
- DelPK → DELETEクエリまたはストアドプロシージャ
- GetField → SELECT文で単一値取得
- KRecToRec → 不要（PHPでは配列操作で対応）

---

### 2.4 フォーム操作系

#### $標準ライブラリ.module

**主要関数**:
```vba
' フォームの全コントロールをクリア
Public Sub AllClearCtrl(vFormName As String)

' フォームが開いているか確認
Public Function KIsLoad(FormName As String) As Boolean

' フォーム表示
Public Function KShowForm(vFormName As String)

' フォーム切り替え（現在のフォームを閉じて新しいフォームを開く）
Public Function KSwitchForm(vFormName, Optional vArg, Optional vWindowMode)

' フォームを開く
Public Function OpenForm(vFormName, Optional vArg, Optional vWindowMode)

' 現在のフォームを閉じる
Public Function KByeBye()
```

**PHP化方針**:
- **フォーム操作は全てJavaScriptに置き換え**
- AllClearCtrl → jQueryで `$('input, select, textarea').val('')`
- KIsLoad → 不要（WEB画面では概念が異なる）
- KShowForm/KSwitchForm → ページ遷移またはAjax表示
- OpenForm → `window.location.href` または Ajax
- KByeBye → `window.close()` または前画面へ戻る

---

### 2.5 ログ処理系

#### $標準ライブラリ.module

**主要関数**:
```vba
' ログ出力
Public Function PutLog(Optional vProcess, Optional vResult)

' 古いログ削除（10万件以前を削除）
Public Function DelOldLog()
```

**PHP化方針**:
- カスタムログクラスを作成
- ログファイル出力（`error_log()` または独自実装）
- ログローテーション機能を実装
- テーブル `$LOG` は廃止し、ファイルベースのログに変更

---

### 2.6 SQL Server連携系

#### SQL_顧客.module

**主要関数**:
```vba
' 顧客コードで1件取得
Public Function V_顧客取得_顧客コード(v顧客コード As Long) As ADODB.Recordset

' 顧客マスタ更新（SQL Server → Access）
Public Sub V_SQL顧客更新()
```

**データソース**:
- サーバー: CEWEB
- データベース: CeCrm
- テーブル: t_customer

**取得項目**:
- seq（SEQNO）
- parent_seq
- cust_main_code（顧客コード）
- cust_name（顧客名）
- cust_kana（顧客名カナ）
- cust_ryaku（顧客略称）
- post_code（郵便番号）
- address01 + address02（住所）
- tel01（電話番号）
- fax
- update_datetime（更新日時）

#### SQL_作業担当.module

**主要関数**:
```vba
' 担当者コードで1件取得
Public Function V_担当者取得_担当者コード(v担当者コード As Long) As ADODB.Recordset

' 担当者マスタ更新（SQL Server → Access）
Public Sub V_SQL作業担当更新()
```

**データソース**:
- サーバー: CEWEB
- データベース: CeCrm
- テーブル: m_staff

**取得項目**:
- staff_code（担当者コード）
- org_seq（部門コード）
- staff_name（担当者名）
- update_datetime（更新日時）

#### SQL_部門.module

**主要関数**:
```vba
' 部門コードで1件取得
Public Function V_部門取得_部門コード(v部門コード As Long) As ADODB.Recordset

' 部門マスタ更新（SQL Server → Access）
Public Sub V_SQL部門更新()
```

**データソース**:
- サーバー: CEWEB
- データベース: CeCrm
- テーブル: m_org
- 取得条件: seq IN (10, 16, 20)

**取得項目**:
- seq（部門コード）
- org_name（部門名）
- update_datetime（更新日時）

**PHP化方針**:
- **全てストアドプロシージャまたはPHPのPDO処理に置き換え**
- V_顧客取得系 → SELECT文またはストアドプロシージャ
- V_SQL*更新系 → **WEB版では不要**（SQL Serverから直接取得）
- Accessのローカルテーブル（SQL_顧客/SQL_作業担当/SQL_部門）は廃止
- SQL Serverのマスタテーブルを直接参照する設計に変更

---

### 2.7 マスタ取得・更新系

#### $標準ライブラリ.module

**主要関数**:
```vba
' マスタ取得（SQL Server → Access）
Public Function V_マスタ取得(vテーブル名 As String, vローカル名 As String)

' システム管理情報更新
Public Function V_SYS管理更新(v入力マシン, vフィールド名, v値)
```

**処理内容**:
- SQL Serverのマスタテーブルをローカル（Access）に同期
- ストアドプロシージャ `GET_TBL` を呼び出し
- レコードを1件ずつローカルテーブルに挿入・更新

**PHP化方針**:
- **WEB版では同期処理は不要**
- SQL Serverのマスタテーブルを直接参照
- キャッシュが必要な場合のみセッションまたはRedisを使用

---

### 2.8 スタートアップ処理系

#### $スタートアップ処理.module

**主要関数**:
```vba
Public Function StartUP()
```

**処理内容**:
1. ナビゲーションウィンドウ非表示
2. リボン非表示
3. ネットワーク接続チェック
4. システム管理情報更新
5. サーバー同期
6. DB バックアップ
7. システム更新
8. VBE参照設定追加
9. 一時モジュール削除
10. SQL Server接続（CEWEB、SUPPORTDB）
11. マスタ取得
12. 作業報告データ取得
13. ログイン画面表示（未ログインの場合）
14. メイン画面表示

**PHP化方針**:
- **ログイン処理をセッション管理に置き換え**
- ネットワークチェック → 不要（WEBなので常時接続前提）
- サーバー同期/バックアップ → サーバー側で別途実装
- マスタ取得 → 初回アクセス時にセッションにキャッシュ
- SQL Server接続 → `inc.php`で接続プールを使用

---

## 3. 詳細分析

### 3.1 Access固有の処理

#### 3.1.1 DAO操作
- **概要**: AccessのDAOを使用したローカルテーブル操作
- **影響範囲**: $テーブル操作.module全体
- **PHP化**: PDO + SQL Serverの直接操作に置き換え

#### 3.1.2 フォーム操作
- **概要**: Accessフォームの開閉・切り替え・コントロール操作
- **影響範囲**: $標準ライブラリ.module
- **PHP化**: JavaScript/jQueryによるDOM操作に置き換え

#### 3.1.3 VBEモジュール操作
- **概要**: VBAモジュールの動的追加・削除
- **影響範囲**: AddVBExtensibilityReference、DeleteAllTempModules
- **PHP化**: 不要（WEB版では使用しない）

---

### 3.2 SQL Server連携処理

#### 3.2.1 接続管理
- **現状**: ADODBを使用した接続
- **グローバル変数**: `xCE_CON`（CEWEB）、`xSUP_CON`（SUPPORTDB）
- **PHP化**: PDO接続を `inc.php` で一元管理

#### 3.2.2 ストアドプロシージャ呼び出し
- **現状**: ADODBのCommandオブジェクトで実行
- **例**: 
  ```vba
  SPCMD.CommandText = "GET_TBL"
  SPCMD.Parameters("@テーブル名") = "M_商品"
  Set RSADO = SPCMD.Execute
  ```
- **PHP化**: PDOのprepareとexecuteで実行

---

### 3.3 データ同期処理

#### 3.3.1 同期対象
1. **SQL_顧客**: SQL Server (CeCrm.t_customer) → Access
2. **SQL_作業担当**: SQL Server (CeCrm.m_staff) → Access
3. **SQL_部門**: SQL Server (CeCrm.m_org) → Access
4. **M_商品**: SQL Server (SUPPORTDB) → Access
5. **M_定型文**: SQL Server (SUPPORTDB) → Access
6. **M_対応区分**: SQL Server (SUPPORTDB) → Access
7. **M_対応内容項目**: SQL Server (SUPPORTDB) → Access

#### 3.3.2 同期タイミング
- **起動時**: 全マスタを更新日時でチェックして同期
- **定期**: 不明（コード上は起動時のみ）

#### 3.3.3 PHP化での変更点
- **ローカルテーブルを廃止**
- **SQL Serverから直接取得**
- **必要に応じてセッションキャッシュ**

---

## 4. PHP化方針

### 4.1 共通関数ライブラリ（myFunction.php）

#### 4.1.1 文字列処理関数
```php
/**
 * 指定文字を削除
 */
function cutAny($str, $any) {
    return str_replace($any, '', $str);
}

/**
 * カナ変換（半角→全角）
 */
function kanaConv($str) {
    return mb_convert_kana($str, 'KVCAS', 'UTF-8');
}

/**
 * 全空白削除
 */
function aTrim($str) {
    return preg_replace('/\s+/', '', $str);
}

/**
 * バイト数取得
 */
function getByte($str) {
    return strlen($str);
}
```

#### 4.1.2 日付処理関数
```php
/**
 * 月初日取得
 */
function getMonthStart($date, $prevMonth = 0) {
    $d = new DateTime($date);
    $d->modify("$prevMonth month");
    return $d->format('Y-m-01');
}

/**
 * 月末日取得
 */
function getMonthEnd($date, $prevMonth = 0) {
    $d = new DateTime($date);
    $d->modify("$prevMonth month");
    return $d->format('Y-m-t');
}

/**
 * 締日年月取得
 */
function getClosingYearMonth($date, $closingDay) {
    $d = new DateTime($date);
    $day = (int)$d->format('d');
    
    if ($day > $closingDay) {
        $d->modify('+1 month');
    }
    
    return $d->format('Ym');
}
```

#### 4.1.3 VBA互換関数
```php
/**
 * Null値を指定値に変換（Access Nz関数の代替）
 */
function Nz($value, $default = '') {
    return is_null($value) || $value === '' ? $default : $value;
}

/**
 * 空文字チェック（参考システムから）
 */
function Ez($value) {
    return empty($value);
}
```

---

### 4.2 データベース操作クラス（db_support.php）

#### 4.2.1 基本構造
```php
class SupportDB {
    private $pdo;
    
    public function __construct() {
        // inc.phpの接続を使用
        global $pdo_support;
        $this->pdo = $pdo_support;
    }
    
    /**
     * ストアドプロシージャ実行
     */
    public function executeStoredProc($procName, $params = []) {
        $placeholders = implode(', ', array_fill(0, count($params), '?'));
        $sql = "EXEC $procName $placeholders";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($params));
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * プライマリキーでレコード取得
     */
    public function findByPK($table, $keys) {
        // 実装
    }
    
    /**
     * プライマリキーでレコード削除
     */
    public function deleteByPK($table, $keys) {
        // 実装
    }
}
```

---

### 4.3 ログ処理クラス（logger.php）

```php
class Logger {
    private $logFile;
    
    public function __construct($logFile = 'logs/system.log') {
        $this->logFile = $logFile;
    }
    
    public function putLog($process, $result = '') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $process: $result\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
```

---

### 4.4 セッション管理（session_manager.php）

```php
class SessionManager {
    /**
     * ログイン処理
     */
    public function login($staffCode, $orgCode) {
        $_SESSION['staff_code'] = $staffCode;
        $_SESSION['org_code'] = $orgCode;
        $_SESSION['login_time'] = time();
    }
    
    /**
     * ログアウト処理
     */
    public function logout() {
        session_destroy();
    }
    
    /**
     * ログインチェック
     */
    public function isLoggedIn() {
        return isset($_SESSION['staff_code']);
    }
    
    /**
     * セッションタイムアウトチェック（9時間）
     */
    public function checkTimeout() {
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > 32400) { // 9時間 = 32400秒
                $this->logout();
                return false;
            }
        }
        return true;
    }
}
```

---

## 5. 実装優先度

### 5.1 最優先（Phase 09で実装）
| No | 機能 | VBA元 | PHP実装先 |
|----|------|--------|-----------|
| 1 | データベース接続 | $グローバル定義 | inc.php |
| 2 | セッション管理 | $スタートアップ処理 | session_manager.php |
| 3 | Nz/Ez関数 | 参考システム | myFunction.php |
| 4 | 文字列処理（cutAny, aTrim等） | $標準モジュール | myFunction.php |
| 5 | 日付処理（月初/月末等） | $標準モジュール | myFunction.php |

### 5.2 高優先度（Phase 10-11で実装）
| No | 機能 | VBA元 | PHP実装先 |
|----|------|--------|-----------|
| 6 | ストアドプロシージャ実行 | SQL_顧客等 | db_support.php |
| 7 | ログ出力 | $標準ライブラリ | logger.php |
| 8 | フォーム操作（JavaScript） | $標準ライブラリ | common.js |

### 5.3 中優先度（Phase 11以降で実装）
| No | 機能 | VBA元 | PHP実装先 |
|----|------|--------|-----------|
| 9 | カナ変換 | $標準モジュール | myFunction.php |
| 10 | バイト数取得 | $標準モジュール | myFunction.php |
| 11 | エラーハンドリング | 各所 | 各PHPファイル |

### 5.4 低優先度（必要に応じて実装）
| No | 機能 | VBA元 | 備考 |
|----|------|--------|------|
| 12 | VBEモジュール操作 | $標準ライブラリ | WEB版では不要 |
| 13 | サーバー同期 | $サーバー同期 | 別途サーバー側で実装 |
| 14 | ネットワークチェック | $ネットワーク環境チェック | WEB版では不要 |

---

## 6. 実装時の注意事項

### 6.1 文字コード
- VBA: Shift_JIS（Accessのデフォルト）
- PHP: UTF-8（BOMなし）
- **変換が必要な場合**: `mb_convert_encoding()`を使用

### 6.2 データ型
| VBA | PHP |
|-----|-----|
| Long | int |
| String | string |
| Date | DateTime (string) |
| Boolean | bool |
| Variant | mixed |
| Null | null |

### 6.3 配列のインデックス
- VBA: 0始まりまたは1始まり（Option Base）
- PHP: 0始まり（固定）

### 6.4 エラー処理
- VBA: `On Error GoTo ERR` / `On Error Resume Next`
- PHP: `try-catch` 構文を使用

---

## 7. まとめ

### 7.1 PHP化の全体方針
1. **Access固有の処理を削除**: DAO操作、フォーム操作、VBEモジュール操作
2. **SQL Serverから直接取得**: ローカルテーブル（SQL_*）を廃止
3. **セッション管理**: ログイン状態、マスタキャッシュ
4. **共通関数ライブラリ**: VBA関数をPHPで再実装
5. **ストアドプロシージャ活用**: 既存SPを継続使用

### 7.2 削除する処理
- Accessローカルテーブルへの同期処理
- VBEモジュール操作
- ネットワーク接続チェック（WEB版では不要）
- フォーム切り替え処理（ページ遷移に変更）

### 7.3 新規追加する処理
- セッション管理（ログイン、タイムアウト）
- Ajax通信処理
- JavaScriptによるフォーム制御
- RESTful APIエンドポイント

---

## 付録A: VBA→PHP対応表

| VBA関数/構文 | PHP対応 |
|------------|---------|
| `Nz(value, default)` | `is_null($value) ? $default : $value` |
| `IsNull(value)` | `is_null($value)` |
| `Len(str)` | `mb_strlen($str)` |
| `Mid(str, start, length)` | `mb_substr($str, $start-1, $length)` |
| `InStr(str, search)` | `mb_strpos($str, $search)` |
| `Trim(str)` | `trim($str)` |
| `Format(date, "yyyy/mm/dd")` | `date('Y/m/d', strtotime($date))` |
| `DateAdd("M", n, date)` | `date('Y-m-d', strtotime("+$n month", strtotime($date)))` |
| `Year(date)` | `date('Y', strtotime($date))` |
| `Month(date)` | `date('m', strtotime($date))` |
| `Day(date)` | `date('d', strtotime($date))` |
| `Now()` | `date('Y-m-d H:i:s')` |
| `CLng(value)` | `(int)$value` |
| `CStr(value)` | `(string)$value` |
| `CBool(value)` | `(bool)$value` |

---

**作成者**: Claude AI  
**最終更新**: 2025-11-20
