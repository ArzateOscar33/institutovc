/** Instituto Visual California | Detalle V7.
 * Funciona con el HTML del controlador existente y sin bibliotecas adicionales.
 * Los handlers del carrito siguen siendo responsabilidad de detail.js y carrito.js.
 */
(function () {
  'use strict';

  function init() {
    const root = document.getElementById('ivc-product-detail');
    if (!root || root.dataset.ivcdReady === '1') return;
    root.dataset.ivcdReady = '1';

    const fallbackImages = document.querySelectorAll('#ivc-product-detail img[data-fallback], #ivcdZoomModal img[data-fallback]');
    fallbackImages.forEach((img) => {
      const useFallback = function () {
        if (img.dataset.fallbackUsed === '1') return;
        img.dataset.fallbackUsed = '1';
        img.src = img.dataset.fallback;
      };
      img.addEventListener('error', useFallback);
      if (img.complete && img.naturalWidth === 0) useFallback();
    });

    // Cantidad limitada a las existencias; evita activar los handlers heredados de TemplateMo.
    const qtyInput = document.getElementById('product-quanity');
    const qtyOutput = document.getElementById('var-value');
    const maxQty = Math.max(0, Number(qtyInput && qtyInput.dataset.max) || 0);
    let qty = Math.min(1, maxQty);
    const qtyButtons = Array.from(root.querySelectorAll('[data-ivcd-qty]'));
    function renderQty() {
      if (qtyInput) qtyInput.value = String(qty);
      if (qtyOutput) qtyOutput.textContent = String(qty);
      qtyButtons.forEach((button) => {
        const delta = Number(button.dataset.ivcdQty);
        button.disabled = (delta < 0 && qty <= 1) || (delta > 0 && qty >= maxQty) || maxQty === 0;
      });
    }
    qtyButtons.forEach(button => button.addEventListener('click', () => {
      qty = Math.max(1, Math.min(maxQty, qty + Number(button.dataset.ivcdQty)));
      renderQty();
    }));
    renderQty();

    // Selección de fotografías y visor modal.
    const mainPhoto = document.getElementById('product-detail');
    const zoomPhoto = document.getElementById('ivcdZoomImage');
    const zoomCounter = document.getElementById('ivcdZoomCounter');
    const zoomModal = document.getElementById('ivcdZoomModal');
    const thumbs = [...root.querySelectorAll('[data-gallery-index]')];
    let gallery = [];
    try { gallery = JSON.parse(document.getElementById('ivcdGalleryData').textContent); } catch (_) {}
    let activeIndex = 0;
    function showPhoto(index) {
      if (!gallery.length) return;
      activeIndex = (index + gallery.length) % gallery.length;
      const photo = gallery[activeIndex];
      if (mainPhoto) {
        mainPhoto.src = photo.src;
        mainPhoto.alt = photo.alt;
        delete mainPhoto.dataset.fallbackUsed;
      }
      if (zoomPhoto) {
        zoomPhoto.src = photo.src;
        zoomPhoto.alt = photo.alt;
        delete zoomPhoto.dataset.fallbackUsed;
      }
      if (zoomCounter) zoomCounter.textContent = `Imagen ${activeIndex + 1} de ${gallery.length}`;
      thumbs.forEach((thumb, i) => {
        const active = activeIndex === i;
        thumb.classList.toggle('is-active', active);
        thumb.setAttribute('aria-pressed', String(active));
      });
    }
    thumbs.forEach(thumb => thumb.addEventListener('click', () => showPhoto(Number(thumb.dataset.galleryIndex))));
    document.getElementById('ivcdOpenZoom')?.addEventListener('click', () => {
      showPhoto(activeIndex);
      if (window.bootstrap && window.bootstrap.Modal && zoomModal) {
        window.bootstrap.Modal.getOrCreateInstance(zoomModal).show();
      }
    });
    document.getElementById('ivcdZoomPrevious')?.addEventListener('click', () => showPhoto(activeIndex - 1));
    document.getElementById('ivcdZoomNext')?.addEventListener('click', () => showPhoto(activeIndex + 1));
    zoomModal?.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft') { event.preventDefault(); showPhoto(activeIndex - 1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); showPhoto(activeIndex + 1); }
    });
    showPhoto(0);

    // Carrusel de movimiento lineal, infinito y sin dependencia de Slick / GSAP.
    // Los clones permiten que el tramo repetido aparezca exactamente igual al original.
    const viewport = document.getElementById('ivcdRelatedViewport');
    const track = document.getElementById('ivcdRelatedTrack');
    const prev = document.getElementById('ivcdRelatedPrev');
    const next = document.getElementById('ivcdRelatedNext');
    if (!viewport || !track) return;
    const originals = [...track.children];
    if (originals.length <= 1) {
      if (prev) prev.disabled = true;
      if (next) next.disabled = true;
      return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let hovered = false, focusing = false, touching = false, inView = true;
    let resetAt = 0, previousTime = 0, frameId = 0, manuallyScrollingUntil = 0;
    const cloneGroup = () => {
      const fragment = document.createDocumentFragment();
      originals.forEach((card) => {
        const clone = card.cloneNode(true);
        clone.dataset.ivcdClone = '1';
        clone.setAttribute('aria-hidden', 'true');
        // Evita duplicar destinos en navegación por teclado; los originales siguen accesibles.
        clone.querySelectorAll('a,button,[tabindex]').forEach(a => a.setAttribute('tabindex', '-1'));
        fragment.appendChild(clone);
      });
      track.appendChild(fragment);
    };
    const canMove = () => track.scrollWidth > viewport.clientWidth + 15;
    if (canMove()) {
      cloneGroup();
      resetAt = track.children[originals.length].offsetLeft - originals[0].offsetLeft;
      // Suficientes clones para sostener el loop en pantallas anchas.
      let tries = 0;
      while (track.scrollWidth - viewport.clientWidth < resetAt + 4 && tries < 5) {
        cloneGroup(); tries++;
      }
    }
    const controlsEnabled = canMove();
    if (!controlsEnabled) {
      if (prev) prev.disabled = true;
      if (next) next.disabled = true;
      return;
    }

    function normalize() {
      if (resetAt > 0 && viewport.scrollLeft >= resetAt) viewport.scrollLeft -= resetAt;
      else if (viewport.scrollLeft < 0) viewport.scrollLeft += resetAt;
    }
    function step(direction) {
      const card = originals[0];
      if (!card) return;
      manuallyScrollingUntil = performance.now() + 1000;
      if (direction < 0 && viewport.scrollLeft < card.offsetWidth) viewport.scrollLeft += resetAt;
      viewport.scrollBy({left: direction * (card.offsetWidth + 21), behavior: reduceMotion.matches ? 'auto' : 'smooth'});
    }
    prev?.addEventListener('click', () => step(-1));
    next?.addEventListener('click', () => step(1));
    viewport.addEventListener('mouseenter', () => { hovered = true; });
    viewport.addEventListener('mouseleave', () => { hovered = false; });
    viewport.addEventListener('focusin', () => { focusing = true; });
    viewport.addEventListener('focusout', () => { focusing = viewport.contains(document.activeElement); });
    viewport.addEventListener('touchstart', () => { touching = true; }, {passive:true});
    viewport.addEventListener('touchend', () => { touching = false; manuallyScrollingUntil = performance.now() + 1400; }, {passive:true});
    viewport.addEventListener('wheel', () => { manuallyScrollingUntil = performance.now() + 1600; }, {passive:true});
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => { inView = entries[0]?.isIntersecting ?? true; }, {threshold: 0.05});
      observer.observe(viewport);
    }
    function frame(now) {
      const elapsed = previousTime ? Math.min(now - previousTime, 45) : 0;
      previousTime = now;
      if (!reduceMotion.matches && inView && !document.hidden && !hovered && !focusing && !touching && now > manuallyScrollingUntil) {
        viewport.scrollLeft += elapsed * 0.028; // 28 px/segundo; movimiento continuo.
        normalize();
      }
      frameId = window.requestAnimationFrame(frame);
    }
    frameId = window.requestAnimationFrame(frame);
    window.addEventListener('pagehide', () => window.cancelAnimationFrame(frameId), {once:true});
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
  else init();
})();
