<?php
// admin/directorio_api.php - FINAL (con Provincia/Municipio/Localidad)
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Auth placeholder (integrar tu login real)
function require_login(): void {
  // if (empty($_SESSION['logged_in'])) {
  //   http_response_code(401);
  //   echo json_encode(['ok'=>false,'error'=>'No autorizado']);
  //   exit;
  // }
}
require_login();

// Paths
$root = realpath(__DIR__ . '/..');
if ($root === false) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'No se pudo resolver root']);
  exit;
}

$jsonFile  = $root . '/data/directorio.json';
$backupDir = $root . '/admin/_backup_directorio';

// Helpers
function respond($data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  exit;
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true)) throw new RuntimeException("No se pudo crear dir: $dir");
  }
}

function sanitize_str($v, int $max = 2000): string {
  $s = is_string($v) ? $v : '';
  $s = trim($s);
  if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
  return $s;
}

function sanitize_upper($v, int $max = 128): string {
  return mb_strtoupper(sanitize_str($v, $max), 'UTF-8');
}

function read_json_file(string $path): array {
  if (!file_exists($path)) {
    return [
      'meta' => [
        'version' => 1,
        'updated' => date('Y-m-d H:i:s'),
        'titulo' => 'Directorio',
        'subtitulo' => 'Defensa Civil / Protección Civil',
        'source_note' => 'Creado desde editor'
      ],
      'entries' => []
    ];
  }

  $raw = file_get_contents($path);
  if ($raw === false) throw new RuntimeException("No se pudo leer JSON");
  $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

  $data = json_decode($raw, true);
  if (!is_array($data)) throw new RuntimeException("JSON inválido");

  if (!isset($data['entries']) || !is_array($data['entries'])) $data['entries'] = [];
  if (!isset($data['meta']) || !is_array($data['meta'])) $data['meta'] = [];

  return $data;
}

function backup_file(string $src, string $backupDir): void {
  if (!file_exists($src)) return;
  ensure_dir($backupDir);
  $stamp = date('Ymd_His');
  $dst = rtrim($backupDir, '/') . "/directorio_$stamp.json";
  @copy($src, $dst);
}

