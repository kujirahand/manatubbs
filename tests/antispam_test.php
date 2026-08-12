<?php

require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_lib.inc.php';
require_once dirname(__DIR__).'/mbbs_engine/inc/mbbs_form.inc.php';

function assert_antispam_test($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$_SESSION = array();

$token = m_antispam_issue_form_token(100000);
assert_antispam_test(
    $token,
    m_antispam_get_form_token($token, 105000),
    'エラー後は有効なフォームトークンを引き継ぐ'
);
assert_antispam_test(
    'too_fast',
    m_antispam_validate($token, '', 109999, 10000),
    '設定時間の1ミリ秒前では拒否する'
);
assert_antispam_test(
    'ok',
    m_antispam_validate($token, '', 110000, 10000),
    '設定したミリ秒数で許可する'
);
assert_antispam_test(
    'honeypot',
    m_antispam_validate($token, 'https://spam.example/', 110000, 10000),
    'ハニーポットへ入力された投稿は拒否する'
);
assert_antispam_test(
    'invalid_token',
    m_antispam_validate('unknown', '', 110000, 10000),
    '存在しないフォームトークンは拒否する'
);

$expired_token = m_antispam_issue_form_token(200000);
assert_antispam_test(
    'invalid_token',
    m_antispam_validate($expired_token, '', 3800001, 10000),
    '1時間を超えたフォームトークンは拒否する'
);

$_SESSION = array();
$first_token = m_antispam_issue_form_token(100000);
$preopened_token = m_antispam_issue_form_token(100000);
m_antispam_mark_success($first_token, 120000);
assert_antispam_test(
    'invalid_token',
    m_antispam_validate($first_token, '', 130000, 10000),
    '使用済みフォームトークンは再利用できない'
);
assert_antispam_test(
    'too_fast',
    m_antispam_validate($preopened_token, '', 129999, 10000),
    '事前に別タブを開いていても設定時間内は拒否する'
);
assert_antispam_test(
    'ok',
    m_antispam_validate($preopened_token, '', 130000, 10000),
    '前回の成功から設定したミリ秒数で許可する'
);

$mbbs = array('antispam.min_wait_ms' => 2500);
assert_antispam_test(
    2500,
    m_antispam_get_min_wait_ms(),
    '設定ファイルの待機時間をミリ秒単位で取得する'
);

$mbbs = array(
    'script_name' => 'index.php',
    'bot.message' => 'form-check',
    'antispam.min_wait_ms' => 10000,
    'body.template' => '',
    'priority.label' => '優先度',
    'priority' => array('通常'),
    'status.label' => '状態',
    'status' => array('未処理'),
    'upload.format.hint' => '画像のみ',
    'upload.maxsize' => 300000,
);
$_POST = array();
$_GET = array();
$_COOKIE = array();
$form_html = m_show_form('新規で書き込む');
assert_antispam_test(
    true,
    strpos($form_html, "name='website'") !== false,
    'フォームにハニーポットを出力する'
);
assert_antispam_test(
    true,
    strpos($form_html, "name='mbbs_form_token'") !== false,
    'フォームに待機時間検証用トークンを出力する'
);
assert_antispam_test(
    false,
    strpos($form_html, 'manatubbs_checkbot') !== false,
    '確認キーを出力しない'
);

echo "OK\n";
