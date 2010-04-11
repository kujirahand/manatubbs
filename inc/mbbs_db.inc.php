<?php
//----------------------------------------------------------------------
// manatubbs database library function
// いろはにほへと
//----------------------------------------------------------------------
define("FILE_INIT_SQL","db/sql.txt");

function m_error_dbopen()
{
    echo "DATABASE OPEN ERROR!!<br/>\n";
    echo "DBディレクトリのアクセス権限を0705に設定してください。";
    exit;
}

//----------------------------------------------------------------------
// base library
//----------------------------------------------------------------------
function m_initDB()
{
    global $mbbs;
    $dbfile = m_info("db.name", "db/manatubbs.db");
    if (!file_exists($dbfile)) {
        m_db_createTable($dbfile);
    }
    $h = @sqlite_open($dbfile, 0604);
    if (!$h) { m_error_dbopen(); }
    $mbbs["db.handle"] = &$h;
}

function m_db_createTable($dbfile)
{
    $h = @sqlite_open($dbfile, 0604);
    if (!$h) { m_error_dbopen(); }
    $query  = file_get_contents(FILE_INIT_SQL);
    $result = sqlite_exec($h, $query);
    if (!$result) {
        $err = sqlite_error_string( sqlite_last_error($h) );
        echo "DBの初期化に失敗しました。<br/>\n$err";
    }
    sqlite_close($h);
}

function m_db_get_last_error()
{
    global $mbbs;
    return sqlite_error_string( sqlite_last_error($mbbs["db.handle"]) );
}

function m_db_query($query)
{
    global $mbbs;
    $res = sqlite_array_query($mbbs["db.handle"], $query, SQLITE_ASSOC);
    return $res;
}

function m_db_exec($query)
{
    global $mbbs;
    $err = sqlite_exec($mbbs["db.handle"], $query);
    if (!$err) {
        echo "[ERROR] ".m_db_get_last_error()."[$query]\n";
    }
    return $err;
}

function m_db_escape($s)
{
    return sqlite_escape_string($s);
}

function m_db_insert($table, $values)
{
    // SQL を生成
    $key_a = array();
    $val_a = array();
    foreach ($values as $key => $val) {
        $k = m_db_escape($key);
        $v = m_db_escape($val);
        $key_a[] = $k;
        $val_a[] = is_numeric($v) ? $v : "'{$v}'";
    }
    $key_s = join(",", $key_a);
    $val_s = join(",", $val_a);
    $s = "INSERT INTO {$table} ($key_s)VALUES($val_s)";
    return m_db_exec($s);
}

function m_db_update($table, $values, $where, $where_str = "")
{
    $v_a = array();
    foreach ($values as $key => $val) {
        $k = m_db_escape($key);
        $v = m_db_escape($val);
        $v = is_numeric($v) ? $v : "'{$v}'";
        $v_a[] = "$k=$v";
    }
    $w_a = array();
    foreach ($where as $key => $val) {
        $k = m_db_escape($key);
        $v = m_db_escape($val);
        $v = is_numeric($v) ? $v : "'{$v}'";
        $w_a[] = "$k=$v";
    }
    $v_s = join(",", $v_a);
    $w_s = join(" AND ", $w_a);
    if ($where_str != "") {
        $w_s .= " AND ".$where_str;
    }
    if ($w_s != "") {
        $w_s = "WHERE {$w_s}";
    }
    $s = "UPDATE $table SET $v_s $w_s";
    return m_db_exec($s);
}

function m_db_last_insert_rowid()
{
    global $mbbs;
    return sqlite_last_insert_rowid($mbbs["db.handle"]);
}

//----------------------------------------------------------------------
// table io
//----------------------------------------------------------------------

function m_get_index($threadid, &$items)
{
    $res = "<div class='indexbox'>\n";
    $r = m_db_query("SELECT * FROM logs WHERE threadid={$threadid} ORDER BY ctime ASC");
    $root = FALSE;
    foreach ($r as $log) {
        $logid    = intval($log["logid"]   );
        $items[$logid] = $log;
    }
    foreach ($items as $log) {
        $logid    = intval($log["logid"]   );
        $parentid = intval($log["parentid"]);
        if ($parentid == 0) {
            $root = $logid;
        } else {
            $items[$parentid]["children"][] = $logid;
        }
    }
    if ($root) {
        $res .= m_get_index_title__(0, $items, $root);
    } else {
        $res .= "[スレッドにルートがありません。id=$threadid]";
    }
    $res .= "</div>\n";
    return $res;
}

