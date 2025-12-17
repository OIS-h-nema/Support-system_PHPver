<?php
/**
 * ファイル名: master_teikei.php
 * 機能概要: 定型文マスタ設定画面
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 08）
 * 2025-12-17 ヘッダー固定・フィルタ既定値修正
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// ログインチェック（権限制限なし）
requireLogin();

// ログインユーザーの部門コード取得
$user_bumon_code = getCurrentBumonCode();

// 部門リスト取得（エイリアス使用で文字化け対策）
$bumon_list = array();
try {
    global $pdo_conn;
    $stmt = $pdo_conn->query("SELECT 部門コード AS code, 部門名 AS name FROM SQL_部門 ORDER BY 部門コード");
    $bumon_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Bumon list error: ' . $e->getMessage());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>定型文マスタ設定 - <?php echo h(SYSTEM_NAME); ?></title>
    <link rel="stylesheet" href="css/common.css" type="text/css" />
    <link rel="stylesheet" href="css/master.css" type="text/css" />
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/master.js"></script>
</head>
<body>
<div class="master-container">
    <!-- ヘッダー（固定表示） -->
    <div class="master-header">
        <h1>マスタ設定 - 定型文</h1>
        <button type="button" id="btn-master-close" class="master-close-btn">閉じる</button>
    </div>
    
    <!-- メインコンテンツ -->
    <div class="master-content">
        <!-- メッセージエリア -->
        <div class="master-message-area"></div>
        
        <!-- 入力エリア -->
        <div class="master-input-area">
            <div class="master-input-row">
                <label for="master-bumon" class="required">部門</label>
                <div class="input-field">
                    <select id="master-bumon" name="bumon_code">
                        <option value="">選択してください</option>
                        <?php foreach ($bumon_list as $b): ?>
                        <option value="<?php echo h($b['code']); ?>">
                            <?php echo h($b['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="master-input-row">
                <label for="master-code">定型文コード</label>
                <div class="input-field">
                    <input type="text" id="master-code" name="code" readonly class="readonly" style="width: 100px;" />
                    <span class="input-hint">新規登録時は部門内で自動採番されます</span>
                </div>
            </div>
            
            <div class="master-input-row">
                <label for="master-text" class="required">定型文</label>
                <div class="input-field">
                    <textarea id="master-text" name="text" rows="10"></textarea>
                </div>
            </div>
            
            <div class="master-input-actions">
                <button type="button" id="btn-master-clear" class="btn btn-outline">クリア</button>
                <button type="button" id="btn-master-save" class="btn btn-primary">保存</button>
            </div>
        </div>
        
        <!-- フィルタエリア -->
        <div class="master-filter-area">
            <label for="filter-bumon">部門:</label>
            <select id="filter-bumon">
                <option value="">すべて</option>
                <?php foreach ($bumon_list as $b): ?>
                <option value="<?php echo h($b['code']); ?>"<?php echo ($b['code'] == $user_bumon_code) ? ' selected' : ''; ?>>
                    <?php echo h($b['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- 一覧エリア -->
        <div class="master-list-area">
            <div class="data-table-container">
                <div class="data-table-wrapper">
                    <table class="master-table">
                        <thead>
                            <tr>
                                <th class="col-bumon">部門</th>
                                <th class="col-code">コード</th>
                                <th class="col-text">定型文（先頭80文字）</th>
                                <th class="col-date">更新日時</th>
                                <th class="col-action">操作</th>
                            </tr>
                        </thead>
                        <tbody id="master-table-body">
                            <tr><td colspan="5" class="no-data">読み込み中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function() {
    TemplateMaster.init('template');
});
</script>
</body>
</html>
