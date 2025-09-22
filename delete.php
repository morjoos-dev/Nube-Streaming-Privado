<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

requireLogin(false);

$csrf = (string)($_POST['csrf'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
  http_response_code(400);
  exit('CSRF inválido');
}
$user = (string)($_SESSION['user'] ?? '');
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
$f = (string)($_POST['f'] ?? '');
$f = trim($f, "/\\");

$base = ensureUserDir($user);
$path = safeJoin($base, $f);
if ($f === '' || !is_file($path)) {
  header('Location: dashboard.php?msg=' . rawurlencode('No se encontró el archivo') . '&err=1');
  exit;
}
if (@unlink($path)) {
  header('Location: dashboard.php?msg=' . rawurlencode('Archivo eliminado: '.$f));
  exit;
}
header('Location: dashboard.php?msg=' . rawurlencode('No se pudo eliminar el archivo') . '&err=1');
