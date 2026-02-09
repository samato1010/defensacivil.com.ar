# 🔐 MEJORA 01: SEPARACIÓN DE CREDENCIALES

## ⚠️ IMPORTANTE - LEÉ ANTES DE EMPEZAR

Esta mejora separa los datos públicos de los privados para evitar que credenciales, IPs y datos sensibles queden expuestos en archivos JSON accesibles públicamente.

**Tiempo estimado:** 15-20 minutos  
**Dificultad:** Media  
**Riesgo:** Bajo (se crean backups automáticos)

---

## 📦 ARCHIVOS INCLUIDOS

En la carpeta `mejora_01_seguridad/` encontrarás:

```
mejora_01_seguridad/
├── admin/
│   ├── _storage/
│   │   └── .htaccess                        (NUEVO - protege el directorio)
│   ├── config.php                           (REEMPLAZAR el existente)
│   ├── login.php                            (REEMPLAZAR el existente)
│   └── migracion_colaboradores.php          (NUEVO - script de migración)
└── colaboradores/
    └── alta.php                             (REEMPLAZAR el existente)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Subir archivos al servidor

Conectate a tu hosting de Hostinger (por hPanel > File Manager o FTP):

#### 1.1 Crear el directorio protegido
```
Ruta: /public_html/admin/_storage/
```
- Crear la carpeta `_storage` dentro de `/public_html/admin/`
- Subir el archivo `.htaccess` a `/public_html/admin/_storage/.htaccess`

#### 1.2 Subir el script de migración
```
Archivo: migracion_colaboradores.php
Ruta destino: /public_html/admin/migracion_colaboradores.php
```

---

### PASO 2: Ejecutar la migración

1. Abrir en tu navegador:
   ```
   https://defensacivil.com.ar/admin/migracion_colaboradores.php
   ```

2. Verás una pantalla con logs verdes (✅). Si todo sale bien:
   - Se crea `/data/colaboradores_public.json` (SIN credenciales)
   - Se crea `/admin/_storage/colaboradores_private.json` (CON credenciales, protegido)
   - Se crea un backup del archivo original

3. **IMPORTANTE:** Una vez que veas "✅ Migración Exitosa", BORRAR el archivo:
   ```
   Borrar: /public_html/admin/migracion_colaboradores.php
   ```

---

### PASO 3: Reemplazar archivos PHP

Ahora que los datos están separados, hay que actualizar el código para que use los nuevos archivos:

#### 3.1 Reemplazar config.php
```
Archivo local: mejora_01_seguridad/admin/config.php
Ruta destino: /public_html/admin/config.php
Acción: REEMPLAZAR (sobrescribir el existente)
```

#### 3.2 Reemplazar login.php
```
Archivo local: mejora_01_seguridad/admin/login.php
Ruta destino: /public_html/admin/login.php
Acción: REEMPLAZAR (sobrescribir el existente)
```

#### 3.3 Reemplazar alta.php
```
Archivo local: mejora_01_seguridad/colaboradores/alta.php
Ruta destino: /public_html/colaboradores/alta.php
Acción: REEMPLAZAR (sobrescribir el existente)
```

---

### PASO 4: Probar que funcione

#### 4.1 Probar el login
1. Ir a: `https://defensacivil.com.ar/admin/login.php`
2. Intentar ingresar con tu usuario admin
3. Debería funcionar normalmente

#### 4.2 Probar alta de colaborador
1. Ir a: `https://defensacivil.com.ar/colaboradores/alta.php`
2. Completar el formulario de prueba
3. Verificar que se guarde correctamente

#### 4.3 Verificar archivos protegidos
1. Intentar acceder desde navegador:
   ```
   https://defensacivil.com.ar/admin/_storage/colaboradores_private.json
   ```
2. **Deberías ver un error 403 Forbidden** ✅ (esto es correcto, significa que está protegido)

#### 4.4 Verificar archivo público
1. Acceder desde navegador:
   ```
   https://defensacivil.com.ar/data/colaboradores_public.json
   ```
2. **Deberías ver solo el JSON con colaboradores SIN credenciales** ✅

---

## ✅ CHECKLIST DE VERIFICACIÓN

Marcá cada item cuando lo completes:

- [ ] Creada carpeta `/public_html/admin/_storage/`
- [ ] Subido `.htaccess` a `_storage/`
- [ ] Ejecutado script de migración exitosamente
- [ ] Borrado archivo `migracion_colaboradores.php`
- [ ] Reemplazado `/admin/config.php`
- [ ] Reemplazado `/admin/login.php`
- [ ] Reemplazado `/colaboradores/alta.php`
- [ ] Probado login admin (funciona ✅)
- [ ] Probado alta de colaborador (funciona ✅)
- [ ] Verificado que `_storage/colaboradores_private.json` da 403 ✅
- [ ] Verificado que `data/colaboradores_public.json` es accesible pero SIN credenciales ✅

---

## 🔍 ¿QUÉ CAMBIÓ?

### ANTES (inseguro)
```
/data/colaboradores.json
├── meta
├── collaborators[]
└── items[]  ← ⚠️ Contiene pass_hash, IPs, user-agents (PÚBLICO)
```

### DESPUÉS (seguro)
```
/data/colaboradores_public.json
├── meta
└── collaborators[]  ← ✅ Solo nombres, emails, provincias (PÚBLICO)

/admin/_storage/colaboradores_private.json  (protegido por .htaccess)
├── meta
└── items[]  ← 🔒 Contiene pass_hash, IPs, user-agents (PRIVADO)
```

---

## 🆘 SOLUCIÓN DE PROBLEMAS

### Problema: No puedo acceder al admin después de reemplazar archivos
**Solución:** 
1. Verificá que el archivo `/admin/_storage/colaboradores_private.json` exista
2. Verificá que tenga contenido (no esté vacío)
3. Si sigue sin funcionar, restaurá el config.php original temporalmente

### Problema: El script de migración da error
**Solución:**
1. Verificá que `/data/colaboradores.json` exista
2. Verificá permisos de escritura en `/data/` y `/admin/_storage/`
3. En hPanel, los permisos deberían ser 755 para carpetas y 644 para archivos

### Problema: Puedo acceder a colaboradores_private.json desde el navegador
**Solución:**
1. Verificá que el `.htaccess` esté en `/admin/_storage/.htaccess`
2. Verificá que el archivo empiece con "Order deny,allow" (sin espacios extra)
3. En Hostinger, el .htaccess debería funcionar por defecto

---

## 📞 CONTACTO

Si tenés algún problema con la implementación, avisame y te ayudo a resolverlo.

---

## 🗑️ LIMPIEZA (OPCIONAL)

Una vez que todo funcione correctamente durante unos días, podés:

1. **Hacer backup del archivo original:**
   ```
   Renombrar: /data/colaboradores.json 
   A: /data/colaboradores.json.backup_2026
   ```

2. **Limpiar backups viejos** de `/admin/_backup/` (dejar solo los últimos 5)

---

**FECHA DE IMPLEMENTACIÓN:** _________
**IMPLEMENTADO POR:** _________
**ESTADO:** [ ] Pendiente  [ ] En progreso  [ ] Completado ✅
