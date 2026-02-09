# DefensaCivil.com.ar — Manifesto técnico (para IA)

Fecha de generación: 2026-02-02 14:28:34 (America/Argentina/Buenos_Aires)

Este documento resume la estructura y funcionamiento del sitio **defensacivil.com.ar** a partir del ZIP `public_html.zip`.

---

## 1) Mapa del sitio (rutas principales)

### Páginas públicas (HTML)
- `/index.html` → portada con navegación.
- `/directorio.html` → consume `JSON_URL = /data/directorio.json`.
- `/notas.html` → consume `JSON_URL = /data/notas.json`, imágenes desde `UPLOAD_PATH = /uploads/notas/`.
- `/historia.html` → página informativa (estática).
- `/colaboradores.html` → página informativa + link a alta de colaboradores.
- `/normativa/*.html` → documentos normativos (HTML individuales).
- `/publicaciones/*.html` → publicaciones (HTML individuales).

### Alta/registro (PHP público)
- `/colaboradores/alta.php` → alta de colaborador (escribe en `/data/colaboradores.json`).
- `/colaborar.php` → alta alternativa (usa helpers de `admin/config.php`).

### Admin (PHP)
- `/admin/` o `/admin/index.php` → panel.
- `/admin/login.php` / `/admin/logout.php`
- Editores:
  - `/admin/directorio_editor.php` + `/admin/directorio_api.php`
  - `/admin/notas_editor.php` + `/admin/notas_api.php`
  - `/admin/normativa_editor.php` + `/admin/normativa_api.php`
  - `/admin/colaboradores_editor.php` (gestión de solicitudes/roles)
  - `/admin/usuarios.php` (usuarios)
- Infra:
  - `/admin/config.php` (sesión, helpers, paths)
  - `/admin/estado.php`, `/admin/ping.php`, `/admin/phpver.php` (diagnóstico)

### Rewrites
- `/.htaccess` redirige `/admin` y `/admin.php` → `/admin/index.php`.

---

## 2) Datos (JSON) y esquemas

### `/data/directorio.json` (actualizado: 2026-01-30 19:41:19)
Estructura:
- `meta`: `{
  "version", "updated", "titulo", "subtitulo", "source_note"
}`
- `entries[]` con campos:
  - `id`, `provincia`, `municipio`, `localidad`,
  - `denominacion`, `domicilio`, `telefono`, `email`,
  - `website`, `instagram`, `facebook`, `comentario`

### `/data/notas.json` (actualizado: 2026-01-30)
Estructura:
- `meta`: `{ "updated", "titulo"?, "subtitulo"? }`
- `notes[]` con campos usados por la UI:
  - `fecha`, `provincia`, `categoria`, `titulo`, `texto`
  - `imagen` (string, filename en `/uploads/notas/`)
  - `links[]` (objetos con `href` y `label`)

### `/data/colaboradores.json` (actualizado: 2026-01-31 10:04:58)
Estructura:
- `meta`: `{ "titulo", "subtitulo", "updated" }`
- `collaborators[]`: lista pública de colaboradores (datos de contacto).
- `items[]`: solicitudes / cuentas (incluye credenciales hash, IP, user-agent).

**Nota de seguridad importante:** `items[]` contiene `pass_hash` y metadatos sensibles. Si `/data/colaboradores.json` es accesible públicamente (lo normal), esto expone hashes de contraseña y datos de origen. Recomendación: separar **datos públicos** y **datos privados** (ver sección 5).

### `/normativa/normativa.json` (actualizado: 2026-01-30 20:41:38)
Estructura:
- `meta`: `{ "version", "updated", "source_note" }`
- `items[]` con campos:
  - `id`, `fecha`, `fecha_label`, `provincia`,
  - `tipo`, `origen`, `titulo`, `descripcion`,
  - `tags[]`, `links[]`

---

## 3) Estética (design tokens)

Variables base detectadas en `index.html`:
`--az1:#003366; --az2:#004080; --n:#ff6600; --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555; --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc; --shadow:0 10px 24px rgba(0,0,0,.14);`

Patrón UI:
- Header: degradado azul + brillos radiales + grilla sutil.
- Links/acciones: estilo “pill”.
- Contenido: cards con sombras suaves, tipografía Arial/Helvetica, layout centrado.

