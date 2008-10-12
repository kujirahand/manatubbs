<?php
//----------------------------------------------------------------------
// manatubbs library
//----------------------------------------------------------------------
function m_info($param, $default = FALSE)
{
    global $mbbs;
    return empty($mbbs[$param]) ? $default : $mbbs[$param];
}

function m_param($param, $default = FALSE)
{
    if (empty($_POST[$param])) {
        if (empty($_GET[$param])) {
            return $default;
        }
        return $_GET[$param];
    }
    return $_POST[$param];
}

function m_date($time)
{
    $s = date("Y-m-d", $time);
    $h = "<span class='date'>$s</span>";
    if ($time > (time() - 60*60*24)) {
        $h = "<span class='date'>$s<span class='new'>New!</span></span>";
    }
    return $h;
}

function m_url($mod = "", $param_str = "")
{
    $script = m_info("script_name");
    $r = array();
    if ($mod != ""      ) { $r[] = "m=$mod";   }
    if ($param_str != "") { $r[] = $param_str; }
    $url = $script."?".join("&", $r);
    return $url;
}

function m_array_value(&$array, $value, $def = false)
{
    if (empty($array[$value])) {
        return $def;
    }
    return $array[$value];
}

function m_create_menu(&$menu)
{
    $r = array();
    foreach($menu as $v) {
        $type = m_array_value($v, "type", "normal");
        $s    = "";
        $link = "";
        switch ($type) {
            case "normal":
                $lbl   = $v["label"];
                $link  = $v["link"];
                $s = "[<a href='{$link}'>{$lbl}</a>]";
                break;
            case "html":
                $link  = $v["link"];
                $s = "[$link]";
                break;
            case "-":
                $s = "-";
                break;
        }
        if ($s != "") {
            $r[] = $s;
        }
    }
    return join(" ", $r);
}
//----------------------------------------------------------------------
// form
//----------------------------------------------------------------------
function m_build_form($form_array, $method = "get", $button = "送信")
{
    $hidden = "";
    $action = m_info("script_name");
    $parts = "<div class='inputformtable'><table>\n";
    foreach ($form_array as $row) {
        if (substr($row, 0, 20) == "<input type='hidden'") {
            $hidden .= $row;
            continue;
        }
        $parts .= $row . "\n";
    }
    $parts .= "<tr><th></th><td align='right'><input type='submit' value='$button'/></td></tr>\n";
    $parts .= "</table></div>\n";
    return <<< EOS__
<form action="$action" method="$method">
{$parts}
<div>{$hidden}</div>
</form>
EOS__;
}

function m_form_parts($caption, $name, $type, $attr = "", $value = "")
{
    # $attr の値をを補完する
    $attr_init = array(
        "hint"=>"", "size"=>50, "items"=>array()
    );
    foreach ($attr_init as $key => $val) {
        if (empty($attr[$key])) $attr[$key] = $val;
    }
    $hint = $attr["hint"];
    unset($attr["hint"]);
    $items = $attr["items"];
    unset($attr["items"]);
    if ($type=="select") $attr["size"] = 1;
    if ($type=="textarea" || $type=="hidden") {
        unset($attr["size"]);
    }
    # $attr を文字列に変換する
    $attr_list = array();
    foreach ($attr as $key=>$val) {
        $attr_list[] = "$key='$val'";
    }
    $attr_s = join(" ", $attr_list);
    
    $f = "";
    switch ($type) {
        case "text":
            $f = "<input type='text' name='$name' value='{$value}' $attr_s />";
            break;
        case "password":
            $f = "<input type='password' name='$name' $attr_s />";
            break;
        case "textarea":
            $f = "<textarea name='$name' $attr_s>$value</textarea>";
            break;
        case "select":
            $f = "<select name='$name' $attr_s>\n";
            foreach($items as $item) {
                $vvv = ($value == $item) ? "selected" : "";
                $f .= "<option value='$item' $vvv>$item</option>\n";
            }
            $f .= "</select>\n";
            break;
        case "hidden":
            if ($attr_s != "") { $attr_s = " ".$attr_s; }
            $f = "<input type='hidden' name='$name' value='$value'{$attr_s}>";
            break;
    }
    if ($hint != "") {
        $hint = "&nbsp;<span class='hint'>$hint</span>";
    }
    # hidden
    if ($type == "hidden") {
        return $f;
    } else {
        return "<tr><th>{$caption}</th><td>{$f}{$hint}</td></tr>";
    }
}

