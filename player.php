<?php
require_once __DIR__.'/helpers.php';
requireLogin();

$support = '/\.(mp4|m4v|webm|mkv)$/i';

$v      = $_GET['v']      ?? '';
$folder = trim($_GET['folder'] ?? '');
$file   = trim($_GET['file']   ?? '');

if ($v !== '') {
  $rel = str_replace('\\','/', $v);
  if (str_starts_with($rel, 'files/')) $rel = substr($rel, 6);
  if (str_starts_with($rel, 'video/')) $rel = substr($rel, 6);
  $rel = ltrim($rel, '/');
  $folder = trim(dirname($rel), '/');
  $file   = basename($rel);
}

if ($folder === '') { http_response_code(400); exit('Falta carpeta'); }

$absFolder = safeJoin(VIDEO_DIR, $folder);
if (!is_dir($absFolder)) { http_response_code(404); exit('Carpeta no encontrada'); }

$videos = array_values(array_filter(scandir($absFolder), fn($n)=>preg_match($support, $n)));
natcasesort($videos);
$videos = array_values($videos);

if (!$videos) { http_response_code(404); exit('No hay vídeos en la carpeta'); }

if ($file === '' || !in_array($file, $videos, true)) {
  $file = $videos[0];
}

$webPath = 'video/'.rawurlencode($folder).'/'.rawurlencode($file);
$src     = 'download.php?f='.$webPath.'&inline=1';

$cover = is_file($absFolder.'/portada.jpg') ? 'files/video/'.rawurlencode($folder).'/portada.jpg' : '';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($folder) ?> – Reproductor</title>
<style>
  body{margin:0;background:#0b1020;color:#fff;font:16px system-ui}
  header{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#121a35;position:sticky;top:0;z-index:2}
  a.btn{background:#0e63e0;color:#fff;text-decoration:none;padding:8px 12px;border-radius:10px}
  main{display:grid;grid-template-columns:1fr 320px;gap:16px;max-width:1200px;margin:16px auto;padding:0 16px}
  video{width:100%;height:auto;background:#000;border-radius:12px}
  .panel{background:#121a35;border-radius:12px;padding:12px}
  .title{display:flex;align-items:center;gap:10px;margin-bottom:10px}
  .title img{width:48px;height:48px;object-fit:cover;border-radius:8px}
  ul{list-style:none;margin:0;padding:0;max-height:70vh;overflow:auto}
  li{border-bottom:1px solid #23325a}
  li a{display:flex;justify-content:space-between;gap:8px;padding:10px 8px;color:#cfe1ff;text-decoration:none}
  li a.active{background:#0e1a3d}
  .muted{color:#a8b3d1}
  @media (max-width:900px){ main{grid-template-columns:1fr} }
</style>
</head>
<body>
<header>
  <div>🎬 <?= htmlspecialchars($folder) ?></div>
  <nav>
    <a class="btn" href="multimedia.php">← Volver</a>
  </nav>
</header>

<main>
  <section>
    <div class="panel title">
      <?php if ($cover): ?><img src="<?= htmlspecialchars($cover) ?>" alt="portada"><?php endif; ?>
      <div>
        <div style="font-weight:700"><?= htmlspecialchars($file) ?></div>
        <div class="muted"><?= count($videos) ?> episodio(s)</div>
      </div>
    </div>
    <video controls preload="metadata" playsinline>     
      <source src="<?= htmlspecialchars($src) ?>" type="video/mp4">
      Tu navegador no soporta el vídeo HTML5.
    </video>
  </section>

  <aside class="panel">
    <div style="font-weight:700;margin-bottom:8px">Episodios</div>
    <ul>
      <?php foreach ($videos as $vname): 
        $link = 'player.php?folder='.rawurlencode($folder).'&file='.rawurlencode($vname);
        $active = ($vname === $file) ? 'active' : '';
      ?>
        <li><a class="<?= $active ?>" href="<?= $link ?>">
          <span><?= htmlspecialchars($vname) ?></span>
          <span>▶</span>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </aside>
</main>
</body>
</html>
