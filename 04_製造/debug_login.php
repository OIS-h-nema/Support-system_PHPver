<?php
/**
 * ログイン認証デバッグスクリプト
 * 問題調査用
 */

error_reporting(E_ALL);
ini_set('display_errors', 'On');

echo "<h2>Login Debug Script</h2>";
echo "<pre>";

// データベース接続
$server = 'dev-se02\\SQL22';
$database = 'SUPPORTDB';
$user = 'sa';
$password = 'OIS8973113fmv';

echo "=== Database Connection ===\n";
echo "Server: $server\n";
echo "Database: $database\n\n";

try {
    $dsn = "sqlsrv:server=$server;Database=$database";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection: SUCCESS\n\n";
} catch (PDOException $e) {
    echo "Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit;
}

// テーブル存在確認
echo "=== Table Check ===\n";
$tables = array('SQL_作業担当', 'SQL_部門', 'SQL_顧客');
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT TOP 1 * FROM $table");
        echo "$table: EXISTS\n";
    } catch (PDOException $e) {
        echo "$table: NOT FOUND - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// SQL_作業担当のカラム確認
echo "=== SQL_作業担当 Table Structure ===\n";
try {
    $stmt = $pdo->query("SELECT TOP 1 * FROM SQL_作業担当");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Columns:\n";
        foreach (array_keys($row) as $col) {
            echo "  - $col\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 担当者コード47のデータ確認
echo "=== Check User Code 47 ===\n";
try {
    // エイリアス使用
    $sql = "SELECT 
                担当者コード AS tantou_code, 
                担当者名 AS tantou_name, 
                部門コード AS bumon_code,
                パスワード AS pass
            FROM SQL_作業担当
            WHERE 担当者コード = 47";
    $stmt = $pdo->query($sql);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User Found:\n";
        echo "  tantou_code: " . $user['tantou_code'] . "\n";
        echo "  tantou_name: " . $user['tantou_name'] . "\n";
        echo "  bumon_code: " . $user['bumon_code'] . "\n";
        echo "  password (stored): " . $user['pass'] . "\n";
        echo "  password (expected): 3113\n";
        echo "  password match: " . ($user['pass'] === '3113' ? 'YES' : 'NO') . "\n";
        echo "  password match (trimmed): " . (trim($user['pass']) === '3113' ? 'YES' : 'NO') . "\n";
        echo "  password length: " . strlen($user['pass']) . "\n";
        echo "  password hex: " . bin2hex($user['pass']) . "\n";
    } else {
        echo "User NOT FOUND\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 実際の認証クエリをテスト
echo "=== Authentication Test ===\n";
$test_user = 47;
$test_pass = '3113';

try {
    $sql = "SELECT 
                担当者コード AS tantou_code, 
                担当者名 AS tantou_name, 
                部門コード AS bumon_code
            FROM SQL_作業担当
            WHERE 担当者コード = ? AND パスワード = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array($test_user, $test_pass));
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Authentication: SUCCESS\n";
        echo "  User: " . $result['tantou_name'] . "\n";
    } else {
        echo "Authentication: FAILED\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 権限レベルカラムの確認
echo "=== Check 権限レベル Column ===\n";
try {
    $sql = "SELECT 
                担当者コード,
                COALESCE(権限レベル, 1) AS auth_level
            FROM SQL_作業担当
            WHERE 担当者コード = 47";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "auth_level: " . $result['auth_level'] . "\n";
    }
} catch (PDOException $e) {
    echo "権限レベル column may not exist: " . $e->getMessage() . "\n";
    
    // カラムなしで再試行
    echo "\nRetrying without 権限レベル...\n";
    try {
        $sql = "SELECT 
                    担当者コード AS tantou_code, 
                    担当者名 AS tantou_name, 
                    部門コード AS bumon_code
                FROM SQL_作業担当
                WHERE 担当者コード = ? AND パスワード = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(47, '3113'));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "Auth without 権限レベル: SUCCESS\n";
        } else {
            echo "Auth without 権限レベル: FAILED\n";
        }
    } catch (PDOException $e2) {
        echo "Error: " . $e2->getMessage() . "\n";
    }
}

echo "</pre>";
?>
