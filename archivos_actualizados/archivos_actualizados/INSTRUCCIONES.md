# 📄 ARCHIVOS ACTUALIZADOS - Historia, Notas y Colaboradores

## 📦 CONTENIDO

Este paquete contiene las versiones actualizadas de 3 páginas HTML con:

✅ CSS externo (main.css + mobile.css)  
✅ Meta tags SEO completos  
✅ Open Graph para redes sociales  
✅ JavaScript para menú móvil  
✅ Responsive optimizado  

```
archivos_actualizados/
├── historia.html           (REEMPLAZAR)
├── notas.html              (REEMPLAZAR)
├── colaboradores.html      (REEMPLAZAR)
└── INSTRUCCIONES.md        (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Reemplazar los archivos

```
Archivo: historia.html
Ruta destino: /public_html/historia.html
Acción: REEMPLAZAR

Archivo: notas.html
Ruta destino: /public_html/notas.html
Acción: REEMPLAZAR

Archivo: colaboradores.html
Ruta destino: /public_html/colaboradores.html
Acción: REEMPLAZAR
```

### PASO 2: Verificar que tenés los archivos previos instalados

Estos HTMLs requieren:

- ✅ `/assets/css/main.css` (Mejora 02)
- ✅ `/assets/css/mobile.css` (Mejora 06)
- ✅ `/assets/js/mobile-nav.js` (Mejora 06)

Si no los tenés, instalá primero las mejoras 02 y 06.

---

## ✅ VERIFICACIÓN

### 1. Probar historia.html

1. Abrir: `https://defensacivil.com.ar/historia.html`
2. Verificar que:
   - ✅ Se ve con estilos correctos
   - ✅ Menú hamburger funciona en móvil
   - ✅ Timeline se muestra correctamente
   - ✅ Links a normativa funcionan

### 2. Probar notas.html

1. Abrir: `https://defensacivil.com.ar/notas.html`
2. Verificar que:
   - ✅ Carga las notas desde `/data/notas.json`
   - ✅ Muestra imágenes (si hay)
   - ✅ Links externos funcionan
   - ✅ Responsive en móvil

### 3. Probar colaboradores.html

1. Abrir: `https://defensacivil.com.ar/colaboradores.html`
2. Verificar que:
   - ✅ Carga colaboradores desde `/data/colaboradores_public.json`
   - ✅ Muestra botón "Registrate"
   - ✅ Link a `/colaboradores/alta.php` funciona
   - ✅ Link a login funciona

---

## 🎯 QUÉ CAMBIÓ

### ANTES:
```html
<head>
  <style>
    /* 200+ líneas de CSS inline repetido */
  </style>
</head>
```

### DESPUÉS:
```html
<head>
  <!-- Meta tags SEO -->
  <meta name="description" content="..."/>
  <meta property="og:title" content="..."/>
  
  <!-- CSS externo -->
  <link rel="stylesheet" href="/assets/css/main.css"/>
  <link rel="stylesheet" href="/assets/css/mobile.css"/>
</head>
```

### Beneficios:
- ✅ Menor tamaño de archivos
- ✅ Mejor SEO (meta tags)
- ✅ Vista previa en WhatsApp/Facebook
- ✅ CSS cacheable
- ✅ Más fácil de mantener

---

## 📝 NOTAS IMPORTANTES

### Historia.html
Esta es una **versión simplificada** del historia.html original. Si tu archivo actual tiene:
- Sistema de filtros complejo
- Búsqueda avanzada
- Múltiples vistas (timeline/tabla/grid)

Entonces **NO reemplaces** este archivo, ya que perderías funcionalidad. En su lugar:

1. Abrí tu `historia.html` actual
2. Reemplazá solo el `<head>` con el del nuevo archivo
3. Mantené todo el `<body>` y `<script>` original

### Notas.html
Carga datos desde `/data/notas.json`. Si no tenés ese archivo con notas, mostrará un mensaje de "sin notas".

### Colaboradores.html
Carga datos desde `/data/colaboradores_public.json` (creado en Mejora 01 - Seguridad).

---

## 🔧 PERSONALIZACIÓN

### Cambiar meta descriptions

Editar cada HTML, línea ~10:

```html
<meta name="description" content="[Tu descripción personalizada]"/>
```

### Cambiar imagen OG

Si ya creaste tu imagen Open Graph, actualizá en cada HTML:

```html
<meta property="og:image" content="https://defensacivil.com.ar/assets/images/og-image.jpg"/>
```

### Agregar más eventos en Historia

Editar `historia.html`, sección `HISTORIA_DATA` (línea ~163):

```javascript
const HISTORIA_DATA = [
  {
    fecha: "2026",
    titulo: "Nuevo evento",
    descripcion: "Descripción...",
    provincia: "Nacional",
    tipo: "Legislación",
    links: [
      { href: "/ruta.html", label: "Ver más" }
    ]
  },
  // Agregar más eventos aquí
];
```

O mejor aún: crear `/data/historia.json` y descomentar la función `loadHistoriaFromJSON()`.

---

## 🆘 PROBLEMAS COMUNES

### Página sin estilos / se ve fea
**Causa:** No encuentra main.css o mobile.css  
**Solución:**
1. Verificar que `/assets/css/main.css` existe
2. Verificar que `/assets/css/mobile.css` existe
3. Limpiar caché del navegador (Ctrl+Shift+R)

### "Cargando..." infinito en notas/colaboradores
**Causa:** No encuentra el archivo JSON o hay error  
**Solución:**
1. Abrir DevTools (F12) > Console
2. Ver el error específico
3. Verificar que `/data/notas.json` o `/data/colaboradores_public.json` existen
4. Verificar que los JSON son válidos (sin errores de sintaxis)

### Menú hamburger no funciona
**Causa:** Falta mobile-nav.js  
**Solución:**
1. Verificar que `/assets/js/mobile-nav.js` existe
2. Ver Console para errores JavaScript

---

## 📊 CHECKLIST POST-INSTALACIÓN

- [ ] historia.html reemplazado
- [ ] notas.html reemplazado
- [ ] colaboradores.html reemplazado
- [ ] Probado en desktop (se ve bien ✅)
- [ ] Probado en móvil (menú funciona ✅)
- [ ] Meta tags verificados (View Source)
- [ ] Links internos funcionan
- [ ] JSONs cargan correctamente
- [ ] Sin errores en Console

---

## 🎉 ¡COMPLETADO!

Con estos 3 archivos actualizados, **todas las páginas principales** de tu sitio tienen:

✅ CSS unificado  
✅ SEO optimizado  
✅ Responsive mobile  
✅ Vista previa en redes  

Tu sitio está 100% listo para el lanzamiento! 🚀

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
