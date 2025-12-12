# 商品選択機能設計書

**作成日**: 2025-11-25  
**バージョン**: 1.0  
**Phase**: 03  
**機能ID**: product_select  
**関連ファイル**: support_input.php（商品セレクトボックス部分）

---

## 1. 概要

### 1.1 機能の目的
入力ダイアログで商品を選択するためのセレクトボックス機能を提供する。

### 1.2 機能範囲
- 商品リストの取得（部門フィルタ）
- セレクトボックスの表示
- 複数商品選択（最大3件）
- 重複チェック

---

## 2. 商品選択UI

### 2.1 レイアウト（PC版）

```
商品          [SILPS           ▼] [ちゃんぷる～EJ   ▼] [               ▼]
```

### 2.2 セレクトボックス選択肢

```html
<select id="shohin-code1" name="shohin_code1">
    <option value="">選択してください</option>
    <option value="1">SILPS</option>
    <option value="2">ちゃんぷる～EJ</option>
    <option value="3">マイヘルスEJ</option>
    <!-- 部門に紐づく商品のみ表示 -->
</select>
```

---

## 3. 処理フロー

### 3.1 商品リスト取得

```
START
  │
  ↓
┌─────────────────────┐
│部門コード取得        │
│（セレクト変更時）    │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│Ajax通信             │
│SEL_M_商品_部門別    │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│商品リスト取得        │
│（使用区分=使用する） │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│セレクトボックス      │
│オプション更新        │
└─────────────────────┘
         │
        END
```

### 3.2 取得SQL

```sql
-- SEL_M_商品_部門別
SELECT 
    商品コード,
    商品名
FROM 
    M_商品
WHERE 
    部門コード = @bumon_code
    AND 使用区分 = 1  -- 使用する
ORDER BY 
    商品コード
```

---

## 4. Ajax通信仕様

### 4.1 リクエスト

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'get_shohin_list' |
| bumon_code | int | ○ | 部門コード |

### 4.2 レスポンス

```json
{
    "status": "success",
    "data": [
        { "code": 1, "name": "SILPS" },
        { "code": 2, "name": "ちゃんぷる～EJ" },
        { "code": 3, "name": "マイヘルスEJ" }
    ]
}
```

---

## 5. JavaScript実装

```javascript
/**
 * 商品リスト取得
 */
function loadShohinList(bumonCode) {
    $.ajax({
        type: 'POST',
        url: 'support_ajax_master.php',
        data: {
            action: 'get_shohin_list',
            bumon_code: bumonCode
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateShohinSelects(response.data);
            }
        }
    });
}

/**
 * 商品セレクトボックス更新
 */
function updateShohinSelects(data) {
    var $selects = $('#shohin-code1, #shohin-code2, #shohin-code3');
    
    $selects.each(function() {
        var currentVal = $(this).val();
        $(this).empty();
        $(this).append('<option value="">選択してください</option>');
        
        $.each(data, function(i, item) {
            $('<option>')
                .val(item.code)
                .text(item.name)
                .appendTo($selects);
        });
        
        $(this).val(currentVal);
    });
}

/**
 * 商品重複チェック
 */
function checkShohinDuplicate() {
    var codes = [];
    var hasDuplicate = false;
    
    $('#shohin-code1, #shohin-code2, #shohin-code3').each(function() {
        var code = $(this).val();
        if (code !== '' && codes.indexOf(code) !== -1) {
            hasDuplicate = true;
        }
        if (code !== '') {
            codes.push(code);
        }
    });
    
    return hasDuplicate;
}
```

---

## 6. バリデーション

| No | チェック内容 | エラーメッセージ |
|----|-------------|-----------------|
| 1 | 重複選択 | 同じ商品が複数選択されています。 |

---

## 7. 備考

- 商品選択は任意（空欄可）
- 最大3件まで選択可能
- 部門変更時にリストを再取得
- 編集時は選択済みの値を保持

---

**作成者**: Claude AI
