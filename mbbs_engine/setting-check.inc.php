<?php
/**
 * 基本的な設定
 */
$defvalue["TITLE"] = "manatubbs";
$defvalue["HOME"] = "<a href='https://github.com/kujirahand/manatubbs'>manatubbs</a>";
$defvalue["DESCRIPTION"]  = "掲示板";
$defvalue["AUTHOR"]       = "manatubbsのユーザー";

/** ログイン機能を使うか */
$defvalue["use.login"]    = TRUE;
$defvalue["users"]        = "user1:pass1,user2:pass2,user3:pass3";
// カンマで区切って user1:pass,user2:pass,user3:pass のように書く

/**
 * データベースの設定
 */
$root = dirname(__DIR__);
$defvalue["db.name"] = "{$root}/db/manatubbs.sqlite3";

/**
 * 各種の初期値
 */
$defvalue["firstview"]       = "threads"; // はじめに表示するページ
#$defvalue["firstview"]       = "tree"; // はじめに表示するページ
$defvalue["threads.perpage"] = 50;
$defvalue["logs.perpage"]    = 20;
$defvalue["tree.perpage"]    = 8;
$defvalue["bot.message"]     = "棚から牡丹餅";
$defvalue["adminpass"]       = "manatu_admin";
$defvalue["repos.link"]      = "https://github.com/kujirahand/manatubbs";

/**
 * スパム対策
 */
$defvalue["bot.q"]           = "「真夏」の読み方を記入してください。";
$defvalue["bot.a"]           = "まなつ";
$defvalue["bot.enabled"]     = TRUE;

$defvalue["ng_words"] = [];


/**
 * 読み取り専用モード設定
 */
$defvalue["readonly"]        = FALSE;

/**
 * アップロードの設定
 */
$defvalue["upload.maxsize"]      = 1024 * 300; // 300KB
$defvalue["upload.format.hint"]  = "画像ファイル(最大300KB)を添付可能";
$defvalue["upload.format"]       = "#\.(jpeg|jpg|png|gif)$#i";
$defvalue["upload.dir"]          = "{$root}/attach/";

/**
 * メール通知設定
 */
$defvalue["mail.to"]    = "";
$defvalue["mail.cc"]    = "";
$defvalue["mail.bcc"]   = "";
$defvalue["mail.from"]  = "";
$defvalue["mail.title"] = "[manatubbs]";

/**
 * フィールドの定義
 */
$defvalue["priority.label"] = "優先度";
$defvalue["priority"] = array(
  '低','中','高','緊急'
);
$defvalue["priority.color"] = array(
  '低'  =>array("bgcolor"=>'#ffffff', "color"=>"#888888"),
  '高'  =>array("bgcolor"=>'#f8e0e0', "color"=>"#000000"),
  '緊急'=>array("bgcolor"=>'#f8e0e0', "color"=>"#ff00cc"),
);
$defvalue["status.label"] = "状態";
$defvalue["status"] = array(
  '未処理','調査中','修正中','確認待ち','解決','---','アイデア','感想','告知'
);
$defvalue["status.color"] = array(
  '解決' => array(
    'bgcolor' => '#f0f0ff',
    'color' => '#a0a0a0',
    'style' => 'text-decoration:normal;'
  ),
);

$defvalue["body.template"] = <<<EOS__
【症状】どのような症状、現象か？
【再現方法】サンプルソース、再現手順など
【要望】どのような解決が望ましいか？
【バージョン】確認したバージョン
【その他】
EOS__;

/**
 * メニューの定義
 */
$script_name = 'index.php';
$defvalue["menubar"] = array();
$defvalue["menubar"][] = array("label"=>"新規",     "link"=>"{$script_name}#inputform");
$defvalue["menubar"][] = array("type"=>"-");
$defvalue["menubar"][] = array("label"=>"ツリー",   "link"=>m_url("tree"));
$defvalue["menubar"][] = array("type"=>"-");
$defvalue["menubar"][] = array("label"=>"スレッド", "link"=>m_url("threads"));
$defvalue["menubar"][] = array("type"=>"-");
$defvalue["menubar"][] = array("label"=>"未解決",   "link"=>m_url("mikaiketu"));
$defvalue["menubar"][] = array("label"=>"緊急",     "link"=>m_url("kinkyu"));
$defvalue["menubar"][] = array("label"=>"優先",     "link"=>m_url("yusen"));
$defvalue["menubar"][] = array("type"=>"-");
$defvalue["menubar"][] = array("label"=>"検索",     "link"=>m_url("search"));
$defvalue["menubar"][] = array("label"=>"RSS",      "link"=>m_url("rss"));
$defvalue["menubar"][] = array("type"=>"-");
$defvalue["menubar"][] = array("type"=>"html",      "link"=>m_info("HOME"));

// 最低限必要な設定をチェックする
global $mbbs;
foreach ($defvalue as $key => $def) {
  if (!isset($mbbs[$key])) { $mbbs[$key] = $def; }
}


