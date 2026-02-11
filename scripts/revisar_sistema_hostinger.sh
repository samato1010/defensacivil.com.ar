#!/usr/bin/env bash
set -euo pipefail

# Uso:
#   SSH_HOST=2.59.150.43 SSH_PORT=65002 SSH_USER=u293796925 \
#   REMOTE_PATH=~/domains/defensacivil.com.ar/public_html \
#   ./scripts/revisar_sistema_hostinger.sh

: "${SSH_HOST:?Falta SSH_HOST (ej: 2.59.150.43)}"
: "${SSH_PORT:?Falta SSH_PORT (ej: 65002)}"
: "${SSH_USER:?Falta SSH_USER (ej: u293796925)}"
: "${REMOTE_PATH:?Falta REMOTE_PATH (ruta en hosting)}"

SSH_OPTS=(
  -p "$SSH_PORT"
  -o BatchMode=no
  -o StrictHostKeyChecking=accept-new
)

echo "==> Ejecutando revisión remota en ${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}"

ssh "${SSH_OPTS[@]}" "${SSH_USER}@${SSH_HOST}" "bash -s" -- "$REMOTE_PATH" <<'REMOTE'
set -euo pipefail
REMOTE_PATH="$1"

if [ ! -d "$REMOTE_PATH" ]; then
  echo "[ERROR] No existe la ruta remota: $REMOTE_PATH"
  exit 1
fi

cd "$REMOTE_PATH"

echo "[OK] Ruta remota: $(pwd)"

echo
printf '==== 1) Archivos críticos ====\n'
for f in notas.html data/notas.json index.html directorio.html historia.html; do
  if [ -f "$f" ]; then
    echo "[OK] $f"
  else
    echo "[ERROR] Falta $f"
  fi
done

echo
printf '==== 2) Permisos recomendados ====\n'
stat -c '%A %U:%G %n' notas.html data/notas.json 2>/dev/null || true

echo
printf '==== 3) Referencias en notas.html ====\n'
grep -n 'data/notas.json\|uploads/notas' notas.html || echo '[WARN] No se encontraron referencias esperadas'

echo
printf '==== 4) JSON válido ====\n'
if command -v php >/dev/null 2>&1; then
  php -r '
    $j = @file_get_contents("data/notas.json");
    if ($j === false) { fwrite(STDERR, "No se pudo leer data/notas.json\n"); exit(2); }
    json_decode($j, true);
    if (json_last_error() !== JSON_ERROR_NONE) { fwrite(STDERR, json_last_error_msg()."\n"); exit(3); }
    echo "JSON OK\n";
  '
else
  echo '[WARN] PHP no está disponible para validar JSON'
fi

echo
printf '==== 5) URLs públicas ====\n'
if command -v curl >/dev/null 2>&1; then
  for url in "https://defensacivil.com.ar/notas.html" "https://defensacivil.com.ar/data/notas.json"; do
    code=$(curl -L -s -o /dev/null -w '%{http_code}' "$url" || true)
    echo "$url -> HTTP $code"
  done
else
  echo '[WARN] curl no está disponible en el servidor'
fi

echo
printf '==== 6) Últimos errores PHP (si existen) ====\n'
if [ -f error_log ]; then
  tail -n 40 error_log
else
  echo '[INFO] No se encontró error_log en public_html'
fi
REMOTE

echo
echo "==> Revisión remota finalizada"
