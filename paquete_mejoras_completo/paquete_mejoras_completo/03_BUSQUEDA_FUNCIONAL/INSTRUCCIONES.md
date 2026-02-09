# 🔍 MEJORA 03: BÚSQUEDA FUNCIONAL EN DIRECTORIO

## ⚠️ IMPORTANTE

Esta mejora agrega búsqueda en tiempo real, filtros y estadísticas al directorio.

**Tiempo estimado:** 10 minutos  
**Dificultad:** Baja  
**Riesgo:** Muy bajo  
**Requisito:** Mejora 02 (CSS Unificado) implementada

---

## 📦 ARCHIVOS INCLUIDOS

```
03_BUSQUEDA_FUNCIONAL/
├── directorio.html           (REEMPLAZAR)
└── INSTRUCCIONES.md          (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Hacer backup (opcional pero recomendado)

En tu hosting, renombrar el actual:
```
/public_html/directorio.html
→ /public_html/directorio.html.backup_2026
```

### PASO 2: Subir el nuevo directorio.html

```
Archivo: directorio.html
Ruta destino: /public_html/directorio.html
Acción: REEMPLAZAR
```

---

## ✅ PROBAR QUE FUNCIONE

1. Abrir: `https://defensacivil.com.ar/directorio.html`
2. Debés ver:
   - ✅ Barra de búsqueda arriba
   - ✅ Filtro por provincia
   - ✅ Estadísticas (contactos totales, visibles, provincias)
   - ✅ Listado de contactos

3. **Probar búsqueda:**
   - Escribir "Buenos Aires" → debería filtrar instantáneamente
   - Escribir "San Isidro" → debería mostrar solo ese municipio
   - Las coincidencias aparecen resaltadas en naranja

4. **Probar filtro:**
   - Seleccionar una provincia en el dropdown
   - Debería mostrar solo contactos de esa provincia

5. **Probar botón limpiar:**
   - Click en "🗑️ Limpiar filtros"
   - Debería resetear búsqueda y filtro

---

## 🎯 FUNCIONALIDADES

### 🔍 Búsqueda instantánea
- Busca en: provincia, municipio, localidad, denominación, domicilio
- Sin recargar la página
- Optimizada con debounce (300ms)

### 📊 Estadísticas dinámicas
- Contactos totales
- Contactos visibles (después de filtrar)
- Cantidad de provincias

### 🎯 Filtro por provincia
- Dropdown con todas las provincias
- Se combina con la búsqueda de texto

### ✨ Resaltado de coincidencias
- Las palabras que coinciden aparecen resaltadas

### 📱 Responsive
- Funciona perfecto en móvil
- Touch-friendly

---

## 🔧 PERSONALIZACIÓN

### Cambiar el placeholder de búsqueda

Editar directorio.html, línea ~73:

```html
<input 
  placeholder="🔍 Buscar por provincia, municipio..."
  <!-- Cambiar el texto del placeholder aquí -->
/>
```

### Cambiar el delay de búsqueda

Por defecto es 300ms. Para cambiarlo, editar línea ~236:

```javascript
searchInput.addEventListener('input', debounce(filterAndRender, 300));
// Cambiar 300 por otro número (en milisegundos)
```

### Agregar más campos buscables

Editar la función filterAndRender, línea ~154:

```javascript
const searchableText = [
  entry.provincia,
  entry.municipio,
  entry.localidad,
  entry.denominacion,
  entry.domicilio,
  // Agregar más campos aquí:
  // entry.email,
  // entry.telefono,
].join(' ').toLowerCase();
```

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### No aparecen los contactos / Dice "Cargando..."
**Causa:** No se puede cargar `/data/directorio.json`  
**Solución:**
1. Verificar que `/data/directorio.json` existe y es accesible
2. Abrir DevTools (F12) > Console
3. Ver si hay errores de CORS o 404

### La búsqueda no funciona
**Causa:** JavaScript no se está ejecutando  
**Solución:**
1. Abrir DevTools (F12) > Console
2. Ver si hay errores de JavaScript
3. Verificar que el archivo se subió completo (tiene el `<script>` al final)

### El filtro de provincia está vacío
**Causa:** El JSON no tiene provincias o hay error al cargar  
**Solución:**
1. Verificar que `/data/directorio.json` tiene entries con campo "provincia"
2. Ver Console para errores

---

## 🎨 VISTA PREVIA

### ANTES
- Lista estática
- Scroll manual
- Sin filtros
- Sin contador

### DESPUÉS
- 🔍 Búsqueda en tiempo real
- 📊 Estadísticas dinámicas
- 🎯 Filtro por provincia
- ✨ Resaltado de coincidencias
- 🗑️ Botón limpiar filtros

---

## 📱 COMPATIBILIDAD

Funciona en:
- ✅ Chrome/Edge (últimas 3 versiones)
- ✅ Firefox (últimas 3 versiones)
- ✅ Safari 14+
- ✅ Mobile browsers (iOS Safari, Chrome Android)

Usa JavaScript vanilla (ES6+), no requiere librerías externas.

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
