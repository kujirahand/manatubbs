<?php
/*
 * mbbs form
 */


/**
 * show user form
 * @param string $caption
 * @param string $formmode
 * @return string
 */
function m_show_form($caption = "", $formmode = "write")
{
    // 読み取り専用モードチェック
    if (m_info("readonly", FALSE)) {
        return "<div class='readonly-notice'><p>📄 メンテナンス中のため現在読み込み専用です。</p></div>";
    }
    
    if ($caption == "") { $caption = "書き込みフォーム"; }
    global $mbbs;
    extract($mbbs);
    
    // デフォルト値を設定する
    $ff_name    = m_cookie("mbbs_name", "");
    if ($formmode == "editlog") {
        $logid = intval(m_param('logid', 0));
        $r = m_db_query("SELECT * FROM logs WHERE logid=?",[$logid]);
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
        $ff_title   = htmlspecialchars(m_param('mbbs_user_title',''), ENT_QUOTES);
        $ff_body    = htmlspecialchars(m_param('mbbs_user_body',''), ENT_QUOTES);
        $ff_mode    = htmlspecialchars(m_param('mode',''), ENT_QUOTES);
        $ff_status  = htmlspecialchars(m_param('status',''),ENT_QUOTES);
        // new
        if ($caption == "新規で書き込む") {
            $ff_body = htmlspecialchars(m_info("body.template"),ENT_QUOTES);
        }
        // エラー時に入力済みの名前も保持
        if (m_param('mbbs_user_name','') != '') {
            $ff_name = htmlspecialchars(m_param('mbbs_user_name',''), ENT_QUOTES);
        }
    }
    
    $caption_ = preg_replace_callback("|\#(\d+)|","m_logid_embedLink", $caption);
    
    // form items
    $items = array();
    $items[] = m_form_parts("名前",		"mbbs_user_name",	"text",     array("style"=>"width:70%"), $ff_name);
    $items[] = m_form_parts("タイトル",	"mbbs_user_title",	"text",     array("style"=>"width:70%"), $ff_title);
    $items[] = m_form_parts("本文",		"mbbs_user_body",	"textarea", array("style"=>"width:100%;height:130px;"), $ff_body);
    $items[] = m_form_parts(
    	m_info("priority.label"),		"mode",     "select",
                array(
                    'items'=>m_info('priority'),
                    'style'=>'width:200px',
                ), $ff_mode);
    $items[] = m_form_parts(
    	m_info("status.label"),    "status",   "select",
                array(
                    'items'=>m_info('status'),
                    'style'=>'width:200px',
                ), $ff_status);
    if (m_info('bot.enabled')) {
        $bot_key_value = m_param("manatubbs_checkbot", m_cookie("mbbs_botkey",""));
        $items[] = m_form_parts("確認キー","manatubbs_checkbot", "text",
                array(
                    'hint'=>"👆お手数ですが、いたずら防止のために、".m_info('bot.q'),
                    'style'=>'width:200px',
                ), $bot_key_value);
    }
    $editkey_value = m_param("mbbs_user_editkey", m_cookie("mbbs_editkey", ""));
    $items[] = m_form_parts(
    	"編集キー","mbbs_user_editkey",  "password",
                array(
                    'size'=>20,
                    'hint'=>'編集時に使うキーを入力(省略可能)',
                    'style'=>'width:200px',
                ), $editkey_value); // この編集キーは大して重要ではないと思うが sha1 で暗号化したもの。
    $items[] = m_form_parts("添付ファイル", "attach",   "file", 
                array(
                    "hint"=>m_info('upload.format.hint'),
                ));
    $items[] = m_form_parts("", "MAX_FILE_SIZE",  "hidden", array(), m_info("upload.maxsize", 1024*1024));
    $items[] = m_form_parts("", "m",   "hidden", array(), $formmode);
    $items[] = m_form_parts("", "threadid", "hidden", array(), m_param("threadid",0));
    $items[] = m_form_parts("", "parentid", "hidden", array(), m_param("parentid",0));
    $items[] = m_form_parts("", "logid", "hidden", array(), m_param("logid",0));
    $items[] = m_form_parts("", "bot",  "hidden", array(), $mbbs["bot.message"]);
    $items[] = m_form_parts("", "csrf_token", "hidden", array(), m_csrf_generate_token());
    
    return
    "<div class='inputform'>\n".
    "<div class='inputform-title'><a name='inputform'></a>✏️ {$caption_}</div>\n".
    m_build_form($items, "post", $caption, TRUE).
    "</div><!-- end of inputform -->\n";
}
