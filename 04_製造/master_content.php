<?php
/**
 * ファイル名: master_content.php
 * 機能概要: 対応内容項目マスタ設定画面
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 08）
 * 2025-12-17 ヘッダー固定対応
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// ログインチェック（権限制限なし）
requireLogin();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>対応内容項目マスタ設定 - <?php echo h(SYSTEM_NAME); ?></title>
    <link rel="stylesheet" href="css/common.css" type="text/css" />
    <link rel="stylesheet" href="css/master.css" type="text/css" />
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/master.js"></script>
</head>
<body>
<div class="master-container">
    <!-- メインコンテンツ -->
    <div class="master-content">
        <!-- メッセージエリア -->
        <div class="master-message-area"></div>
        
        <!-- 入力エリア -->
        <div class="master-input-area">
            <div class="master-input-row">
                <label for="master-code" class="required">項目コード</label>
                <div class="input-field">
                    <input type="number" id="master-code" name="code" min="1" max="20" style="width: 100px;" />
                    <span class="input-hint code-range-note">1〜20の範囲で指定してください</span>
                </div>
            </div>
            
            <div class="master-input-row">
                <label for="master-name" class="required">項目名</label>
                <div class="input-field">
                    <input type="text" id="master-name" name="name" maxlength="255" />
                </div>
            </div>
            
            <div class="master-input-actions">
                <button type="button" id="btn-master-clear" class="btn btn-outline">クリア</button>
                <button type="button" id="btn-master-save" class="btn btn-primary">保存</button>
            </div>
        </div>
        
        <!-- 注意書き -->
        <div class="master-note">
            <strong>※ 注意事項</strong><br />
            ・項目コードは1〜20の範囲で指定してください。<br />
            ・入力ダイアログのチェックボックスの並び順はコード順になります。<br />
            ・削除すると入力ダイアログのチェックボックスから消えます。
        </div>
        
        <!-- 一覧エリア -->
        <div class="master-list-area">
            <div class="data-table-container">
                <div class="data-table-wrapper">
                    <table class="master-table">
                        <thead>
                            <tr>
                                <th class="col-code">項目コード</th>
                                <th class="col-name">項目名</th>
                                <th class="col-date">更新日時</th>
                                <th class="col-action">操作</th>
                            </tr>
                        </thead>
                        <tbody id="master-table-body">
                            <tr><td colspan="4" class="no-data">読み込み中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    ContentMaster.init('content');
});
</script>
</body>
</html>
