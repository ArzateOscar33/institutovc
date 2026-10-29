/** Instituto Visual California · V8 | MEJORAS VISUALES SOLAMENTE.
 * No registra, elimina ni transfiere productos; esas acciones siguen en listaDeseo.js y carrito.js.
 */
(function () {
  'use strict';
  function initWishPresentation() {
    const root = document.getElementById('ivc-wishlist');
    if (!root || root.dataset.ivcwInitialized === 'true') return;
    root.dataset.ivcwInitialized = 'true';
    const body = root.querySelector('#tableListaDeseo tbody');
    const products = root.querySelector('#ivcwProducts');
    const empty = root.querySelector('#ivcwEmpty');
    const loading = root.querySelector('#ivcwLoading');
    const error = root.querySelector('#ivcwError');
    const count = root.querySelector('#ivcwCountLabel');
    const fallback = (typeof base_url === 'string' ? base_url : '/') + 'assets/img/ivc-producto-sin-foto.svg';
    let seenServerResponse = false;

    function savedCount() {
      try {
        const items = JSON.parse(localStorage.getItem('listaDeseo') || '[]');
        return Array.isArray(items) ? items.length : 0;
      } catch (_) { return 0; }
    }

    function enhanceRow(row) {
      if (row.dataset.ivcwEnhanced === 'true') return;
      row.dataset.ivcwEnhanced = 'true';
      const cells = row.querySelectorAll('td');
      if (cells.length < 5) return;
      const remove = cells[4].querySelector('.btnEliminarDeseo');
      const cart = cells[4].querySelector('.btnAddCart');
      const id = remove && remove.getAttribute('prod');
      const name = cells[1].textContent.trim();
      if (remove) {
        remove.setAttribute('aria-label', 'Quitar ' + name + ' de favoritos');
        remove.setAttribute('title', 'Quitar de favoritos');
        remove.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 4h4M6 7l1 13h10l1-13M10 11v6m4-6v6"/></svg>';
      }
      if (cart) {
        cart.setAttribute('aria-label', 'Agregar ' + name + ' al carrito');
        cart.setAttribute('title', 'Agregar al carrito');
        cart.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2 12h12l2-8H6M9 20h.01M17 20h.01M13 8v6m-3-3h6"/></svg>';
      }
      if (id && /^\d+$/.test(id)) {
        const href = (typeof base_url === 'string' ? base_url : '/') + 'principal/detail/' + id;
        if (!cells[1].querySelector('a')) {
          const link = document.createElement('a');
          link.href = href;
          link.textContent = name;
          cells[1].textContent = '';
          cells[1].appendChild(link);
        }
        const img = cells[0].querySelector('img');
        if (img) {
          const src = img.getAttribute('src') || '';
          const cleaned = src.replace(/^\.\//, '');
          if (!src || src.includes('robohash.org')) img.src = fallback;
          else if (!/^(https?:\/\/|data:|blob:)/i.test(src) && !src.startsWith('/') && typeof base_url === 'string') img.src = base_url + cleaned;
          img.alt = name;
          img.loading = 'lazy';
          img.addEventListener('error', function () { if (this.src !== new URL(fallback, location.href).href) this.src = fallback; }, { once: true });
          const imageLink = document.createElement('a');
          imageLink.href = href;
          imageLink.setAttribute('aria-label', 'Ver detalles de ' + name);
          img.parentNode.insertBefore(imageLink, img);
          imageLink.appendChild(img);
        }
      }
    }

    function refresh() {
      const rows = [...body.querySelectorAll('tr')];
      const n = rows.length;
      const localN = savedCount();
      if (n) {
        seenServerResponse = true;
        rows.forEach(enhanceRow);
        products.hidden = false;
        empty.hidden = true;
        loading.hidden = true;
        error.hidden = true;
        count.textContent = n === 1 ? '1 armazón guardado en tus favoritos' : n + ' productos guardados en tus favoritos';
      } else if (localN === 0 || seenServerResponse) {
        products.hidden = true;
        empty.hidden = false;
        loading.hidden = true;
        error.hidden = true;
        count.textContent = '0 productos guardados · Encuentra tu próximo favorito';
      } else {
        products.hidden = true;
        empty.hidden = true;
        loading.hidden = false;
        error.hidden = true;
        count.textContent = 'Cargando tus favoritos...';
      }
    }
    const observer = new MutationObserver(refresh);
    observer.observe(body, { childList: true });
    refresh();
    setTimeout(function () {
      if (savedCount() > 0 && !body.querySelector('tr')) {
        loading.hidden = true;
        error.hidden = false;
        count.textContent = 'No se pudo cargar la selección';
      }
    }, 10000);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initWishPresentation, { once: true });
  else initWishPresentation();
})();
