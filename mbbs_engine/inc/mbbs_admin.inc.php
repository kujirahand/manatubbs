<?php
//----------------------------------------------------------------------
// manatubbs admin functions
//----------------------------------------------------------------------

/**
 * 管理画面のメイン関数
 */
function m_mode__admin() {
  // 管理者パスワードのチェック
  $admin_pass = m_param('admin_pass', '');
  $action = m_param('action', '');
  $log_id = m_param('log_id', '');
  $thread_id = m_param('thread_id', '');
  
  // CSRFトークンの処理
  $csrf_token = m_param('csrf_token', '');
  
  // デバッグ情報
  $debug_info = "";
  if (m_info('debug.mode', false)) {
    $debug_info = "Debug: admin_pass='$admin_pass', action='$action', log_id='$log_id', thread_id='$thread_id', csrf_token='$csrf_token'";
  }
  
  // パスワードが正しいかチェック
  $correct_pass = m_info('adminpass', 'admin');
  $is_authenticated = false;
  
  if (!empty($admin_pass) && $admin_pass === $correct_pass) {
    $is_authenticated = true;
    
    // ログ削除処理
    if ($action === 'delete' && !empty($log_id) && is_numeric($log_id)) {
      // 管理画面では簡単なCSRFチェックを行う（セッションベースではなく）
      if (empty($csrf_token)) {
        $message = "セキュリティトークンがありません。" . ($debug_info ? "<br>$debug_info" : "");
      } else {
        // ログを削除
        $delete_result = m_admin_delete_log($log_id);
        if ($delete_result) {
          // 削除成功メッセージを設定
          $message = "ログID: {$log_id} を削除しました。";
        } else {
          $message = "ログの削除に失敗しました。データベースエラーの可能性があります。" . ($debug_info ? "<br>$debug_info" : "");
        }
      }
    }
    
    // スレッド削除処理
    if ($action === 'delete_thread' && !empty($thread_id) && is_numeric($thread_id)) {
      // 管理画面では簡単なCSRFチェックを行う（セッションベースではなく）
      if (empty($csrf_token)) {
        $message = "セキュリティトークンがありません。" . ($debug_info ? "<br>$debug_info" : "");
      } else {
        // スレッドを削除
        $delete_result = m_admin_delete_thread($thread_id);
        if ($delete_result['success']) {
          // 削除成功メッセージを設定
          $message = "スレッドID: {$thread_id} を削除しました。（削除されたログ数: {$delete_result['log_count']}）";
        } else {
          $message = "スレッドの削除に失敗しました。" . $delete_result['error'] . ($debug_info ? "<br>$debug_info" : "");
        }
      }
    }
  } else if (!empty($admin_pass)) {
    $message = "管理者パスワードが間違っています。";
  }
  
  // 管理画面の表示
  m_admin_show_page($is_authenticated, $message ?? '');
}

/**
 * ログを削除する関数
 */
function m_admin_delete_log($log_id) {
  global $mbbs_db;
  
  // デバッグ情報
  if (m_info('debug.mode', false)) {
    error_log("Admin delete: Attempting to delete log_id = $log_id");
  }
  
  // まず削除対象のログが存在するかチェック
  $check_query = "SELECT logid, title, name FROM logs WHERE logid = ?";
  $result = m_db_query($check_query, [$log_id]);
  
  if (empty($result)) {
    if (m_info('debug.mode', false)) {
      error_log("Admin delete: Log with ID $log_id not found");
    }
    return false; // ログが存在しない
  }
  
  if (m_info('debug.mode', false)) {
    error_log("Admin delete: Found log with ID $log_id, title: " . $result[0]['title']);
  }
  
  // ログを削除
  $delete_query = "DELETE FROM logs WHERE logid = ?";
  $success = m_db_exec($delete_query, [$log_id]);
  
  if (m_info('debug.mode', false)) {
    error_log("Admin delete: Delete query result = " . ($success ? 'true' : 'false'));
  }
  
  if ($success) {
    // 関連するスレッドの更新時刻を更新
    $thread_query = "SELECT threadid FROM logs WHERE logid = ?";
    $thread_result = m_db_query("SELECT threadid FROM logs WHERE threadid = (SELECT threadid FROM logs WHERE logid = ? LIMIT 1) LIMIT 1", [$log_id]);
    
    if (!empty($thread_result)) {
      $thread_id = $thread_result[0]['threadid'];
      // スレッドのカウントを更新
      $count_query = "SELECT COUNT(*) as cnt FROM logs WHERE threadid = ?";
      $count_result = m_db_query($count_query, [$thread_id]);
      $count = $count_result[0]['cnt'];
      
      // スレッドの更新
      $update_thread_query = "UPDATE threads SET count = ?, mtime = ? WHERE threadid = ?";
      m_db_exec($update_thread_query, [$count, time(), $thread_id]);
    }
  }
  
  return $success;
}

