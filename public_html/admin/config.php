<?php
// /admin/config.php
declare(strict_types=1);

/**
 * Debug opcional: agregá ?debug=1 en cualquier URL de /admin
 * Ej: /admin/index.php?debug=1
 */
if (isset($_GET['debug'])) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
}

date_default_timezone_set('America/Argentina/Buenos_Aires');

/* =========================
   Sesión (cookies seguras)
   ========================= */
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

session_name('DCADMINSESS');
session_set_cookie_params([
  'lifetime' => 0,
  'path' => '/',
  'domain' => '',
  'secure' => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);

ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* =========================
   Usuarios internos (fallback)
   =========================
   Recomendación: dejá un admin interno para emergencias.
*/
$USERS = [
  'admin' => [
    'pass_hash' => '$2y$10$1930YIC7B5H1KRhU5McHAOCZM6tSo0pRnl5YYAKM0xzqAjhmbEd7S',
    'role' => 'admin',
    'permisos' => ['directorio'=>1,'historia'=>1,'notas'=>1,'colaboradores'=>1],
  ],
];

/* =========================
   Paths (con guards)
   ========================= */
if (!defined('SITE_ROOT')) {
  define('SITE_ROOT', dirname(__DIR__)); // /public_html
}
if (!defined('DATA_DIR')) {
  define('DATA_DIR', SITE_ROOT . '/data'); // /public_html/data
}
if (!defined('NORMATIVA_DIR')) {
  define('NORMATIVA_DIR', SITE_ROOT . '/normativa'); // /public_html/normativa
}
if (!defined('NORMATIVA_JSON')) {
  define('NORMATIVA_JSON', NORMATIVA_DIR . '/normativa.json'); // /public_html/normativa/normativa.json
}
if (!defined('COLAB_JSON')) {
  define('COLAB_JSON', DATA_DIR . '/colaboradores.json'); // /public_html/data/colaboradores.json
}
if (!defined('NOTAS_JSON')) {
  define('NOTAS_JSON', DATA_DIR . '/notas.json'); // /public_html/data/notas.json (si lo usás)
}
if (!defined('BACKUP_DIR')) {
  define('BACKUP_DIR', __DIR__ . '/_backup'); // /public_html/admin/_backup
}

/* =========================
   Helpers: directorios / JSON
   ========================= */
function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
  }
}

function strip_bom(string $s): string {
  if (substr($s, 0, 3) === "\xEF\xBB\xBF") return substr($s, 3);
  return $s;
}

function json_read(string $path, array $default): array {
  if (!is_file($path)) return $default;
  $raw = @file_get_contents($path);
  if ($raw === false) return $default;
  $raw = strip_bom($raw);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : $default;
}

function json_write_atomic(string $path, array $data): bool {
  $dir = dirname($path);
  ensure_dir($dir);

  $tmp = $path . '.tmp';
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) return false;

  $ok = (@file_put_contents($tmp, $json, LOCK_EX) !== false);
  if (!$ok) return false;

  return @rename($tmp, $path);
}

/**
 * Arregla mojibake típico (ej: "visualizaciÃ³n" -> "visualización")
 * Solo aplica si detecta patrones comunes.
 */
function fix_mojibake(string $s): string {
  if ($s === '') return $s;

  // patrones típicos de UTF-8 mal interpretado
  if (!preg_match('/[ÃÂâ€™â€œâ€�â€“â€”]/u', $s)) {
    return $s;
  }

  // intentar convertir asumiendo que el texto actual está en ISO-8859-1
  $fixed = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $s);
  if ($fixed !== false && $fixed !== '') {
    return $fixed;
  }
  return $s;
}

/* =========================
   Storage bootstrap
   ========================= */
function ensure_storage(): void {
  ensure_dir(DATA_DIR);
  ensure_dir(NORMATIVA_DIR);
  ensure_dir(BACKUP_DIR);

  if (!file_exists(NORMATIVA_JSON)) {
    $seed = [
      'meta' => ['updated' => date('Y-m-d H:i:s')],
      'items' => [],
    ];
    json_write_atomic(NORMATIVA_JSON, $seed);
  }

  if (!file_exists(COLAB_JSON)) {
    $seed = [
      'meta' => [
        'titulo' => 'Defensa Civil Argentina',
        'subtitulo' => 'Red de colaboradores',
        'updated' => date('Y-m-d H:i:s'),
      ],
      'collaborators' => [],
      'items' => [],
    ];
    json_write_atomic(COLAB_JSON, $seed);
  }

  if (!file_exists(NOTAS_JSON)) {
    $seed = [
      'meta' => [
        'titulo' => 'Defensa Civil Argentina',
        'subtitulo' => 'Notas y actualizaciones',
        'updated' => date('Y-m-d H:i:s'),
      ],
      'notes' => [],
    ];
    json_write_atomic(NOTAS_JSON, $seed);
  }
}

