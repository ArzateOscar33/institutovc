/* IVC · Cart Drawer 9.1 — integración robusta con carrito.js original.
 * No cambia los datos guardados, los endpoints, precios ni flujo de compra.
 */
(function () {
    'use strict';
    if (window.__IVCCartDrawer91) return;
    window.__IVCCartDrawer91 = true;

    const drawer = document.getElementById('myModal');
    if (!drawer || !drawer.classList.contains('ivc-cart-drawer')) {
        console.warn('[IVC carrito] Falta el Offcanvas V9 en footer.php.');
        return;
    }

    const trigger = document.getElementById('verCarrito');
    const empty = document.getElementById('ivcCartEmpty');
    const list = document.getElementById('ivcCartList');
    const count = document.getElementById('ivcCartItemCount');
    const live = document.getElementById('ivcCartLive');
    const tbody = drawer.querySelector('#tableListaCarrito tbody');

    function items() {
        try {
            const value = JSON.parse(localStorage.getItem('listaCarrito') || '[]');
            return Array.isArray(value) ? value : [];
        } catch (_) {
            return [];
        }
    }

    function sync() {
        const total = items().length + (window.IVCCart43 ? window.IVCCart43.countConfigured() : 0);
        if (count) count.textContent = String(total);
        if (empty) empty.hidden = total > 0;
        if (list) list.hidden = total === 0;
        drawer.classList.toggle('is-empty', total === 0);
    }

    function getOffcanvas() {
        if (!window.bootstrap || !bootstrap.Offcanvas) {
            console.error('[IVC carrito] Bootstrap Offcanvas no está disponible. Revisa assets/js/bootstrap.bundle.min.js.');
            return null;
        }
        return bootstrap.Offcanvas.getOrCreateInstance(drawer);
    }

    function openCart(refresh) {
        sync();
        const offcanvas = getOffcanvas();
        if (!offcanvas) return;
        // Nunca permitir que una solicitud AJAX fallida impida abrir el carrito.
        offcanvas.show();
        if (refresh && typeof window.getListaCarrito === 'function') {
            try {
                window.getListaCarrito();
            } catch (error) {
                console.error('[IVC carrito] Error al cargar productos:', error);
            }
        }
    }

    // Bootstrap abre el panel incluso si falla el manejador anterior de carrito.js.
    // Captura el evento antes del listener original para evitar dobles aperturas
    // y para bloquear el href="#" del icono.
    if (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            openCart(true);
        }, true);
    } else {
        console.warn('[IVC carrito] No existe #verCarrito en header.php.');
    }

    drawer.addEventListener('show.bs.offcanvas', sync);
    drawer.addEventListener('shown.bs.offcanvas', sync);

    if (tbody) {
        new MutationObserver(sync).observe(tbody, {childList: true});
        tbody.addEventListener('error', function (event) {
            if (event.target instanceof HTMLImageElement && !event.target.dataset.ivcFailed) {
                event.target.dataset.ivcFailed = '1';
                event.target.src = (typeof window.base_url === 'string' ? window.base_url : '/') + 'assets/img/ivc-producto-sin-foto.svg';
            }
        }, true);
        tbody.addEventListener('change', function (event) {
            if (event.target.matches('.agregarCantidad')) {
                window.setTimeout(function () {
                    if (typeof window.getListaCarrito === 'function') {
                        try { window.getListaCarrito(); } catch (error) { console.error(error); }
                    }
                }, 0);
            }
        });
    }

    // Mantener la función original de agregar al carrito y abrir solo al agregar
    // una referencia nueva, no cuando el artículo ya se encontraba guardado.
    if (typeof window.agregarCarrito === 'function' && !window.agregarCarrito.ivcDrawerWrapped) {
        const original = window.agregarCarrito;
        function agregarYMostrar() {
            const antes = items().length;
            const result = original.apply(this, arguments);
            if (items().length > antes) {
                openCart(true);
                if (live) live.textContent = 'Producto agregado al carrito.';
            }
            return result;
        }
        agregarYMostrar.ivcDrawerWrapped = true;
        window.agregarCarrito = agregarYMostrar;
    }

    const login = document.getElementById('ivcCartLogin');
    if (login) {
        login.addEventListener('click', function () {
            const modal = document.getElementById('modalLogin');
            if (!modal || !window.bootstrap || !bootstrap.Modal) return;
            drawer.addEventListener('hidden.bs.offcanvas', function () {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }, {once: true});
            const instance = getOffcanvas();
            if (instance) instance.hide();
        });
    }

    sync();
})();
