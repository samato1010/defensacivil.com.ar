<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

require_role(['admin']);

$db = load_users();
$users = $db['users'] ?? [];

$action = (string)($_POST['action'] ?? '');
$user = trim((string)($_POST['user'] ?? ''));
$token = (string)($_POST['csrf'] ?? '');

$flash = '';
$err = '';

function set_colab_estado(string $username, string $estado, bool $publico): void {
  $cdb = colabs_read();
  $changed = false;
  foreach ($cdb['collaborators'] as &$c) {
    if (($c['user'] ?? '') === $username) {
      $c['estado'] = $estado;
      $c['publico'] = $publico;
      $changed = true;
    }
  }
  if ($changed) colabs_write($cdb);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($token)) {
    $err = 'CSRF inválido.';
  } elseif (!$user || !isset($users[$user])) {
    $err = 'Usuario no encontrado.';
  } else {
    if ($action === 'accept') {
      $db['users'][$user]['status'] = 'active';
      // editor por defecto; si querés que algunos sean solo "viewer", lo cambiás acá
      $db['users'][$user]['role'] = $db['users'][$user]['role'] ?? 'editor';
      if (!save_users($db)) {
        $err = 'No se pudo guardar users.json.';
      } else {
        set_colab_estado($user, 'Activo', true);
        $flash = "Aprobado: {$user}";
      }
    } elseif ($action === 'reject') {
      unset($db['users'][$user]);
      if (!save_users($db)) {
        $err = 'No se pudo guardar users.json.';
      } else {
        set_colab_estado($user, 'Rechazado', false);
        $flash = "Rechazado: {$user}";
      }
    }
  }
}

$db = load_users();
$users = $db['users'] ?? [];
$pending = [];
$active = [];

foreach ($users as $uname => $u) {
  $st = (string)($u['status'] ?? '');
  if ($st === 'pending') $pending[$uname] = $u;
  else $active[$uname] = $u;
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin · Usuarios / Aprobaciones</title>
<style>
:root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--b:rgba(0,0,0,.10);--r:14px;}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#222;}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.wrap{max-width:1100px;margin:18px auto;padding:0 16px 60px;}
.card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px;margin:12px 0;}
.badge{display:inline-flex;align-items:center;font-weight:900;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;}
.small{color:#555;font-size:.95em;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid rgba(0,0,0,.08);vertical-align:top;}
th{color:var(--az1);text-align:left;background:#f6f9ff;}
.btn{border:0;border-radius:999px;padding:8px 10px;font-weight:900;cursor:pointer;}
.btn.ok{background:#0a7;color:#fff;}
.btn.ok:hover{background:#096;}
.btn.no{background:#c33;color:#fff;}
.btn.no:hover{background:#b22;}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.msg{padding:10px;border-radius:12px;border:1px solid rgba(0,0,0,.12);background:#fff;margin:10px 0;}
.msg.ok{background:#f2fff6;border-color:rgba(0,128,64,.2);color:#0a5a2b;}
.msg.err{background:#fff3f3;border-color:rgba(180,0,0,.2);color:#7a0000;}
</style>
</head>
<body>

<header>
  <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div>
      <div style="font-weight:900;font-size:1.25em;color:var(--n);">Usuarios / Aprobaciones</div>
      <div class="small" style="color:#fff;opacity:.92;">Aprobá colaboradores para que editen Directorio e Historia.</div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Panel</a>
      <a href="/admin/logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">
  <?php if ($flash): ?><div class="msg ok"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="card">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between;">
      <div class="badge">⏳ Pendientes (<?= count($pending) ?>)</div>
      <div class="small">Alta pública: /colaborar.php</div>
    </div>

    <?php if (!count($pending)): ?>
      <p class="small" style="margin:10px 0 0;">No hay solicitudes pendientes.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Datos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pending as $uname => $u): ?>
            <?php $p = $u['profile'] ?? []; ?>
            <tr>
              <td><strong><?= htmlspecialchars($uname) ?></strong><div class="small"><?= htmlspecialchars((string)($u['created'] ?? '')) ?></div></td>
              <td class="small">
                <strong><?= htmlspecialchars((string)($p['nombre'] ?? '')) ?></strong><br>
                Nivel: <?= htmlspecialchars((string)($p['nivel'] ?? '')) ?><br>
                Provincia: <?= htmlspecialchars((string)($p['provincia'] ?? '')) ?><br>
                Municipio/Localidad: <?= htmlspecialchars(trim((string)($p['municipio'] ?? '') . ' ' . (string)($p['localidad'] ?? ''))) ?><br>
                Email/Tel: <?= htmlspecialchars(trim((string)($p['email'] ?? '') . ' ' . (string)($p['telefono'] ?? ''))) ?><br>
                Nota: <?= htmlspecialchars((string)($p['nota'] ?? '')) ?>
              </td>
              <td>
                <form method="post" style="display:flex;gap:10px;flex-wrap:wrap;">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                  <input type="hidden" name="user" value="<?= htmlspecialchars($uname, ENT_QUOTES) ?>">
                  <button class="btn ok" name="action" value="accept" type="submit">Aceptar</button>
                  <button class="btn no" name="action" value="reject" type="submit">Rechazar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="badge">✅ Activos (<?= count($active) ?>)</div>
    <p class="small" style="margin:10px 0 0;">(En la próxima mejora metemos “revocar / cambiar rol / limitar provincias”).</p>
  </div>
</div>
</body>
</html>