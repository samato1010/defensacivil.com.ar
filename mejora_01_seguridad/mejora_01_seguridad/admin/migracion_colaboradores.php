<?php
/**
 * Script de migración: Separar colaboradores.json en público y privado
 * 
 * INSTRUCCIONES:
 * 1. Subir este archivo a /public_html/admin/
 * 2. Ejecutar desde navegador: https://tudominio.com.ar/admin/migracion_colaboradores.php
 * 3. Una vez completado exitosamente, BORRAR este archivo por seguridad
 * 
 * ¿Qué hace?
 * - Lee /data/colaboradores.json (actual)
 * - Separa en dos archivos:
 *   - /data/colaboradores_public.json (solo colaboradores aprobados, SIN credenciales)
 *   - /admin/_storage/colaboradores_private.json (todos los items CON credenciales)
 * - Crea backup del archivo original
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('America/Argentina/Buenos_Aires');

$SITE_ROOT = dirname(__DIR__);
$DATA_DIR = $SITE_ROOT . '/data';
$STORAGE_DIR = __DIR__ . '/_storage';
$BACKUP_DIR = __DIR__ . '/_backup';

$OLD_FILE = $DATA_DIR . '/colaboradores.json';
$PUBLIC_FILE = $DATA_DIR . '/colaboradores_public.json';
$PRIVATE_FILE = $STORAGE_DIR . '/colaboradores_private.json';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

function read_json(string $path): ?array {
  if (!file_exists($path)) return null;
  $raw = @file_get_contents($path);
  if ($raw === false) return null;
  
  if (substr($raw, 0, 3) === "\xEF\xBB\xBF") $raw = substr($raw, 3);
  
  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function write_json(string $path, array $data): bool {
  $dir = dirname($path);
  ensure_dir($dir);
  
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;
  
  return (@file_put_contents($path, $json, LOCK_EX) !== false);
}

$log = [];
$errors = [];

// Paso 1: Leer archivo original
$log[] = "🔍 Leyendo archivo original: $OLD_FILE";
$original = read_json($OLD_FILE);

if ($original === null) {
  $errors[] = "❌ No se pudo leer $OLD_FILE. Verificá que exista.";
} else {
  $log[] = "✅ Archivo original leído correctamente";
  
  // Paso 2: Crear backup
  ensure_dir($BACKUP_DIR);
  $backupName = 'colaboradores_' . date('Ymd_His') . '_backup.json';
  $backupPath = $BACKUP_DIR . '/' . $backupName;
  
  if (write_json($backupPath, $original)) {
    $log[] = "✅ Backup creado: $backupPath";
  } else {
    $errors[] = "⚠️ No se pudo crear backup (no crítico, continúa...)";
  }
  
  // Paso 3: Separar en público y privado
  $meta = $original['meta'] ?? [
    'titulo' => 'Defensa Civil Argentina',
    'subtitulo' => 'Red de colaboradores',
    'updated' => date('Y-m-d H:i:s'),
  ];
  
  $items = $original['items'] ?? [];
  $collaborators_old = $original['collaborators'] ?? [];
  
  // Crear archivo PRIVADO (todos los items con credenciales)
  $private_data = [
    'meta' => [
      'titulo' => 'Colaboradores (datos privados)',
      'updated' => date('Y-m-d H:i:s'),
      'migrated_from' => 'colaboradores.json',
      'migration_date' => date('Y-m-d H:i:s'),
    ],
    'items' => $items, // todos los items con credenciales
  ];
  
  // Crear archivo PÚBLICO (solo aprobados, sin credenciales)
  $public_collaborators = [];
  
  // Primero, los colaboradores del array 'collaborators' (si existen)
  foreach ($collaborators_old as $c) {
    if (!is_array($c)) continue;
    $public_collaborators[] = [
      'id' => (string)($c['id'] ?? ''),
      'nombre' => (string)($c['nombre'] ?? ''),
      'email' => (string)($c['email'] ?? ''),
      'telefono' => (string)($c['telefono'] ?? ''),
      'provincia' => (string)($c['provincia'] ?? ''),
      'localidad' => (string)($c['localidad'] ?? ''),
      'municipio' => (string)($c['municipio'] ?? ''),
      'nivel' => (string)($c['nivel'] ?? 'local'),
      'rol' => (string)($c['rol'] ?? 'Colaborador'),
      'estado' => (string)($c['estado'] ?? 'Activo'),
    ];
  }
  
  // Luego, agregar items APROBADOS del array 'items'
  foreach ($items as $item) {
    if (!is_array($item)) continue;
    
    $status = strtolower(trim((string)($item['status'] ?? 'pendiente')));
    
    if ($status === 'aprobado') {
      $public_collaborators[] = [
        'id' => (string)($item['id'] ?? ''),
        'nombre' => (string)($item['nombre'] ?? ''),
        'email' => (string)($item['email'] ?? ''),
        'telefono' => (string)($item['telefono'] ?? ''),
        'provincia' => (string)($item['provincia'] ?? ''),
        'localidad' => (string)($item['localidad'] ?? ''),
        'nivel' => (string)($item['nivel_sugerido'] ?? 'local'),
        'rol' => 'Colaborador',
        'estado' => 'Activo',
      ];
    }
  }
  
  $public_data = [
    'meta' => [
      'titulo' => $meta['titulo'] ?? 'Defensa Civil Argentina',
      'subtitulo' => $meta['subtitulo'] ?? 'Red de colaboradores',
      'updated' => date('Y-m-d H:i:s'),
    ],
    'collaborators' => $public_collaborators,
  ];
  
  // Paso 4: Escribir archivos
  $log[] = "📝 Creando archivo privado...";
  if (write_json($PRIVATE_FILE, $private_data)) {
    $log[] = "✅ Archivo privado creado: $PRIVATE_FILE";
    $log[] = "   - Items totales: " . count($items);
  } else {
    $errors[] = "❌ No se pudo crear archivo privado: $PRIVATE_FILE";
  }
  
  $log[] = "📝 Creando archivo público...";
  if (write_json($PUBLIC_FILE, $public_data)) {
    $log[] = "✅ Archivo público creado: $PUBLIC_FILE";
    $log[] = "   - Colaboradores visibles: " . count($public_collaborators);
  } else {
    $errors[] = "❌ No se pudo crear archivo público: $PUBLIC_FILE";
  }
  
  // Paso 5: Crear .htaccess para proteger _storage
  $htaccess_content = "# Protección del directorio _storage\n";
  $htaccess_content .= "# Este archivo NIEGA acceso web a todos los archivos en esta carpeta\n\n";
  $htaccess_content .= "Order deny,allow\n";
  $htaccess_content .= "Deny from all\n";
  
  $htaccess_path = $STORAGE_DIR . '/.htaccess';
  if (file_put_contents($htaccess_path, $htaccess_content) !== false) {
    $log[] = "✅ .htaccess creado en $STORAGE_DIR";
  } else {
    $errors[] = "⚠️ No se pudo crear .htaccess en $STORAGE_DIR (crear manualmente)";
  }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Migración Colaboradores - Defensa Civil</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Courier New', monospace;
      background: #1e1e1e;
      color: #d4d4d4;
      padding: 2rem;
      line-height: 1.6;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
      background: #252526;
      border: 1px solid #3e3e42;
      border-radius: 8px;
      padding: 2rem;
      box-shadow: 0 4px 16px rgba(0,0,0,0.4);
    }
    h1 {
      color: #4ec9b0;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    .log {
      background: #1e1e1e;
      border: 1px solid #3e3e42;
      border-radius: 4px;
      padding: 1rem;
      margin: 1rem 0;
      font-size: 0.9rem;
    }
    .log-item {
      margin: 0.5rem 0;
      padding-left: 1.5rem;
    }
    .success { color: #4ec9b0; }
    .error { color: #f48771; }
    .warning { color: #dcdcaa; }
    .info { color: #569cd6; }
    .section {
      margin: 1.5rem 0;
      padding: 1rem;
      background: #2d2d30;
      border-left: 4px solid #007acc;
      border-radius: 4px;
    }
    .section h2 {
      color: #569cd6;
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }
    .code {
      background: #1e1e1e;
      padding: 0.75rem;
      border-radius: 4px;
      margin: 0.5rem 0;
      overflow-x: auto;
      font-size: 0.85rem;
    }
    .btn {
      display: inline-block;
      background: #007acc;
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 4px;
      text-decoration: none;
      font-weight: bold;
      margin-top: 1rem;
      transition: 0.2s;
    }
    .btn:hover {
      background: #005a9e;
    }
    .btn-danger {
      background: #e51400;
    }
    .btn-danger:hover {
      background: #c41200;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🔐 Migración de Colaboradores</h1>
    <p style="margin-bottom: 1rem; color: #9cdcfe;">
      Separación de datos públicos y privados para mejorar la seguridad
    </p>
    
    <div class="log">
      <?php foreach ($log as $item): ?>
        <div class="log-item <?php 
          if (strpos($item, '✅') !== false) echo 'success';
          elseif (strpos($item, '⚠️') !== false) echo 'warning';
          elseif (strpos($item, '🔍') !== false || strpos($item, '📝') !== false) echo 'info';
        ?>"><?php echo htmlspecialchars($item); ?></div>
      <?php endforeach; ?>
      
      <?php foreach ($errors as $error): ?>
        <div class="log-item error"><?php echo htmlspecialchars($error); ?></div>
      <?php endforeach; ?>
    </div>
    
    <?php if (empty($errors)): ?>
      <div class="section success">
        <h2>✅ Migración Exitosa</h2>
        <p>Los archivos se separaron correctamente:</p>
        <div class="code">
          <div>📁 <?php echo $PUBLIC_FILE; ?></div>
          <div style="color: #6a9955;">// Colaboradores visibles (sin credenciales)</div>
          <br>
          <div>📁 <?php echo $PRIVATE_FILE; ?></div>
          <div style="color: #6a9955;">// Items completos (con credenciales, protegido)</div>
        </div>
      </div>
      
      <div class="section">
        <h2>📋 Próximos Pasos</h2>
        <ol style="margin-left: 1.5rem; color: #d4d4d4;">
          <li>Reemplazar <code>/admin/config.php</code> con la nueva versión</li>
          <li>Reemplazar <code>/admin/login.php</code> con la nueva versión</li>
          <li>Reemplazar <code>/colaboradores/alta.php</code> con la nueva versión</li>
          <li>Probar que el login funcione correctamente</li>
          <li><strong>BORRAR este archivo (migracion_colaboradores.php) por seguridad</strong></li>
          <li>Opcional: renombrar o mover /data/colaboradores.json como backup adicional</li>
        </ol>
      </div>
      
      <div class="section warning">
        <h2>⚠️ Importante</h2>
        <p><strong>BORRAR este archivo</strong> una vez completada la migración:</p>
        <div class="code">
          rm /public_html/admin/migracion_colaboradores.php
        </div>
      </div>
      
    <?php else: ?>
      <div class="section error">
        <h2>❌ Hubo Errores</h2>
        <p>Revisá los errores arriba y corregí los problemas antes de continuar.</p>
      </div>
    <?php endif; ?>
    
    <a href="/admin/index.php" class="btn">← Volver al Admin</a>
  </div>
</body>
</html>
