<?php
// 简易聊天室后端：SQLite 数据库（若无 SQLite 扩展则使用文件存储作为回退）
// 路径：web/chat/api.php

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');

function respond($arr) {
  echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// 尝试 SQLite3
$useSqlite = class_exists('SQLite3');

if ($useSqlite) {
  $dbPath = __DIR__ . DIRECTORY_SEPARATOR . 'chat.db';
  $db = new SQLite3($dbPath);
  $db->exec('CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    content TEXT,
    created_at INTEGER
  )');

  if ($action === 'send') {
    // 支持 JSON 或表单
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!is_array($data)) $data = $_POST;
    $username = isset($data['username']) ? trim($data['username']) : '';
    $content  = isset($data['content']) ? trim($data['content']) : '';
    if ($content === '') respond(['ok' => false, 'error' => 'EMPTY_CONTENT']);
    if (mb_strlen($username, 'UTF-8') > 32) $username = mb_substr($username, 0, 32, 'UTF-8');
    if (mb_strlen($content, 'UTF-8') > 500) $content = mb_substr($content, 0, 500, 'UTF-8');

    $stmt = $db->prepare('INSERT INTO messages (username, content, created_at) VALUES (:u, :c, :t)');
    $stmt->bindValue(':u', $username ?: '匿名', SQLITE3_TEXT);
    $stmt->bindValue(':c', $content, SQLITE3_TEXT);
    $stmt->bindValue(':t', time(), SQLITE3_INTEGER);
    $ok = $stmt->execute();
    respond(['ok' => (bool)$ok]);
  }

  if ($action === 'list') {
    $sinceId = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;
    if ($sinceId > 0) {
      $stmt = $db->prepare('SELECT id, username, content, created_at FROM messages WHERE id > :sid ORDER BY id ASC LIMIT 200');
      $stmt->bindValue(':sid', $sinceId, SQLITE3_INTEGER);
    } else {
      $stmt = $db->prepare('SELECT id, username, content, created_at FROM messages ORDER BY id DESC LIMIT 50');
    }
    $res = $stmt->execute();
    $rows = [];
    while ($res && ($row = $res->fetchArray(SQLITE3_ASSOC))) {
      $rows[] = $row;
    }
    if ($sinceId <= 0) {
      // 逆序取最近 50 条，展示时按时间正序
      $rows = array_reverse($rows);
    }
    respond(['ok' => true, 'messages' => $rows]);
  }

  // 未知动作
  respond(['ok' => false, 'error' => 'UNKNOWN_ACTION']);
}

// 文件存储回退：messages.json
$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'messages.json';
if (!file_exists($filePath)) {
  file_put_contents($filePath, json_encode(['last_id' => 0, 'messages' => []], JSON_UNESCAPED_UNICODE));
}

function filedb_read($path) {
  $fp = fopen($path, 'r');
  if (!$fp) return ['last_id' => 0, 'messages' => []];
  flock($fp, LOCK_SH);
  $data = stream_get_contents($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
  $json = json_decode($data, true);
  if (!is_array($json)) $json = ['last_id' => 0, 'messages' => []];
  return $json;
}

function filedb_write($path, $data) {
  $fp = fopen($path, 'c+');
  if (!$fp) return false;
  flock($fp, LOCK_EX);
  ftruncate($fp, 0);
  fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
  fflush($fp);
  flock($fp, LOCK_UN);
  fclose($fp);
  return true;
}

if ($action === 'send') {
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);
  if (!is_array($data)) $data = $_POST;
  $username = isset($data['username']) ? trim($data['username']) : '';
  $content  = isset($data['content']) ? trim($data['content']) : '';
  if ($content === '') respond(['ok' => false, 'error' => 'EMPTY_CONTENT']);
  if (mb_strlen($username, 'UTF-8') > 32) $username = mb_substr($username, 0, 32, 'UTF-8');
  if (mb_strlen($content, 'UTF-8') > 500) $content = mb_substr($content, 0, 500, 'UTF-8');

  $db = filedb_read($filePath);
  $db['last_id'] = intval($db['last_id']) + 1;
  $msg = [
    'id' => $db['last_id'],
    'username' => $username ?: '匿名',
    'content' => $content,
    'created_at' => time(),
  ];
  $db['messages'][] = $msg;
  // 仅保留最近 1000 条
  if (count($db['messages']) > 1000) {
    $db['messages'] = array_slice($db['messages'], -1000);
  }
  $ok = filedb_write($filePath, $db);
  respond(['ok' => (bool)$ok]);
}

if ($action === 'list') {
  $sinceId = isset($_GET['since_id']) ? intval($_GET['since_id']) : 0;
  $db = filedb_read($filePath);
  $messages = $db['messages'];
  if ($sinceId > 0) {
    $messages = array_values(array_filter($messages, function($m) use ($sinceId){ return intval($m['id']) > $sinceId; }));
  } else {
    $messages = array_slice($messages, -50); // 最近 50 条
  }
  respond(['ok' => true, 'messages' => $messages]);
}

respond(['ok' => false, 'error' => 'UNKNOWN_ACTION']);
?>