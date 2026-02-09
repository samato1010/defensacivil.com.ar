# 🔧 ARCHIVOS CORREGIDOS - Notas y Colaboradores

## 🐛 PROBLEMA DETECTADO

Los archivos anteriores no cargaban datos porque:

❌ **notas.html** - Faltaba manejo de errores y debugging  
❌ **colaboradores.html** - Buscaba `/data/colaboradores_public.json` que no existe  

## ✅ SOLUCIÓN

Estos archivos corregidos:

✅ **notas.html** - Ahora muestra errores claros y tiene mejor debugging  
✅ **colaboradores.html** - Busca primero `colaboradores_public.json`, si no existe usa `colaboradores.json`  
✅ **historia.html** - Sin cambios (ya funcionaba)  

---

## 📦 ARCHIVOS INCLUIDOS

```
archivos_corregidos/
├── notas.html              (CORREGIDO)
├── colaboradores.html      (CORREGIDO)
├── historia.html           (igual que antes)
└── INSTRUCCIONES.md        (este archivo)
```

---

## 📋 INSTALACIÓN

### PASO 1: Reemplazar los archivos

```
Archivo: notas.html
Ruta destino: /public_html/notas.html
Acción: REEMPLAZAR

Archivo: colaboradores.html
Ruta destino: /public_html/colaboradores.html
Acción: REEMPLAZAR

Archivo: historia.html (opcional)
Ruta destino: /public_html/historia.html
Acción: REEMPLAZAR
```

---

## 🔍 DEBUGGING

### Para ver por qué no cargan los datos:

1. Abrir la página en Chrome/Firefox
2. Presionar **F12** (DevTools)
3. Ir a pestaña **Console**
4. Recargar la página (F5)
5. Ver los mensajes:

**Mensajes que deberías ver:**

✅ **Si funciona:**
```
Datos cargados: {meta: {...}, notes: Array(2)}
```

❌ **Si hay error:**
```
Error cargando notas: HTTP 404: Not Found
```

Esto te dirá exactamente qué está fallando.

---

## 🔧 SOLUCIONES SEGÚN EL ERROR

### Error: "HTTP 404: Not Found"

**Causa:** El archivo `/data/notas.json` o `/data/colaboradores.json` no existe.

**Solución:**
1. Verificar que existe: `/public_html/data/notas.json`
2. Verificar que existe: `/public_html/data/colaboradores.json`
3. Si no existen, crearlos (ver sección de JSONs de ejemplo abajo)

---

### Error: "Unexpected token..."

**Causa:** El JSON tiene errores de sintaxis.

**Solución:**
1. Abrir el archivo JSON problemático
2. Verificar con un validador: https://jsonlint.com/
3. Corregir errores de sintaxis (comas faltantes, llaves sin cerrar, etc.)

---

### Error: "CORS policy..."

**Causa:** Estás probando desde `file://` en vez de `http://`

**Solución:**
- Subir los archivos al servidor y probar desde: `https://defensacivil.com.ar/notas.html`
- NO probar abriendo los archivos directamente desde tu PC

---

### No aparecen datos pero tampoco hay error

**Causa:** El JSON está vacío o tiene estructura incorrecta.

**Solución:**
1. Abrir la Console (F12)
2. Ver el mensaje: `Datos cargados: {...}`
3. Verificar que tiene `notes: []` o `collaborators: []`
4. Si están vacíos, agregar datos (ver ejemplos abajo)

---

## 📄 EJEMPLOS DE JSONs

### /data/notas.json (mínimo)

```json
{
  "meta": {
    "updated": "2026-02-02"
  },
  "notes": [
    {
      "id": "n-001",
      "fecha": "2026-02-02",
      "provincia": "Buenos Aires",
      "categoria": "Evento",
      "titulo": "Capacitación en Primeros Auxilios",
      "texto": "Se realizó una jornada de capacitación en primeros auxilios para voluntarios de Defensa Civil.",
      "imagen": "",
      "links": []
    }
  ]
}
```

### /data/colaboradores.json (estructura actual)

```json
{
  "meta": {
    "titulo": "Colaboradores",
    "updated": "2026-02-02"
  },
  "collaborators": [
    {
      "id": "c-001",
      "nombre": "Juan Pérez",
      "email": "juan@ejemplo.com",
      "telefono": "011 1234-5678",
      "provincia": "Buenos Aires",
      "localidad": "La Plata",
      "nivel": "provincial",
      "rol": "Colaborador provincial",
      "estado": "Activo"
    }
  ],
  "items": []
}
```

**Nota:** Si ya tenés `items[]` con solicitudes, el JavaScript filtrará solo los aprobados y los mostrará automáticamente.

---

## ✅ CHECKLIST DE VERIFICACIÓN

- [ ] Archivos reemplazados en el servidor
- [ ] Abrir notas.html en navegador
- [ ] Abrir Console (F12)
- [ ] Ver si hay errores en Console
- [ ] Si hay error, seguir soluciones de arriba
- [ ] Verificar que `/data/notas.json` existe y es válido
- [ ] Verificar que `/data/colaboradores.json` existe y es válido
- [ ] Probar desde `https://` (no desde `file://`)
- [ ] Si funciona, marcar como ✅

---

## 🎯 RESULTADO ESPERADO

**notas.html:**
- Muestra las 2 notas que tenés en el JSON
- O muestra "Sin notas por el momento" si está vacío
- O muestra error claro con botón "Reintentar"

**colaboradores.html:**
- Muestra colaboradores aprobados
- O muestra "Próximamente..." si no hay ninguno
- O muestra error claro con botón "Reintentar"

---

## 💡 TIPS

1. **Siempre usar la Console (F12)** - Es tu mejor amigo para debugging
2. **Validar JSONs** - Usar https://jsonlint.com/ antes de subir
3. **Probar en servidor** - No desde archivos locales
4. **Cache** - Si no ves cambios, limpiar con Ctrl+Shift+R

---

## 🆘 SI SIGUE SIN FUNCIONAR

Avisame y decime:

1. ¿Qué dice la Console? (copiar el error exacto)
2. ¿Los archivos JSON existen en tu servidor?
3. ¿Qué estructura tienen? (primeras 20 líneas)

Y te ayudo a solucionarlo específicamente.

---

**FECHA:** 2026-02-02  
**VERSIÓN:** CORREGIDA v2  
**ESTADO:** [ ] Instalado [ ] Funcionando ✅
