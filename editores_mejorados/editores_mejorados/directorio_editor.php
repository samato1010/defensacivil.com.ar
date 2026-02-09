<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

require_role(['admin']);
set_security_headers();

// Leer BD
$db = directorio_read();
$entries = $db['entries'] ?? [];
if (!is_array($entries)) $entries = [];

// Variables
$flash = '';
$err = '';
$editingEntry = null;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf'] ?? '';
    
    if (!csrf_check($csrf)) {
        $err = 'Token CSRF inválido.';
        log_security_event('csrf_fail', 'CSRF fail en directorio_editor');
    } else {
        
        // GUARDAR
        if ($action === 'save') {
            $id = trim($_POST['id'] ?? '');
            $provincia = sanitize_text($_POST['provincia'] ?? '');
            $municipio = sanitize_text($_POST['municipio'] ?? '');
            $localidad = sanitize_text($_POST['localidad'] ?? '');
            $denominacion = sanitize_text($_POST['denominacion'] ?? '', 200);
            $domicilio = sanitize_text($_POST['domicilio'] ?? '', 300);
            $telefono = sanitize_text($_POST['telefono'] ?? '', 100);
            $email = sanitize_email($_POST['email'] ?? '');
            $website = trim($_POST['website'] ?? '');
            $instagram = trim($_POST['instagram'] ?? '');
            $facebook = trim($_POST['facebook'] ?? '');
            $comentario = sanitize_text($_POST['comentario'] ?? '', 500);
            
            // Validar
            if (!$provincia || !$denominacion) {
                $err = 'Provincia y Denominación son obligatorios.';
            } else {
                
                $entryData = [
                    'id' => $id ?: 'd-' . uniqid(),
                    'provincia' => $provincia,
                    'municipio' => $municipio,
                    'localidad' => $localidad,
                    'denominacion' => $denominacion,
                    'domicilio' => $domicilio,
                    'telefono' => $telefono,
                    'email' => $email,
                    'website' => $website && validate_url($website) ? $website : '',
                    'instagram' => $instagram,
                    'facebook' => $facebook,
                    'comentario' => $comentario
                ];
                
                // Actualizar o agregar
                $found = false;
                foreach ($entries as $idx => $e) {
                    if (($e['id'] ?? '') === $entryData['id']) {
                        $entries[$idx] = $entryData;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $entries[] = $entryData;
                }
                
                // Ordenar por provincia, municipio
                usort($entries, function($a, $b) {
                    $cmp = strcmp($a['provincia'] ?? '', $b['provincia'] ?? '');
                    if ($cmp !== 0) return $cmp;
                    return strcmp($a['municipio'] ?? '', $b['municipio'] ?? '');
                });
                
                // Guardar
                $db['entries'] = $entries;
                $db['meta']['updated'] = date('Y-m-d H:i:s');
                directorio_write($db);
                
                $flash = $found ? '✅ Contacto actualizado' : '✅ Contacto agregado';
                log_security_event('directorio_saved', 'Contacto guardado: ' . $denominacion);
            }
        }
        
        // ELIMINAR
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            $entries = array_filter($entries, function($e) use ($id) {
                return ($e['id'] ?? '') !== $id;
            });
            $entries = array_values($entries);
            
            $db['entries'] = $entries;
            $db['meta']['updated'] = date('Y-m-d H:i:s');
            directorio_write($db);
            
            $flash = '✅ Contacto eliminado';
            log_security_event('directorio_deleted', 'Contacto eliminado: ' . $id);
        }
    }
}

// Modo edición
$editId = $_GET['edit'] ?? '';
if ($editId) {
    foreach ($entries as $e) {
        if (($e['id'] ?? '') === $editId) {
            $editingEntry = $e;
            break;
        }
    }
}

// Obtener provincias únicas para el filtro
$provincias = array_unique(array_map(function($e) {
    return $e['provincia'] ?? '';
}, $entries));
sort($provincias);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Editor de Directorio · Admin</title>
  
  <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0"/>
  
  <style>
    .entry-item {
      background: var(--card);
      border: 1px solid rgba(0,0,0,.08);
      border-radius: var(--r);
      padding: 14px;
      margin: 8px 0;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
    }
    
    .entry-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,.10);
    }
    
    .entry-meta {
      font-size: 0.85em;
      color: var(--mut);
      margin: 4px 0;
    }
    
    .filter-bar {
      background: #fafbfc;
      border: 1px solid rgba(0,0,0,.06);
      border-radius: var(--r);
      padding: 14px;
      margin: 14px 0;
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }
    
    .filter-bar input, .filter-bar select {
      flex: 1;
      min-width: 200px;
    }
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>📒 Editor de Directorio</h1>
      <div class="sub">Gestionar contactos de organismos</div>
    </div>
    <div class="nav">
      <a href="/admin/">⬅️ Volver</a>
      <a href="/directorio.html" target="_blank">👁️ Ver sitio</a>
    </div>
  </div>
</header>

