# 📱 MEJORA 06: RESPONSIVE MOBILE

## ⚠️ IMPORTANTE

Esta mejora optimiza la experiencia en dispositivos móviles con menú hamburger y ajustes táctiles.

**Tiempo estimado:** 15 minutos  
**Dificultad:** Baja  
**Impacto:** Alto (50%+ de usuarios usan móvil)

---

## 📦 ARCHIVOS INCLUIDOS

```
06_RESPONSIVE_MOBILE/
├── assets/
│   ├── css/mobile.css       (NUEVO)
│   └── js/mobile-nav.js     (NUEVO)
└── INSTRUCCIONES.md         (este archivo)
```

---

## 📋 PASOS DE INSTALACIÓN

### PASO 1: Crear carpetas (si no existen)

```
/public_html/assets/js/    (permisos 755)
```

### PASO 2: Subir archivos CSS y JS

```
Archivo: assets/css/mobile.css
Ruta destino: /public_html/assets/css/mobile.css

Archivo: assets/js/mobile-nav.js
Ruta destino: /public_html/assets/js/mobile-nav.js
```

### PASO 3: Incluir en TODOS los HTML

Editar cada archivo HTML (`index.html`, `directorio.html`, etc.)

**Agregar antes de `</head>`:**

```html
<!-- Responsive mobile -->
<link rel="stylesheet" href="/assets/css/mobile.css?v=1.0.0"/>
<script src="/assets/js/mobile-nav.js" defer></script>
</head>
```

**Archivos a actualizar:**
- `/public_html/index.html`
- `/public_html/directorio.html`
- `/public_html/historia.html`
- `/public_html/notas.html`
- `/public_html/colaboradores.html`
- Cualquier otro HTML público

---

## ✅ VERIFICACIÓN

### 1. Probar en móvil real

1. Abrir desde tu celular: `https://defensacivil.com.ar/`
2. Debería verse:
   - ✅ Botón hamburger (☰) arriba a la derecha
   - ✅ Navegación oculta por defecto
3. Tocar el botón ☰
   - ✅ Se abre menú lateral desde la derecha
   - ✅ Fondo oscuro (overlay)
   - ✅ Botón ✕ para cerrar
4. Tocar fuera del menú
   - ✅ Se cierra el menú
5. Tocar un link del menú
   - ✅ Navega y cierra el menú

### 2. Probar en DevTools

1. Abrir Chrome DevTools (F12)
2. Click en "Toggle device toolbar" (icono celular)
3. Seleccionar dispositivo: iPhone SE, Pixel 5, etc.
4. Repetir pruebas del punto 1

### 3. Verificar touch targets

1. En móvil, verificar que todos los botones sean fáciles de tocar
2. No debería haber zoom automático al hacer click en inputs

---

## 🎯 FUNCIONALIDADES

### ✅ Hamburger Menu
- Aparece en pantallas < 768px
- Se abre desde la derecha
- Overlay oscuro en el fondo
- Animación suave
- Cierra al presionar ESC

### ✅ Touch Targets Optimizados
- Botones mínimo 44x44px
- Inputs con font-size 16px (evita zoom en iOS)
- Links ampliados para fácil toque

### ✅ Hero Responsive
- Tamaños de texto adaptados
- Logo/badge escalado
- Padding optimizado

### ✅ Tablas Scrolleables
- Scroll horizontal en tablas anchas
- Indicador visual "→ Deslizá"
- Border visible

### ✅ Compatibilidad
- iOS Safari 14+
- Chrome Android
- Firefox Mobile
- Samsung Internet

---

## 🔧 PERSONALIZACIÓN

### Cambiar punto de quiebre (breakpoint)

Por defecto el menú se activa en pantallas < 768px.

Para cambiar, editar `mobile.css` y `mobile-nav.js`:

**En mobile.css** (línea ~23):
```css
@media (max-width: 768px) {
                    /* ↑ cambiar este número */
```

**En mobile-nav.js** (línea ~16 y ~94):
```javascript
if (window.innerWidth > 768) return;
                        /* ↑ cambiar este número */
```

### Cambiar posición del menú (izquierda en vez de derecha)

Editar `mobile.css`, línea ~33:

