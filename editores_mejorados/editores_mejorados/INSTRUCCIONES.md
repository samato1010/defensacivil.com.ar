# 📝 EDITORES MEJORADOS - Panel Admin

## 📦 CONTENIDO

Versiones mejoradas de los editores principales con:

✅ CSS unificado (admin.css)  
✅ Seguridad mejorada (CSRF + logs)  
✅ Búsqueda y filtros en tiempo real  
✅ Validación de inputs  
✅ Mejor UX y feedback visual  
✅ Responsive mobile  

```
editores_mejorados/
├── notas_editor.php         (REEMPLAZAR)
├── directorio_editor.php    (REEMPLAZAR)
└── INSTRUCCIONES.md         (este archivo)
```

---

## 📋 INSTALACIÓN

### REQUISITOS PREVIOS

Antes de instalar estos editores, necesitás tener:

- ✅ `/assets/css/admin.css` (del paquete admin_mejorado)
- ✅ `/admin/security.php` (del paquete mejora_05_csrf_protection)
- ✅ Mejora 01 (seguridad) implementada

Si no los tenés, instalá primero esos paquetes.

---

### PASO 1: Reemplazar editores

```
Archivo: notas_editor.php
Ruta destino: /public_html/admin/notas_editor.php
Acción: REEMPLAZAR

Archivo: directorio_editor.php
Ruta destino: /public_html/admin/directorio_editor.php
Acción: REEMPLAZAR
```

**Importante:** Hacer backup de los archivos originales antes de reemplazar.

---

## ✅ VERIFICACIÓN

### Editor de Notas

1. Ir a: `https://defensacivil.com.ar/admin/notas_editor.php`
2. Verificar que:
   - ✅ Se ve con estilos correctos
   - ✅ Formulario para crear nota funciona
   - ✅ Se pueden subir imágenes
   - ✅ Se pueden agregar hasta 3 links
   - ✅ Lista de notas se muestra correctamente
   - ✅ Botón "Editar" funciona
   - ✅ Botón "Eliminar" pide confirmación

### Editor de Directorio

1. Ir a: `https://defensacivil.com.ar/admin/directorio_editor.php`
2. Verificar que:
   - ✅ Se ve con estilos correctos
   - ✅ Búsqueda en tiempo real funciona
   - ✅ Filtro por provincia funciona
   - ✅ Formulario completo con todos los campos
   - ✅ Autocompletado de provincias
   - ✅ Validación de emails y URLs
   - ✅ Editar y eliminar funcionan

---

## 🎯 MEJORAS INCLUIDAS

### Seguridad 🔒

**ANTES:**
- CSRF básico
- Sin logs
- Sin sanitización completa

**DESPUÉS:**
- ✅ CSRF tokens únicos por formulario
- ✅ Logs de todas las acciones (security.log)
- ✅ Sanitización con funciones dedicadas
- ✅ Validación de URLs y emails
- ✅ Headers de seguridad HTTP

### Funcionalidad 💪

**Notas Editor:**
- ✅ Upload de imágenes con preview
- ✅ Hasta 3 links por nota
- ✅ Categorías predefinidas
- ✅ Contador de caracteres
- ✅ Ordenamiento por fecha
- ✅ Edición inline

**Directorio Editor:**
- ✅ Búsqueda en tiempo real
- ✅ Filtro por provincia
- ✅ Autocompletado de provincias
- ✅ Validación de campos
- ✅ Grid layout para campos relacionados
- ✅ Contador de resultados

### UX/UI ✨

- ✅ Mensajes de éxito/error claros
- ✅ Botones con iconos descriptivos
- ✅ Confirmación antes de eliminar
- ✅ Formulario con hints
- ✅ Responsive mobile
- ✅ Loading states
- ✅ Feedback visual inmediato

---

## 🔧 USO

### Crear una Nota

1. Ir al editor de notas
2. Completar el formulario:
   - **Fecha:** Seleccionar del calendario
   - **Provincia:** Escribir el nombre
   - **Categoría:** Elegir del dropdown
   - **Título:** Máximo 200 caracteres
   - **Texto:** Máximo 2000 caracteres
   - **Imagen:** Subir JPG, PNG, GIF o WebP
   - **Links:** Hasta 3 links con texto personalizado
3. Click en "➕ Crear nota"
4. ✅ Aparece mensaje de confirmación

### Editar una Nota

1. En la lista, click en "✏️ Editar"
2. Modificar los campos necesarios
3. Click en "💾 Guardar cambios"

### Eliminar una Nota

1. Click en "🗑️"
2. Confirmar en el popup
3. ✅ Nota eliminada

### Buscar en Directorio

1. Escribir en el campo de búsqueda
2. Los resultados se filtran automáticamente
3. O usar el filtro por provincia
4. Contador se actualiza en tiempo real

---

## 📊 DATOS Y VALIDACIÓN

### Notas (notas.json)

