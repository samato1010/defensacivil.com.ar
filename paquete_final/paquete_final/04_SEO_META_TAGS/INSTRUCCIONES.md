# 🏷️ MEJORA 04: SEO Y META TAGS

## ⚠️ IMPORTANTE

Esta mejora optimiza tu sitio para buscadores y redes sociales. Es CRÍTICA para el lanzamiento.

**Tiempo estimado:** 20 minutos  
**Dificultad:** Baja  
**Impacto:** Alto (visibilidad en Google y redes)

---

## 📦 ARCHIVOS INCLUIDOS

```
04_SEO_META_TAGS/
├── index.html               (REEMPLAZAR)
├── sitemap.xml              (NUEVO)
├── robots.txt               (NUEVO)
└── INSTRUCCIONES.md         (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Subir sitemap.xml

```
Archivo: sitemap.xml
Ruta destino: /public_html/sitemap.xml
```

**Importante:** Editar el archivo y actualizar `<lastmod>` con la fecha actual.

### PASO 2: Subir robots.txt

```
Archivo: robots.txt
Ruta destino: /public_html/robots.txt
```

### PASO 3: Reemplazar index.html

```
Archivo: index.html (con meta tags)
Ruta destino: /public_html/index.html
Acción: REEMPLAZAR
```

### PASO 4: Crear imagen Open Graph

Necesitás crear una imagen de 1200x630px para redes sociales:

**Opción A: Crear imagen personalizada**
1. Crear imagen de 1200x630px (usar Canva, Figma, etc.)
2. Incluir: logo DC, texto "Defensa Civil Argentina"
3. Guardar como JPG
4. Subir a: `/public_html/assets/images/og-image.jpg`

**Opción B: Usar placeholder temporalmente**
- Comentar las líneas de `og:image` en el HTML
- Crear imagen después del lanzamiento

### PASO 5: Actualizar otros HTMLs (recomendado)

Repetir para: `directorio.html`, `historia.html`, `notas.html`, `colaboradores.html`

Agregar en el `<head>` de cada uno:

```html
<!-- SEO Básico -->
<meta name="description" content="[Descripción específica de la página]"/>
<meta name="keywords" content="defensa civil, protección civil, argentina, [palabras clave específicas]"/>

<!-- Open Graph -->
<meta property="og:title" content="[Título de la página] - Defensa Civil Argentina"/>
<meta property="og:description" content="[Descripción específica]"/>
<meta property="og:image" content="https://defensacivil.com.ar/assets/images/og-image.jpg"/>
<meta property="og:url" content="https://defensacivil.com.ar/[nombre-pagina].html"/>

<!-- Canonical -->
<link rel="canonical" href="https://defensacivil.com.ar/[nombre-pagina].html"/>
```

**Descripciones sugeridas:**

- **Directorio:** "Directorio completo de contactos de Defensa Civil en Argentina. Búsqueda por provincia, municipio y localidad."
- **Historia:** "Historia de la Defensa Civil en Argentina desde sus orígenes hasta la actualidad. Línea de tiempo y documentos históricos."
- **Notas:** "Notas y actualizaciones sobre Defensa Civil en Argentina. Eventos, capacitaciones y noticias del sector."
- **Colaboradores:** "Red de colaboradores de DefensaCivil.com.ar. Sumate para contribuir información de tu provincia o municipio."

---

## ✅ VERIFICACIÓN

### 1. Verificar sitemap.xml
Abrir: `https://defensacivil.com.ar/sitemap.xml`

Deberías ver XML válido con todas las URLs.

### 2. Verificar robots.txt
Abrir: `https://defensacivil.com.ar/robots.txt`

Debería mostrar las directivas correctamente.

### 3. Verificar meta tags
1. Abrir `https://defensacivil.com.ar/`
2. Click derecho > "Ver código fuente"
3. Buscar `<meta property="og:title"`
4. Verificar que estén todos los meta tags

### 4. Probar vista previa social
Usar: https://www.opengraph.xyz/

1. Pegar URL: `https://defensacivil.com.ar/`
2. Ver preview de Facebook/Twitter
3. Verificar que se vea bien

### 5. Google Search Console (post-lanzamiento)
1. Ir a: https://search.google.com/search-console
2. Agregar propiedad: `defensacivil.com.ar`
3. Verificar propiedad (DNS o archivo HTML)
4. Enviar sitemap: `https://defensacivil.com.ar/sitemap.xml`

---

## 🎯 QUÉ LOGRAMOS

### Antes:
- ❌ Sin meta tags
- ❌ Google no sabe de qué trata el sitio
- ❌ Vista previa fea en WhatsApp/Facebook
- ❌ Sin structured data

### Después:
- ✅ Meta tags completos
- ✅ Google entiende el contenido
- ✅ Vista previa profesional en redes
- ✅ Rich snippets en resultados
- ✅ Sitemap para indexación rápida

---

## 📊 IMPACTO ESPERADO

**En 1 semana:**
- Sitio indexado en Google
- Aparece en búsquedas de "defensa civil argentina"

**En 1 mes:**
- Primeros 100-200 visitantes orgánicos
- Vista previa en redes funcionando

**En 3 meses:**
- Posicionamiento en búsquedas relevantes
- 500+ visitantes/mes si el contenido es completo

---

## 🔧 PERSONALIZACIÓN

### Cambiar descripción del sitio

Editar `index.html`, línea ~10:

```html
<meta name="description" content="[Tu descripción aquí]"/>
```

Máximo 160 caracteres para que no se corte en resultados.

### Cambiar keywords

Línea ~11:

```html
<meta name="keywords" content="defensa civil, [más palabras clave]"/>
```

### Cambiar structured data

Líneas ~40-60 (script JSON-LD):

```json
{
  "@context": "https://schema.org",
  "@type": "GovernmentOrganization",
  "name": "Tu nombre",
  ...
}
```

---

## 🆘 PROBLEMAS COMUNES

### La imagen OG no se ve en Facebook
**Solución:**
1. Verificar que la imagen existe en: `/assets/images/og-image.jpg`
2. Verificar que es 1200x630px
3. Limpiar caché de Facebook: https://developers.facebook.com/tools/debug/
4. Pegar tu URL y click en "Scrape Again"

### Sitemap da error 404
**Solución:**
1. Verificar que está en: `/public_html/sitemap.xml` (raíz del sitio)
2. Verificar permisos: debe ser 644
3. Verificar que el archivo es XML válido

### Google no indexa el sitio
**Solución:**
1. Esperar 3-7 días (es normal)
2. Enviar sitemap en Search Console
3. Pedir indexación manual de la home

---

## 📝 CONTENIDO DEL SITEMAP

El sitemap.xml incluye:
- / (home) - prioridad 1.0
- /directorio.html - prioridad 0.9
- /historia.html - prioridad 0.8
- /notas.html - prioridad 0.7
- /colaboradores.html - prioridad 0.6
- /normativa/*.html - prioridad 0.5

**Actualizar fechas cuando hagas cambios importantes.**

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
