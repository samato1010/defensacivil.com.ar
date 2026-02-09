<?php
// /admin/login.php - VERSIÓN CON RATE LIMITING
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

// Establecer headers de seguridad
set_security_headers();

if (is_logged_in()) {
  header('Location: index.php');
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = trim((string)($_POST['username'] ?? ''));
  $p = (string)($_POST['password'] ?? '');
  $token = (string)($_POST['csrf'] ?? '');

  // 1. Verificar CSRF
  if (!csrf_check($token)) {
    $error = 'CSRF inválido. Reintentá.';
    log_security_event('csrf_fail', 'CSRF token inválido en login');
  }
  // 2. Verificar rate limiting (máximo 5 intentos por hora)
  elseif (!check_rate_limit('login', 5, 3600)) {
    $attempts_left = get_remaining_attempts('login', 5);
    $error = 'Demasiados intentos de login. Esperá una hora e intentá de nuevo.';
    log_security_event('rate_limit_exceeded', 'Límite de login excedido', ['username' => $u]);
  }
  // 3. Verificar campos no vacíos
  elseif ($u === '' || $p === '') {
    $error = 'Completá usuario y contraseña.';
  }
  // 4. Intentar login
  else {
    $ok = false;
    $loginRole = 'colaborador';
    $loginPerms = [];

    // Admin fijo
    if (isset($USERS[$u]['pass_hash']) && password_verify($p, (string)$USERS[$u]['pass_hash'])) {
      $ok = true;
      $loginRole = 'admin';
      $loginPerms = ['directorio'=>1,'historia'=>1,'notas'=>1,'colaboradores'=>1];
      log_security_event('login_success', 'Login exitoso (admin fijo)', ['username' => $u]);
    }

    // Usuarios desde archivo privado
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

          if ($status !== 'aprobado') {
            $error = 'Tu cuenta todavía no está aprobada.';
            log_security_event('login_fail', 'Cuenta no aprobada', ['username' => $u]);
            break;
          }

          $hash = (string)($it['pass_hash'] ?? '');
          if (!$hash || !password_verify($p, $hash)) {
            $error = 'Usuario o contraseña inválidos.';
            log_security_event('login_fail', 'Credenciales inválidas', ['username' => $u]);
            break;
          }

          // Login exitoso
          $ok = true;
          $loginRole = $role ?: 'colaborador';
          $perms = $it['permisos'] ?? [];
          $loginPerms = is_array($perms) ? $perms : [];

          // Actualizar last_login
          $data['items'][$idx]['last_login'] = date('Y-m-d H:i:s');
          $data['items'][$idx]['updated_at'] = date('Y-m-d H:i:s');
          $data['meta']['updated'] = date('Y-m-d H:i:s');
          json_write_atomic(COLAB_PRIVATE_JSON, $data);
          
          log_security_event('login_success', 'Login exitoso (colaborador)', ['username' => $u]);

          break;
        }
      }

      if (!$ok && $error === '') {
        $error = 'Usuario o contraseña inválidos.';
        log_security_event('login_fail', 'Usuario no encontrado', ['username' => $u]);
      }
    }

    if ($ok) {
      session_login($u, $loginRole, $loginPerms);
      header('Location: index.php');
      exit;
    }
  }
}

// Obtener intentos restantes para mostrar al usuario
$attempts_left = get_remaining_attempts('login', 5);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Login Admin · Defensa Civil</title>
  <link rel="stylesheet" href="/assets/css/main.css?v=1.0.0"/>
  <style>
    .attempts-warning {
      background: #fff9e6;
      border: 1px solid rgba(255,152,0,0.25);
      color: #7a5300;
      padding: 10px 12px;
      border-radius: 8px;
      margin: 10px 0;
      font-size: 0.9em;
    }
  </style>
</head>
<body>
<header>
  <div class="wrap" style="max-width:520px;">
    <h1>Panel Admin</h1>
    <div class="sub">Ingresá con tu usuario (admin fijo o colaborador aprobado).</div>
  </div>
</header>

<div class="wrap" style="max-width:520px;">
  <div class="card">
    <?php if ($error): ?>
      <div class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    
    <?php if ($attempts_left <= 2 && $attempts_left > 0): ?>
      <div class="attempts-warning">
        ⚠️ Te quedan <strong><?php echo $attempts_left; ?></strong> intentos antes de ser bloqueado temporalmente.
      </div>
    <?php endif; ?>

    <form method="post" action="login.php">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"/>

      <label>Usuario</label>
      <input name="username" autocomplete="username" required />

      <label>Contraseña</label>
      <input type="password" name="password" autocomplete="current-password" required />

      <div class="row" style="display:flex;gap:10px;margin-top:14px;">
        <button class="btn" type="submit">Entrar</button>
        <a class="btn alt" href="/index.html">Volver al sitio</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