---

## 4) Estructura de archivos (resumen)

(Profundidad 2)

```
admin/
  _backup/
    notas.json.20260130_161514.bak
    notas.json.20260130_180126.bak
    notas.json.20260130_181314.bak
    notas.json.20260130_181414.bak
    notas.json.20260130_181824.bak
    notas.json.20260130_182430.bak
  _backup_directorio/
    changes.log
    directorio.lock
    directorio_20260129_193131.json
    directorio_20260129_193634.json
    directorio_20260129_194458.json
    directorio_20260129_194537.json
    directorio_20260129_195515.json
    directorio_20260130_144307.json
    directorio_20260130_194119.json
  _backup_normativa/
    .htaccess
    htaccess
    index.html
    normativa.lock
    normativa_20260128_232801.json
    normativa_20260130_154933.json
    normativa_20260130_154938.json
    normativa_20260130_155000.json
    normativa_20260130_185021.json
    normativa_20260130_204138.json
  _storage/
    .htaccess
    users.json
  uploads/
    notas/
  _probe_admin.php
  admin.php
  api.php
  colaboradores_admin.php
  colaboradores_editor.php
  config.php
  directorio_api.php
  directorio_editor.php
  editor.php
  est_api_min.php
  estado.php
  index.php
  login.php
  logout.php
  mapa.php
  normativa_api.php
  normativa_editor.php
  notas_api.php
  notas_editor.php
  phpver.php
  ping.php
  test_api.php
  usuarios.php
colaboradores/
  alta.php
data/
  backups/
  colaboradores.json
  directorio.json
  notas.json
normativa/
  backups/
    normativa_20260127_161711.json
    normativa_20260127_161728.json
    normativa_20260127_161820.json
  .htaccess
  Decreto-207-2007.html
  DECRETO-3321-1992.html
  decreto-ley-2580-1973.html
  decreto-ley-4104-1943.html
  DECRETO-LEY-7738-1971.html
  Decreto_1170_82.html
  ley-4060-1984.html
  ley-8906-2000.html
  LEY-9401-1979.html
  normativa.json
publicaciones/
  gdcpba1986.html
uploads/
  notas/
    n-1986-07-30-001-1769807594.jpeg
    n-1986-07-30-001-1769807904.jpeg
.htaccess
_probe.php
admin.php
colaboradores.html
colaborar.php
default.php
directorio.html
historia.html
index.html
notas.html
```

---

## 5) Riesgos y mejoras recomendadas (alta prioridad)

1) **Separar credenciales del JSON público**
   - Actualmente `data/colaboradores.json` contiene `items[]` con `pass_hash`, IP y user-agent.
   - Propuesta: 
     - `data/colaboradores_public.json` → solo `meta + collaborators` (sin cuentas).
     - `admin/_storage/colaboradores_items.json` (protegido por `.htaccess`) → `items[]` privados.
     - Ajustar:
       - `/colaboradores/alta.php` para escribir en storage privado.
       - `/admin/login.php` para leer del storage privado.

2) **Consistencia de constantes**
   - Detectado uso de `COLABS_JSON` en algunos PHP, pero en config aparece `COLAB_JSON`. Unificar.

3) **Unificar CSS**
   - Hoy cada HTML incluye CSS inline similar.
   - Propuesta: `assets/style.css` común + mantener tokens centralizados.

---

## 6) Qué subir a una IA para “conocer toda la web”

Recomendado (seguro y efectivo):
1) Este manifiesto (`DEFENSACIVIL_MANIFEST.md`).
2) Un ZIP **sanitizado** del sitio (sin hashes, IPs, backups), o al menos:
   - `index.html`, `directorio.html`, `notas.html`, `historia.html`, `colaboradores.html`
   - Carpeta `admin/` (sin `_storage/users.json` ni backups)
   - Carpeta `data/` (sin `items[]` con credenciales)
   - Carpeta `normativa/` (html + normativa.json)
   - `.htaccess`

---

## 7) Regla de trabajo para la IA (para continuidad)

- Cambios incrementales, “no romper lo que ya funciona”.
- Entregar archivos completos listos para copiar y reemplazar.
- Siempre indicar cómo probar local/en hosting.
- Si toca seguridad/roles: sanitizar inputs, CSRF, y no publicar secretos.