function write_json_file_atomic(string $path, array $data, string $backupDir): void {
  ensure_dir(dirname($path));
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

function normalize_entry($in): array {
  if (!is_array($in)) $in = [];

  $id = sanitize_str($in['id'] ?? '', 64);
  if ($id === '') $id = 'dir_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));

  return [
    'id' => $id,

    // NUEVOS CAMPOS
    'provincia' => sanitize_upper($in['provincia'] ?? '', 64),
    'municipio' => sanitize_str($in['municipio'] ?? '', 128),
    'localidad' => sanitize_str($in['localidad'] ?? '', 128),

    // CAMPOS EXISTENTES
    'denominacion' => sanitize_str($in['denominacion'] ?? 'Defensa Civil', 80),
    'domicilio'    => sanitize_str($in['domicilio'] ?? '', 220),
    'telefono'     => sanitize_str($in['telefono'] ?? '', 80),
    'email'        => sanitize_str($in['email'] ?? '', 160),
    'website'      => sanitize_str($in['website'] ?? '', 2048),
    'instagram'    => sanitize_str($in['instagram'] ?? '', 2048),
    'facebook'     => sanitize_str($in['facebook'] ?? '', 2048),
    'comentario'   => sanitize_str($in['comentario'] ?? '', 2000),
  ];
}

$action = (string)($_GET['action'] ?? '');

try {
  ensure_dir($backupDir);

  // CSRF token
  if ($action === 'csrf') {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    respond(['ok'=>true, 'csrf'=>$_SESSION['csrf_token']]);
  }

  // LIST
  if ($action === 'list') {
    $data = read_json_file($jsonFile);
    $allEntries = array_map('normalize_entry', array_filter($data['entries'], 'is_array'));

    // options (sobre TODO el dataset, no filtrado)
    $denoms = [];
    $provs  = [];
    $munis  = [];
    $locs   = [];

    foreach ($allEntries as $e) {
      if (!empty($e['denominacion'])) $denoms[] = $e['denominacion'];
      if (!empty($e['provincia']))    $provs[]  = $e['provincia'];
      if (!empty($e['municipio']))    $munis[]  = $e['municipio'];
      if (!empty($e['localidad']))    $locs[]   = $e['localidad'];
    }

    $denoms = array_values(array_unique($denoms)); sort($denoms, SORT_NATURAL | SORT_FLAG_CASE);
    $provs  = array_values(array_unique($provs));  sort($provs, SORT_NATURAL | SORT_FLAG_CASE);
    $munis  = array_values(array_unique($munis));  sort($munis, SORT_NATURAL | SORT_FLAG_CASE);
    $locs   = array_values(array_unique($locs));   sort($locs, SORT_NATURAL | SORT_FLAG_CASE);

    // filters
    $entries = $allEntries;

    $q   = trim((string)($_GET['q'] ?? ''));
    $den = trim((string)($_GET['denominacion'] ?? ''));
    $prov = trim((string)($_GET['provincia'] ?? ''));
    $mun  = trim((string)($_GET['municipio'] ?? ''));
    $loc  = trim((string)($_GET['localidad'] ?? ''));

    if ($q !== '') {
      $qLow = mb_strtolower($q, 'UTF-8');
      $entries = array_values(array_filter($entries, function($e) use ($qLow) {
        $hay = implode(' ', [
          (string)($e['provincia'] ?? ''),
          (string)($e['municipio'] ?? ''),
          (string)($e['localidad'] ?? ''),
          (string)($e['denominacion'] ?? ''),
          (string)($e['domicilio'] ?? ''),
          (string)($e['telefono'] ?? ''),
          (string)($e['email'] ?? ''),
          (string)($e['website'] ?? ''),
          (string)($e['instagram'] ?? ''),
          (string)($e['facebook'] ?? ''),
          (string)($e['comentario'] ?? ''),
        ]);
        return mb_strpos(mb_strtolower($hay, 'UTF-8'), $qLow) !== false;
      }));
    }

    if ($den !== '')   $entries = array_values(array_filter($entries, fn($e) => (string)($e['denominacion'] ?? '') === $den));
    if ($prov !== '')  $entries = array_values(array_filter($entries, fn($e) => (string)($e['provincia'] ?? '') === $prov));
    if ($mun !== '')   $entries = array_values(array_filter($entries, fn($e) => (string)($e['municipio'] ?? '') === $mun));
    if ($loc !== '')   $entries = array_values(array_filter($entries, fn($e) => (string)($e['localidad'] ?? '') === $loc));

    // sort
    $sort = (string)($_GET['sort'] ?? 'geo_asc');
    usort($entries, function($a, $b) use ($sort){
      $pa = (string)($a['provincia'] ?? '');  $pb = (string)($b['provincia'] ?? '');
      $ma = (string)($a['municipio'] ?? '');  $mb = (string)($b['municipio'] ?? '');
      $la = (string)($a['localidad'] ?? '');  $lb = (string)($b['localidad'] ?? '');
      $da = (string)($a['denominacion'] ?? ''); $db = (string)($b['denominacion'] ?? '');

      if ($sort === 'den_asc')  return strcasecmp($da, $db);
      if ($sort === 'den_desc') return strcasecmp($db, $da);

      // geo_asc / geo_desc
      $cmpP = strcasecmp($pa, $pb);
      $cmpM = strcasecmp($ma, $mb);
      $cmpL = strcasecmp($la, $lb);
      $cmpD = strcasecmp($da, $db);

      $cmp = $cmpP ?: ($cmpM ?: ($cmpL ?: $cmpD));
      return ($sort === 'geo_desc') ? -$cmp : $cmp;
    });

    // pagination
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = min(200, max(1, (int)($_GET['per_page'] ?? 50)));
    $total = count($entries);
    $pages = max(1, (int)ceil($total / $per));
    if ($page > $pages) $page = $pages;

    $offset = ($page - 1) * $per;
    $pageItems = array_slice($entries, $offset, $per);

    respond([
      'ok' => true,
      'meta' => $data['meta'] ?? [],
      'options' => [
        'denominaciones' => $denoms,
        'provincias' => $provs,
        'municipios' => $munis,
        'localidades' => $locs,
      ],
      'entries' => $pageItems,
      'paging' => ['page'=>$page, 'per_page'=>$per, 'total'=>$total, 'pages'=>$pages]
    ]);
  }

  // Write actions require POST + CSRF
  $isWrite = in_array($action, ['save_entry','delete_entry','save_meta'], true);
  if ($isWrite) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(['ok'=>false,'error'=>'Método inválido'], 405);

    $want = $_SESSION['csrf_token'] ?? '';
    $hdr  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($want === '' || !hash_equals($want, $hdr)) respond(['ok'=>false,'error'=>'CSRF inválido'], 403);
  }

  if ($action === 'save_entry') {
    $data = read_json_file($jsonFile);

    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($payload)) respond(['ok'=>false,'error'=>'Payload inválido'], 400);

    $entry = normalize_entry($payload);
    $found = false;

    foreach ($data['entries'] as $i => $existing) {
      if (is_array($existing) && (string)($existing['id'] ?? '') === (string)$entry['id']) {
        $data['entries'][$i] = $entry;
        $found = true;
        break;
      }
    }
    if (!$found) $data['entries'][] = $entry;

    write_json_file_atomic($jsonFile, $data, $backupDir);
    respond(['ok'=>true, 'entry'=>$entry]);
  }

  if ($action === 'delete_entry') {
    $data = read_json_file($jsonFile);

    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($payload)) respond(['ok'=>false,'error'=>'Payload inválido'], 400);

    $id = (string)($payload['id'] ?? '');
    if ($id === '') respond(['ok'=>false,'error'=>'Falta id'], 400);

    $data['entries'] = array_values(array_filter($data['entries'], fn($e) => !is_array($e) || (string)($e['id'] ?? '') !== $id));
    write_json_file_atomic($jsonFile, $data, $backupDir);

    respond(['ok'=>true]);
  }

  if ($action === 'save_meta') {
    $data = read_json_file($jsonFile);

    $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($payload)) respond(['ok'=>false,'error'=>'Payload inválido'], 400);

    $meta = $data['meta'] ?? [];
    $meta['titulo']      = sanitize_str($payload['titulo'] ?? ($meta['titulo'] ?? ''), 128);
    $meta['subtitulo']   = sanitize_str($payload['subtitulo'] ?? ($meta['subtitulo'] ?? ''), 128);
    $meta['source_note'] = sanitize_str($payload['source_note'] ?? ($meta['source_note'] ?? ''), 512);

    $data['meta'] = $meta;
    write_json_file_atomic($jsonFile, $data, $backupDir);

    respond(['ok'=>true, 'meta'=>$meta]);
  }

  respond(['ok'=>false,'error'=>'Acción inválida'], 400);

} catch (Throwable $e) {
  respond(['ok'=>false,'error'=>$e->getMessage()], 500);
}