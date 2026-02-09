<?php
// /admin/login.php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (is_logged_in()) {
  header('Location: index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim((string)($_POST['username'] ?? ''));
  $p = (string)($_POST['password'] ?? '');
  $token = (string)($_POST['csrf'] ?? '');

  if (!csrf_check($token)) {
    $error = 'CSRF inválido. Reintentá.';
  } elseif ($u === '' || $p === '') {
    $error = 'Completá usuario y contraseña.';
  } else {
    $ok = false;
    $loginRole = 'colaborador';
    $loginPerms = [];

    // 1) Admin fijo (config.php $USERS)
    if (isset($USERS[$u]['pass_hash']) && password_verify($p, (string)$USERS[$u]['pass_hash'])) {
      $ok = true;
      $loginRole = 'admin';
      $loginPerms = ['directorio'=>1,'historia'=>1,'notas'=>1]; // full
    }

    // 2) Usuarios desde archivo PRIVADO (admin/_storage/colaboradores_private.json)
    if (!$ok) {
      $data = json_read(COLAB_PRIVATE_JSON, ['meta'=>[], 'items'=>[]]);
      $items = $data['items'] ?? null;

      if (is_array($items)) {
        foreach ($items as $idx => $it) {
          if (!is_array($it)) continue;

          $user = (string)($it['usuario'] ?? '');
          if ($user !== $u) continue;

          $status = strtolower(trim((string)($it['status'] ?? '')));
          $role   = strtolower(trim((string)($it['role'] ?? 'colaborador')));

          // SOLO aprobados
          if ($status !== 'aprobado') {
            $error = 'Tu cuenta todavía no está aprobada.';
            break;
          }

          $hash = (string)($it['pass_hash'] ?? '');
          if (!$hash || !password_verify($p, $hash)) {
            $error = 'Usuario o contraseña inválidos.';
            break;
          }

          // OK login por JSON
          $ok = true;
          $loginRole = $role ?: 'colaborador';
          $perms = $it['permisos'] ?? [];
          $loginPerms = is_array($perms) ? $perms : [];

          // update last_login
          $data['items'][$idx]['last_login'] = date('Y-m-d H:i:s');
          $data['items'][$idx]['updated_at'] = date('Y-m-d H:i:s');
          $data['meta']['updated'] = date('Y-m-d H:i:s');
          json_write_atomic(COLAB_PRIVATE_JSON, $data);

          break;
        }
      }

      if (!$ok && $error === '') {
        $error = 'Usuario o contraseña inválidos.';
      }
    }

    if ($ok) {
      session_login($u, $loginRole, $loginPerms);
      header('Location: index.php');
      exit;
    }
  }
}

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login Admin · Defensa Civil</title>
  <style>
    :root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--b:rgba(0,0,0,.10);}
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial,Helvetica,sans-serif;background:var(--bg);color:#222}
    header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px}
    .wrap{max-width:520px;margin:22px auto;padding:0 16px}
    .card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:14px;box-shadow:0 6px 16px rgba(0,0,0,.06);padding:16px}
    h1{margin:0;font-size:1.25em}
    .sub{opacity:.9;margin-top:6px}
    label{display:block;font-weight:800;color:var(--az1);margin:10px 0 6px}
    input{width:100%;padding:12px 12px;border:1px solid var(--b);border-radius:12px;font-size:1em;outline:0}
    input:focus{border-color:rgba(0,102,204,.55);box-shadow:0 0 0 3px rgba(0,102,204,.10)}
    .row{display:flex;gap:10px;align-items:center;margin-top:14px;flex-wrap:wrap}
    .btn{border:0;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:900;background:var(--az2);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.14);transition:.15s}
    .btn:hover{background:#0066cc;transform:translateY(-1px)}
    .btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08)}
    .err{background:#fff3f3;border:1px solid rgba(180,0,0,0.18);color:#7a0000;padding:12px 14px;border-radius:12px;margin:12px 0}
    a{color:#0066cc;font-weight:800;text-decoration:none}
    a:hover{text-decoration:underline}
  </style>
</head>
<body>
<header>
  <div class="wrap">
    <h1>Panel Admin</h1>
    <div class="sub">Ingresá con tu usuario (admin fijo o colaborador aprobado).</div>
  </div>
</header>

<div class="wrap">
  <div class="card">
    <?php if ($error): ?>
      <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"/>

      <label>Usuario</label>
      <input name="username" autocomplete="username" required />

      <label>Contraseña</label>
      <input type="password" name="password" autocomplete="current-password" required />

      <div class="row">
        <button class="btn" type="submit">Entrar</button>
        <a class="btn alt" href="/index.html">Volver al sitio</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
