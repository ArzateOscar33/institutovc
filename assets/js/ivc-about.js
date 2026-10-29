/* Instituto Visual California — animaciones suaves solo para /principal/about */
(() => {
  'use strict';
  const start = () => {
    const root = document.querySelector('.ivc-about');
    if (!root) return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduced || !('IntersectionObserver' in window)) return;

    const hero = root.querySelectorAll('.ivc-about-anim');
    const items = root.querySelectorAll('.ivc-about-reveal');
    const gsapAvailable = typeof window.gsap !== 'undefined';

    if (gsapAvailable && hero.length) {
      window.gsap.fromTo(hero,
        {opacity: 0, y: 22},
        {opacity: 1, y: 0, duration: 0.83, stagger: 0.11, ease: 'power2.out', clearProps: 'all'}
      );
    }
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        if (gsapAvailable) {
          window.gsap.fromTo(entry.target, {opacity: 0, y: 28},
            {opacity: 1, y: 0, duration: 0.75, ease: 'power2.out', clearProps: 'all'});
        } else {
          entry.target.classList.add('ivc-about-visible');
        }
      });
    }, {threshold: 0.11, rootMargin: '0px 0px -24px 0px'});
    items.forEach((item) => observer.observe(item));
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, {once: true});
  } else {
    start();
  }
})();
