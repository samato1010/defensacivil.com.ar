<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

require_admin();
set_security_headers();

// Paths
$PRIVATE_JSON = COLAB_PRIVATE_JSON ?? __DIR__ . '/_storage/colaboradores_private.json';
$PUBLIC_JSON = COLAB_PUBLIC_JSON ?? __DIR__ . '/../data/colaboradores_public.json';

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Leer datos
$data = json_read($PRIVATE_JSON, ['meta' => [], 'items' => []]);
$items = $data['items'] ?? [];

// Variables
$flash = '';
$err = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf'] ?? '';
    
    if (!csrf_check($csrf)) {
        $err = 'Token CSRF inválido.';
        log_security_event('csrf_fail', 'CSRF fail en colaboradores_admin');
    } else {
        
        // APROBAR
        if ($action === 'approve') {
            $id = $_POST['id'] ?? '';
            $role = sanitize_text($_POST['role'] ?? 'colaborador');
            
            // Permisos por defecto según rol
            $perms = [];
            if ($role === 'admin') {
                $perms = ['directorio' => 1, 'historia' => 1, 'notas' => 1, 'colaboradores' => 1];
            } else {
                // Permisos individuales
                $perms['directorio'] = isset($_POST['perm_directorio']) ? 1 : 0;
                $perms['historia'] = isset($_POST['perm_historia']) ? 1 : 0;
                $perms['notas'] = isset($_POST['perm_notas']) ? 1 : 0;
                $perms['colaboradores'] = 0; // Solo admin
            }
            
            foreach ($items as $idx => $item) {
                if (($item['id'] ?? '') === $id) {
                    $items[$idx]['status'] = 'aprobado';
                    $items[$idx]['role'] = $role;
                    $items[$idx]['permisos'] = $perms;
                    $items[$idx]['approved_at'] = date('Y-m-d H:i:s');
                    $items[$idx]['approved_by'] = current_username();
                    $items[$idx]['updated_at'] = date('Y-m-d H:i:s');
                    
                    $flash = '✅ Colaborador aprobado correctamente';
                    log_security_event('colaborador_approved', 'Aprobado: ' . ($item['nombre'] ?? $id), [
                        'id' => $id,
                        'role' => $role
                    ]);
                    break;
                }
            }
            
            // Guardar
            $data['items'] = $items;
            $data['meta']['updated'] = date('Y-m-d H:i:s');
            json_write_atomic($PRIVATE_JSON, $data);
            
            // Sincronizar público
            if (function_exists('sync_colaboradores_public')) {
                sync_colaboradores_public();
            }
        }
        
        // RECHAZAR
        elseif ($action === 'reject') {
            $id = $_POST['id'] ?? '';
            $motivo = sanitize_text($_POST['motivo'] ?? 'Sin especificar', 500);
            
            foreach ($items as $idx => $item) {
                if (($item['id'] ?? '') === $id) {
                    $items[$idx]['status'] = 'rechazado';
                    $items[$idx]['motivo_rechazo'] = $motivo;
                    $items[$idx]['rejected_at'] = date('Y-m-d H:i:s');
                    $items[$idx]['rejected_by'] = current_username();
                    $items[$idx]['updated_at'] = date('Y-m-d H:i:s');
                    
                    $flash = '✅ Colaborador rechazado';
                    log_security_event('colaborador_rejected', 'Rechazado: ' . ($item['nombre'] ?? $id));
                    break;
                }
            }
            
            $data['items'] = $items;
            $data['meta']['updated'] = date('Y-m-d H:i:s');
            json_write_atomic($PRIVATE_JSON, $data);
        }
        
        // ELIMINAR
        elseif ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            
            $items = array_filter($items, function($item) use ($id) {
                return ($item['id'] ?? '') !== $id;
            });
            $items = array_values($items);
            
            $data['items'] = $items;
            $data['meta']['updated'] = date('Y-m-d H:i:s');
            json_write_atomic($PRIVATE_JSON, $data);
            
            $flash = '✅ Colaborador eliminado';
            log_security_event('colaborador_deleted', 'Eliminado: ' . $id);
            
            // Sincronizar público
            if (function_exists('sync_colaboradores_public')) {
                sync_colaboradores_public();
            }
        }
    }
}

