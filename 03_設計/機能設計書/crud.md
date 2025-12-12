# CRUD機能設計書

**作成日**: 2025-11-25  
**更新日**: 2025-12-08  
**バージョン**: 1.1  
**Phase**: 03  
**機能ID**: crud  
**関連ファイル**: support_ajax02.php, support_ajax03.php

---

## 1. 概要

### 1.1 機能の目的
サポート報告書データの登録・取得・更新・削除（CRUD）を行う。

### 1.2 機能範囲
- 新規登録（Create）
- 単一データ取得（Read）
- データ更新（Update）
- データ削除（Delete）

---

## 2. 機能一覧

| No | 機能名 | 機能ID | 対象ファイル |
|----|-------|--------|------------|
| 1 | 新規登録 | crud_create | support_ajax02.php |
| 2 | データ取得 | crud_read | support_ajax02.php |
| 3 | データ更新 | crud_update | support_ajax02.php |
| 4 | データ削除 | crud_delete | support_ajax03.php |

---

## 3. 新規登録（crud_create）

### 3.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│入力データ取得        │
│(POSTパラメータ)     │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│入力値サニタイズ      │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│バリデーション        │
│・必須チェック        │
│・形式チェック        │
│・存在チェック        │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│SEQNO採番            │
│COUNTUP_SYS_SEQNO   │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│登録処理             │
│INS_D_作業報告       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  成功      失敗
    │         │
    ↓         ↓
成功JSON返却 エラーJSON返却
    │
   END
```

### 3.2 SEQNO採番

```sql
-- SYS_SEQNOテーブルから採番
EXEC COUNTUP_SYS_SEQNO
-- 戻り値: 新しいSEQNO
```

### 3.3 登録SQL

```sql
-- INS_D_作業報告
INSERT INTO D_作業報告 (
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
    -- ... 対応内容フラグ3〜19
    対応内容フラグ20,
    入力日時,
    更新日時,
    入力マシン
) VALUES (
    @SEQNO,
    @taiou_start,
    @taiou_end,
    @kokyaku_code,
    @kokyaku_tanto,
    @bumon_code,
    @shohin_code1,
    @shohin_code2,
    @shohin_code3,
    @tantou_code,
    @kubun_code,
    @hikitsugi_code,
    @houkoku_naiyo,
    @flag1,
    @flag2,
    -- ... @flag3〜19
    @flag20,
    GETDATE(),
    GETDATE(),
    @machine_name
)
```

---

## 4. データ取得（crud_read）

### 4.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│SEQNO取得            │
│(POSTパラメータ)     │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│バリデーション        │
│・必須チェック        │
│・数値チェック        │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│データ取得           │
│SEL_D_作業報告_単一  │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  取得成功  取得失敗
    │         │
    ↓         ↓
┌─────────┐  エラー返却
│権限チェック│  「データなし」
└────┬────┘
     │
  ┌──┴──┐
  │      │
 OK     NG
  │      │
  ↓      ↓
成功返却 エラー返却
  │     「権限なし」
 END
```

### 4.2 取得SQL

```sql
-- SEL_D_作業報告_単一
SELECT 
    D.SEQNO,
    D.対応開始日時,
    D.対応終了日時,
    D.顧客コード,
    D.顧客担当者名,
    D.部門コード,
    D.商品コード1,
    D.商品コード2,
    D.商品コード3,
    D.作業担当コード,
    D.対応区分コード,
    D.引継担当コード,
    D.報告内容,
    D.対応内容フラグ1,
    D.対応内容フラグ2,
    -- ... 対応内容フラグ3〜20
    D.対応内容フラグ20,
    D.入力日時,
    D.更新日時,
    D.入力マシン,
    C.顧客名,
    T.作業担当名,
    B.部門名
FROM 
    D_作業報告 D
    LEFT JOIN SQL_顧客 C ON D.顧客コード = C.SEQNO  -- ※重要: SQL_顧客.SEQNOと結合
    LEFT JOIN SQL_作業担当 T ON D.作業担当コード = T.担当者コード
    LEFT JOIN SQL_部門 B ON D.部門コード = B.部門コード
WHERE 
    D.SEQNO = @seqno
    AND D.削除日時 IS NULL
```

### 4.3 権限チェック

```php
// 一般ユーザーは自分のデータのみ取得可能
if ($_SESSION['AUTH_LEVEL'] < 2) {
    if ($row['作業担当コード'] != $_SESSION['USER_CODE']) {
        return array(
            'status' => 'error',
            'message' => 'このデータを閲覧する権限がありません。'
        );
    }
}
```

