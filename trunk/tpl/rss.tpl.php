<?xml version="1.0" encoding="utf-8"?>
<?php
// $logs
global $mbbs;
extract($mbbs);
?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xml:lang="ja">
    <channel>
        <title><?php echo $TITLE?></title>
        <link><?php echo htmlspecialchars($linkurl)?></link>
        <description><?php echo $DESCRIPTION?></description>
        <dc:creator><?php echo $AUTHOR?></dc:creator>
        <pubDate><?php echo date("r", time())?></pubDate>
        <language>ja</language>
<?php
/*
        <image>
            <url><?php echo $logourl?></url>
            <title><?php echo $TITLE?></title>
            <link><?php echo $linkurl?></link>
        </image>
*/
foreach ($logs as $log) {
    $logid = $log["logid"];
    $url   = htmlspecialchars($linkurl."?m=log&logid={$logid}");
    $name_ = htmlspecialchars($log['name']);
    $title = htmlspecialchars($log['title']);
    $body = $log['body'];
    $body = preg_replace("#(\r|\r\n|\n)#","",$body);
    $desc  = htmlspecialchars(
                mb_strimwidth($body, 0, 254, ".."));
    $date = date("r", $log['ctime']);
    echo <<< EOS__
        <item>
            <title><![CDATA[{$title}({$name_})]]></title>
            <link>{$url}</link>
            <description><![CDATA[{$desc}]]></description>
            <dc:creator><![CDATA[{$name_}]]></dc:creator>
            <pubDate>{$date}</pubDate>
        </item>

EOS__;
}
?>
    </channel>
</rss>
