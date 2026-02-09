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
  $u = strtolower(trim((string)($_POST['username'] ?? '')));
  $p = (string)($_POST['password'] ?? '');
  $token = (string)($_POST['csrf'] ?? '');

  if (!csrf_check($token)) {
    $error = 'CSRF inválido. Reintentá.';
  } elseif ($u === '' || $p === '') {
    $error = 'Completá usuario y contraseña.';
  } else {
    $user = dc_get_user($u);

    if ($user === null) {
      $error = 'Usuario o contraseña inválidos.';
    } elseif (!password_verify($p, $user['pass_hash'])) {
      $error = 'Usuario o contraseña inválidos.';
    } elseif (!$user['enabled']) {
      $error = 'Tu cuenta todavía no está aprobada.';
    } else {
      // Login OK
      dc_login_session_set($u, $user);

      // Actualizar last_login en colaboradores.json si no es usuario interno
      if ($user['status'] !== 'interno') {
        $data = json_read(COLAB_JSON, ['meta'=>[], 'items'=>[]]);
        $items = $data['items'] ?? [];
        foreach ($items as $idx => $it) {
          if (strtolower(trim((string)($it['usuario'] ?? ''))) === $u) {
            $data['items'][$idx]['last_login'] = date('Y-m-d H:i:s');
            $data['items'][$idx]['updated_at'] = date('Y-m-d H:i:s');
            $data['meta']['updated'] = date('Y-m-d H:i:s');
            json_write_atomic(COLAB_JSON, $data);
            break;
          }
        }
      }

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
  <title>Login Admin &middot; Defensa Civil</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg"/>
  <style>
    :root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--b:rgba(0,0,0,.10);--r:14px;}
    *{box-sizing:border-box}
    body{margin:0;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#e4eaf3 0%,#f0f4f8 40%,#e8edf5 100%);color:#222;min-height:100vh;display:flex;flex-direction:column}

    /* Header */
    header{background:linear-gradient(135deg,var(--az1) 0%,var(--az2) 60%,#004a99 100%);color:#fff;padding:22px 16px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.18)}
    header h1{margin:0;font-size:1.4em;display:inline-flex;align-items:center;gap:10px}
    header .sub{opacity:.85;margin-top:6px;font-size:.92em}

    /* Main content */
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:24px 16px}

    /* Card */
    .card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 12px 40px rgba(0,0,0,.10);padding:28px 24px;width:100%;max-width:420px;animation:fadeInUp .5s ease both}
    @keyframes fadeInUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

    /* Logo */
    .logo-wrap{text-align:center;margin-bottom:20px}
    .logo{width:72px;height:72px;border-radius:16px;box-shadow:0 6px 20px rgba(0,51,102,.15);animation:logoIn .6s ease both;animation-delay:.15s}
    @keyframes logoIn{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}

    /* Form */
    label{display:block;font-weight:800;color:var(--az1);margin:14px 0 6px;font-size:.92em}
    .input-wrap{position:relative}
    .input-wrap .icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;opacity:.5}
    input[type="text"],input[type="password"]{width:100%;padding:12px 12px 12px 42px;border:1px solid var(--b);border-radius:12px;font-size:1em;outline:0;transition:.2s}
    input:focus{border-color:rgba(0,102,204,.55);box-shadow:0 0 0 3px rgba(0,102,204,.12)}

    /* Password toggle */
    .pass-wrap{position:relative}
    .toggle-pass{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:0;cursor:pointer;font-size:1.3em;padding:4px 6px;opacity:.6;transition:.2s}
    .toggle-pass:hover{opacity:1}

    /* Buttons row */
    .row{display:flex;gap:10px;align-items:center;margin-top:18px;flex-wrap:wrap}
    .btn{border:0;border-radius:999px;padding:12px 20px;cursor:pointer;font-weight:900;font-size:.95em;background:var(--az2);color:#fff;box-shadow:0 4px 14px rgba(0,0,0,.14);transition:.2s;display:inline-flex;align-items:center;gap:8px;text-decoration:none}
    .btn:hover{background:#0066cc;transform:translateY(-1px);box-shadow:0 6px 20px rgba(0,0,0,.18)}
    .btn:disabled{opacity:.65;cursor:wait;transform:none}
    .btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08)}
    .btn.alt:hover{background:#dde7f7}

    /* Spinner */
    .spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}

    /* Error */
    .err{background:#fff3f3;border:1px solid rgba(180,0,0,0.18);color:#7a0000;padding:12px 14px;border-radius:12px;margin:0 0 16px;font-size:.93em;display:flex;align-items:center;gap:10px;animation:shake .4s ease}
    @keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-6px)}40%,80%{transform:translateX(6px)}}

    /* Link */
    a{color:#0066cc;font-weight:800;text-decoration:none}
    a:hover{text-decoration:underline}

    /* Footer hint */
    .footer-hint{text-align:center;margin-top:16px;font-size:.85em;color:#666}

    /* Scrollbar */
    ::-webkit-scrollbar{width:8px}
    ::-webkit-scrollbar-track{background:#f1f1f1}
    ::-webkit-scrollbar-thumb{background:#b0bec5;border-radius:4px}
  </style>
</head>
<body>

<header>
  <h1>&#128737;&#65039; Panel Admin</h1>
  <div class="sub">Defensa Civil Argentina</div>
</header>

<main>
  <div class="card">
    <!-- Logo -->
    <div class="logo-wrap">
      <img class="logo" src="/favicon.svg" alt="Defensa Civil" onerror="this.style.display='none'"/>
    </div>

    <?php if ($error): ?>
      <div class="err">
        <span>&#9888;&#65039;</span>
        <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="login.php" id="loginForm">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"/>

      <label for="username">Usuario</label>
      <div class="input-wrap">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <input type="text" id="username" name="username" autocomplete="username" required placeholder="Tu usuario"/>
      </div>

      <label for="password">Contrase&ntilde;a</label>
      <div class="input-wrap pass-wrap">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <input type="password" id="password" name="password" autocomplete="current-password" required placeholder="Tu contrase&ntilde;a"/>
        <button type="button" class="toggle-pass" id="togglePass" title="Mostrar/ocultar">&#128065;</button>
      </div>

      <div class="row">
        <button class="btn" type="submit" id="submitBtn">
          <span id="btnText">Entrar</span>
        </button>
        <a class="btn alt" href="/index.html">&#127968; Volver al sitio</a>
      </div>
    </form>

    <div class="footer-hint">
      Ingres&aacute; con tu usuario de admin o colaborador aprobado.
    </div>
  </div>
</main>

<script>
// Toggle password visibility
document.getElementById('togglePass').addEventListener('click', function(){
  var inp = document.getElementById('password');
  var isPass = inp.type === 'password';
  inp.type = isPass ? 'text' : 'password';
  this.textContent = isPass ? '\u{1F648}' : '\u{1F441}';
});

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function(){
  var btn = document.getElementById('submitBtn');
  var txt = document.getElementById('btnText');
  btn.disabled = true;
  txt.innerHTML = '<span class="spinner"></span> Ingresando...';
});
</script>

</body>
</html>
