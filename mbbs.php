<?php
$m = isset($_GET['m']) ? $_GET['m'] : '';
$m = urlencode($m);
if ($m != '') {
  $url = "./index.php?m={$m}";
} else {
  $url = './index.php';
}
header("location: {$url}");
echo <<< __EOS
<html>
<head>
<meta http-equiv="Refresh" content="0;URL={$url}">
</head>
<body>
<a href="{$url}">bbs</a>
</body>
</html>
__EOS;

