/**
 * Mobile Navigation (Hamburger Menu)
 * DefensaCivil.com.ar
 * 
 * INSTRUCCIONES:
 * 1. Subir a /assets/js/mobile-nav.js
 * 2. Incluir en el <head> o antes de </body>: 
 *    <script src="/assets/js/mobile-nav.js" defer></script>
 */

(function() {
  'use strict';
  
  // Esperar a que el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  function init() {
    // Verificar si estamos en móvil
    if (window.innerWidth > 768) return;
    
    const header = document.querySelector('header');
    const toplinks = document.querySelector('.toplinks');
    
    if (!header || !toplinks) return;
    
    // Crear botón hamburger
    const menuToggle = document.createElement('button');
    menuToggle.className = 'menu-toggle';
    menuToggle.setAttribute('aria-label', 'Abrir menú');
    menuToggle.innerHTML = '☰';
    
    // Crear botón cerrar
    const menuClose = document.createElement('button');
    menuClose.className = 'menu-close';
    menuClose.setAttribute('aria-label', 'Cerrar menú');
    menuClose.innerHTML = '✕';
    
    // Crear overlay
    const overlay = document.createElement('div');
    overlay.className = 'menu-overlay';
    
    // Insertar elementos
    const head = header.querySelector('.head');
    if (head) {
      head.appendChild(menuToggle);
    }
    toplinks.insertBefore(menuClose, toplinks.firstChild);
    document.body.appendChild(overlay);
    
    // Funciones
    function openMenu() {
      toplinks.classList.add('active');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevenir scroll
    }
    
    function closeMenu() {
      toplinks.classList.remove('active');
      overlay.classList.remove('active');
      document.body.style.overflow = ''; // Restaurar scroll
    }
    
    // Event listeners
    menuToggle.addEventListener('click', openMenu);
    menuClose.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    
    // Cerrar al hacer click en un link
    const links = toplinks.querySelectorAll('a');
    links.forEach(link => {
      link.addEventListener('click', closeMenu);
    });
    
    // Cerrar con tecla ESC
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && toplinks.classList.contains('active')) {
        closeMenu();
      }
    });
    
    // Manejar cambio de tamaño de ventana
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) {
        closeMenu();
      }
    });
  }
})();
