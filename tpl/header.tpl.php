<?php
global $mbbs;
extract($mbbs);
$linkurl = "http://".$_SERVER["HTTP_HOST"]. $_SERVER["SCRIPT_NAME"];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="mbbs.css">
  <title><?php echo $TITLE?></title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo $linkurl?>?m=rss">
</head>
<body>
<!-- header -->
<div id="header">
<?php 
$mbar = m_info("menubar");
echo m_create_menu($mbar); 
?>
</div>
<!-- title -->
<div id="title"><h1><a href="<?php echo $script_name?>"><?php echo $TITLE?></a></h1></div>
<div class="description"><?php echo $DESCRIPTION?></div>
