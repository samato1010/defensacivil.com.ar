# 🎨 MEJORA 02: CSS UNIFICADO

## ⚠️ IMPORTANTE

Esta mejora extrae todo el CSS inline a un archivo externo, facilitando mantenimiento y mejorando performance.

**Tiempo estimado:** 15 minutos  
**Dificultad:** Baja  
**Riesgo:** Muy bajo (solo cambia estilos, no funcionalidad)

---

## 📦 ARCHIVOS INCLUIDOS

```
02_CSS_UNIFICADO/
├── assets/
│   └── css/
│       └── main.css          (NUEVO)
├── index.html                (REEMPLAZAR)
└── INSTRUCCIONES.md          (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Crear la carpeta assets

En tu hosting (hPanel > File Manager o FTP):

```
Crear: /public_html/assets/
Crear: /public_html/assets/css/
```

### PASO 2: Subir el CSS principal

```
Archivo: assets/css/main.css
Ruta destino: /public_html/assets/css/main.css
```

### PASO 3: Reemplazar el index.html

```
Archivo: index.html
Ruta destino: /public_html/index.html
Acción: REEMPLAZAR
```

### PASO 4: Actualizar otros HTMLs (opcional pero recomendado)

Si querés aplicar el CSS unificado a todas las páginas, deberás:

1. Abrir cada HTML (directorio.html, historia.html, etc.)
2. **Borrar** todo el `<style>` del `<head>`
3. **Agregar** esta línea en el `<head>`:
   ```html
   <link rel="stylesheet" href="/assets/css/main.css?v=1.0.0"/>
   ```

**Nota:** Si esto te lleva mucho tiempo, podés hacerlo gradualmente (página por página).

---

## ✅ PROBAR QUE FUNCIONE

1. Abrir: `https://defensacivil.com.ar/`
2. Verificar que se vea igual que antes
3. Abrir DevTools (F12) > pestaña Network
4. Recargar la página
5. Verificar que cargue `main.css` (debe aparecer en la lista con status 200)

---

## 🎯 BENEFICIOS

### Antes (CSS inline):
- ❌ 200+ líneas de CSS repetidas en cada HTML
- ❌ Cambio de estilo = editar 5+ archivos
- ❌ Navegador re-descarga CSS en cada página
- ❌ Difícil mantener consistencia

### Después (CSS externo):
- ✅ Un solo archivo CSS
- ✅ Cambio de estilo = editar 1 archivo
- ✅ Navegador cachea el CSS
- ✅ Fácil agregar modo oscuro
- ✅ Mejor performance (menos bytes por página)

---

## 🔧 PERSONALIZACIÓN

### Cambiar colores

Editar `/assets/css/main.css`, líneas 9-15:

```css
:root {
  --az1: #003366;    /* Azul primario */
  --az2: #004080;    /* Azul secundario */
  --n: #ff6600;      /* Naranja acento */
  /* ... */
}
```

### Cambiar fuente

Línea 32 del main.css:

```css
body {
  font-family: Arial, Helvetica, sans-serif;
  /* Cambiar por: 'Roboto', 'Inter', etc. */
}
```

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### La página se ve sin estilos
**Causa:** El navegador no encuentra main.css  
**Solución:**
1. Verificar que `/assets/css/main.css` existe en el servidor
2. Verificar que la ruta en el HTML es `/assets/css/main.css` (con barra inicial)
3. Limpiar caché del navegador (Ctrl+Shift+R)

### Los estilos se ven "viejos"
**Causa:** Caché del navegador  
**Solución:**
1. Cambiar la versión en el HTML: `?v=1.0.0` → `?v=1.0.1`
2. O hacer hard refresh (Ctrl+Shift+R)

---

## 🗂️ ESTRUCTURA FINAL

```
/public_html/
├── assets/
│   └── css/
│       └── main.css          ✅ (cacheable, versionado)
├── index.html                ✅ (link al CSS)
├── directorio.html           ⚠️ (actualizar después)
├── historia.html             ⚠️ (actualizar después)
└── ...
```

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
