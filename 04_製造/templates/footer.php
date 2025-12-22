<?php
/**
 * ファイル名: footer.php
 * 機能概要: フッターテンプレート
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 */

// このファイルは他のPHPから include される前提
?>
<!-- フッター -->
<footer class="footer">
    <div class="footer-inner">
        &copy; <?php echo date('Y'); ?> OIS &nbsp;|&nbsp; Version <?php echo h(SYSTEM_VERSION); ?>
    </div>
    <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
    <div class="commit-badge" aria-label="current commit">
        SHA: <?php echo h(substr(APP_COMMIT_SHA, 0, 12)); ?>
    </div>
    <?php endif; ?>
</footer>

<!-- ローディングオーバーレイ -->
<div class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<style>
/* フッタースタイル */
.footer {
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    padding: 15px 20px;
    text-align: center;
    color: #6b7280;
    font-size: 12px;
    margin-top: auto;
}

.footer-inner {
    max-width: 1200px;
    margin: 0 auto;
}

.commit-badge {
    position: fixed;
    right: 10px;
    bottom: 10px;
    background: rgba(30, 58, 95, 0.85);
    color: #fff;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
    z-index: 9999;
    letter-spacing: 0.3px;
}
</style>
