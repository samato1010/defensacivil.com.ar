<?php
// /admin/colaboradores_editor.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

/* =========================
   Paths (TU estructura real)
   ========================= */
if (!defined('SITE_ROOT')) define('SITE_ROOT', dirname(__DIR__)); // /public_html

$DATA_DIR      = SITE_ROOT . '/data';
$COLABS_JSON   = $DATA_DIR . '/colaboradores.json';
$BACKUPS_DIR   = $DATA_DIR . '/backups';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}
ensure_dir($DATA_DIR);
ensure_dir($BACKUPS_DIR);

/* =========================
   Helpers
   ========================= */
function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function strip_bom(string $s): string {
  if ($s !== '' && substr($s, 0, 3) === "\xEF\xBB\xBF") return substr($s, 3);
  return $s;
}

/** Fix típico mojibake (visualizaciÃ³n -> visualización) */
function fix_mojibake(string $s): string {
  if ($s === '') return $s;
  if (strpos($s, "Ã") === false && strpos($s, "Â") === false) return $s;
  $fixed = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
  return is_string($fixed) && $fixed !== '' ? $fixed : $s;
}

function now_iso(): string { return date('Y-m-d H:i:s'); }

function read_json_file(string $path, array $seed): array {
  if (!file_exists($path)) {
    @file_put_contents($path, json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    return $seed;
  }
  $raw = (string)@file_get_contents($path);
  $raw = strip_bom($raw);
  $data = json_decode($raw, true);
  if (!is_array($data)) return $seed;
  return $data;
}

function backup_file(string $path, string $backupsDir): void {
  if (!file_exists($path)) return;
  $ts = date('Ymd_His');
  $base = basename($path);
  $dest = rtrim($backupsDir, '/\\') . '/' . $base . '.' . $ts . '.bak.json';
  @copy($path, $dest);
}

function write_json_atomic(string $path, array $data): bool {
  $tmp = $path . '.tmp';
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;
  $ok = (@file_put_contents($tmp, $json, LOCK_EX) !== false);
  if (!$ok) return false;
  return @rename($tmp, $path);
}

function gen_id(): string {
  return 'c-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
}

function gen_username_from_email(string $email): string {
  $email = strtolower(trim($email));
  $u = explode('@', $email)[0] ?? 'colab';
  $u = preg_replace('/[^a-z0-9._-]+/i', '', $u) ?: 'colab';
  return substr($u, 0, 24);
}

function gen_password(int $len = 12): string {
  $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
  $out = '';
  for ($i=0; $i<$len; $i++) $out .= $alphabet[random_int(0, strlen($alphabet)-1)];
  return $out;
}

function int01($v): int {
  return ($v === '1' || $v === 1 || $v === true || $v === 'on') ? 1 : 0;
}

function find_idx_by_id(array $items, string $id): int {
  foreach ($items as $i => $it) {
    if (is_array($it) && (string)($it['id'] ?? '') === $id) return (int)$i;
  }
  return -1;
}

/* =========================
   Flash
   ========================= */
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = null;

function flash_set(string $msg, string $type = 'ok'): void {
  $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}
function flash_get(): ?array {
  $f = $_SESSION['flash'] ?? null;
  $_SESSION['flash'] = null;
  return is_array($f) ? $f : null;
}

function badge_class(string $estado): string {
  $e = strtolower(trim($estado));
  if ($e === 'aprobado') return 'ok';
  if ($e === 'rechazado') return 'bad';
  return 'warn';
}

/* =========================
   Seed / Load
   ========================= */
$seed = [
  'meta' => [
    'titulo' => 'Defensa Civil Argentina',
    'subtitulo' => 'Red de colaboradores',
    'updated' => now_iso(),
  ],
  'collaborators' => [],
  'items' => [],
];

$root = read_json_file($COLABS_JSON, $seed);

// Asegurar keys sin romper el archivo
if (!isset($root['meta']) || !is_array($root['meta'])) $root['meta'] = $seed['meta'];
if (!isset($root['collaborators']) || !is_array($root['collaborators'])) $root['collaborators'] = [];
if (!isset($root['items']) || !is_array($root['items'])) $root['items'] = [];

$items = $root['items'];
$collaborators_public = $root['collaborators'];

/* =========================
   POST Actions (sobre items)
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf'] ?? null;
  if (!csrf_check(is_string($token) ? $token : null)) {
    flash_set('CSRF inválido. Recargá la página e intentá de nuevo.', 'bad');
    header('Location: colaboradores_editor.php');
    exit;
  }

  // Recargar siempre fresco
  $root = read_json_file($COLABS_JSON, $seed);
  if (!isset($root['items']) || !is_array($root['items'])) $root['items'] = [];
  if (!isset($root['collaborators']) || !is_array($root['collaborators'])) $root['collaborators'] = [];
  if (!isset($root['meta']) || !is_array($root['meta'])) $root['meta'] = $seed['meta'];

  $items = $root['items'];

  $action = (string)($_POST['action'] ?? '');
  $id     = (string)($_POST['id'] ?? '');
  $idx    = $id !== '' ? find_idx_by_id($items, $id) : -1;

  $adminUser = (string)($_SESSION['user'] ?? 'admin');

  if ($action === 'update' && $idx >= 0) {
    $it = is_array($items[$idx]) ? $items[$idx] : [];

    $it['nombre']    = fix_mojibake(trim((string)($_POST['nombre'] ?? ($it['nombre'] ?? ''))));
    $it['email']     = fix_mojibake(trim((string)($_POST['email'] ?? ($it['email'] ?? ''))));
    $it['telefono']  = fix_mojibake(trim((string)($_POST['telefono'] ?? ($it['telefono'] ?? ''))));
    $it['provincia'] = fix_mojibake(trim((string)($_POST['provincia'] ?? ($it['provincia'] ?? ''))));
    $it['localidad'] = fix_mojibake(trim((string)($_POST['localidad'] ?? ($it['localidad'] ?? ''))));

    $it['usuario']   = trim((string)($_POST['usuario'] ?? ($it['usuario'] ?? '')));
    $it['role']      = trim((string)($_POST['role'] ?? ($it['role'] ?? 'colaborador')));

    $it['status']    = trim((string)($_POST['status'] ?? ($it['status'] ?? 'pendiente')));

    $per = $it['permisos'] ?? [];
    if (!is_array($per)) $per = [];
    $per['directorio'] = int01($_POST['perm_directorio'] ?? 0);
    $per['historia']   = int01($_POST['perm_historia'] ?? 0);
    $per['notas']      = int01($_POST['perm_notas'] ?? 0);
    $it['permisos'] = $per;

    // Cambiar clave manual (opcional)
    $newPass = trim((string)($_POST['newpass'] ?? ''));
    if ($newPass !== '') {
      $it['pass_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 10]);
      $it['must_change_password'] = 1;
    }

    $it['updated_at'] = now_iso();
    $items[$idx] = $it;

    $root['items'] = $items;
    $root['meta']['updated'] = now_iso();

    backup_file($COLABS_JSON, $BACKUPS_DIR);
    if (!write_json_atomic($COLABS_JSON, $root)) {
      flash_set('No se pudo guardar el JSON (permiso/IO).', 'bad');
    } else {
      flash_set('Guardado ✅', 'ok');
    }

    header('Location: colaboradores_editor.php');
    exit;
  }

  if ($action === 'approve' && $idx >= 0) {
    $it = is_array($items[$idx]) ? $items[$idx] : [];
    $email = trim((string)($it['email'] ?? ''));

    if ($email === '') {
      flash_set('No se puede aprobar: falta email.', 'bad');
      header('Location: colaboradores_editor.php');
      exit;
    }

    if (empty($it['usuario'])) {
      $it['usuario'] = gen_username_from_email($email);
    }

    // Permisos por defecto al aprobar
    $per = $it['permisos'] ?? [];
    if (!is_array($per)) $per = [];
    if (!isset($per['directorio'])) $per['directorio'] = 1;
    if (!isset($per['historia']))   $per['historia']   = 1;
    if (!isset($per['notas']))      $per['notas']      = 1;
    $it['permisos'] = $per;

    $passPlain = null;
    if (empty($it['pass_hash'])) {
      $passPlain = gen_password(12);
      $it['pass_hash'] = password_hash($passPlain, PASSWORD_BCRYPT, ['cost' => 10]);
      $it['must_change_password'] = 1;
    }

    $it['status'] = 'aprobado';
    $it['approved_at'] = now_iso();
    $it['approved_by'] = $adminUser;
    $it['updated_at']  = now_iso();

    $items[$idx] = $it;
    $root['items'] = $items;
    $root['meta']['updated'] = now_iso();

    backup_file($COLABS_JSON, $BACKUPS_DIR);
    if (!write_json_atomic($COLABS_JSON, $root)) {
      flash_set('No se pudo guardar el JSON (permiso/IO).', 'bad');
      header('Location: colaboradores_editor.php');
      exit;
    }

    if ($passPlain !== null) {
      flash_set("Aprobado ✅ Usuario: <b>" . e($it['usuario']) . "</b> — Clave temporal: <b>" . e($passPlain) . "</b> (se muestra una sola vez)", 'ok');
    } else {
      flash_set("Aprobado ✅ Usuario: <b>" . e($it['usuario']) . "</b> (clave ya existente)", 'ok');
    }

    header('Location: colaboradores_editor.php');
    exit;
  }

  if ($action === 'reject' && $idx >= 0) {
    $it = is_array($items[$idx]) ? $items[$idx] : [];
    $it['status'] = 'rechazado';
    $it['updated_at'] = now_iso();
    $items[$idx] = $it;

    $root['items'] = $items;
    $root['meta']['updated'] = now_iso();

    backup_file($COLABS_JSON, $BACKUPS_DIR);
    if (!write_json_atomic($COLABS_JSON, $root)) {
      flash_set('No se pudo guardar el JSON (permiso/IO).', 'bad');
    } else {
      flash_set('Marcado como rechazado.', 'ok');
    }

    header('Location: colaboradores_editor.php');
    exit;
  }

  if ($action === 'reset_password' && $idx >= 0) {
    $it = is_array($items[$idx]) ? $items[$idx] : [];
    $email = trim((string)($it['email'] ?? ''));

    if ($email === '') {
      flash_set('No se puede resetear clave: falta email.', 'bad');
      header('Location: colaboradores_editor.php');
      exit;
    }
    if (empty($it['usuario'])) $it['usuario'] = gen_username_from_email($email);

    $passPlain = gen_password(12);
    $it['pass_hash'] = password_hash($passPlain, PASSWORD_BCRYPT, ['cost' => 10]);
    $it['must_change_password'] = 1;
    $it['updated_at'] = now_iso();

    $items[$idx] = $it;
    $root['items'] = $items;
    $root['meta']['updated'] = now_iso();

    backup_file($COLABS_JSON, $BACKUPS_DIR);
    if (!write_json_atomic($COLABS_JSON, $root)) {
      flash_set('No se pudo guardar el JSON (permiso/IO).', 'bad');
      header('Location: colaboradores_editor.php');
      exit;
    }

    flash_set("Clave reseteada ✅ Usuario: <b>" . e($it['usuario']) . "</b> — Nueva clave temporal: <b>" . e($passPlain) . "</b> (se muestra una sola vez)", 'ok');
    header('Location: colaboradores_editor.php');
    exit;
  }

  if ($action === 'delete' && $idx >= 0) {
    array_splice($items, $idx, 1);

    $root['items'] = $items;
    $root['meta']['updated'] = now_iso();

    backup_file($COLABS_JSON, $BACKUPS_DIR);
    if (!write_json_atomic($COLABS_JSON, $root)) {
      flash_set('No se pudo guardar el JSON (permiso/IO).', 'bad');
    } else {
      flash_set('Colaborador eliminado.', 'ok');
    }

    header('Location: colaboradores_editor.php');
    exit;
  }

  flash_set('Acción inválida.', 'bad');
  header('Location: colaboradores_editor.php');
  exit;
}

/* =========================
   Render
   ========================= */
$flash = flash_get();

// Reload final
$root = read_json_file($COLABS_JSON, $seed);
if (!isset($root['items']) || !is_array($root['items'])) $root['items'] = [];
if (!isset($root['collaborators']) || !is_array($root['collaborators'])) $root['collaborators'] = [];
if (!isset($root['meta']) || !is_array($root['meta'])) $root['meta'] = $seed['meta'];

$items = $root['items'];
$collaborators_public = $root['collaborators'];

// Normalización suave para mostrar
foreach ($items as &$it) {
  if (!is_array($it)) $it = [];
  $it['id']        = (string)($it['id'] ?? gen_id());
  $it['status']    = (string)($it['status'] ?? 'pendiente');
  $it['nombre']    = fix_mojibake((string)($it['nombre'] ?? ''));
  $it['email']     = fix_mojibake((string)($it['email'] ?? ''));
  $it['telefono']  = fix_mojibake((string)($it['telefono'] ?? ''));
  $it['provincia'] = fix_mojibake((string)($it['provincia'] ?? ''));
  $it['localidad'] = fix_mojibake((string)($it['localidad'] ?? ''));
  $it['usuario']   = (string)($it['usuario'] ?? '');
  $it['role']      = (string)($it['role'] ?? 'colaborador');

  if (!isset($it['permisos']) || !is_array($it['permisos'])) $it['permisos'] = [];
  $it['permisos']['directorio'] = isset($it['permisos']['directorio']) ? (int)$it['permisos']['directorio'] : 0;
  $it['permisos']['historia']   = isset($it['permisos']['historia'])   ? (int)$it['permisos']['historia']   : 0;
  $it['permisos']['notas']      = isset($it['permisos']['notas'])      ? (int)$it['permisos']['notas']      : 0;
}
unset($it);

$csrf = csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin · Colaboradores</title>
<style>
:root{
  --az1:#003366; --az2:#004080; --n:#ff6600;
  --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555;
  --b:rgba(0,0,0,.10); --r:14px;
}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);line-height:1.7}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.head{max-width:1200px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
h1{margin:0;font-size:1.35em}
.sub{opacity:.92;margin:6px 0 0}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.wrap{max-width:1200px;margin:18px auto;padding:0 16px 60px;}

.card{
  background:var(--card);
  border:1px solid rgba(0,0,0,.08);
  border-radius:var(--r);
  box-shadow:0 6px 16px rgba(0,0,0,.06);
  padding:16px;
  margin:12px 0;
}
.title{
  margin:0 0 12px;
  color:var(--az1);
  border-left:4px solid var(--n);
  padding-left:12px;
  font-size:1.15em;
}
.small{color:var(--mut);font-size:.95em}

.flash{padding:12px 14px;border-radius:12px;margin:12px 0;border:1px solid rgba(0,0,0,.12);background:#fff;}
.flash.ok{border-color:rgba(0,128,64,.25);background:#f2fff6;color:#0a5a2b}
.flash.bad{border-color:rgba(180,0,0,.22);background:#fff3f3;color:#7a0000}
.flash.warn{border-color:rgba(255,102,0,.25);background:#fff6ee;color:#7a2d00}

.table-wrap{
  overflow:auto;border:1px solid rgba(0,0,0,0.08);
  border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.06);
  background:#fff;
}
table{width:100%;border-collapse:collapse;min-width:1200px;}
thead th{
  position:sticky;top:0;z-index:2;
  background:linear-gradient(to bottom,#eaf1ff,#dbe8ff);
  color:var(--az1);text-align:left;padding:12px;
  border-bottom:2px solid rgba(0,0,0,0.08);
  font-size:.95em;
}
tbody td{padding:12px;border-bottom:1px solid rgba(0,0,0,0.06);vertical-align:top;font-size:.95em;}
tbody tr:hover{background:#f6f9ff;}

input[type=text], input[type=email], input[type=password], select{
  width:100%; border:1px solid rgba(0,0,0,.14); border-radius:10px;
  padding:9px 10px; font-size:0.95em; outline:none; background:#fff;
}
.btn{
  border:0;border-radius:999px;padding:9px 12px;cursor:pointer;font-weight:900;
  background:var(--az2);color:#fff;transition:.15s;text-decoration:none;
  display:inline-flex;align-items:center;gap:8px;white-space:nowrap;
}
.btn:hover{background:#0066cc;transform:translateY(-1px);}
.btn.alt{background:#e9eef6;color:var(--az1);border:1px solid rgba(0,0,0,.10);}
.btn.bad{background:#b00020;}
.btn.ok{background:#0a7a3a;}
.btn.sm{padding:7px 10px;font-size:.9em;}

.badge{
  display:inline-flex;align-items:center;justify-content:center;
  padding:4px 10px;border-radius:999px;font-weight:900;font-size:.85em;
  border:1px solid rgba(0,0,0,.10);background:#f4f7ff;color:#244;
  white-space:nowrap;
}
.badge.ok{background:#f2fff6;color:#0a5a2b;border-color:rgba(0,128,64,.18)}
.badge.bad{background:#fff3f3;color:#7a0000;border-color:rgba(180,0,0,.18)}
.badge.warn{background:#fff6ee;color:#7a2d00;border-color:rgba(255,102,0,.25)}

.checks{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.checks label{display:inline-flex;gap:8px;align-items:center;font-weight:900;color:var(--az1);}
.checks input{transform:scale(1.1);}

.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}

footer{background:var(--az1);color:#fff;text-align:center;padding:30px 16px;margin-top:40px;}
</style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>Panel · Colaboradores (Login)</h1>
      <div class="sub">Administra cuentas reales desde /data/colaboradores.json → <b>items[]</b></div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Admin</a>
      <a href="/colaboradores.html">Página pública</a>
      <a href="/index.html">Inicio</a>
    </div>
  </div>
</header>

<div class="wrap">

  <?php if ($flash): ?>
    <div class="flash <?= e($flash['type'] ?? 'ok') ?>"><?= $flash['msg'] ?? '' ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="title">Estado general</div>
    <div class="small">
      <b>Archivo:</b> <?= e($COLABS_JSON) ?><br>
      <b>Backups:</b> <?= e($BACKUPS_DIR) ?><br>
      <b>Actualizado:</b> <?= e((string)($root['meta']['updated'] ?? '—')) ?><br>
      <b>Total cuentas (items):</b> <?= count($items) ?><br>
      <b>Total fichas públicas (collaborators):</b> <?= count($collaborators_public) ?>
    </div>
  </div>

  <div class="card">
    <div class="title">Cuentas con acceso (items)</div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="min-width:140px;">Status</th>
            <th style="min-width:210px;">Nombre</th>
            <th style="min-width:250px;">Email</th>
            <th style="min-width:170px;">Teléfono</th>
            <th style="min-width:140px;">Provincia</th>
            <th style="min-width:140px;">Localidad</th>
            <th style="min-width:160px;">Usuario</th>
            <th style="min-width:160px;">Role</th>
            <th style="min-width:250px;">Permisos</th>
            <th style="min-width:220px;">Nueva clave</th>
            <th style="min-width:520px;">Acciones</th>
          </tr>
        </thead>
        <tbody>

        <?php foreach ($items as $it):
          $id = (string)($it['id'] ?? '');
          $status = (string)($it['status'] ?? 'pendiente');
          $per = is_array($it['permisos'] ?? null) ? $it['permisos'] : [];
          $pDir = (int)($per['directorio'] ?? 0);
          $pHis = (int)($per['historia'] ?? 0);
          $pNot = (int)($per['notas'] ?? 0);
        ?>
          <tr>
            <td>
              <span class="badge <?= e(badge_class($status)) ?>"><?= e($status) ?></span>
              <div class="small" style="margin-top:6px;">
                <div><b>ID:</b> <?= e($id) ?></div>
                <?php if (!empty($it['created_at'])): ?><div><b>Alta:</b> <?= e((string)$it['created_at']) ?></div><?php endif; ?>
                <?php if (!empty($it['updated_at'])): ?><div><b>Upd:</b> <?= e((string)$it['updated_at']) ?></div><?php endif; ?>
                <?php if (!empty($it['last_login'])): ?><div><b>Login:</b> <?= e((string)$it['last_login']) ?></div><?php endif; ?>
              </div>
            </td>

            <td>
              <form method="post" style="margin:0;">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <input type="text" name="nombre" value="<?= e((string)($it['nombre'] ?? '')) ?>">
            </td>

            <td><input type="email" name="email" value="<?= e((string)($it['email'] ?? '')) ?>"></td>
            <td><input type="text" name="telefono" value="<?= e((string)($it['telefono'] ?? '')) ?>"></td>
            <td><input type="text" name="provincia" value="<?= e((string)($it['provincia'] ?? '')) ?>"></td>
            <td><input type="text" name="localidad" value="<?= e((string)($it['localidad'] ?? '')) ?>"></td>
            <td><input type="text" name="usuario" value="<?= e((string)($it['usuario'] ?? '')) ?>"></td>

            <td>
              <select name="role">
                <?php
                  $roles = ['colaborador','admin'];
                  $cur = (string)($it['role'] ?? 'colaborador');
                  foreach ($roles as $r):
                ?>
                  <option value="<?= e($r) ?>" <?= $r === $cur ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
              </select>

              <div style="margin-top:8px;">
                <select name="status">
                  <?php
                    $st = ['pendiente','aprobado','rechazado'];
                    foreach ($st as $s):
                  ?>
                    <option value="<?= e($s) ?>" <?= $s === strtolower(trim($status)) ? 'selected' : '' ?>><?= e($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </td>

            <td>
              <div class="checks">
                <label><input type="checkbox" name="perm_directorio" value="1" <?= $pDir ? 'checked' : '' ?>> Directorio</label>
                <label><input type="checkbox" name="perm_historia" value="1" <?= $pHis ? 'checked' : '' ?>> Historia</label>
                <label><input type="checkbox" name="perm_notas" value="1" <?= $pNot ? 'checked' : '' ?>> Notas</label>
              </div>
            </td>

            <td>
              <input type="password" name="newpass" placeholder="(vacío = no cambia)">
              <div class="small" style="margin-top:6px;">
                <?= !empty($it['must_change_password']) ? '🔐 debe cambiar clave' : '—' ?>
              </div>
            </td>

            <td>
              <div class="actions">
                <button class="btn sm" type="submit">💾 Guardar</button>
              </form>

              <form method="post" style="margin:0;">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <button class="btn ok sm" type="submit">✅ Aprobar</button>
              </form>

              <form method="post" style="margin:0;">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <button class="btn alt sm" type="submit">⛔ Rechazar</button>
              </form>

              <form method="post" style="margin:0;">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <button class="btn alt sm" type="submit">🔁 Reset clave</button>
              </form>

              <form method="post" style="margin:0;" onsubmit="return confirm('¿Eliminar definitivamente esta cuenta?');">
                <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= e($id) ?>">
                <button class="btn bad sm" type="submit">🗑️ Borrar</button>
              </form>
              </div>

              <div class="small" style="margin-top:10px;">
                <?php if (!empty($it['approved_at']) || !empty($it['approved_by'])): ?>
                  <b>Aprobado:</b> <?= e((string)($it['approved_at'] ?? '—')) ?> · <?= e((string)($it['approved_by'] ?? '—')) ?><br>
                <?php endif; ?>
                <?php if (!empty($it['created_ip'])): ?>
                  <b>IP:</b> <?= e((string)$it['created_ip']) ?><br>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (count($items) === 0): ?>
          <tr><td colspan="11" style="padding:16px;">
            <div class="flash warn">No hay cuentas cargadas todavía en <b>items[]</b>.</div>
          </td></tr>
        <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="title">Fichas públicas (collaborators) — solo lectura</div>
    <div class="small">Esto es lo que podés mostrar en /colaboradores.html como “Red de colaboradores” (no son cuentas de login).</div>

    <div class="table-wrap" style="margin-top:12px;">
      <table style="min-width:900px;">
        <thead>
          <tr>
            <th>Nivel</th>
            <th>Provincia</th>
            <th>Municipio</th>
            <th>Localidad</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Email</th>
            <th>Estado</th>
            <th>Nota</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($collaborators_public as $c):
            if (!is_array($c)) continue;
          ?>
            <tr>
              <td><?= e((string)($c['nivel'] ?? '')) ?></td>
              <td><?= e(fix_mojibake((string)($c['provincia'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['municipio'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['localidad'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['nombre'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['rol'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['email'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['estado'] ?? ''))) ?></td>
              <td><?= e(fix_mojibake((string)($c['nota'] ?? ''))) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (count($collaborators_public) === 0): ?>
            <tr><td colspan="9" style="padding:16px;">
              <div class="flash warn">No hay fichas en <b>collaborators[]</b>.</div>
            </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<footer>
  <div><b>defensacivil.com.ar</b> · Admin</div>
</footer>

</body>
</html>