// Agrupar por estado
$pendientes = array_filter($items, fn($i) => ($i['status'] ?? '') === 'pendiente');
$aprobados = array_filter($items, fn($i) => ($i['status'] ?? '') === 'aprobado');
$rechazados = array_filter($items, fn($i) => ($i['status'] ?? '') === 'rechazado');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Gestión de Colaboradores · Admin</title>
  
  <link rel="stylesheet" href="/assets/css/admin.css?v=1.0.0"/>
  
  <style>
    .tabs-nav {
      display: flex;
      gap: 4px;
      border-bottom: 2px solid rgba(0,0,0,.08);
      margin: 20px 0;
    }
    
    .tab-btn {
      background: transparent;
      border: none;
      padding: 12px 20px;
      font-weight: 700;
      color: var(--mut);
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: .2s;
    }
    
    .tab-btn:hover {
      color: var(--az1);
      background: rgba(0,51,102,.05);
    }
    
    .tab-btn.active {
      color: var(--az1);
      border-bottom-color: var(--n);
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
    
    .colab-item {
      background: var(--card);
      border: 1px solid rgba(0,0,0,.08);
      border-radius: var(--r);
      padding: 18px;
      margin: 12px 0;
    }
    
    .colab-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 12px;
    }
    
    .colab-info {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 10px;
      margin: 12px 0;
      padding: 12px;
      background: #fafbfc;
      border-radius: 8px;
      font-size: 0.9em;
    }
    
    .colab-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 12px;
    }
    
    .perms-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 10px;
      margin: 12px 0;
    }
    
    .perm-check {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px;
      background: #fafbfc;
      border-radius: 6px;
    }
    
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,.5);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }
    
    .modal.active {
      display: flex;
    }
    
    .modal-content {
      background: var(--card);
      border-radius: var(--r);
      padding: 24px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>👥 Gestión de Colaboradores</h1>
      <div class="sub">Aprobar solicitudes y asignar permisos</div>
    </div>
    <div class="nav">
      <a href="/admin/">⬅️ Volver</a>
      <a href="/colaboradores/alta.php" target="_blank">➕ Nueva alta</a>
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

  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-value"><?= count($pendientes) ?></div>
      <div class="stat-label">⏳ Pendientes</div>
    </div>
    <div class="stat">
      <div class="stat-value"><?= count($aprobados) ?></div>
      <div class="stat-label">✅ Aprobados</div>
    </div>
    <div class="stat">
      <div class="stat-value"><?= count($rechazados) ?></div>
      <div class="stat-label">❌ Rechazados</div>
    </div>
    <div class="stat">
      <div class="stat-value"><?= count($items) ?></div>
      <div class="stat-label">📊 Total</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs-nav">
    <button class="tab-btn active" data-tab="pendientes">
      ⏳ Pendientes (<?= count($pendientes) ?>)
    </button>
    <button class="tab-btn" data-tab="aprobados">
      ✅ Aprobados (<?= count($aprobados) ?>)
    </button>
    <button class="tab-btn" data-tab="rechazados">
      ❌ Rechazados (<?= count($rechazados) ?>)
    </button>
  </div>

  <!-- Tab: Pendientes -->
  <div class="tab-content active" id="tab-pendientes">
    <?php if (empty($pendientes)): ?>
      <div class="card text-center" style="padding:40px;">
        <h3 style="border:none;">✨ No hay solicitudes pendientes</h3>
        <p class="text-muted">Todas las solicitudes han sido procesadas.</p>
      </div>
    <?php else: ?>
      <?php foreach ($pendientes as $item): ?>
        <div class="colab-item">
          <div class="colab-header">
            <div>
              <strong style="font-size:1.15em;color:var(--az1);">
                <?= h($item['nombre'] ?? 'Sin nombre') ?>
              </strong>
              <div style="font-size:0.85em;color:var(--mut);margin-top:4px;">
                📅 Solicitado: <?= h($item['created_at'] ?? '') ?>
              </div>
            </div>
            <span class="badge orange">⏳ Pendiente</span>
          </div>
          
          <div class="colab-info">
            <div>
              <strong>Email:</strong><br>
              <?= h($item['email'] ?? '') ?>
            </div>
            <div>
              <strong>Teléfono:</strong><br>
              <?= h($item['telefono'] ?? '-') ?>
            </div>
            <div>
              <strong>Provincia:</strong><br>
              <?= h($item['provincia'] ?? '-') ?>
            </div>
            <div>
              <strong>Localidad:</strong><br>
              <?= h($item['localidad'] ?? '-') ?>
            </div>
            <div>
              <strong>Nivel sugerido:</strong><br>
              <?= h($item['nivel_sugerido'] ?? '-') ?>
            </div>
            <div>
              <strong>Organismo:</strong><br>
              <?= h($item['organismo'] ?? '-') ?>
            </div>
          </div>
          
          <?php if (!empty($item['justificacion'])): ?>
            <div style="background:#f8f9fb;padding:12px;border-radius:8px;margin:10px 0;">
              <strong>Justificación:</strong><br>
              <?= nl2br(h($item['justificacion'] ?? '')) ?>
            </div>
          <?php endif; ?>
          
          <div class="colab-actions">
            <button class="btn success" onclick="openApproveModal('<?= h($item['id'] ?? '') ?>', '<?= h($item['nombre'] ?? '') ?>')">
              ✅ Aprobar
            </button>
            <button class="btn danger" onclick="openRejectModal('<?= h($item['id'] ?? '') ?>', '<?= h($item['nombre'] ?? '') ?>')">
              ❌ Rechazar
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Tab: Aprobados -->
  <div class="tab-content" id="tab-aprobados">
    <?php if (empty($aprobados)): ?>
      <div class="card text-center" style="padding:40px;">
        <p class="text-muted">No hay colaboradores aprobados aún.</p>
      </div>
    <?php else: ?>
      <?php foreach ($aprobados as $item): ?>
        <div class="colab-item">
          <div class="colab-header">
            <div>
              <strong style="font-size:1.15em;color:var(--az1);">
                <?= h($item['nombre'] ?? '') ?>
              </strong>
              <div style="font-size:0.85em;color:var(--mut);margin-top:4px;">
                Usuario: <strong><?= h($item['usuario'] ?? '') ?></strong> · 
                Rol: <strong><?= h($item['role'] ?? 'colaborador') ?></strong>
              </div>
            </div>
            <span class="badge green">✅ Aprobado</span>
          </div>
          
          <div class="colab-info">
            <div>
              <strong>Email:</strong><br>
              <?= h($item['email'] ?? '') ?>
            </div>
            <div>
              <strong>Provincia:</strong><br>
              <?= h($item['provincia'] ?? '-') ?>
            </div>
            <div>
              <strong>Aprobado:</strong><br>
              <?= h($item['approved_at'] ?? '') ?>
            </div>
            <div>
              <strong>Por:</strong><br>
              <?= h($item['approved_by'] ?? '-') ?>
            </div>
          </div>
          
          <?php if (!empty($item['permisos'])): ?>
            <div style="margin:10px 0;">
              <strong>Permisos:</strong>
              <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px;">
                <?php foreach ($item['permisos'] as $perm => $val): ?>
                  <?php if ($val): ?>
                    <span class="badge green">✓ <?= h(ucfirst($perm)) ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="colab-actions">
            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar este colaborador?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= h($item['id'] ?? '') ?>"/>
              <button type="submit" class="btn danger">🗑️ Eliminar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Tab: Rechazados -->
  <div class="tab-content" id="tab-rechazados">
    <?php if (empty($rechazados)): ?>
      <div class="card text-center" style="padding:40px;">
        <p class="text-muted">No hay solicitudes rechazadas.</p>
      </div>
    <?php else: ?>
      <?php foreach ($rechazados as $item): ?>
        <div class="colab-item">
          <div class="colab-header">
            <div>
              <strong style="font-size:1.15em;">
                <?= h($item['nombre'] ?? '') ?>
              </strong>
              <div style="font-size:0.85em;color:var(--mut);margin-top:4px;">
                Rechazado: <?= h($item['rejected_at'] ?? '') ?> · 
                Por: <?= h($item['rejected_by'] ?? '') ?>
              </div>
            </div>
            <span class="badge red">❌ Rechazado</span>
          </div>
          
          <?php if (!empty($item['motivo_rechazo'])): ?>
            <div class="alert warning">
              <strong>Motivo:</strong> <?= h($item['motivo_rechazo']) ?>
            </div>
          <?php endif; ?>
          
          <div class="colab-actions">
            <form method="post" style="display:inline;" onsubmit="return confirm('¿Eliminar permanentemente?');">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= h($item['id'] ?? '') ?>"/>
              <button type="submit" class="btn danger">🗑️ Eliminar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Modal: Aprobar -->
<div class="modal" id="approveModal">
  <div class="modal-content">
    <h2 style="margin-top:0;">✅ Aprobar Colaborador</h2>
    <p id="approveName" style="font-size:1.1em;font-weight:700;color:var(--az1);"></p>
    
    <form method="post" id="approveForm">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <input type="hidden" name="action" value="approve"/>
      <input type="hidden" name="id" id="approveId"/>
      
      <label>Rol</label>
      <select name="role" id="approveRole" onchange="togglePerms()">
        <option value="colaborador">Colaborador</option>
        <option value="admin">Administrador</option>
      </select>
      
      <div id="permsContainer">
        <label style="margin-top:16px;">Permisos</label>
        <div class="perms-grid">
          <label class="perm-check">
            <input type="checkbox" name="perm_directorio" value="1"/>
            <span>📒 Directorio</span>
          </label>
          <label class="perm-check">
            <input type="checkbox" name="perm_historia" value="1"/>
            <input type="checkbox" name="perm_historia" value="1"/>
            <span>📌 Historia</span>
          </label>
          <label class="perm-check">
            <input type="checkbox" name="perm_notas" value="1"/>
            <span>📝 Notas</span>
          </label>
        </div>
      </div>
      
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" class="btn success">✅ Confirmar aprobación</button>
        <button type="button" class="btn alt" onclick="closeModal('approveModal')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Rechazar -->
<div class="modal" id="rejectModal">
  <div class="modal-content">
    <h2 style="margin-top:0;">❌ Rechazar Solicitud</h2>
    <p id="rejectName" style="font-size:1.1em;font-weight:700;"></p>
    
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"/>
      <input type="hidden" name="action" value="reject"/>
      <input type="hidden" name="id" id="rejectId"/>
      
      <label>Motivo del rechazo (opcional)</label>
      <textarea name="motivo" rows="3" placeholder="Ej: Datos incompletos, no cumple requisitos..."></textarea>
      
      <div style="display:flex;gap:10px;margin-top:20px;">
        <button type="submit" class="btn danger">❌ Confirmar rechazo</button>
        <button type="button" class="btn alt" onclick="closeModal('rejectModal')">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<footer>
  <p>DefensaCivil.com.ar © 2026 · Gestión de Colaboradores</p>
</footer>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const tab = btn.dataset.tab;
    
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    btn.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
  });
});

// Modals
function openApproveModal(id, nombre) {
  document.getElementById('approveId').value = id;
  document.getElementById('approveName').textContent = nombre;
  document.getElementById('approveModal').classList.add('active');
}

function openRejectModal(id, nombre) {
  document.getElementById('rejectId').value = id;
  document.getElementById('rejectName').textContent = nombre;
  document.getElementById('rejectModal').classList.add('active');
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('active');
}

function togglePerms() {
  const role = document.getElementById('approveRole').value;
  const permsContainer = document.getElementById('permsContainer');
  
  if (role === 'admin') {
    permsContainer.style.display = 'none';
  } else {
    permsContainer.style.display = 'block';
  }
}

// Cerrar modal al hacer click fuera
document.querySelectorAll('.modal').forEach(modal => {
  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      closeModal(modal.id);
    }
  });
});

// Init
togglePerms();
</script>

</body>
</html>
