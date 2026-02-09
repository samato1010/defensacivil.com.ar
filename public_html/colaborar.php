<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/config.php';

function valid_username(string $u): bool {
  return (bool)preg_match('/^[a-zA-Z0-9_]{3,24}$/', $u);
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim((string)($_POST['username'] ?? ''));
  $pass1    = (string)($_POST['pass1'] ?? '');
  $pass2    = (string)($_POST['pass2'] ?? '');

  $nombre   = trim((string)($_POST['nombre'] ?? ''));
  $nivel    = trim((string)($_POST['nivel'] ?? ''));
  $provincia= trim((string)($_POST['provincia'] ?? ''));
  $municipio= trim((string)($_POST['municipio'] ?? ''));
  $localidad= trim((string)($_POST['localidad'] ?? ''));
  $email    = trim((string)($_POST['email'] ?? ''));
  $telefono = trim((string)($_POST['telefono'] ?? ''));
  $nota     = trim((string)($_POST['nota'] ?? ''));

  if (!valid_username($username)) {
    $err = 'Usuario inválido (3-24, letras/números/_).';
  } elseif ($pass1 === '' || strlen($pass1) < 8) {
    $err = 'La contraseña debe tener al menos 8 caracteres.';
  } elseif ($pass1 !== $pass2) {
    $err = 'Las contraseñas no coinciden.';
  } elseif ($nombre === '' || $nivel === '' || $provincia === '') {
    $err = 'Completá al menos Nombre, Nivel y Provincia.';
  } else {
    $db = load_users();
    if (isset($db['users'][$username])) {
      $err = 'Ese usuario ya existe. Elegí otro.';
    } else {
      // crear usuario PENDIENTE
      $db['users'][$username] = [
        'pass_hash' => password_hash($pass1, PASSWORD_BCRYPT, ['cost'=>10]),
        'role' => 'editor',
        'status' => 'pending',
        'created' => date('Y-m-d H:i:s'),
        'profile' => [
          'nombre' => $nombre,
          'nivel' => $nivel,
          'provincia' => $provincia,
          'municipio' => $municipio,
          'localidad' => $localidad,
          'email' => $email,
          'telefono' => $telefono,
          'nota' => $nota,
        ],
      ];

      if (!save_users($db)) {
        $err = 'No se pudo guardar users.json (permisos).';
      } else {
        // agregar ficha pública (sin credenciales)
        $cdb = colabs_read();
        $cdb['collaborators'][] = [
          'id' => 'c-' . date('Ymd-His'),
          'user' => $username,
          'nivel' => $nivel,
          'provincia' => $provincia,
          'municipio' => $municipio,
          'localidad' => $localidad,
          'nombre' => $nombre,
          'rol' => 'Colaborador',
          'email' => $email,
          'telefono' => $telefono,
          'website' => '',
          'estado' => 'Pendiente',
          'publico' => false,
          'nota' => $nota,
        ];
        colabs_write($cdb);

        $msg = 'Solicitud enviada. Cuando el admin te apruebe, vas a poder ingresar al panel.';
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
<title>Sumate como colaborador</title>
<style>
:root{--az1:#003366;--az2:#004080;--n:#ff6600;--bg:#f8f9fa;--b:rgba(0,0,0,.10);--r:14px;}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#222;}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.wrap{max-width:760px;margin:18px auto;padding:0 16px 60px;}
.card{background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:16px;}
h1{margin:0;color:var(--n);text-shadow:0 2px 10px rgba(0,0,0,.25);}
label{display:block;margin:10px 0 6px;font-weight:800;color:var(--az1);}
input,select,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid var(--b);font-size:1em;}
textarea{min-height:90px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:820px){.row{grid-template-columns:1fr;}}
.btn{margin-top:14px;width:100%;padding:12px;border:0;border-radius:999px;background:var(--az2);color:#fff;font-weight:900;cursor:pointer;}
.btn:hover{background:#0066cc;}
.msg{padding:12px;border-radius:12px;margin:12px 0;border:1px solid rgba(0,0,0,.12);background:#fff;}
.msg.ok{border-color:rgba(0,128,64,.2);background:#f2fff6;color:#0a5a2b;}
.msg.err{border-color:rgba(180,0,0,.2);background:#fff3f3;color:#7a0000;}
.small{color:#555;font-size:.95em;margin-top:10px;}
a{color:#0066cc;font-weight:900;text-decoration:none}
a:hover{text-decoration:underline}
</style>
</head>
<body>
<header>
  <div style="max-width:760px;margin:0 auto;">
    <h1>Sumate como colaborador</h1>
    <div class="small" style="color:#fff;opacity:.92;">
      Vas a poder editar Directorio e Historia cuando el admin apruebe tu cuenta.
    </div>
  </div>
</header>

<div class="wrap">
  <div class="card">
    <?php if ($msg): ?><div class="msg ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="row">
        <div>
          <label>Usuario (para login)</label>
          <input name="username" required placeholder="ej: juan_perez" />
        </div>
        <div>
          <label>Nombre y Apellido</label>
          <input name="nombre" required />
        </div>
      </div>

      <div class="row">
        <div>
          <label>Contraseña</label>
          <input type="password" name="pass1" required />
        </div>
        <div>
          <label>Repetir contraseña</label>
          <input type="password" name="pass2" required />
        </div>
      </div>

      <div class="row">
        <div>
          <label>Nivel</label>
          <select name="nivel" required>
            <option value="">Elegir…</option>
            <option>Local</option>
            <option>Provincial</option>
            <option>Nacional</option>
          </select>
        </div>
        <div>
          <label>Provincia</label>
          <input name="provincia" required placeholder="ej: Buenos Aires" />
        </div>
      </div>

      <div class="row">
        <div>
          <label>Municipio</label>
          <input name="municipio" placeholder="opcional" />
        </div>
        <div>
          <label>Localidad</label>
          <input name="localidad" placeholder="opcional" />
        </div>
      </div>

      <div class="row">
        <div>
          <label>E-mail</label>
          <input name="email" type="email" placeholder="opcional" />
        </div>
        <div>
          <label>Teléfono</label>
          <input name="telefono" placeholder="opcional" />
        </div>
      </div>

      <label>Comentario</label>
      <textarea name="nota" placeholder="Qué querés aportar, zona, disponibilidad, etc."></textarea>

      <button class="btn" type="submit">Enviar solicitud</button>

      <div class="small">
        Luego podés entrar desde <a href="/admin/login.php">/admin/login.php</a> cuando estés aprobado.
      </div>
    </form>
  </div>
</div>
</body>
</html>