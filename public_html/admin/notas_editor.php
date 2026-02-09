<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_admin();

define('UPLOAD_DIR', __DIR__ . '/../uploads/notas/');
define('UPLOAD_URL', '/uploads/notas/');

// Crear carpeta si no existe
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Helper functions for notas.json
function notas_read(): array {
    return json_read(NOTAS_JSON, ['meta' => [], 'notes' => []]);
}

function notas_write(array $data): bool {
    $data['meta']['updated'] = date('Y-m-d H:i:s');
    return json_write_atomic(NOTAS_JSON, $data);
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function ne_fix_mojibake(string $s): string {
    if (strpos($s, 'Ã') !== false || strpos($s, 'Â') !== false) {
        if (function_exists('mb_convert_encoding')) {
            return (string)mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
        }
    }
    return $s;
}

function clean_note_array(array $n): array {
    $out = [];
    $out['id'] = ne_fix_mojibake(trim((string)($n['id'] ?? '')));
    $out['fecha'] = ne_fix_mojibake(trim((string)($n['fecha'] ?? '')));
    $out['provincia'] = ne_fix_mojibake(trim((string)($n['provincia'] ?? '')));
    $out['categoria'] = ne_fix_mojibake(trim((string)($n['categoria'] ?? '')));
    $out['titulo'] = ne_fix_mojibake(trim((string)($n['titulo'] ?? '')));
    $out['texto'] = ne_fix_mojibake(trim((string)($n['texto'] ?? '')));
    $out['imagen'] = ne_fix_mojibake(trim((string)($n['imagen'] ?? ''))); // nuevo

    $links = $n['links'] ?? [];
    $cleanLinks = [];
    if (is_array($links)) {
        foreach ($links as $l) {
            if (!is_array($l)) continue;
            $href = ne_fix_mojibake(trim((string)($l['href'] ?? '')));
            $label = ne_fix_mojibake(trim((string)($l['label'] ?? '')));
            if ($href !== '' || $label !== '') {
                $cleanLinks[] = ['href' => $href, 'label' => $label ?: 'Ver'];
            }
        }
    }
    $out['links'] = $cleanLinks;
    return $out;
}

function generate_note_id(string $fecha, array $notes): string {
    $fecha = trim($fecha);
    if (!$fecha) $fecha = date('Y-m-d');
    $prefix = 'n-' . $fecha . '-';
    $max = 0;
    foreach ($notes as $n) {
        $id = (string)($n['id'] ?? '');
        if (strpos($id, $prefix) === 0) {
            $suf = substr($id, strlen($prefix));
            $num = (int)ltrim($suf, '0');
            if ($num > $max) $max = $num;
        }
    }
    $next = $max + 1;
    return $prefix . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

$db = notas_read();
$notes = $db['notes'] ?? [];
if (!is_array($notes)) $notes = [];

$flash = '';
$err = '';
$action = (string)($_POST['action'] ?? '');
$csrf = (string)($_POST['csrf'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($csrf)) {
        $err = 'CSRF inválido.';
    } else {
        if ($action === 'save') {
            $id = trim((string)($_POST['id'] ?? ''));
            $fecha = trim((string)($_POST['fecha'] ?? ''));
            $provincia = trim((string)($_POST['provincia'] ?? ''));
            $categoria = trim((string)($_POST['categoria'] ?? ''));
            $titulo = trim((string)($_POST['titulo'] ?? ''));
            $texto = trim((string)($_POST['texto'] ?? ''));

            $links = [];
            for ($i = 1; $i <= 3; $i++) {
                $href = trim((string)($_POST["link_href_$i"] ?? ''));
                $label = trim((string)($_POST["link_label_$i"] ?? ''));
                if ($href !== '' || $label !== '') {
                    $links[] = [
                        'href'  => $href,
                        'label' => ($label !== '' ? $label : 'Ver'),
                    ];
                }
            }

            if ($fecha === '') $fecha = date('Y-m-d');

            if ($titulo === '') {
                $err = 'El título es obligatorio.';
            } else {
                // Generar ID si es nuevo
                if ($id === '') {
                    $id = generate_note_id($fecha, $notes);
                }

                // Manejo de imagen
                $imagen = $editing['imagen'] ?? ''; // conservar valor existente al editar

                // ¿Eliminar imagen actual?
                if (isset($_POST['eliminar_imagen']) && $_POST['eliminar_imagen'] === '1' && $imagen !== '') {
                    $oldFile = UPLOAD_DIR . $imagen;
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                    $imagen = '';
                }

                // ¿Nueva subida?
                if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['imagen'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    if (!in_array($ext, $allowed)) {
                        $err = 'Formato no permitido (solo jpg, jpeg, png, gif, webp)';
                    } elseif ($file['size'] > 5 * 1024 * 1024) {
                        $err = 'La imagen es demasiado grande (máx 5 MB)';
                    } else {
                        $baseName = $id . '-' . time() . '.' . $ext;
                        $targetPath = UPLOAD_DIR . $baseName;

                        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                            // Si había una anterior, eliminarla
                            if ($imagen !== '') {
                                $old = UPLOAD_DIR . $imagen;
                                if (file_exists($old)) @unlink($old);
                            }
                            $imagen = $baseName;
                            $flash .= ' (imagen subida)';
                        } else {
                            $err = 'Error al guardar la imagen (permisos en la carpeta?)';
                        }
                    }
                }

                $new = [
                    'id'       => $id,
                    'fecha'    => $fecha,
                    'provincia' => $provincia,
                    'categoria' => $categoria,
                    'titulo'   => $titulo,
                    'texto'    => $texto,
                    'links'    => $links,
                    'imagen'   => $imagen,  // ← campo nuevo
                ];

                $new = clean_note_array($new);

                // Upsert
                $found = false;
                foreach ($notes as $k => $n) {
                    if (($n['id'] ?? '') === $id) {
                        $notes[$k] = $new;
                        $found = true;
                        break;
                    }
                }
                if (!$found) $notes[] = $new;

                // Orden descendente por fecha → id
                usort($notes, function($a, $b) {
                    $fa = (string)($a['fecha'] ?? '');
                    $fb = (string)($b['fecha'] ?? '');
                    if ($fa !== $fb) return strcmp($fb, $fa);
                    return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
                });

                $db['notes'] = $notes;

                if (!notas_write($db)) {
                    $err = 'No se pudo guardar /data/notas.json (permisos).';
                } else {
                    $flash = "Guardado: {$id}" . ($flash ?: '');
                }
            }
        }

        if ($action === 'delete') {
            $id = trim((string)($_POST['id'] ?? ''));
            if ($id === '') {
                $err = 'ID vacío.';
            } else {
                // Eliminar imagen asociada si existe
                foreach ($notes as $n) {
                    if (($n['id'] ?? '') === $id && !empty($n['imagen'])) {
                        $file = UPLOAD_DIR . $n['imagen'];
                        if (file_exists($file)) @unlink($file);
                        break;
                    }
                }

                $notes = array_values(array_filter($notes, fn($n) => (string)($n['id'] ?? '') !== $id));
                $db['notes'] = $notes;
                if (!notas_write($db)) {
                    $err = 'No se pudo guardar /data/notas.json.';
                } else {
                    $flash = "Eliminado: {$id}";
                }
            }
        }

        if ($action === 'repair') {
            $clean = [];
            foreach ($notes as $n) {
                if (!is_array($n)) continue;
                $clean[] = clean_note_array($n);
            }
            $db['notes'] = $clean;
            if (!notas_write($db)) {
                $err = 'No se pudo reescribir /data/notas.json.';
            } else {
                $flash = "Reparación aplicada (acentos corregidos si había mojibake).";
            }
            $notes = $clean;
        }
    }
}

// GET: modo new / edit
$mode = (string)($_GET['mode'] ?? '');
$editId = trim((string)($_GET['id'] ?? ''));
$editing = null;

if ($mode === 'edit' && $editId !== '') {
    foreach ($notes as $n) {
        if (($n['id'] ?? '') === $editId) {
            $editing = $n;
            break;
        }
    }
}

if ($mode === 'new') {
    $editing = [
        'id'       => '',
        'fecha'    => date('Y-m-d'),
        'provincia' => '',
        'categoria' => '',
        'titulo'   => '',
        'texto'    => '',
        'links'    => [],
        'imagen'   => '',
    ];
}
?>

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Admin · Notas</title>
<style>
:root{
  --az1:#003366; --az2:#004080; --n:#ff6600;
  --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555;
  --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc;
}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.head{max-width:1100px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
h1{margin:0;font-size:1.35em;color:var(--n);text-shadow:0 2px 10px rgba(0,0,0,.25)}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.wrap{max-width:1100px;margin:18px auto;padding:0 16px 60px;}
.card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px;margin:12px 0;}
.badge{display:inline-flex;align-items:center;font-weight:900;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;white-space:nowrap;}
.small{color:var(--mut);font-size:.92em;}
.btn{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;border:0;border-radius:999px;padding:10px 12px;background:var(--az2);color:#fff;font-weight:900;cursor:pointer;}
.btn:hover{background:#0066cc}
.btn.alt{background:#eaf1ff;color:var(--az1);border:1px solid rgba(0,51,102,.12);}
.btn.alt:hover{background:#dbe8ff}
.btn.danger{background:#c33;}
.btn.danger:hover{background:#b22;}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;}
table{width:100%;border-collapse:collapse;}
th,td{padding:10px;border-bottom:1px solid rgba(0,0,0,.08);vertical-align:top;}
th{color:var(--az1);text-align:left;background:#f6f9ff;}
tr:hover td{background:#fbfdff;}
code{background:#f3f5f7;padding:2px 6px;border-radius:6px;}
label{display:block;margin:10px 0 6px;font-weight:900;color:var(--az1);}
input,textarea{width:100%;padding:12px;border-radius:12px;border:1px solid var(--b);font-size:1em;}
textarea{min-height:110px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:980px){.grid2{grid-template-columns:1fr;}}
hr{border:0;border-top:1px solid rgba(0,0,0,.08);margin:12px 0;}
.msg{padding:10px;border-radius:12px;border:1px solid rgba(0,0,0,.12);background:#fff;margin:10px 0;}
.msg.ok{background:#f2fff6;border-color:rgba(0,128,64,.2);color:#0a5a2b;}
.msg.err{background:#fff3f3;border-color:rgba(180,0,0,.2);color:#7a0000;}

/* UI enhancements */
.card{transition:box-shadow .2s ease}
.card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09)}
html{scroll-behavior:smooth}
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px}
::-webkit-scrollbar-thumb{background:#b0bec5;border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:#90a4ae}
</style>
</head>
<body>
<header>
  <div class="head">
    <div>
      <h1>Notas · Editor (Solo admin)</h1>
      <div class="small" style="color:#fff;opacity:.92;">Archivo: <code>/data/notas.json</code></div>
    </div>
    <div class="nav">
      <a href="/admin/index.php">Panel</a>
      <a href="/notas.html" target="_blank" rel="noopener">Ver público</a>
      <a href="/admin/logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">
  <?php if ($flash): ?><div class="msg ok"><?= h($flash) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>

  <div class="card">
    <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;">
      <div class="badge">📝 Notas (<?= count($notes) ?>)</div>
      <div class="actions">
        <a class="btn" href="/admin/notas_editor.php?mode=new">+ Nueva nota</a>
        <form method="post" style="display:inline-flex;">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <button class="btn alt" type="submit" name="action" value="repair" title="Corrige Ã¡/Ã³/Â… si quedaron mal guardados">Reparar acentos</button>
        </form>
      </div>
    </div>

    <?php if (!count($notes)): ?>
      <p class="small" style="margin:10px 0 0;">Todavía no hay notas.</p>
    <?php else: ?>
      <div style="overflow:auto;margin-top:10px;border:1px solid rgba(0,0,0,.08);border-radius:12px;">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Provincia</th>
              <th>Categoría</th>
              <th>Título</th>
              <th>Links</th>
              <th>Imagen</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($notes as $n): ?>
            <?php
              $id = (string)($n['id'] ?? '');
              $links = $n['links'] ?? [];
              $lc = is_array($links) ? count($links) : 0;
              $hasImg = !empty($n['imagen']);
            ?>
            <tr>
              <td><strong><?= h((string)($n['fecha'] ?? '')) ?></strong><div class="small"><?= h($id) ?></div></td>
              <td><?= h((string)($n['provincia'] ?? '')) ?></td>
              <td><?= h((string)($n['categoria'] ?? '')) ?></td>
              <td>
                <strong><?= h((string)($n['titulo'] ?? '')) ?></strong>
                <div class="small" style="margin-top:6px;max-width:520px;">
                  <?= h(mb_strimwidth((string)($n['texto'] ?? ''), 0, 140, '…', 'UTF-8')) ?>
                </div>
              </td>
              <td><?= $lc ?></td>
              <td><?= $hasImg ? 'Sí' : '—' ?></td>
              <td>
                <div class="actions">
                  <a class="btn alt" href="/admin/notas_editor.php?mode=edit&id=<?= urlencode($id) ?>">Editar</a>
                  <form method="post" onsubmit="return confirm('¿Eliminar esta nota?');">
                    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= h($id) ?>">
                    <button class="btn danger" type="submit" name="action" value="delete">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if (is_array($editing)): ?>
  <div class="card">
    <div class="badge"><?= ($mode === 'edit') ? '✏️ Editar nota' : '➕ Nueva nota' ?></div>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= h((string)($editing['id'] ?? '')) ?>">

      <div class="grid2">
        <div>
          <label>Fecha (YYYY-MM-DD)</label>
          <input name="fecha" value="<?= h((string)($editing['fecha'] ?? date('Y-m-d'))) ?>" required>
        </div>
        <div>
          <label>Provincia</label>
          <input name="provincia" value="<?= h((string)($editing['provincia'] ?? '')) ?>" placeholder="Ej: Buenos Aires / Nacional">
        </div>
      </div>

      <div class="grid2">
        <div>
          <label>Categoría</label>
          <input name="categoria" value="<?= h((string)($editing['categoria'] ?? '')) ?>" placeholder="Ej: Directorio / Normativa / Sitio">
        </div>
        <div>
          <label>Título</label>
          <input name="titulo" value="<?= h((string)($editing['titulo'] ?? '')) ?>" required>
        </div>
      </div>

      <label>Texto</label>
      <textarea name="texto"><?= h((string)($editing['texto'] ?? '')) ?></textarea>

      <hr>
      <div class="badge">🖼️ Imagen principal (opcional)</div>

      <?php if (!empty($editing['imagen'])): ?>
        <div style="margin:12px 0;">
          <img src="<?= h(UPLOAD_URL . $editing['imagen']) ?>" 
               alt="Imagen actual" 
               style="max-width:380px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.15); display:block; margin-bottom:8px;">
          <div class="small" style="margin-bottom:8px;">
            Actual: <?= h($editing['imagen']) ?>
          </div>
          <label style="display:inline-flex; align-items:center; gap:6px; color:#c33; font-weight:normal;">
            <input type="checkbox" name="eliminar_imagen" value="1"> Eliminar esta imagen
          </label>
        </div>
      <?php endif; ?>

      <input type="file" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp">
      <div class="small" style="margin-top:6px; color:#555;">
        Máx. 5 MB • Formatos: jpg, jpeg, png, gif, webp
      </div>

      <hr>
      <div class="badge">🔗 Links (hasta 3)</div>
      <?php
        $links = $editing['links'] ?? [];
        if (!is_array($links)) $links = [];
        $l1 = $links[0] ?? ['href'=>'','label'=>''];
        $l2 = $links[1] ?? ['href'=>'','label'=>''];
        $l3 = $links[2] ?? ['href'=>'','label'=>''];
      ?>
      <div class="grid2">
        <div>
          <label>Link 1 · URL</label>
          <input name="link_href_1" value="<?= h((string)($l1['href'] ?? '')) ?>" placeholder="/directorio.html o https://...">
        </div>
        <div>
          <label>Link 1 · Texto</label>
          <input name="link_label_1" value="<?= h((string)($l1['label'] ?? '')) ?>" placeholder="Ej: Ver Directorio">
        </div>
      </div>
      <div class="grid2">
        <div>
          <label>Link 2 · URL</label>
          <input name="link_href_2" value="<?= h((string)($l2['href'] ?? '')) ?>">
        </div>
        <div>
          <label>Link 2 · Texto</label>
          <input name="link_label_2" value="<?= h((string)($l2['label'] ?? '')) ?>">
        </div>
      </div>
      <div class="grid2">
        <div>
          <label>Link 3 · URL</label>
          <input name="link_href_3" value="<?= h((string)($l3['href'] ?? '')) ?>">
        </div>
        <div>
          <label>Link 3 · Texto</label>
          <input name="link_label_3" value="<?= h((string)($l3['label'] ?? '')) ?>">
        </div>
      </div>

      <div class="actions" style="margin-top:24px;">
        <button class="btn" type="submit">Guardar</button>
        <a class="btn alt" href="/admin/notas_editor.php">Cancelar</a>
      </div>

      <div class="small" style="margin-top:12px;">
        Tip: si dejás el ID vacío (nota nueva), se genera automático tipo <code>n-YYYY-MM-DD-001</code>.
      </div>
    </form>
  </div>
  <?php endif; ?>
</div>
</body>
</html>