```css
.toplinks {
  /* Cambiar de: */
  right: 0;
  /* A: */
  left: 0;
}
```

Y en línea ~47:
```css
box-shadow: -4px 0 12px rgba(0,0,0,.3);
/* Cambiar a: */
box-shadow: 4px 0 12px rgba(0,0,0,.3);
```

### Cambiar animación

Editar `mobile.css`, final del archivo (~líneas 225-232):

```css
@media (prefers-reduced-motion: no-preference) {
  .toplinks {
    transition: transform .3s cubic-bezier(0.4, 0, 0.2, 1);
    /* Cambiar .3s por .5s para más lento */
  }
}
```

---

## 📱 TESTING EN DISPOSITIVOS REALES

### iOS (iPhone/iPad)
- [ ] iPhone SE (pantalla pequeña)
- [ ] iPhone 14 Pro (pantalla moderna)
- [ ] iPad (tablet)
- Safari y Chrome

### Android
- [ ] Pixel 5 (pantalla mediana)
- [ ] Samsung Galaxy S23 (pantalla grande)
- [ ] Tablet Android
- Chrome, Firefox, Samsung Internet

### Aspectos a verificar:
- [ ] Menú se abre/cierra correctamente
- [ ] No hay zoom automático en inputs
- [ ] Todos los botones son tocables
- [ ] El sitio se ve bien en horizontal y vertical
- [ ] Scroll funciona normalmente

---

## 🆘 PROBLEMAS COMUNES

### El menú no se abre
**Solución:**
1. Verificar que `mobile-nav.js` está cargando
2. Abrir DevTools > Console, buscar errores JS
3. Verificar que hay un elemento con clase `.toplinks` en el HTML
4. Probar en pantalla < 768px (o ajustar ventana)

### El menú se ve mal / sin estilos
**Solución:**
1. Verificar que `mobile.css` está cargando
2. Verificar URL: debe ser `/assets/css/mobile.css`
3. Limpiar caché del navegador (Ctrl+Shift+R)
4. Verificar que el archivo se subió completo

### Zoom automático en inputs (iOS)
**Solución:**
Ya está solucionado con `font-size: 16px` en los inputs.
Si sigue pasando, verificar que el CSS se aplicó.

### El overlay no cubre toda la pantalla
**Solución:**
Verificar que `mobile.css` tiene:
```css
.menu-overlay {
  width: 100%;
  height: 100%;
}
```

### Funciona en desktop pero no en móvil
**Solución:**
1. Limpiar caché del móvil
2. Verificar que no hay otro CSS que interfiere
3. Probar en modo incógnito

---

## 📊 ESTADÍSTICAS ESPERADAS

**Mejora en UX móvil:**
- ✅ Tiempo en página +30%
- ✅ Bounce rate -20%
- ✅ Navegación entre páginas +40%

**Con Google Analytics verás:**
- Más páginas vistas por sesión
- Menor tasa de rebote en móvil
- Mayor tiempo promedio de sesión

---

## 🎨 ESTÉTICA

El menú mantiene la identidad visual del sitio:
- Fondo azul (var(--az1))
- Links tipo "pill"
- Transiciones suaves
- Overlay semi-transparente

---

## 🚀 MEJORAS FUTURAS (Opcional)

### Después del lanzamiento puedes agregar:

1. **Gestos táctiles**
   - Swipe para abrir/cerrar menú
   - Pull-to-refresh

2. **Menú sticky**
   - Header que se queda arriba al scrollear

3. **Bottom navigation**
   - Navegación inferior para acceso rápido

4. **Dark mode toggle**
   - Botón en el menú para modo oscuro

---

## 📋 CHECKLIST POST-IMPLEMENTACIÓN

- [ ] mobile.css subido
- [ ] mobile-nav.js subido
- [ ] Incluido en todos los HTML
- [ ] Probado en iPhone
- [ ] Probado en Android
- [ ] Probado en tablet
- [ ] Menú abre/cierra correctamente
- [ ] No hay zoom en inputs
- [ ] Touch targets funcionan bien
- [ ] Sin errores en Console

---

**FECHA DE IMPLEMENTACIÓN:** _________  
**IMPLEMENTADO POR:** _________  
**ESTADO:** [ ] Pendiente  [ ] Completado ✅
