<?php
require_once __DIR__.'/helpers.php';
$err = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = trim($_POST['username']??''); $p = $_POST['password']??'';
  $act = $_POST['action'] ?? 'login';

  if ($act==='register') {
    if (!ALLOW_REGISTRATION) $err='Registro desactivado.';
    elseif ($u===''||$p==='') $err='Completa usuario y contraseña.';
    else {
      $db=db(); $q=$db->prepare('SELECT id FROM usuarios WHERE username=?'); $q->execute([$u]);
      if ($q->fetch()) $err='El usuario ya existe.';
      else {
        $db->prepare('INSERT INTO usuarios(username,pass_hash) VALUES(?,?)')
           ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
        ensureUserDir($u); $_SESSION['user']=$u; header('Location: dashboard.php'); exit;
      }
    }
  } else {
    $db=db(); $q=$db->prepare('SELECT pass_hash FROM usuarios WHERE username=?'); $q->execute([$u]);
    $row=$q->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($p, $row['pass_hash'])) { $_SESSION['user']=$u; header('Location: dashboard.php'); exit; }
    else $err='Credenciales incorrectas.';
  }
}
?>
<!doctype html><html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Nube familiar · Acceso</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"/>
<style>
  body{margin:0;background:#0b1020;color:#fff;font:16px system-ui;display:grid;place-items:center;height:100vh}
  .card{background:#121a35;border-radius:16px;padding:24px;width:min(420px,92vw);box-shadow:0 12px 24px rgba(0,0,0,.3)}
  label{display:block;margin:10px 0 6px}
  input{width:100%;padding:10px;border-radius:10px;border:1px solid #26335a;background:#0f1837;color:#fff}
  .row{display:flex;gap:10px;margin-top:12px}
  button{flex:1;background:#0e63e0;color:#fff;border:0;border-radius:10px;padding:10px;cursor:pointer;font-weight:700}
  .ghost{background:transparent;border:1px solid #0e63e0}
  .err{color:#ff9aa2;margin:8px 0}
  .muted{color:#a8b3d1;font-size:14px}
</style></head><body>
<div class="card">
  <h2 style="margin:0 0 12px">Nube familiar</h2>
  <?php if($err):?><div class="err"><?=$err?></div><?php endif;?>
  <form method="post">
    <input type="hidden" name="action" value="login">
    <label>Usuario</label><input name="username" required>
    <label>Contraseña</label><input type="password" name="password" required>
    <div class="row">
      <button>Entrar</button>
      <?php if (ALLOW_REGISTRATION): ?>
      <button class="ghost" type="button" onclick="reg.style.display='block'">Registrarse</button>
      <?php endif; ?>
    </div>
  </form>

  <?php if (ALLOW_REGISTRATION): ?>
  <form id="reg" method="post" style="display:none;margin-top:14px;border-top:1px solid #26335a;padding-top:14px">
    <input type="hidden" name="action" value="register">
    <label>Nuevo usuario</label><input name="username">
    <label>Nueva contraseña</label><input type="password" name="password">
    <div class="row"><button>Crear cuenta</button><button class="ghost" type="button" onclick="reg.style.display='none'">Cancelar</button></div>
  </form>
  <?php endif; ?>
</div>
<script></script>
</body></html>