```json
{
  "meta": {
    "updated": "2026-02-02 15:30:00"
  },
  "notes": [
    {
      "id": "n-2026-02-02-001",
      "fecha": "2026-02-02",
      "provincia": "Buenos Aires",
      "categoria": "Evento",
      "titulo": "Capacitación en Primeros Auxilios",
      "texto": "Descripción del evento...",
      "imagen": "nota_abc123.jpg",
      "links": [
        {
          "href": "https://ejemplo.com",
          "label": "Ver más información"
        }
      ]
    }
  ]
}
```

### Directorio (directorio.json)

```json
{
  "meta": {
    "titulo": "Directorio",
    "updated": "2026-02-02 15:30:00"
  },
  "entries": [
    {
      "id": "d-abc123",
      "provincia": "Buenos Aires",
      "municipio": "La Plata",
      "localidad": "",
      "denominacion": "Defensa Civil Municipal",
      "domicilio": "Calle 12 n° 1234",
      "telefono": "0221 1234-5678",
      "email": "defensacivil@laplata.gov.ar",
      "website": "https://laplata.gov.ar/defensacivil",
      "instagram": "",
      "facebook": "",
      "comentario": ""
    }
  ]
}
```

---

## 🔐 LOGS DE SEGURIDAD

Todas las acciones quedan registradas en:

```
/public_html/admin/_storage/security.log
```

Ejemplo de log:

```json
{
  "timestamp": "2026-02-02 15:30:45",
  "type": "nota_saved",
  "message": "Nota guardada: Capacitación en Primeros Auxilios",
  "ip": "200.123.45.67",
  "user_agent": "Mozilla/5.0...",
  "user": "admin",
  "context": {}
}
```

### Ver logs

```bash
# Últimas 20 líneas
tail -20 /public_html/admin/_storage/security.log

# Buscar acciones específicas
grep "nota_saved" security.log
grep "directorio_deleted" security.log
```

---

## 🆘 PROBLEMAS COMUNES

### Error: "require_once security.php failed"

**Causa:** No existe `/admin/security.php`  
**Solución:**
1. Instalar primero la Mejora 05 (CSRF Protection)
2. Subir `security.php` a `/admin/security.php`

### Error: "Call to undefined function csrf_token()"

**Causa:** Falta incluir `security.php` en `config.php`  
**Solución:**
1. Editar `/admin/config.php`
2. Agregar: `require_once __DIR__ . '/security.php';`

### No se suben las imágenes

**Causa:** Carpeta `/uploads/notas/` no existe o sin permisos  
**Solución:**
1. Crear carpeta: `/public_html/uploads/notas/`
2. Permisos: `chmod 755 uploads/notas`
3. Verificar que PHP puede escribir

### La búsqueda no funciona

**Causa:** JavaScript no se carga  
**Solución:**
1. Abrir Console (F12)
2. Ver errores JavaScript
3. Verificar que el HTML se cargó completo

### Sin estilos / se ve feo

**Causa:** No encuentra `admin.css`  
**Solución:**
1. Verificar que `/assets/css/admin.css` existe
2. Limpiar caché (Ctrl+Shift+R)

---

## 📱 RESPONSIVE

Los editores son completamente responsivos:

- ✅ Desktop (1200px+) - Layout completo
- ✅ Tablet (768px-1200px) - Grid adaptado
- ✅ Mobile (<768px) - Columna única

Probar en móvil con DevTools (F12) > Toggle device toolbar

---

## 🎨 PERSONALIZACIÓN

### Cambiar límite de caracteres

Editar el archivo PHP correspondiente:

```php
// En notas_editor.php, línea ~88
$titulo = sanitize_text($_POST['titulo'] ?? '', 200); // Cambiar 200
$texto = sanitize_text($_POST['texto'] ?? '', 2000); // Cambiar 2000
```

Y actualizar el HTML:

```html
<input type="text" name="titulo" maxlength="200"/> <!-- Cambiar aquí también -->
```

### Agregar más categorías

En `notas_editor.php`, línea ~151:

```html
<select name="categoria">
  <option value="">Sin categoría</option>
  <option value="Evento">Evento</option>
  <option value="Nueva">Nueva Categoría</option> <!-- Agregar aquí -->
</select>
```

### Cambiar cantidad de links

En `notas_editor.php`, cambiar el bucle:

```php
<?php for ($i = 1; $i <= 5; $i++): ?> <!-- Cambiar de 3 a 5 -->
```

---

## 📋 CHECKLIST

- [ ] `security.php` instalado
- [ ] `admin.css` instalado
- [ ] `notas_editor.php` reemplazado
- [ ] `directorio_editor.php` reemplazado
- [ ] Carpeta `/uploads/notas/` creada
- [ ] Permisos verificados (755)
- [ ] Editor de notas funciona
- [ ] Editor de directorio funciona
- [ ] Búsqueda en tiempo real OK
- [ ] Upload de imágenes OK
- [ ] Logs funcionando
- [ ] Responsive mobile OK

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**ESTADO:** [ ] Pendiente [ ] Completado ✅
