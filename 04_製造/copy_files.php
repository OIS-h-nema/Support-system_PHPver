<?php
/**
 * ファイルコピースクリプト
 * 検証環境から製造ディレクトリにファイルをコピーする
 */

// コピー元とコピー先の定義
$copies = array(
    array(
        'src' => 'C:\\inetpub\\wwwroot\\support-system\\support_main.php',
        'dst' => 'D:\\#M\\PG_DATA\\OIS社内システム\\PG_DATA\\サポート報告書WEB\\04_製造\\support_main.php'
    )
);

foreach ($copies as $copy) {
    $src = $copy['src'];
    $dst = $copy['dst'];
    
    if (file_exists($src)) {
        if (copy($src, $dst)) {
            echo "OK: {$src} -> {$dst}\n";
        } else {
            echo "NG: Failed to copy {$src}\n";
        }
    } else {
        echo "NG: Source not found: {$src}\n";
    }
}

echo "\nコピー完了\n";
