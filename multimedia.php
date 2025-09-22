<?php
require_once __DIR__.'/helpers.php';
requireLogin();
$items = listVideoFolders();
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Multimedia</title>
<style>
  body{margin:0;font:16px system-ui;background:#0b1020;color:#fff}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:#121a35}
  a.btn{background:#0e63e0;color:#fff;border-radius:10px;padding:8px 12px;text-decoration:none}
  main{max-width:1100px;margin:20px auto;padding:0 16px}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
  .tile{background:#121a35;border-radius:14px;overflow:hidden;text-decoration:none;color:#fff}
  .tile img{width:100%;height:240px;object-fit:cover;display:block}
  .tile .t{padding:10px}
  .muted{color:#a8b3d1}
</style></head><body>
<header>
  <div>🎬 Multimedia compartida</div>
  <nav>
    <a class="btn" href="dashboard.php">Volver</a>
  </nav>
</header>
<main>
  <?php if(!$items): ?>
    <p class="muted">Aún no hay contenido en <code>files/video/</code>.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach($items as $it): ?>
        <a class="tile" href="player.php?folder=<?= urlencode($it['folder']) ?>">
          <?php if($it['cover_exists']): ?>
            <img src="<?=htmlspecialchars($it['cover'])?>" alt="portada">
          <?php else: ?>
            <img src="data:image/svg+xml;charset=utf-8,<?=rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 400 240\'><rect width=\'100%\' height=\'100%\' fill=\'#26335a\'/><text x=\'50%\' y=\'50%\' fill=\'#a8b3d1\' font-size=\'22\' text-anchor=\'middle\' dominant-baseline=\'middle\'>'.htmlspecialchars($it['folder']).'</text></svg>')?>">
          <?php endif; ?>
          <div class="t"><?=htmlspecialchars($it['folder'])?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
</body></html>
