<?php
//----------------------------------------------------------------------
// manatubbs library
//----------------------------------------------------------------------
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

function m_is_login()
{
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
    // CSRF対策
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!m_csrf_verify_token($csrf_token)) {
      $msg = "セキュリティトークンが無効です。ページを再読み込みしてから再度お試しください。";
    } else {
      // トークンを使用済みにマーク
      m_csrf_mark_token_used();
      
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
        // CSRFトークンをクリア
        m_csrf_clear_token();
        return;
      } else {
        $msg = "パスワードが違います。";
      }
    }
  }
  
  // CSRFトークンを生成
  $csrf_token = m_csrf_generate_token();
  
  // form
  m_show_error(
    "<p>$msg</p>".
    "<div class='form card' style='width:20em;padding:1em;'><form method='POST'>".
    "<p><label>ユーザー名:<br><input class='input' name='user' size='12'></label></p>".
    "<p><label>パスワード:<br><input class='input' name='pass' size='12' type='password'></label></p>".
    "<input type='hidden' name='csrf_token' value='".htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8')."'>".
    "<p><input class='button is-primary' type='submit' value='ログイン'></p>".
    "</div>"
  );
  exit;
}

function m_date($time)
{
    if (!is_numeric($time)) { return '---'; }
    $s = date("Y-m-d", $time);
    $h = "<span class='date'>$s</span>";
    if ($time > (time() - 60*60*24)) {
        $h = "<span class='date'>$s<span class='new'>New!</span></span>";
    }
    return $h;
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
    $cur_m = m_param("m", "all");
    $count = count($menu);

    for ($i = 0; $i < $count; $i++) {
        $v = $menu[$i];
        $type = m_array_value($v, "type", "normal");
        $lbl  = m_array_value($v, "label", "");
        $link = m_array_value($v, "link", "");

        // 「ツリー」があれば「スレッド」とまとめて1つのspanで囲む
        if ($type === "normal" && $lbl === "ツリー") {
            $thread_v = null;
            $next_idx = -1;
            for ($j = $i + 1; $j < $count; $j++) {
                if (m_array_value($menu[$j], "label", "") === "スレッド") {
                    $thread_v = $menu[$j];
                    $next_idx = $j;
                    break;
                }
            }
            
            if ($thread_v !== null) {
                $tree_link = $link;
                $thread_link = m_array_value($thread_v, "link", "");
                
                $tree_active = ($cur_m === "tree") ? " is-active" : "";
                $thread_active = ($cur_m === "threads" || $cur_m === "all" || $cur_m === "") ? " is-active" : "";
                
                $html = "<span class='menu-switch-group'>" .
                        "<span class='menu-item menu-switch{$tree_active}'><a href='{$tree_link}'>ツリー</a></span>" .
                        "<span class='menu-item menu-switch{$thread_active}'><a href='{$thread_link}'>スレッド</a></span>" .
                        "</span>";
                
                $r[] = $html;
                $i = $next_idx;
                continue;
            }
        }

        switch ($type) {
            case "normal":
                $is_active = FALSE;
                if (preg_match('/[?&]m=([a-zA-Z0-9_]+)/', $link, $matches)) {
                    if ($matches[1] === $cur_m) {
                        $is_active = TRUE;
                    }
                } else if ($cur_m === 'all' && (strpos($link, 'index.php') !== FALSE && strpos($link, 'm=') === FALSE)) {
                    $is_active = TRUE;
                }
                
                $active_cls = $is_active ? " is-active" : "";
                $r[] = "<span class='menu-item{$active_cls}'><a href='{$link}'>{$lbl}</a></span>";
                break;
            case "html":
                $r[] = "<span class='menu-item'>$link</span>";
                break;
            case "-":
                $r[] = "<span class='menu-sep'>・</span>";
                break;
        }
    }
    return join("", $r);
}
//----------------------------------------------------------------------
// form
//----------------------------------------------------------------------
function m_build_form($form_array, $method = "get", $button = "送信", $flag_upload = FALSE)
{
    $hidden = "";
    $action = m_info("script_name");
    $parts = "<div class='inputformtable'>\n";
    foreach ($form_array as $row) {
        if (substr($row, 0, 20) == "<input type='hidden'") {
            $hidden .= $row;
            continue;
        }
        $parts .= $row . "\n";
    }
    $parts .= "<div><input class='button is-info' type='submit' value='$button'/></div>\n";
    $parts .= "</div><!-- /.inputformtable -->\n";
    $enctype = ($flag_upload) ? 'enctype="multipart/form-data"' : "";
    return <<< EOS__
<form $enctype action="$action" method="$method">
{$parts}
<div>{$hidden}</div>
</form>
EOS__;
}

function m_form_parts($caption, $name, $type, $attr = [], $value = "")
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
            $f = "<input class='input' type='text' name='$name' value='{$value}' $attr_s />";
            break;
        case "password":
            $f = "<input class='input' type='password' name='$name' value='{$value}' $attr_s />";
            break;
        case "textarea":
            $f = "<textarea class='textarea' name='$name' $attr_s rows='80' cols='6'>$value</textarea>";
            break;
        case "select":
            $f =  "<div class='select'>".
                  "<select name='$name' $attr_s>\n";
            foreach($items as $item) {
                $vvv = ($value == $item) ? "selected" : "";
                $f .= "<option value='$item' $vvv>$item</option>\n";
            }
            $f .= "</select></div>\n";
            break;
        case "hidden":
            if ($attr_s != "") { $attr_s = " ".$attr_s; }
            $f = "<input type='hidden' name='$name' value='$value'{$attr_s}>";
            break;
        case "file":
            $f = "<input class='input' type='file' name='$name' $attr_s />";
            break;
    }
    if ($hint != "") {
        $hint = "<p class='help hint'>$hint</p>";
    }
    # hidden
    if ($type == "hidden") {
        return $f;
    } else {
        return "<div class='field'>".
          "<label class='label'>{$caption}</label>".
          "<div class='control'>{$f}</div>".
          "{$hint}</div>";
    }
}

