<?php
require_once __DIR__ . '/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';

if ($action === 'get') {
  $data = json_read_file(JSON_PATH);
  echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

if ($action === 'save') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
  }

  $csrf = $_POST['csrf'] ?? null;
  verify_csrf(is_string($csrf) ? $csrf : null);

  $json = (string)($_POST['json'] ?? '');
  if (!$json) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON vacío']);
    exit;
  }

  $data = json_decode($json, true);
  if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido (no parsea)']);
    exit;
  }

  $errors = json_validate_basic($data);
  if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Validación falló', 'details' => $errors]);
    exit;
  }

  ensure_backup_dir();

  // backup
  $ts = date('Ymd_His');
  $backupPath = BACKUP_DIR . "/defensa_civil_$ts.json";
  if (file_exists(JSON_PATH)) {
    @copy(JSON_PATH, $backupPath);
  }

  // write atomic-ish
  $tmp = JSON_PATH . '.tmp';
  $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($encoded === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo serializar JSON']);
    exit;
  }

  $ok = file_put_contents($tmp, $encoded . "\n", LOCK_EX);
  if ($ok === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo escribir archivo temporal']);
    exit;
  }

  $ren = @rename($tmp, JSON_PATH);
  if (!$ren) {
    @unlink($tmp);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo reemplazar el JSON final']);
    exit;
  }

  echo json_encode(['ok' => true, 'message' => 'Guardado OK', 'backup' => basename($backupPath)]);
  exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Acción inválida']);