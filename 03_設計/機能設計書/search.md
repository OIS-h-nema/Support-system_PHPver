# 検索機能設計書

**作成日**: 2025-11-25  
**更新日**: 2025-12-08  
**バージョン**: 1.1  
**Phase**: 03  
**機能ID**: search  
**関連ファイル**: support_main.php, support_ajax01.php

---

## 1. 概要

### 1.1 機能の目的
サポート報告書一覧を様々な条件で絞り込み検索し、目的のデータを素早く見つけられるようにする。

### 1.2 機能範囲
- 複合条件検索
- キーワード検索（あいまい検索）
- 対応内容チェックボックスによる検索
- 検索結果のソート
- ページネーション

---

## 2. 検索条件

### 2.1 検索項目一覧

| No | 項目名 | 検索ID | 検索方法 | データ型 |
|----|-------|--------|---------|---------|
| 1 | 作業担当 | tantou_code | 完全一致 | int |
| 2 | 顧客名 | kokyaku_name | 部分一致 | string |
| 3 | 商品 | shohin_code | 完全一致 | int |
| 4 | 対応区分 | kubun_code | 完全一致 | int |
| 5 | 対応日（開始） | taiou_date_from | 以上 | date |
| 6 | 対応日（終了） | taiou_date_to | 以下 | date |
| 7 | キーワード | keyword | 部分一致（複数項目） | string |
| 8 | 対応内容1〜20 | taiou_flag[] | フラグON検索 | int[] |

### 2.2 キーワード検索対象カラム

キーワードは以下のカラムを対象に部分一致検索を行う：

- 報告内容
- 顧客名
- 顧客担当者名

---

## 3. 処理フロー

### 3.1 検索処理フロー

```
START
  │
  ↓
┌─────────────────────┐
│フォームから検索条件取得│
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│検索条件サニタイズ     │
│htmlspecialchars()   │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│検索条件バリデーション │
│・日付形式チェック     │
│・数値形式チェック     │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   OK        NG
    │         │
    ↓         ↓
  継続     エラーJSON返却
    │
    ↓
┌─────────────────────┐
│検索SQL組み立て        │
│・WHERE句構築         │
│・ORDER BY句構築      │
│・OFFSET-FETCH句構築  │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│ストアドプロシージャ   │
│実行                  │
│SEL_D_作業報告_検索   │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│結果をJSON形式で返却   │
│・データ配列          │
│・総件数              │
│・ページ情報          │
└─────────────────────┘
         │
        END
```

### 3.2 ページネーション処理

```
START
  │
  ↓
┌─────────────────────┐
│パラメータ取得         │
│・page（現在ページ）   │
│・limit（表示件数）    │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│OFFSET計算            │
│offset = (page-1)*limit│
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│総件数取得            │
│COUNT(*) WHERE条件   │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│データ取得            │
│OFFSET offset        │
│FETCH NEXT limit     │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│ページ情報計算         │
│・総ページ数          │
│・前ページ有無        │
│・次ページ有無        │
└─────────────────────┘
         │
        END
```

---

## 4. SQL設計

### 4.1 基本検索SQL

```sql
-- SEL_D_作業報告_検索
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
    D.更新日時,
    T.作業担当名,
    S1.商品名 AS 商品名1,
    S2.商品名 AS 商品名2,
    S3.商品名 AS 商品名3,
    K.対応区分名,
    C.顧客名
FROM 
    D_作業報告 D
    LEFT JOIN SQL_作業担当 T ON D.作業担当コード = T.担当者コード
    LEFT JOIN M_商品 S1 ON D.商品コード1 = S1.商品コード
    LEFT JOIN M_商品 S2 ON D.商品コード2 = S2.商品コード
    LEFT JOIN M_商品 S3 ON D.商品コード3 = S3.商品コード
    LEFT JOIN M_対応区分 K ON D.対応区分コード = K.対応区分コード
    LEFT JOIN SQL_顧客 C ON D.顧客コード = C.SEQNO  -- ※重要: SQL_顧客.SEQNOと結合
WHERE 
    D.削除日時 IS NULL
    -- 動的条件が追加される
ORDER BY 
    D.対応開始日時 DESC
OFFSET @offset ROWS
FETCH NEXT @limit ROWS ONLY
```

### 4.2 動的WHERE句構築

