<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_login();

$user = current_username();
$role = current_role();
$isAdmin = is_admin();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Admin · DefensaCivil</title>
<link rel="stylesheet" href="/assets/css/admin.css"/>
</head>
<body>

<header>
<div class="head">
<div>
<h1>🛠️ Panel Admin</h1>
<div class="sub">Defensa Civil Argentina</div>
</div>
<div class="nav">
<a href="/">🏠 Sitio</a>
<a href="logout.php">🚪 Salir</a>
</div>
</div>
</header>

<div class="wrap">

<div class="card" style="background:linear-gradient(135deg,#eaf1ff,#f8f9fb);border:1px solid rgba(0,51,102,0.1);padding:24px;margin:20px 0">
<h2 style="border:none;padding:0;margin:0 0 10px">👋 Hola, <?= htmlspecialchars($user) ?></h2>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
<span class="badge">👤 <?= htmlspecialchars(ucfirst($role)) ?></span>
<?php if($isAdmin): ?>
<span class="badge orange">⭐ ADMIN</span>
<?php endif; ?>
</div>
</div>

<h2 style="margin:30px 0 16px;color:#003366">📝 Editores</h2>

<div class="grid">

<?php if(user_can('directorio')): ?>
<div class="card">
<h3>📒 Directorio</h3>
<p>Gestionar contactos de organismos.</p>
<a class="btn" href="directorio_editor.php" style="margin-top:16px">Abrir Editor</a>
</div>
<?php endif; ?>

<?php if(user_can('historia')): ?>
<div class="card">
<h3>📌 Historia</h3>
<p>Eventos históricos y normativa.</p>
<a class="btn" href="normativa_editor.php" style="margin-top:16px">Abrir Editor</a>
</div>
<?php endif; ?>

<?php if(user_can('notas')): ?>
<div class="card">
<h3>📝 Notas</h3>
<p>Publicar notas y eventos.</p>
<a class="btn" href="notas_editor.php" style="margin-top:16px">Abrir Editor</a>
</div>
<?php endif; ?>

<?php if($isAdmin): ?>
<div class="card">
<h3>👥 Colaboradores</h3>
<p>Gestionar solicitudes y permisos.</p>
<a class="btn" href="colaboradores_admin.php" style="margin-top:16px">Gestionar</a>
</div>
<?php endif; ?>

</div>

</div>

<footer>
<p>DefensaCivil.com.ar © 2026</p>
</footer>

</body>
</html>
