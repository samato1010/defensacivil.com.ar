<?php
// /admin/normativa_api.php - FINAL + options + filtros + paginado
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// ===== Auth placeholder (integrar tu login real) =====
function require_login(): void {
  // if (empty($_SESSION['logged_in'])) {
  //   http_response_code(401);
  //   echo json_encode(['ok'=>false,'error'=>'No autorizado']);
  //   exit;
  // }
}
require_login();

// ===== Paths =====
$root = realpath(__DIR__ . '/..');
if ($root === false) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'No se pudo resolver root']);
  exit;
}

$jsonFile  = $root . '/normativa/normativa.json';
$backupDir = $root . '/admin/_backup_normativa';

// ===== Helpers =====
function respond($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true)) {
      throw new RuntimeException("No se pudo crear dir: $dir");
    }
  }
}

function _strlen(string $s): int {
  return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}
function _substr(string $s, int $start, int $len): string {
  return function_exists('mb_substr') ? mb_substr($s, $start, $len) : substr($s, $start, $len);
}

function read_json_file(string $path): array {
  if (!file_exists($path)) {
    return [
      'meta' => [
        'version' => 1,
        'updated' => date('Y-m-d H:i:s'),
        'source_note' => 'Creado desde editor'
      ],
      'items' => []
    ];
  }
  $raw = file_get_contents($path);
  if ($raw === false) throw new RuntimeException("No se pudo leer JSON");

  // remover BOM
  $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

  $data = json_decode($raw, true);
  if (!is_array($data)) throw new RuntimeException("JSON inválido");
  if (!isset($data['items']) || !is_array($data['items'])) $data['items'] = [];
  if (!isset($data['meta'])  || !is_array($data['meta']))  $data['meta']  = [];
  return $data;
}

function backup_file(string $src, string $backupDir): void {
  if (!file_exists($src)) return;
  ensure_dir($backupDir);
  $stamp = date('Ymd_His');
  $dst = rtrim($backupDir, '/') . "/normativa_$stamp.json";
  @copy($src, $dst);
}