```php
// PHP側でのWHERE句構築
$where_conditions = array("D.削除日時 IS NULL");
$params = array();

// 作業担当
if (!empty($tantou_code)) {
    $where_conditions[] = "D.作業担当コード = :tantou_code";
    $params[':tantou_code'] = array('value' => $tantou_code, 'type' => PDO::PARAM_INT);
}

// 顧客名（部分一致）
if (!empty($kokyaku_name)) {
    $where_conditions[] = "C.顧客名 LIKE :kokyaku_name";
    $params[':kokyaku_name'] = array('value' => '%' . $kokyaku_name . '%', 'type' => PDO::PARAM_STR);
}

// 商品（3カラムいずれかに一致）
if (!empty($shohin_code)) {
    $where_conditions[] = "(D.商品コード1 = :shohin_code OR D.商品コード2 = :shohin_code OR D.商品コード3 = :shohin_code)";
    $params[':shohin_code'] = array('value' => $shohin_code, 'type' => PDO::PARAM_INT);
}

// 対応区分
if (!empty($kubun_code)) {
    $where_conditions[] = "D.対応区分コード = :kubun_code";
    $params[':kubun_code'] = array('value' => $kubun_code, 'type' => PDO::PARAM_INT);
}

// 対応日（開始）
if (!empty($taiou_date_from)) {
    $where_conditions[] = "CAST(D.対応開始日時 AS DATE) >= :taiou_date_from";
    $params[':taiou_date_from'] = array('value' => $taiou_date_from, 'type' => PDO::PARAM_STR);
}

// 対応日（終了）
if (!empty($taiou_date_to)) {
    $where_conditions[] = "CAST(D.対応開始日時 AS DATE) <= :taiou_date_to";
    $params[':taiou_date_to'] = array('value' => $taiou_date_to, 'type' => PDO::PARAM_STR);
}

// キーワード（複数カラム対象）
if (!empty($keyword)) {
    $keyword_escaped = '%' . $keyword . '%';
    $where_conditions[] = "(D.報告内容 LIKE :keyword1 OR C.顧客名 LIKE :keyword2 OR D.顧客担当者名 LIKE :keyword3)";
    $params[':keyword1'] = array('value' => $keyword_escaped, 'type' => PDO::PARAM_STR);
    $params[':keyword2'] = array('value' => $keyword_escaped, 'type' => PDO::PARAM_STR);
    $params[':keyword3'] = array('value' => $keyword_escaped, 'type' => PDO::PARAM_STR);
}

// 対応内容フラグ（配列）
if (!empty($taiou_flags) && is_array($taiou_flags)) {
    foreach ($taiou_flags as $flag_num) {
        $flag_num = intval($flag_num);
        if ($flag_num >= 1 && $flag_num <= 20) {
            $where_conditions[] = "D.対応内容フラグ" . $flag_num . " = 1";
        }
    }
}

// WHERE句組み立て
$where_clause = implode(" AND ", $where_conditions);
```

### 4.3 件数取得SQL

```sql
-- 件数取得
SELECT COUNT(*) AS total_count
FROM 
    D_作業報告 D
    LEFT JOIN SQL_顧客 C ON D.顧客コード = C.SEQNO  -- ※重要: SQL_顧客.SEQNOと結合
WHERE 
    -- 動的条件（データ取得と同じ条件）
```

---

## 5. Ajax通信仕様

### 5.1 リクエスト（support_ajax01.php）

**URL**: `support_ajax01.php`  
**Method**: POST  
**Content-Type**: application/x-www-form-urlencoded

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'search' |
| tantou_code | int | - | 作業担当コード |
| kokyaku_name | string | - | 顧客名（部分一致） |
| shohin_code | int | - | 商品コード |
| kubun_code | int | - | 対応区分コード |
| taiou_date_from | string | - | 対応日開始（YYYY-MM-DD） |
| taiou_date_to | string | - | 対応日終了（YYYY-MM-DD） |
| keyword | string | - | キーワード |
| taiou_flag[] | int[] | - | 対応内容フラグ（1〜20） |
| page | int | - | ページ番号（デフォルト: 1） |
| limit | int | - | 表示件数（デフォルト: 50） |
| sort_column | string | - | ソートカラム |
| sort_order | string | - | ソート順（asc/desc） |

### 5.2 レスポンス（成功時）

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
            "kokyaku_tanto": "山田太郎",
            "bumon_code": 1,
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
            "houkoku": "お客様より○○の問い合わせがありました...",
            "taiou_flags": [1, 3, 5],
            "update_date": "2025-11-25 10:35:00"
        },
        // ... 続くデータ
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

