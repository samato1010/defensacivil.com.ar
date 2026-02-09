<?php
// /admin/colaboradores_admin.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');

require_login();

// SOLO ADMIN
if (($_SESSION['user'] ?? '') !== 'admin') {
  header('Location: /admin/index.php');
  exit;
}

$SITE_ROOT = dirname(__DIR__);
$DB_PATH   = $SITE_ROOT . '/data/colaboradores.json';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function read_json_safe(string $path): array {
  if (!file_exists($path)) return ['meta'=>['updated'=>date('Y-m-d H:i:s')], 'items'=>[]];
  $raw = @file_get_contents($path);
  if ($raw === false) return ['meta'=>['updated'=>date('Y-m-d H:i:s')], 'items'=>[]];
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
  $data = json_decode($raw, true);
  if (!is_array($data)) return ['meta'=>['updated'=>date('Y-m-d H:i:s')], 'items'=>[]];
  if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];
  if (!isset($data['meta']) || !is_array($data['meta'])) $data['meta'] = ['updated'=>date('Y-m-d H:i:s')];
  return $data;
}

function write_json_atomic(string $path, array $data): bool {
  $dir = dirname($path);
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $tmp = $path . '.tmp.' . bin2hex(random_bytes(6));
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;

  if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
  @chmod($tmp, 0644);
  return @rename($tmp, $path);
}

function find_index_by_id(array $items, string $id): int {
  foreach ($items as $i => $it) {
    if (is_array($it) && (string)($it['id'] ?? '') === $id) return (int)$i;
  }
  return -1;
}

function norm_status(string $s): string {
  $s = strtolower(trim($s));
  $allowed = ['pendiente','aprobado','rechazado','deshabilitado'];
  return in_array($s, $allowed, true) ? $s : 'pendiente';
}

$db = read_json_safe($DB_PATH);
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf'] ?? '');
  if (!csrf_check($token)) {
    $err = 'CSRF inválido. Refrescá la página.';
  } else {
    $action = (string)($_POST['action'] ?? '');
    $id = (string)($_POST['id'] ?? '');

    $idx = find_index_by_id($db['items'], $id);
    if ($idx < 0) {
      $err = 'No se encontró el registro.';
    } else {
      $now = date('Y-m-d H:i:s');
      $it = $db['items'][$idx];

      // Helpers para permisos
      $p_dir  = isset($_POST['p_directorio']) ? 1 : 0;
      $p_hist = isset($_POST['p_historia']) ? 1 : 0;
      $p_not  = isset($_POST['p_notas']) ? 1 : 0;

      if ($action === 'aprobar') {
        $it['status'] = 'aprobado';
        $it['approved_at'] = $now;
        $it['approved_by'] = 'admin';
        $it['updated_at'] = $now;

        // Si admin marca permisos, se guardan; si no, default razonable
        $it['permisos'] = [
          'directorio' => $p_dir ?: 1,
          'historia'   => $p_hist ?: 1,
          'notas'      => $p_not ?: 0,
        ];

        // Permitir setear role
        $role = (string)($_POST['role'] ?? 'colaborador');
        $it['role'] = ($role === 'admin') ? 'admin' : 'colaborador';

        $db['items'][$idx] = $it;
        $db['meta']['updated'] = $now;

        if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
        else $msg = 'Aprobado ✅';

      } elseif ($action === 'rechazar') {
        $it['status'] = 'rechazado';
        $it['updated_at'] = $now;
        $it['approved_at'] = null;
        $it['approved_by'] = null;
        $db['items'][$idx] = $it;
        $db['meta']['updated'] = $now;

        if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
        else $msg = 'Rechazado ✅';

      } elseif ($action === 'deshabilitar') {
        $it['status'] = 'deshabilitado';
        $it['updated_at'] = $now;
        $db['items'][$idx] = $it;
        $db['meta']['updated'] = $now;

        if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
        else $msg = 'Deshabilitado ✅';

      } elseif ($action === 'habilitar') {
        $it['status'] = 'aprobado';
        $it['updated_at'] = $now;
        $db['items'][$idx] = $it;
        $db['meta']['updated'] = $now;

        if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
        else $msg = 'Habilitado ✅';

      } elseif ($action === 'permisos') {
        $it['permisos'] = [
          'directorio' => $p_dir ? 1 : 0,
          'historia'   => $p_hist ? 1 : 0,
          'notas'      => $p_not ? 1 : 0,
        ];
        $role = (string)($_POST['role'] ?? ($it['role'] ?? 'colaborador'));
        $it['role'] = ($role === 'admin') ? 'admin' : 'colaborador';

        $it['updated_at'] = $now;
        $db['items'][$idx] = $it;
        $db['meta']['updated'] = $now;

        if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
        else $msg = 'Permisos actualizados ✅';

      } elseif ($action === 'reset_pass') {
        $np = (string)($_POST['newpass'] ?? '');
        $np2 = (string)($_POST['newpass2'] ?? '');
        if (strlen($np) < 8) {
          $err = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($np !== $np2) {
          $err = 'Las contraseñas no coinciden.';
        } else {
          $it['pass_hash'] = password_hash($np, PASSWORD_BCRYPT, ['cost'=>10]);
          $it['updated_at'] = $now;
          $db['items'][$idx] = $it;
          $db['meta']['updated'] = $now;

          if (!write_json_atomic($DB_PATH, $db)) $err = 'No se pudo guardar. Verificá permisos en /data.';
          else $msg = 'Contraseña reseteada ✅';
        }

      } else {
        $err = 'Acción inválida.';
      }

      // recargar DB post-acción
      $db = read_json_safe($DB_PATH);
    }
  }
}