function m_show_all($title = "", $where_str = FALSE, $m_mode = "all")
{
    // query all
    $page   = m_param("p", 1) - 1;
    $per    = m_info("threads.perpage");
    $offset = $per * $page;
    $limit  = $per + 1;
    $res    = "";
    $script = m_info("script");
    $priority_label = m_info("priority.label");
    $status_label   = m_info("status.label");
    $pri_color      = m_info("priority.color");
    $sta_color      = m_info("status.color");
    // query threads
    $pager = "";
    $where = ($where_str !== FALSE) ? "WHERE {$where_str}" : "";
    $r = m_db_query("SELECT * FROM threads $where ORDER BY mtime DESC LIMIT {$limit} OFFSET {$offset}");
    if (count($r) == 0) {
        return "<div class='item'>ログがありません。</div>";
    }
    $pager .= "<span class='pager'>";
    if ($page > 0) {
        $page_ = $page;
        $pager .= "<a href='{$script}?p={$page_}&m={$m_mode}'>←前へ</a>&nbsp;";
    }
    if (count($r) >= $limit) {
        $page_ = $page + 2;
        $pager .= "<a href='{$script}?p={$page_}&m={$m_mode}'>次へ→</a>";
        array_pop($r);
    }
    $pager .= "</span>\n";
    $res  = <<<EOS__
<div class="thread">
<div class="desctiption2">$title</div>
<table>
<tr class="head"><th>@ID</th><th>タイトル</th><th>返信</th><th>更新日</th><th>{$priority_label}</th><th>{$status_label}</t></tr>
EOS__;
    foreach ($r as $row) {
        $threadid = $row["threadid"];
        $title = htmlspecialchars($row["title"]);
        $priority = htmlspecialchars($row["mode"]);
        $status = htmlspecialchars($row["status"]);
        $count = intval($row["count"]);
        $date = m_date($row["mtime"]);
        $color = $bgcolor = $style = "";
        if (isset($pri_color[$priority])) {
            $color   = $pri_color[$priority]["color"];
            $bgcolor = $pri_color[$priority]["bgcolor"];
            $style   = isset($pri_color[$priority]["style"]) ? $pri_color[$priority]["style"] : '';
        }
        if (isset($sta_color[$status])) {
            $color   = $sta_color[$status]["color"];
            $bgcolor = $sta_color[$status]["bgcolor"];
            $style   = isset($sta_color[$status]["style"]) ? $sta_color[$status]["style"] : '';
        }
        if ($color != "") {
            $style = " style='background-color:$bgcolor;color:$color;$style;'";
        }
        //
        $titlelink = "<a href='{$script}?m=thread&threadid=$threadid'>$title</a>";
        $idlink = "<a href='{$script}?m=thread&threadid=$threadid'>@{$threadid}</a>";
        $res .= <<<EOS__
<tr{$style}><td align="right">$idlink</td><td>$titlelink</td><td align="right">$count</td><td>$date</td><td>$priority</td><td>$status</td></tr>
EOS__;
    }
    $res .= "</table>\n";
    $res .= "</div>\n";
    $res = "<div style='text-align:right;padding-bottom:6px;'>$pager</div>$res<div style='text-align:right'>$pager</div>\n";
    return $res;
}

function m_show_tree()
{
    // query all
    $page   = m_param("p", 1) - 1;
    $per    = m_info("tree.perpage");
    $offset = $per * $page;
    $limit  = $per + 1;
    $res    = "";
    $script = m_info("script");
    
    $pager = "";
    $r = m_db_query("SELECT * FROM threads ORDER BY mtime DESC LIMIT {$limit} OFFSET {$offset}");
    if (count($r) == 0) {
        return "<div class='item'>ログがありません。</div>";
    }
    $pager .= "<span class='pager'>";
    if ($page > 0) {
        $page_ = $page;
        $pager .= "<a href='{$script}?m=tree&p={$page_}'>←前へ</a>&nbsp;";
    }
    if (count($r) >= $limit) {
        $page_ = $page + 2;
        $pager .= "<a href='{$script}?m=tree&p={$page_}'>次へ→</a>";
        array_pop($r);
    }
    $pager .= "</span>\n";
    foreach ($r as $row) {
        $threadid = $row["threadid"];
        $items = array();
        $res .= m_get_index($threadid, $items);
        $res .= "<br/>";
    }
    $res = $pager."<br/>\n".$res.$pager."<br/>\n";
    return $res;
}

