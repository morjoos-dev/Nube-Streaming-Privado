<?php
declare(strict_types=1);

// Solo desde localhost
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
  http_response_code(403); exit('Solo configurable desde este equipo (localhost).');
}

$cfgPath = __DIR__ . '/config.php';

$defaults = [
  'db_host' => '127.0.0.1',
  'db_port' => 3306,
  'db_user' => 'root',
  'db_pass' => '',
  'db_name' => 'mycloud',
  'allow_registration' => true,
  'base_url' => '',
  'timezone' => 'Europe/Madrid',
];

$cfg = $defaults;
if (is_file($cfgPath)) {
  include $cfgPath;
  $cfg['db_host'] = defined('DB_HOST') ? DB_HOST : $cfg['db_host'];
  $cfg['db_port'] = defined('DB_PORT') ? (int)DB_PORT : $cfg['db_port'];
  $cfg['db_user'] = defined('DB_USER') ? DB_USER : $cfg['db_user'];
  $cfg['db_pass'] = defined('DB_PASS') ? DB_PASS : $cfg['db_pass'];
  $cfg['db_name'] = defined('DB_NAME') ? DB_NAME : $cfg['db_name'];
  $cfg['allow_registration'] = defined('ALLOW_REGISTRATION') ? (bool)ALLOW_REGISTRATION : $cfg['allow_registration'];
  $cfg['base_url'] = defined('BASE_URL') ? BASE_URL : ($cfg['base_url'] ?? '');
  $cfg['timezone'] = date_default_timezone_get() ?: $cfg['timezone'];
}

$ok = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cfg['db_host'] = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
  $cfg['db_port'] = (int)($_POST['db_port'] ?? 3306);
  $cfg['db_user'] = trim((string)($_POST['db_user'] ?? 'root'));
  $cfg['db_pass'] = (string)($_POST['db_pass'] ?? '');
  $cfg['db_name'] = trim((string)($_POST['db_name'] ?? 'mycloud'));
  $cfg['allow_registration'] = isset($_POST['allow_registration']);
  $cfg['base_url'] = trim((string)($_POST['base_url'] ?? ''));
  $cfg['timezone'] = trim((string)($_POST['timezone'] ?? 'Europe/Madrid'));

  $q = fn(string $s) => str_replace("'", "\\'", $s);

  $php = "<?php\n";
  $php .= "declare(strict_types=1);\n";
  $php .= "session_start();\n";
  $php .= "if ('".$q($cfg['timezone'])."') { @date_default_timezone_set('".$q($cfg['timezone'])."'); }\n";
  $php .= "const DB_HOST = '".$q($cfg['db_host'])."';\n";
  $php .= "const DB_PORT = ".$cfg['db_port'].";\n";
  $php .= "const DB_USER = '".$q($cfg['db_user'])."';\n";
  $php .= "const DB_PASS = '".$q($cfg['db_pass'])."';\n";
  $php .= "const DB_NAME = '".$q($cfg['db_name'])."';\n";
  $php .= "const ALLOW_REGISTRATION = ".($cfg['allow_registration'] ? 'true' : 'false').";\n";
  $php .= "define('BASE_URL', '".$q($cfg['base_url'])."');\n";
  $php .= "define('FILES_DIR', __DIR__ . '/files');\n";
  $php .= "define('VIDEO_DIR',  FILES_DIR . '/video');\n";

  if (@file_put_contents($cfgPath, $php) === false) {
    $err = 'No se pudo escribir config.php. Ejecuta como administrador o revisa permisos.';
  } else {
    try {
      $pdo = new PDO(
        'mysql:host='.$cfg['db_host'].';port='.$cfg['db_port'].';charset=utf8mb4',
        $cfg['db_user'], $cfg['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
      );
      $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$cfg['db_name'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
      $pdo = new PDO(
        'mysql:host='.$cfg['db_host'].';port='.$cfg['db_port'].';dbname='.$cfg['db_name'].';charset=utf8mb4',
        $cfg['db_user'], $cfg['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
      );
      $pdo->exec('
        CREATE TABLE IF NOT EXISTS usuarios (
          id INT AUTO_INCREMENT PRIMARY KEY,
          username VARCHAR(50) NOT NULL UNIQUE,
          pass_hash VARCHAR(255) NOT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
      ');
      @mkdir(__DIR__.'/files', 0775, true);
      @mkdir(__DIR__.'/files/video', 0775, true);
      $ok = 'Configuración guardada y base de datos verificada. Ya puedes ir a index.php';
    } catch (Throwable $e) {
      $err = 'Error probando la base de datos: '.$e->getMessage();
    }
  }
}
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Asistente de configuración</title>
<style>
body{margin:0;background:#0b1020;color:#fff;font:16px system-ui}
.card{max-width:720px;margin:24px auto;padding:20px;background:#121a35;border-radius:16px}
label{display:block;margin:10px 0 4px}
input[type=text],input[type=password],input[type=number]{width:100%;padding:10px;border-radius:10px;border:1px solid #26335a;background:#0f1837;color:#fff}
.row{display:flex;gap:10px}
button{background:#0e63e0;color:#fff;border:0;border-radius:10px;padding:10px 14px;margin-top:12px;cursor:pointer}
.muted{color:#a8b3d1}.ok{color:#7cffb2}.err{color:#ff9aa2}
</style></head><body>
<div class="card">
  <h2>Asistente de configuración</h2>
  <?php if($ok):?><p class="ok"><?= htmlspecialchars($ok) ?></p><?php endif; ?>
  <?php if($err):?><p class="err"><?= htmlspecialchars($err) ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <h3>Base de datos</h3>
    <div class="row">
      <div style="flex:2">
        <label>Host</label><input name="db_host" value="<?= htmlspecialchars((string)$cfg['db_host']) ?>">
      </div>
      <div style="flex:1">
        <label>Puerto</label><input type="number" name="db_port" value="<?= (int)$cfg['db_port'] ?>">
      </div>
    </div>
    <div class="row">
      <div style="flex:1">
        <label>Usuario</label><input name="db_user" value="<?= htmlspecialchars((string)$cfg['db_user']) ?>">
      </div>
      <div style="flex:1">
        <label>Contraseña</label><input type="password" name="db_pass" value="<?= htmlspecialchars((string)$cfg['db_pass']) ?>">
      </div>
    </div>
    <label>Nombre de base de datos</label>
    <input name="db_name" value="<?= htmlspecialchars((string)$cfg['db_name']) ?>">

    <h3>App</h3>
    <label><input type="checkbox" name="allow_registration" <?= $cfg['allow_registration'] ? 'checked' : '' ?>> Permitir registro de usuarios</label>
    <label>Base URL (opcional)</label>
    <input name="base_url" placeholder="http://tu-host/myservidor" value="<?= htmlspecialchars((string)$cfg['base_url']) ?>">
    <label>Timezone</label>
    <input name="timezone" value="<?= htmlspecialchars((string)$cfg['timezone']) ?>">

    <button>Guardar configuración</button>
  </form>
  <p class="muted">* Tras configurar, visita <code>index.php</code>.</p>
</div>
</body></html>
