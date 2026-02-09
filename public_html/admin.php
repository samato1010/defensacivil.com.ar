<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

echo json_encode([
  'ok' => true,
  'hit' => 'admin.php',
  'time' => date('Y-m-d H:i:s'),
  'file' => __FILE__,
  'uri' => $_SERVER['REQUEST_URI'] ?? null,
  'host' => $_SERVER['HTTP_HOST'] ?? null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);