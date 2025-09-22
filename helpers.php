<?php
require_once __DIR__ . '/config.php';

function db() {
  static $pdo;
  if ($pdo) return $pdo;

  $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

  try {
    $pdo = new PDO(
      'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
      DB_USER, DB_PASS, $opts
    );
  } catch (PDOException $e) {
    if (stripos($e->getMessage(), 'Unknown database') !== false) {
      $pdoTmp = new PDO(
        'mysql:host='.DB_HOST.';port='.DB_PORT.';charset=utf8mb4',
        DB_USER, DB_PASS, $opts
      );
      $pdoTmp->exec('CREATE DATABASE IF NOT EXISTS `'.DB_NAME.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
      $pdoTmp = null;

      $pdo = new PDO(
        'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS, $opts
      );
      $pdo->exec('
        CREATE TABLE IF NOT EXISTS usuarios (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(50) NOT NULL UNIQUE,
          pass_hash VARCHAR(255) NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      ');
    } else {
      throw $e;
    }
  }
  return $pdo;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf'];
}

function requireLogin(bool $releaseSession = true): void {
  if (empty($_SESSION['user'])) {
    header('Location: index.php'); exit;
  }
  if ($releaseSession && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
}

function ensureUserDir(string $u): string {
  $d = FILES_DIR . DIRECTORY_SEPARATOR . $u;
  if (!is_dir($d)) @mkdir($d, 0775, true);
  return $d;
}
function sanitizeFileName(string $n): string {
  return preg_replace('/[^a-zA-Z0-9._-]/', '_', $n);
}
function safeJoin(string $base, string $rel): string {
  $rel = ltrim($rel, "/\\");
  $path = $base . DIRECTORY_SEPARATOR . $rel;
  $realBase = realpath($base);
  $real = realpath($path);
  if ($real && strpos($real, $realBase) === 0) return $real;
  $norm = [];
  foreach (explode('/', str_replace('\\','/',$rel)) as $seg) {
    if ($seg===''||$seg==='.') continue;
    if ($seg==='..') { array_pop($norm); continue; }
    $norm[] = $seg;
  }
  return $base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $norm);
}
function listUserFiles(string $username): array {
  $dir = ensureUserDir($username);
  $out = [];
  if (is_dir($dir) && ($dh = opendir($dir))) {
    while (($n = readdir($dh)) !== false) {
      if ($n==='.'||$n==='..') continue;
      $p = $dir.DIRECTORY_SEPARATOR.$n;
      if (is_file($p)) $out[] = ['name'=>$n, 'size'=>@filesize($p)?:0, 'mtime'=>@filemtime($p)?:0];
    }
    closedir($dh);
  }
  usort($out, fn($a,$b)=>strcasecmp($a['name'],$b['name']));
  return $out;
}
function listVideoFolders(): array {
  if (!is_dir(VIDEO_DIR)) return [];
  $out = [];
  foreach (scandir(VIDEO_DIR) as $f) {
    if ($f==='.'||$f==='..') continue;
    $p = VIDEO_DIR . DIRECTORY_SEPARATOR . $f;
    if (!is_dir($p)) continue;

    $coverPath = $p . DIRECTORY_SEPARATOR . 'portada.jpg';
    $videos = array_values(array_filter(scandir($p), fn($n)=>
      preg_match('/\.(mp4|m4v|webm|mkv)$/i', $n)
    ));
    if (!$videos) continue;

    $out[] = [
      'folder'        => $f,
      'cover_exists'  => is_file($coverPath),
      'cover'         => 'files/video/'.rawurlencode($f).'/portada.jpg',
      'video'         => 'files/video/'.rawurlencode($f).'/'.rawurlencode($videos[0]),
    ];
  }
  return $out;
}
