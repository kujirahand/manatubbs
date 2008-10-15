<?php
//----------------------------------------------------------------------
// manatubbs
//----------------------------------------------------------------------
mb_internal_encoding("UTF-8");
header("Content-Type: text/html; charset=UTF-8");
// 変数の初期化
global $mbbs;
$mbbs = array();
$mbbs["db.handle"] = NULL;
//----------------------------------------------------------------------
// ライブラリの取り込み
require_once "inc/mbbs_lib.inc.php";
require_once "inc/mbbs_db.inc.php";
//----------------------------------------------------------------------
// 初期設定の取り込み
require_once "setting-org.ini.php";
if (file_exists("setting-user.ini.php")) {
    require_once "setting-user.ini.php";
}
$mbbs["mode"] = $mbbs["priority"];
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
    __m_mode__show_threads("スレッド一覧を表示:", false, "threads");
}

function m_mode__mikaiketu()
{
    __m_mode__show_threads("未解決のタスクを表示:","status!='解決'","mikaiketu");
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
    // 簡易 bot チェック
    $bot_message = m_info('bot.message');
    $bot_param   = m_param('bot');
    if ($bot_param != $bot_message) {
        m_show_error("フォームから書き込んでください。");
    }
    // 日本語 bot チェック
    if (m_param("bot2") != m_info("bot.a")) {
        m_show_error("フォームの「いたずら防止」の項目が間違っています。".m_info("bot.q"));
    }
    // フィールドチェック
    if (m_param('name','') == "") {
        m_show_error("名前が未入力です。[戻る]キーで再入力ください。");
    }
    if (m_param('title','') == "") {
        m_show_error("タイトルが未入力です。[戻る]キーで再入力ください。");
    }
    if (m_param('editkey','') == "") {
        m_show_error("編集キーが未入力です。[戻る]キーで再入力ください。");
    }
    // threads & logs
    $thread_keys    = array('mode','status');
    $log_keys       = array('threadid','parentid','title','body','name','ip','editkey','mode','status');
    foreach ($thread_keys as $key) {
        $v = m_param($key,"");
        $thread_v[$key] = $v;
    }
    foreach ($log_keys as $key) {
        $v = m_param($key,"");
        $log_v[$key] = $v;
    }
    $log_v['threadid']  = intval($log_v['threadid']);
    $log_v['parentid']  = intval($log_v['parentid']);
    $log_v['ip']        = $_SEVER['SERVER_ADDR'];
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
    m_db_query("begin");
    // 簡略版を作っておく
    $title = $log_v["title"];
    $name  = $log_v["name"];
    m_mode__write_strim($title, $name);
    //
    if (m_param("threadid", 0) == 0) { // new
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
    } else { // reply
        $log_v["mtime"] = $log_v["ctime"] = $thread_v["mtime"] = time();
        $threadid = intval(m_param("threadid"));
        // threads
        $old = m_db_query("SELECT * FROM threads WHERE threadid=$threadid");
        if (!$old) {
            m_show_error("threadid=$threadid は存在しません。"); exit;
        }
        $old = $old[0];
        $thread_v["count"] = intval($old["count"]) + 1;
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
    m_db_query("commit");
    //
    $limit = time() *60 *60 *24 *30;
    setcookie("name",$log_v["name"],$limit);
    $script = m_info("script_name");
    $msg = urlencode("書き込みが完了しました。");
    header("Location: $script?logid=$logid&m=log&msg=$msg");
}

function m_mode__editlog()
{
    // 編集
    $thread_v = array();
    $log_v    = array();
    m_mode__write_checkParam($thread_v, $log_v);
    // ログの読み出し
    $logid = intval(m_param("logid", 0));
    $r = m_db_query("SELECT * FROM logs WHERE logid=$logid");
    if (empty($r[0]["logid"])) {
        m_show_error("id=$logid は存在しません。");
    }
    $oldlog = $r[0];
    // スレッドの読み出し
    $threadid = $oldlog["threadid"];
    $r = m_db_query("SELECT * FROM threads WHERE threadid=$threadid");
    if (empty($r[0]["threadid"])) {
        m_show_error("id=$threadid は存在しません。");
    }
    $oldthread = $r[0];
    // 不足している?パラメータを追加
    $log_v["parentid"] = $oldlog["parentid"];
    $log_v["threadid"] = $oldlog["threadid"];
    //
    // パスワードのチェック
    $pass = m_param("editkey","");
    if ($pass != $oldlog["editkey"] && $pass != m_info("adminpass")) {
        m_show_error("パスワードが違います。");
    }
    //
    m_db_query("begin");
    // set time
    $log_v["ctime"] = $oldlog["ctime"];
    $log_v["mtime"] = $thread_v["mtime"] = time();
    $log_v["editkey"] = $oldlog["editkey"]; // adminpass で更新することを考慮
    //
    // 簡略版を作っておく
    $title = $log_v["title"];
    $name  = $log_v["name"];
    m_mode__write_strim($title, $name);
    if ($log_v["parentid"] == 0) {
        $thread_v["title"] = "$title ($name)";
    }
    //
    if (!m_db_update("logs", $log_v, array("logid"=>$logid))) {
        m_show_error("DBへの書き込みに失敗。");
    }
    if (!m_db_update("threads", $thread_v, array("threadid"=>$threadid))) {
        m_show_error("DBへの書き込みに失敗。");
    }
    m_db_query("commit");
    //
    $limit = time() *60 *60 *24 *30;
    setcookie("name",$log_v["name"],$limit);
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
    $sql = "SELECT * FROM logs ORDER BY ctime DESC LIMIT 20";
    $logs = m_db_query($sql);
    //
    $linkurl = "http://".$_SERVER["HTTP_HOST"]. $_SERVER["SCRIPT_NAME"];
    $script = m_info("script_name");
    $logourl = preg_replace("#{$script}#","logo.png",$linkurl);
    include "tpl/rss.tpl.php";
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
    $words = split(" ", $key);
    foreach ($words as $word) {
        $word = m_db_escape($word);
        $word = "%$word%";
        $w[] = "body LIKE '$word'";
        $w2[] = "title LIKE 'word'";
    }
    $keys = '('.join(" AND ", $w2).')OR('.join(" AND ", $w).')';
    $r   = m_db_query("SELECT * FROM logs WHERE $keys LIMIT 20");
    $body  = "<item>";
    $body .= "<div class='node'><span class='root'>検索語句 [$key_]</span></div>";
    foreach ($r as $log) {
        $logid = $log["logid"];
        $title = htmlspecialchars($log["title"]);
        $name  = htmlspecialchars($log["name"]);
        $link = "./{$script}?m=log&logid={$logid}";
        $ctime = date("Y-m-d", $logid["ctime"]);
        $date = "<span class='date'>({$ctime})</span>";
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

?>
