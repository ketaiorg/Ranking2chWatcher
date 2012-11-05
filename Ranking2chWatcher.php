#!/usr/bin/php
<?php
/* vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Ranking2chWatcher
 * 2chのスレッドの勢いを監視するためのスクリプト
 * 2ch-ranking.netで公開されているAPIからキーワードで指定したスレッドの勢いを出力する
 *
 * @package		Ranking2chWatcher
 * @author		松井 健太郎 (Kentaro Matsui) <info@ke-tai.org>
 * @copyright	ke-tai.org
 * @license		BSD
 * @see			http://2ch-ranking.net/api.html
 **/

// 定義
define('BOARD_NAME', 'gameswf');									// 板の名前
define('SEARCH_KEYWORD', '/^.*ブラウザ.*part.*$/');					// 検索キーワードを正規表現で定義（通常はタイトル名を定義）
define('API_URL', 'http://2ch-ranking.net/ranking.json?board=');	// APIのURL

// 実行
$r2ch = new Ranking_2ch_Watcher();
$r2ch->run();
exit(0);



/**
 * Ranking_2ch_Watcher
 */
class Ranking_2ch_Watcher
{
	/**
	 * 処理実行
	 */
	function run()
	{
		// APIからデータを取得
		$thread_list = $this->getThreadList();

		// キーワードから対象スレッドの勢いを調べる
		$forces = $this->getForces($thread_list, SEARCH_KEYWORD);

		// 出力
		echo $forces;
	}



	/**
	 * APIからデータを取得する
	 */
	function getThreadList()
	{
		// cURLを利用
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, API_URL . BOARD_NAME);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($ch);
		curl_close($ch);

		if (false === $ret) {
			// curlに失敗した（通信エラー）
			exit(1);
		}

		// データを加工
		$ret = substr($ret, strlen('callback('));		// 先頭の不要部分をカット
		$ret = substr($ret, 0, -1 * strlen(');'));		// 最後の不要部分をカット

		return $ret;
	}



	/**
	 * キーワードによる抽出を行い勢いを返す
	 * @param string $thread_list APIが返したJSON形式の文字列
	 * @param string $keyword 検索キーワード（正規表現形式）
	 * @return int 勢い
	 */
	function getForces($thread_list, $keyword)
	{
		$thread_arr = json_decode($thread_list, true);
		if (null === $thread_arr) {
			// json_decodeに失敗した
			exit(1);
		}

		foreach ($thread_arr as $row) {
			if (preg_match($keyword, $row['title'])) {
				// 一番最初にマッチしたものが最も勢いがあるのでそれを返す
				return $row['ikioi'];
			}
		}

		// マッチしなかった
		return 0;
	}



}

