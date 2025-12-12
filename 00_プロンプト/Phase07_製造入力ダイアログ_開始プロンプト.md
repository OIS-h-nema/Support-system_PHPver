# Phase 07 製造（入力ダイアログ）開始プロンプト

**作成日**: 2025-11-25  
**前Phase**: Phase 06（製造 - 認証・メイン画面）  
**次Phase**: Phase 08（製造 - マスタ設定・Excel出力）

---

## プロジェクト概要

### 達成目標
現在Accessで動いてるシステムのWEB版をリリースする

### システム概要
自社の社員向けに、日々の顧客からの問い合わせや、それに対する対応を入力し、記録・分析を行うもの

### 技術スタック
- **DB**: SQL Server 2022（継続）
- **DB接続**: PDO（PHP）
- **データ操作**: ストアドプロシージャ（継続）/（一部PHP）
- **フロント**: PHP 7.4+ / HTML5 / CSS3 / jQuery 1.11.3

---

## 作業ディレクトリ

- **作業ディレクトリ**: `D:\#M\PG_DATA\OIS社内システム\PG_DATA\サポート報告書WEB\`
- **検証ディレクトリ**: `\\LPG-NEMA\C\inetpub\wwwroot\support-system\`
- **本番ディレクトリ**: `\\DEV-SE\C\inetpub\wwwroot\support-system\`

---

## Phase 06 完了成果物

### 04_製造/
1. `login.php` - ログイン画面
   - ログインフォーム（担当者コード、パスワード）
   - クライアント側・サーバー側バリデーション
   - 認証処理（SQL_作業担当テーブル参照）
   - セッション初期化
   - エラー表示
   - レスポンシブ対応

2. `logout.php` - ログアウト処理
   - セッション破棄
   - ログイン画面へリダイレクト

3. `support_main.php` - メイン画面（一覧表示）
   - ヘッダー（システム名、ユーザー情報、ログアウト）
   - ナビゲーションメニュー（新規入力、マスタ設定、Excel出力）
   - 検索エリア（アコーディオン形式）
   - 一覧テーブル（Ajax読み込み）
   - ページネーション
   - ソート機能
   - 権限制御（管理者メニュー）

4. `support_ajax01.php` - Ajax処理
   - 検索処理（action=search）
   - データ取得（action=get）
   - 表示件数変更（action=change_display_count）
   - ソート変更（action=sort）

### 04_製造/templates/
1. `header.php` - ヘッダーテンプレート
2. `footer.php` - フッターテンプレート

---

## Phase 07 作業内容

### 目的
サポート報告書の新規登録・編集機能を実装する入力ダイアログを作成する。

### 成果物

#### 1. 入力ダイアログ関連
```
04_製造/
├── support_dialog.php      # 入力ダイアログHTML
├── support_ajax02.php      # 入力ダイアログ用Ajax処理（CRUD）
├── js/
│   └── support_dialog.js   # 入力ダイアログ用JavaScript
├── css/
│   └── dialog.css          # ダイアログ用CSS（追加）
```

### 実装項目

#### support_dialog.php
- モーダルダイアログ形式
- 入力フォーム項目:
  - 対応開始日時（日付＋時間）
  - 対応終了日時（日付＋時間）
  - 顧客名（オートコンプリート or 選択）
  - 顧客担当者名
  - 商品（複数選択可：商品1、商品2、商品3）
  - 対応区分
  - 対応内容（チェックボックス群）
  - 報告内容（テキストエリア）
  - 定型文選択ボタン
- 新規・編集モード対応
- バリデーション
- 登録・更新・削除ボタン

#### support_ajax02.php
- 登録処理（action=insert）
- 更新処理（action=update）
- 削除処理（action=delete）
- 顧客検索（action=search_customer）
- 定型文取得（action=get_template）

#### support_dialog.js
- ダイアログ開閉処理
- フォーム初期化・データ設定
- バリデーション
- 登録・更新・削除処理
- 顧客オートコンプリート
- 定型文挿入

### 格納先
- `D:\#M\PG_DATA\OIS社内システム\PG_DATA\サポート報告書WEB\04_製造\`

---

## 必須参照ドキュメント

### 規約書
1. `01_規約/開発規約書.md` - 開発の基本方針
2. `01_規約/コーディング規約書.md` - コーディングルール

### 設計書
1. `03_設計/画面設計書/input_dialog.md` - 入力ダイアログ設計

### Phase 06成果物（参照用）
1. `04_製造/support_main.php` - メイン画面（ダイアログ呼び出し元）
2. `04_製造/support_ajax01.php` - Ajax処理パターン参照
3. `04_製造/includes/` - 基盤ファイル
4. `04_製造/js/common.js` - 共通JavaScript

---

## 作業進行の制約

1. **大容量ファイルの分割処理**
   - ファイル読み込みは必要に応じて分割
   - ファイル書き込みも大きい場合は分割

2. **成果物出力**
   - 作業ディレクトリのみにファイル出力
   - チャットでは出力結果の報告のみ

3. **トークン節約**
   - 長尺タスクは分割処理
   - 不要な出力を控える

4. **チャット容量管理**
   - 容量オーバーになる前に次チャット立ち上げを促す
   - プロンプトファイルを生成して継続性を確保

---

## 実装仕様

### support_dialog.php 基本構造

