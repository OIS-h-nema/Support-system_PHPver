# jQuery配置手順

## 概要
本システムはjQuery 1.11.3を使用しています。
デプロイ前に、以下の手順でjQueryファイルを配置してください。

## 配置方法

### 方法1: ローカルファイルとして配置（推奨）

1. 以下のURLからjQuery 1.11.3をダウンロード:
   - https://code.jquery.com/jquery-1.11.3.min.js
   - または https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js

2. ダウンロードしたファイルを以下に配置:
   ```
   04_製造/js/jquery-1.11.3.min.js
   ```

3. デプロイ時は以下にコピー:
   ```
   検証環境: \\LPG-NEMA\C\inetpub\wwwroot\support-system\js\jquery-1.11.3.min.js
   本番環境: \\DEV-SE\C\inetpub\wwwroot\support-system\js\jquery-1.11.3.min.js
   ```

### 方法2: CDNを使用

社内ネットワークからCDNにアクセス可能な場合は、PHPファイルのjQuery読み込み部分を以下に変更:

```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
```

※社内ネットワークのセキュリティポリシーによりCDNアクセスが制限されている場合は、方法1を使用してください。

## 対象ファイル

以下のファイルでjQueryを読み込んでいます:
- login.php
- support_main.php
- master_shohin.php
- master_kubun.php
- master_content.php
- master_teikei.php

## 確認方法

ブラウザの開発者ツール（F12）のコンソールで以下のエラーが出なければ正常です:
- `$ is not defined`
- `jQuery is not defined`
- `Failed to load resource: jquery-1.11.3.min.js`

---
作成日: 2025-11-26
