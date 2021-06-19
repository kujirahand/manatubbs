<?php

global $mbbs;
extract($mbbs);
$linkurl = "//".$_SERVER["HTTP_HOST"]. $_SERVER["SCRIPT_NAME"];

$root = dirname(__DIR__);
$mtime = filemtime("$root/pub/mbbs.css");

$mbar = m_info("menubar");
$mbar_html = m_create_menu($mbar); 

echo <<< EOS
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="pub/bulma@0.9.3/bulma.min.css">
  <link rel="stylesheet" type="text/css" href="pub/mbbs.css?mtime={$mtime}">
  <title>{$TITLE}</title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" 
    href="{$linkurl}?m=rss">
</head>
<body>
<!-- header -->
<div id="header" class="menubar">{$mbar_html}</div>
<!-- title -->
<div class="title" id="title">
  <h1><a href="{$script_name}">{$TITLE}</a></h1>
  <div class="subtitle">{$DESCRIPTION}</div>
</div>

EOS;

