<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$user = current_username();
$role = current_role();
$perms = current_perms();
$isAdmin = is_admin();

function badge(string $txt, string $color = ''): string {
  $t = htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
  $class = 'badge' . ($color ? ' ' . $color : '');
  return '<span class="' . $class . '">' . $t . '</span>';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Panel Admin · DefensaCivil</title>
  
  <!-- CSS Admin -->
  <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0"/>
  
  <style>
    .welcome-banner {
      background: linear-gradient(135deg, #eaf1ff, #f8f9fb);
      border: 1px solid rgba(0,51,102,0.1);
      border-radius: var(--r);
      padding: 24px;
      margin: 20px 0;
    }
    
    .welcome-banner h2 {
      margin: 0 0 10px;
      color: var(--az1);
      border: none;
      padding: 0;
      font-size: 1.5em;
    }
    
    .quick-stats {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin: 20px 0;
    }
    
    .quick-stat {
      background: var(--card);
      border: 1px solid rgba(0,0,0,.08);
      border-radius: var(--r);
      padding: 16px;
      flex: 1;
      min-width: 180px;
      text-align: center;
    }
    
    .quick-stat-value {
      font-size: 2.2em;
      font-weight: 900;
      color: var(--az1);
      margin: 0;
    }
    
    .quick-stat-label {
      font-size: 0.85em;
      color: var(--mut);
      margin: 6px 0 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .editor-card {
      position: relative;
      overflow: hidden;
    }
    
    .editor-card::after {
      content: '';
      position: absolute;
      top: 0;
      right: 0;
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, transparent 50%, rgba(255,102,0,0.08) 50%);
      pointer-events: none;
    }
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>🛠️ Panel Admin</h1>
      <div class="sub">Defensa Civil Argentina · Gestión del sitio</div>
    </div>
    <div class="nav">
      <a href="/index.html">🏠 Ver sitio</a>
      <a href="/admin/estado.php">📊 Estado</a>
      <a href="/admin/logout.php">🚪 Salir</a>
    </div>
  </div>
</header>

<div class="wrap">

  <!-- Bienvenida -->
  <div class="welcome-banner">
    <h2>👋 Hola, <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?></h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
      <?= badge("👤 " . ucfirst($role)) ?>
      <?php if ($isAdmin): ?>
        <?= badge("⭐ ADMIN", "orange") ?>
      <?php endif; ?>
      <?php if (!empty($perms)): ?>
        <?php foreach ($perms as $perm => $val): ?>
          <?php if ($val): ?>
            <?= badge("✓ " . ucfirst($perm), "green") ?>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats rápidas -->
  <div class="quick-stats">
    <div class="quick-stat">
      <div class="quick-stat-value">📒</div>
      <div class="quick-stat-label">Directorio</div>
    </div>
    <div class="quick-stat">
      <div class="quick-stat-value">📌</div>
      <div class="quick-stat-label">Historia</div>
    </div>
    <div class="quick-stat">
      <div class="quick-stat-value">📝</div>
      <div class="quick-stat-label">Notas</div>
    </div>
    <div class="quick-stat">
      <div class="quick-stat-value">⚖️</div>
      <div class="quick-stat-label">Normativa</div>
    </div>
  </div>

  <!-- Editores disponibles -->
  <h2 style="margin:30px 0 16px;color:var(--az1);font-size:1.3em;">
    📝 Editores Disponibles
  </h2>

  <div class="grid">
    
    <!-- Directorio -->
    <?php if (user_can('directorio')): ?>
    <div class="card editor-card">
      <h3>📒 Directorio</h3>
      <p>Gestionar contactos de organismos de Defensa Civil de todo el país.</p>
      <div style="margin-top:16px;">
        <a class="btn" href="/admin/directorio_editor.php">
          ✏️ Abrir Editor
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Historia -->
    <?php if (user_can('historia')): ?>
    <div class="card editor-card">
      <h3>📌 Historia</h3>
      <p>Editar eventos históricos y evolución institucional del sistema.</p>
      <div style="margin-top:16px;">
        <a class="btn" href="/admin/normativa_editor.php">
          ✏️ Abrir Editor
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Notas -->
    <?php if (user_can('notas')): ?>
    <div class="card editor-card">
      <h3>📝 Notas</h3>
      <p>Publicar notas, eventos, capacitaciones y actualizaciones del sector.</p>
      <div style="margin-top:16px;">
        <a class="btn" href="/admin/notas_editor.php">
          ✏️ Abrir Editor
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Colaboradores (solo admin) -->
    <?php if ($isAdmin): ?>
    <div class="card editor-card">
      <h3>🤝 Colaboradores</h3>
      <p>Gestionar solicitudes, aprobar usuarios y asignar permisos.</p>
      <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn" href="/admin/colaboradores_admin.php">
          👥 Gestionar
        </a>
        <a class="btn alt" href="/admin/colaboradores_editor.php">
          📋 Contenido
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Herramientas admin (solo para admins) -->
  <?php if ($isAdmin): ?>
  <h2 style="margin:40px 0 16px;color:var(--az1);font-size:1.3em;">
    🔧 Herramientas de Administración
  </h2>

  <div class="grid">
    
    <div class="card">
      <h3>🗺️ Mapa del Sitio</h3>
      <p>Verificar rutas, archivos JSON, permisos y estado general del servidor.</p>
      <a class="btn alt" href="/admin/mapa.php">Ver Mapa</a>
    </div>

    <div class="card">
      <h3>📊 Estado del Sistema</h3>
      <p>Información de PHP, configuración del servidor y diagnósticos.</p>
      <a class="btn alt" href="/admin/estado.php">Ver Estado</a>
    </div>

    <div class="card">
      <h3>👥 Gestión de Usuarios</h3>
      <p>Administrar cuentas de colaboradores y permisos del sistema.</p>
      <a class="btn alt" href="/admin/usuarios.php">Gestionar</a>
    </div>

  </div>
  <?php endif; ?>

  <!-- Ayuda rápida -->
  <div class="card" style="margin-top:40px;background:#f0f7ff;">
    <h3>💡 Ayuda Rápida</h3>
    <ul style="margin:12px 0;padding-left:22px;line-height:2;">
      <li><strong>Directorio:</strong> Agregar, editar y eliminar contactos de organismos.</li>
      <li><strong>Historia:</strong> Mantener actualizada la línea de tiempo histórica.</li>
      <li><strong>Notas:</strong> Publicar eventos, capacitaciones y noticias del sector.</li>
      <?php if ($isAdmin): ?>
      <li><strong>Colaboradores:</strong> Aprobar solicitudes y asignar roles y permisos.</li>
      <?php endif; ?>
    </ul>
    
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(0,51,102,0.1);">
      <strong>¿Necesitás ayuda?</strong><br>
      Contactá al administrador: <a href="mailto:samato@hst.com.ar" style="color:var(--az1);font-weight:700;">samato@hst.com.ar</a>
    </div>
  </div>

</div>

<footer>
  <p>DefensaCivil.com.ar © 2026 · Panel de Administración</p>
  <p style="margin-top:8px;opacity:0.8;font-size:0.9em;">
    Usuario: <?= htmlspecialchars($user) ?> · 
    Rol: <?= htmlspecialchars($role) ?>
  </p>
</footer>

</body>
</html>