---

## 5. データ更新（crud_update）

### 5.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│入力データ取得        │
│(POSTパラメータ)     │
│SEQNOを含む         │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│入力値サニタイズ      │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│バリデーション        │
│・必須チェック        │
│・形式チェック        │
│・存在チェック        │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│既存データ取得        │
│（存在確認・権限確認）│
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│更新処理             │
│UPD_D_作業報告       │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  成功      失敗
    │         │
    ↓         ↓
成功JSON返却 エラーJSON返却
    │
   END
```

### 5.2 更新SQL

```sql
-- UPD_D_作業報告
UPDATE D_作業報告
SET
    対応開始日時 = @taiou_start,
    対応終了日時 = @taiou_end,
    顧客コード = @kokyaku_code,
    顧客担当者名 = @kokyaku_tanto,
    部門コード = @bumon_code,
    商品コード1 = @shohin_code1,
    商品コード2 = @shohin_code2,
    商品コード3 = @shohin_code3,
    作業担当コード = @tantou_code,
    対応区分コード = @kubun_code,
    引継担当コード = @hikitsugi_code,
    報告内容 = @houkoku_naiyo,
    対応内容フラグ1 = @flag1,
    対応内容フラグ2 = @flag2,
    -- ... 対応内容フラグ3〜19
    対応内容フラグ20 = @flag20,
    更新日時 = GETDATE(),
    入力マシン = @machine_name
WHERE
    SEQNO = @seqno
    AND 削除日時 IS NULL
```

---

## 6. データ削除（crud_delete）

### 6.1 処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│SEQNO取得            │
│(POSTパラメータ)     │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│バリデーション        │
│・必須チェック        │
│・数値チェック        │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│既存データ取得        │
│（存在確認・権限確認）│
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  OK        NG
    │         │
    ↓         ↓
  継続     エラー返却
    │
    ↓
┌─────────────────────┐
│論理削除処理          │
│DEL_D_作業報告       │
│（削除日時をセット）  │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  成功      失敗
    │         │
    ↓         ↓
成功JSON返却 エラーJSON返却
    │
   END
```

### 6.2 削除SQL

```sql
-- DEL_D_作業報告（論理削除）
UPDATE D_作業報告
SET
    削除日時 = GETDATE(),
    更新日時 = GETDATE(),
    入力マシン = @machine_name
WHERE
    SEQNO = @seqno
    AND 削除日時 IS NULL
```

---

## 7. バリデーション詳細

### 7.1 新規登録・更新時のバリデーション

| No | 項目 | チェック内容 | エラーメッセージ |
|----|------|-------------|-----------------|
| 1 | 顧客コード | 必須 | 顧客コードを入力してください。 |
| 2 | 顧客コード | 数値 | 顧客コードは数字で入力してください。 |
| 3 | 顧客コード | DB存在 | 顧客コードが存在しません。 |
| 4 | 部門 | 必須 | 部門を選択してください。 |
| 5 | 部門 | DB存在 | 部門コードが存在しません。 |
| 6 | 対応日 | 必須 | 対応日を入力してください。 |
| 7 | 対応日 | 日付形式 | 対応日の形式が正しくありません。 |
| 8 | 開始時刻 | 必須 | 開始時刻を入力してください。 |
| 9 | 開始時刻 | 時刻形式 | 開始時刻の形式が正しくありません。 |
| 10 | 終了時刻 | 開始≦終了 | 終了時刻は開始時刻以降を指定してください。 |
| 11 | 商品 | DB存在 | 商品コードが存在しません。 |
| 12 | 商品 | 重複 | 同じ商品が複数選択されています。 |
| 13 | 作業担当 | 必須 | 作業担当を選択してください。 |
| 14 | 作業担当 | DB存在 | 担当者コードが存在しません。 |
| 15 | 対応区分 | DB存在 | 対応区分コードが存在しません。 |
| 16 | 報告内容 | 必須 | 報告内容を入力してください。 |
| 17 | 引継担当 | DB存在 | 引継担当者コードが存在しません。 |

### 7.2 バリデーション実装

