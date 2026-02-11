#!/usr/bin/env bash
set -euo pipefail

# Compatibilidad: este script ahora corre la revisión remota en Hostinger.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$SCRIPT_DIR/revisar_sistema_hostinger.sh" "$@"
