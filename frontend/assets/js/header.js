/* =========================================================
   LATINSHOP HEADER.JS - OPTIMIZADO PRO
========================================================= */

document.addEventListener('DOMContentLoaded', initHeader);

function initHeader() {
    initCursor();
    initMobileMenu();
    initDropdownNav();
    initUserDropdown();
    initScrollHeader();
    initRevealAnimations();
    initCounters();
    initGlitch();
    initRipple();
    initProgressBar();
    initPWA();
}

/* =========================================================
   CURSOR CUSTOM (OPTIMIZADO)
========================================================= */
function initCursor() {
    if (!window.matchMedia('(pointer:fine)').matches) return;

    const cursor = document.createElement('div');
    const ring   = document.createElement('div');

    cursor.className = 'tbt-cursor';
    ring.className   = 'tbt-cursor-ring';

    document.body.appendChild(cursor);
    document.body.appendChild(ring);

    let mx = -100, my = -100;
    let rx = -100, ry = -100;

    document.addEventListener('pointermove', (e) => {
        mx = e.clientX;
        my = e.clientY;

        cursor.style.transform = `translate(${mx}px, ${my}px)`;
    }, { passive: true });

    function animate() {
        rx += (mx - rx) * 0.12;
        ry += (my - ry) * 0.12;

        ring.style.transform = `translate(${rx}px, ${ry}px)`;
        requestAnimationFrame(animate);
    }
    animate();

    const hoverTargets = 'a,button,input,textarea,select,[data-cursor-hover]';

    document.addEventListener('pointerover', (e) => {
        if (e.target.closest(hoverTargets)) {
            cursor.classList.add('is-hover');
            ring.classList.add('is-hover');
        }
    });

    document.addEventListener('pointerout', (e) => {
        if (e.target.closest(hoverTargets)) {
            cursor.classList.remove('is-hover');
            ring.classList.remove('is-hover');
        }
    });

    document.addEventListener('mouseleave', () => {
        cursor.style.opacity = ring.style.opacity = '0';
    });

    document.addEventListener('mouseenter', () => {
        cursor.style.opacity = ring.style.opacity = '1';
    });
}

/* =========================================================
   MOBILE MENU
========================================================= */
function initMobileMenu() {
    const toggle = document.getElementById('tbt-toggle');
    const nav    = document.getElementById('tbt-nav');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', () => {
        const open = nav.classList.toggle('tbt-is-active');

        toggle.classList.toggle('tbt-is-active', open);
        toggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
    });

    document.addEventListener('click', (e) => {
        if (!nav.contains(e.target) && !toggle.contains(e.target)) {
            closeMenu(nav, toggle);
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) closeMenu(nav, toggle);
    });

    function closeMenu(nav, toggle) {
        nav.classList.remove('tbt-is-active');
        toggle.classList.remove('tbt-is-active');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
}

/* =========================================================
   DROPDOWNS NAV MOBILE
========================================================= */
function initDropdownNav() {
    document.querySelectorAll('[data-has-dropdown]').forEach(item => {
        const link = item.querySelector('.tbt-header__link');
        const dd   = item.querySelector('.tbt-header__dropdown');
        const arrow = link?.querySelector('.tbt-header__arrow');

        if (!link || !dd) return;

        link.addEventListener('click', (e) => {
            if (window.innerWidth > 900) return;

            e.preventDefault();

            const open = dd.classList.toggle('tbt-is-open');

            if (arrow) arrow.style.transform = open ? 'rotate(180deg)' : '';

            if (open) {
                dd.style.height = dd.scrollHeight + 'px';
                requestAnimationFrame(() => dd.style.height = 'auto');
            } else {
                dd.style.height = dd.scrollHeight + 'px';
                requestAnimationFrame(() => dd.style.height = '0px');
            }
        });
    });
}

