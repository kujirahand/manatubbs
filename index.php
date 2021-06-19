<?php
//----------------------------------------------------------------------
// manatubbs
//----------------------------------------------------------------------
// 変数の初期化
global $mbbs;
$mbbs = array();
$mbbs["db.handle"] = NULL;

//----------------------------------------------------------------------
// ディレクトリの指定
//    複数BBS設置のためエンジン変更するなら setting-user.ini.php で以下を変更
$mbbs['dir.engine'] = __DIR__.'/mbbs_engine';

//----------------------------------------------------------------------
// 初期設定の取り込み
$setting_user_file = __DIR__.'/setting-user.ini.php';
if (file_exists($setting_user_file)) {
  require_once $setting_user_file;
}

//----------------------------------------------------------------------
// ディレクトリの確認
$dir_engine = $mbbs['dir.engine'];
$file_main = $dir_engine.'/index.inc.php';
if (!file_exists($dir_engine)) {
  echo 'Please set $mbbs["dir.engine"] in setting-user.ini.php'; exit;
}
include_once $file_main;

//----------------------------------------------------------------------
// 設定で使われる関数
function m_url($mod = "", $param_str = "") {
  $script = 'index.php';
  $r = [];
  if ($mod != '') { $r[] = "m=$mod"; }
  if ($param_str != "") { $r[] = $param_str; }
  $url = $script."?".join("&amp;", $r);
  return $url;
}
function m_info($param, $default = FALSE)
{
    global $mbbs;
    return empty($mbbs[$param]) ? $default : $mbbs[$param];
}

