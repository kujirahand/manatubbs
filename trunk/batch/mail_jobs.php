#!/usr/local/bin/php
<?php
mb_internal_encoding("UTF-8");
//
// 日々優先度の高い案件をメールを通知してくれるバッチファイル
//
//----------------------------------------------------------------------
global $mbbs;
$mbbs = array();
$mbbs["db.handle"] = NULL;
// ライブラリの取り込み
$root = dirname(dirname(__FILE__));
$inc = $root.'/inc';
require_once "{$inc}/mbbs_lib.inc.php";
require_once "{$inc}/mbbs_db.inc.php";
require_once "{$inc}/mbbs_form.inc.php";
//----------------------------------------------------------------------
// 初期設定の取り込み
require_once "{$root}/setting-org.ini.php";
if (file_exists("{$root}/setting-user.ini.php")) {
    require_once "{$root}/setting-user.ini.php";
}
$mbbs["mode"] = $mbbs["priority"];
remove_magic_quotes_gpc(); // remove
//----------------------------------------------------------------------
// データベースの初期化処理
m_initDB();
$conf = dirname(dirname(__FILE__)).'setting-user.ini.php';
if (file_exists($conf)) {
	include_once $conf;
}
//----------------------------------------------------------------------
// 優先順にメール送信
$text = yusen_text();
send_text($text);

function yusen_text()
{
    $body = m_show_all_text("緊急のもの","status!='解決' AND mode='緊急'","yusen")."\n".
            m_show_all_text("高のもの","status!='解決' AND mode='高'","yusen")."\n".
            m_show_all_text("中のもの","status!='解決' AND mode='中'","yusen")."\n".
            "";
	return $body;
}

function send_text($text)
{
  $to      = m_info("mail.to");
  $subject = m_info("mail.title") . "優先度リスト";
  $body    =  $text . "\n";
  $from = m_info("mail.from","");
  $cc   = m_info("mail.cc","");
  $bcc  = m_info("mail.bcc","");
  $header = "From:$from
Cc:$cc
Bcc:$bcc
";
  mb_send_mail($to, $subject, $body, $header);
}