<div class="wrap">

  <?php if ($flash): ?>
    <div class="alert success"><?= h($flash) ?></div>
  <?php endif; ?>
  
  <?php if ($err): ?>
    <div class="alert error"><?= h($err) ?></div>
  <?php endif; ?>

  <!-- Formulario -->
  <div class="card">
    <h2><?= $editingEntry ? '✏️ Editar Contacto' : '➕ Nuevo Contacto' ?></h2>
    
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="id" value="<?= h($editingEntry['id'] ?? '') ?>"/>
      
      <div class="grid">
        <div>
          <label>Provincia *</label>
          <input type="text" name="provincia" value="<?= h($editingEntry['provincia'] ?? '') ?>" required list="provincias"/>
          <datalist id="provincias">
            <?php foreach ($provincias as $p): ?>
              <option value="<?= h($p) ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        
        <div>
          <label>Municipio</label>
          <input type="text" name="municipio" value="<?= h($editingEntry['municipio'] ?? '') ?>"/>
        </div>
        
        <div>
          <label>Localidad</label>
          <input type="text" name="localidad" value="<?= h($editingEntry['localidad'] ?? '') ?>"/>
        </div>
      </div>
      
      <label>Denominación *</label>
      <input type="text" name="denominacion" value="<?= h($editingEntry['denominacion'] ?? '') ?>" maxlength="200" required/>
      
      <label>Domicilio</label>
      <input type="text" name="domicilio" value="<?= h($editingEntry['domicilio'] ?? '') ?>" maxlength="300"/>
      
      <div class="grid">
        <div>
          <label>Teléfono</label>
          <input type="tel" name="telefono" value="<?= h($editingEntry['telefono'] ?? '') ?>"/>
        </div>
        
        <div>
          <label>Email</label>
          <input type="email" name="email" value="<?= h($editingEntry['email'] ?? '') ?>"/>
        </div>
      </div>
      
      <label>Sitio Web</label>
      <input type="url" name="website" value="<?= h($editingEntry['website'] ?? '') ?>" placeholder="https://..."/>
      
      <div class="grid">
        <div>
          <label>Instagram</label>
          <input type="url" name="instagram" value="<?= h($editingEntry['instagram'] ?? '') ?>" placeholder="https://instagram.com/..."/>
        </div>
        
        <div>
          <label>Facebook</label>
          <input type="url" name="facebook" value="<?= h($editingEntry['facebook'] ?? '') ?>" placeholder="https://facebook.com/..."/>
        </div>
      </div>
      
      <label>Comentario</label>
      <textarea name="comentario" rows="3" maxlength="500"><?= h($editingEntry['comentario'] ?? '') ?></textarea>
      
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" class="btn">
          <?= $editingEntry ? '💾 Guardar cambios' : '➕ Agregar contacto' ?>
        </button>
        <?php if ($editingEntry): ?>
          <a href="?" class="btn alt">❌ Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Búsqueda y filtro -->
  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="🔍 Buscar por nombre, provincia, municipio..."/>
    <select id="provinciaFilter">
      <option value="">Todas las provincias</option>
      <?php foreach ($provincias as $p): ?>
        <option value="<?= h($p) ?>"><?= h($p) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Lista de contactos -->
  <div class="card">
    <h2>📋 Directorio (<span id="totalCount"><?= count($entries) ?></span> contactos)</h2>
    
    <div id="entriesContainer">
      <?php if (empty($entries)): ?>
        <p class="text-muted">No hay contactos cargados aún.</p>
      <?php else: ?>
        <?php foreach ($entries as $entry): ?>
          <div class="entry-item" data-provincia="<?= h($entry['provincia'] ?? '') ?>" data-search="<?= h(strtolower(json_encode($entry))) ?>">
            <div style="flex:1;">
              <strong style="color:var(--az1);font-size:1.05em;"><?= h($entry['denominacion'] ?? 'Sin nombre') ?></strong>
              <div class="entry-meta">
                📍 <?= h($entry['provincia'] ?? '') ?>
                <?php if ($entry['municipio'] ?? ''): ?>
                  · <?= h($entry['municipio']) ?>
                <?php endif; ?>
                <?php if ($entry['telefono'] ?? ''): ?>
                  · 📞 <?= h($entry['telefono']) ?>
                <?php endif; ?>
                <?php if ($entry['email'] ?? ''): ?>
                  · ✉️ <?= h($entry['email']) ?>
                <?php endif; ?>
              </div>
            </div>
            
            <div style="display:flex;gap:8px;">
              <a href="?edit=<?= urlencode($entry['id'] ?? '') ?>" class="btn alt">✏️ Editar</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este contacto?');">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="id" value="<?= h($entry['id'] ?? '') ?>"/>
                <button type="submit" class="btn danger">🗑️</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<footer>
  <p>DefensaCivil.com.ar © 2026 · Editor de Directorio</p>
</footer>

<script>
// Búsqueda y filtro en tiempo real
const searchInput = document.getElementById('searchInput');
const provinciaFilter = document.getElementById('provinciaFilter');
const entries = document.querySelectorAll('.entry-item');
const totalCount = document.getElementById('totalCount');

function filterEntries() {
  const search = searchInput.value.toLowerCase();
  const provincia = provinciaFilter.value;
  let visible = 0;
  
  entries.forEach(entry => {
    const matchSearch = !search || entry.dataset.search.includes(search);
    const matchProvincia = !provincia || entry.dataset.provincia === provincia;
    
    if (matchSearch && matchProvincia) {
      entry.style.display = '';
      visible++;
    } else {
      entry.style.display = 'none';
    }
  });
  
  totalCount.textContent = visible;
}

searchInput.addEventListener('input', filterEntries);
provinciaFilter.addEventListener('change', filterEntries);
</script>

</body>
</html>
