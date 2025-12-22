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
</style>
