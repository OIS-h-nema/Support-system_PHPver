<?php
/**
 * ファイル名: logout.php
 * 機能概要: ログアウト処理
 * 作成日: 2025-11-25
 * 作成者: Claude AI
 * 
 * 修正履歴:
 * 2025-11-25 新規作成（Phase 06）
 */

// 設定ファイル読み込み
require_once("includes/config.php");
require_once("includes/auth.php");

// ログアウト処理
logout();

// ログイン画面へリダイレクト
header('Location: login.php?logout=1');
exit;
