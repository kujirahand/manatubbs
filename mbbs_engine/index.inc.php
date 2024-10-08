<?php
//----------------------------------------------------------------------
// manatubbs (main library)
//----------------------------------------------------------------------
define("MBBS_VER", "1.59");
$mbbs['script_name'] = 'index.php'; // force change setting
//----------------------------------------------------------------------
// ライブラリの取り込み
require_once "inc/mbbs_lib.inc.php";
require_once "inc/mbbs_db.inc.php";
require_once "inc/mbbs_form.inc.php";
//----------------------------------------------------------------------
// 初期設定の取り込み
require_once "setting-check.inc.php";
//----------------------------------------------------------------------
// セッションを開始する
session_start();
// ログインを利用する場合
if (isset($mbbs["use.login"]) && $mbbs["use.login"]) {
  if (!m_is_login()) {
    if (m_param("m", "") == 'resource') {
      m_mode__resource(); exit;
    }
    m_check_login();
  }
  $mbbs['menubar'][] = array('label'=>'ログアウト', 'link'=>m_url('logout'));
}

//----------------------------------------------------------------------
// データベースの初期化処理
m_initDB();

//----------------------------------------------------------------------
// メインコントローラー
// パラメータの取得、m が省略されたら all をセットする
$mode = m_param("m", "all");
// m の値によって、実行する関数名を特定する
$ctrl_mode = "m_mode__{$mode}";
// 関数が実行可能か調べる
if (is_callable($ctrl_mode)) {
    call_user_func($ctrl_mode);// 関数を実行する
} else {
    $mode_ = htmlspecialchars($mode);
    $top = "./".m_info("script_name");
    m_show_error("<p>パラメータ【{$mode_}】は利用できません。".
        "URLを確認してください。</p>".
        "<p><a href='{$top}'>→トップへ</a></p>");
}

//----------------------------------------------------------------------
// コントローラ
//----------------------------------------------------------------------
function m_mode__resource()
{
    $f = m_param('f', '');
    if ($f == '') {
      echo "no file"; return;
    }
    $f = str_replace('..', '__', $f);
    $path = __DIR__."/resource/{$f}";
    if (!file_exists($path)) {
      echo "file not found"; return;
    }
    if (!preg_match('#\.([a-z]+)$#', $f, $m)) {
      echo "bad file name"; return;
    }
    $ext = $m[1];
    if ($ext === 'css') {
        header('content-type: text/css; charset=utf-8');
    } else if ($ext === 'png') {
        header('content-type: image/png');
    } else if ($ext === 'jpg' || $ext === 'jpeg') {
      header('content-type: text/css; charset=utf-8');
    } else {
      echo "The resource type is not allowed.";
      return;
    }
    echo @file_get_contents($path);
}

function m_mode__all()
{
    $view = m_info("firstview", "threads");
    if ($view == "threads") {
        m_mode__threads();
    }
    else {
        m_mode__tree();
    }
}

function m_mode__threads()
{
	$rlink = m_link(array("m=mikaiketu"));
    __m_mode__show_threads(
    	"スレッド一覧を表示中(<a href='$rlink'>→未解決のみ表示</a>):",
    	false, "threads");
}

function m_mode__mikaiketu()
{
	$rlink = m_link(array("m=threads"));
	__m_mode__show_threads(
		"未解決のタスクを表示中(<a href='$rlink'>→全て表示</a>):",
		"status!='解決'",
		"mikaiketu");
}

function m_mode__kinkyu()
{
    __m_mode__show_threads("緊急のタスクを表示:","status!='解決' AND mode='緊急'","kinkyu");
}