function m_logid_embedLink($m)
{
    $script = m_info("script_name");
    if (isset($m[1])) {
        $logid = $m[1];
        $link = m_link(array("m=log","logid={$logid}"));
        return "<a href='{$link}'>#{$logid}</a>";
    } else {
        return $m[0];
    }
}

function m_show_form($caption = "", $formmode = "write")
{
    if ($caption == "") { $caption = "書き込みフォーム"; }
    global $mbbs;
    extract($mbbs);
    
    if ($formmode == "editlog") {
        $logid = intval(m_param('logid', 0));
        $sql = "SELECT * FROM logs WHERE logid={$logid}";
        $r = m_db_query($sql);
        if (empty($r[0]["logid"])) {
            m_show_error("{$logid} は存在しない id です。");
        }
        $log = $r[0];
        $ff_name    = htmlspecialchars($log["name"],ENT_QUOTES);
        $ff_title   = htmlspecialchars($log["title"],ENT_QUOTES);
        $ff_body    = htmlspecialchars($log["body"],ENT_QUOTES);
        $ff_mode    = htmlspecialchars($log["mode"],ENT_QUOTES);
        $ff_status  = htmlspecialchars($log["status"],ENT_QUOTES);
    } else {
        $ff_name    = isset($_COOKIE['name']) ? htmlspecialchars($_COOKIE['name'],ENT_QUOTES) : '';
        $ff_title   = htmlspecialchars(m_param('title',''), ENT_QUOTES);
        $ff_body    = "";
        $ff_mode    = htmlspecialchars(m_param('mode',''), ENT_QUOTES);
        $ff_status  = htmlspecialchars(m_param('status',''),ENT_QUOTES);
        // new
        if ($caption == "新規で書き込む") {
            $ff_body = htmlspecialchars(m_info("body.template"),ENT_QUOTES);
        }
    }
    
    $caption_ = preg_replace_callback("|\#(\d+)|","m_logid_embedLink", $caption);
    
    return
    "<div class='inputform'>\n".
    "<div><a name='inputform'>→</a>{$caption_}:</div><br/>\n".
    m_build_form(array(
        m_form_parts("名前",    "name",     "text",     array("size"=>50), $ff_name),
        m_form_parts("タイトル","title",    "text",     array("size"=>50), $ff_title),
        m_form_parts("本文",    "body",     "textarea", array("rows"=>6,"cols"=>80), $ff_body),
        m_form_parts(m_info("priority.label"),    "mode",     "select",
            array(
                'items'=>m_info('mode'),
                'style'=>'width:200px',
            ), $ff_mode),
        m_form_parts(m_info("status.label"),    "status",   "select",
            array(
                'items'=>m_info('status'),
                'style'=>'width:200px',
            ), $ff_status),
        m_form_parts("確認キー","bot2", "text",
            array(
                'hint'=>"お手数ですが、いたずら防止のために、".m_info('bot.q'),
                'style'=>'width:200px',
            ), ""),
        m_form_parts("編集キー","editkey",  "password",
            array(
                'size'=>20,
                'hint'=>'編集する時に必要です',
                'style'=>'width:200px',
            )),
        m_form_parts("", "m",   "hidden", array(), $formmode),
        m_form_parts("", "threadid", "hidden", array(), m_param("threadid",0)),
        m_form_parts("", "parentid", "hidden", array(), m_param("parentid",0)),
        m_form_parts("", "logid", "hidden", array(), m_param("logid",0)),
        m_form_parts("", "bot",  "hidden", array(), $mbbs["bot.message"]),
    ),"post",$caption).
    "</div><!-- end of inputform -->\n";
}

function m_link($params = array())
{
    $p   = join("&amp;", $params);
    $uri = "http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"];
    if ($p != "") {
        $uri .= "?".$p;
    }
    return $uri;
}

//----------------------------------------------------------------------
// Message
//----------------------------------------------------------------------
function m_show_error($msg)
{
    // ヘッダを表示
    include "tpl/header.tpl.php";
    // 本文
    $body = $msg;
    include "tpl/body.tpl.php";
    // フッターを表示
    include "tpl/footer.tpl.php";
    exit;
}

?>
