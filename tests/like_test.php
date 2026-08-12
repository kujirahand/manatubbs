<?php

require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_db.inc.php';

function assert_like_test($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

global $mbbs_db;
$mbbs_db = new PDO('sqlite::memory:');
$mbbs_db->exec(
    'CREATE TABLE logs ('.
    'logid INTEGER PRIMARY KEY, threadid INTEGER, body TEXT)'
);
$mbbs_db->exec(
    'CREATE TABLE threads ('.
    'threadid INTEGER PRIMARY KEY, status TEXT, mtime INTEGER)'
);

assert_like_test(
    true,
    m_db_ensure_logs_likes_column($mbbs_db),
    '既存DBへlikes列を追加できる'
);

$columns = m_db_query('PRAGMA table_info(logs)', []);
$column_names = array_column($columns, 'name');
assert_like_test(true, in_array('likes', $column_names, true), 'likes列が存在する');

m_db_exec('INSERT INTO logs (logid, threadid, body) VALUES (?, ?, ?)', [1, 10, '本文']);
$log = m_db_query('SELECT likes FROM logs WHERE logid=?', [1]);
assert_like_test(0, intval($log[0]['likes']), '初期値は0になる');

assert_like_test(true, m_db_increment_log_likes(1), 'いいねを加算できる');
assert_like_test(true, m_db_increment_log_likes(1), 'いいねを再度加算できる');
$log = m_db_query('SELECT likes FROM logs WHERE logid=?', [1]);
assert_like_test(2, intval($log[0]['likes']), '加算後の件数が保存される');

m_db_exec('INSERT INTO logs (logid, threadid, body, likes) VALUES (?, ?, ?, ?)', [2, 10, '返信', 8]);
m_db_exec('INSERT INTO threads (threadid, status, mtime) VALUES (?, ?, ?), (?, ?, ?)', [10, '未処理', 200, 20, '解決', 100]);
$threads = m_db_get_threads_with_likes(false, 10, 0);
assert_like_test(10, intval($threads[0]['likes']), 'スレッド内のいいね数を合計する');
assert_like_test(0, intval($threads[1]['likes']), '投稿がないスレッドのいいね数は0になる');

$threads = m_db_get_threads_with_likes("status!='解決'", 10, 0);
assert_like_test(1, count($threads), 'スレッドの絞り込み条件を維持する');
assert_like_test(10, intval($threads[0]['likes']), '絞り込み後もいいね数を合計する');

assert_like_test(
    true,
    m_db_ensure_logs_likes_column($mbbs_db),
    'likes列が存在していても再実行できる'
);

echo "OK\n";
