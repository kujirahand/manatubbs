<?php

require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_db.inc.php';
require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_lib.inc.php';

function assert_tree_test($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\nExpected: ".var_export($expected, true)."\nActual: ".var_export($actual, true)."\n");
        exit(1);
    }
}

global $mbbs_db, $mbbs;
$mbbs_db = new PDO('sqlite::memory:');
$mbbs_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$mbbs_db->exec('CREATE TABLE threads (threadid INTEGER PRIMARY KEY, title TEXT, mode TEXT, status TEXT, count INTEGER, mtime INTEGER, ctime INTEGER)');
$mbbs_db->exec('CREATE TABLE logs (logid INTEGER PRIMARY KEY, threadid INTEGER, parentid INTEGER, title TEXT, body TEXT, name TEXT, ip TEXT, editkey TEXT, mode TEXT, status TEXT, mtime INTEGER, ctime INTEGER)');

$mbbs = [
    'script' => 'index.php',
    'script_name' => 'index.php',
    'tree.perpage' => 10,
];

// 1. 未解決スレッドのデータ
$mbbs_db->exec("INSERT INTO threads (threadid, title, mode, status, count, mtime, ctime) VALUES (1, '未解決スレッド', '中', '未処理', 1, 1000, 1000)");
$mbbs_db->exec("INSERT INTO logs (logid, threadid, parentid, title, body, name, ip, editkey, mode, status, mtime, ctime) VALUES (1, 1, 0, '未解決スレッド', '内容1', '名無し', '127.0.0.1', '', '中', '未処理', 1000, 1000)");

// 2. 解決済みスレッドのデータ
$mbbs_db->exec("INSERT INTO threads (threadid, title, mode, status, count, mtime, ctime) VALUES (2, '解決済みスレッド', '高', '解決', 1, 2000, 2000)");
$mbbs_db->exec("INSERT INTO logs (logid, threadid, parentid, title, body, name, ip, editkey, mode, status, mtime, ctime) VALUES (2, 2, 0, '解決済みスレッド', '内容2', '名無し', '127.0.0.1', '', '高', '解決', 2000, 2000)");

$tree_html = m_show_tree();

// 解決済みスレッドに is-solved クラスと solved-badge が含まれていることを確認
assert_tree_test(true, strpos($tree_html, "class='indexbox is-solved'") !== false, '解決済みスレッドに indexbox is-solved が付与されている');
assert_tree_test(true, strpos($tree_html, "class='solved-badge'") !== false, '解決済みスレッドに solved-badge が表示されている');

// 未解決スレッドは通常の indexbox であることを確認
assert_tree_test(true, strpos($tree_html, "<div class='indexbox'>") !== false, '未解決スレッドは通常の indexbox が使用される');

echo "OK\n";
