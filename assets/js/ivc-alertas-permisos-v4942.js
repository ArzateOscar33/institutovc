/* Instituto Visual California | Helper global para alertas de acceso, Fase 4.9.4.2.
 * Intercepta acciones de la interfaz y respuestas HTTP 401/403 de la aplicación.
 * IMPORTANTE: Los permisos de este archivo solo mejoran la UX. RolesAcceso.php
 * es la fuente de verdad y debe bloquear SIEMPRE cada ruta y operación.
 */
(() => {
  'use strict';
  if (window.IVCPermisos) return;
  const permisos = Object.freeze(Object.assign({}, window.IVC_PERMISOS_ADMIN || {}));
  let ultimoAviso = 0;
  const rutaApp = (() => {
    // Definida por el footer antes de cargar este script (sin depender de base_url).
    const base = typeof window.IVC_APP_BASE === 'string' ? window.IVC_APP_BASE : '/';
    try {
      const url = new URL(base, window.location.href);
      return url.origin === window.location.origin ? url.pathname : '/';
    } catch (_) { return '/'; }
  })();

  function esRutaPropia(uri) {
    if (!uri) return false;
    try {
      const url = new URL(uri, window.location.href);
      return url.origin === location.origin && url.pathname.startsWith(rutaApp);
    } catch (_) { return false; }
  }
  function tiene(codigo) {
    return Object.prototype.hasOwnProperty.call(permisos, codigo) && permisos[codigo] === true;
  }
  function aviso(tipo = 'permiso') {
    // Varios endpoints pueden fallar simultáneamente: se muestra solo una alerta.
    const ahora = Date.now();
    if (ahora - ultimoAviso < 1200) return Promise.resolve();
    ultimoAviso = ahora;
    const sesion = tipo === 'sesion';
    const titulo = sesion ? 'Sesión no disponible' : 'No tienes permisos';
    const texto = sesion
      ? 'Tu sesión ha expirado. Inicia sesión nuevamente para continuar.'
      : 'Tu cuenta no tiene autorización para realizar esta acción. Solicita acceso a un administrador si lo necesitas.';
    if (window.Swal && typeof Swal.fire === 'function') {
      return Swal.fire({
        icon: sesion ? 'info' : 'warning',
        title: titulo, text: texto,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#1568a6',
        buttonsStyling: true,
        customClass: { popup: 'ivc-alerta-permisos' }
      });
    }
    window.alert(titulo + '\n' + texto);
    return Promise.resolve();
  }
  function requerir(codigo) {
    if (tiene(codigo)) return true;
    aviso('permiso');
    return false;
  }

  // API pública para futuras pantallas: IVCPermisos.tiene/requerir/aviso.
  window.IVCPermisos = Object.freeze({ tiene, requerir, aviso });

  // Previene la acción ANTES de que Bootstrap y el onclick del módulo la ejecuten.
  document.addEventListener('click', (event) => {
    const boton = event.target && event.target.closest
      ? event.target.closest('[data-ivc-permiso]') : null;
    if (!boton || !boton.isConnected) return;
    const codigo = boton.getAttribute('data-ivc-permiso');
    if (codigo && !tiene(codigo)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      event.stopPropagation();
      aviso('permiso');
    }
  }, true);

  // Captura respuestas 403 silenciosas de XMLHttpRequest (p.ej. módulos heredados).
  const xhrOpen = XMLHttpRequest.prototype.open;
  const xhrSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method, url, ...args) {
    this.__ivcUrl = url;
    return xhrOpen.call(this, method, url, ...args);
  };
  XMLHttpRequest.prototype.send = function (...args) {
    this.addEventListener('loadend', () => {
      if (!esRutaPropia(this.responseURL || this.__ivcUrl)) return;
      if (this.status === 403) aviso('permiso');
      else if (this.status === 401) aviso('sesion');
    }, { once: true });
    return xhrSend.apply(this, args);
  };

  // Lo mismo para fetch(), sin consumir el body ni modificar el Response.
  if (typeof window.fetch === 'function') {
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
      const peticion = args[0];
      const url = typeof peticion === 'string' || peticion instanceof URL
        ? String(peticion) : (peticion && peticion.url);
      return originalFetch.apply(this, args).then((response) => {
        if (esRutaPropia(response.url || url)) {
          if (response.status === 403) aviso('permiso');
          else if (response.status === 401) aviso('sesion');
        }
        return response;
      });
    };
  }
})();
