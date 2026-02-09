# 🔒 MEJORA 05: CSRF PROTECTION Y SEGURIDAD

## ⚠️ CRÍTICO - IMPLEMENTAR ANTES DEL LANZAMIENTO

Esta mejora completa la seguridad del admin con protección CSRF, rate limiting y logs.

**Tiempo estimado:** 15 minutos  
**Dificultad:** Media  
**Prioridad:** CRÍTICA (seguridad)

---

## 📦 ARCHIVOS INCLUIDOS

```
05_CSRF_PROTECTION/
├── admin/
│   ├── security.php         (NUEVO)
│   └── login.php            (REEMPLAZAR)
└── INSTRUCCIONES.md         (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Subir security.php

```
Archivo: admin/security.php
Ruta destino: /public_html/admin/security.php
```

### PASO 2: Incluir security.php en config.php

Editar `/public_html/admin/config.php`

Agregar DESPUÉS de la línea que dice `declare(strict_types=1);`:

```php
// Incluir funciones de seguridad
require_once __DIR__ . '/security.php';
```

Debería quedar así:

```php
<?php
declare(strict_types=1);

// Incluir funciones de seguridad
require_once __DIR__ . '/security.php';

// ... resto del archivo
```

### PASO 3: Reemplazar login.php

```
Archivo: admin/login.php
Ruta destino: /public_html/admin/login.php
Acción: REEMPLAZAR
```

### PASO 4: Verificar permisos de _storage

Asegurar que existe y es escribible:

```
/public_html/admin/_storage/ (permisos 755)
```

Si no existe, crearlo manualmente.

---

## ✅ VERIFICACIÓN

### 1. Probar Rate Limiting

1. Ir a: `https://defensacivil.com.ar/admin/login.php`
2. Intentar login con contraseña **incorrecta** 3 veces
3. Debería aparecer: **"Te quedan 2 intentos..."**
4. Intentar 2 veces más (total 5)
5. Debería bloquearse: **"Demasiados intentos..."**
6. Esperar 5 minutos y probar de nuevo (o limpiar el archivo de rate limits)

### 2. Verificar Logs

Abrir (vía FTP o File Manager):
```
/public_html/admin/_storage/security.log
```

Deberías ver entradas JSON como:

```json
{"timestamp":"2026-02-02 15:30:45","type":"login_fail","message":"Credenciales inválidas","ip":"xxx.xxx.xxx.xxx","user_agent":"Mozilla/5.0...","user":"anonymous","context":{"username":"test"}}
```

### 3. Probar Login Exitoso

1. Limpiar rate limit: borrar `/admin/_storage/rate_limits.json`
2. Ingresar con credenciales correctas
3. Debería funcionar normalmente
4. Verificar en `security.log` el evento `login_success`

---

## 🎯 FUNCIONALIDADES

### ✅ Rate Limiting
- Máximo 5 intentos de login por hora por IP
- Se reinicia automáticamente después de 1 hora
- Muestra intentos restantes al usuario

### ✅ CSRF Protection Mejorado
- Tokens únicos por formulario
- Expiran en 1 hora
- Uso único (se eliminan tras usar)

### ✅ Security Logs
- Registra todos los eventos de seguridad
- Login exitoso/fallido
- CSRF fails
- Rate limit exceeded
- Incluye IP, user-agent, timestamp

### ✅ Headers de Seguridad
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Content-Security-Policy básico

### ✅ Validación de Inputs
- sanitize_text()
- sanitize_email()
- sanitize_html()
- validate_url()

### ✅ Honeypot Anti-Bots
- Campo invisible para detectar bots
- Ya implementado en `colaboradores/alta.php`

---

## 🔧 USO DE LAS FUNCIONES

### En formularios PHP:

