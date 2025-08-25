<?php

/**
 * 基本的な設定
 */
$mbbs["TITLE"]        = "「なでしこ」バグ掲示板";
$mbbs["HOME"]         = "<a href='http://nadesi.com/'>なでしこTOP</a>";
$mbbs["DESCRIPTION"]  = "なでしこのバグ（不具合）を報告する掲示板です。";
$mbbs["AUTHOR"]       = "なでしこユーザー";

/** ログイン機能を使うか */
$mbbs["use.login"]    = TRUE;
$mbbs["users"]        = "user1:pass1,user2:pass2,user3:pass3";
// カンマで区切って user1:pass,user2:pass,user3:pass のように書く

/**
 * データベースの設定(SQLite3)
 */
$root = dirname(__FILE__);
$mbbs["db.name"] = "{$root}/db/manatubbs.sqlite3";
$mbbs["dir.engine"] = "{$root}/mbbs_engine";

/**
 * 各種の初期値
 */
$mbbs["firstview"]       = "threads"; // はじめに表示するページ
#$mbbs["firstview"]       = "tree"; // はじめに表示するページ
$mbbs["threads.perpage"] = 50;
$mbbs["logs.perpage"]    = 20;
$mbbs["tree.perpage"]    = 8;
$mbbs["bot.message"]     = "棚から牡丹餅";
$mbbs["adminpass"]       = "manatu_admin";
$mbbs["repos.link"]      = "http://code.google.com/p/nadesiko/source/detail?r=";

/**
 * スパム対策
 */
$mbbs["bot.q"]           = "「真夏」の読み方を平仮名で記入してください。";
$mbbs["bot.a"]           = "まなつ";
$mbbs["bot.enabled"]     = TRUE;

/**
 * NGワード設定
 * スパム対策として禁止する単語を配列で指定
 * タイトル、本文、名前の全てがチェック対象
 */
$mbbs["ng_words"] = [
    // 例として以下のような単語を指定可能：
    // "spam",
    // "広告",
    // "宣伝",
    // "出会い",
    // "副業"
    // 
    // 実際の運用では、適切なNGワードを設定してください
];

/**
 * アップロードの設定
 */
$mbbs["upload.maxsize"]      = 1024 * 300; // 300KB
$mbbs["upload.format.hint"]  = "画像ファイル(最大300KB)を添付可能";
$mbbs["upload.format"]       = "#\.(jpeg|jpg|png|gif)$#i";
$mbbs["upload.dir"]          = "attach/";

/**
 * メール通知設定
 */
$mbbs["mail.to"]    = "";
$mbbs["mail.cc"]    = "";
$mbbs["mail.bcc"]   = "";
$mbbs["mail.from"]  = "";
$mbbs["mail.title"] = "[manatubbs]";

/**
 * Discordへの通知機能
 * ウェブフックのURLを取得して以下に設定してください。
 */
$mbbs["discord.webhook_url"] = "";
$mbbs["discord.title"] = "[manatubbs]";

/**
 * フィールドの定義
 */
$mbbs["priority.label"] = "優先度";
$mbbs["priority"] = array(
  '低','中','高','緊急'
);
$mbbs["priority.color"] = array(
  '低'  =>array("bgcolor"=>'#ffffff', "color"=>"#888888"),
  '高'  =>array("bgcolor"=>'#f8e0e0', "color"=>"#000000"),
  '緊急'=>array("bgcolor"=>'#f8e0e0', "color"=>"#ff00cc"),
);
$mbbs["status.label"] = "状態";
$mbbs["status"] = array(
  '未処理','調査中','修正中','確認待ち','解決','---','アイデア','感想','告知'
);
$mbbs["status.color"] = array(
  '解決' => array(
    'bgcolor' => '#f0f0ff',
    'color' => '#a0a0a0',
    'style' => 'text-decoration:normal;'
  ),
);

$mbbs["body.template"] = <<<EOS__
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
$mbbs["menubar"] = array();
$mbbs["menubar"][] = array("label"=>"新規",     "link"=>"{$script_name}#inputform");
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"ツリー",   "link"=>m_url("tree"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"スレッド", "link"=>m_url("threads"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"未解決",   "link"=>m_url("mikaiketu"));
$mbbs["menubar"][] = array("label"=>"緊急",     "link"=>m_url("kinkyu"));
$mbbs["menubar"][] = array("label"=>"優先",     "link"=>m_url("yusen"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"検索",     "link"=>m_url("search"));
$mbbs["menubar"][] = array("label"=>"RSS",      "link"=>m_url("rss"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("type"=>"html",      "link"=>m_info("HOME"));

