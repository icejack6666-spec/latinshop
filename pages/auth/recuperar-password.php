<?php
/**
 * RECUPERAR-PASSWORD.PHP
 * Flujo por SMS: paso1=teléfono → paso2=código → paso3=nueva contraseña
 * Si está logueado: cambio directo (contraseña actual + nueva)
 */
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth     = Auth::getInstance();
$logueado = $auth->isLoggedIn();
$usuario  = $logueado ? $auth->getUser() : null;
$db       = Database::getInstance();

$error   = null;
$success = null;

if ($logueado && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cambiar_directa') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $actual    = $_POST['password_actual']  ?? '';
        $nueva     = $_POST['password']         ?? '';
        $confirmar = $_POST['password_confirm'] ?? '';

        if (empty($actual)) {
            $error = 'Ingresa tu contraseña actual.';
        } elseif (strlen($nueva) < 8) {
            $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } else {
            $row = $db->fetch("SELECT password_hash FROM users WHERE id = ? LIMIT 1", [$usuario['id']]);
            if (!$row || !password_verify($actual, $row['password_hash'])) {
                $error = 'La contraseña actual es incorrecta.';
            } else {
                $hash = password_hash($nueva, PASSWORD_ARGON2ID);
                $db->update("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$hash, $usuario['id']]);
                $success = '¡Contraseña actualizada correctamente!';
            }
        }
    }
}

$paso = (int)($_SESSION['reset_paso'] ?? 1);

// Limpiar sesión si llevan más de 15 minutos
if (isset($_SESSION['reset_ts']) && (time() - $_SESSION['reset_ts']) > 900) {
    unset($_SESSION['reset_paso'], $_SESSION['reset_user_id'], $_SESSION['reset_phone_mask'], $_SESSION['reset_ts']);
    $paso = 1;
}