function m_password_to_sha($pass)
{
	if (substr($pass, 0, 5) == '[sha]') {
		return $pass;
	}
	return '[sha]'.sha1($pass);
}

/**
 * CSRF対策: トークンを生成する
 */
function m_csrf_generate_token()
{
    // 既存のトークンがあり、まだ有効期限内なら再利用
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        $token_age = time() - $_SESSION['csrf_token_time'];
        if ($token_age < 3600) { // 1時間以内なら再利用
            return $_SESSION['csrf_token'];
        }
    }
    
    // 新しいトークンを生成
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    $_SESSION['csrf_token_used'] = false; // 使用フラグをリセット
    
    return $_SESSION['csrf_token'];
}

/**
 * CSRF対策: トークンを検証する
 */
function m_csrf_verify_token($token)
{
    // デバッグ情報を一時的に追加
    if (m_info('test_mode', FALSE)) {
        error_log("CSRF Debug: Starting verification");
        error_log("CSRF Debug: Received token = " . $token);
        error_log("CSRF Debug: Session token = " . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'not_set'));
        error_log("CSRF Debug: Session used = " . (isset($_SESSION['csrf_token_used']) ? ($_SESSION['csrf_token_used'] ? 'true' : 'false') : 'not_set'));
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        if (m_info('test_mode', FALSE)) {
            error_log("CSRF Debug: Session token or time not set");
        }
        return false;
    }
    
    // トークンの有効期限チェック（1時間）
    $token_age = time() - $_SESSION['csrf_token_time'];
    if ($token_age > 3600) {
        if (m_info('test_mode', FALSE)) {
            error_log("CSRF Debug: Token expired, age = " . $token_age);
        }
        return false;
    }
    
    // 既に使用済みのトークンかチェック
    if (isset($_SESSION['csrf_token_used']) && $_SESSION['csrf_token_used']) {
        if (m_info('test_mode', FALSE)) {
            error_log("CSRF Debug: Token already used - clearing and regenerating");
        }
        // 使用済みトークンの場合は一度クリアして新しいトークンで再試行を許可
        unset($_SESSION['csrf_token_used']);
    }
    
    // トークンの値を検証
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        if (m_info('test_mode', FALSE)) {
            error_log("CSRF Debug: Token mismatch");
        }
        return false;
    }
    
    if (m_info('test_mode', FALSE)) {
        error_log("CSRF Debug: Token verification successful");
    }
    return true;
}

/**
 * CSRF対策: トークンを使用済みにマークする
 */
function m_csrf_mark_token_used()
{
    $_SESSION['csrf_token_used'] = true;
}

/**
 * CSRF対策: トークンをクリアする
 */
function m_csrf_clear_token()
{
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_time']);
    unset($_SESSION['csrf_token_used']);
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
    $scheme = 'https';
    if (empty($_SERVER['HTTPS'])) { $scheme = 'http'; }
    $uri = "$scheme://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"];
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
  $dir = dirname(__DIR__);
  include "$dir/tpl/header.tpl.php";
  // 本文
  $body = $msg;
  include "$dir/tpl/body.tpl.php";
  // フッターを表示
  include "$dir/tpl/footer.tpl.php";
  exit;
}

/**
 * エラーメッセージとフォームを同時に表示
 * ユーザーが入力した内容を維持して再表示
 */
function m_show_error_with_form($msg)
{
    // POSTされたパラメータを保持
    $form_caption = "エラー：再入力してください";
    
    // ヘッダを表示
    $dir = dirname(__DIR__);
    include "$dir/tpl/header.tpl.php";
    
    // エラーメッセージ + フォーム
    $body = "<div class='notification is-warning'><strong>エラー:</strong> $msg</div>\n";
    $body .= m_show_form($form_caption);
    
    include "$dir/tpl/body.tpl.php";
    // フッターを表示
    include "$dir/tpl/footer.tpl.php";
    exit;
}

function m_discord_webhook($title, $body, $username, $url)
{
    // check webhook url
    $discord_webhook_url = m_info('discord.webhook_url', '');
    if ($discord_webhook_url == '') {
        return;
    }
    //メッセージの内容を定義
    $contents = "{$username}さんが「{$title}」を投稿しました。\n{$body}\n[URL] {$url}";
    $message = array(
        'username' => m_info('discord_webhook_name', '[manatubbs]'),
        'content'  => $contents
    );
    $message_json = json_encode($message);
    // curlを利用してポスト(非同期)
    $curl_command = sprintf(
        'curl -X POST %s -H "Content-Type: application/json; charset=utf-8" -d %s --insecure > /dev/null 2>&1 &',
        escapeshellarg($discord_webhook_url),
        escapeshellarg($message_json)
    );
    @exec($curl_command);
}

if (!function_exists('m_url')) {
    function m_url($mod = "", $param_str = "")
    {
        $script = 'index.php';
        $r = [];
        if ($mod != '') {
            $r[] = "m=$mod";
        }
        if ($param_str != "") {
            $r[] = $param_str;
        }
        $url = $script . "?" . join("&amp;", $r);
        return $url;
    }
}

if (!function_exists('m_info')) {
    function m_info($param, $default = FALSE)
    {
        global $mbbs;
        return empty($mbbs[$param]) ? $default : $mbbs[$param];
    }
}
