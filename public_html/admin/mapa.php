<?php
// /admin/mapa.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$SITE_ROOT = defined('SITE_ROOT') ? SITE_ROOT : dirname(__DIR__);
$DATA_DIR  = $SITE_ROOT . '/data';
$NORM_JSON = defined('NORMATIVA_JSON') ? NORMATIVA_JSON : ($SITE_ROOT . '/normativa/normativa.json');

function exists_ok(string $path): bool {
  return file_exists($path) && is_readable($path);
}

function count_from_json(string $path, string $key): ?int {
  if (!is_file($path) || !is_readable($path)) return null;
  $txt = @file_get_contents($path);
  if ($txt === false) return null;
  $j = json_decode($txt, true);
  if (json_last_error() !== JSON_ERROR_NONE) return null;
  if (!isset($j[$key]) || !is_array($j[$key])) return null;
  return count($j[$key]);
}

$counts = [
  'directorio' => count_from_json($DATA_DIR . '/directorio.json', 'entries'),
  'notas'      => count_from_json($DATA_DIR . '/notas.json', 'notes'),
  'normativa'  => count_from_json($NORM_JSON, 'items'),
];

$publicPages = [
  ['label'=>'Inicio',        'url'=>'/index.html',         'file'=>$SITE_ROOT . '/index.html'],
  ['label'=>'Directorio',    'url'=>'/directorio.html',    'file'=>$SITE_ROOT . '/directorio.html'],
  ['label'=>'Historia',      'url'=>'/historia.html',      'file'=>$SITE_ROOT . '/historia.html'],
  ['label'=>'Colaboradores', 'url'=>'/colaboradores.html', 'file'=>$SITE_ROOT . '/colaboradores.html'],
  ['label'=>'Notas',         'url'=>'/notas.html',         'file'=>$SITE_ROOT . '/notas.html'],
];

$dataFiles = [
  ['label'=>'Directorio JSON', 'file'=>$DATA_DIR . '/directorio.json', 'hint'=>'/data/directorio.json', 'count'=>$counts['directorio']],
  ['label'=>'Notas JSON',      'file'=>$DATA_DIR . '/notas.json',      'hint'=>'/data/notas.json',      'count'=>$counts['notas']],
  ['label'=>'Normativa JSON',  'file'=>$NORM_JSON,                    'hint'=>'/normativa/normativa.json', 'count'=>$counts['normativa']],
];

$adminPages = [
  ['label'=>'Tablero',          'url'=>'/admin/index.php',            'file'=>__DIR__ . '/index.php'],
  ['label'=>'Estado del sistema','url'=>'/admin/estado.php',          'file'=>__DIR__ . '/estado.php'],
  ['label'=>'Mapa',             'url'=>'/admin/mapa.php',             'file'=>__DIR__ . '/mapa.php'],
  ['label'=>'Editor Directorio','url'=>'/admin/directorio_editor.php','file'=>__DIR__ . '/directorio_editor.php'],
  ['label'=>'Editor Normativa (filas)','url'=>'/admin/normativa_editor.php','file'=>__DIR__ . '/normativa_editor.php'],
  ['label'=>'Editor Normativa (JSON)','url'=>'/admin/editor.php',     'file'=>__DIR__ . '/editor.php'],
  ['label'=>'Login',            'url'=>'/admin/login.php',            'file'=>__DIR__ . '/login.php'],
  ['label'=>'Logout',           'url'=>'/admin/logout.php',           'file'=>__DIR__ . '/logout.php'],
];

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin · Mapa del sitio</title>
<style>
:root{
  --az1:#003366; --az2:#004080; --n:#ff6600;
  --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555;
  --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc;
  --ok:#0a7a2b; --bad:#b00020;
}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.head{max-width:1100px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
h1{margin:0;font-size:1.35em}
.sub{opacity:.92;margin:6px 0 0}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.wrap{max-width:1100px;margin:18px auto;padding:0 16px 60px;}
.grid{display:grid;grid-template-columns:repeat(2, minmax(0,1fr));gap:12px;}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid rgba(0,0,0,.10);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px 16px;}
.title{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px;}
.badge{display:inline-flex;align-items:center;font-weight:800;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;white-space:nowrap;}
.pill{display:inline-flex;align-items:center;font-weight:900;padding:4px 10px;border-radius:999px;border:1px solid rgba(0,0,0,.10);white-space:nowrap;}
.pill.ok{background:#f2fff6;color:var(--ok);border-color:rgba(0,128,64,.18);}
.pill.bad{background:#fff3f3;color:var(--bad);border-color:rgba(180,0,0,.18);}
.small{color:var(--mut);font-size:.92em;line-height:1.35;}
.item{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;padding:10px 0;border-top:1px solid rgba(0,0,0,.06);}
.item:first-child{border-top:0;}
.left{display:flex;flex-direction:column;gap:4px;}
.right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
a{color:var(--link);font-weight:bold;text-decoration:none;}
a:hover{text-decoration:underline;color:var(--az2);}
.chip{display:inline-flex;align-items:center;gap:8px;font-weight:800;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;white-space:nowrap;}
</style>
</head>
<body>
<header>
  <div class="head">
    <div>
      <h1>Mapa del sitio (Admin)</h1>
      <div class="sub">Vista ordenada de páginas + datos + herramientas</div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Tablero</a>
      <a href="/admin/estado.php">Estado</a>
      <a href="/admin/logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">

  <div class="grid">
    <div class="card">
      <div class="title">
        <div class="badge">🌐 Público</div>
        <div class="pill ok">Páginas</div>
      </div>

      <?php foreach ($publicPages as $p): $ok = exists_ok($p['file']); ?>
        <div class="item">
          <div class="left">
            <div><a href="<?= h($p['url']) ?>" target="_blank" rel="noopener"><?= h($p['label']) ?></a></div>
            <div class="small"><?= h($p['url']) ?></div>
          </div>
          <div class="right">
            <span class="pill <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FALTA' ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="title">
        <div class="badge">🗃️ Datos</div>
        <div class="pill <?= (exists_ok($DATA_DIR . '/directorio.json') && exists_ok($NORM_JSON)) ? 'ok' : 'bad' ?>">JSON</div>
      </div>

      <?php foreach ($dataFiles as $d): $ok = exists_ok($d['file']); ?>
        <div class="item">
          <div class="left">
            <div><strong><?= h($d['label']) ?></strong></div>
            <div class="small"><?= h($d['hint']) ?></div>
          </div>
          <div class="right">
            <?php if ($d['count'] !== null): ?>
              <span class="chip">📦 <?= (int)$d['count'] ?></span>
            <?php endif; ?>
            <span class="pill <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FALTA' ?></span>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="item">
        <div class="left">
          <div><strong>Ir a diagnóstico</strong></div>
          <div class="small">Pruebas de lectura / escritura / preview / seeds</div>
        </div>
        <div class="right">
          <a class="chip" href="/admin/estado.php">Abrir</a>
        </div>
      </div>
    </div>

    <div class="card" style="grid-column:1/-1;">
      <div class="title">
        <div class="badge">🛠️ Admin</div>
        <div class="pill ok">Herramientas</div>
      </div>

      <?php foreach ($adminPages as $a): $ok = exists_ok($a['file']); ?>
        <div class="item">
          <div class="left">
            <div><a href="<?= h($a['url']) ?>"><?= h($a['label']) ?></a></div>
            <div class="small"><?= h($a['url']) ?></div>
          </div>
          <div class="right">
            <span class="pill <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'FALTA' ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>

</div>
</body>
</html>