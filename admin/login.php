<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

// Si ya logueado, ir al dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    if (do_login($user, $pass)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Login Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:linear-gradient(135deg,#003366,#004080);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);padding:40px;width:100%;max-width:400px}
h1{color:#003366;text-align:center;margin-bottom:30px}
.error{background:#ffe6e6;border:1px solid #ff9999;color:#cc0000;padding:12px;border-radius:8px;margin-bottom:20px}
label{display:block;font-weight:700;color:#003366;margin:12px 0 6px;font-size:0.95em}
input{width:100%;padding:12px;border:2px solid #e5e7eb;border-radius:8px;font-size:1em;margin-bottom:16px}
input:focus{outline:none;border-color:#004080;box-shadow:0 0 0 3px rgba(0,64,128,.1)}
button{width:100%;padding:14px;background:#004080;color:#fff;border:none;border-radius:8px;font-size:1.05em;font-weight:700;cursor:pointer;transition:.2s}
button:hover{background:#0066cc;transform:translateY(-2px)}
.footer{text-align:center;margin-top:20px;color:#666;font-size:0.85em}
.footer a{color:#004080;text-decoration:none}
</style>
</head>
<body>
<div class="box">
<h1>🔐 Admin</h1>
<?php if($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post">
<label>Usuario</label>
<input type="text" name="username" required autofocus/>
<label>Contraseña</label>
<input type="password" name="password" required/>
<button type="submit">Ingresar</button>
</form>
<div class="footer"><a href="/">← Volver al sitio</a></div>
</div>
</body>
</html>
