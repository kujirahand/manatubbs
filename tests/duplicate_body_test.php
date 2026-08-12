<?php

require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_db.inc.php';

function assert_same($expected, $actual, $message)
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
    'logid INTEGER PRIMARY KEY, body TEXT, ctime INTEGER)'
);

$now = 1000;
$since = $now - 180;
m_db_exec(
    'INSERT INTO logs (body, ctime) VALUES (?, ?), (?, ?)',
    ['同じ本文', $since - 1, '別の本文', $now]
);

assert_same(
    false,
    m_db_has_duplicate_body_since('同じ本文', $since),
    '3分より前の同一本文は許可する'
);
assert_same(
    false,
    m_db_has_duplicate_body_since('未投稿の本文', $since),
    '異なる本文は許可する'
);

m_db_exec(
    'INSERT INTO logs (body, ctime) VALUES (?, ?)',
    ['同じ本文', $since]
);

assert_same(
    true,
    m_db_has_duplicate_body_since('同じ本文', $since),
    'ちょうど3分前の同一本文は拒否する'
);
assert_same(
    false,
    m_db_has_duplicate_body_since("同じ本文\n", $since),
    '本文は完全一致で比較する'
);

echo "OK\n";
