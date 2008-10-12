<?php

/**
 * 基本的な設定
 */
$mbbs["TITLE"]        = "「なでしこ」バグ掲示板";
$mbbs["HOME"]         = "<a href='http://nadesi.com/'>なでしこTOP</a>";
$mbbs["DESCRIPTION"]  = "なでしこのバグ（不具合）を報告する掲示板です。";
$mbbs["AUTHOR"]       = "なでしこユーザー";

/**
 * データベースの設定
 */
$mbbs["db.name"] = "db/manatubbs.db";

/**
 * 各種の初期値
 */
$mbbs["firstview"]       = "threads"; // はじめに表示するページ
#$mbbs["firstview"]       = "tree"; // はじめに表示するページ
$mbbs["threads.perpage"] = 20;
$mbbs["logs.perpage"]    = 10;
$mbbs["tree.perpage"]    = 8;
$mbbs["script_name"]     = "mbbs.php";
$mbbs["bot.message"]     = "棚から牡丹餅";
$mbbs["adminpass"]       = "xxx";
$mbbs["bot.q"]           = "「なでしこ」と記入してください。";
$mbbs["bot.a"]           = "なでしこ";
$mbbs["repos.link"]      = "http://code.google.com/p/nadesiko/source/detail?r=";

/**
 * フィールドの定義
 */
$mbbs["priority.label"] = "重要度";
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
    '解決' => array('bgcolor' => '#f0f0f0', 'color' => '#aaaaaa'),
);

$mbbs["body.template"] = <<<EOS__
【症状】どのような症状、現象か？
【再現方法】サンプルソース、再現手順など
【要望】どのような解決が望ましいか？
【バージョン】なでしこのバージョン
【その他】
EOS__;

/**
 * メニューの定義
 */
$script_name = $mbbs["script_name"];
$mbbs["menubar"] = array();
$mbbs["menubar"][] = array("label"=>"新規",     "link"=>"{$script_name}#inputform");
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"ツリー",   "link"=>m_url("tree"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"スレッド", "link"=>m_url("threads"));
$mbbs["menubar"][] = array("label"=>"未解決",   "link"=>m_url("mikaiketu"));
$mbbs["menubar"][] = array("label"=>"緊急",     "link"=>m_url("kinkyu"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("label"=>"検索",     "link"=>m_url("search"));
$mbbs["menubar"][] = array("label"=>"RSS",      "link"=>m_url("rss"));
$mbbs["menubar"][] = array("type"=>"-");
$mbbs["menubar"][] = array("type"=>"html",      "link"=>m_info("HOME"));

?>
