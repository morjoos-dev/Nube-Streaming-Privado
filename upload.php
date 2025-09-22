<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

requireLogin(false);

$csrf = (string)($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok'=>false, 'error'=>'CSRF inválido']); exit;
}

$user = (string)($_SESSION['user'] ?? '');
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
  echo json_encode(['ok'=>false, 'error'=>'Sin archivo']); exit;
}

$err = (int)($_FILES['file']['error'] ?? UPLOAD_ERR_OK);
if ($err !== UPLOAD_ERR_OK) {
  echo json_encode(['ok'=>false, 'error'=>'Error de subida: '.$err]); exit;
}

$srcTmp = (string)$_FILES['file']['tmp_name'];
$orig   = sanitizeFileName((string)$_FILES['file']['name']);

$dir = ensureUserDir($user);
$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
if (in_array($ext, ['php','phtml','phar'], true)) {
  $orig .= '.txt';
}

$destName = $orig;
$destPath = $dir.DIRECTORY_SEPARATOR.$destName;
$base = pathinfo($orig, PATHINFO_FILENAME);
$extDot = $ext ? ('.'.$ext) : '';
$idx = 1;
while (is_file($destPath)) {
  $destName = $base.' ('.$idx.')'.$extDot;
  $destPath = $dir.DIRECTORY_SEPARATOR.$destName;
  $idx++;
}

if (!move_uploaded_file($srcTmp, $destPath)) {
  echo json_encode(['ok'=>false, 'error'=>'No se pudo mover el archivo']); exit;
}

echo json_encode([
  'ok'   => true,
  'name' => $destName,
  'size' => @filesize($destPath) ?: 0
]);