/**
 * スレッドを削除する関数（関連するログも全て削除）
 */
function m_admin_delete_thread($thread_id) {
  global $mbbs_db;
  
  // デバッグ情報
  if (m_info('debug.mode', false)) {
    error_log("Admin delete thread: Attempting to delete thread_id = $thread_id");
  }
  
  // まず削除対象のスレッドが存在するかチェック
  $check_query = "SELECT threadid, title FROM threads WHERE threadid = ?";
  $result = m_db_query($check_query, [$thread_id]);
  
  if (empty($result)) {
    if (m_info('debug.mode', false)) {
      error_log("Admin delete thread: Thread with ID $thread_id not found");
    }
    return ['success' => false, 'error' => 'スレッドが存在しません。', 'log_count' => 0];
  }
  
  if (m_info('debug.mode', false)) {
    error_log("Admin delete thread: Found thread with ID $thread_id, title: " . $result[0]['title']);
  }
  
  // スレッドに含まれるログの数を取得
  $log_count_query = "SELECT COUNT(*) as cnt FROM logs WHERE threadid = ?";
  $log_count_result = m_db_query($log_count_query, [$thread_id]);
  $log_count = $log_count_result[0]['cnt'];
  
  // トランザクション開始（複数のテーブルを操作するため）
  $mbbs_db->beginTransaction();
  
  try {
    // 関連するログを全て削除
    $delete_logs_query = "DELETE FROM logs WHERE threadid = ?";
    $logs_success = m_db_exec($delete_logs_query, [$thread_id]);
    
    if (!$logs_success) {
      throw new Exception("ログの削除に失敗しました");
    }
    
    // スレッドを削除
    $delete_thread_query = "DELETE FROM threads WHERE threadid = ?";
    $thread_success = m_db_exec($delete_thread_query, [$thread_id]);
    
    if (!$thread_success) {
      throw new Exception("スレッドの削除に失敗しました");
    }
    
    // コミット
    $mbbs_db->commit();
    
    if (m_info('debug.mode', false)) {
      error_log("Admin delete thread: Successfully deleted thread $thread_id with $log_count logs");
    }
    
    return ['success' => true, 'error' => '', 'log_count' => $log_count];
    
  } catch (Exception $e) {
    // ロールバック
    $mbbs_db->rollback();
    
    if (m_info('debug.mode', false)) {
      error_log("Admin delete thread: Error - " . $e->getMessage());
    }
    
    return ['success' => false, 'error' => $e->getMessage(), 'log_count' => 0];
  }
}

/**
 * 管理画面のページを表示する関数
 */
