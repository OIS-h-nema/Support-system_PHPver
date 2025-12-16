<?php

/**
 * ファイル名: support_main.php
 * 機能概要: メイン画面（サポート報告書一覧）
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 * 2025-11-25 入力ダイアログ組み込み（Phase 07）
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");
require_once("includes/functions.php");
require_once("includes/error.php");

// 認証チェック
requireLogin();

// 初期データ取得
$bumon_list = array();      // 部門リスト
$tantou_list = array();     // 担当者リスト
$shohin_list = array();     // 商品リスト
$kubun_list = array();      // 対応区分リスト
$content_list = array();    // 対応内容項目リスト

try {
    global $pdo_conn;

    // 部門リスト取得
    $stmt = $pdo_conn->query("SELECT 部門コード AS code, 部門名 AS name FROM SQL_部門 ORDER BY 部門コード");
    $bumon_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 担当者リスト取得（部門コード付き、エイリアス使用で文字化け対策）
    $stmt = $pdo_conn->query("SELECT 担当者コード AS code, 担当者名 AS name, 部門コード AS bumon_code FROM SQL_作業担当 ORDER BY 担当者コード");
    $tantou_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 商品リスト取得（使用区分=1のもの）
    $stmt = $pdo_conn->query("SELECT 商品コード AS code, 商品名 AS name FROM M_商品 WHERE 使用区分 = 1 ORDER BY 商品コード");
    $shohin_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 対応区分リスト取得（対応区分コード>0のみ）
    $stmt = $pdo_conn->query("SELECT 対応区分コード AS code, 対応区分名 AS name FROM M_対応区分 WHERE 対応区分コード > 0 ORDER BY 対応区分コード");
    $kubun_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 対応内容項目リスト取得
    $stmt = $pdo_conn->query("SELECT 項目コード AS code, 項目名 AS name FROM M_対応内容項目 ORDER BY 項目コード");
    $content_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Master data fetch error: ' . $e->getMessage());
}

// 検索条件をセッションから復元
$search_params = getSearchParams();
$current_page = getCurrentPage();
$display_count = getDisplayCount();
$sort_params = getSortParams();

// デフォルトの日付範囲（今日～1週間前）
$default_date_to = date('Y-m-d');  // 今日
$default_date_from = date('Y-m-d', strtotime('-7 days'));  // 1週間前

// セッションに日付範囲が設定されていない場合はデフォルト値を使用
$is_initial_load = empty($search_params['taiou_date_from']) && empty($search_params['taiou_date_to']);

// フラッシュメッセージ取得
$flash_message = getFlashMessage();
$flash_errors = getFlashErrors();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo h(SYSTEM_NAME); ?></title>
    <link rel="stylesheet" href="css/common.css" type="text/css" />
    <link rel="stylesheet" href="css/dialog.css" type="text/css" />
    <style type="text/css">
        /* メイン画面専用スタイル */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            margin: 20px;
            padding: 20px;
        }

        /* アコーディオン */
        .accordion {
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .accordion-header {
            background: #f3f4f6;
            padding: 12px 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            user-select: none;
        }

        .accordion-header:hover {
            background: #e5e7eb;
        }

        .accordion-icon {
            transition: transform 0.3s;
            font-size: 12px;
        }

        .accordion-icon.open {
            transform: rotate(180deg);
        }

        .accordion-content {
            background: #fff;
            padding: 20px;
            display: none;
        }

        .accordion-content.open {
            display: block;
        }

        /* 検索フォーム */
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-item {
            display: flex;
            flex-direction: column;
        }

        .search-item label {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }

        .search-item input,
        .search-item select {
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-item input:focus,
        .search-item select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
        }

        .search-item.wide input {
            width: 200px;
        }

        .search-date-range {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-date-range input {
            width: 130px;
        }

        /* チェックボックスグループ */
        .checkbox-group-wrapper {
            width: 100%;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .checkbox-group-wrapper label.group-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
            display: block;
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .checkbox-item input {
            margin: 0;
        }

        /* 検索ボタン */
        .search-actions {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }

        /* 結果エリア */
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .result-count {
            font-size: 14px;
        }

        .result-count strong {
            color: #2563eb;
        }

        .display-options {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .display-options label {
            font-size: 13px;
            color: #666;
        }

        .display-options select {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
        }

        /* データテーブル（埋め込み型スクロール対応） */
        .data-table-container {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .data-table-header {
            background: #f9fafb;
            border-bottom: 2px solid #d1d5db;
        }

        .data-table-header table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table-body-wrapper {
            max-height: 500px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .data-table-body-wrapper table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table-header th,
        .data-table-body-wrapper td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table-header th {
            background: #f9fafb;
            font-weight: 500;
            font-size: 13px;
            white-space: nowrap;
        }

        .data-table-header th.sortable {
            cursor: pointer;
            user-select: none;
        }

        .data-table-header th.sortable:hover {
            background: #e5e7eb;
        }

        .data-table-header th .sort-icon {
            margin-left: 5px;
            opacity: 0.4;
        }

        .data-table-header th.sorted .sort-icon {
            opacity: 1;
        }

        .data-table-body-wrapper tbody tr:hover {
            background: #f0f7ff;
            cursor: pointer;
        }

        .data-table-body-wrapper tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .data-table-body-wrapper tbody tr:nth-child(even):hover {
            background: #f0f7ff;
        }

        /* カラム幅設定（ヘッダーとボディで共通） */
        .col-tantou {
            width: 100px;
        }

        .col-shohin {
            width: 120px;
        }

        .col-datetime {
            width: 140px;
        }

        .col-content {
            width: 150px;
        }

        .col-kokyaku {
            width: 150px;
        }

        .col-houkoku {
            width: auto;
        }

        .col-action {
            width: 80px;
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .houkoku-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            position: relative;
        }

        /* 報告内容プレビューポップアップ（グローバルツールチップ） */
        #houkoku-tooltip {
            display: none;
            position: fixed;
            z-index: 9999;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 12px;
            min-width: 300px;
            max-width: 450px;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 13px;
            line-height: 1.5;
        }

        #houkoku-tooltip.visible {
            display: block;
        }

        .btn-edit {
            padding: 4px 12px;
            font-size: 12px;
        }

        /* ページネーション */
        .paging-area {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .paging-buttons {
            display: flex;
            gap: 5px;
        }

        .paging-buttons button,
        .paging-buttons span {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #fff;
            font-size: 13px;
            cursor: pointer;
        }

        .paging-buttons button:hover:not(:disabled) {
            background: #f3f4f6;
        }

        .paging-buttons button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .paging-buttons .current {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .page-info {
            font-size: 13px;
            color: #666;
        }

        /* レスポンシブ対応 */
        @media screen and (max-width: 768px) {
            .main-content {
                margin: 10px;
                padding: 15px;
            }

            .search-form {
                flex-direction: column;
            }

            .search-item {
                width: 100%;
            }

            .search-item input,
            .search-item select,
            .search-item.wide input {
                width: 100%;
            }

            .search-date-range {
                flex-wrap: wrap;
            }

            .search-date-range input {
                width: calc(50% - 15px);
            }

            .result-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .paging-area {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/common.js"></script>
    <script type="text/javascript" src="js/support_dialog.js"></script>
</head>

<body>
    <div class="page-wrapper">
        <?php include("templates/header.php"); ?>

        <div class="main-content">
            <?php if ($flash_message): ?>
                <div class="<?php echo $flash_message['type'] == 'success' ? 'success-box' : 'info-box'; ?>">
                    <p class="<?php echo $flash_message['type'] == 'success' ? 'success-message' : 'info-message'; ?>">
                        <?php echo h($flash_message['text']); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if (count($flash_errors) > 0): ?>
                <div class="error-box">
                    <?php foreach ($flash_errors as $err): ?>
                        <p class="error-message"><span class="icon-error">！</span><?php echo h($err); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 検索エリア（アコーディオン） -->
            <div class="accordion">
                <div class="accordion-header" id="search-accordion-header">
                    <span>データ検索</span>
                    <span class="accordion-icon">▼</span>
                </div>
                <div class="accordion-content" id="search-accordion-content">
                    <div class="search-form" id="search-form">
                        <div class="search-item">
                            <label for="search-bumon">部門</label>
                            <select id="search-bumon" name="search_bumon">
                                <option value="">すべて</option>
                                <?php foreach ($bumon_list as $b): ?>
                                    <option value="<?php echo h($b['code']); ?>"
                                        <?php echo (isset($search_params['bumon_code']) && $search_params['bumon_code'] == $b['code']) ? 'selected' : ''; ?>>
                                        <?php echo h($b['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="search-item">
                            <label for="search-tantou">作業担当</label>
                            <select id="search-tantou" name="search_tantou">
                                <option value="">すべて</option>
                                <?php foreach ($tantou_list as $t): ?>
                                    <option value="<?php echo h($t['code']); ?>" data-bumon="<?php echo h($t['bumon_code']); ?>"
                                        <?php echo (isset($search_params['tantou_code']) && $search_params['tantou_code'] == $t['code']) ? 'selected' : ''; ?>>
                                        <?php echo h($t['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="search-item wide">
                            <label for="search-kokyaku">顧客名</label>
                            <input type="text" id="search-kokyaku" name="search_kokyaku"
                                value="<?php echo h(isset($search_params['kokyaku_name']) ? $search_params['kokyaku_name'] : ''); ?>"
                                placeholder="部分一致" />
                        </div>

                        <div class="search-item">
                            <label for="search-shohin">商品</label>
                            <select id="search-shohin" name="search_shohin">
                                <option value="">すべて</option>
                                <?php foreach ($shohin_list as $s): ?>
                                    <option value="<?php echo h($s['code']); ?>"
                                        <?php echo (isset($search_params['shohin_code']) && $search_params['shohin_code'] == $s['code']) ? 'selected' : ''; ?>>
                                        <?php echo h($s['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="search-item">
                            <label for="search-kubun">対応区分</label>
                            <select id="search-kubun" name="search_kubun">
                                <option value="">すべて</option>
                                <?php foreach ($kubun_list as $k): ?>
                                    <option value="<?php echo h($k['code']); ?>"
                                        <?php echo (isset($search_params['kubun_code']) && $search_params['kubun_code'] == $k['code']) ? 'selected' : ''; ?>>
                                        <?php echo h($k['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="search-item">
                            <label>対応日</label>
                            <div class="search-date-range">
                                <input type="date" id="search-date-from" name="search_date_from"
                                    value="<?php echo h($is_initial_load ? $default_date_from : (isset($search_params['taiou_date_from']) ? $search_params['taiou_date_from'] : '')); ?>" />
                                <span>～</span>
                                <input type="date" id="search-date-to" name="search_date_to"
                                    value="<?php echo h($is_initial_load ? $default_date_to : (isset($search_params['taiou_date_to']) ? $search_params['taiou_date_to'] : '')); ?>" />
                            </div>
                        </div>

                        <div class="search-item wide">
                            <label for="search-keyword">キーワード</label>
                            <input type="text" id="search-keyword" name="search_keyword"
                                value="<?php echo h(isset($search_params['keyword']) ? $search_params['keyword'] : ''); ?>"
                                placeholder="報告内容を検索" />
                        </div>

                        <?php if (count($content_list) > 0): ?>
                            <div class="checkbox-group-wrapper">
                                <label class="group-label">対応内容</label>
                                <div class="checkbox-group">
                                    <?php
                                    $selected_flags = isset($search_params['taiou_flags']) ? $search_params['taiou_flags'] : array();
                                    foreach ($content_list as $c):
                                    ?>
                                        <div class="checkbox-item">
                                            <input type="checkbox"
                                                id="search-content-<?php echo h($c['code']); ?>"
                                                name="search_content[]"
                                                value="<?php echo h($c['code']); ?>"
                                                <?php echo in_array($c['code'], $selected_flags) ? 'checked' : ''; ?> />
                                            <label for="search-content-<?php echo h($c['code']); ?>">
                                                <?php echo h($c['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="search-actions">
                            <button type="button" id="btn-clear" class="btn btn-outline">クリア</button>
                            <button type="button" id="btn-search" class="btn btn-primary">検索</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 結果エリア -->
            <div class="result-area">
                <div class="result-header">
                    <div class="result-count">
                        検索結果: <strong id="total-count">0</strong> 件
                    </div>
                    <div class="display-options">
                        <label for="display-count">表示件数:</label>
                        <select id="display-count">
                            <?php foreach (PAGE_SIZE_OPTIONS as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $display_count == $opt ? 'selected' : ''; ?>>
                                    <?php echo $opt; ?>件
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 埋め込み型テーブル（ヘッダー固定、ボディスクロール） -->
                <div class="data-table-container">
                    <!-- ヘッダー部分（固定） -->
                    <div class="data-table-header">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-tantou sortable" data-sort="担当者名">担当者<span class="sort-icon">▼</span></th>
                                    <th class="col-shohin">商品</th>
                                    <th class="col-datetime sortable sorted" data-sort="対応開始日時">対応日時<span class="sort-icon">▼</span></th>
                                    <th class="col-content">対応内容</th>
                                    <th class="col-kokyaku sortable" data-sort="顧客名">顧客名<span class="sort-icon">▼</span></th>
                                    <th class="col-houkoku">報告内容</th>
                                    <th class="col-action">操作</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <!-- ボディ部分（スクロール可能） -->
                    <div class="data-table-body-wrapper" id="data-table-body-wrapper">
                        <table>
                            <tbody id="data-table-body">
                                <tr>
                                    <td colspan="7" class="no-data">データを読み込み中...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="paging-area" id="paging-area">
                    <div class="paging-buttons">
                        <button type="button" id="btn-first" disabled>最初</button>
                        <button type="button" id="btn-prev" disabled>前へ</button>
                        <span class="page-info" id="page-info">1 / 1 ページ</span>
                        <button type="button" id="btn-next" disabled>次へ</button>
                        <button type="button" id="btn-last" disabled>最後</button>
                    </div>
                </div>
            </div>
        </div>

        <?php include("templates/footer.php"); ?>
    </div>

    <!-- 入力ダイアログ -->
    <?php include("support_dialog.php"); ?>

    <!-- JavaScript: グローバル変数とイベント処理 -->
    <script type="text/javascript">
        // グローバル変数
        var currentPage = <?php echo $current_page; ?>;
        var totalPages = 1;
        var sortColumn = '<?php echo h($sort_params['column']); ?>';
        var sortOrder = '<?php echo h($sort_params['order']); ?>';

        // ダイアログ用変数（Phase 07追加）
        var currentUserId = <?php echo getCurrentUserId() ? getCurrentUserId() : 'null'; ?>;
        var isAdminUser = <?php echo isAdmin() ? 'true' : 'false'; ?>;

        $(function() {
            // 初期データ読み込み
            loadData(currentPage);

            // 報告内容プレビューツールチップ初期化
            initHoukokuTooltip();

            // アコーディオン開閉
            $('#search-accordion-header').on('click', function() {
                var $content = $('#search-accordion-content');
                var $icon = $(this).find('.accordion-icon');
                $content.toggleClass('open');
                $icon.toggleClass('open');
            });

            // 検索ボタン
            $('#btn-search').on('click', function() {
                currentPage = 1;
                loadData(1);
            });

            // クリアボタン
            $('#btn-clear').on('click', function() {
                $('#search-form input[type="text"]').val('');
                $('#search-form input[type="date"]').val('');
                $('#search-form select').val('');
                $('#search-form input[type="checkbox"]').prop('checked', false);
                // 部門クリア時に作業担当の絞込を解除
                filterTantouByBumon('');
            });

            // 部門プルダウン変更時に作業担当を絞り込み
            $('#search-bumon').on('change', function() {
                var bumonCode = $(this).val();
                filterTantouByBumon(bumonCode);
            });

            // 表示件数変更
            $('#display-count').on('change', function() {
                currentPage = 1;
                loadData(1);
            });

            // ページング
            $('#btn-first').on('click', function() {
                if (!$(this).prop('disabled')) loadData(1);
            });
            $('#btn-prev').on('click', function() {
                if (!$(this).prop('disabled')) loadData(currentPage - 1);
            });
            $('#btn-next').on('click', function() {
                if (!$(this).prop('disabled')) loadData(currentPage + 1);
            });
            $('#btn-last').on('click', function() {
                if (!$(this).prop('disabled')) loadData(totalPages);
            });

            // ソート
            $('.data-table-header th.sortable').on('click', function() {
                var column = $(this).data('sort');
                if (sortColumn === column) {
                    sortOrder = (sortOrder === 'ASC') ? 'DESC' : 'ASC';
                } else {
                    sortColumn = column;
                    sortOrder = 'DESC';
                }
                updateSortIcons();
                loadData(1);
            });

            // 編集ボタン（動的要素）
            $(document).on('click', '.btn-edit', function(e) {
                e.stopPropagation();
                var seqno = $(this).data('seqno');
                openEditDialog(seqno);
            });

            // 削除ボタン（動的要素：管理者のみ表示）
            $(document).on('click', '.btn-delete', function(e) {
                e.stopPropagation();
                var seqno = $(this).data('seqno');
                if (!seqno) {
                    return;
                }

                if (!confirm('このサポート報告書を削除してもよろしいですか？\nこの操作は取り消せません。')) {
                    return;
                }

                AppLoading.show();
                AppAjax.post('support_ajax02.php', {
                    action: 'delete',
                    seqno: seqno
                }, function(response) {
                    AppLoading.hide();

                    if (response.status === 'success') {
                        AppMessage.showSuccess(response.message || '削除しました。');
                        loadData(currentPage);
                    } else {
                        AppMessage.showError(response.message || '削除に失敗しました。');
                    }
                }, function(message) {
                    AppLoading.hide();
                    AppMessage.showError(message);
                });
            });

            // テーブル行クリックで編集（Phase 07追加）
            $(document).on('click', '#data-table-body tr[data-seqno]', function() {
                var seqno = $(this).data('seqno');
                if (seqno) {
                    openEditDialog(seqno);
                }
            });

            // 新規入力ボタン
            $('#btn-new').on('click', function(e) {
                e.preventDefault();
                openNewDialog();
            });

            // Excel出力ボタン
            $('#btn-excel').on('click', function(e) {
                e.preventDefault();
                exportExcel();
            });

            // マスタ設定ボタン（モーダル表示）
            $('#btn-master-product').on('click', function(e) {
                e.preventDefault();
                openMasterModal('master_shohin.php', '商品マスタ設定');
            });

            $('#btn-master-category').on('click', function(e) {
                e.preventDefault();
                openMasterModal('master_kubun.php', '対応区分マスタ設定');
            });

            $('#btn-master-content').on('click', function(e) {
                e.preventDefault();
                openMasterModal('master_content.php', '対応内容項目マスタ設定');
            });

            $('#btn-master-template').on('click', function(e) {
                e.preventDefault();
                openMasterModal('master_teikei.php', '定型文マスタ設定');
            });

            // Enterキーで検索
            $('#search-form input[type="text"], #search-form input[type="date"]').on('keydown', function(e) {
                if (e.keyCode === 13) {
                    e.preventDefault();
                    $('#btn-search').click();
                }
            });
        });

        /**
         * データ読み込み
         */
        function loadData(page) {
            var params = {
                action: 'search',
                bumon: $('#search-bumon').val(),
                tantou: $('#search-tantou').val(),
                kokyaku: $('#search-kokyaku').val(),
                shohin: $('#search-shohin').val(),
                kubun: $('#search-kubun').val(),
                date_from: $('#search-date-from').val(),
                date_to: $('#search-date-to').val(),
                keyword: $('#search-keyword').val(),
                content: [],
                page: page,
                limit: $('#display-count').val(),
                sort_column: sortColumn,
                sort_order: sortOrder
            };

            // 対応内容チェックボックス
            $('input[name="search_content[]"]:checked').each(function() {
                params.content.push($(this).val());
            });

            AppAjax.post('support_ajax01.php', params, function(response) {
                if (response.status === 'success') {
                    renderTable(response.data);
                    currentPage = response.data.page;
                    totalPages = response.data.total_pages;
                    updatePaging();
                } else {
                    AppMessage.showError(response.message || '検索に失敗しました。');
                }
            }, function(message) {
                AppMessage.showError(message);
            });
        }

        /**
         * テーブル描画
         */
        function renderTable(responseData) {
            var html = '';
            var data = responseData.data;

            if (!data || data.length === 0) {
                html = '<tr><td colspan="7" class="no-data">データがありません。</td></tr>';
            } else {
                for (var i = 0; i < data.length; i++) {
                    var row = data[i];
                    html += '<tr data-seqno="' + row.seqno + '">';
                    html += '<td class="col-tantou">' + AppUtil.escapeHtml(row.tantou_name) + '</td>';
                    html += '<td class="col-shohin">' + AppUtil.escapeHtml(row.shohin_name) + '</td>';
                    html += '<td class="col-datetime">' + AppUtil.escapeHtml(row.taiou_datetime) + '</td>';
                    html += '<td class="col-content">' + AppUtil.escapeHtml(row.taiou_content) + '</td>';
                    html += '<td class="col-kokyaku">' + AppUtil.escapeHtml(row.kokyaku_name) + '</td>';
                    // 報告内容にプレビュー用data属性を追加
                    var houkokuFull = row.houkoku_full || '';
                    html += '<td class="col-houkoku houkoku-cell" data-houkoku="' + AppUtil.escapeHtml(houkokuFull).replace(/"/g, '&quot;') + '">';
                    html += '<span class="houkoku-text">' + AppUtil.escapeHtml(row.houkoku_short || row.houkoku) + '</span>';
                    html += '</td>';
                    html += '<td class="col-action">';
                    html += '<button class="btn btn-sm btn-outline btn-edit" data-seqno="' + row.seqno + '">編集</button>';
                    if (typeof isAdminUser !== 'undefined' && isAdminUser) {
                        html += ' <button class="btn btn-sm btn-danger btn-delete" data-seqno="' + row.seqno + '">削除</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                }
            }

            $('#data-table-body').html(html);
            $('#total-count').text(responseData.total || 0);
        }

        /**
         * ページング更新
         */
        function updatePaging() {
            $('#btn-first').prop('disabled', currentPage <= 1);
            $('#btn-prev').prop('disabled', currentPage <= 1);
            $('#btn-next').prop('disabled', currentPage >= totalPages);
            $('#btn-last').prop('disabled', currentPage >= totalPages);
            $('#page-info').text(currentPage + ' / ' + totalPages + ' ページ');
        }

        /**
         * ソートアイコン更新
         */
        function updateSortIcons() {
            $('.data-table-header th.sortable').removeClass('sorted').find('.sort-icon').text('▼');
            $('.data-table-header th[data-sort="' + sortColumn + '"]')
                .addClass('sorted')
                .find('.sort-icon').text(sortOrder === 'ASC' ? '▲' : '▼');
        }

        /**
         * 新規ダイアログを開く（Phase 07実装）
         */
        function openNewDialog() {
            InputDialog.openNew();
        }

        /**
         * 編集ダイアログを開く（Phase 07実装）
         */
        function openEditDialog(seqno) {
            InputDialog.openEdit(seqno);
        }

        /**
         * Excel出力（Phase 08実装）
         */
        function exportExcel() {
            // 検索条件を取得してExcel出力
            var params = {
                tantou: $('#search-tantou').val(),
                kokyaku: $('#search-kokyaku').val(),
                shohin: $('#search-shohin').val(),
                kubun: $('#search-kubun').val(),
                date_from: $('#search-date-from').val(),
                date_to: $('#search-date-to').val(),
                keyword: $('#search-keyword').val(),
                content: []
            };

            // 対応内容チェックボックス
            $('input[name="search_content[]"]:checked').each(function() {
                params.content.push($(this).val());
            });

            // URLパラメータを構築
            var queryString = $.param(params);

            // ダウンロード開始
            window.location.href = 'export_excel.php?' + queryString;
        }

        /**
         * 部門に応じて作業担当の選択肢を絞り込み
         * @param {string} bumonCode 部門コード（空の場合は全て表示）
         */
        function filterTantouByBumon(bumonCode) {
            var $tantou = $('#search-tantou');
            var currentValue = $tantou.val();
            
            // 一度全ての選択肢を表示
            $tantou.find('option').show();
            
            if (bumonCode === '' || bumonCode === null) {
                // 部門が未選択の場合は全て表示
                return;
            }
            
            // 部門が選択されている場合は絞り込み
            $tantou.find('option').each(function() {
                var $opt = $(this);
                var optBumon = $opt.data('bumon');
                
                // "すべて"オプションは常に表示
                if ($opt.val() === '') {
                    return;
                }
                
                // 部門コードが一致しない場合は非表示
                if (optBumon != bumonCode) {
                    $opt.hide();
                    // 選択中の値が非表示になった場合はクリア
                    if ($opt.val() === currentValue) {
                        $tantou.val('');
                    }
                }
            });
        }

        /**
         * 報告内容プレビューツールチップの初期化
         */
        function initHoukokuTooltip() {
            $(document).on('mouseenter', '.houkoku-cell', function(e) {
                var houkokuText = $(this).data('houkoku');
                if (!houkokuText || houkokuText.length === 0) {
                    return;
                }
                
                var $tooltip = $('#houkoku-tooltip');
                $tooltip.find('.houkoku-content').text(houkokuText);
                
                // 位置計算（セルの下に表示）
                var offset = $(this).offset();
                var cellHeight = $(this).outerHeight();
                var tooltipLeft = offset.left;
                var tooltipTop = offset.top + cellHeight + 5;
                
                // 画面右端を超える場合は調整
                var tooltipWidth = 400;
                if (tooltipLeft + tooltipWidth > $(window).width()) {
                    tooltipLeft = $(window).width() - tooltipWidth - 20;
                }
                
                // 画面下端を超える場合は上に表示
                var tooltipHeight = 200;
                if (tooltipTop + tooltipHeight > $(window).height() + $(window).scrollTop()) {
                    tooltipTop = offset.top - tooltipHeight - 5;
                }
                
                $tooltip.css({
                    left: tooltipLeft + 'px',
                    top: tooltipTop + 'px'
                }).addClass('visible');
            });
            
            $(document).on('mouseleave', '.houkoku-cell', function() {
                $('#houkoku-tooltip').removeClass('visible');
            });
        }
    </script>

    <!-- 報告内容プレビューツールチップ -->
    <div id="houkoku-tooltip">
        <div class="houkoku-content"></div>
    </div>

    <!-- マスタモーダル -->
    <div id="master-modal" class="master-modal-overlay" style="display: none;">
        <div class="master-modal-container">
            <div class="master-modal-header">
                <span class="master-modal-title"></span>
            </div>
            <div class="master-modal-body">
                <iframe id="master-modal-iframe" src="" frameborder="0"></iframe>
            </div>
        </div>
    </div>

    <style>
    /* マスタモーダルスタイル */
    .master-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .master-modal-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        width: 90%;
        max-width: 1000px;
        height: 85%;
        max-height: 750px;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    .master-modal-header {
        background: #1e3a5f;
        color: #fff;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-shrink: 0;
    }
    
    .master-modal-title {
        font-size: 16px;
        font-weight: 500;
    }
    
    .master-modal-body {
        flex: 1;
        overflow: hidden;
    }
    
    #master-modal-iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    
    @media screen and (max-width: 768px) {
        .master-modal-container {
            width: 95%;
            height: 90%;
            max-width: none;
            max-height: none;
        }
    }
    </style>

    <script type="text/javascript">
    /**
     * マスタモーダルを開く
     * @param {string} url マスタ画面のURL
     * @param {string} title モーダルタイトル
     */
    function openMasterModal(url, title) {
        $('#master-modal .master-modal-title').text(title);
        $('#master-modal-iframe').attr('src', url);
        $('#master-modal').fadeIn(200);
        $('body').css('overflow', 'hidden');
    }
    
    /**
     * マスタモーダルを閉じる
     */
    function closeMasterModal() {
        $('#master-modal').fadeOut(200);
        $('#master-modal-iframe').attr('src', '');
        $('body').css('overflow', '');
    }
    
    // オーバーレイクリックで閉じる
    $(document).on('click', '.master-modal-overlay', function(e) {
        if ($(e.target).hasClass('master-modal-overlay')) {
            closeMasterModal();
        }
    });
    
    // ESCキーで閉じる
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && $('#master-modal').is(':visible')) {
            closeMasterModal();
        }
    });
    </script>
</body>

</html>