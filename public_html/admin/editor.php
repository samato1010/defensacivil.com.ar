<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function load_json_raw(string $path): string {
  if (!file_exists($path)) return "{\n  \"meta\": {\"updated\": \"\"},\n  \"items\": []\n}\n";
  $raw = file_get_contents($path);
  return ($raw === false || trim($raw) === '') ? "{\n  \"meta\": {\"updated\": \"\"},\n  \"items\": []\n}\n" : $raw;
}

function validate_normativa_structure(array $data): array {
  if (!isset($data['meta']) || !is_array($data['meta'])) $data['meta'] = [];
  if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];
  $data['meta']['updated'] = date('Y-m-d H:i:s');
  if (!isset($data['meta']['schema_version'])) $data['meta']['schema_version'] = 1;
  return $data;
}

function save_with_backup(string $path, string $content): bool {
  if (!is_dir(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0755, true);

  if (file_exists($path)) {
    $ts = date('Ymd_His');
    @copy($path, BACKUP_DIR . "/defensa_civil_{$ts}.json");
  }

  $tmp = $path . '.tmp';
  $ok = file_put_contents($tmp, $content, LOCK_EX);
  if ($ok === false) return false;

  return @rename($tmp, $path);
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf'] ?? '');
  if (!csrf_check($token)) {
    $err = 'CSRF inválido. Refrescá la página y reintentá.';
  } else {
    $raw = (string)($_POST['json'] ?? '');
    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
      $err = 'JSON inválido (no se pudo parsear).';
    } else {
      $decoded = validate_normativa_structure($decoded);
      $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

      if ($pretty === false) {
        $err = 'No se pudo re-serializar el JSON.';
      } else {
        if (!save_with_backup(NORMATIVA_JSON, $pretty . "\n")) {
          $err = 'No se pudo guardar. Verificá permisos de escritura del archivo.';
        } else {
          $msg = 'Guardado OK (se generó backup en /data/backups).';
        }
      }
    }
  }
}

$rawNow = load_json_raw(NORMATIVA_JSON);
$statsTotal = 0; $statsNoHref = 0;

$decodedNow = json_decode($rawNow, true);
if (is_array($decodedNow) && isset($decodedNow['items']) && is_array($decodedNow['items'])) {
  $statsTotal = count($decodedNow['items']);
  foreach ($decodedNow['items'] as $it) {
    $href = trim((string)($it['href'] ?? ''));
    if ($href === '') $statsNoHref++;
  }
}

$user = (string)($_SESSION['user'] ?? 'admin');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Admin - Editor JSON</title>
  <style>
    :root{--az1:#003366;--az2:#004080;--bg:#f8f9fa;--card:#fff;--b:rgba(0,0,0,.10);}
    body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#222;}
    header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
    .head{max-width:1100px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;}
    .nav a{color:#fff;text-decoration:none;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;}
    .nav a:hover{background:rgba(255,255,255,.20);}
    .wrap{max-width:1100px;margin:16px auto;padding:0 16px 30px;}
    .card{background:var(--card);border:1px solid var(--b);border-radius:14px;box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px;}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin:12px 0;}
    .kpi{background:#fff;border:1px solid var(--b);border-radius:999px;padding:10px 12px;}
    textarea{width:100%;min-height:520px;border-radius:12px;border:1px solid rgba(0,0,0,.18);padding:12px;font-family:ui-monospace, Menlo, Consolas, monospace;font-size:.95em;}
    .btn{border:0;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:bold;background:var(--az2);color:#fff;}
    .btn:hover{background:#0066cc;}
    .btn.alt{background:#e9eef6;color:var(--az1);border:1px solid rgba(0,0,0,.08);}
    .btn.alt:hover{background:#dde7f7;}
    .msg{margin:10px 0;padding:12px;border-radius:12px;border:1px solid rgba(0,0,0,.10);background:#fff;}
    .msg.ok{background:#eefaf0;color:#0a6b2b;border-color:rgba(10,107,43,.25);}
    .msg.err{background:#fff3f3;color:#7a0000;border-color:rgba(180,0,0,.25);}
    .hint{color:#555;margin:8px 0 0;}
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <div style="font-size:1.2em;font-weight:bold;">Admin · Editor JSON</div>
      <div style="opacity:.92;">Usuario: <strong><?= h($user) ?></strong> · Archivo: <strong><?= h(NORMATIVA_JSON) ?></strong></div>
    </div>
    <div class="nav">
      <a href="index.php">Tablero</a>
      <a href="ping.php">Ping</a>
      <a href="../index.html">Sitio</a>
      <a href="logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">
  <?php if ($msg): ?><div class="msg ok"><strong><?= h($msg) ?></strong></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><strong><?= h($err) ?></strong></div><?php endif; ?>

  <div class="row">
    <div class="kpi">Items: <strong><?= (int)$statsTotal ?></strong></div>
    <div class="kpi">Sin href: <strong><?= (int)$statsNoHref ?></strong></div>
    <button class="btn alt" type="button" id="validate">Validar</button>
    <button class="btn alt" type="button" id="prettify">Prettify</button>
  </div>

  <div class="card">
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <textarea name="json" id="json"><?= h($rawNow) ?></textarea>
      <div class="row" style="justify-content:flex-end;">
        <button class="btn" type="submit">Guardar</button>
      </div>
      <div class="hint">Tip: Guardar genera backup automático en <code>/data/backups/</code>.</div>
    </form>
  </div>
</div>

<script>
  const ta = document.getElementById('json');
  document.getElementById('validate').addEventListener('click', () => {
    try { JSON.parse(ta.value); alert('JSON OK'); }
    catch(e){ alert('JSON inválido: ' + e.message); }
  });
  document.getElementById('prettify').addEventListener('click', () => {
    try {
      const obj = JSON.parse(ta.value);
      ta.value = JSON.stringify(obj, null, 2);
      alert('Listo.');
    } catch(e) {
      alert('No puedo prettify: ' + e.message);
    }
  });
</script>

</body>
</html>