```html
<!-- モーダルオーバーレイ -->
<div class="modal-overlay" id="input-dialog-overlay">
    <div class="modal input-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="dialog-title">新規登録</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="input-form">
                <input type="hidden" id="edit-seqno" name="seqno" value="" />
                <input type="hidden" id="edit-mode" name="mode" value="new" />
                
                <table class="input-table">
                    <!-- 対応日時 -->
                    <tr>
                        <th>対応開始日時<span class="required">*</span></th>
                        <td>
                            <input type="date" id="taiou-date-from" />
                            <input type="time" id="taiou-time-from" />
                        </td>
                    </tr>
                    <tr>
                        <th>対応終了日時</th>
                        <td>
                            <input type="date" id="taiou-date-to" />
                            <input type="time" id="taiou-time-to" />
                        </td>
                    </tr>
                    <!-- 顧客情報 -->
                    <tr>
                        <th>顧客名<span class="required">*</span></th>
                        <td>
                            <input type="text" id="kokyaku-name" />
                            <input type="hidden" id="kokyaku-code" />
                        </td>
                    </tr>
                    <!-- 商品選択 -->
                    <tr>
                        <th>商品</th>
                        <td>
                            <select id="shohin-1"></select>
                            <select id="shohin-2"></select>
                            <select id="shohin-3"></select>
                        </td>
                    </tr>
                    <!-- 対応区分 -->
                    <tr>
                        <th>対応区分</th>
                        <td>
                            <select id="kubun"></select>
                        </td>
                    </tr>
                    <!-- 対応内容 -->
                    <tr>
                        <th>対応内容</th>
                        <td>
                            <div class="checkbox-group" id="taiou-content">
                                <!-- 動的生成 -->
                            </div>
                        </td>
                    </tr>
                    <!-- 報告内容 -->
                    <tr>
                        <th>報告内容<span class="required">*</span></th>
                        <td>
                            <div class="template-buttons" id="template-buttons">
                                <!-- 定型文ボタン動的生成 -->
                            </div>
                            <textarea id="houkoku" rows="10"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="btn-delete" class="btn btn-danger hidden">削除</button>
            <button type="button" id="btn-cancel" class="btn btn-outline">キャンセル</button>
            <button type="button" id="btn-save" class="btn btn-primary">登録</button>
        </div>
    </div>
</div>
```

### support_ajax02.php 基本構造

```php
<?php
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/validation.php");

header('Content-Type: application/json; charset=utf-8');
checkSessionForAjax();

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'insert':
        doInsert();
        break;
    case 'update':
        doUpdate();
        break;
    case 'delete':
        doDelete();
        break;
    case 'search_customer':
        searchCustomer();
        break;
    case 'get_template':
        getTemplate();
        break;
    default:
        jsonError('不正なリクエストです。');
        break;
}
```

---

## データベース情報

### D_作業報告テーブル（主要カラム）

| カラム名 | 型 | 必須 | 説明 |
|---------|-----|------|------|
| SEQNO | int | ○ | 主キー（自動採番） |
| 対応開始日時 | datetime | ○ | 対応開始日時 |
| 対応終了日時 | datetime | - | 対応終了日時 |
| 顧客コード | int | ○ | 顧客マスタ参照 |
| 顧客担当者名 | nvarchar | - | 顧客側担当者 |
| 作業担当コード | int | ○ | ログインユーザー |
| 商品コード1 | int | - | 商品1 |
| 商品コード2 | int | - | 商品2 |
| 商品コード3 | int | - | 商品3 |
| 対応区分コード | int | - | 対応区分 |
| 対応内容フラグ1～20 | bit | - | 対応内容チェック |
| 報告内容 | nvarchar(max) | ○ | 報告内容テキスト |
| 登録日時 | datetime | ○ | 登録日時 |
| 更新日時 | datetime | - | 更新日時 |
| 削除日時 | datetime | - | 論理削除日時 |

### 関連テーブル

- `SQL_顧客` - 顧客マスタ（外部DB）
- `M_商品` - 商品マスタ
- `M_対応区分` - 対応区分マスタ
- `M_対応内容項目` - 対応内容項目マスタ
- `M_定型文` - 定型文マスタ

---

## 作業開始指示

1. 上記の必須参照ドキュメントを読み込む
2. 入力ダイアログ設計書（input_dialog.md）を確認
3. support_dialog.php を作成
4. support_ajax02.php を作成（CRUD処理）
5. js/support_dialog.js を作成
6. css/dialog.css を作成
7. support_main.php のダイアログ呼び出し部分を修正
8. 動作確認項目を整理
9. Phase 08用プロンプトを生成

---

## 確認事項

Phase 07開始時に以下を確認してください：

- [ ] 04_製造/support_main.php を読み込んだ
- [ ] 04_製造/support_ajax01.php を読み込んだ
- [ ] 04_製造/includes/ 各ファイルを参照した
- [ ] 04_製造/js/common.js を読み込んだ
- [ ] 03_設計/画面設計書/input_dialog.md を参照した

上記確認後、作業を開始してください。

---

## 品質チェックリスト

Phase 07完了時に以下を確認：

- [ ] 新規入力ダイアログが正常に表示されるか
- [ ] 編集ダイアログにデータが正しく表示されるか
- [ ] 必須項目のバリデーションが動作するか
- [ ] 登録処理が正常に動作するか
- [ ] 更新処理が正常に動作するか
- [ ] 削除処理が正常に動作するか（管理者のみ）
- [ ] 顧客検索（オートコンプリート）が動作するか
- [ ] 定型文挿入が動作するか
- [ ] 登録後に一覧が更新されるか
- [ ] エラー時に適切なメッセージが表示されるか

---

## 権限制御

| 機能 | 一般ユーザー | 管理者 |
|------|------------|--------|
| 新規登録 | ○ | ○ |
| 自分のデータ編集 | ○ | ○ |
| 他人のデータ編集 | × | ○ |
| 削除 | × | ○ |

---

**作成者**: Claude AI  
**Phase 06完了日**: 2025-11-25