ensure_storage();

/* =========================
   Auth / Roles / Permisos
   ========================= */
function is_logged_in(): bool {
  return !empty($_SESSION['auth']) && $_SESSION['auth'] === true && !empty($_SESSION['user']);
}

function current_username(): string {
  return (string)($_SESSION['user'] ?? '');
}

function current_role(): string {
  return (string)($_SESSION['role'] ?? '');
}

function current_perms(): array {
  $p = $_SESSION['permisos'] ?? [];
  return is_array($p) ? $p : [];
}

function is_admin(): bool {
  if (!is_logged_in()) return false;
  $u = current_username();
  $r = strtolower(current_role());
  return ($u === 'admin') || ($r === 'admin');
}

function require_login(): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
}

function require_admin(): void {
  require_login();
  if (!is_admin()) {
    http_response_code(403);
    echo "No autorizado.";
    exit;
  }
}

function user_can(string $cap): bool {
  if (!is_logged_in()) return false;
  if (is_admin()) return true;
  $p = current_perms();
  return !empty($p[$cap]);
}

/* =========================
   CSRF
   ========================= */
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
  if (!$token || empty($_SESSION['csrf'])) return false;
  return hash_equals((string)$_SESSION['csrf'], (string)$token);
}

/* =========================
   Users store (internos + colaboradores.json)
   ========================= */

/**
 * Devuelve un map username => userData
 * userData: pass_hash, role, permisos, status
 */
function dc_load_users_from_colaboradores(): array {
  $data = json_read(COLAB_JSON, [
    'meta' => [],
    'collaborators' => [],
    'items' => [],
  ]);

  $items = $data['items'] ?? [];
  if (!is_array($items)) $items = [];

  $out = [];
  foreach ($items as $it) {
    if (!is_array($it)) continue;

    $usuario = trim((string)($it['usuario'] ?? ''));
    $hash = (string)($it['pass_hash'] ?? '');
    if ($usuario === '' || $hash === '') continue;

    $role = strtolower(trim((string)($it['role'] ?? 'colaborador')));
    if ($role !== 'admin') $role = 'colaborador';

    $status = strtolower(trim((string)($it['status'] ?? 'pendiente')));
    $perms = $it['permisos'] ?? [];
    if (!is_array($perms)) $perms = [];

    // normalizamos claves a int 0/1
    $permsNorm = [
      'directorio' => !empty($perms['directorio']) ? 1 : 0,
      'historia'   => !empty($perms['historia']) ? 1 : 0,
      'notas'      => !empty($perms['notas']) ? 1 : 0,
      'colaboradores' => !empty($perms['colaboradores']) ? 1 : 0,
    ];

    // reglas de habilitación:
    // - admin: puede loguear aunque esté pendiente (para que puedas “levantarte” como samato)
    // - colaborador: solo si status = aprobado
    $enabled = false;
    if ($role === 'admin') $enabled = true;
    else $enabled = ($status === 'aprobado');

    $out[strtolower($usuario)] = [
      'pass_hash' => $hash,
      'role' => $role,
      'status' => $status,
      'enabled' => $enabled,
      'permisos' => $permsNorm,
    ];
  }

  return $out;
}

function dc_get_user(string $username): ?array {
  $u = strtolower(trim($username));
  if ($u === '') return null;

  // 1) internos
  global $USERS;
  if (isset($USERS[$u]) && is_array($USERS[$u])) {
    $x = $USERS[$u];
    return [
      'pass_hash' => (string)($x['pass_hash'] ?? ''),
      'role' => strtolower((string)($x['role'] ?? 'admin')),
      'enabled' => true,
      'permisos' => is_array($x['permisos'] ?? null) ? $x['permisos'] : ['directorio'=>1,'historia'=>1,'notas'=>1,'colaboradores'=>1],
      'status' => 'interno',
    ];
  }

  // 2) colaboradores.json
  $col = dc_load_users_from_colaboradores();
  if (!isset($col[$u])) return null;

  return $col[$u];
}

/**
 * Setea la sesión al loguear (lo usa login.php)
 */
function dc_login_session_set(string $username, array $user): void {
  $_SESSION['auth'] = true;
  $_SESSION['user'] = $username;
  $_SESSION['role'] = (string)($user['role'] ?? 'colaborador');
  $_SESSION['permisos'] = is_array($user['permisos'] ?? null) ? $user['permisos'] : [];
}

/**
 * Logout helper
 */
function dc_logout(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}