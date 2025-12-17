<?php
/**
 * ファイル名: support_dialog.php
 * 機能概要: サポート報告書入力ダイアログ
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 07）
 * 2025-12-10 tabindex="-1"を追加（フォーカス制御対応）
 * 
 * 使用方法:
 * support_main.phpでincludeして使用
 */

// このファイルは単独では動作しません
// support_main.phpからincludeされることを前提としています
?>

<!-- ================================================
     サポート報告書入力ダイアログ
     ================================================ -->
<div class="modal-overlay" id="input-dialog-overlay" tabindex="-1">
    <div class="modal-content input-dialog">
        <div class="modal-header">
            <h3 class="modal-title" id="dialog-title">サポート報告書入力</h3>
            <button type="button" class="modal-close" id="btn-dialog-close">&times;</button>
        </div>
        <div class="modal-body">
            <!-- エラー表示エリア -->
            <div class="error-box" id="dialog-error-box" style="display: none;">
            </div>
            
            <form id="input-form">
                <!-- hidden項目 -->
                <input type="hidden" id="edit-seqno" name="seqno" value="" />
                <input type="hidden" id="edit-mode" name="mode" value="new" />
                
                <table class="input-form-table">
                    <!-- 顧客情報 -->
                    <tr>
                        <th>顧客コード<span class="required">*</span></th>
                        <td>
                            <div class="field-group">
                                <input type="text" id="kokyaku-code" name="kokyaku_code" 
                                       class="input-sm numeric-only" maxlength="10" />
                                <button type="button" id="btn-kokyaku-search" class="btn btn-sm btn-outline">検索</button>
                                <span class="kokyaku-name-display" id="kokyaku-name-display"></span>
                            </div>
                            <input type="hidden" id="kokyaku-name" name="kokyaku_name" value="" />
                        </td>
                    </tr>
                    
                    <!-- 顧客担当者名 -->
                    <tr>
                        <th>顧客担当者名</th>
                        <td>
                            <input type="text" id="kokyaku-tanto" name="kokyaku_tanto" 
                                   class="input-md" maxlength="50" />
                        </td>
                    </tr>
                    
                    <!-- 対応日時 -->
                    <tr>
                        <th>対応日時<span class="required">*</span></th>
                        <td>
                            <div class="field-group">
                                <input type="date" id="taiou-date" name="taiou_date" class="input-sm" />
                                <input type="time" id="taiou-time-start" name="taiou_time_start" class="input-sm" step="300" />
                                <span class="separator">～</span>
                                <input type="time" id="taiou-time-end" name="taiou_time_end" class="input-sm" step="300" />
                            </div>
                        </td>
                    </tr>
                    
                    <!-- 商品選択 -->
                    <tr>
                        <th>商品</th>
                        <td>
                            <div class="product-select-group">
                                <div class="product-select-row">
                                    <span class="product-label">商品1:</span>
                                    <select id="shohin-code1" name="shohin_code1" class="input-md">
                                        <option value="">選択してください</option>
                                        <?php foreach ($shohin_list as $s): ?>
                                        <option value="<?php echo h($s['code']); ?>">
                                            <?php echo h($s['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="product-select-row">
                                    <span class="product-label">商品2:</span>
                                    <select id="shohin-code2" name="shohin_code2" class="input-md">
                                        <option value="">選択してください</option>
                                        <?php foreach ($shohin_list as $s): ?>
                                        <option value="<?php echo h($s['code']); ?>">
                                            <?php echo h($s['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="product-select-row">
                                    <span class="product-label">商品3:</span>
                                    <select id="shohin-code3" name="shohin_code3" class="input-md">
                                        <option value="">選択してください</option>
                                        <?php foreach ($shohin_list as $s): ?>
                                        <option value="<?php echo h($s['code']); ?>">
                                            <?php echo h($s['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- 作業担当 -->
                    <tr>
                        <th>作業担当<span class="required">*</span></th>
                        <td>
                            <select id="tantou-code" name="tantou_code" class="input-md">
                                <option value="">選択してください</option>
                                <?php foreach ($tantou_list as $t): ?>
                                <option value="<?php echo h($t['code']); ?>">
                                    <?php echo h($t['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- 対応区分 -->
                    <tr>
                        <th>対応区分</th>
                        <td>
                            <select id="kubun-code" name="kubun_code" class="input-md">
                                <option value="">選択してください</option>
                                <?php foreach ($kubun_list as $k): ?>
                                <option value="<?php echo h($k['code']); ?>">
                                    <?php echo h($k['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- 対応内容チェックボックス -->
                    <tr>
                        <th>対応内容</th>
                        <td>
                            <div class="content-checkbox-group" id="content-checkbox-group">
                                <?php foreach ($content_list as $c): ?>
                                <div class="content-checkbox-item">
                                    <input type="checkbox" 
                                           id="taiou-flag-<?php echo h($c['code']); ?>"
                                           name="taiou_flag[]"
                                           value="<?php echo h($c['code']); ?>" />
                                    <label for="taiou-flag-<?php echo h($c['code']); ?>">
                                        <?php echo h($c['name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- 報告内容 -->
                    <tr>
                        <th>報告内容<span class="required">*</span></th>
                        <td>
                            <div class="template-button-area" id="template-button-area">
                                <button type="button" id="btn-template" class="btn btn-sm btn-outline">定型文選択</button>
                            </div>
                            <textarea id="houkoku-naiyo" name="houkoku_naiyo" 
                                      class="houkoku-textarea" placeholder="報告内容を入力してください"></textarea>
                        </td>
                    </tr>
                    
                    <!-- 引継担当 -->
                    <tr>
                        <th>引継担当</th>
                        <td>
                            <select id="hikitsugi-code" name="hikitsugi_code" class="input-md">
                                <option value="">選択してください</option>
                                <?php foreach ($tantou_list as $t): ?>
                                <option value="<?php echo h($t['code']); ?>">
                                    <?php echo h($t['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <div class="modal-footer-left">
                <span class="update-info" id="update-info"></span>
            </div>
            <div class="modal-footer-right">
                <button type="button" id="btn-delete" class="btn btn-danger" style="display: none;">削除</button>
                <button type="button" id="btn-save" class="btn btn-primary">保存</button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================
     顧客検索サブモーダル
     ================================================ -->
<div class="modal-overlay sub-modal" id="kokyaku-search-overlay">
    <div class="modal-content search-dialog">
        <div class="modal-header">
            <h3 class="modal-title">顧客検索</h3>
            <button type="button" class="modal-close" id="btn-kokyaku-search-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="search-input-area">
                <input type="text" id="kokyaku-search-keyword" placeholder="顧客名で検索" />
                <button type="button" id="btn-kokyaku-search-exec" class="btn btn-primary">検索</button>
            </div>
            <div class="search-result-list" id="kokyaku-search-result">
                <div class="search-no-result">検索キーワードを入力してください</div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="modal-footer-right">
                <button type="button" id="btn-kokyaku-search-select" class="btn btn-primary" disabled>選択</button>
            </div>
        </div>
    </div>
</div>

<!-- ================================================
     定型文選択サブモーダル
     ================================================ -->
<div class="modal-overlay sub-modal" id="template-search-overlay">
    <div class="modal-content template-dialog">
        <div class="modal-header">
            <h3 class="modal-title">定型文選択</h3>
            <button type="button" class="modal-close" id="btn-template-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="template-list" id="template-list">
                <!-- 定型文リストは動的に生成 -->
            </div>
        </div>
    </div>
</div>