function m_show_thread()
{
    $script = m_info("script_name");
    $threadid = intval(m_param("threadid", 0));
    if ($threadid <= 0) return "";
    $page = intval(m_param("p", 1)); if ($page > 0) $page--;
    $perpage  = m_info("logs.perpage");
    $offset = $page * $perpage;
    $limit = $perpage + 1;
    
    // check thread
    $sql = "SELECT * FROM threads WHERE threadid=$threadid LIMIT 1";
    $r = m_db_query($sql);
    if (!$r) {
        m_show_error("スレッド id=$threadid はありません。"); return;
    }
    // get first log
    $sql = "SELECT * FROM logs WHERE threadid=$threadid ORDER BY logid LIMIT 1";
    $topr = m_db_query($sql);
    if (!$topr) {
        m_show_error("スレッド id=$threadid はありません。"); return;
    }
    $logid = $topr[0]["logid"];
    $top = m_get_log_item($topr[0]);
    
    // get record log
    $sql = "SELECT * FROM logs WHERE threadid=$threadid AND logid != $logid ORDER BY logid DESC LIMIT $limit OFFSET $offset";
    $items = m_db_query($sql);
    $pager = "";
    if ($page > 0) {
        $pager .= "<a href='{$script}?m=thread&p=$page&threadid=$threadid'>←前へ</a> ";
    }
    if (count($items) > $perpage) {
        $tmp = array_pop($items);
        $p2 = $page + 2;
        $pager .= "<a href='{$script}?m=thread&p={$p2}&threadid=$threadid'>次へ→</a>";
    }
    if ($pager != "") {
        $pager = "<span class='pager'>$pager</span>";
    }
    // -----------------------------------------------------------------
    // logs
    // -----------------------------------------------------------------
    if (is_array($items)){
        $items = array_reverse($items);
        foreach ($items as $log) {
            $res .= m_get_log_item($log);
        }
    }
    // トップに最新のステータスを表示
    $_POST["mode"]   = $r[0]["mode"];
    $_POST["status"] = $r[0]["status"];
    $_POST["parentid"] = $logid;
    $threadurl  = m_url("thread","threadid=$threadid");
    $threadlink = "(<a href='$threadurl'>@{$threadid}</a>)";
    //
    $header = <<<EOS__
<span class='pager'>[<a href='$script?m=all'>一覧へ</a>] &gt; $threadlink</span>
<span class='hint'>[{$_POST['mode']}]</span>
<span class='hint'>[{$_POST['status']}]</span>
{$top}
EOS__;
    $res = "<div style='text-align:center'>$pager</div>{$res}\n<div style='text-align:center'>$pager</div><br/>";
    return $header.$res;
}

function m_get_log_item($log)
{
    if (empty($log)) return;
    extract($log);
    $script = m_info("script_name");
    $mtime_s = "<span class='date'>(".date("Y-m-d h:i", $log["mtime"]).")</span>";
    $parentlink = "*";
    if ($parentid > 0) {
        $link = m_link(array("m=log", "logid={$parentid}"));
        $parentlink = "<a href='{$link}'>↑</a>";
    } else {
        $link = m_link(array("m=thread", "threadid={$threadid}"));
        $parentlink = "<a href='{$link}'>@{$threadid}■</a>";
    }
    $class = ($parentid == 0) ? "itemhead" : "itemhead2";
    $title = htmlspecialchars($title);
    $name = htmlspecialchars($name);
    // body
    $body = htmlspecialchars($body);
    $body = preg_replace("#(\r\n|\r|\n)#","<br/>\n",$body);
    $body = preg_replace("#\t#","　　",$body);
    $body = preg_replace("#\x20#","&nbsp;",$body);
    $body = preg_replace("#((http|https|ftp)\:\/\/[a-zA-Z0-9\.\,\/\#\?\&\=\-\_\~\+\%\;\:\*\!\@\[\]]+)#","<a href='$1'>$1</a>",$body);
    $body = "<!-- body -->\n".$body."\n<!-- end of body-->\n";
    // body replyto
    $body = preg_replace("#\n(\&gt\;[^\n]+)#","\n<span class='reply'>$1</span>",$body);
    // thread link
    $body = preg_replace("/\(\#(\d+)\)/","(<a href='{$script}?m=log&logid=$1'>#$1</a>)",$body);
    $body = preg_replace("/\(\@(\d+)\)/","(<a href='{$script}?m=thread&threadid=$1'>@$1</a>)",$body);
    // repos link
    $repos = m_info("repos.link",false);
    if ($repos) {
        $body = preg_replace("/\(r(\d+)\)/","(<a href='{$repos}$1'>r$1</a>)",$body);
    }
    // attach file
    if (!function_exists("replace_attach_link")) {
        function replace_attach_link($m) {
            $attachdir = m_info("upload.dir");
            if (preg_match("#\.(jpeg|jpg|png|gif)$#", $m[1])) {
                return "<img src='{$attachdir}{$m[1]}'/>";
            } else {
                return "<a href='{$attachdir}{$m[1]}'>(attach:{$m[1]})</a>";
            }
        }
    }
    $body = preg_replace_callback(
        "#\(attach\:(\d+[a-zA-Z0-9\-\_\.\+]+?)\)#","replace_attach_link",$body);
    //
    $logidlink = "<span class='id'>(<a href='{$script}?m=log&logid=$logid'>#{$logid}</a>)</span>";
    //
    $mode   = htmlspecialchars($mode);
    $status = htmlspecialchars($status);
    $editlink = m_link(array("m=edit","logid={$logid}"));
    $res .= <<<EOS__
<br/>
<div class="item">
    <div class="$class">
         $parentlink $logidlink $title - $name $mtime_s
        <span class="hint">/$mode $status</span>
    </div>
    <div class="body">
        <code>$body</code>
        <div class="editlink"><a href="{$editlink}">編集</a></div>
    </div>
</div>
EOS__;
    return $res;
}


