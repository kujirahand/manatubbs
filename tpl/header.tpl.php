<?php
global $mbbs;
extract($mbbs);
$linkurl = "http://".$_SERVER["HTTP_HOST"]. $_SERVER["SCRIPT_NAME"];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <link rel="stylesheet" type="text/css" href="mbbs.css">
  <title><?php echo $TITLE?></title>
  <link rel="alternate" type="application/rss+xml" title="RSS 2.0" href="<?php echo $linkurl?>?m=rss">
</head>
<body>
<!-- header -->
<div id="header">
<?php echo m_create_menu(m_info("menubar")); ?>
</div>
<!-- title -->
<div id="title"><h1><a href="<?php echo $script_name?>"><?php echo $TITLE?></a></h1></div>
<div class="description"><?php echo $DESCRIPTION?></div>
