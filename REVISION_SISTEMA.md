# Revisión del sistema y deploy a Hostinger

Este documento deja un runbook completo para que `https://defensacivil.com.ar/notas.html` refleje siempre el último commit en producción.

## Estado de ramas (objetivo de despliegue)

- Rama de despliegue configurada: `main`.
- Cambios del PR/commit `dd83266` deben estar en `main` para activar deploy automático.

## Runbook de merge y deploy

### 1) Merge del trabajo a `main`

Si venís trabajando en otra rama (ej. `work`), mergeá a `main`:

```bash
git checkout main
git merge --no-ff work
git push origin main
```

> Si no existe `main` local todavía, crearla desde la rama actual y subirla:

```bash
git checkout -b main
git push -u origin main
```

### 2) Deploy automático (implementado: Opción B)

Se agregó workflow de GitHub Actions:

- Archivo: `.github/workflows/deploy_hostinger.yml`
- Trigger: `push` a `main` (y ejecución manual con `workflow_dispatch`)
- Método: `rsync` de `./public_html/` hacia Hostinger por SSH
- Verificación post-deploy: `curl` a `https://defensacivil.com.ar/notas.html` y falla si no devuelve HTTP 200.
- Si falla la verificación, el workflow lee `error_log` remoto y lo muestra en logs.

### 3) Secrets requeridos en GitHub

Configurar en `Settings > Secrets and variables > Actions`:

- `HOSTINGER_SSH_HOST` (ej. `2.59.150.43`)
- `HOSTINGER_SSH_PORT` (ej. `65002`)
- `HOSTINGER_SSH_USER` (ej. `u293796925`)
- `HOSTINGER_SSH_KEY` (clave privada SSH)
- `REMOTE_PATH` (ruta de despliegue, ej. `~/domains/defensacivil.com.ar/public_html`)

### 4) Verificación manual rápida

```bash
curl -I https://defensacivil.com.ar/notas.html
curl -I https://defensacivil.com.ar/data/notas.json
```

Esperado: HTTP 200 en ambas.

## Diagnóstico remoto por SSH (Hostinger)

Para revisar el estado del sitio directamente en hosting:

```bash
SSH_HOST=2.59.150.43 \
SSH_PORT=65002 \
SSH_USER=u293796925 \
REMOTE_PATH=~/domains/defensacivil.com.ar/public_html \
./scripts/revisar_sistema.sh
```

El script valida:

1. Archivos críticos (`notas.html`, `data/notas.json`, `index.html`, `directorio.html`, `historia.html`).
2. Permisos de `notas.html` y `data/notas.json`.
3. Referencias dentro de `notas.html` a `data/notas.json` y `uploads/notas`.
4. JSON válido en `data/notas.json`.
5. HTTP status de `notas.html` y `data/notas.json`.
6. Últimas líneas de `error_log`.

## Opción A (alternativa): Git Deploy en hPanel

Si preferís usar Git Deploy nativo de Hostinger en vez de GitHub Actions:

1. hPanel → sitio `defensacivil.com.ar` → **Git** / **Git Deploy**.
2. Conectar el repositorio GitHub.
3. Seleccionar rama `main`.
4. Configurar directorio de deploy como `public_html` del dominio (ruta equivalente a `~/domains/defensacivil.com.ar/public_html`).
5. Ejecutar deploy y verificar `https://defensacivil.com.ar/notas.html` en HTTP 200.

> Si usás Opción A, desactivar o ajustar el workflow de GitHub para evitar doble despliegue.
