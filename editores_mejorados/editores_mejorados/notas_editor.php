<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php'; // Mejora 05

require_role(['admin']);
set_security_headers();

define('UPLOAD_DIR', __DIR__ . '/../uploads/notas/');
define('UPLOAD_URL', '/uploads/notas/');

// Crear carpeta si no existe
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Helper: sanitizar HTML
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Leer BD
$db = notas_read();
$notes = $db['notes'] ?? [];
if (!is_array($notes)) $notes = [];

// Variables de estado
$flash = '';
$err = '';
$editingNote = null;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf'] ?? '';
    
    if (!csrf_check($csrf)) {
        $err = 'Token CSRF inválido. Recargá la página.';
        log_security_event('csrf_fail', 'CSRF fail en notas_editor');
    } else {
        
        // CREAR / EDITAR
        if ($action === 'save') {
            $id = trim($_POST['id'] ?? '');
            $fecha = sanitize_text($_POST['fecha'] ?? '');
            $provincia = sanitize_text($_POST['provincia'] ?? '');
            $categoria = sanitize_text($_POST['categoria'] ?? '');
            $titulo = sanitize_text($_POST['titulo'] ?? '', 200);
            $texto = sanitize_text($_POST['texto'] ?? '', 2000);
            
            // Validar
            if (!$titulo) {
                $err = 'El título es obligatorio.';
            } else {
                
                // Links
                $links = [];
                for ($i = 1; $i <= 3; $i++) {
                    $href = trim($_POST["link_href_$i"] ?? '');
                    $label = trim($_POST["link_label_$i"] ?? '');
                    if ($href && validate_url($href)) {
                        $links[] = [
                            'href' => $href,
                            'label' => $label ?: 'Ver más'
                        ];
                    }
                }
                
                // Imagen (upload)
                $imagen = '';
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $filename = uniqid('nota_') . '.' . $ext;
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], UPLOAD_DIR . $filename)) {
                            $imagen = $filename;
                        }
                    }
                }
                
                $noteData = [
                    'id' => $id ?: 'n-' . date('Y-m-d') . '-' . substr(uniqid(), -3),
                    'fecha' => $fecha ?: date('Y-m-d'),
                    'provincia' => $provincia,
                    'categoria' => $categoria,
                    'titulo' => $titulo,
                    'texto' => $texto,
                    'imagen' => $imagen,
                    'links' => $links
                ];
                
                // Actualizar o agregar
                $found = false;
                foreach ($notes as $idx => $n) {
                    if (($n['id'] ?? '') === $noteData['id']) {
                        // Si hay nueva imagen, mantener la vieja si no se subió nada
                        if (!$imagen && isset($n['imagen'])) {
                            $noteData['imagen'] = $n['imagen'];
                        }
                        $notes[$idx] = $noteData;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $notes[] = $noteData;
                }
                
                // Ordenar por fecha desc
                usort($notes, function($a, $b) {
                    return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
                });
                
                // Guardar
                $db['notes'] = $notes;
                $db['meta']['updated'] = date('Y-m-d H:i:s');
                
                notas_write($db);
                
                $flash = $found ? '✅ Nota actualizada correctamente' : '✅ Nota creada correctamente';
                log_security_event('nota_saved', 'Nota guardada: ' . $titulo);
            }
        }
        
        // ELIMINAR
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            $notes = array_filter($notes, function($n) use ($id) {
                return ($n['id'] ?? '') !== $id;
            });
            $notes = array_values($notes);
            
            $db['notes'] = $notes;
            $db['meta']['updated'] = date('Y-m-d H:i:s');
            notas_write($db);
            
            $flash = '✅ Nota eliminada';
            log_security_event('nota_deleted', 'Nota eliminada: ' . $id);
        }
    }
}

