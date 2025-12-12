# 定型文選択機能設計書

**作成日**: 2025-11-25  
**バージョン**: 1.0  
**Phase**: 03  
**機能ID**: template_select  
**関連ファイル**: support_input.php（定型文選択ダイアログ部分）

---

## 1. 概要

### 1.1 機能の目的
報告内容入力時に定型文を選択し、テキストエリアに挿入する機能を提供する。

### 1.2 機能範囲
- 定型文リストの取得（部門フィルタ）
- 定型文選択ダイアログの表示
- 定型文の報告内容への挿入
- カーソル位置または末尾への挿入

---

## 2. 定型文選択UI

### 2.1 呼び出しボタン

```
サポート報告 *    [定型文]
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  （報告内容入力エリア）                                  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 2.2 定型文選択ダイアログ（サブモーダル）

```
┌─────────────────────────────────────────────────────────┐
│ 定型文選択                                        [×]   │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ ┌─────────────────────────────────────────────────────┐ │
│ │ ○ お客様へお問い合わせいたしました。              │ │
│ │   折り返しご連絡をお待ちしております。              │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ ○ データ更新が完了いたしました。                   │ │
│ │   ご確認をお願いいたします。                        │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ ○ プログラム更新を行いました。                     │ │
│ │   動作確認をお願いいたします。                      │ │
│ ├─────────────────────────────────────────────────────┤ │
│ │ ...                                                 │ │
│ └─────────────────────────────────────────────────────┘ │
│                                                         │
├─────────────────────────────────────────────────────────┤
│                              [キャンセル] [挿入]        │
└─────────────────────────────────────────────────────────┘
```

---

## 3. 処理フロー

### 3.1 定型文リスト取得

```
START
  │
  ↓
┌─────────────────────┐
│[定型文]ボタン押下    │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│現在の部門コード取得  │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│Ajax通信             │
│SEL_M_定型文_部門別  │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│定型文リスト表示      │
│（ラジオボタン形式）  │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│ダイアログ表示        │
└─────────────────────┘
         │
        END
```

### 3.2 定型文挿入

```
START
  │
  ↓
┌─────────────────────┐
│[挿入]ボタン押下      │
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│選択された定型文取得  │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
  選択あり  選択なし
    │         │
    ↓         ↓
┌─────────┐  エラー表示
│カーソル  │  「定型文を選択してください」
│位置取得  │
└────┬────┘
     │
     ↓
┌─────────────────────┐
│テキストエリアに挿入  │
│（カーソル位置 or 末尾）│
└────────┬────────────┘
         │
         ↓
┌─────────────────────┐
│ダイアログを閉じる    │
└─────────────────────┘
         │
        END
```

---

## 4. 取得SQL

```sql
-- SEL_M_定型文_部門別
SELECT 
    定型文コード,
    定型文
FROM 
    M_定型文
WHERE 
    部門コード = @bumon_code
ORDER BY 
    定型文コード
```

---

## 5. Ajax通信仕様

### 5.1 リクエスト

| パラメータ名 | 型 | 必須 | 説明 |
|-------------|-----|------|------|
| action | string | ○ | 'get_teikei_list' |
| bumon_code | int | ○ | 部門コード |

### 5.2 レスポンス

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
        },
        {
            "code": 3,
            "text": "プログラム更新を行いました。\n動作確認をお願いいたします。"
        }
    ]
}
```

---

## 6. JavaScript実装

### 6.1 定型文ダイアログ表示

```javascript
/**
 * 定型文ダイアログを開く
 */
function openTeikeiDialog() {
    var bumonCode = $('#bumon-code').val();
    
    if (!bumonCode) {
        showError('部門を選択してください。');
        return;
    }
    
    // 定型文リスト取得
    $.ajax({
        type: 'POST',
        url: 'support_ajax_master.php',
        data: {
            action: 'get_teikei_list',
            bumon_code: bumonCode
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                renderTeikeiList(response.data);
                $('#teikei-dialog').fadeIn(200);
            } else {
                showError('定型文の取得に失敗しました。');
            }
        }
    });
}

/**
 * 定型文リスト描画
 */
function renderTeikeiList(data) {
    var $list = $('#teikei-list');
    $list.empty();
    
    if (data.length === 0) {
        $list.append('<p class="no-data">登録された定型文がありません。</p>');
        return;
    }
    
    $.each(data, function(i, item) {
        var $item = $('<div class="teikei-item">');
        var $radio = $('<input type="radio" name="teikei" id="teikei-' + item.code + '">')
            .val(item.code)
            .data('text', item.text);
        var $label = $('<label for="teikei-' + item.code + '">')
            .text(item.text.substring(0, 100) + (item.text.length > 100 ? '...' : ''));
        
        $item.append($radio).append($label);
        $list.append($item);
    });
}
```

### 6.2 定型文挿入

```javascript
/**
 * 定型文を挿入
 */
function insertTeikei() {
    var $selected = $('input[name="teikei"]:checked');
    
    if ($selected.length === 0) {
        showError('定型文を選択してください。');
        return;
    }
    
    var text = $selected.data('text');
    var $textarea = $('#houkoku-naiyo');
    var textarea = $textarea[0];
    
    // カーソル位置に挿入
    var startPos = textarea.selectionStart;
    var endPos = textarea.selectionEnd;
    var currentText = $textarea.val();
    
    if (startPos !== undefined) {
        // カーソル位置に挿入
        var newText = currentText.substring(0, startPos) + text + currentText.substring(endPos);
        $textarea.val(newText);
        
        // カーソル位置を挿入後に移動
        var newPos = startPos + text.length;
        textarea.setSelectionRange(newPos, newPos);
    } else {
        // 末尾に追加
        $textarea.val(currentText + text);
    }
    
    // ダイアログを閉じる
    closeTeikeiDialog();
    
    // テキストエリアにフォーカス
    $textarea.focus();
}

/**
 * 定型文ダイアログを閉じる
 */
function closeTeikeiDialog() {
    $('#teikei-dialog').fadeOut(200);
    // 選択をクリア
    $('input[name="teikei"]').prop('checked', false);
}
```

---

## 7. CSS設計

```css
/* 定型文ダイアログ */
.teikei-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 500px;
    max-width: 90%;
    max-height: 80vh;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    z-index: 1100;
    display: none;
}

.teikei-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.teikei-item {
    padding: 10px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.teikei-item:hover {
    background-color: #f5f5f5;
}

.teikei-item input[type="radio"] {
    margin-right: 10px;
}

.teikei-item label {
    display: inline-block;
    white-space: pre-wrap;
    word-break: break-all;
    cursor: pointer;
}
```

---

## 8. バリデーション

| No | チェック内容 | エラーメッセージ |
|----|-------------|-----------------|
| 1 | 部門未選択 | 部門を選択してください。 |
| 2 | 定型文未選択 | 定型文を選択してください。 |

---

## 9. 備考

### 9.1 既存システムとの違い

| 項目 | Accessシステム | WEBシステム |
|------|--------------|------------|
| 表示形式 | 別フォーム | サブモーダル |
| 選択方式 | ダブルクリック | ラジオボタン＋挿入ボタン |
| 挿入位置 | 末尾 | カーソル位置または末尾 |

### 9.2 テスト観点

- 定型文リスト表示：部門別のリスト取得
- 定型文選択：ラジオボタン選択
- 定型文挿入：カーソル位置への挿入
- 定型文挿入：末尾への追加
- ダイアログ操作：開く、閉じる

---

**作成者**: Claude AI
