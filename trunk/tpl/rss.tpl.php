<?php
// $logs
global $mbbs;
extract($mbbs);

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xml:lang="ja">
    <channel>
        <title><?=$TITLE?></title>
        <link><?=htmlspecialchars($linkurl)?></link>
        <description><?=$DESCRIPTION?></description>
        <dc:creator><?=$AUTHOR?></dc:creator>
        <pubDate><?=date("r", time())?></pubDate>
        <language>ja</language>

<?php
/*
        <image>
            <url><?=$logourl?></url>
            <title><?=$TITLE?></title>
            <link><?=$linkurl?></link>
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
