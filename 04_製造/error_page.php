<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>エラー | サポート報告書システム</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
    background: #f5f5f5;
    color: #333;
    line-height: 1.6;
}
.error-container {
    max-width: 600px;
    margin: 80px auto;
    background: #fff;
    padding: 50px 40px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}
.error-icon {
    font-size: 64px;
    color: #dc2626;
    margin-bottom: 20px;
}
h1 {
    color: #dc2626;
    font-size: 24px;
    margin-bottom: 20px;
    font-weight: 500;
}
p {
    color: #666;
    margin-bottom: 15px;
}
.error-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.btn {
    display: inline-block;
    padding: 12px 30px;
    background: #2563eb;
    color: #fff;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    transition: background 0.3s;
}
.btn:hover {
    background: #1d4ed8;
}
.btn-secondary {
    background: #6b7280;
    margin-left: 10px;
}
.btn-secondary:hover {
    background: #4b5563;
}
</style>
</head>
<body>
<div class="error-container">
    <div class="error-icon">⚠</div>
    <h1>エラーが発生しました</h1>
    <p>申し訳ございません。システムエラーが発生しました。</p>
    <p>しばらく待ってから再度お試しください。</p>
    <p>問題が続く場合は、システム管理者にご連絡ください。</p>
    <div class="error-actions">
        <a href="support_main.php" class="btn">メイン画面に戻る</a>
        <a href="login.php" class="btn btn-secondary">ログイン画面へ</a>
    </div>
</div>
</body>
</html>
