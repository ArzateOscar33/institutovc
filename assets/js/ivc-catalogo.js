/* Catálogo V4: interacciones accesibles y entrada sutil si GSAP está disponible. */
(function () {
  'use strict';
  function initCatalogo() {
    const root = document.querySelector('.ivc-shop');
    if (!root) return;

    // El ordenamiento funciona también sin JavaScript mediante su botón "Ordenar".
    const sort = document.getElementById('ivcShopSort');
    if (sort && sort.form) sort.addEventListener('change', () => sort.form.requestSubmit ? sort.form.requestSubmit() : sort.form.submit());

    // Mostrar el panel de filtros cerrado inicialmente en pantallas pequeñas.
    const filters = document.querySelector('.ivc-shop-filter-details');
    if (filters && window.matchMedia('(max-width: 767px)').matches) filters.open = false;
    if (filters) {
      // En escritorio se mantiene expandido y accesible.
      window.addEventListener('resize', () => {
        if (window.innerWidth > 767) filters.open = true;
      }, {passive: true});
    }

    if (!window.gsap || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const items = root.querySelectorAll('.ivc-shop-enter');
    if (!items.length) return;
    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        window.gsap.fromTo(entry.target, {opacity: 0.3, y: 15}, {
          opacity: 1, y: 0, duration: 0.55, ease: 'power2.out', clearProps: 'all'
        });
      });
    }, {threshold: 0.06, rootMargin: '0px 0px -20px 0px'});
    items.forEach(item => observer.observe(item));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCatalogo);
  else initCatalogo();
}());