```php
// Verificar rate limiting
if (!check_rate_limit('nombre_accion', 5, 3600)) {
  die('Demasiados intentos');
}

// Generar token CSRF específico
$token = csrf_token_for('mi_formulario');

// En el HTML
<input type="hidden" name="csrf" value="<?= $token ?>"/>

// Verificar al procesar
if (!csrf_check_for($_POST['csrf'], 'mi_formulario')) {
  die('CSRF inválido');
}

// Sanitizar inputs
$email = sanitize_email($_POST['email']);
$nombre = sanitize_text($_POST['nombre'], 100);

// Log de eventos
log_security_event('user_action', 'Usuario hizo algo', [
  'extra' => 'data'
]);
```

---

## 🚨 AJUSTAR LÍMITES (Opcional)

### Cambiar intentos de login

Editar `login.php`, línea ~30:

```php
elseif (!check_rate_limit('login', 5, 3600)) {
                            // ↑   ↑
                         max    tiempo
```

Cambiar:
- `5` = máximo de intentos
- `3600` = ventana de tiempo en segundos (3600 = 1 hora)

Ejemplos:
- `check_rate_limit('login', 3, 1800)` = 3 intentos cada 30 min
- `check_rate_limit('login', 10, 7200)` = 10 intentos cada 2 horas

### Aplicar a otros formularios

En `/colaboradores/alta.php`, agregar ANTES de procesar el POST:

```php
if (!check_rate_limit('alta_colaborador', 3, 3600)) {
  $error = 'Demasiados intentos. Esperá 1 hora.';
}
```

---

## 📊 MONITOREO

### Ver logs en tiempo real (SSH/terminal)

```bash
tail -f /public_html/admin/_storage/security.log
```

### Analizar logs

```bash
# Contar login fallidos
grep 'login_fail' security.log | wc -l

# Ver IPs bloqueadas
grep 'rate_limit_exceeded' security.log

# Últimos 10 eventos
tail -10 security.log
```

### Rotar logs (automático)

Los logs se rotan automáticamente cuando superan 5MB.
Se crea backup con timestamp: `security.log.20260202153045.old`

---

## 🆘 PROBLEMAS COMUNES

### Error: "Call to undefined function check_rate_limit()"
**Solución:**
1. Verificar que `security.php` está en `/admin/security.php`
2. Verificar que está incluido en `config.php`
3. Verificar sintaxis (no debe haber errores PHP)

### Rate limit no funciona
**Solución:**
1. Verificar que `/admin/_storage/` tiene permisos 755
2. Verificar que PHP puede escribir en esa carpeta
3. Probar crear archivo manualmente para verificar permisos

### Logs no se crean
**Solución:**
1. Verificar permisos de `/admin/_storage/`
2. Verificar que no hay errores PHP (activar display_errors temporalmente)
3. Llamar manualmente: `log_security_event('test', 'Prueba');`

### Bloqueado permanentemente
**Solución temporal:**
1. Borrar: `/admin/_storage/rate_limits.json`
2. O editar y cambiar `expires` a un timestamp pasado

**Solución permanente:**
- Aumentar el límite o tiempo de ventana

---

## 🔐 SEGURIDAD ADICIONAL (Recomendado)

### 1. Cambiar nombre de carpeta admin (opcional)

```
/admin/ → /panel-dc-2026/
```

Luego actualizar todas las rutas en el código.

### 2. IP Whitelist para admin (opcional)

En `/admin/.htaccess`, agregar:

```apache
# Solo permitir tu IP
Order Deny,Allow
Deny from all
Allow from TU.IP.AQUI.XXX
```

### 3. Autenticación de dos factores (avanzado)

Implementar después del lanzamiento con librerías como Google Authenticator.

---

## 📋 CHECKLIST POST-IMPLEMENTACIÓN

- [ ] security.php subido
- [ ] Incluido en config.php
- [ ] login.php reemplazado
- [ ] Rate limiting testeado (5 intentos)
- [ ] Logs creándose correctamente
- [ ] Login exitoso funciona
- [ ] Probado en producción
- [ ] Documentado para el equipo

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