```php
/**
 * 入力データバリデーション
 * 
 * @param array $data 入力データ
 * @return array バリデーション結果
 */
function validateInputData($data) {
    global $pdo_conn;
    $errors = array();
    
    // 顧客コード：必須・数値
    if (empty($data['kokyaku_code'])) {
        $errors[] = '顧客コードを入力してください。';
    } elseif (!is_numeric($data['kokyaku_code'])) {
        $errors[] = '顧客コードは数字で入力してください。';
    } else {
        // DB存在チェック
        if (!existsKokyaku($data['kokyaku_code'])) {
            $errors[] = '顧客コードが存在しません。';
        }
    }
    
    // 部門：必須・存在
    if (empty($data['bumon_code'])) {
        $errors[] = '部門を選択してください。';
    } elseif (!existsBumon($data['bumon_code'])) {
        $errors[] = '部門コードが存在しません。';
    }
    
    // 対応日：必須・形式
    if (empty($data['taiou_date'])) {
        $errors[] = '対応日を入力してください。';
    } elseif (!isValidDate($data['taiou_date'])) {
        $errors[] = '対応日の形式が正しくありません。';
    }
    
    // 開始時刻：必須・形式
    if (empty($data['taiou_time_start'])) {
        $errors[] = '開始時刻を入力してください。';
    } elseif (!isValidTime($data['taiou_time_start'])) {
        $errors[] = '開始時刻の形式が正しくありません。';
    }
    
    // 終了時刻：開始≦終了
    if (!empty($data['taiou_time_end'])) {
        if (!isValidTime($data['taiou_time_end'])) {
            $errors[] = '終了時刻の形式が正しくありません。';
        } elseif ($data['taiou_time_start'] > $data['taiou_time_end']) {
            $errors[] = '終了時刻は開始時刻以降を指定してください。';
        }
    }
    
    // 商品：存在・重複
    $shohin_codes = array();
    for ($i = 1; $i <= 3; $i++) {
        $code = $data['shohin_code' . $i];
        if (!empty($code)) {
            if (!existsShohin($code)) {
                $errors[] = '商品コードが存在しません。';
            }
            if (in_array($code, $shohin_codes)) {
                $errors[] = '同じ商品が複数選択されています。';
            }
            $shohin_codes[] = $code;
        }
    }
    
    // 作業担当：必須・存在
    if (empty($data['tantou_code'])) {
        $errors[] = '作業担当を選択してください。';
    } elseif (!existsTantou($data['tantou_code'])) {
        $errors[] = '担当者コードが存在しません。';
    }
    
    // 対応区分：存在
    if (!empty($data['kubun_code']) && !existsKubun($data['kubun_code'])) {
        $errors[] = '対応区分コードが存在しません。';
    }
    
    // 報告内容：必須
    if (empty($data['houkoku_naiyo'])) {
        $errors[] = '報告内容を入力してください。';
    }
    
    // 引継担当：存在
    if (!empty($data['hikitsugi_code']) && !existsTantou($data['hikitsugi_code'])) {
        $errors[] = '引継担当者コードが存在しません。';
    }
    
    return $errors;
}
```

---

## 8. Ajax通信仕様

### 8.1 新規登録・更新リクエスト（support_ajax02.php）

**URL**: `support_ajax02.php`  
**Method**: POST

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'save' |
| mode | string | ○ | 'new' or 'edit' |
| seqno | int | △ | 編集時のみ必須 |
| kokyaku_code | int | ○ | 顧客コード |
| bumon_code | int | ○ | 部門コード |
| kokyaku_tanto | string | - | 顧客担当者名 |
| taiou_date | string | ○ | 対応日（YYYY-MM-DD） |
| taiou_time_start | string | ○ | 開始時刻（HH:MM） |
| taiou_time_end | string | - | 終了時刻（HH:MM） |
| shohin_code1 | int | - | 商品コード1 |
| shohin_code2 | int | - | 商品コード2 |
| shohin_code3 | int | - | 商品コード3 |
| tantou_code | int | ○ | 作業担当コード |
| kubun_code | int | - | 対応区分コード |
| taiou_flag[] | int[] | - | 対応内容フラグ配列 |
| houkoku_naiyo | string | ○ | 報告内容 |
| hikitsugi_code | int | - | 引継担当コード |

### 8.2 データ取得リクエスト（support_ajax02.php）

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'get' |
| seqno | int | ○ | SEQNO |

### 8.3 削除リクエスト（support_ajax03.php）

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'delete' |
| seqno | int | ○ | SEQNO |

### 8.4 レスポンス形式

**成功時（保存）**:

```json
{
    "status": "success",
    "message": "保存しました。",
    "seqno": 12345
}
```

**成功時（取得）**:

```json
{
    "status": "success",
    "data": {
        "seqno": 12345,
        "kokyaku_code": 1001,
        "kokyaku_name": "株式会社○○○○",
        "bumon_code": 1,
        "kokyaku_tanto": "山田様",
        "taiou_date": "2025-11-25",
        "taiou_time_start": "10:30",
        "taiou_time_end": "11:00",
        "shohin_code1": 1,
        "shohin_code2": 2,
        "shohin_code3": null,
        "tantou_code": 101,
        "kubun_code": 1,
        "taiou_flags": [1, 3, 5],
        "houkoku_naiyo": "お客様より...",
        "hikitsugi_code": null,
        "input_date": "2025-11-25 10:35:00",
        "update_date": "2025-11-25 10:35:00"
    }
}
```

**成功時（削除）**:

```json
{
    "status": "success",
    "message": "削除しました。"
}
```

**エラー時**:

```json
{
    "status": "error",
    "message": "保存に失敗しました。",
    "errors": [
        "顧客コードを入力してください。",
        "報告内容を入力してください。"
    ]
}
```

---

## 9. 権限制御

### 9.1 新規登録

- 全ユーザーが実行可能
- 作業担当は一般ユーザーの場合、自分のコードのみ設定可能

### 9.2 データ取得

- 一般ユーザー：自分のデータのみ取得可能
- 管理者：全データ取得可能

### 9.3 データ更新

- 一般ユーザー：自分のデータのみ更新可能
- 管理者：全データ更新可能

### 9.4 データ削除

- 一般ユーザー：自分のデータのみ削除可能
- 管理者：全データ削除可能

---

## 10. データ整形処理

### 10.1 日時データの変換

```php
/**
 * 日付と時刻からdatetimeを生成
 */
function makeDatetime($date, $time) {
    if (empty($time)) {
        $time = '00:00';
    }
    return $date . ' ' . $time . ':00';
}

/**
 * datetimeから日付を抽出
 */
function extractDate($datetime) {
    return date('Y-m-d', strtotime($datetime));
}

/**
 * datetimeから時刻を抽出
 */
function extractTime($datetime) {
    return date('H:i', strtotime($datetime));
}
```

### 10.2 対応内容フラグの変換

```php
/**
 * フラグ配列を個別フラグに展開
 */
function expandTaiouFlags($flags) {
    $result = array();
    for ($i = 1; $i <= 20; $i++) {
        $result['flag' . $i] = (is_array($flags) && in_array($i, $flags)) ? 1 : 0;
    }
    return $result;
}

/**
 * 個別フラグをフラグ配列に集約
 */
function collectTaiouFlags($row) {
    $flags = array();
    for ($i = 1; $i <= 20; $i++) {
        if ($row['対応内容フラグ' . $i] == 1) {
            $flags[] = $i;
        }
    }
    return $flags;
}
```

---

## 11. エラーハンドリング

### 11.1 エラーコード

| コード | メッセージ |
|--------|-----------|
| CRUD_001 | データの登録に失敗しました。 |
| CRUD_002 | データの取得に失敗しました。 |
| CRUD_003 | データの更新に失敗しました。 |
| CRUD_004 | データの削除に失敗しました。 |
| CRUD_005 | 指定されたデータが見つかりません。 |
| CRUD_006 | このデータを操作する権限がありません。 |
| CRUD_010 | データベースエラーが発生しました。 |

### 11.2 例外処理

```php
try {
    // DB操作
    $stmt = $pdo_conn->prepare($sql);
    $stmt->execute($params);
    // ...
} catch (PDOException $e) {
    // エラーログ出力
    error_log('CRUD Error: ' . $e->getMessage());
    
    return array(
        'status' => 'error',
        'message' => 'データベースエラーが発生しました。'
    );
}
```

---

## 12. 備考

### 12.1 既存システムとの互換性

- SEQNOの採番はSYS_SEQNOテーブルを使用（既存ロジックを継承）
- 削除は論理削除（削除日時をセット）
- 対応内容フラグは1〜20の20カラムで保存

### 12.2 テスト観点

- 新規登録：全必須項目入力、保存成功
- 新規登録：バリデーションエラー各パターン
- データ取得：正常取得
- データ取得：存在しないSEQNO
- データ取得：権限外データ
- データ更新：既存データの変更
- データ更新：権限外データの更新拒否
- データ削除：正常削除
- データ削除：削除確認ダイアログ
- データ削除：権限外データの削除拒否

---

**作成者**: Claude AI  
**レビュー**: Phase 03完了時  
**改訂履歴**:
- v1.0 (2025-11-25): 初版作成
- v1.1 (2025-12-08): SQL_顧客とのJOINキーを修正（顧客コード→SEQNO）
