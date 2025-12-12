# サポート報告書WEBシステム 内部API設計書

**作成日**: 2025-11-25  
**バージョン**: 1.0  
**Phase**: 03

---

## 1. 概要

本資料は、サポート報告書WEBシステムで使用するAjax通信のAPI仕様を定義したものです。

---

## 2. API一覧

| No | API名 | エンドポイント | メソッド | 説明 |
|----|-------|--------------|---------|------|
| 1 | 報告書検索 | support_ajax01.php | POST | 報告書一覧検索 |
| 2 | 報告書取得 | support_ajax02.php | POST | 単一データ取得 |
| 3 | 報告書保存 | support_ajax02.php | POST | 新規登録・更新 |
| 4 | 報告書削除 | support_ajax03.php | POST | 論理削除 |
| 5 | 顧客情報取得 | support_ajax_kokyaku.php | POST | 顧客名取得 |
| 6 | マスタ取得 | support_ajax_master.php | POST | 各種マスタ取得 |
| 7 | マスタ一覧 | master_ajax.php | POST | マスタ一覧取得 |
| 8 | マスタ保存 | master_ajax.php | POST | マスタCRUD |
| 9 | マスタ削除 | master_ajax.php | POST | マスタ削除 |

---

## 3. 共通仕様

### 3.1 リクエスト形式

- **Content-Type**: application/x-www-form-urlencoded
- **Method**: POST
- **文字コード**: UTF-8

### 3.2 レスポンス形式

- **Content-Type**: application/json; charset=utf-8
- **文字コード**: UTF-8

### 3.3 共通レスポンス構造

**成功時**:
```json
{
    "status": "success",
    "data": { ... },
    "message": "処理が完了しました。"
}
```

**エラー時**:
```json
{
    "status": "error",
    "message": "エラーが発生しました。",
    "errors": [
        "エラーメッセージ1",
        "エラーメッセージ2"
    ]
}
```

### 3.4 認証

全てのAPIはセッション認証が必要です。未認証の場合は以下のレスポンスを返します。

```json
{
    "status": "error",
    "message": "セッションが切れました。再度ログインしてください。",
    "redirect": "login.php"
}
```

---

## 4. 報告書検索API

### 4.1 エンドポイント

**URL**: `support_ajax01.php`  
**Method**: POST

### 4.2 リクエストパラメータ

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|-----------|-----|------|-----------|------|
| action | string | ○ | - | 'search' |
| tantou_code | int | - | - | 作業担当コード |
| kokyaku_name | string | - | - | 顧客名（部分一致） |
| shohin_code | int | - | - | 商品コード |
| kubun_code | int | - | - | 対応区分コード |
| taiou_date_from | string | - | - | 対応日開始（YYYY-MM-DD） |
| taiou_date_to | string | - | - | 対応日終了（YYYY-MM-DD） |
| keyword | string | - | - | キーワード検索 |
| taiou_flag[] | int[] | - | - | 対応内容フラグ（1〜20） |
| page | int | - | 1 | ページ番号 |
| limit | int | - | 50 | 表示件数（25/50/100） |
| sort_column | string | - | taiou_date | ソートカラム |
| sort_order | string | - | desc | ソート順（asc/desc） |

### 4.3 レスポンス

```json
{
    "status": "success",
    "data": [
        {
            "seqno": 12345,
            "taiou_start": "2025-11-25 10:30:00",
            "taiou_end": "2025-11-25 11:00:00",
            "kokyaku_code": 1001,
            "kokyaku_name": "株式会社○○○○",
            "kokyaku_tanto": "山田様",
            "bumon_code": 1,
            "bumon_name": "LPG支援部",
            "shohin_code1": 1,
            "shohin_name1": "SILPS",
            "shohin_code2": 2,
            "shohin_name2": "ちゃんぷる～EJ",
            "shohin_code3": null,
            "shohin_name3": null,
            "tantou_code": 101,
            "tantou_name": "鈴木一郎",
            "kubun_code": 1,
            "kubun_name": "問い合わせ",
            "hikitsugi_code": null,
            "hikitsugi_name": null,
            "houkoku": "お客様より○○の問い合わせがありました...",
            "taiou_flags": [1, 3, 5],
            "update_date": "2025-11-25 10:35:00"
        }
    ],
    "pagination": {
        "total_count": 1234,
        "total_pages": 25,
        "current_page": 1,
        "limit": 50,
        "has_prev": false,
        "has_next": true
    }
}
```

---

## 5. 報告書取得API

### 5.1 エンドポイント

**URL**: `support_ajax02.php`  
**Method**: POST

### 5.2 リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get' |
| seqno | int | ○ | SEQNO |