// Agrupar
$items = $db['items'] ?? [];
$pend = [];
$aprob = [];
$rech = [];
$desh = [];

foreach ($items as $it) {
  if (!is_array($it)) continue;
  $st = norm_status((string)($it['status'] ?? 'pendiente'));
  if ($st === 'pendiente') $pend[] = $it;
  elseif ($st === 'aprobado') $aprob[] = $it;
  elseif ($st === 'rechazado') $rech[] = $it;
  else $desh[] = $it;
}

function perm_checked(array $it, string $k, int $fallback=0): string {
  $p = $it['permisos'] ?? [];
  $v = is_array($p) ? (int)($p[$k] ?? $fallback) : $fallback;
  return $v ? 'checked' : '';
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin · Colaboradores</title>
  <style>
    :root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--card:#fff;--tx:#222;--mut:#555;--b:rgba(0,0,0,.10);--r:14px;--ok:#0a5a2b;--bad:#7a0000;}
    *{box-sizing:border-box}
    body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);line-height:1.7}
    header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
    .head{max-width:1200px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
    h1{margin:0;font-size:1.25em}
    .nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
    .nav a:hover{background:rgba(255,255,255,.20);}
    .wrap{max-width:1200px;margin:18px auto;padding:0 16px 60px;}
    .card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:16px;margin:12px 0;}
    .title{margin:0 0 10px;color:var(--az1);border-left:4px solid var(--n);padding-left:12px;font-size:1.15em;}
    .kpi{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 10px;}
    .chip{display:inline-flex;align-items:center;gap:10px;padding:8px 12px;border-radius:999px;font-weight:900;background:#eaf1ff;color:var(--az1);border:1px solid rgba(0,51,102,0.12);}
    .msg{background:#f2fff6;border:1px solid rgba(0,128,64,0.18);color:var(--ok);padding:12px 14px;border-radius:12px;margin:0 0 12px;}
    .err{background:#fff3f3;border:1px solid rgba(180,0,0,0.18);color:var(--bad);padding:12px 14px;border-radius:12px;margin:0 0 12px;}
    table{width:100%;border-collapse:collapse;}
    th,td{padding:10px 10px;border-bottom:1px solid rgba(0,0,0,.08);vertical-align:top;}
    th{color:var(--az1);text-align:left;background:linear-gradient(to bottom,#eaf1ff,#dbe8ff);position:sticky;top:0;}
    .small{color:var(--mut);font-size:.95em}
    .btn{border:0;border-radius:999px;padding:9px 12px;cursor:pointer;font-weight:900;background:var(--az2);color:#fff;transition:.15s;text-decoration:none;display:inline-flex;align-items:center;gap:8px;}
    .btn:hover{background:#0066cc;transform:translateY(-1px);}
    .btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08);}
    .btn.alt:hover{background:#dde7f7;}
    .btn.bad{background:#a40000;}
    .btn.bad:hover{background:#c00000;}
    .btn.warn{background:#ff6600;}
    .btn.warn:hover{background:#ff7a1f;}
    .rowbtn{display:flex;gap:8px;flex-wrap:wrap}
    .box{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:10px;margin-top:8px;}
    label{font-weight:900;color:var(--az1);}
    input[type="text"],input[type="password"],select{padding:8px 10px;border:1px solid rgba(0,0,0,.14);border-radius:10px;outline:0;}
    .tabs{display:flex;gap:10px;flex-wrap:wrap;margin:8px 0 0;}
    .tab{border:1px solid rgba(0,0,0,.10);background:#fff;color:var(--az1);font-weight:900;padding:9px 12px;border-radius:999px;cursor:pointer;}
    .tab.active{background:var(--az1);color:#fff;border-color:transparent;}
    .section{display:none;}
    .section.active{display:block;}

    /* UI enhancements */
    .card{transition:box-shadow .2s ease}
    .card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09)}
    html{scroll-behavior:smooth}
    ::-webkit-scrollbar{width:8px;height:8px}
    ::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px}
    ::-webkit-scrollbar-thumb{background:#b0bec5;border-radius:4px}
    ::-webkit-scrollbar-thumb:hover{background:#90a4ae}
  </style>
</head>
<body>
<header>
  <div class="head">
    <div>
      <h1>Admin · Colaboradores</h1>
      <div class="small" style="color:rgba(255,255,255,.9);">Aprobar / rechazar / permisos · Fuente: /data/colaboradores.json</div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Panel</a>
      <a href="/colaboradores.html">Web</a>
      <a href="/admin/mapa.php">Mapa</a>
    </div>
  </div>
</header>

<main class="wrap">
  <div class="card">
    <h2 class="title">Estado</h2>

    <?php if ($msg): ?><div class="msg"><?=h($msg)?></div><?php endif; ?>
    <?php if ($err): ?><div class="err"><strong>Error:</strong> <?=h($err)?></div><?php endif; ?>

    <div class="kpi">
      <span class="chip">⏳ Pendientes: <?=count($pend)?></span>
      <span class="chip">✅ Aprobados: <?=count($aprob)?></span>
      <span class="chip">❌ Rechazados: <?=count($rech)?></span>
      <span class="chip">⛔ Deshabilitados: <?=count($desh)?></span>
      <span class="chip">🕒 Updated: <?=h((string)($db['meta']['updated'] ?? ''))?></span>
    </div>

    <div class="tabs">
      <button class="tab active" data-target="secPend">Pendientes</button>
      <button class="tab" data-target="secAprob">Aprobados</button>
      <button class="tab" data-target="secDesh">Deshabilitados</button>
      <button class="tab" data-target="secRech">Rechazados</button>
    </div>
  </div>

  <div class="card section active" id="secPend">
    <h2 class="title">⏳ Pendientes</h2>

    <?php if (!count($pend)): ?>
      <p class="small">No hay solicitudes pendientes.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Solicitante</th>
            <th>Jurisdicción</th>
            <th>Usuario</th>
            <th>Detalle</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pend as $it): ?>
            <tr>
              <td>
                <strong><?=h((string)($it['nombre'] ?? ''))?></strong><br/>
                <span class="small"><?=h((string)($it['email'] ?? ''))?></span><br/>
                <span class="small"><?=h((string)($it['telefono'] ?? ''))?></span>
              </td>
              <td>
                <strong><?=h((string)($it['provincia'] ?? ''))?></strong><br/>
                <span class="small"><?=h((string)($it['localidad'] ?? ''))?></span><br/>
                <span class="small">Nivel sugerido: <strong><?=h((string)($it['nivel_sugerido'] ?? 'local'))?></strong></span>
              </td>
              <td>
                <strong><?=h((string)($it['usuario'] ?? ''))?></strong><br/>
                <span class="small">Creado: <?=h((string)($it['created_at'] ?? ''))?></span>
              </td>
              <td>
                <?php if (!empty($it['mensaje'])): ?>
                  <div class="box"><?=nl2br(h((string)$it['mensaje']))?></div>
                <?php else: ?>
                  <span class="small">—</span>
                <?php endif; ?>
                <div class="small" style="margin-top:8px;">
                  IP: <?=h((string)($it['created_ip'] ?? ''))?>
                </div>
              </td>
              <td>
                <form method="post" class="box" style="margin:0;">
                  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>"/>
                  <input type="hidden" name="id" value="<?=h((string)($it['id'] ?? ''))?>"/>

                  <div class="small" style="margin-bottom:6px;"><strong>Permisos al aprobar</strong></div>
                  <div class="rowbtn">
                    <label><input type="checkbox" name="p_directorio" checked/> Directorio</label>
                    <label><input type="checkbox" name="p_historia" checked/> Historia</label>
                    <label><input type="checkbox" name="p_notas"/> Notas</label>
                  </div>

                  <div style="margin-top:8px;">
                    <label class="small">Role</label><br/>
                    <select name="role">
                      <option value="colaborador" selected>colaborador</option>
                      <option value="admin">admin (no recomendado)</option>
                    </select>
                  </div>

                  <div class="rowbtn" style="margin-top:10px;">
                    <button class="btn" name="action" value="aprobar" type="submit">✅ Aprobar</button>
                    <button class="btn bad" name="action" value="rechazar" type="submit">❌ Rechazar</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card section" id="secAprob">
    <h2 class="title">✅ Aprobados</h2>

    <?php if (!count($aprob)): ?>
      <p class="small">No hay aprobados.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Solicitante</th>
            <th>Jurisdicción</th>
            <th>Permisos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($aprob as $it): ?>
            <tr>
              <td>
                <strong><?=h((string)($it['usuario'] ?? ''))?></strong><br/>
                <span class="small">Role: <?=h((string)($it['role'] ?? 'colaborador'))?></span><br/>
                <span class="small">Último login: <?=h((string)($it['last_login'] ?? '—'))?></span>
              </td>
              <td>
                <strong><?=h((string)($it['nombre'] ?? ''))?></strong><br/>
                <span class="small"><?=h((string)($it['email'] ?? ''))?></span>
              </td>
              <td>
                <strong><?=h((string)($it['provincia'] ?? ''))?></strong><br/>
                <span class="small"><?=h((string)($it['localidad'] ?? ''))?></span>
              </td>
              <td class="small">
                Dir: <strong><?=((int)(($it['permisos']['directorio'] ?? 0)) ? 'Sí' : 'No')?></strong> ·
                His: <strong><?=((int)(($it['permisos']['historia'] ?? 0)) ? 'Sí' : 'No')?></strong> ·
                Not: <strong><?=((int)(($it['permisos']['notas'] ?? 0)) ? 'Sí' : 'No')?></strong>
              </td>
              <td>
                <form method="post" class="box" style="margin:0;">
                  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>"/>
                  <input type="hidden" name="id" value="<?=h((string)($it['id'] ?? ''))?>"/>

                  <div class="small" style="margin-bottom:6px;"><strong>Ajustar permisos</strong></div>
                  <div class="rowbtn">
                    <label><input type="checkbox" name="p_directorio" <?=perm_checked($it,'directorio',1)?>/> Directorio</label>
                    <label><input type="checkbox" name="p_historia" <?=perm_checked($it,'historia',1)?>/> Historia</label>
                    <label><input type="checkbox" name="p_notas" <?=perm_checked($it,'notas',0)?>/> Notas</label>
                  </div>

                  <div style="margin-top:8px;">
                    <label class="small">Role</label><br/>
                    <select name="role">
                      <option value="colaborador" <?=((string)($it['role'] ?? '')!=='admin'?'selected':'')?>>colaborador</option>
                      <option value="admin" <?=((string)($it['role'] ?? '')==='admin'?'selected':'')?>>admin</option>
                    </select>
                  </div>

                  <div class="rowbtn" style="margin-top:10px;">
                    <button class="btn alt" name="action" value="permisos" type="submit">💾 Guardar</button>
                    <button class="btn warn" name="action" value="deshabilitar" type="submit">⛔ Deshabilitar</button>
                  </div>

                  <div class="box" style="margin-top:10px;">
                    <div class="small" style="margin-bottom:6px;"><strong>Reset contraseña</strong></div>
                    <div class="rowbtn">
                      <input type="password" name="newpass" placeholder="Nueva (mín 8)"/>
                      <input type="password" name="newpass2" placeholder="Repetir"/>
                    </div>
                    <div class="rowbtn" style="margin-top:8px;">
                      <button class="btn bad" name="action" value="reset_pass" type="submit">🔁 Reset</button>
                    </div>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card section" id="secDesh">
    <h2 class="title">⛔ Deshabilitados</h2>

    <?php if (!count($desh)): ?>
      <p class="small">No hay deshabilitados.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Solicitante</th>
            <th>Jurisdicción</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($desh as $it): ?>
            <tr>
              <td><strong><?=h((string)($it['usuario'] ?? ''))?></strong></td>
              <td><?=h((string)($it['nombre'] ?? ''))?><br/><span class="small"><?=h((string)($it['email'] ?? ''))?></span></td>
              <td><?=h((string)($it['provincia'] ?? ''))?> · <span class="small"><?=h((string)($it['localidad'] ?? ''))?></span></td>
              <td>
                <form method="post" class="rowbtn" style="margin:0;">
                  <input type="hidden" name="csrf" value="<?=h(csrf_token())?>"/>
                  <input type="hidden" name="id" value="<?=h((string)($it['id'] ?? ''))?>"/>
                  <button class="btn" name="action" value="habilitar" type="submit">✅ Habilitar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card section" id="secRech">
    <h2 class="title">❌ Rechazados</h2>

    <?php if (!count($rech)): ?>
      <p class="small">No hay rechazados.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Usuario</th>
            <th>Solicitante</th>
            <th>Motivo / mensaje</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rech as $it): ?>
            <tr>
              <td><strong><?=h((string)($it['usuario'] ?? ''))?></strong></td>
              <td><?=h((string)($it['nombre'] ?? ''))?><br/><span class="small"><?=h((string)($it['email'] ?? ''))?></span></td>
              <td><?=!empty($it['mensaje']) ? nl2br(h((string)$it['mensaje'])) : '<span class="small">—</span>'?></td>
              <td class="small">—</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</main>

<script>
  const tabs = document.querySelectorAll('.tab');
  const secs = document.querySelectorAll('.section');

  tabs.forEach(t => t.addEventListener('click', () => {
    tabs.forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    secs.forEach(s => s.classList.remove('active'));
    document.getElementById(t.dataset.target).classList.add('active');
    window.scrollTo({top:0, behavior:'smooth'});
  }));
</script>
</body>
</html>