# manatubbs

manatubbs はスレッド型/ツリー型の掲示板です。
プロジェクト管理などに利用することを目的としています。

 - [Repo] https://github.com/kujirahand/manatubbs
 - [Web] https://kujirahand.com/wiki/index.php?manatubbs

## 使用方法

 - 1. ダウンロードしたファイルを解凍
 - 2. setting-org.ini.phpをコピーして、setting-user.ini.phpという名前で保存
 - 3. メモ帳で開いて設定を書き換え
 - 4. 転送してパーミッションを変更
 - 5. 転送先へブラウザでアクセス

## パーミッションの変更について

 - db ディレクトリと attach ディレクトリのパーミッションを 777 に変更
 - *.php ファイルのパーミッションを 777 に変更
 - その他 644 になっていれば動く

## ライセンス

改編自由・再配布自由

## 修正履歴

 - [こちら](https://github.com/kujirahand/manatubbs/commits/master)を参照

### それ以前の履歴は以下:

 - 2014-08-04 r33
   - SQlite2 => Sqlite3に対応

 - 2009-09-23 r25
   - 設定ファイルの記述方法を微修正。

 - 2008-10-06 old r253
   - ログ書き込み後の画面にあったセキュリティの脆弱性を修正。

