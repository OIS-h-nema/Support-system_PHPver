<?php
/**
 * ファイル名: header.php
 * 機能概要: ヘッダーテンプレート
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 */

// このファイルは他のPHPから include される前提
// config.php, auth.php, functions.php は呼び出し元で読み込み済みとする
?>
<!-- ヘッダー -->
<header class="header">
    <div class="header-inner">
        <div class="header-title">
            <?php echo h(SYSTEM_NAME); ?> <span class="version">ver.<?php echo h(SYSTEM_VERSION); ?></span>
        </div>
        <div class="header-user">
            <span class="user-info">ログイン中: <?php echo h(getCurrentUserName()); ?></span>
            <a href="logout.php" class="btn btn-sm btn-outline logout-btn" onclick="return confirm('ログアウトしますか？');">ログアウト</a>
        </div>
    </div>
</header>

<!-- メニューナビゲーション -->
<nav class="nav">
    <div class="nav-inner">
        <a href="#" id="btn-new" class="nav-item">新規入力</a>
        <a href="#" id="btn-master-product" class="nav-item">商品</a>
        <a href="#" id="btn-master-category" class="nav-item">対応区分</a>
        <a href="#" id="btn-master-content" class="nav-item">対応内容</a>
        <a href="#" id="btn-master-template" class="nav-item">定型文</a>
        <a href="#" id="btn-excel" class="nav-item nav-right">EXCEL出力</a>
    </div>
</nav>

<style>
/* ヘッダー追加スタイル */
.header .version {
    font-size: 12px;
    color: rgba(255,255,255,0.7);
    margin-left: 10px;
}

.header .user-info {
    margin-right: 15px;
}

.header .logout-btn {
    color: #fff;
    border-color: rgba(255,255,255,0.5);
}

.header .logout-btn:hover {
    background-color: rgba(255,255,255,0.1);
    text-decoration: none;
}

/* ナビゲーション追加スタイル */
.nav-inner {
    position: relative;
}

.nav-item.nav-right {
    position: absolute;
    right: 0;
}

/* SP向けハンバーガーメニュー対応（将来用） */
@media screen and (max-width: 767px) {
    .header-inner {
        flex-direction: column;
        gap: 10px;
    }
    
    .header-title {
        font-size: 16px;
    }
    
    .header .version {
        display: none;
    }
    
    .nav-inner {
        flex-wrap: wrap;
    }
    
    .nav-item {
        padding: 10px 15px;
        font-size: 13px;
    }
    
    .nav-item.nav-right {
        position: static;
    }
}
</style>