function m_show_log()
{
    // query thread
    $res = "";
    $logid = intval(m_param("logid", 0));
    $r = m_db_query("SELECT * FROM logs WHERE logid={$logid} ORDER BY ctime");
    if (!$r) return "[no logid=$logid]";
    $cur_log = $r[0];
    // -----------------------------------------------------------------
    // logs
    // -----------------------------------------------------------------
    $res .= m_get_log_item($cur_log);
    // -----------------------------------------------------------------
    // index
    // -----------------------------------------------------------------
    $threadid = $cur_log["threadid"];
    $_POST["threadid"] = $threadid;
    $_POST["title"]    = "RE:".$cur_log["title"];
    $_POST["mode"]     = $r[0]["mode"];
    $_POST["stat"]     = $r[0]["stat"];
    $_POST["parentid"] = $r[0]["logid"];
    $res .= m_get_index($threadid, $items);
    return $res;
}

function m_get_index_title__($level, $items, $no)
{
    $script = m_info("script_name");
    $log = $items[$no];
    extract($log);
    if ($level > 0) {
        $tree = "<tt>";
        for ($i = 0; $i < $level; $i++) {
            $tree .= "　　";
        }
        $tree .= "</tt>";
    } else {
        $tree = "";
    }
    $head_a = array();
    $foot_a = array();
    
    array_push   ($head_a,"<div class='node'>");
    array_unshift($foot_a,"</div>");
    
    if ($level == 0) {
        $link = m_link(array("m=thread","threadid={$threadid}"));
        $css = ($logid == m_param("logid")) ? "curnode" : "itemnode";
        $icon = "<a href='{$link}'><span class='$css'>■</span></a>";
        array_push   ($head_a,"<span class='root'>$icon ");
        array_unshift($foot_a,"</span>");
    } else {
        $link = m_link(array("m=log","logid={$logid}"));
        $css = ($logid == m_param("logid")) ? "curnode" : "itemnode";
        $icon = "";
        array_push   ($head_a, $icon." ");
        array_unshift($foot_a,"");
    }
    
    $mode = htmlspecialchars($mode);
    $status = htmlspecialchars($status);
    $mtime = date("Y-m-d h:i", $mtime);
    $mtime = "<span class='date'>({$mtime})</span>";
    $title = mb_strimwidth($title, 0, 40, '..');
    $title = htmlspecialchars($title);
    $name = htmlspecialchars($name);
    $link = m_link(array("m=log","logid={$logid}"));
    $logidlink = "<span class='id'>(<a href='{$link}'>#{$logid}</a>)</span>";
    $line  = "<a href='{$link}'>$title</a> / $name $mtime $logidlink";
    $line .= "<span class='hint'>/ $mode $status</span>";
    
    $s = join("",$head_a) . $tree . $line . join("",$foot_a);
    
    $s .= "\n";
    if ($log["children"]) {
        $len = count($log["children"]);
        for ($i = 0; $i < $len; $i++) {
            $row = $log["children"][$i];
            $s .= m_get_index_title__($level+1, $items, $row);
        }
    }
    return $s;
}
?>