if (!$logueado) {

    if ($paso === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'enviar_codigo') {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = 'Token de seguridad inválido.';
        } elseif (!check_rate_limit('reset_sms', 3, 600)) {
            $error = 'Demasiados intentos. Espera 10 minutos.';
        } else {
            $phone = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? '');
            if (!str_starts_with($phone, '+')) $phone = '+52' . $phone;

            if (strlen($phone) < 10) {
                $error = 'Ingresa un número de teléfono válido con código de país.';
            } else {
                $user = $db->fetch(
                    "SELECT id, phone FROM users WHERE phone = ? AND phone_verified = 1 AND role != 'banned' LIMIT 1",
                    [$phone]
                );

                if ($user) {
                    $db->update(
                        "UPDATE verification_codes SET used = 1 WHERE user_id = ? AND type = 'reset' AND used = 0",
                        [$user['id']]
                    );
                    $code = Twilio::generateCode();
                    $db->insert(
                        "INSERT INTO verification_codes (user_id, code, type, created_at, expires_at, used)
                         VALUES (?, ?, 'reset', NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)",
                        [$user['id'], $code]
                    );
                    $twilio = Twilio::getInstance();
                    $twilio->sendSMS($phone, "Latin Shop: Tu código para restablecer contraseña es {$code}. Válido 10 min. No lo compartas.");

                    $_SESSION['reset_paso']      = 2;
                    $_SESSION['reset_user_id']   = $user['id'];
                    $_SESSION['reset_phone_mask'] = substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
                    $_SESSION['reset_ts']         = time();
                    $paso = 2;
                }
                if ($paso !== 2) {
                    $success = 'Si ese número está registrado y verificado, recibirás un SMS con el código en breve.';
                }
            }
        }
    }

    if ($paso === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'verificar_codigo') {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = 'Token de seguridad inválido.';
        } else {
            $code    = trim($_POST['codigo'] ?? '');
            $user_id = (int)($_SESSION['reset_user_id'] ?? 0);

            $registro = $db->fetch(
                "SELECT * FROM verification_codes
                 WHERE user_id = ? AND code = ? AND type = 'reset' AND used = 0 AND expires_at > NOW()
                 LIMIT 1",
                [$user_id, $code]
            );

            if (!$registro) {
                $error = 'Código incorrecto o expirado.';
            } else {
                // Marcar código como usado
                $db->update("UPDATE verification_codes SET used = 1 WHERE id = ?", [$registro['id']]);
                $_SESSION['reset_paso']      = 3;
                $_SESSION['reset_verified']  = true;
                $_SESSION['reset_ts']        = time();
                $paso = 3;
            }
        }
    }

    if ($paso === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'reenviar_codigo') {
        if (!check_rate_limit('reset_resend', 2, 300)) {
            $error = 'Espera unos minutos antes de reenviar.';
        } else {
            $user_id = (int)($_SESSION['reset_user_id'] ?? 0);
            $user    = $db->fetch("SELECT phone FROM users WHERE id = ? LIMIT 1", [$user_id]);
            if ($user && !empty($user['phone'])) {
                $db->update("UPDATE verification_codes SET used = 1 WHERE user_id = ? AND type = 'reset' AND used = 0", [$user_id]);
                $code = Twilio::generateCode();
                $db->insert(
                    "INSERT INTO verification_codes (user_id, code, type, created_at, expires_at, used)
                     VALUES (?, ?, 'reset', NOW(), DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)",
                    [$user_id, $code]
                );
                $twilio = Twilio::getInstance();
                $twilio->sendSMS($user['phone'], "Latin Shop: Tu código para restablecer contraseña es {$code}. Válido 10 min.");
                $_SESSION['reset_ts'] = time();
                $success = 'Código reenviado. Revisa tu teléfono.';
            } else {
                $error = 'No se pudo reenviar. Intenta desde el paso 1.';
            }
        }
    }

    if ($paso === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'nueva_password') {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $error = 'Token de seguridad inválido.';
        } elseif (empty($_SESSION['reset_verified'])) {
            $error = 'Sesión inválida. Vuelve al paso 1.';
            $paso  = 1;
        } else {
            $nueva     = $_POST['password']         ?? '';
            $confirmar = $_POST['password_confirm'] ?? '';
            $user_id   = (int)($_SESSION['reset_user_id'] ?? 0);

            if (strlen($nueva) < 8) {
                $error = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($nueva !== $confirmar) {
                $error = 'Las contraseñas no coinciden.';
            } elseif (!$user_id) {
                $error = 'Sesión inválida. Vuelve al paso 1.';
                $paso  = 1;
            } else {
                $hash = password_hash($nueva, PASSWORD_ARGON2ID);
                $db->update("UPDATE users SET password_hash = ?, remember_token = NULL, updated_at = NOW() WHERE id = ?", [$hash, $user_id]);
                $db->update("UPDATE sessions SET is_active = 0 WHERE user_id = ?", [$user_id]);
                unset($_SESSION['reset_paso'], $_SESSION['reset_user_id'], $_SESSION['reset_phone_mask'],
                      $_SESSION['reset_ts'], $_SESSION['reset_verified']);
                $auth->setFlash('success', '¡Contraseña actualizada! Ya puedes iniciar sesión.');
                safe_redirect(u('/login'));
            }
        }
    }

    if (isset($_GET['reiniciar'])) {
        unset($_SESSION['reset_paso'], $_SESSION['reset_user_id'], $_SESSION['reset_phone_mask'],
              $_SESSION['reset_ts'], $_SESSION['reset_verified']);
        safe_redirect(u('/recuperar-password'));
    }
}

$page_title       = ($logueado ? 'Cambiar' : 'Recuperar') . ' Contraseña | Latin Shop';
$page_canonical   = u('/recuperar-password');
$extra_css        = ['auth-epic.css'];
include INCLUDES_PATH . '/header.php';
?>