/* =========================================================
   USER DROPDOWN
========================================================= */
function initUserDropdown() {
    const btn = document.getElementById('hdr-user-btn');
    const dd  = document.getElementById('hdr-user-dropdown');
    if (!btn || !dd) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = dd.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', (e) => {
        if (!btn.contains(e.target) && !dd.contains(e.target)) {
            dd.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dd.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
}

/* =========================================================
   HEADER SCROLL (OPTIMIZADO RAF)
========================================================= */
function initScrollHeader() {
    const header = document.querySelector('.tbt-header');
    if (!header) return;

    let ticking = false;

    window.addEventListener('scroll', () => {
        if (ticking) return;

        ticking = true;

        requestAnimationFrame(() => {
            header.classList.toggle(
                'tbt-header--scrolled',
                window.scrollY > 20
            );
            ticking = false;
        });
    }, { passive: true });
}

/* =========================================================
   REVEAL ANIMATIONS
========================================================= */
function initRevealAnimations() {
    const els = document.querySelectorAll('.tbt-reveal, .tbt-stagger');
    if (!els.length) return;

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('tbt-visible');
                obs.unobserve(e.target);
            }
        });
    }, { threshold: 0.1 });

    els.forEach(el => io.observe(el));
}

/* =========================================================
   COUNTERS
========================================================= */
function initCounters() {
    const counters = document.querySelectorAll('.tbt-count');
    if (!counters.length) return;

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            const el = entry.target;
            const target = +el.dataset.to || 0;
            const prefix = el.dataset.prefix || '';
            const suffix = el.dataset.suffix || '';
            const dec = +el.dataset.decimals || 0;

            const start = performance.now();
            const dur = 1200;

            function step(t) {
                const p = Math.min((t - start) / dur, 1);
                const val = target * (1 - Math.pow(1 - p, 3));

                el.textContent =
                    prefix +
                    (dec ? val.toFixed(dec) : Math.floor(val)) +
                    suffix;

                if (p < 1) requestAnimationFrame(step);
            }

            requestAnimationFrame(step);
            obs.unobserve(el);
        });
    }, { threshold: 0.5 });

    counters.forEach(c => io.observe(c));
}

/* =========================================================
   GLITCH TEXT (OPTIMIZADO)
========================================================= */
function initGlitch() {
    const els = document.querySelectorAll('.tbt-glitch');
    if (!els.length) return;

    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&';

    els.forEach(el => {
        const original = el.textContent;

        const run = () => {
            let i = 0;

            const interval = setInterval(() => {
                el.textContent = original
                    .split('')
                    .map((c, idx) =>
                        idx < i || c === ' '
                            ? c
                            : chars[Math.random() * chars.length | 0]
                    )
                    .join('');

                i++;

                if (i > original.length) {
                    clearInterval(interval);
                    el.textContent = original;

                    setTimeout(run, 8000 + Math.random() * 4000);
                }
            }, 50);
        };

        setTimeout(run, 3000 + Math.random() * 3000);
    });
}

/* =========================================================
   RIPPLE BUTTONS
========================================================= */
function initRipple() {
    document.querySelectorAll('.tbt-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const r = btn.getBoundingClientRect();
            const s = Math.max(r.width, r.height);

            const ripple = document.createElement('span');

            ripple.style.cssText = `
                position:absolute;
                width:${s}px;
                height:${s}px;
                left:${e.clientX - r.left - s / 2}px;
                top:${e.clientY - r.top - s / 2}px;
                background:rgba(255,255,255,.12);
                border-radius:50%;
                transform:scale(0);
                animation:tbt-ripple .5s ease-out;
                pointer-events:none;
            `;

            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 500);
        });
    });
}

/* =========================================================
   PROGRESS BAR
========================================================= */
function initProgressBar() {
    const bar = document.createElement('div');

    Object.assign(bar.style, {
        position: 'fixed',
        top: 0,
        left: 0,
        height: '2px',
        width: '0%',
        background: 'linear-gradient(90deg,#e8602c,#c9a227)',
        zIndex: 99999,
        transition: 'width .3s,opacity .4s'
    });

    document.body.appendChild(bar);

    let w = 0;
    const int = setInterval(() => {
        w += (100 - w) * 0.08;
        bar.style.width = w + '%';
    }, 30);

    window.addEventListener('load', () => {
        clearInterval(int);
        bar.style.width = '100%';

        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => bar.remove(), 400);
        }, 200);
    });
}

/* =========================================================
   PWA (placeholder limpio)
========================================================= */
function initPWA() {
    // aquí conectas tu lógica actual de install button/modal
}