### 5.3 レスポンス（エラー時）

```json
{
    "status": "error",
    "message": "検索条件にエラーがあります。",
    "errors": [
        "対応日（開始）の形式が正しくありません。"
    ]
}
```

---

## 6. ソート機能

### 6.1 ソート可能カラム

| カラム | ソートID | デフォルト順 |
|--------|---------|------------|
| 対応日時 | taiou_date | DESC |
| 担当者 | tantou_name | ASC |
| 商品 | shohin_name | ASC |
| 顧客名 | kokyaku_name | ASC |
| 更新日時 | update_date | DESC |

### 6.2 ソート切り替え

```javascript
// カラムヘッダークリック時のソート切り替え
function toggleSort(column) {
    if (currentSortColumn === column) {
        // 同じカラム：順序を切り替え
        currentSortOrder = (currentSortOrder === 'asc') ? 'desc' : 'asc';
    } else {
        // 違うカラム：そのカラムの昇順
        currentSortColumn = column;
        currentSortOrder = 'asc';
    }
    
    // 検索実行
    doSearch();
}
```

---

## 7. 検索初期値

### 7.1 画面初期表示時の検索条件

| 項目 | 初期値 | 備考 |
|------|--------|------|
| 作業担当 | ログインユーザー | 一般ユーザーのみ |
| 対応日（開始） | 当月1日 | - |
| 対応日（終了） | 当日 | - |
| その他 | 空欄 | - |
| ソート | 対応日時 DESC | - |
| ページ | 1 | - |
| 表示件数 | 50 | - |

### 7.2 初期値設定コード

```javascript
// 検索条件初期値設定
function setDefaultSearchConditions() {
    var today = new Date();
    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    // 日付をYYYY-MM-DD形式に変換
    var todayStr = formatDate(today);
    var firstDayStr = formatDate(firstDay);
    
    $('#taiou-date-from').val(firstDayStr);
    $('#taiou-date-to').val(todayStr);
    
    // 一般ユーザーは自分のデータのみ（権限レベルで制御）
    if (authLevel < 2) {
        $('#tantou-code').val(userCode);
    }
}
```

---

## 8. 権限による検索制御

### 8.1 一般ユーザー（AUTH_LEVEL = 1）

- 作業担当は自分のみ選択可能
- 検索結果も自分のデータのみ表示
- サーバー側でも強制的にフィルタ

### 8.2 管理者（AUTH_LEVEL >= 2）

- 全ての担当者を選択可能
- 全てのデータを検索・表示可能

### 8.3 権限制御コード（サーバー側）

```php
// 一般ユーザーは強制的に自分のデータのみ
if ($_SESSION['AUTH_LEVEL'] < 2) {
    $tantou_code = $_SESSION['USER_CODE'];
    $where_conditions[] = "D.作業担当コード = :tantou_code";
    $params[':tantou_code'] = array('value' => $tantou_code, 'type' => PDO::PARAM_INT);
}
```

---

## 9. パフォーマンス考慮

### 9.1 インデックス推奨

```sql
-- 検索パフォーマンス向上のためのインデックス
CREATE INDEX IX_D_作業報告_対応開始日時 ON D_作業報告(対応開始日時);
CREATE INDEX IX_D_作業報告_作業担当コード ON D_作業報告(作業担当コード);
CREATE INDEX IX_D_作業報告_顧客コード ON D_作業報告(顧客コード);
CREATE INDEX IX_D_作業報告_削除日時 ON D_作業報告(削除日時);
```

### 9.2 検索制限

- 1回の検索で取得する最大件数: 100件
- 検索結果が多い場合はページネーションで分割表示
- キーワード検索は最低2文字以上

---

## 10. エラーハンドリング

### 10.1 バリデーションエラー

| コード | メッセージ |
|--------|-----------|
| SEARCH_001 | 対応日（開始）の形式が正しくありません。 |
| SEARCH_002 | 対応日（終了）の形式が正しくありません。 |
| SEARCH_003 | 対応日（終了）は対応日（開始）以降を指定してください。 |
| SEARCH_004 | キーワードは2文字以上で入力してください。 |

### 10.2 システムエラー

| コード | メッセージ |
|--------|-----------|
| SEARCH_010 | データベースエラーが発生しました。 |
| SEARCH_011 | 検索処理でエラーが発生しました。 |