function m_admin_show_page($is_authenticated, $message = '') {
  // 簡素なHTMLページを直接出力
  $csrf_token = uniqid('admin_', true); // 管理画面用の簡単なトークン
  
  echo "<!DOCTYPE html><html><head>";
  echo "<meta charset='UTF-8'>";
  echo "<title>管理画面</title>";
  echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .container{max-width:800px;} .card{border:1px solid #ddd;padding:20px;margin:20px 0;} .button{padding:8px 16px;margin:4px;cursor:pointer;} .is-primary{background:#3273dc;color:white;border:none;} .is-danger{background:#f14668;color:white;border:none;} .is-info{background:#3298dc;color:white;border:none;} .input{padding:8px;border:1px solid #ddd;width:200px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f5f5f5;}</style>";
  echo "</head><body>";
  echo "<div class='container'>";
  echo "<h1>管理画面</h1>";
  
  if (!empty($message)) {
    echo "<div style='background:#d4edda;color:#155724;padding:10px;border:1px solid #c3e6cb;margin:10px 0;'>$message</div>";
  }
  
  if (!$is_authenticated) {
    // パスワード入力フォーム
    echo "<div class='card'>";
    echo "<h2>管理者認証</h2>";
    echo "<form method='POST'>";
    echo "<label>管理者パスワード:</label><br>";
    echo "<input class='input' type='password' name='admin_pass' required><br><br>";
    echo "<input type='hidden' name='m' value='admin'>";
    echo "<input type='hidden' name='csrf_token' value='".htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8')."'>";
    echo "<button class='button is-primary' type='submit'>ログイン</button>";
    echo "</form>";
    echo "</div>";
  } else {
    // 管理機能の表示
    echo "<div class='card'>";
    echo "<h2>ログ削除</h2>";
    echo "<form method='POST'>";
    echo "<label>削除するログID:</label><br>";
    echo "<input class='input' type='number' name='log_id' placeholder='例: 123' required><br>";
    echo "<p>削除したいログのIDを入力してください。この操作は取り消せません。</p>";
    echo "<input type='hidden' name='m' value='admin'>";
    echo "<input type='hidden' name='action' value='delete'>";
    echo "<input type='hidden' name='admin_pass' value='".htmlspecialchars(m_param('admin_pass', ''), ENT_QUOTES, 'UTF-8')."'>";
    echo "<input type='hidden' name='csrf_token' value='".htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8')."'>";
    echo "<button class='button is-danger' type='submit' onclick='return confirm(\"本当にこのログを削除しますか？この操作は取り消せません。\");'>ログを削除</button>";
    echo "</form>";
    echo "</div>";
    
    // スレッド削除フォーム
    echo "<div class='card'>";
    echo "<h2>スレッド削除</h2>";
    echo "<form method='POST'>";
    echo "<label>削除するスレッドID:</label><br>";
    echo "<input class='input' type='number' name='thread_id' placeholder='例: 456' required><br>";
    echo "<p><strong>注意:</strong> スレッドを削除すると、そのスレッドに含まれる全てのログも削除されます。この操作は取り消せません。</p>";
    echo "<input type='hidden' name='m' value='admin'>";
    echo "<input type='hidden' name='action' value='delete_thread'>";
    echo "<input type='hidden' name='admin_pass' value='".htmlspecialchars(m_param('admin_pass', ''), ENT_QUOTES, 'UTF-8')."'>";
    echo "<input type='hidden' name='csrf_token' value='".htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8')."'>";
    echo "<button class='button is-danger' type='submit' onclick='return confirm(\"本当にこのスレッドと含まれる全てのログを削除しますか？この操作は取り消せません。\");'>スレッドを削除</button>";
    echo "</form>";
    echo "</div>";
    
    // 最近のログ一覧を表示
    echo "<div class='card'>";
    echo "<h2>最近のログ一覧</h2>";
    echo m_admin_show_recent_logs();
    echo "</div>";
    
    // スレッド一覧を表示
    echo "<div class='card'>";
    echo "<h2>スレッド一覧</h2>";
    echo m_admin_show_recent_threads();
    echo "</div>";
  }
  
  echo "<div style='margin-top:20px;'>";
  echo "<a href='index.php' class='button'>トップページに戻る</a>";
  echo "</div>";
  echo "</div>";
  echo "</body></html>";
}

/**
 * 最近のログ一覧を表示する関数
 */
function m_admin_show_recent_logs() {
  $query = "SELECT logid, threadid, title, name, ctime FROM logs ORDER BY ctime DESC LIMIT 20";
  $logs = m_db_query($query, []);
  
  if (empty($logs)) {
    return "<p>ログがありません。</p>";
  }
  
  $csrf_token = uniqid('admin_', true); // 管理画面用の簡単なトークン
  $admin_pass = htmlspecialchars(m_param('admin_pass', ''), ENT_QUOTES, 'UTF-8');
  
  $html = "<table class='table is-fullwidth'>\n";
  $html .= "<thead>\n";
  $html .= "<tr><th>ログID</th><th>スレッドID</th><th>タイトル</th><th>投稿者</th><th>投稿日時</th><th>操作</th></tr>\n";
  $html .= "</thead>\n";
  $html .= "<tbody>\n";
  
  foreach ($logs as $log) {
    $log_id = htmlspecialchars($log['logid'], ENT_QUOTES, 'UTF-8');
    $thread_id = htmlspecialchars($log['threadid'], ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($log['title'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($log['name'], ENT_QUOTES, 'UTF-8');
    $date = date('Y-m-d H:i:s', $log['ctime']);
    
    $html .= "<tr>\n";
    $html .= "<td>{$log_id}</td>\n";
    $html .= "<td><a href='".m_url('thread', "t={$thread_id}")."'>{$thread_id}</a></td>\n";
    $html .= "<td>{$title}</td>\n";
    $html .= "<td>{$name}</td>\n";
    $html .= "<td>{$date}</td>\n";
    $html .= "<td>\n";
    $html .= "  <a href='".m_url('log', "l={$log_id}")."' class='button is-small is-info'>表示</a>\n";
    $html .= "  <form method='POST' style='display:inline-block; margin-left:5px;'>\n";
    $html .= "    <input type='hidden' name='m' value='admin'>\n";
    $html .= "    <input type='hidden' name='action' value='delete'>\n";
    $html .= "    <input type='hidden' name='log_id' value='{$log_id}'>\n";
    $html .= "    <input type='hidden' name='admin_pass' value='{$admin_pass}'>\n";
    $html .= "    <input type='hidden' name='csrf_token' value='{$csrf_token}'>\n";
    $html .= "    <button type='submit' class='button is-small is-danger' onclick='return confirm(\"ログID:{$log_id} を本当に削除しますか？この操作は取り消せません。\");'>削除</button>\n";
    $html .= "  </form>\n";
    $html .= "</td>\n";
    $html .= "</tr>\n";
  }
  
  $html .= "</tbody>\n";
  $html .= "</table>\n";
  
  return $html;
}

/**
 * 最近のスレッド一覧を表示する関数
 */
function m_admin_show_recent_threads() {
  $query = "SELECT threadid, title, count, ctime, mtime FROM threads ORDER BY mtime DESC LIMIT 20";
  $threads = m_db_query($query, []);
  
  if (empty($threads)) {
    return "<p>スレッドがありません。</p>";
  }
  
  $csrf_token = uniqid('admin_', true); // 管理画面用の簡単なトークン
  $admin_pass = htmlspecialchars(m_param('admin_pass', ''), ENT_QUOTES, 'UTF-8');
  
  $html = "<table class='table is-fullwidth'>\n";
  $html .= "<thead>\n";
  $html .= "<tr><th>スレッドID</th><th>タイトル</th><th>ログ数</th><th>作成日時</th><th>更新日時</th><th>操作</th></tr>\n";
  $html .= "</thead>\n";
  $html .= "<tbody>\n";
  
  foreach ($threads as $thread) {
    $thread_id = htmlspecialchars($thread['threadid'], ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($thread['title'], ENT_QUOTES, 'UTF-8');
    $count = htmlspecialchars($thread['count'], ENT_QUOTES, 'UTF-8');
    $cdate = date('Y-m-d H:i:s', $thread['ctime']);
    $mdate = date('Y-m-d H:i:s', $thread['mtime']);
    
    $html .= "<tr>\n";
    $html .= "<td>{$thread_id}</td>\n";
    $html .= "<td><a href='".m_url('thread', "t={$thread_id}")."'>{$title}</a></td>\n";
    $html .= "<td>{$count}</td>\n";
    $html .= "<td>{$cdate}</td>\n";
    $html .= "<td>{$mdate}</td>\n";
    $html .= "<td>\n";
    $html .= "  <a href='".m_url('thread', "t={$thread_id}")."' class='button is-small is-info'>表示</a>\n";
    $html .= "  <form method='POST' style='display:inline-block; margin-left:5px;'>\n";
    $html .= "    <input type='hidden' name='m' value='admin'>\n";
    $html .= "    <input type='hidden' name='action' value='delete_thread'>\n";
    $html .= "    <input type='hidden' name='thread_id' value='{$thread_id}'>\n";
    $html .= "    <input type='hidden' name='admin_pass' value='{$admin_pass}'>\n";
    $html .= "    <input type='hidden' name='csrf_token' value='{$csrf_token}'>\n";
    $html .= "    <button type='submit' class='button is-small is-danger' onclick='return confirm(\"スレッドID:{$thread_id} とその全てのログ（{$count}件）を本当に削除しますか？この操作は取り消せません。\");'>削除</button>\n";
    $html .= "  </form>\n";
    $html .= "</td>\n";
    $html .= "</tr>\n";
  }
  
  $html .= "</tbody>\n";
  $html .= "</table>\n";
  
  return $html;
}
