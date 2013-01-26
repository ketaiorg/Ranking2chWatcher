# Ranking2chWatcher

* @author 松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
* @copyright ke-tai.org
* @license BSD

----

## 概要
* 2chのスレッドの勢いを監視するためのスクリプトです。
* 2ch-ranking.netで公開されているAPIからキーワードで指定したスレッドの勢いを出力します。
* Cactiなどでグラフ化し監視するような使い方を想定しています。

## 動作について
* ドキュメントやtestファイルなどが同梱されていますが、プログラムの本体は1ファイルのみです。
* 「Ranking2chWatcher.php」をコマンドラインから実行することで動作します。
* PHP5.1以降で動作するはずです。（動作確認はバージョン5.3.6 on Ubuntuで行っています）
* 実行ユーザが/tmp以下にテンポラリファイルを作成できる必要があります。

## 設定について
* Ranking2chWatcher.phpを修正し、定数「BOARD_NAME」「SEARCH_KEYWORD」を設定してください。
* 期待通りの動作をしない場合は、定数「DEBUG_MODE」をtrueに設定して、コマンドラインから実行を行い、エラーを確認してください。

## リンク
- http://2ch-ranking.net/
- http://2ch-ranking.net/api.html
- https://github.com/ketaiorg/Ranking2chWatcher/
- http://www.infiniteloop.co.jp/blog/2012/11/2chcacti/