### 5.3 レスポンス

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
        "houkoku_naiyo": "お客様より○○の問い合わせがありました。\n対応完了。",
        "hikitsugi_code": null,
        "input_date": "2025-11-25 10:35:00",
        "update_date": "2025-11-25 10:35:00"
    }
}
```

---

## 6. 報告書保存API

### 6.1 エンドポイント

**URL**: `support_ajax02.php`  
**Method**: POST

### 6.2 リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
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

### 6.3 レスポンス

```json
{
    "status": "success",
    "message": "保存しました。",
    "seqno": 12345
}
```

---

## 7. 報告書削除API

### 7.1 エンドポイント

**URL**: `support_ajax03.php`  
**Method**: POST

### 7.2 リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'delete' |
| seqno | int | ○ | SEQNO |

### 7.3 レスポンス

```json
{
    "status": "success",
    "message": "削除しました。"
}
```

---

## 8. 顧客情報取得API

### 8.1 エンドポイント

**URL**: `support_ajax_kokyaku.php`  
**Method**: POST

### 8.2 リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_kokyaku' |
| kokyaku_code | int | ○ | 顧客コード |

### 8.3 レスポンス

```json
{
    "status": "success",
    "data": {
        "kokyaku_code": 1001,
        "kokyaku_name": "株式会社○○○○",
        "address": "東京都千代田区..."
    }
}
```

---

## 9. マスタ取得API（入力画面用）

### 9.1 エンドポイント

**URL**: `support_ajax_master.php`  
**Method**: POST

### 9.2 担当者リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_tantou_list' |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { "code": 101, "name": "鈴木一郎" },
        { "code": 102, "name": "田中花子" }
    ]
}
```

### 9.3 商品リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_shohin_list' |
| bumon_code | int | ○ | 部門コード |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { "code": 1, "name": "SILPS" },
        { "code": 2, "name": "ちゃんぷる～EJ" }
    ]
}
```

### 9.4 対応区分リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_kubun_list' |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { "code": 1, "name": "問い合わせ" },
        { "code": 2, "name": "要望" }
    ]
}
```

### 9.5 対応内容項目リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_taiou_list' |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { "code": 1, "name": "操作説明" },
        { "code": 2, "name": "データ更新" },
        { "code": 3, "name": "データ調査" }
    ]
}
```

### 9.6 定型文リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_teikei_list' |
| bumon_code | int | ○ | 部門コード |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { 
            "code": 1, 
            "text": "お客様へお問い合わせいたしました。\n折り返しご連絡をお待ちしております。"
        },
        { 
            "code": 2, 
            "text": "データ更新が完了いたしました。\nご確認をお願いいたします。"
        }
    ]
}
```

### 9.7 部門リスト取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'get_bumon_list' |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        { "code": 1, "name": "LPG支援部" },
        { "code": 2, "name": "開発部" }
    ]
}
```

---

## 10. マスタ保守API

### 10.1 エンドポイント

**URL**: `master_ajax.php`  
**Method**: POST

### 10.2 マスタ一覧取得

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'list' |
| master_type | string | ○ | 'product'/'template'/'category'/'content' |
| filter_bumon | int | - | 部門フィルタ（商品・定型文） |
| filter_use | int | - | 使用区分フィルタ（商品） |

**レスポンス**:

```json
{
    "status": "success",
    "data": [
        {
            "code": 1,
            "name": "SILPS",
            "bumon_code": 1,
            "bumon_name": "LPG支援部",
            "use_flag": 1,
            "update_date": "2025/11/25 10:30"
        }
    ]
}
```

### 10.3 マスタ保存

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'save' |
| master_type | string | ○ | マスタ種別 |
| mode | string | ○ | 'new' or 'edit' |
| code | int | △ | 編集時必須 |
| bumon_code | int | △ | 商品・定型文の場合 |
| name | string | ○ | 名称 |
| use_flag | int | △ | 商品の場合 |
| text | string | △ | 定型文の場合 |

**レスポンス**:

```json
{
    "status": "success",
    "message": "保存しました。",
    "code": 5
}
```

### 10.4 マスタ削除

**リクエスト**:

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| action | string | ○ | 'delete' |
| master_type | string | ○ | マスタ種別 |
| code | int | ○ | 削除対象コード |
| bumon_code | int | △ | 商品・定型文の場合 |

**レスポンス**:

```json
{
    "status": "success",
    "message": "削除しました。"
}
```

---

## 11. エラーコード一覧

| コード | メッセージ | 発生条件 |
|--------|-----------|---------|
| AUTH_001 | セッションが切れました。 | 未認証アクセス |
| AUTH_002 | 権限がありません。 | 権限不足 |
| VALID_001 | 必須項目が入力されていません。 | 必須チェックエラー |
| VALID_002 | 入力形式が正しくありません。 | 形式チェックエラー |
| VALID_003 | データが存在しません。 | 存在チェックエラー |
| DB_001 | データベースエラーが発生しました。 | DB操作エラー |
| DB_002 | データの保存に失敗しました。 | 保存エラー |
| DB_003 | データの削除に失敗しました。 | 削除エラー |
| SYS_001 | 不正なリクエストです。 | actionパラメータ不正 |

---

## 12. 備考

### 12.1 セキュリティ

- 全APIでセッション認証必須
- 入力値は全てサニタイズ処理
- SQLはプリペアドステートメント使用

### 12.2 文字コード

- リクエスト・レスポンス共にUTF-8
- 日本語カラム名にも対応

### 12.3 日付形式

- 日付: YYYY-MM-DD
- 時刻: HH:MM
- 日時: YYYY-MM-DD HH:MM:SS

---

**作成者**: Claude AI  
**レビュー**: Phase 03完了時  
**改訂履歴**:
- v1.0 (2025-11-25): 初版作成
