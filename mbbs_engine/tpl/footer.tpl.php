<?php
global $mbbs;
extract($mbbs);

$mbar = m_info("menubar");
$mbar_html = m_create_menu($mbar); 
$ver = MBBS_VER;

echo <<< __EOS__
<div id="footer">
  <div class="menubar">{$mbar_html}</div>
  
  <p>
    <a href="{$script_name}?m=rss">♪RSS</a>
    -
    <a href="https://kujirahand.com/wiki/index.php?manatubbs">
     manatubbs v.{$ver}</a>
  </p>
  <br><br>
</div>
</body>
</html>
__EOS__;

