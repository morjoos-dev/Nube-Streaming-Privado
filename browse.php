<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
header('Content-Type: application/json; charset=utf-8');
requireLogin(false);
$user = (string)($_SESSION['user'] ?? '');
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
$p = trim((string)($_GET['p'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$isVideo = (bool)preg_match('~^video(?:/|$)~i', $p);
$sub = $isVideo ? ltrim((string)preg_replace('~^video/?~i', '', $p), "/\\") : $p;
$base = $isVideo ? VIDEO_DIR : ensureUserDir($user);
$abs  = safeJoin($base, $sub);

if (!is_dir($abs)) { http_response_code(404); echo json_encode(['error'=>'NOT_FOUND']); exit; }

$dirs=[]; $files=[];
if ($dh = opendir($abs)) {
  while (($name = readdir($dh)) !== false) {
    if ($name==='.'||$name==='..') continue;
    $path = $abs . DIRECTORY_SEPARATOR . $name;
    $rel  = ($isVideo ? 'video/' : '') . ($sub ? $sub.'/' : '') . $name;
    if ($q !== '' && stripos($name, $q) === false) continue;
    if (is_dir($path)) $dirs[]=['name'=>$name,'rel'=>$rel,'type'=>'dir','mtime'=>@filemtime($path)?:0];
    else $files[]=['name'=>$name,'rel'=>$rel,'type'=>'file','size'=>@filesize($path)?:0,'mtime'=>@filemtime($path)?:0];
  }
  closedir($dh);
}
usort($dirs, fn($a,$b)=>strcasecmp($a['name'],$b['name']));
usort($files, fn($a,$b)=>strcasecmp($a['name'],$b['name']));
echo json_encode(['dirs'=>$dirs,'files'=>$files], JSON_UNESCAPED_UNICODE);