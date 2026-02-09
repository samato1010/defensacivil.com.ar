function fix_mojibake(string $s): string {
  // Solo repara si detecta patrón típico roto (Ã, Â)
  if (preg_match('/[ÃÂ]/u', $s)) {
    $fixed = utf8_decode($s); // convierte Ã³ -> ó (en bytes UTF-8)
    // si quedó válido como UTF-8, usamos el fixed
    if (function_exists('mb_check_encoding')) {
      if (mb_check_encoding($fixed, 'UTF-8')) return $fixed;
    } else {
      return $fixed;
    }
  }
  return $s;
}