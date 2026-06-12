<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
if ($auth->isLoggedIn()) safe_redirect(u('/'));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit('login_form', 10, 300)) {
        $error = 'Demasiados intentos. Espera unos minutos.';
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } elseif (ENV === 'production' && !verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        $error = 'Por favor verifica que no eres un robot.';
    } else {
        $email       = sanitize_email($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        if (!$email) { $error = 'El email no es válido.'; }
        elseif (empty($password)) { $error = 'Ingresa tu contraseña.'; }
        else {
            $resultado = $auth->login($email, $password, $remember_me);
            if ($resultado['success']) {
                $user_id = $resultado['user_id'] ?? $_SESSION['2fa_user_id'] ?? $_SESSION['user_id'] ?? null;
                if ($user_id && $auth->has2FA($user_id)) {
                    $twofa_method = $auth->get2FAMethod($user_id);
                    session_regenerate_id(true);
                    $_SESSION = [];
                    $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
                    $_SESSION['csrf_token_time'] = time();
                    $_SESSION['2fa_pending']     = true;
                    $_SESSION['2fa_user_id']     = $user_id;
                    $_SESSION['2fa_time']        = time();
                    $_SESSION['2fa_remember']    = $remember_me;
                    $_SESSION['2fa_method']      = $twofa_method;
                   if ($twofa_method === 'totp') {
                        safe_redirect(u('/verificar-2fa'));
                    } else {
                        $sms = $auth->send2FACode($user_id);
                        if ($sms['success']) {
                            safe_redirect(u('/verificar-2fa'));
                        } else {
                            $error = 'No se pudo enviar el código de verificación. Intenta de nuevo o contacta soporte.';
                        }
                    }
                } else {
                    $redirect = $_SESSION['redirect_after_login'] ?? u('/');
                    unset($_SESSION['redirect_after_login']);
                    safe_redirect($redirect);
                }
            } else {
                $error = $resultado['error'];
            }
        }
    }
}

$page_title = 'Iniciar Sesión | Latin Shop';
$page_canonical = u('/login');
$extra_css = ['auth-epic.css'];
include INCLUDES_PATH . '/header.php';
$flash_ok = $auth->getFlash('success');
?>
<style>body{background:var(--tbt-bg-base);}.tbt-site-content{padding:0;}</style>

<div class="auth-epic-layout">

  <div class="auth-epic-left">
    <div class="auth-epic-grid"></div>
    <canvas id="auth-particles"></canvas>
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>
    <div class="auth-orb auth-orb-3"></div>
    <span class="auth-rune auth-rune-1">⚔</span>
    <span class="auth-rune auth-rune-2">🛡</span>
    <div class="auth-epic-brand">
      <div class="auth-epic-shield">
        <svg viewBox="0 0 120 140" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="sg" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="var(--tbt-jade-light)"/>
              <stop offset="50%" stop-color="var(--tbt-jade)"/>
              <stop offset="100%" stop-color="#f5a623"/>
            </linearGradient>
            <linearGradient id="si" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="rgba(232,96,44,.25)"/>
              <stop offset="100%" stop-color="rgba(232,96,44,.05)"/>
            </linearGradient>
          </defs>
          <path d="M60 4 L108 22 L108 66 Q108 106 60 136 Q12 106 12 66 L12 22 Z" fill="url(#si)" stroke="url(#sg)" stroke-width="2.5"/>
          <path d="M60 18 L94 30 L94 62 Q94 94 60 118 Q26 94 26 62 L26 30 Z" fill="none" stroke="rgba(232,96,44,.25)" stroke-width="1"/>
          <line x1="60" y1="28" x2="60" y2="108" stroke="url(#sg)" stroke-width="3" stroke-linecap="round"/>
          <line x1="42" y1="58" x2="78" y2="58" stroke="url(#sg)" stroke-width="2.5" stroke-linecap="round"/>
          <circle cx="60" cy="28" r="4" fill="#f5a623"/>
          <circle cx="60" cy="108" r="3" fill="var(--tbt-jade)"/>
        </svg>
      </div>
      <h1 class="auth-epic-title">Solitary<br>Store</h1>
      <p class="auth-epic-subtitle">Lords Mobile · Premium Services</p>
      <div class="auth-epic-stats">
        <div class="auth-stat"><span class="auth-stat__num">500+</span><span class="auth-stat__label">Clientes</span></div>
        <div class="auth-stat"><span class="auth-stat__num">24/7</span><span class="auth-stat__label">Soporte</span></div>
        <div class="auth-stat"><span class="auth-stat__num">100%</span><span class="auth-stat__label">Seguro</span></div>
      </div>
    </div>
  </div>

  <div class="auth-epic-right">
    <div class="auth-glass-card">

      <div class="auth-glass-logo">
        <div class="auth-glass-logo-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z" fill="rgba(232,96,44,.3)" stroke="var(--tbt-jade)" stroke-width="1.5"/>
            <path d="M9 12l2 2 4-4" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <span class="auth-glass-logo-title">Latin Shop</span>
        <span class="auth-glass-logo-sub">Lords Mobile</span>
      </div>

      <h2 class="auth-glass-heading">Bienvenido de vuelta</h2>
      <p class="auth-glass-subheading">Accede a tu cuenta para continuar</p>

      <?php if ($error): ?>
        <div class="auth-glass-alert auth-glass-alert--error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if ($flash_ok): ?>
        <div class="auth-glass-alert auth-glass-alert--success">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          <?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form class="auth-glass-form" method="POST" action="<?= u('/login') ?>" novalidate>
        <?= csrf_field() ?>

        <div class="auth-glass-field">
          <label class="auth-glass-label" for="email">Email</label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <input type="email" id="email" name="email" class="auth-glass-input"
              placeholder="tu@email.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="email" required>
          </div>
        </div>

        <div class="auth-glass-field">
          <div class="auth-glass-label-row">
            <label class="auth-glass-label" for="password">Contraseña</label>
            <a href="<?= u('/recuperar-password') ?>" class="auth-glass-link-sm">¿Olvidaste?</a>
          </div>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            <input type="password" id="password" name="password" class="auth-glass-input"
              placeholder="Tu contraseña" autocomplete="current-password" required>
            <button type="button" class="auth-glass-toggle-pass" onclick="togglePass()">
              <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="auth-glass-check-row">
          <label class="auth-glass-check-label">
            <input type="checkbox" name="remember_me" class="auth-glass-check" <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
            <span class="auth-glass-check-custom"></span>
            Mantener sesión por 30 días
          </label>
        </div>

        <?php if (ENV === 'production'): ?>
          <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
        <?php endif; ?>

        <button type="submit" class="auth-glass-submit">
          Entrar al reino
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/></svg>
        </button>
      </form>

      <div class="auth-glass-footer">
        ¿No tienes cuenta? <a href="<?= u('/registrar') ?>" class="auth-glass-link">Únete ahora</a>
      </div>
    </div>
  </div>

</div>

<?php if (ENV === 'production'): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<script src="/latinshop/frontend/assets/js/auth-login.js" defer></script>

<?php include INCLUDES_PATH . '/footer.php'; ?>