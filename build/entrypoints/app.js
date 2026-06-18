/**
 * Entrypoint: app
 * Carga en TODAS las páginas públicas.
 *
 * CSS global → un solo archivo bundleado con hash
 * JS global  → header.js (navegación, menú móvil)
 */

// ── CSS ───────────────────────────────────────────────────────────────────────
import '../../frontend/assets/css/main.css';
import '../../frontend/assets/css/header.css';
import '../../frontend/assets/css/components.css';

// ── JS global ─────────────────────────────────────────────────────────────────
import '../../frontend/assets/js/header.js';
