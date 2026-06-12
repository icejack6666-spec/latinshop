/**
 * lazy-load.js — Latin Shop
 * ============================================================
 * Lazy loading de imágenes con IntersectionObserver.
 *
 * Estrategia:
 *  1. Native lazy loading (`loading="lazy"`) como primera capa.
 *  2. IntersectionObserver como segunda capa para:
 *     - Placeholder blur (LQIP via CSS)
 *     - Animación de fade-in al entrar en viewport
 *     - Soporte para <picture> / srcset
 *     - Background images declaradas en data-bg
 *     - Carga progresiva en grids (stagger effect)
 *  3. Fallback sin Observer: carga inmediata.
 *
 * Uso en PHP (imágenes nuevas):
 *   <img data-src="imagen.jpg" alt="..." class="ls-lazy">
 *
 * Background lazy:
 *   <div data-bg="fondo.jpg" class="ls-lazy-bg"></div>
 *
 * No modifica imágenes ya cargadas (above-the-fold con `loading="eager"`)
 * ni el logo/avatar del header.
 * ============================================================
 */

(function () {
    'use strict';

    // ─── CONFIG ──────────────────────────────────────────────────────────────
    var CONFIG = {
        rootMargin  : '200px 0px',   // pre-carga 200px antes de entrar al viewport
        threshold   : 0.01,
        fadeClass   : 'ls-lazy--loaded',
        errorClass  : 'ls-lazy--error',
        placeholder : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4 3"%3E%3C/svg%3E',
    };

    // ─── SOPORTE ─────────────────────────────────────────────────────────────
    var hasObserver = 'IntersectionObserver' in window;
    var hasNative   = 'loading' in HTMLImageElement.prototype;

    // ─── HELPERS ─────────────────────────────────────────────────────────────

    function loadImage(img) {
        var src    = img.dataset.src;
        var srcset = img.dataset.srcset;
        var sizes  = img.dataset.sizes;

        if (!src && !srcset) return;

        // Crear imagen auxiliar para detectar carga/error antes de mostrar
        var tmp = new Image();

        tmp.onload = function () {
            if (srcset) img.srcset = srcset;
            if (sizes)  img.sizes  = sizes;
            if (src)    img.src    = src;

            img.removeAttribute('data-src');
            img.removeAttribute('data-srcset');
            img.removeAttribute('data-sizes');
            img.classList.add(CONFIG.fadeClass);
            img.classList.remove('ls-lazy');
        };

        tmp.onerror = function () {
            img.classList.add(CONFIG.errorClass);
            img.classList.remove('ls-lazy');
            // Intentar fallback si existe
            if (img.dataset.fallback) {
                img.src = img.dataset.fallback;
            }
        };

        tmp.src = srcset || src;
    }

    function loadBg(el) {
        var bg = el.dataset.bg;
        if (!bg) return;

        var tmp = new Image();
        tmp.onload = function () {
            el.style.backgroundImage = 'url("' + bg + '")';
            el.removeAttribute('data-bg');
            el.classList.add(CONFIG.fadeClass);
            el.classList.remove('ls-lazy-bg');
        };
        tmp.src = bg;
    }

    function handleEntry(entry) {
        if (!entry.isIntersecting) return;

        var el = entry.target;

        if (el.tagName === 'IMG') {
            loadImage(el);
        } else {
            loadBg(el);
        }

        observer.unobserve(el);
    }

    // ─── OBSERVER ────────────────────────────────────────────────────────────
    var observer;

    if (hasObserver) {
        observer = new IntersectionObserver(function (entries) {
            entries.forEach(handleEntry);
        }, {
            rootMargin : CONFIG.rootMargin,
            threshold  : CONFIG.threshold,
        });
    }

    // ─── REGISTRAR IMÁGENES ──────────────────────────────────────────────────

    function observe(el) {
        if (hasObserver) {
            observer.observe(el);
        } else {
            // Fallback: cargar inmediatamente
            if (el.tagName === 'IMG') loadImage(el);
            else loadBg(el);
        }
    }

    function init() {
        // Imágenes con data-src
        document.querySelectorAll('img.ls-lazy[data-src]').forEach(function (img) {
            // Placeholder mientras carga
            if (!img.src || img.src === window.location.href) {
                img.src = CONFIG.placeholder;
            }
            observe(img);
        });

        // Backgrounds con data-bg
        document.querySelectorAll('.ls-lazy-bg[data-bg]').forEach(function (el) {
            observe(el);
        });

        // Imágenes con loading="lazy" nativo pero sin Observer ya asignado
        // (mejora el fade-in en navegadores que soportan ambos)
        if (hasNative && hasObserver) {
            document.querySelectorAll('img[loading="lazy"]:not(.ls-lazy)').forEach(function (img) {
                if (img.complete && img.naturalWidth > 0) {
                    img.classList.add(CONFIG.fadeClass);
                } else {
                    img.classList.add('ls-native-lazy');
                    img.addEventListener('load', function () {
                        img.classList.add(CONFIG.fadeClass);
                        img.classList.remove('ls-native-lazy');
                    }, { once: true });
                }
            });
        }
    }

    // ─── STAGGER EN GRIDS ────────────────────────────────────────────────────
    // Aplica retraso progresivo a tarjetas dentro de grids para efecto cascada.

    function initStagger() {
        var grids = document.querySelectorAll(
        );

        if (!hasObserver || grids.length === 0) return;

        var staggerObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('ls-stagger--visible');
                staggerObserver.unobserve(entry.target);
            });
        }, { rootMargin: '0px', threshold: 0.05 });

        // Agrupar por fila (mismo offsetTop aproximado) para sincronizar
        var byRow = {};
        grids.forEach(function (card) {
            var row = Math.round(card.getBoundingClientRect().top / 10) * 10;
            if (!byRow[row]) byRow[row] = [];
            byRow[row].push(card);
        });

        Object.values(byRow).forEach(function (row) {
            row.forEach(function (card, i) {
                card.style.transitionDelay = (i * 60) + 'ms';
                card.classList.add('ls-stagger');
                staggerObserver.observe(card);
            });
        });
    }

    // ─── API PÚBLICA ─────────────────────────────────────────────────────────
    // Permite registrar imágenes inyectadas dinámicamente (e.g., infinite scroll)

    window.LazyLoad = {
        /**
         * Observar nuevas imágenes/elementos añadidos dinámicamente.
         * @param {Element|NodeList|Array} elements
         */
        observe: function (elements) {
            var list = elements instanceof Element ? [elements] : Array.from(elements);
            list.forEach(function (el) {
                if (el.tagName === 'IMG' && el.dataset.src) observe(el);
                if (el.classList && el.classList.contains('ls-lazy-bg') && el.dataset.bg) observe(el);
            });
        },

        /**
         * Re-escanear el DOM completo (útil tras navegación SPA parcial).
         */
        refresh: function () { init(); },
    };

    // ─── INIT ─────────────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
            initStagger();
        });
    } else {
        init();
        initStagger();
    }

}());
