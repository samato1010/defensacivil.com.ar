<?php
// /colaboradores/alta.php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('America/Argentina/Buenos_Aires');

$SITE_ROOT = dirname(__DIR__); // /public_html
$DATA_DIR  = $SITE_ROOT . '/data';
$DB_PATH   = $DATA_DIR . '/colaboradores.json';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

function read_json(string $path, array $default): array {
  if (!file_exists($path)) return $default;
  $raw = @file_get_contents($path);
  if ($raw === false) return $default;

  // Strip BOM
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);

  $data = json_decode($raw, true);
  if (!is_array($data)) return $default;
  return $data;
}

function write_json_atomic(string $path, array $data): bool {
  $dir = dirname($path);
  ensure_dir($dir);

  $tmp = $path . '.tmp.' . bin2hex(random_bytes(6));
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;

  if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
  @chmod($tmp, 0644);
  return @rename($tmp, $path);
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function norm_user(string $u): string {
  $u = trim($u);
  $u = preg_replace('/\s+/', '', $u);
  return strtolower($u);
}

function is_valid_username(string $u): bool {
  // letras, números, punto, guión, guión bajo. 4-24 chars
  return (bool)preg_match('/^[a-z0-9._-]{4,24}$/', $u);
}

function is_valid_email(string $e): bool {
  return (bool)filter_var($e, FILTER_VALIDATE_EMAIL);
}

function gen_id(): string {
  return 'c-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
}

$DEFAULT_DB = [
  'meta' => [
    'titulo'   => 'Colaboradores',
    'updated'  => date('Y-m-d H:i:s'),
    'version'  => 1,
  ],
  'items' => []
];

$ok = false;
$error = '';
$posted = [
  'nombre' => '',
  'email' => '',
  'telefono' => '',
  'provincia' => '',
  'localidad' => '',
  'nivel' => 'local',
  'usuario' => '',
  'mensaje' => ''
];

// Honeypot (anti-bot)
$honeypot = $_POST['website'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $posted['nombre'] = trim((string)($_POST['nombre'] ?? ''));
  $posted['email'] = trim((string)($_POST['email'] ?? ''));
  $posted['telefono'] = trim((string)($_POST['telefono'] ?? ''));
  $posted['provincia'] = trim((string)($_POST['provincia'] ?? ''));
  $posted['localidad'] = trim((string)($_POST['localidad'] ?? ''));
  $posted['nivel'] = trim((string)($_POST['nivel'] ?? 'local'));
  $posted['usuario'] = norm_user((string)($_POST['usuario'] ?? ''));
  $posted['mensaje'] = trim((string)($_POST['mensaje'] ?? ''));

  $pass = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');

  if ($honeypot !== '') {
    $error = 'Error de validación.';
  } elseif ($posted['nombre'] === '' || mb_strlen($posted['nombre']) < 3) {
    $error = 'Ingresá tu nombre y apellido.';
  } elseif (!is_valid_email($posted['email'])) {
    $error = 'Ingresá un email válido.';
  } elseif ($posted['provincia'] === '' || mb_strlen($posted['provincia']) < 2) {
    $error = 'Ingresá una provincia.';
  } elseif ($posted['localidad'] === '' || mb_strlen($posted['localidad']) < 2) {
    $error = 'Ingresá una localidad/municipio.';
  } elseif (!in_array($posted['nivel'], ['local','provincial','nacional'], true)) {
    $error = 'Nivel inválido.';
  } elseif (!is_valid_username($posted['usuario'])) {
    $error = 'Usuario inválido. Usá 4 a 24 caracteres: letras/números y . _ -';
  } elseif (strlen($pass) < 8) {
    $error = 'La contraseña debe tener al menos 8 caracteres.';
  } elseif ($pass !== $pass2) {
    $error = 'Las contraseñas no coinciden.';
  } else {
    ensure_dir($DATA_DIR);
    $db = read_json($DB_PATH, $DEFAULT_DB);
    if (!isset($db['items']) || !is_array($db['items'])) $db['items'] = [];

    // evitar duplicados (en cualquier estado)
    $u = $posted['usuario'];
    $e = strtolower($posted['email']);

    foreach ($db['items'] as $it) {
      $iu = strtolower((string)($it['usuario'] ?? ''));
      $ie = strtolower((string)($it['email'] ?? ''));
      if ($iu === $u) { $error = 'Ese usuario ya existe. Elegí otro.'; break; }
      if ($ie === $e) { $error = 'Ese email ya está registrado. Si ya te anotaste, esperá aprobación.'; break; }
    }

    if ($error === '') {
      $now = date('Y-m-d H:i:s');

      $item = [
        'id' => gen_id(),
        'status' => 'pendiente', // pendiente | aprobado | rechazado | deshabilitado
        'created_at' => $now,
        'updated_at' => $now,
        'nombre' => $posted['nombre'],
        'email' => $posted['email'],
        'telefono' => $posted['telefono'],
        'provincia' => $posted['provincia'],
        'localidad' => $posted['localidad'],
        'nivel_sugerido' => $posted['nivel'],
        'mensaje' => $posted['mensaje'],
        'usuario' => $posted['usuario'],
        'pass_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]),
        // permisos por defecto: directorio + historia (notas NO por defecto)
        'permisos' => [
          'directorio' => 1,
          'historia'   => 1,
          'notas'      => 0
        ],
        'role' => 'colaborador',
        'approved_at' => null,
        'approved_by' => null,
        'last_login' => null,
        'created_ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180)
      ];

      $db['items'][] = $item;
      $db['meta']['updated'] = $now;

      if (!write_json_atomic($DB_PATH, $db)) {
        $error = 'No se pudo guardar la postulación. Verificá permisos de /data.';
      } else {
        $ok = true;
      }
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Alta de colaborador · Defensa Civil</title>
  <style>
    :root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--card:#fff;--tx:#222;--mut:#555;--b:rgba(0,0,0,.10);--r:14px;}
    *{box-sizing:border-box}
    body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);line-height:1.7}
    header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
    .head{max-width:980px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
    h1{margin:0;font-size:1.25em}
    .nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
    .nav a:hover{background:rgba(255,255,255,.20);}
    .wrap{max-width:980px;margin:18px auto;padding:0 16px 60px;}
    .card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:16px;}
    .title{margin:0 0 10px;color:var(--az1);border-left:4px solid var(--n);padding-left:12px;font-size:1.15em;}
    .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
    label{font-weight:900;color:var(--az1);display:block;margin-bottom:6px;}
    input,select,textarea{width:100%;padding:10px 12px;border:1px solid rgba(0,0,0,.14);border-radius:12px;outline:0;background:#fff;font-size:1em;}
    textarea{min-height:90px;resize:vertical;}
    .mut{color:var(--mut);font-size:.95em}
    .btn{border:0;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:bold;background:var(--az2);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.14);transition:.15s;text-decoration:none;display:inline-flex;align-items:center;gap:10px;}
    .btn:hover{background:#0066cc;transform:translateY(-1px);}
    .btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08);}
    .btn.alt:hover{background:#dde7f7;transform:translateY(-1px);}
    .error{background:#fff3f3;border:1px solid rgba(180,0,0,0.18);color:#7a0000;padding:12px 14px;border-radius:12px;margin:0 0 12px;}
    .ok{background:#f2fff6;border:1px solid rgba(0,128,64,0.18);color:#0a5a2b;padding:12px 14px;border-radius:12px;margin:0 0 12px;}
    .rowbtn{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .hidden{position:absolute;left:-9999px;top:-9999px;height:1px;width:1px;overflow:hidden;}
    @media (max-width: 820px){.grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<header>
  <div class="head">
    <div>
      <h1>Alta de colaborador</h1>
      <div class="mut" style="color:rgba(255,255,255,.9);">Registro público · queda pendiente de aprobación</div>
    </div>
    <div class="nav">
      <a href="/index.html">Inicio</a>
      <a href="/colaboradores.html">Colaboradores</a>
      <a href="/admin/login.php">Login</a>
    </div>
  </div>
</header>

<main class="wrap">
  <div class="card">
    <h2 class="title">Formulario de postulación</h2>

    <?php if ($ok): ?>
      <div class="ok">
        <strong>¡Listo!</strong> Tu solicitud quedó registrada como <strong>PENDIENTE</strong>.  
        Cuando sea aprobada, vas a poder ingresar por <a href="/admin/login.php">/admin/login.php</a>.
      </div>
      <div class="rowbtn">
        <a class="btn alt" href="/colaboradores.html">← Volver</a>
        <a class="btn" href="/admin/login.php">🔐 Ir a login</a>
      </div>
    <?php else: ?>
      <?php if ($error !== ''): ?>
        <div class="error"><strong>Error:</strong> <?=h($error)?></div>
      <?php endif; ?>

      <form method="post" action="">
        <!-- honeypot -->
        <div class="hidden">
          <label>Website</label>
          <input type="text" name="website" value="" autocomplete="off"/>
        </div>

        <div class="grid">
          <div>
            <label>Nombre y apellido</label>
            <input name="nombre" required value="<?=h($posted['nombre'])?>" placeholder="Ej: Juan Pérez"/>
          </div>
          <div>
            <label>Email</label>
            <input name="email" type="email" required value="<?=h($posted['email'])?>" placeholder="ej@correo.com"/>
          </div>

          <div>
            <label>Teléfono (opcional)</label>
            <input name="telefono" value="<?=h($posted['telefono'])?>" placeholder="Ej: +54 9 11 ..."/>
          </div>
          <div>
            <label>Nivel sugerido</label>
            <select name="nivel">
              <option value="local" <?=($posted['nivel']==='local'?'selected':'')?>>Local (ciudad)</option>
              <option value="provincial" <?=($posted['nivel']==='provincial'?'selected':'')?>>Provincial</option>
              <option value="nacional" <?=($posted['nivel']==='nacional'?'selected':'')?>>Nacional</option>
            </select>
          </div>

          <div>
            <label>Provincia</label>
            <input name="provincia" required value="<?=h($posted['provincia'])?>" placeholder="Ej: Buenos Aires"/>
          </div>
          <div>
            <label>Localidad / Municipio</label>
            <input name="localidad" required value="<?=h($posted['localidad'])?>" placeholder="Ej: Ituzaingó"/>
          </div>

          <div>
            <label>Usuario (4–24)</label>
            <input name="usuario" required value="<?=h($posted['usuario'])?>" placeholder="Ej: jperez (solo a-z 0-9 . _ -)"/>
            <div class="mut">Permitido: letras/números y . _ -</div>
          </div>
          <div></div>

          <div>
            <label>Contraseña (mín 8)</label>
            <input name="password" type="password" required placeholder="********"/>
          </div>
          <div>
            <label>Repetir contraseña</label>
            <input name="password2" type="password" required placeholder="********"/>
          </div>

          <div style="grid-column:1/-1;">
            <label>Mensaje (opcional)</label>
            <textarea name="mensaje" placeholder="Breve referencia: por qué querés colaborar / experiencia / fuentes disponibles..."><?=h($posted['mensaje'])?></textarea>
          </div>
        </div>

        <div class="rowbtn">
          <button class="btn" type="submit">🧾 Enviar postulación</button>
          <a class="btn alt" href="/colaboradores.html">Cancelar</a>
        </div>

        <p class="mut" style="margin-top:12px;">
          La solicitud se guarda como <strong>pendiente</strong>. La publicación final es responsabilidad del administrador.
        </p>
      </form>
    <?php endif; ?>
  </div>
</main>
</body>
</html>