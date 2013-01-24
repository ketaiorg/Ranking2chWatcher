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
define('TMP_FILE', '/tmp/Ranking2chWatcher_%%HASH%%');				// テンポラリパス名

// 実行
$r2ch = new Ranking2chWatcher();
$r2ch->run();



/**
 * Ranking_2ch_Watcher
 */
class Ranking2chWatcher
{
	/**
	 * 処理実行
	 */
	public function run()
	{
		// テンポラリファイルの読み込み
		$file_name = strtr(TMP_FILE, array('%%HASH%%' => md5(API_URL . BOARD_NAME)));
		$file_data = $this->readTmpFile($file_name);
		if (is_null($file_data)) {
			// テンポラリファイルが読めなかった場合は、初期値をセット
			$file_data = json_decode();
			$file_data->time = time();
			$file_data->value = 0;
		}

		// APIからデータを取得
		$thread_list = $this->getThreadList($file_data->time);
		if (is_null($thread_list)) {
			// 前回取得時と比べて変化がないので、キャッシュデータを利用
			$forces = $file_data->value;
		} else {
			// キーワードから対象スレッドの勢いを調べる
			$forces = $this->getForces($thread_list, SEARCH_KEYWORD);

			// テンポラリファイルへ最新の情報を保存
			$file_data->time = time();
			$file_data->value = $forces;
			if (!$this->putTmpFile($file_name, json_encode($file_data))) {
				// ファイル出力エラーの場合
				exit(1);
			}
		}

		// 出力
		echo $forces;
	}



	/**
	 * テンポラリファイル内のデータを取得する
	 * @param string $file_name テンポラリファイル名
	 * @return class 読み込んだファイル内のデータを格納したJSONクラス
	 */
	protected function readTmpFile($file_name)
	{
		if (file_exists($file_name)) {
			// 既にファイルがある場合
			$ret_data = json_decode(file_get_contents($file_name));
		} else {
			// ファイルが無い場合
			$ret_data = null;
		}

		return $ret_data;
	}



	/**
	 * テンポラリファイルへのデータ出力
	 * @param string $file_name テンポラリファイル名
	 * @param string $data 出力データ
	 * @return bool 成否
	 */
	protected function putTmpFile($file_name, $data)
	{
		// ファイルに出力
		$fp = fopen($file_name, 'w');		// 書き込みモードでファイルをオープン
		if ((empty($fp))) {
			// 書き込みエラー
			return false;
		}

		// データの書き出し
		flock($fp, LOCK_EX);
		fputs($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);

		return true;
	}



	/**
	 * APIからデータを取得する
	 * @param int $last_access 最後にアクセスを行ったUNIXタイム
	 * @return string APIから取得した文字列データ、ただしステータスコードが304の場合はnullが返る
	 */
	protected function getThreadList($last_access)
	{
		// cURLを利用
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, API_URL . BOARD_NAME);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEVALUE, $last_access);
		curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$ret = curl_exec($ch);

		if (false === $ret) {
			// curlに失敗した（通信エラー）
			curl_close($ch);
			exit(1);
		}

		// ステータスコードを取得
		$header = curl_getinfo($ch);
		curl_close($ch);
		$status_code = $header['http_code'];

		// ステータスコードで分岐
		if ('304' == $status_code) {
			// 304 Not Modifiedの場合、キャッシュデータを利用するためnullを返す
			$ret = null;
		} elseif ('200' == $status_code) {
			// 200 OKの場合、データを加工
			$ret = substr($ret, strlen('callback('));		// 先頭の不要部分をカット
			$ret = substr($ret, 0, -1 * strlen(');'));		// 最後の不要部分をカット
		} else {
			// その他のステータスコード異常
			exit(1);
		}

		return $ret;
	}



	/**
	 * キーワードによる抽出を行い勢いを返す
	 * @param string $thread_list APIが返したJSON形式の文字列
	 * @param string $keyword 検索キーワード（正規表現形式）
	 * @return int 勢い
	 */
	protected function getForces($thread_list, $keyword)
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

