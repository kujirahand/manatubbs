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

function m_cookie($key, $default = FALSE)
{
    return empty($_COOKIE[$key]) ? $default : $_COOKIE[$key];
}

function m_is_login() {
  global $mbbs;
  if (isset($_SESSION['mbbs_login']) && $_SESSION['mbbs_login'] > 0) {
    return TRUE;
  }
  return FALSE;
}
function m_set_login($islogin) {
  if ($islogin) {
    $_SESSION['mbbs_login'] = time();
  }
  else {
    $_SESSION['mbbs_login'] = 0;
  }
}

function m_check_login() {
  global $mbbs;
  $msg = "ログインしていません。";
  if (isset($_POST['user']) && isset($_POST['pass'])) {
    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);
    // check conf
    $a = array();
    $users_s = $mbbs['users'];
    $users_a = explode(",", $users_s);
    foreach ($users_a as $line) {
      $cs = explode(":", $line);
      $a[trim($cs[0])] = trim($cs[1]);
    }
    if (isset($a[$user]) && $a[$user] === $pass) {
      m_set_login(TRUE);
      return;
    } else {
      $msg = "パスワードが違います。";
    }
  }
  // form
  m_show_error(
    "<p>$msg</p>".
    "<form method='POST'>".
    "<p>ユーザー名:<br><input name='user' size='12'></p>".
    "<p>パスワード:<br><input name='pass' size='12'></p>".
    "<p><input type='submit' value='ログイン'></p>".
    ""
  );
  exit;
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
    $url = $script."?".join("&amp;", $r);
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
function m_build_form($form_array, $method = "get", $button = "送信", $flag_upload = FALSE)
{
    $hidden = "";
    $action = m_info("script_name");
    $parts = "<div class='inputformtable'><table width='100%'>\n";
    foreach ($form_array as $row) {
        if (substr($row, 0, 20) == "<input type='hidden'") {
            $hidden .= $row;
            continue;
        }
        $parts .= $row . "\n";
    }
    $parts .= "<tr><th></th><td align='right'><input type='submit' value='$button'/></td></tr>\n";
    $parts .= "</table></div>\n";
    $enctype = ($flag_upload) ? 'enctype="multipart/form-data"' : "";
    return <<< EOS__
<form $enctype action="$action" method="$method">
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
            $f = "<input type='password' name='$name' value='{$value}' $attr_s />";
            break;
        case "textarea":
            $f = "<textarea name='$name' $attr_s rows='80' cols='6'>$value</textarea>";
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
        case "file":
            $f = "<input type='file' name='$name' $attr_s />";
            break;
    }
    if ($hint != "") {
        $hint = "&nbsp;<span class='hint'>$hint</span>";
    }
    # hidden
    if ($type == "hidden") {
        return $f;
    } else {
        return "<tr><th width='20%'>{$caption}</th><td width='80%'>{$f}{$hint}</td></tr>";
    }
}

function m_password_to_sha($pass)
{
	if (substr($pass, 0, 5) == '[sha]') {
		return $pass;
	}
	return '[sha]'.sha1($pass);
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

function remove_magic_quotes_gpc()
{
/*
    if (get_magic_quotes_gpc()) {
        foreach ($_GET as $key => $val) {
            $_GET[$key] = stripslashes($val);
        }
        foreach ($_POST as $key => $val) {
            $_POST[$key] = stripslashes($val);
        }
    }
*/
}

?>
