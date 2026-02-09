<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

require_role(['admin']);
set_security_headers();

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Path del JSON de historia (ajustar según tu estructura)
$HISTORIA_JSON = __DIR__ . '/../data/historia.json';

// Si no existe, crear estructura básica
if (!file_exists($HISTORIA_JSON)) {
    $estructura = [
        'meta' => [
            'titulo' => 'Historia',
            'updated' => date('Y-m-d H:i:s')
        ],
        'events' => []
    ];
    @file_put_contents($HISTORIA_JSON, json_encode($estructura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Leer datos
$data = json_decode(file_get_contents($HISTORIA_JSON), true) ?: ['meta' => [], 'events' => []];
$events = $data['events'] ?? [];

// Variables
$flash = '';
$err = '';
$editingEvent = null;

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf'] ?? '';
    
    if (!csrf_check($csrf)) {
        $err = 'Token CSRF inválido.';
        log_security_event('csrf_fail', 'CSRF fail en historia_editor');
    } else {
        
        // GUARDAR
        if ($action === 'save') {
            $id = trim($_POST['id'] ?? '');
            $fecha = sanitize_text($_POST['fecha'] ?? '');
            $titulo = sanitize_text($_POST['titulo'] ?? '', 300);
            $descripcion = sanitize_text($_POST['descripcion'] ?? '', 2000);
            $provincia = sanitize_text($_POST['provincia'] ?? '');
            $tipo = sanitize_text($_POST['tipo'] ?? '');
            $link_href = trim($_POST['link_href'] ?? '');
            $link_label = sanitize_text($_POST['link_label'] ?? '');
            
            // Validar
            if (!$fecha || !$titulo) {
                $err = 'Fecha y Título son obligatorios.';
            } else {
                
                $eventData = [
                    'id' => $id ?: 'h-' . uniqid(),
                    'fecha' => $fecha,
                    'titulo' => $titulo,
                    'descripcion' => $descripcion,
                    'provincia' => $provincia,
                    'tipo' => $tipo,
                    'links' => []
                ];
                
                if ($link_href && validate_url($link_href)) {
                    $eventData['links'][] = [
                        'href' => $link_href,
                        'label' => $link_label ?: 'Ver documento'
                    ];
                }
                
                // Actualizar o agregar
                $found = false;
                foreach ($events as $idx => $e) {
                    if (($e['id'] ?? '') === $eventData['id']) {
                        $events[$idx] = $eventData;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $events[] = $eventData;
                }
                
                // Ordenar por fecha desc
                usort($events, function($a, $b) {
                    return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
                });
                
                // Guardar
                $data['events'] = $events;
                $data['meta']['updated'] = date('Y-m-d H:i:s');
                file_put_contents($HISTORIA_JSON, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                $flash = $found ? '✅ Evento actualizado' : '✅ Evento creado';
                log_security_event('historia_saved', 'Evento guardado: ' . $titulo);
            }
        }
        
        // ELIMINAR
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            
            $events = array_filter($events, function($e) use ($id) {
                return ($e['id'] ?? '') !== $id;
            });
            $events = array_values($events);
            
            $data['events'] = $events;
            $data['meta']['updated'] = date('Y-m-d H:i:s');
            file_put_contents($HISTORIA_JSON, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $flash = '✅ Evento eliminado';
            log_security_event('historia_deleted', 'Evento eliminado: ' . $id);
        }
    }
}

// Modo edición
$editId = $_GET['edit'] ?? '';
if ($editId) {
    foreach ($events as $e) {
        if (($e['id'] ?? '') === $editId) {
            $editingEvent = $e;
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
  <title>Editor de Historia · Admin</title>
  
  <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0"/>
  
  <style>
    .event-item {
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
    
    .event-item:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,.10);
    }
    
    .event-date {
      font-weight: 900;
      color: var(--n);
      font-size: 1.1em;
    }
    
    .event-meta {
      font-size: 0.85em;
      color: var(--mut);
      margin: 6px 0;
    }
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>📌 Editor de Historia</h1>
      <div class="sub">Gestionar eventos históricos y legislación</div>
    </div>
    <div class="nav">
      <a href="/admin/">⬅️ Volver</a>
      <a href="/historia.html" target="_blank">👁️ Ver sitio</a>
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
    <h2><?= $editingEvent ? '✏️ Editar Evento' : '➕ Nuevo Evento Histórico' ?></h2>
    
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <input type="hidden" name="action" value="save"/>
      <input type="hidden" name="id" value="<?= h($editingEvent['id'] ?? '') ?>"/>
      
      <div class="grid">
        <div>
          <label>Fecha *</label>
          <input type="text" name="fecha" value="<?= h($editingEvent['fecha'] ?? '') ?>" placeholder="Ej: 1943, 1973-05, 1992-12-30" required/>
          <div class="form-hint">Formato: YYYY o YYYY-MM o YYYY-MM-DD</div>
        </div>
        
        <div>
          <label>Provincia</label>
          <input type="text" name="provincia" value="<?= h($editingEvent['provincia'] ?? '') ?>" placeholder="Nacional, Buenos Aires, etc."/>
        </div>
        
        <div>
          <label>Tipo</label>
          <select name="tipo">
            <option value="">Sin especificar</option>
            <option value="Legislación" <?= ($editingEvent['tipo'] ?? '') === 'Legislación' ? 'selected' : '' ?>>Legislación</option>
            <option value="Evento" <?= ($editingEvent['tipo'] ?? '') === 'Evento' ? 'selected' : '' ?>>Evento</option>
            <option value="Institucional" <?= ($editingEvent['tipo'] ?? '') === 'Institucional' ? 'selected' : '' ?>>Institucional</option>
            <option value="Histórico" <?= ($editingEvent['tipo'] ?? '') === 'Histórico' ? 'selected' : '' ?>>Histórico</option>
          </select>
        </div>
      </div>
      
      <label>Título *</label>
      <input type="text" name="titulo" value="<?= h($editingEvent['titulo'] ?? '') ?>" maxlength="300" required/>
      
      <label>Descripción</label>
      <textarea name="descripcion" rows="6" maxlength="2000"><?= h($editingEvent['descripcion'] ?? '') ?></textarea>
      <div class="form-hint">Máximo 2000 caracteres</div>
      
      <h3 style="margin-top:24px;">🔗 Link al documento (opcional)</h3>
      
      <?php $link = $editingEvent['links'][0] ?? null; ?>
      <div style="background:#fafbfc;padding:14px;border-radius:8px;margin:10px 0;">
        <label>URL del documento</label>
        <input type="url" name="link_href" value="<?= h($link['href'] ?? '') ?>" placeholder="https://... o /normativa/..."/>
        
        <label>Texto del link</label>
        <input type="text" name="link_label" value="<?= h($link['label'] ?? '') ?>" placeholder="Ej: Ver documento completo"/>
      </div>
      
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" class="btn">
          <?= $editingEvent ? '💾 Guardar cambios' : '➕ Crear evento' ?>
        </button>
        <?php if ($editingEvent): ?>
          <a href="?" class="btn alt">❌ Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Lista de eventos -->
  <div class="card" style="margin-top:30px;">
    <h2>📋 Eventos Históricos (<?= count($events) ?>)</h2>
    
    <?php if (empty($events)): ?>
      <p class="text-muted">No hay eventos cargados aún.</p>
    <?php else: ?>
      <?php foreach ($events as $event): ?>
        <div class="event-item">
          <div style="flex:1;">
            <div class="event-date"><?= h($event['fecha'] ?? '') ?></div>
            <strong style="color:var(--az1);font-size:1.05em;">
              <?= h($event['titulo'] ?? 'Sin título') ?>
            </strong>
            <div class="event-meta">
              <?php if ($event['provincia'] ?? ''): ?>
                📍 <?= h($event['provincia']) ?>
              <?php endif; ?>
              <?php if ($event['tipo'] ?? ''): ?>
                · 🏷️ <?= h($event['tipo']) ?>
              <?php endif; ?>
            </div>
            <?php if (!empty($event['descripcion'])): ?>
              <div style="margin-top:8px;font-size:0.95em;color:var(--mut);">
                <?= h(substr($event['descripcion'], 0, 200)) ?><?= strlen($event['descripcion']) > 200 ? '...' : '' ?>
              </div>
            <?php endif; ?>
          </div>
          
          <div style="display:flex;gap:8px;">
            <a href="?edit=<?= urlencode($event['id'] ?? '') ?>" class="btn alt">✏️ Editar</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este evento?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= h($event['id'] ?? '') ?>"/>
              <button type="submit" class="btn danger">🗑️</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<footer>
  <p>DefensaCivil.com.ar © 2026 · Editor de Historia</p>
</footer>

</body>
</html>
