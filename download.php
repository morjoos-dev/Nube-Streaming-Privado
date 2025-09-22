<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
$f = (string)($_GET['f'] ?? '');
$inline = isset($_GET['inline']);
requireLogin(false);
$user = (string)($_SESSION['user'] ?? '');

if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
$isVideo = (bool)preg_match('~^video(?:/|$)~i', $f);
$sub = $isVideo ? ltrim((string)preg_replace('~^video/?~i', '', $f), "/\\") : $f;
$base = $isVideo ? VIDEO_DIR : ensureUserDir($user);
$path = safeJoin($base, $sub);

if (!is_file($path)) { http_response_code(404); exit('NOT_FOUND'); }
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $path) ?: 'application/octet-stream';
finfo_close($finfo);

$size = filesize($path);
$start = 0; $end = $size - 1; $http_status = 200;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', (string)$_SERVER['HTTP_RANGE'], $m)) {
  if ($m[1] !== '') $start = (int)$m[1];
  if ($m[2] !== '') $end = (int)$m[2]; else $end = $size - 1;
  if ($start > $end || $start >= $size) { http_response_code(416); exit; }
  $http_status = 206;
}

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; media-src 'self' blob:; frame-ancestors 'self';");
header('Content-Type: '.$mime);
header('Accept-Ranges: bytes');
header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="'.basename($path).'"');
$length = $end - $start + 1;

if ($http_status===206) {
  header("HTTP/1.1 206 Partial Content");
  header("Content-Range: bytes $start-$end/$size");
}

header("Content-Length: $length");
$fp = fopen($path, 'rb'); fseek($fp, $start);
$buf = 8192;

while (!feof($fp) && $length > 0) {
  $chunk = ($length > $buf) ? $buf : $length;
  echo fread($fp, $chunk);
  $length -= $chunk;
  @ob_flush(); flush();
}
fclose($fp);