---

## 11. JavaScript実装

### 11.1 検索実行

```javascript
/**
 * 検索実行
 */
function doSearch() {
    // 検索条件収集
    var searchParams = collectSearchParams();
    
    // ローディング表示
    showLoading();
    
    $.ajax({
        type: 'POST',
        url: 'support_ajax01.php',
        data: searchParams,
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.status === 'success') {
                renderList(response.data);
                renderPagination(response.pagination);
            } else {
                showErrors(response.errors);
            }
        },
        error: function() {
            hideLoading();
            showError('通信エラーが発生しました。');
        }
    });
}

/**
 * 検索条件収集
 */
function collectSearchParams() {
    var params = {
        action: 'search',
        tantou_code: $('#tantou-code').val(),
        kokyaku_name: $('#kokyaku-name').val(),
        shohin_code: $('#shohin-code').val(),
        kubun_code: $('#kubun-code').val(),
        taiou_date_from: $('#taiou-date-from').val(),
        taiou_date_to: $('#taiou-date-to').val(),
        keyword: $('#keyword').val(),
        page: currentPage,
        limit: currentLimit,
        sort_column: currentSortColumn,
        sort_order: currentSortOrder
    };
    
    // 対応内容フラグ
    params['taiou_flag[]'] = [];
    $('input[name="taiou_flag[]"]:checked').each(function() {
        params['taiou_flag[]'].push($(this).val());
    });
    
    return params;
}
```

### 11.2 一覧描画

```javascript
/**
 * 一覧描画
 */
function renderList(data) {
    var $tbody = $('#list-table tbody');
    $tbody.empty();
    
    if (data.length === 0) {
        $tbody.append('<tr><td colspan="7" class="no-data">データがありません</td></tr>');
        return;
    }
    
    $.each(data, function(i, row) {
        var $tr = $('<tr>');
        $tr.data('seqno', row.seqno);
        
        $tr.append($('<td>').text(row.tantou_name));
        $tr.append($('<td>').text(row.shohin_name1));
        $tr.append($('<td>').text(formatDateTime(row.taiou_start)));
        $tr.append($('<td>').text(formatTaiouFlags(row.taiou_flags)));
        $tr.append($('<td>').text(row.kokyaku_name));
        $tr.append($('<td>').text(truncate(row.houkoku, 50)));
        $tr.append($('<td>').html(
            '<button class="btn-edit" onclick="openEdit(' + row.seqno + ')">修正</button>'
        ));
        
        $tbody.append($tr);
    });
}

/**
 * ページネーション描画
 */
function renderPagination(pagination) {
    var $paging = $('#paging-area');
    $paging.empty();
    
    // 最初
    if (pagination.has_prev) {
        $paging.append('<button onclick="goPage(1)">≪</button>');
        $paging.append('<button onclick="goPage(' + (pagination.current_page - 1) + ')">＜</button>');
    }
    
    // ページ情報
    $paging.append('<span>' + pagination.current_page + ' / ' + pagination.total_pages + 'ページ</span>');
    $paging.append('<span>（全' + pagination.total_count + '件）</span>');
    
    // 最後
    if (pagination.has_next) {
        $paging.append('<button onclick="goPage(' + (pagination.current_page + 1) + ')">＞</button>');
        $paging.append('<button onclick="goPage(' + pagination.total_pages + ')">≫</button>');
    }
}
```

---

## 12. 備考

### 12.1 既存システムとの違い

| 項目 | Accessシステム | WEBシステム |
|------|--------------|------------|
| 検索方式 | フィルタ | Ajax検索 |
| 表示更新 | フォーム再描画 | DOM更新 |
| ページング | Access標準 | OFFSET-FETCH |

### 12.2 テスト観点

- 単一条件検索：各条件での検索成功
- 複合条件検索：複数条件の組み合わせ
- キーワード検索：部分一致検索
- 対応内容フラグ：複数フラグでの絞り込み
- ソート：各カラムでのソート切り替え
- ページング：ページ遷移、表示件数変更
- 権限制御：一般ユーザーの検索制限

---

**作成者**: Claude AI  
**レビュー**: Phase 03完了時  
**改訂履歴**:
- v1.0 (2025-11-25): 初版作成
- v1.1 (2025-12-08): SQL_顧客とのJOINキーを修正（顧客コード→SEQNO）
