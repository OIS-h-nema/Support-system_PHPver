<?php
/**
 * ファイル名: __opcache.php
 * 機能概要: OPCache 状態表示（IIS 環境切り分け用）
 * 注意: デバッグ専用。アクセス制御を適宜行うこと。
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!function_exists('opcache_get_status')) {
    echo json_encode([
        'status' => 'unavailable',
        'message' => 'OPcache extension is not enabled.',
        'commit' => APP_COMMIT_SHA
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$status = opcache_get_status(false);

echo json_encode([
    'status' => 'ok',
    'commit' => APP_COMMIT_SHA,
    'opcache' => [
        'enabled' => $status['opcache_enabled'] ?? null,
        'cache_full' => $status['cache_full'] ?? null,
        'restart_pending' => $status['restart_pending'] ?? null,
        'restart_in_progress' => $status['restart_in_progress'] ?? null,
        'memory_usage' => $status['memory_usage'] ?? null,
        'opcache_statistics' => $status['opcache_statistics'] ?? null,
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
