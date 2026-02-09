<?php
// /admin/estado.php
declare(strict_types=1);

// Forzamos debug acá mismo (por si config no lo muestra)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_login();

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function perm_str(string $path): string {
  if (!file_exists($path)) return '—';
  $p = @fileperms($path);
  if ($p === false) return '—';
  return substr(sprintf('%o', $p), -4);
}

function fmt_bytes(int $b): string {
  $u = ['B','KB','MB','GB','TB'];
  $i = 0;
  $x = (float)$b;
  while ($x >= 1024 && $i < count($u)-1) { $x /= 1024; $i++; }
  return rtrim(rtrim(number_format($x, 2, '.', ''), '0'), '.') . ' ' . $u[$i];
}

function json_count(string $path): array {
  $out = ['ok'=>false,'count'=>null,'err'=>'','schema'=>''];
  if (!is_file($path)) { $out['err'] = 'No existe.'; return $out; }
  if (!is_readable($path)) { $out['err'] = 'No legible (permisos).'; return $out; }

  $txt = @file_get_contents($path);
  if ($txt === false) { $out['err'] = 'No se pudo leer.'; return $out; }

  $j = json_decode($txt, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    $out['err'] = 'JSON inválido: ' . json_last_error_msg();
    return $out;
  }

  $out['ok'] = true;

  if (isset($j['entries']) && is_array($j['entries'])) {
    $out['schema'] = 'entries';
    $out['count'] = count($j['entries']);
  } elseif (isset($j['items']) && is_array($j['items'])) {
    $out['schema'] = 'items';
    $out['count'] = count($j['items']);
  } elseif (isset($j['notes']) && is_array($j['notes'])) {
    $out['schema'] = 'notes';
    $out['count'] = count($j['notes']);
  } else {
    $out['schema'] = 'desconocido';
    $out['count'] = null;
  }

  return $out;
}

$SITE_ROOT = defined('SITE_ROOT') ? SITE_ROOT : dirname(__DIR__);
$DATA_DIR  = $SITE_ROOT . '/data';
$NORM_JSON = defined('NORMATIVA_JSON') ? NORMATIVA_JSON : ($SITE_ROOT . '/normativa/normativa.json');
$BACKUPDIR = defined('BACKUP_DIR') ? BACKUP_DIR : (__DIR__ . '/_backup');

$targets = [
  ['label'=>'/data (dir)', 'path'=>$DATA_DIR, 'is_dir'=>true],
  ['label'=>'/admin/_backup (dir)', 'path'=>$BACKUPDIR, 'is_dir'=>true],
  ['label'=>'directorio.json', 'path'=>$DATA_DIR . '/directorio.json', 'is_dir'=>false, 'is_json'=>true],
  ['label'=>'notas.json', 'path'=>$DATA_DIR . '/notas.json', 'is_dir'=>false, 'is_json'=>true],
  ['label'=>'normativa.json', 'path'=>$NORM_JSON, 'is_dir'=>false, 'is_json'=>true],
];

$user = (string)($_SESSION['user'] ?? 'admin');

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin · Estado</title>
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
.card{background:var(--card);border:1px solid rgba(0,0,0,.10);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px 16px;margin:12px 0;}
.title{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.badge{display:inline-flex;align-items:center;font-weight:800;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;}
.pill{display:inline-flex;align-items:center;font-weight:900;padding:4px 10px;border-radius:999px;border:1px solid rgba(0,0,0,.10);}
.pill.ok{background:#f2fff6;color:var(--ok);border-color:rgba(0,128,64,.18);}
.pill.bad{background:#fff3f3;color:var(--bad);border-color:rgba(180,0,0,.18);}
.small{color:var(--mut);font-size:.92em;line-height:1.35;}
.kv{display:grid;grid-template-columns:170px 1fr;gap:8px;margin-top:10px;}
.k{color:var(--mut);font-weight:bold;}
hr{border:0;border-top:1px solid rgba(0,0,0,.08);margin:10px 0}
</style>
</head>
<body>
<header>
  <div class="head">
    <div>
      <h1>Estado del sistema</h1>
      <div class="sub">Usuario: <strong><?= h($user) ?></strong></div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Tablero</a>
      <a href="/admin/mapa.php">Mapa</a>
      <a href="/admin/logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">

  <div class="card">
    <div class="title">
      <div class="badge">🧩 Entorno</div>
      <div class="pill ok">OK</div>
    </div>
    <div class="kv">
      <div class="k">PHP</div><div><?= h(PHP_VERSION) ?></div>
      <div class="k">HTTPS</div><div><?= h((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'on' : 'off') ?></div>
      <div class="k">Host</div><div><?= h((string)($_SERVER['HTTP_HOST'] ?? '')) ?></div>
      <div class="k">URI</div><div><?= h((string)($_SERVER['REQUEST_URI'] ?? '')) ?></div>
      <div class="k">SITE_ROOT</div><div><?= h($SITE_ROOT) ?></div>
      <div class="k">Hora</div><div><?= h(date('Y-m-d H:i:s')) ?></div>
    </div>
  </div>

  <?php foreach ($targets as $t): ?>
    <?php
      $path = $t['path'];
      $exists = file_exists($path);
      $is_dir = !empty($t['is_dir']);
      $readable = $exists ? is_readable($path) : false;
      $writable = $exists ? is_writable($path) : (is_dir(dirname($path)) && is_writable(dirname($path)));
      $perm = perm_str($path);
      $size = (!$is_dir && is_file($path)) ? fmt_bytes((int)filesize($path)) : '—';
      $mtime = (!$is_dir && is_file($path)) ? date('Y-m-d H:i:s', (int)filemtime($path)) : '—';

      $json = null;
      if (!empty($t['is_json']) && is_file($path)) $json = json_count($path);

      $ok = $exists && ($is_dir || $readable) && (!$json || $json['ok']);
    ?>
    <div class="card">
      <div class="title">
        <div class="badge"><?= h($t['label']) ?></div>
        <div class="pill <?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? 'OK' : 'ERROR' ?></div>
      </div>

      <div class="small"><strong>Ruta:</strong> <?= h($path) ?></div>

      <div class="kv">
        <div class="k">Existe</div><div><?= $exists ? 'Sí' : 'No' ?></div>
        <div class="k">Lectura</div><div><?= $readable ? 'OK' : '—' ?></div>
        <div class="k">Escritura</div><div><?= $writable ? 'OK' : '—' ?></div>
        <div class="k">Permisos</div><div><?= h($perm) ?></div>
        <div class="k">Tamaño</div><div><?= h($size) ?></div>
        <div class="k">Modificado</div><div><?= h($mtime) ?></div>

        <?php if ($json): ?>
          <div class="k">JSON</div>
          <div>
            <?= $json['ok'] ? 'Válido' : ('❌ ' . h($json['err'])) ?>
            <?php if ($json['count'] !== null): ?>
              · <strong><?= (int)$json['count'] ?></strong> · esquema: <strong><?= h($json['schema']) ?></strong>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  <?php endforeach; ?>

</div>
</body>
</html>