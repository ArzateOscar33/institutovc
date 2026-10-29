/* Instituto Visual California — animaciones suaves y opcionales con GSAP.
   Si falla el CDN, la web sigue siendo visible y funcional. */
(function () {
  'use strict';
  function start() {
    if (!window.gsap || !document.querySelector('.ivc-home')) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const gsap = window.gsap;
    const hero = document.querySelector('.ivc-editorial-carousel .carousel-item.active .ivc-hero-content');
    if (hero) gsap.fromTo(hero, {y: 18, opacity: 0}, {y: 0, opacity: 1, duration: .85, ease: 'power2.out', clearProps:'all'});
    const carousel = document.getElementById('template-mo-zay-hero-carousel');
    if (carousel) carousel.addEventListener('slid.bs.carousel', function (e) {
      const content = e.relatedTarget && e.relatedTarget.querySelector('.ivc-hero-content');
      if (content) gsap.fromTo(content, {y: 15, opacity: .1}, {y: 0, opacity: 1, duration: .75, ease:'power2.out', clearProps:'all'});
    });
    const targets = document.querySelectorAll('.ivc-reveal');
    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        observer.unobserve(entry.target);
        gsap.fromTo(entry.target, {opacity: .01, y: 20}, {opacity: 1, y: 0, duration: .65, ease: 'power2.out', clearProps:'all'});
      });
    }, {rootMargin: '0px 0px -30px 0px', threshold: .08});
    targets.forEach(function (node) {observer.observe(node);});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
}());