function write_json_file_atomic(string $path, array $data, string $backupDir): void {
  backup_file($path, $backupDir);

  $data['meta']['updated'] = date('Y-m-d H:i:s');
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  if ($json === false) throw new RuntimeException("No se pudo serializar JSON");

  $tmp = $path . '.tmp';
  if (file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException("No se pudo escribir tmp");

  if (!rename($tmp, $path)) {
    @unlink($tmp);
    throw new RuntimeException("No se pudo reemplazar JSON");
  }
}

function sanitize_str($v, int $max = 20000): string {
  $s = is_string($v) ? $v : '';
  $s = trim($s);
  if (_strlen($s) > $max) $s = _substr($s, 0, $max);
  return $s;
}

function sanitize_upper($v, int $max = 64): string {
  return strtoupper(sanitize_str($v, $max));
}

function normalize_item($in): array {
  $id = sanitize_str($in['id'] ?? '', 64);
  if ($id === '') {
    $id = 'it_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
  }

  $out = [
    'id' => $id,
    'fecha' => sanitize_str($in['fecha'] ?? '', 32),
    'fecha_label' => sanitize_str($in['fecha_label'] ?? '', 64),
    'provincia' => sanitize_upper($in['provincia'] ?? 'NACIONAL', 64),
    'tipo' => sanitize_str($in['tipo'] ?? 'Otro', 64),
    'origen' => sanitize_upper($in['origen'] ?? 'LT', 8),
    'titulo' => sanitize_str($in['titulo'] ?? 'Evento', 256),
    'descripcion' => sanitize_str($in['descripcion'] ?? '', 5000),
    'tags' => [],
    'links' => []
  ];

  $tags = $in['tags'] ?? [];
  if (is_string($tags)) $tags = array_filter(array_map('trim', explode(',', $tags)));
  if (is_array($tags)) {
    $clean = array_map(fn($t) => sanitize_str((string)$t, 64), $tags);
    $clean = array_filter($clean);
    $out['tags'] = array_values(array_unique($clean));
  }

  $links = $in['links'] ?? [];
  if (is_array($links)) {
    $cleanL = [];
    foreach ($links as $l) {
      if (!is_array($l)) continue;
      $href = sanitize_str($l['href'] ?? '', 2048);
      if ($href === '') continue;
      $label = sanitize_str($l['label'] ?? 'Ver', 128);
      $icon = sanitize_str($l['icon'] ?? '📖', 8);
      $cleanL[] = ['href' => $href, 'label' => $label, 'icon' => $icon];
    }
    $out['links'] = $cleanL;
  }

  return $out;
}

function haystack(array $it): string {
  $parts = [];
  foreach (['fecha','fecha_label','provincia','tipo','origen','titulo','descripcion'] as $k) {
    $parts[] = (string)($it[$k] ?? '');
  }
  $tags = $it['tags'] ?? [];
  if (is_array($tags)) $parts[] = implode(' ', $tags);
  return mb_strtolower(implode(' | ', $parts));
}

$action = (string)($_GET['action'] ?? '');

try {
  ensure_dir($backupDir);

  // CSRF
  if ($action === 'csrf') {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    respond(['ok'=>true, 'csrf'=>$_SESSION['csrf_token']]);
  }

  // LIST
  if ($action === 'list') {
    $data = read_json_file($jsonFile);
    $items = $data['items'] ?? [];
    $normItems = array_map('normalize_item', array_filter($items, 'is_array'));

    // options
    $provincias = [];
    $tipos = [];
    $origenes = ['LT','LP','PP'];
    foreach ($normItems as $it) {
      $provincias[] = $it['provincia'] ?? 'NACIONAL';
      $tipos[] = $it['tipo'] ?? 'Otro';
    }
    $provincias = array_values(array_unique(array_filter($provincias)));
    $tipos = array_values(array_unique(array_filter($tipos)));
    sort($provincias);
    sort($tipos);

    // filtros
    $q = mb_strtolower(trim((string)($_GET['q'] ?? '')));
    $prov = strtoupper(trim((string)($_GET['prov'] ?? '')));
    $tipo = trim((string)($_GET['tipo'] ?? ''));
    $origen = strtoupper(trim((string)($_GET['origen'] ?? '')));

    $filtered = array_filter($normItems, function($it) use ($q, $prov, $tipo, $origen) {
      if ($prov !== '' && strtoupper((string)($it['provincia'] ?? '')) !== $prov) return false;
      if ($tipo !== '' && (string)($it['tipo'] ?? '') !== $tipo) return false;
      if ($origen !== '' && strtoupper((string)($it['origen'] ?? '')) !== $origen) return false;
      if ($q !== '') {
        $h = haystack($it);
        if (mb_strpos($h, $q) === false) return false;
      }
      return true;
    });

    $filtered = array_values($filtered);

    // sort
    $sort = (string)($_GET['sort'] ?? 'fecha_asc');
    usort($filtered, function($a,$b) use ($sort){
      $fa = (string)($a['fecha'] ?? '');
      $fb = (string)($b['fecha'] ?? '');
      if ($sort === 'fecha_desc') return strcmp($fb, $fa);
      return strcmp($fa, $fb);
    });

    // paging
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = min(200, max(1, (int)($_GET['per_page'] ?? 50)));
    $total = count($filtered);
    $pages = max(1, (int)ceil($total / $per));
    if ($page > $pages) $page = $pages;
    $offset = ($page - 1) * $per;
    $pageItems = array_slice($filtered, $offset, $per);

    respond([
      'ok'=>true,
      'meta'=>$data['meta'] ?? [],
      'options'=>[
        'provincias'=>$provincias,
        'tipos'=>$tipos,
        'origenes'=>$origenes
      ],
      'items'=>$pageItems,
      'paging'=>['page'=>$page, 'per_page'=>$per, 'total'=>$total, 'pages'=>$pages]
    ]);
  }

  // writes: POST + CSRF
  $isWrite = in_array($action, ['save_item','delete_item','save_meta'], true);
  if ($isWrite) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(['ok'=>false,'error'=>'Método inválido'], 405);
    $want = (string)($_SESSION['csrf_token'] ?? '');
    $hdr  = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($want === '' || $hdr === '' || !hash_equals($want, $hdr)) {
      respond(['ok'=>false,'error'=>'CSRF inválido'], 403);
    }
  }

  if ($action === 'save_item') {
    $data = read_json_file($jsonFile);
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($payload)) respond(['ok'=>false,'error'=>'Payload inválido'], 400);

    $item = normalize_item($payload);

    $found = false;
    foreach ($data['items'] as $i => $existing) {
      if (is_array($existing) && (string)($existing['id'] ?? '') === (string)$item['id']) {
        $data['items'][$i] = $item;
        $found = true;
        break;
      }
    }
    if (!$found) $data['items'][] = $item;

    write_json_file_atomic($jsonFile, $data, $backupDir);
    respond(['ok'=>true,'item'=>$item]);
  }

  if ($action === 'delete_item') {
    $data = read_json_file($jsonFile);
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    $id = (string)($payload['id'] ?? '');
    if ($id === '') respond(['ok'=>false,'error'=>'Falta id'], 400);

    $data['items'] = array_values(array_filter($data['items'], fn($it) => !is_array($it) || (string)($it['id'] ?? '') !== $id));
    write_json_file_atomic($jsonFile, $data, $backupDir);

    respond(['ok'=>true]);
  }

  if ($action === 'save_meta') {
    $data = read_json_file($jsonFile);
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);

    $meta = $data['meta'] ?? [];
    $meta['titulo'] = sanitize_str($payload['titulo'] ?? ($meta['titulo'] ?? ''), 128);
    $meta['subtitulo'] = sanitize_str($payload['subtitulo'] ?? ($meta['subtitulo'] ?? ''), 128);
    $meta['source_note'] = sanitize_str($payload['source_note'] ?? ($meta['source_note'] ?? ''), 512);
    $data['meta'] = $meta;

    write_json_file_atomic($jsonFile, $data, $backupDir);
    respond(['ok'=>true,'meta'=>$meta]);
  }

  respond(['ok'=>false,'error'=>'Acción inválida'], 400);

} catch (Throwable $e) {
  respond(['ok'=>false,'error'=>$e->getMessage()], 500);
}