(() => {
    'use strict';

    /* ── Service Worker ─────────────────────────────────────── */
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/latinshop/service-worker.js')
                .then(reg => console.log('[PWA] SW registrado:', reg.scope))
                .catch(err => console.warn('[PWA] SW error:', err));
        });
    }

    /* ── Si ya está instalada como app, no hacer nada ───────── */
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
                      || window.navigator.standalone === true;
    if (isStandalone) return;

    if (localStorage.getItem('pwa-installed')) return;

    /* ── Detección de plataforma ─────────────────────────────── */
    const ua        = navigator.userAgent;
    const isIOS     = /iphone|ipad|ipod/i.test(ua);
    const isSafari  = isIOS && /safari/i.test(ua) && !/crios|fxios|opios/i.test(ua);
    const isChrome  = /chrome|crios/i.test(ua) && !/edg/i.test(ua);
    const isFirefox = /firefox|fxios/i.test(ua);

    let deferredPrompt = null;

    /* ── Elementos existentes del DOM ───────────────────────── */
    const banner     = document.getElementById('pwa-install-banner');
    const btnInstall = document.getElementById('pwa-btn-install');
    const btnDismiss = document.getElementById('pwa-btn-dismiss');
    const modal      = document.getElementById('pwa-manual-modal');
    const modalBody  = document.getElementById('pwa-modal-body');
    const modalClose = document.getElementById('pwa-modal-close');

    /* ── Botón flotante fijo (FAB) ───────────────────────────── */
    const fabCSS = document.createElement('style');
    fabCSS.textContent = `
        #pwa-fab {
            position: fixed;
            bottom: 22px;
            right: 18px;
            z-index: 1100;
            display: none;
            align-items: center;
            gap: 7px;
            padding: 11px 18px;
            background: #e8602c;
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(232,96,44,.5);
            transition: opacity .2s, transform .2s;
            white-space: nowrap;
            font-family: sans-serif;
        }
        #pwa-fab:hover { opacity:.9; transform:translateY(-2px); }
        #pwa-fab.show  { display: flex; }
        @media (max-width:420px) {
            #pwa-fab span { display:none; }
            #pwa-fab { padding:12px; border-radius:50%; }
        }
    `;
    document.head.appendChild(fabCSS);

    const fab = document.createElement('button');
    fab.id = 'pwa-fab';
    fab.innerHTML = `<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg><span>Instalar app</span>`;
    document.body.appendChild(fab);

    /* ── Helpers ─────────────────────────────────────────────── */
    function showFab()    { fab.classList.add('show'); }
    function hideFab()    { fab.classList.remove('show'); }
    function showBanner() { if (banner) banner.classList.add('pwa-banner--visible'); }
    function hideBanner() { if (banner) banner.classList.remove('pwa-banner--visible'); }
    function showModal()  { setModalInstructions(); if (modal) modal.classList.add('is-open'); }
    function hideModal()  { if (modal) modal.classList.remove('is-open'); }

    function setModalInstructions() {
        if (!modalBody) return;
        if (isSafari) {
            modalBody.innerHTML =
                '1. Pulsa <strong>Compartir ↑</strong> en la barra inferior de Safari.<br><br>' +
                '2. Desplázate y toca <strong>"Añadir a pantalla de inicio"</strong>.<br><br>' +
                '3. Confirma con <strong>"Añadir"</strong>.';
        } else if (isIOS) {
            modalBody.innerHTML =
                'Abre esta página en <strong>Safari</strong> y luego pulsa ' +
                '<strong>Compartir ↑</strong> → <strong>"Añadir a pantalla de inicio"</strong>.';
        } else if (isChrome) {
            modalBody.innerHTML =
                '1. Pulsa el menú <strong>⋮</strong> arriba a la derecha.<br><br>' +
                '2. Toca <strong>"Instalar aplicación"</strong> o <strong>"Añadir a pantalla de inicio"</strong>.<br><br>' +
                '3. Confirma en el diálogo.';
        } else if (isFirefox) {
            modalBody.innerHTML =
                '1. Pulsa el menú <strong>⋮</strong> de Firefox.<br><br>' +
                '2. Toca <strong>"Instalar"</strong> o <strong>"Añadir a pantalla de inicio"</strong>.';
        } else {
            modalBody.innerHTML =
                'En el menú de tu navegador busca <strong>"Instalar aplicación"</strong> ' +
                'o <strong>"Añadir a pantalla de inicio"</strong>.';
        }
    }

    async function triggerInstall() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('[PWA] outcome:', outcome);
            deferredPrompt = null;
            hideBanner();
            hideFab();
        } else {
            showModal();
        }
    }

    /* ── Eventos Chrome/Edge Android ────────────────────────── */
    window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        deferredPrompt = e;
        console.log('[PWA] beforeinstallprompt capturado ✓');
        showBanner();
        showFab();
    });

    window.addEventListener('appinstalled', () => {
        hideBanner(); hideModal(); hideFab();
        localStorage.setItem('pwa-installed', '1');
    });

    /* ── Eventos botones ─────────────────────────────────────── */
    if (btnInstall) btnInstall.addEventListener('click', triggerInstall);
    if (btnDismiss) btnDismiss.addEventListener('click', () => { hideBanner(); localStorage.setItem('pwa-dismissed', 'session'); });
    fab.addEventListener('click', triggerInstall);
    if (modalClose) modalClose.addEventListener('click', hideModal);
    if (modal) modal.addEventListener('click', e => { if (e.target === modal) hideModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') hideModal(); });

    /* ── iOS: siempre mostrar FAB (no hay beforeinstallprompt) ─ */
    if (isIOS && !localStorage.getItem('pwa-dismissed')) {
        setTimeout(showFab, 2000);
    }

    /* ── Otros navegadores: FAB manual si no hay prompt en 6s ─ */
    if (!isIOS && !localStorage.getItem('pwa-dismissed')) {
        setTimeout(() => {
            if (!deferredPrompt) {
                console.log('[PWA] Sin beforeinstallprompt — mostrando FAB manual');
                showFab();
            }
        }, 6000);
    }

})();