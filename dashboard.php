<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

requireLogin(false);
$user = (string)($_SESSION['user'] ?? '');
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['csrf'];
  }
}
$csrf = csrf_token();
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

$infoMsg   = isset($_GET['msg']) ? (string)$_GET['msg'] : '';
$infoIsErr = isset($_GET['err']) && $_GET['err'] === '1';

$files = listUserFiles($user);
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi nube</title>
<style>
  body{margin:0;font:16px system-ui;background:#0b1020;color:#fff}
  header{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:#121a35}
  a.btn,button.btn{background:#0e63e0;color:#fff;border:0;border-radius:10px;padding:8px 12px;text-decoration:none;cursor:pointer}
  main{max-width:980px;margin:20px auto;padding:0 16px}
  .card{background:#121a35;border-radius:14px;padding:12px}
  .muted{color:#a8b3d1}
  .alert{padding:10px;border-radius:10px;margin:10px 0}
  .ok{background:#0e3e20;color:#b9ffd6}
  .err{background:#3e0e17;color:#ffd6dd}
  .drop{margin-top:8px;border:2px dashed #2a3970;border-radius:12px;padding:16px;text-align:center;background:#0f1837}
  .drop.drag{background:#0e1a3d;border-color:#3f61ff}
  .hidden{display:none}
  .up-list{list-style:none;margin:10px 0 0;padding:0}
  .up-item{display:flex;align-items:center;gap:10px;margin:8px 0}
  .up-name{flex:1 1 auto;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .up-bar{flex:0 0 220px;background:#0b1020;border-radius:8px;overflow:hidden;height:10px}
  .up-bar>span{display:block;height:10px;width:0%;background:#3f61ff}
  .up-pct{width:48px;text-align:right}
  ul.files{list-style:none;padding-left:0}
  ul.files li{padding:6px 0;border-bottom:1px solid #24305a;display:flex;gap:8px;align-items:center;justify-content:space-between}
  .name{flex:1 1 auto;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .actions{flex:0 0 auto;display:flex;gap:8px;align-items:center}
  .actions form{margin:0}
</style></head>
<body>
<header>
  <div>👤 <?= htmlspecialchars($user) ?></div>
  <nav>
    <a class="btn" href="multimedia.php">🎬 Multimedia</a>
    <a class="btn" href="logout.php">Salir</a>
  </nav>
</header>
<main>
  <h2>Mis archivos</h2>

  <?php if($infoMsg): ?>
    <div class="alert <?= $infoIsErr ? 'err' : 'ok' ?>"><?= htmlspecialchars($infoMsg) ?></div>
  <?php endif; ?>

  <div class="card">
    <p class="muted">Arrastra archivos aquí o usa el botón. Se mostrarán el progreso y, al terminar, se añadirán a la lista.</p>
    <div id="drop" class="drop" tabindex="0">
      Suelta aquí tus archivos
      <br><br>
      <button id="pick" class="btn" type="button">Seleccionar archivos</button>
      <input id="file" type="file" class="hidden" multiple>
    </div>
    <ul id="uploads" class="up-list"></ul>
  </div>

  <div class="card" style="margin-top:10px">
    <?php if (!$files): ?>
      <p class="muted">Aún no tienes archivos.</p>
    <?php else: ?>
      <ul id="filelist" class="files">
        <?php foreach($files as $f): ?>
          <li>
            <span class="name"><?= htmlspecialchars($f['name']) ?></span>
            <span class="muted" style="width:120px;text-align:right">
              <?= number_format(($f['size'] ?? 0)/1024, 1) ?> KB
            </span>
            <span class="actions">
              <a class="btn" href="download.php?f=<?= urlencode($f['name']) ?>">Descargar</a>
              <button class="btn" type="button"
                onclick="openPreview('<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>','<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>')">
                Vista previa
              </button>
              <form method="post" action="delete.php" onsubmit="return confirm('¿Eliminar &quot;<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>&quot;?');">
                <input type="hidden" name="f" value="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
                <button class="btn" type="submit" style="background:#b10e2e">Eliminar</button>
              </form>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</main>
<div id="preview-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.72); z-index:9999;">
  <div style="max-width:1100px; width:92vw; max-height:90vh; margin:4vh auto; background:#0f1837; color:#fff; border-radius:14px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,.6)">
    <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 12px; background:#121a35; border-bottom:1px solid #24305a">
      <div id="preview-title" style="font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"></div>
      <div>
        <a id="preview-download" href="#" style="margin-right:8px; text-decoration:none; color:#fff; background:#0e63e0; padding:6px 10px; border-radius:8px">Descargar</a>
        <button onclick="closePreview()" style="background:#26335a; color:#fff; border:0; padding:6px 10px; border-radius:8px; cursor:pointer">Cerrar</button>
      </div>
    </div>
    <div id="preview-body" style="padding:10px; overflow:auto; max-height:calc(90vh - 52px); display:grid; place-items:center"></div>
  </div>
</div>
<script>
const CSRF = "<?= htmlspecialchars($csrf, ENT_QUOTES) ?>";

const PREVIEW = {
  extsImg: ['png','jpg','jpeg','gif','webp','bmp','svg'],
  extsVid: ['mp4','m4v','webm','mkv'],
  extsAud: ['mp3','ogg','wav','m4a','aac','flac'],
  extsPdf: ['pdf'],
  extsTxt: ['txt','md','csv','log','json','xml','yml','yaml','ini'],
  maxTextBytes: 2*1024*1024
};
function extOf(n=''){ const m=n.toLowerCase().match(/\.([a-z0-9]+)$/); return m?m[1]:''; }
function esc(s=''){ return s.replace(/[&<>"]/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function formatBytes(x){ if(!x) return '0 B'; const k=1024, u=['B','KB','MB','GB']; let i=Math.floor(Math.log(x)/Math.log(k)); i=Math.min(i,u.length-1); return (x/Math.pow(k,i)).toFixed(i?1:0)+' '+u[i]; }

async function openPreview(relPath, fileName){
  const url = 'download.php?f=' + encodeURIComponent(relPath) + '&inline=1';
  const e = extOf(fileName||relPath);
  const modal = document.getElementById('preview-modal');
  const body  = document.getElementById('preview-body');
  const title = document.getElementById('preview-title');
  const aDl   = document.getElementById('preview-download');
  title.textContent = fileName || relPath;
  aDl.href = 'download.php?f=' + encodeURIComponent(relPath);
  body.innerHTML=''; let node=null;
  if (PREVIEW.extsImg.includes(e)) {
    node=document.createElement('img'); node.src=url; node.style.maxWidth='100%'; node.style.maxHeight='80vh';
  } else if (PREVIEW.extsVid.includes(e)) {
    node=document.createElement('video'); node.controls=true; node.preload='metadata'; node.playsInline=true; node.src=url; node.style.width='100%';
  } else if (PREVIEW.extsAud.includes(e)) {
    node=document.createElement('audio'); node.controls=true; node.src=url; node.style.width='100%';
  } else if (PREVIEW.extsPdf.includes(e)) {
    node=document.createElement('iframe'); node.src=url+'#toolbar=1&navpanes=0&view=FitH'; node.style.width='100%'; node.style.height='80vh';
  } else if (PREVIEW.extsTxt.includes(e)) {
    try {
      const res=await fetch(url); let text=await res.text();
      if (text.length>PREVIEW.maxTextBytes) text=text.slice(0,PREVIEW.maxTextBytes)+'\n\n…[recortado]';
      node=document.createElement('pre'); node.style.whiteSpace='pre-wrap'; node.style.width='100%'; node.style.maxHeight='80vh';
      node.style.background='#0b1020'; node.style.padding='12px'; node.style.borderRadius='10px';
      node.innerHTML=esc(text);
    } catch { node=document.createElement('div'); node.innerHTML='No se puede previsualizar. Usa <b>Descargar</b>.'; }
  } else {
    node=document.createElement('iframe'); node.src=url; node.style.width='100%'; node.style.height='80vh';
  }
  body.appendChild(node);
  modal.style.display='block';
}
function closePreview(){ document.getElementById('preview-modal').style.display='none'; }

const dz = document.getElementById('drop');
const pick = document.getElementById('pick');
const finput = document.getElementById('file');
const ulist = document.getElementById('uploads');
const flist = document.getElementById('filelist');

['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('drag'); }));
['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('drag'); }));
dz.addEventListener('drop', e => {
  const files = e.dataTransfer?.files || [];
  if (files.length) queueUploads(files);
});
pick.addEventListener('click', ()=> finput.click());
finput.addEventListener('change', ()=> {
  if (finput.files && finput.files.length) queueUploads(finput.files);
  finput.value = '';
});

function queueUploads(fileList){
  const files = Array.from(fileList);
  (async ()=>{
    for (const f of files){
      await uploadOne(f);
    }
  })();
}

function uploadOne(file){
  return new Promise((resolve)=>{
    const li = document.createElement('li');
    li.className = 'up-item';
    li.innerHTML = `
      <span class="up-name" title="${esc(file.name)}">${esc(file.name)}</span>
      <span class="up-pct">0%</span>
      <span class="up-bar"><span></span></span>
    `;
    const pct = li.querySelector('.up-pct');
    const bar = li.querySelector('.up-bar > span');
    ulist.appendChild(li);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php');
    xhr.responseType = 'json';

    xhr.upload.onprogress = (e)=>{
      if (!e.lengthComputable) return;
      const p = Math.round((e.loaded / e.total) * 100);
      pct.textContent = p + '%';
      bar.style.width = p + '%';
    };

    xhr.onload = ()=>{
      const res = xhr.response || {};
      if (xhr.status === 200 && res.ok){
        pct.textContent = '100%';
        bar.style.width = '100%';
        addToList(res.name, res.size);
      } else {
        pct.textContent = 'ERR';
        li.title = (res.error || 'Error de subida');
        bar.style.background = '#b10e2e';
      }
      resolve();
    };
    xhr.onerror = ()=>{ pct.textContent = 'ERR'; bar.style.background = '#b10e2e'; resolve(); };

    const fd = new FormData();
    fd.append('csrf', CSRF);
    fd.append('file', file);
    xhr.send(fd);
  });
}

function addToList(name, size){
  if (!flist) return;
  const li = document.createElement('li');
  li.innerHTML = `
    <span class="name">${esc(name)}</span>
    <span class="muted" style="width:120px;text-align:right">${esc(formatBytes(size))}</span>
    <span class="actions">
      <a class="btn" href="download.php?f=${encodeURIComponent(name)}">Descargar</a>
      <button class="btn" type="button" onclick="openPreview('${esc(name)}','${esc(name)}')">Vista previa</button>
      <form method="post" action="delete.php" onsubmit="return confirm('¿Eliminar &quot;${esc(name)}&quot;?');">
        <input type="hidden" name="f" value="${esc(name)}">
        <input type="hidden" name="csrf" value="${CSRF}">
        <button class="btn" type="submit" style="background:#b10e2e">Eliminar</button>
      </form>
    </span>
  `;
  flist.appendChild(li);
}
</script>
</body></html>
