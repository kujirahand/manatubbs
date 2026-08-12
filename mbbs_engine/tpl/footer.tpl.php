<?php
global $mbbs;
extract($mbbs);

$home = m_info("HOME");
$ver = MBBS_VER;

echo <<< __EOS__
<div id="footer">
  <div class="footer-links">
    <span class="footer-item">{$home}</span>
    <span class="footer-sep">・</span>
    <span class="footer-item"><a href="https://kujirahand.com/wiki/index.php?manatubbs">manatubbs v.{$ver}</a></span>
  </div>
</div>
</body>
</html>
__EOS__;