function m_mode__yusen()
{
    $msg  = htmlspecialchars(m_param("msg", ""));
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    $body = $msg.
            m_show_all("緊急のもの","status!='解決' AND mode='緊急'","yusen")."<br/>".
            m_show_all("高のもの","status!='解決' AND mode='高'","yusen")."<br/>".
            m_show_all("中のもの","status!='解決' AND mode='中'","yusen")."<br/>".
            m_show_form("新規で書き込む");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function __m_mode__show_threads($title, $where_str, $m_mode = "all")
{
    $msg  = htmlspecialchars(m_param("msg", ""));
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    $body = $msg.
            m_show_all($title,$where_str,$m_mode)."<br/>".
            m_show_form("新規で書き込む");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}


function m_mode__tree()
{
    $msg  = htmlspecialchars(m_param("msg", ""));
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    $body = $msg.
            m_show_tree()."<br/>".
            m_show_form("新規で書き込む");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__thread()
{
    $script = m_info("script_name");
    $msg  = htmlspecialchars(m_param("msg", ""));
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    $body  = $msg;
    $body .= m_show_thread();
    $logid = m_param("parentid", 0);
    $body .= m_show_form("(#{$logid})へ返信する");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__log()
{
    $script = m_info("script_name");
    $msg  = htmlspecialchars(m_param("msg", ""));
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    if (!empty($_SESSION['mbbs.message'])) {
        $msg = "<div class='msg'>".$_SESSION['mbbs.message']."</div>\n";
        unset($_SESSION['mbbs.message']);
    }
    $body  = $msg;
    $body .= m_show_log();
    $body .= "<br/>";
    $logid = m_param("parentid", 0);
    $body .= m_show_form("(#{$logid})へ返信する");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__edit()
{
    $logid = intval(m_param("logid") , 0);
    if ($logid <= 0) {
        m_show_error("logid が指定されていません。");
    }
    $msg  = htmlspecialchars( m_param("msg", "") );
    $script = m_info("script", "");
    if ($msg != "") { $msg = "<div class='msg'>$msg</div>\n"; }
    $body = $msg.
            m_show_form("書き込み(#{$logid})を編集する","editlog");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__write_checkParam(&$thread_v, &$log_v)
{
	//--------------------
    // 簡易 bot チェック
	//--------------------
	$bot_message = m_info('bot.message');
    $bot_param   = m_param('bot');
    if ($bot_param != $bot_message) {
        m_show_error("フォームから書き込んでください。");
    }
    // 日本語 bot チェック
    if (m_info('bot.enabled')) {
        if (m_param("manatubbs_checkbot") != m_info("bot.a")) {
            m_show_error("フォームの「いたずら防止」の項目が間違っています。".m_info("bot.q"));
        }
    }
	//--------------------
    // フィールドチェック
	//--------------------
    if (m_param('mbbs_user_name','') == "") {
        m_show_error("名前が未入力です。[戻る]キーで再入力ください。");
    }
    if (m_param('mbbs_user_title','') == "") {
        m_show_error("タイトルが未入力です。[戻る]キーで再入力ください。");
    }
    //--------------------
    // threads & logs
    //--------------------
    $thread_keys    = array('mode','status');
    $log_keys       = array('threadid','parentid','title','body','name','ip','editkey','mode','status');
    foreach ($thread_keys as $key) {
        $v = m_param($key, null);
        if ($v == null ) {
        	$v = m_param("mbbs_user_{$key}", "");
        }
        $thread_v[$key] = $v;
    }
    foreach ($log_keys as $key) {
        $v = m_param($key, null);
        if ($v == null ) {
        	$v = m_param("mbbs_user_{$key}", "");
        }
        $log_v[$key] = $v;
    }
    $log_v['threadid']  = intval($log_v['threadid']);
    $log_v['parentid']  = intval($log_v['parentid']);
    $log_v['ip'] = empty($_SERVER['REMOTE_ADDR']) ? "" : $_SERVER['REMOTE_ADDR'];
}

function m_mode__write_strim(&$title, &$name)
{
    $name = mb_strimwidth($name, 0, 16, "..");
    $title = mb_strimwidth($title, 0, 48, "..");
}

function m_mode__write()
{
    // 保存
    $thread_v = array();
    $log_v    = array();
    m_mode__write_checkParam($thread_v, $log_v);
    m_db_exec("begin", []);
    // 簡略版を作っておく
    $title = $log_v["title"];
    $name  = $log_v["name"];
    m_mode__write_strim($title, $name);
    //
    if (intval(m_param("threadid", 0)) == 0) { // new thread
        // set time
        $log_v["ctime"] = $log_v["mtime"] = $thread_v["ctime"] = $thread_v["mtime"] = time();
        // threads
        $thread_v["title"] = "$title ($name)";
        if (!m_db_insert("threads", $thread_v)) {
            echo m_db_get_last_error();
            exit;
        }
        $threadid = m_db_last_insert_rowid();
        $log_v["threadid"] = $threadid;
        if (!m_db_insert("logs", $log_v)) {
            echo m_db_get_last_error();
            exit;
        }
        $logid = m_db_last_insert_rowid();
    } else { // reply to thread
        $log_v["mtime"] = $log_v["ctime"] = $thread_v["mtime"] = time();
        $threadid = intval(m_param("threadid"));
        // threads
        $old = m_db_query("SELECT * FROM threads WHERE threadid=?", [$threadid]);
        if (!$old) {
            m_show_error("threadid=$threadid は存在しません。"); exit;
        }
        // 返信カウンタ数を1増やす
        $old = $old[0];
        $thread_v["count"] = intval($old["count"]) + 1;
        // 最後の返信のタイトルを記録する
        $a = explode("-->", $old["title"], 2);
        $old["title"] = trim($a[0]);
        $thread_v["title"] = $old["title"]." --> "."{$log_v['title']}({$log_v['name']})";
        if (!m_db_update("threads", $thread_v,array("threadid"=>$threadid))) {
            echo m_db_get_last_error();
            exit;
        }

        $log_v["threadid"] = $threadid;
        if (!m_db_insert("logs", $log_v)) {
            echo m_db_get_last_error();
            exit;
        }
        $logid = m_db_last_insert_rowid();
    }
    // 添付ファイル
    if (isset($_FILES['attach']) && $_FILES['attach']['size'] > 0) {
        $file_name = $_FILES['attach']['name'];
        $file_size = $_FILES['attach']['size'];
        $file_temp = $_FILES['attach']['tmp_name'];
        $file_err  = $_FILES['attach']['error'];
        // ファイルから拡張子+αだけを得る
        $file_name = trim($file_name);
        $f_ext = preg_match('#([\.a-zA-Z0-9]+)$#', $file_name, $m) ? $m[1] : ".txt";
        $name       = "{$logid}-{$f_ext}";
        if (strlen($name) > 100) { // 最後の20文字だけに変換
            $name = substr($name, strlen($name) - 20, 20);
        }
        $uploadfile = m_info('upload.dir').'/'.$name;
        $uploadfile = str_replace('//', '/', $uploadfile);
        $attach     = "(attach:$name)";
        if (intval(m_info('upload.maxsize')) < $file_size) {
            m_db_exec("rollback", []);
            m_show_error("ファイルサイズが大きすぎます。戻るボタンでやり直してください。"); exit;
        }
        if (!preg_match(m_info("upload.format"), $file_name)) {
            m_db_exec("rollback", []);
            m_show_error("アップロードできない形式です。"); exit;
        }
        if (!move_uploaded_file($file_temp, $uploadfile)) {
            m_db_exec("rollback", []);
            m_show_error("アップロードに失敗しました。"); exit;
        }
        // 権限を読み込み可能に変更する ---
        // chmod($uploadfile,0644);
        // ---
        $log_v["body"] = $log_v["body"]."\n".$attach."\n";
        if (!m_db_update("logs", $log_v, array("logid"=>$logid))) {
            echo m_db_get_last_error();
            exit;
        }
    }
    m_db_exec("commit", []);
    mbbs_setcookie($log_v);

    $script = m_info("script_name");
    $_SESSION['mbbs.message'] = "書き込みが完了しました。";
    $jump = "$script?logid=$logid&m=log";
    header("Location: $jump");
    echo "<body><a href='$jump'>次へ</a></body>";
    $post_url = m_link(array("m=log","logid=$logid"));
    $post_url = str_replace("&amp;", "&", $post_url); // raw url
    // sendmail
    if (m_info("mail.to", FALSE)) {
        $url  = m_link();
        $to = m_info("mail.to");
        $subject = m_info("mail.title") . "($logid)" . $log_v["title"];
        $body  = m_info("TITLE")."への書き込み:\n".
            "[URL] $post_url\n".
            $log_v["name"]." さんより\n".
            $log_v["body"]."\n".
            "[ip] {$_SERVER['REMOTE_ADDR']}\n";
        $from = m_info("mail.from","");
        $cc   = m_info("mail.cc","");
        $bcc  = m_info("mail.bcc","");
        $header = "From:$from
Cc:$cc
Bcc:$bcc
";
        @mb_send_mail($to, $subject, $body,$header);
    }
    // discord
    m_discord_webhook($log_v["title"], $log_v["body"], $log_v["name"], $post_url);
}

function mbbs_setcookie($log_v)
{
    // cookie
    $limit = time() + (60*60*24*90); // 90 day
    $limit_short = time() + (60*60*24*30); // 30 day
    setcookie("mbbs_name",		$log_v["name"], $limit);
    setcookie("mbbs_editkey",	$log_v["editkey"], $limit); // ハッシュを保存
    setcookie("mbbs_botkey",	m_param("manatubbs_checkbot",""), $limit_short);
}

function m_mode__editlog()
{
    // 編集
    $thread_v = array();
    $log_v    = array();
    m_mode__write_checkParam($thread_v, $log_v);
    // ログの読み出し
    $logid = intval(m_param("logid", 0));
    $r = m_db_query("SELECT * FROM logs WHERE logid=?", [$logid]);
    if (empty($r[0]["logid"])) {
        m_show_error("id=$logid は存在しません。");
    }
    $oldlog = $r[0];
    // スレッドの読み出し
    $threadid = $oldlog["threadid"];
    $r = m_db_query("SELECT * FROM threads WHERE threadid=?", [$threadid]);
    if (empty($r[0]["threadid"])) {
        m_show_error("id=$threadid は存在しません。");
    }
    $oldthread = $r[0];
    // 不足している?パラメータを追加
    $log_v["parentid"] = $oldlog["parentid"];
    $log_v["threadid"] = $oldlog["threadid"];
    //
    // パスワードのチェック
    $pass = m_param("mbbs_user_editkey","");
    $pass_user = m_password_to_sha($pass);
    $pass_db   = m_password_to_sha($oldlog["editkey"]);
    if ($pass_user != $pass_db && $pass != m_info("adminpass")) {
        m_show_error("パスワードが違います。");
    }
    //
    m_db_exec("begin", []);
    // set time
    $log_v["ctime"] = $oldlog["ctime"];
    $log_v["mtime"] = $thread_v["mtime"] = time();
    $log_v["editkey"] = $oldlog["editkey"]; // adminpass で更新したことを考慮
    //
    // 簡略版を作っておく
    $title = $log_v["title"];
    $name  = $log_v["name"];
    m_mode__write_strim($title, $name);
    if ($log_v["parentid"] == 0) {
        $thread_v["title"] = "$title ($name)";
    }
    // パスワードをハッシュにして保存
    $log_v["editkey"] = m_password_to_sha($log_v["editkey"]);
    //
    if (!m_db_update("logs", $log_v, array("logid"=>$logid))) {
        m_show_error("DBへの書き込みに失敗。");
    }
    if (!m_db_update("threads", $thread_v, array("threadid"=>$threadid))) {
        m_show_error("DBへの書き込みに失敗。");
    }
    m_db_exec("commit", []);
    //
    mbbs_setcookie($log_v);
    $script = m_info("script_name");
    $msg = urlencode("書き込みが完了しました。");
    header("Location: $script?logid=$logid&m=log&msg=$msg");
}

/**
 * RSS
 */
function m_mode__rss()
{
    header("Content-Type: application/xml; charset=UTF-8");
    $logs = m_db_query("SELECT * FROM logs ORDER BY ctime DESC LIMIT 20", []);
    $linkurl = m_link();
    $script = $_SERVER['SCRIPT_NAME'];
    $logourl = preg_replace("#{$script}#","logo.png",$linkurl);
    require_once "tpl/rss.tpl.php";
}

function m_mode__search()
{
    $script = m_info("script_name");
    $body  =
        "<center>".
        "<div class='inputform'>".
        m_build_form(array(
          m_form_parts("検索語句","key","text",array("size"=>30), ""),
          m_form_parts("","m","hidden",array(), "search2"),
        ),"get","検索");
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__search2()
{
    $script = m_info("script_name");
    $key = m_param("key","");
    if ($key == "") {
        m_show_error("検索語句が指定されていません。");
    }
    $key_ = htmlspecialchars($key);
    $w  = array();
    $w2 = array();
    $words = explode(" ", $key);
    $params = [];
    foreach ($words as $word) {
        $w[] = "body LIKE ?";
        $w2[] = "title LIKE ?";
        $params[] = "%$word%";
    }
    $searchKeyNames = '('.join(" AND ", $w2).')OR('.join(" AND ", $w).')';
    $r   = m_db_query("SELECT * FROM logs WHERE $searchKeyNames LIMIT 20", $params);
    $body  = "<item>";
    $body .= "<div class='node'><span class='root'>検索語句 [$key_]</span></div>";
    foreach ($r as $log) {
        $logid = $log["logid"];
        $title = htmlspecialchars($log["title"]);
        $name  = htmlspecialchars($log["name"]);
        $link = "./{$script}?m=log&logid={$logid}";
        $mtime = date("Y-m-d", $log["mtime"]);
        $date = "<span class='date'>({$mtime})</span>";
        $body .= "<div class='node'>(<a href='{$link}'>#{$logid}</a>) <a href='{$link}'>$title</a> / $name $date</div>";
    }
    if (count($r) == 0) {
        $body .= "合致する語句はありませんでした。";
    }
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

function m_mode__logout() {
  m_set_login(FALSE);
  m_show_error('<a href="./">ログアウトしました。</a>');
}