// Modo edición
$editId = $_GET['edit'] ?? '';
if ($editId) {
    foreach ($notes as $n) {
        if (($n['id'] ?? '') === $editId) {
            $editingNote = $n;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Editor de Notas · Admin</title>
  
  <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0"/>
  
  <style>
    .note-preview {
      background: #fafbfc;
      border: 1px solid rgba(0,0,0,.06);
      border-radius: var(--r);
      padding: 14px;
      margin-top: 12px;
    }
    
    .note-item {
      background: var(--card);
      border: 1px solid rgba(0,0,0,.08);
      border-radius: var(--r);
      padding: 16px;
      margin: 10px 0;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
    }
    
    .note-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,.10);
    }
    
    .note-meta {
      font-size: 0.85em;
      color: var(--mut);
      margin: 6px 0;
    }
    
    .note-actions {
      display: flex;
      gap: 8px;
      flex-shrink: 0;
    }
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>📝 Editor de Notas</h1>
      <div class="sub">Publicar eventos, capacitaciones y actualizaciones</div>
    </div>
    <div class="nav">
      <a href="/admin/">⬅️ Volver</a>
      <a href="/notas.html" target="_blank">👁️ Ver sitio</a>
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
    <h2><?= $editingNote ? '✏️ Editar Nota' : '➕ Nueva Nota' ?></h2>
    
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="id" value="<?= h($editingNote['id'] ?? '') ?>"/>
      
      <label>Fecha</label>
      <input type="date" name="fecha" value="<?= h($editingNote['fecha'] ?? date('Y-m-d')) ?>" required/>
      
      <label>Provincia</label>
      <input type="text" name="provincia" value="<?= h($editingNote['provincia'] ?? '') ?>" placeholder="Ej: Buenos Aires"/>
      
      <label>Categoría</label>
      <select name="categoria">
        <option value="">Sin categoría</option>
        <option value="Evento" <?= ($editingNote['categoria'] ?? '') === 'Evento' ? 'selected' : '' ?>>Evento</option>
        <option value="Capacitación" <?= ($editingNote['categoria'] ?? '') === 'Capacitación' ? 'selected' : '' ?>>Capacitación</option>
        <option value="Publicación" <?= ($editingNote['categoria'] ?? '') === 'Publicación' ? 'selected' : '' ?>>Publicación</option>
        <option value="Normativa" <?= ($editingNote['categoria'] ?? '') === 'Normativa' ? 'selected' : '' ?>>Normativa</option>
        <option value="Otro" <?= ($editingNote['categoria'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
      </select>
      
      <label>Título *</label>
      <input type="text" name="titulo" value="<?= h($editingNote['titulo'] ?? '') ?>" maxlength="200" required/>
      
      <label>Texto</label>
      <textarea name="texto" rows="6" maxlength="2000"><?= h($editingNote['texto'] ?? '') ?></textarea>
      <div class="form-hint">Máximo 2000 caracteres</div>
      
      <label>Imagen (opcional)</label>
      <input type="file" name="imagen" accept="image/*"/>
      <?php if (!empty($editingNote['imagen'])): ?>
        <div class="form-hint">
          Actual: <?= h($editingNote['imagen']) ?>
          <a href="<?= h(UPLOAD_URL . $editingNote['imagen']) ?>" target="_blank">Ver</a>
        </div>
      <?php endif; ?>
      
      <h3 style="margin-top:24px;">🔗 Links (opcionales)</h3>
      
      <?php for ($i = 1; $i <= 3; $i++): ?>
        <?php $link = $editingNote['links'][$i-1] ?? null; ?>
        <div style="background:#fafbfc;padding:12px;border-radius:8px;margin:10px 0;">
          <label>Link <?= $i ?> - URL</label>
          <input type="url" name="link_href_<?= $i ?>" value="<?= h($link['href'] ?? '') ?>" placeholder="https://..."/>
          
          <label>Link <?= $i ?> - Texto</label>
          <input type="text" name="link_label_<?= $i ?>" value="<?= h($link['label'] ?? '') ?>" placeholder="Ej: Ver más información"/>
        </div>
      <?php endfor; ?>
      
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" class="btn">
          <?= $editingNote ? '💾 Guardar cambios' : '➕ Crear nota' ?>
        </button>
        <?php if ($editingNote): ?>
          <a href="?" class="btn alt">❌ Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Lista de notas -->
  <div class="card" style="margin-top:30px;">
    <h2>📋 Notas Publicadas (<?= count($notes) ?>)</h2>
    
    <?php if (empty($notes)): ?>
      <p class="text-muted">No hay notas publicadas aún.</p>
    <?php else: ?>
      <?php foreach ($notes as $note): ?>
        <div class="note-item">
          <div style="flex:1;">
            <strong style="color:var(--az1);font-size:1.05em;"><?= h($note['titulo'] ?? 'Sin título') ?></strong>
            <div class="note-meta">
              📅 <?= h($note['fecha'] ?? '') ?>
              <?php if ($note['provincia'] ?? ''): ?>
                · 📍 <?= h($note['provincia']) ?>
              <?php endif; ?>
              <?php if ($note['categoria'] ?? ''): ?>
                · 🏷️ <?= h($note['categoria']) ?>
              <?php endif; ?>
            </div>
          </div>
          
          <div class="note-actions">
            <a href="?edit=<?= urlencode($note['id'] ?? '') ?>" class="btn alt">✏️ Editar</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar esta nota?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= h($note['id'] ?? '') ?>"/>
              <button type="submit" class="btn danger">🗑️</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<footer>
  <p>DefensaCivil.com.ar © 2026 · Editor de Notas</p>
</footer>

</body>
</html>
