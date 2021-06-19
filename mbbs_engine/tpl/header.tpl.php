<?php

global $mbbs;
extract($mbbs);
$linkurl = "//".$_SERVER["HTTP_HOST"]. $_SERVER["SCRIPT_NAME"];

$engine = dirname(__DIR__);
$mtime = filemtime("$engine/resource/mbbs.css");
$css_bulma = m_url('resource', 'f=bulma@0.9.3/bulma.min.css');
$css_mbbs = m_url('resource', "f=mbbs.css&mt=$mtime");

$mbar = m_info("menubar");
$mbar_html = m_create_menu($mbar); 

echo <<< EOS
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="$css_bulma">
  <link rel="stylesheet" type="text/css" href="$css_mbbs">
  <title>{$TITLE}</title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" 
    href="{$linkurl}?m=rss">
</head>
<body>
<!-- header -->
<div id="header" class="menubar">{$mbar_html}</div>
<!-- title -->
<div class="title_block" id="title">
  <h1 class="title"><a href="{$script_name}">{$TITLE}</a></h1>
  <div class="subtitle">{$DESCRIPTION}</div>
</div>

EOS;

