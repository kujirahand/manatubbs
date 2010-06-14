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
    if ($caption == "") { $caption = "書き込みフォーム"; }
    global $mbbs;
    extract($mbbs);
    
    // デフォルト値を設定する
    $ff_name    = m_cookie("mbbs_name", "");
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
        $ff_title   = htmlspecialchars(m_param('mbbs_user_title',''), ENT_QUOTES);
        $ff_body    = "";
        $ff_mode    = htmlspecialchars(m_param('mode',''), ENT_QUOTES);
        $ff_status  = htmlspecialchars(m_param('status',''),ENT_QUOTES);
        // new
        if ($caption == "新規で書き込む") {
            $ff_body = htmlspecialchars(m_info("body.template"),ENT_QUOTES);
        }
    }
    
    $caption_ = preg_replace_callback("|\#(\d+)|","m_logid_embedLink", $caption);
    
    // form items
    $items = array();
    $items[] = m_form_parts("名前",		"mbbs_user_name",	"text",     array("style"=>"width:70%"), $ff_name);
    $items[] = m_form_parts("タイトル",	"mbbs_user_title",	"text",     array("style"=>"width:70%"), $ff_title);
    $items[] = m_form_parts("本文",		"mbbs_user_body",	"textarea", array("style"=>"width:90%;height:130px;"), $ff_body);
    $items[] = m_form_parts(
    	m_info("priority.label"),		"mode",     "select",
                array(
                    'items'=>m_info('mode'),
                    'style'=>'width:200px',
                ), $ff_mode);
    $items[] = m_form_parts(
    	m_info("status.label"),    "status",   "select",
                array(
                    'items'=>m_info('status'),
                    'style'=>'width:200px',
                ), $ff_status);
    if (m_info('bot.enabled')) {
        $items[] = m_form_parts("確認キー","manatubbs_checkbot", "text",
                array(
                    'hint'=>"お手数ですが、いたずら防止のために、".m_info('bot.q'),
                    'style'=>'width:200px',
                ), m_cookie("mbbs_botkey",""));
    }
    $items[] = m_form_parts(
    	"編集キー","mbbs_user_editkey",  "password",
                array(
                    'size'=>20,
                    'hint'=>'編集時に使うキーを入力(省略可能)',
                    'style'=>'width:200px',
                ), m_cookie("mbbs_editkey", "")); // この編集キーは大して重要ではないと思うが sha1 で暗号化したもの。
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
    
    return
    "<div class='inputform'>\n".
    "<div><a name='inputform'>→</a>{$caption_}:</div><br/>\n".
    m_build_form($items, "post", $caption, TRUE).
    "</div><!-- end of inputform -->\n";
}