<section class="tbt-hero" style="padding-bottom:0;">
    <div class="tbt-wrap">
        <div style="max-width:520px;margin:0 auto;text-align:center;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3">
                <span class="tbt-badge__dot"></span>
                <?php if ($logueado): ?>Cambiar contraseña
                <?php elseif ($paso === 1): ?>Recuperar acceso
                <?php elseif ($paso === 2): ?>Verificar código
                <?php else: ?>Nueva contraseña
                <?php endif; ?>
            </div>
            <h1 class="tbt-h-xl tbt-mb-3">
                <?php if ($logueado): ?>
                    cambia tu<br><span class="tbt-jade">contraseña</span>
                <?php elseif ($paso === 1): ?>
                    ¿olvidaste tu<br><span class="tbt-jade">contraseña?</span>
                <?php elseif ($paso === 2): ?>
                    revisa tu<br><span class="tbt-jade">teléfono</span>
                <?php else: ?>
                    elige una nueva<br><span class="tbt-jade">contraseña</span>
                <?php endif; ?>
            </h1>
            <p class="tbt-body-lg">
                <?php if ($logueado): ?>Ingresa tu contraseña actual y luego la nueva.
                <?php elseif ($paso === 1): ?>Ingresa el número de teléfono que verificaste al registrarte.
                <?php elseif ($paso === 2): ?>Te enviamos un código de 6 dígitos al número <?= htmlspecialchars($_SESSION['reset_phone_mask'] ?? '') ?>.
                <?php else: ?>Elige una contraseña segura para tu cuenta.
                <?php endif; ?>
            </p>

            <?php if (!$logueado): ?>
            <div class="rp-steps">
                <div class="rp-step <?= $paso >= 1 ? 'active' : '' ?> <?= $paso > 1 ? 'done' : '' ?>">
                    <span>1</span><small>Teléfono</small>
                </div>
                <div class="rp-step-line <?= $paso > 1 ? 'done' : '' ?>"></div>
                <div class="rp-step <?= $paso >= 2 ? 'active' : '' ?> <?= $paso > 2 ? 'done' : '' ?>">
                    <span>2</span><small>Código</small>
                </div>
                <div class="rp-step-line <?= $paso > 2 ? 'done' : '' ?>"></div>
                <div class="rp-step <?= $paso >= 3 ? 'active' : '' ?>">
                    <span>3</span><small>Contraseña</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="tbt-section" style="padding-top:var(--tbt-s4);">
    <div class="tbt-wrap">
        <div class="auth-card">

            <?php if ($error): ?>
            <div class="auth-alert auth-alert--error">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            <?php if ($success && !$logueado && $paso === 1): ?>
            <div class="auth-alert auth-alert--success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            <?php if ($success && ($logueado || $paso === 2)): ?>
            <div class="auth-alert auth-alert--success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>

            <?php if ($logueado): ?>
            <?php if (!$success): ?>
            <form class="auth-form" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="cambiar_directa">

                <div class="auth-field">
                    <label class="auth-label">Contraseña actual</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                        <input type="password" name="password_actual" class="auth-input" placeholder="Tu contraseña actual" autocomplete="current-password" required>
                        <button type="button" class="auth-toggle-pass" onclick="togglePassword('password_actual','eye-a')"><svg id="eye-a" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
                    </div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">Nueva contraseña</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                        <input type="password" id="password" name="password" class="auth-input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="auth-toggle-pass" onclick="togglePassword('password','eye-p')"><svg id="eye-p" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
                    </div>
                    <div class="pass-strength-wrap"><div class="pass-strength-bar"><div class="pass-strength-fill" id="pass-fill"></div></div><span class="pass-strength-label" id="pass-label"></span></div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">Confirmar nueva contraseña</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        <input type="password" id="password_confirm" name="password_confirm" class="auth-input" placeholder="Repite la nueva contraseña" autocomplete="new-password" required>
                        <button type="button" class="auth-toggle-pass" onclick="togglePassword('password_confirm','eye-c')"><svg id="eye-c" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
                    </div>
                    <span class="auth-hint" id="hint-confirm" style="display:none;"></span>
                </div>
                <button type="submit" class="auth-btn-submit" id="btn-reset">Guardar contraseña</button>
            </form>
            <?php endif; ?>

            <?php elseif ($paso === 1): ?>
            <!-- ══ PASO 1: Ingresar teléfono ══ -->
            <form class="auth-form" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="enviar_codigo">
                <div class="auth-field">
                    <label class="auth-label">Número de teléfono verificado</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                        <input type="tel" name="phone" class="auth-input" placeholder="+527861234567 o 7861234567" autocomplete="tel" required>
                    </div>
                    <span class="auth-hint">Ingresa el número que registraste y verificaste en tu cuenta.</span>
                </div>
                <button type="submit" class="auth-btn-submit">
                    Enviar código por SMS
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </form>

            <?php elseif ($paso === 2): ?>
            <!-- ══ PASO 2: Ingresar código ══ -->
            <form class="auth-form" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="verificar_codigo">
                <div class="auth-field">
                    <label class="auth-label" style="text-align:center;display:block;">Código de 6 dígitos</label>
                    <div class="rp-code-wrap">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" inputmode="numeric" pattern="[0-9]"
                               maxlength="1" class="rp-code-digit"
                               id="digit-<?= $i ?>" autocomplete="off"
                               <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                        <input type="hidden" name="codigo" id="codigo-hidden">
                    </div>
                    <span class="auth-hint" style="text-align:center;display:block;margin-top:.5rem;">
                        Enviado a <?= htmlspecialchars($_SESSION['reset_phone_mask'] ?? '') ?> · Válido 10 minutos
                    </span>
                </div>
                <button type="submit" class="auth-btn-submit" id="btn-verificar" disabled>
                    Verificar código
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </button>
            </form>
            <!-- Reenviar -->
            <form method="POST" style="margin-top:.75rem;text-align:center;">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="reenviar_codigo">
                <button type="submit" class="auth-link" style="background:none;border:none;cursor:pointer;font-size:.85rem;">
                    ¿No llegó? Reenviar código
                </button>
            </form>
            <div style="text-align:center;margin-top:.5rem;">
                <a href="<?= u('/recuperar-password?reiniciar=1') ?>" class="auth-hint" style="font-size:.78rem;">
                    ← Cambiar número
                </a>
            </div>

            <?php elseif ($paso === 3): ?>
            <form class="auth-form" method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="nueva_password">
                <div class="auth-field">
                    <label class="auth-label">Nueva contraseña</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                        <input type="password" id="password" name="password" class="auth-input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="auth-toggle-pass" onclick="togglePassword('password','eye-p')"><svg id="eye-p" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
                    </div>
                    <div class="pass-strength-wrap"><div class="pass-strength-bar"><div class="pass-strength-fill" id="pass-fill"></div></div><span class="pass-strength-label" id="pass-label"></span></div>
                </div>
                <div class="auth-field">
                    <label class="auth-label">Confirmar contraseña</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                        <input type="password" id="password_confirm" name="password_confirm" class="auth-input" placeholder="Repite la contraseña" autocomplete="new-password" required>
                        <button type="button" class="auth-toggle-pass" onclick="togglePassword('password_confirm','eye-c')"><svg id="eye-c" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg></button>
                    </div>
                    <span class="auth-hint" id="hint-confirm" style="display:none;"></span>
                </div>
                <button type="submit" class="auth-btn-submit" id="btn-reset">
                    Guardar nueva contraseña
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-card-footer">
                <?php if ($logueado): ?>
                <a href="<?= u('/perfil') ?>" class="auth-link">← Volver al perfil</a>
                <?php else: ?>
                <a href="<?= u('/login') ?>" class="auth-link">← Volver al login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.rp-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0;
    margin-top: var(--tbt-s3);
}
.rp-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .3rem;
}
.rp-step span {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: var(--tbt-bg-2);
    border: 2px solid var(--tbt-bg-4);
    display: flex; align-items: center; justify-content: center;
    font-family: var(--tbt-font-mono);
    font-size: .75rem; font-weight: 700;
    color: var(--tbt-txt-muted);
    transition: all .3s;
}
.rp-step small {
    font-family: var(--tbt-font-mono);
    font-size: .58rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--tbt-txt-dim);
    transition: color .3s;
}
.rp-step.active span  { border-color: var(--tbt-jade); color: var(--tbt-jade); background: var(--tbt-jade-08); }
.rp-step.active small { color: var(--tbt-jade); }
.rp-step.done span    { background: var(--tbt-jade); border-color: var(--tbt-jade); color: #fff; }
.rp-step.done small   { color: var(--tbt-txt-sub); }

.rp-step-line {
    width: 48px; height: 2px;
    background: var(--tbt-bg-4);
    transition: background .3s;
    margin-bottom: 1.1rem;
}
.rp-step-line.done { background: var(--tbt-jade); }

.rp-code-wrap {
    display: flex;
    gap: .5rem;
    justify-content: center;
    margin-top: .5rem;
}
.rp-code-digit {
    width: 46px; height: 56px;
    text-align: center;
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-bottom: 2px solid var(--tbt-bg-4);
    border-radius: var(--tbt-r-sm);
    color: var(--tbt-txt-white);
    font-family: var(--tbt-font-display);
    font-size: 1.8rem;
    letter-spacing: .1em;
    outline: none;
    transition: border-color .2s;
    caret-color: var(--tbt-jade);
}
.rp-code-digit:focus { border-color: var(--tbt-jade); border-bottom-color: var(--tbt-jade); }
.rp-code-digit.filled { border-bottom-color: var(--tbt-jade); }
@media (max-width: 360px) { .rp-code-digit { width: 38px; height: 48px; font-size: 1.5rem; } }
</style>

<script <?= csp_nonce() ?>>
function togglePassword(id, iconId) {
    const i = document.getElementById(id);
    const e = document.getElementById(iconId);
    if (!i) return;
    const h = i.type === 'password';
    i.type = h ? 'text' : 'password';
    e.innerHTML = h
        ? '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>'
        : '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
}

const passInput = document.getElementById('password');
if (passInput) {
    passInput.addEventListener('input', function() {
        const v = this.value;
        const fill  = document.getElementById('pass-fill');
        const label = document.getElementById('pass-label');
        if (!fill) return;
        let s = 0;
        if (v.length >= 8)           s++;
        if (v.length >= 12)          s++;
        if (/[A-Z]/.test(v))         s++;
        if (/[0-9]/.test(v))         s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        const n = [{w:'0%',c:'',t:''},{w:'25%',c:'#f87171',t:'Débil'},{w:'50%',c:'var(--tbt-amber)',t:'Regular'},{w:'75%',c:'var(--tbt-jade-light)',t:'Buena'},{w:'100%',c:'#4ade80',t:'Fuerte'}];
        const lv = Math.min(s, 4);
        fill.style.width = v.length ? n[lv].w : '0%';
        fill.style.background = n[lv].c;
        label.textContent = v.length ? n[lv].t : '';
        label.style.color = n[lv].c;
    });
}

const confirmInput = document.getElementById('password_confirm');
if (confirmInput) {
    confirmInput.addEventListener('input', function() {
        const pass = document.getElementById('password')?.value ?? '';
        const hint = document.getElementById('hint-confirm');
        const btn  = document.getElementById('btn-reset');
        if (!hint) return;
        if (!this.value.length) { hint.style.display = 'none'; this.classList.remove('auth-input--ok','auth-input--err'); return; }
        hint.style.display = 'block';
        if (pass === this.value) {
            hint.textContent = '✓ Las contraseñas coinciden';
            hint.className   = 'auth-hint auth-hint--ok';
            this.classList.add('auth-input--ok'); this.classList.remove('auth-input--err');
            if (btn) btn.disabled = false;
        } else {
            hint.textContent = '✕ No coinciden';
            hint.className   = 'auth-hint auth-hint--err';
            this.classList.add('auth-input--err'); this.classList.remove('auth-input--ok');
            if (btn) btn.disabled = true;
        }
    });
}

(function() {
    const digits = document.querySelectorAll('.rp-code-digit');
    const hidden = document.getElementById('codigo-hidden');
    const btnVer = document.getElementById('btn-verificar');
    if (!digits.length) return;

    function updateHidden() {
        const val = Array.from(digits).map(d => d.value).join('');
        if (hidden) hidden.value = val;
        if (btnVer) btnVer.disabled = val.length < 6;
        digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
    }

    digits.forEach((d, i) => {
        d.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(-1);
            updateHidden();
            if (this.value && i < digits.length - 1) digits[i + 1].focus();
        });
        d.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && i > 0) {
                digits[i - 1].focus();
                digits[i - 1].value = '';
                updateHidden();
            }
            if (e.key === 'ArrowLeft'  && i > 0)                 digits[i - 1].focus();
            if (e.key === 'ArrowRight' && i < digits.length - 1) digits[i + 1].focus();
        });
        d.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            text.split('').slice(0, 6).forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
            updateHidden();
            const next = Math.min(text.length, digits.length - 1);
            digits[next].focus();
        });
    });

    updateHidden();
})();
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
