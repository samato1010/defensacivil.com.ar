# Revisión del sistema en Hostinger (no local)

Este proyecto ahora usa una revisión **remota** para diagnosticar por qué puede no cargar `notas.html` en producción.

## Uso rápido

```bash
SSH_HOST=2.59.150.43 \
SSH_PORT=65002 \
SSH_USER=u293796925 \
REMOTE_PATH=~/domains/defensacivil.com.ar/public_html \
./scripts/revisar_sistema.sh
```

> Si tu ruta en Hostinger es distinta, cambiá `REMOTE_PATH`.

## Qué valida

1. Existencia de archivos clave:
   - `notas.html`
   - `data/notas.json`
   - `index.html`
   - `directorio.html`
   - `historia.html`
2. Permisos de `notas.html` y `data/notas.json`.
3. Referencias dentro de `notas.html` a `data/notas.json` y `uploads/notas`.
4. JSON válido en `data/notas.json`.
5. HTTP status de:
   - `https://defensacivil.com.ar/notas.html`
   - `https://defensacivil.com.ar/data/notas.json`
6. Últimas líneas de `error_log` en `public_html` (si existe).

## Nota

Este flujo evita revisar únicamente el workspace local y apunta directamente al entorno real de Hostinger.
