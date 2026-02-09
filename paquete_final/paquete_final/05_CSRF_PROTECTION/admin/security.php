<?php
/**
 * Seguridad adicional para DefensaCivil.com.ar
 * 
 * INSTRUCCIONES:
 * 1. Subir este archivo a /admin/security.php
 * 2. Incluirlo en config.php agregando: require_once __DIR__ . '/security.php';
 * 3. Usar las funciones en formularios y APIs
 */

declare(strict_types=1);

/* =========================
   RATE LIMITING
   ========================= */

/**
 * Verifica si una IP ha excedido el límite de solicitudes
 * 
 * @param string $action Identificador de acción (ej: 'login', 'alta_colaborador')
 * @param int $max_attempts Máximo de intentos permitidos
 * @param int $window_seconds Ventana de tiempo en segundos
 * @return bool true si está dentro del límite, false si lo excedió
 */
function check_rate_limit(string $action, int $max_attempts = 5, int $window_seconds = 3600): bool {
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $key = $action . '_' . $ip;
  
  // Leer archivo de rate limits
  $rate_file = __DIR__ . '/_storage/rate_limits.json';
  
  if (!file_exists($rate_file)) {
    @mkdir(dirname($rate_file), 0755, true);
    file_put_contents($rate_file, '{}');
  }
  
  $data = json_decode(file_get_contents($rate_file), true) ?: [];
  
  // Limpiar entradas viejas
  $now = time();
  foreach ($data as $k => $v) {
    if (isset($v['expires']) && $v['expires'] < $now) {
      unset($data[$k]);
    }
  }
  
  // Verificar límite
  if (isset($data[$key])) {
    if ($data[$key]['count'] >= $max_attempts) {
      return false; // Límite excedido
    }
    $data[$key]['count']++;
  } else {
    $data[$key] = [
      'count' => 1,
      'expires' => $now + $window_seconds,
      'ip' => $ip,
      'action' => $action,
    ];
  }
  
  // Guardar
  file_put_contents($rate_file, json_encode($data, JSON_PRETTY_PRINT));
  
  return true; // Dentro del límite
}

/**
 * Obtiene el número de intentos restantes
 */
function get_remaining_attempts(string $action, int $max_attempts = 5): int {
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $key = $action . '_' . $ip;
  
  $rate_file = __DIR__ . '/_storage/rate_limits.json';
  if (!file_exists($rate_file)) return $max_attempts;
  
  $data = json_decode(file_get_contents($rate_file), true) ?: [];
  
  if (!isset($data[$key])) return $max_attempts;
  
  $used = $data[$key]['count'] ?? 0;
  return max(0, $max_attempts - $used);
}

/* =========================
   CSRF MEJORADO
   ========================= */

/**
 * Genera un token CSRF único por formulario
 * 
 * @param string $form_id Identificador del formulario
 * @return string Token CSRF
 */
function csrf_token_for(string $form_id): string {
  if (!isset($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = [];
  }
  
  // Generar nuevo token para este formulario
  $token = bin2hex(random_bytes(32));
  $_SESSION['csrf_tokens'][$form_id] = [
    'token' => $token,
    'expires' => time() + 3600, // 1 hora
  ];
  
  // Limpiar tokens expirados
  foreach ($_SESSION['csrf_tokens'] as $id => $data) {
    if ($data['expires'] < time()) {
      unset($_SESSION['csrf_tokens'][$id]);
    }
  }
  
  return $token;
}

/**
 * Verifica un token CSRF específico de formulario
 * 
 * @param string $token Token a verificar
 * @param string $form_id Identificador del formulario
 * @return bool true si es válido
 */
function csrf_check_for(string $token, string $form_id): bool {
  if (!isset($_SESSION['csrf_tokens'][$form_id])) {
    return false;
  }
  
  $stored = $_SESSION['csrf_tokens'][$form_id];
  
  // Verificar expiración
  if ($stored['expires'] < time()) {
    unset($_SESSION['csrf_tokens'][$form_id]);
    return false;
  }
  
  // Verificar token
  if (!hash_equals($stored['token'], $token)) {
    return false;
  }
  
  // Token usado, eliminar
  unset($_SESSION['csrf_tokens'][$form_id]);
  
  return true;
}

/* =========================
   VALIDACIÓN DE INPUTS
   ========================= */

/**
 * Sanitiza una cadena de texto
 */
function sanitize_text(string $text, int $max_length = 255): string {
  $text = trim($text);
  $text = strip_tags($text);
  $text = substr($text, 0, $max_length);
  return $text;
}

/**
 * Valida y sanitiza un email
 */
function sanitize_email(string $email): ?string {
  $email = trim($email);
  $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return null;
  }
  
  return strtolower($email);
}

/**
 * Valida una URL
 */
function validate_url(string $url): bool {
  return (bool)filter_var($url, FILTER_VALIDATE_URL);
}

/**
 * Sanitiza HTML (permite solo tags seguros)
 */
function sanitize_html(string $html): string {
  // Permitir solo: <p>, <br>, <strong>, <em>, <a>, <ul>, <ol>, <li>
  $allowed_tags = '<p><br><strong><em><a><ul><ol><li>';
  return strip_tags($html, $allowed_tags);
}

/* =========================
   HEADERS DE SEGURIDAD
   ========================= */

/**
 * Establece headers de seguridad recomendados
 */
function set_security_headers(): void {
  // Prevenir clickjacking
  header('X-Frame-Options: SAMEORIGIN');
  
  // Prevenir MIME sniffing
  header('X-Content-Type-Options: nosniff');
  
  // XSS Protection (legacy, pero no hace daño)
  header('X-XSS-Protection: 1; mode=block');
  
  // Referrer Policy
  header('Referrer-Policy: strict-origin-when-cross-origin');
  
  // Permissions Policy (antes Feature-Policy)
  header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
  
  // Content Security Policy (básico)
  // Nota: Ajustar según necesidades reales
  header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
}

/* =========================
   LOGS DE SEGURIDAD
   ========================= */

/**
 * Registra un evento de seguridad
 */
function log_security_event(string $event_type, string $message, array $context = []): void {
  $log_file = __DIR__ . '/_storage/security.log';
  
  @mkdir(dirname($log_file), 0755, true);
  
  $entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'type' => $event_type,
    'message' => $message,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
    'user' => $_SESSION['user'] ?? 'anonymous',
    'context' => $context,
  ];
  
  $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
  
  @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
  
  // Rotar log si supera 5MB
  if (file_exists($log_file) && filesize($log_file) > 5 * 1024 * 1024) {
    $backup = $log_file . '.' . date('YmdHis') . '.old';
    @rename($log_file, $backup);
  }
}

/* =========================
   HONEYPOT
   ========================= */

/**
 * Verifica campo honeypot (anti-bot)
 * 
 * @param string $field_name Nombre del campo honeypot
 * @return bool true si pasó la verificación (campo vacío = humano)
 */
function check_honeypot(string $field_name = 'website'): bool {
  $value = $_POST[$field_name] ?? '';
  
  if ($value !== '') {
    log_security_event('honeypot_triggered', 'Bot detectado', [
      'field' => $field_name,
      'value' => substr($value, 0, 50),
    ]);
    return false;
  }
  
  return true;
}
