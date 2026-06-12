<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole(['admin', 'client', 'verified', 'pending']);

$db       = Database::getInstance();
$comments = Comments::getInstance();
$usuario  = $auth->getUser();

$msg_ok  = null;
$msg_err = null;

$user_data = $db->fetch(
    "SELECT id, username, email, role, avatar_url, phone, phone_verified,
            two_fa_enabled, created_at, last_login
     FROM users WHERE id=? LIMIT 1",
    [$usuario['id']]
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'avatar' && isset($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $msg_err = 'Error al subir el archivo.';
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $msg_err = 'La imagen no puede superar 2MB.';
            } else {
                $imgInfo = @getimagesize($file['tmp_name']);
                if (!$imgInfo) {
                    $msg_err = 'El archivo no es una imagen válida.';
                } else {
                    $mime    = $imgInfo['mime'];
                    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
                    if (!in_array($mime, $allowed, true)) {
                        $msg_err = 'Solo JPG, PNG, WEBP o GIF.';
                    } else {
                        $ext      = match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif',default=>'jpg'};
                        $filename = 'avatar_'.$usuario['id'].'_'.time().'.'.$ext;
                        $destDir  = ROOT_PATH.'/frontend/assets/images/avatars/';
                        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                        $old = $db->fetch("SELECT avatar_url FROM users WHERE id=? LIMIT 1", [$usuario['id']]);
                        if ($old && !empty($old['avatar_url'])) {
                            $oldFile = $destDir.basename($old['avatar_url']);
                            if (file_exists($oldFile)) @unlink($oldFile);
                        }
                        if (sanitize_and_save_image($file['tmp_name'], $destDir.$filename, $mime)) {
                            $avatarUrl = ASSETS_URL.'/images/avatars/'.$filename;
                            $db->update("UPDATE users SET avatar_url=? WHERE id=?", [$avatarUrl, $usuario['id']]);
                            $_SESSION['user_avatar'] = $avatarUrl;
                            $msg_ok = 'Avatar actualizado correctamente.';
                            $user_data['avatar_url'] = $avatarUrl;
                        } else {
                            $msg_err = 'No se pudo guardar. Verifica permisos de avatars/.';
                        }
                    }
                }
            }
        }

        elseif ($accion === 'toggle_2fa') {
            $activar = ($_POST['two_fa_value'] ?? '0') === '1';
            $ok = $auth->toggle2FA($usuario['id'], $activar);
            if ($ok) {
                $msg_ok = $activar
                    ? '🔐 Autenticación en 2 pasos activada. Se enviará un código a tu email al iniciar sesión.'
                    : 'Autenticación en 2 pasos desactivada.';
                $user_data['two_fa_enabled'] = $activar ? 1 : 0;
            } else {
                $msg_err = 'No se pudo actualizar la configuración.';
            }
        }

        elseif ($accion === 'enviar_codigo_tel') {
            $phone = !empty($_POST['phone_nuevo'])
                ? preg_replace('/[^0-9+]/', '', $_POST['phone_nuevo'])
                : ($user_data['phone'] ?? '');

            if (empty($phone) || strlen($phone) < 10) {
                $msg_err = 'Ingresa un número válido con código de país. Ej: +527861234567';
            } else {
                $resultado = $auth->sendPhoneVerification($usuario['id'], $phone);
                if ($resultado['success']) {
                    $_SESSION['verify_user_id'] = $usuario['id'];
                    $_SESSION['verify_phone']   = $phone;
                    safe_redirect(u('/verificar-telefono'));
                } else {
                    $msg_err = $resultado['error'];
                }
            }
        }

        elseif ($accion === 'revocar_sesion') {
            $session_id = (int)($_POST['session_id'] ?? 0);
            $ok = $auth->revokeSession($session_id, $usuario['id']);
            $msg_ok  = $ok  ? 'Sesión cerrada correctamente.' : null;
            $msg_err = !$ok ? 'No se pudo cerrar esa sesión.'  : null;
        }

        elseif ($accion === 'revocar_todas') {
            $auth->revokeAllSessions($usuario['id']);
            $msg_ok = 'Todas las sesiones han sido cerradas.';
        }
    }
}

$user_data = $db->fetch(
    "SELECT id, username, email, role, avatar_url, phone, phone_verified,
            two_fa_enabled, created_at, last_login
     FROM users WHERE id=? LIMIT 1",
    [$usuario['id']]
);

$mis_comentarios   = $comments->getByUser($usuario['id'], 30);
$total_comentarios = count($mis_comentarios);
$aprobados         = count(array_filter($mis_comentarios, fn($c) => $c['status']==='approved'));
$pendientes_c      = count(array_filter($mis_comentarios, fn($c) => $c['status']==='pending'));
$sesiones_activas  = $auth->getActiveSessions($usuario['id']);

$page_title     = 'Mi Perfil | Latin Shop';
$page_canonical = u('/perfil');
include INCLUDES_PATH . '/header.php';

$rol_info = [
    'admin'    => ['label'=>'Administrador','class'=>'badge--purple','desc'=>'Tienes acceso total al panel de administración.'],
    'verified' => ['label'=>'Cliente Verificado', 'class'=>'badge--blue',  'desc'=>'Cuenta verificada con teléfono. Acceso al foro y comentarios.'],
    'client'   => ['label'=>'Usuario',             'class'=>'badge--green', 'desc'=>'Puedes comentar en páginas y acceder al foro.'],
    'pending'  => ['label'=>'Pendiente',    'class'=>'badge--amber', 'desc'=>'Tu cuenta está esperando aprobación de un administrador.'],
    'banned'   => ['label'=>'Suspendido',   'class'=>'badge--red',   'desc'=>'Tu cuenta ha sido suspendida.'],
];
$rol = $rol_info[$user_data['role']] ?? $rol_info['pending'];

$phone_masked = '';
if (!empty($user_data['phone'])) {
    $p = $user_data['phone'];
    $phone_masked = substr($p,0,3).str_repeat('*',max(0,strlen($p)-7)).substr($p,-4);
}
?>

<section class="tbt-hero" style="padding-bottom:0;">
    <div class="tbt-wrap">
        <div class="tbt-reveal tbt-visible" style="max-width:600px;margin:0 auto;text-align:center;">
            <div class="tbt-badge tbt-badge--jade tbt-mb-3"><span class="tbt-badge__dot"></span>Mi cuenta</div>
            <h1 class="tbt-h-xl tbt-mb-3">hola, <span class="tbt-jade"><?= htmlspecialchars($user_data['username'],ENT_QUOTES,'UTF-8') ?></span></h1>
        </div>
    </div>
</section>

<section class="tbt-section" style="padding-top:var(--tbt-s3);">
<div class="tbt-wrap">


<?php if ($msg_ok): ?>
    <div class="pf-alert pf-alert--ok" style="max-width:900px;margin:0 auto var(--tbt-s3);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        <?= htmlspecialchars($msg_ok,ENT_QUOTES,'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if ($msg_err): ?>
    <div class="pf-alert pf-alert--err" style="max-width:900px;margin:0 auto var(--tbt-s3);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <?= htmlspecialchars($msg_err,ENT_QUOTES,'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="pf-grid">

   
    <div class="pf-sidebar">

        
        <div class="pf-card">
         
            <div class="pf-avatar-wrap">
                <div class="pf-avatar-container" id="avatar-container">
                    <?php if (!empty($user_data['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($user_data['avatar_url'],ENT_QUOTES,'UTF-8') ?>" alt="" class="pf-avatar">
                    <?php else: ?>
                        <div class="pf-avatar pf-avatar--default"><?= Comments::getInitials($user_data['username']) ?></div>
                    <?php endif; ?>
                    <label for="avatar-input" class="pf-avatar-overlay">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        Cambiar
                    </label>
                </div>
                <form method="POST" action="<?= u('/perfil') ?>" enctype="multipart/form-data" id="avatar-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="avatar">
                    <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                </form>
            </div>

            <div class="pf-info">
                <h2 class="pf-username"><?= htmlspecialchars($user_data['username'],ENT_QUOTES,'UTF-8') ?></h2>
                <p class="pf-email"><?= htmlspecialchars($user_data['email'],ENT_QUOTES,'UTF-8') ?></p>
                <span class="pf-badge pf-<?= $rol['class'] ?>"><?= $rol['label'] ?></span>
                <p class="pf-rol-desc"><?= $rol['desc'] ?></p>
            </div>

            <div class="pf-datos">
                <div class="pf-dato"><span>Miembro desde</span><span><?= date('d/m/Y',strtotime($user_data['created_at'])) ?></span></div>
                <div class="pf-dato"><span>Último acceso</span><span><?= $user_data['last_login'] ? date('d/m/Y H:i',strtotime($user_data['last_login'])) : 'Esta sesión' ?></span></div>
                <div class="pf-dato">
                    <span>Teléfono</span>
                    <span><?php
                        if ($user_data['phone_verified'])        echo '<span style="color:#4ade80;">✓ Verificado</span>';
                        elseif (!empty($user_data['phone']))     echo '<span style="color:var(--tbt-amber);">Sin verificar</span>';
                        else                                     echo '<span style="color:var(--tbt-txt-dim);">No registrado</span>';
                    ?></span>
                </div>
                <div class="pf-dato">
                    <span>2FA</span>
                    <span><?= $user_data['two_fa_enabled'] ? '<span style="color:#4ade80;">🔐 Activo</span>' : '<span style="color:var(--tbt-txt-dim);">Inactivo</span>' ?></span>
                </div>
                <div class="pf-dato"><span>Comentarios ✓</span><span style="color:#4ade80;font-family:var(--tbt-font-mono);"><?= $aprobados ?></span></div>
                <?php if ($pendientes_c > 0): ?>
                    <div class="pf-dato"><span>Pendientes</span><span style="color:var(--tbt-amber);font-family:var(--tbt-font-mono);"><?= $pendientes_c ?></span></div>
                <?php endif; ?>
            </div>

            
            <?php if ($user_data['role']==='admin'): ?>
                <a href="<?= u('/admin') ?>" class="pf-btn pf-btn--jade" style="margin-top:var(--tbt-s2);">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    Panel de administración
                </a>
            <?php endif; ?>
            <a href="<?= u('/logout') ?>" onclick="return confirm('¿Cerrar sesión?')" class="pf-btn pf-btn--red" style="margin-top:var(--tbt-s1);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Cerrar sesión
            </a>
        </div>

      
        <div class="pf-card">
            <div class="pf-section-header" style="margin-bottom:var(--tbt-s2);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--tbt-jade-light);flex-shrink:0;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                <h3 class="pf-section-title">Seguridad</h3>
            </div>

           
            <?php if (!$user_data['phone_verified']): ?>
                <div class="pf-verify-phone-box">
                    <div class="pf-verify-phone-header">
                        <span class="pf-verify-phone-icon">📱</span>
                        <div>
                            <p class="pf-verify-phone-title">Verifica tu teléfono</p>
                            <p class="pf-verify-phone-desc">Necesario para activar el 2FA y obtener mayor seguridad.</p>
                        </div>
                    </div>

                    <?php if (!empty($user_data['phone'])): ?>
                       
                        <p class="pf-verify-phone-num">
                            Número: <strong><?= htmlspecialchars($phone_masked, ENT_QUOTES, 'UTF-8') ?></strong>
                        </p>
                        <form method="POST" action="<?= u('/perfil') ?>" style="margin-top:.5rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="enviar_codigo_tel">
                            <button type="submit" class="pf-btn-verify">
                                📲 Enviar código SMS
                            </button>
                        </form>
                        <p class="pf-verify-phone-change">
                            ¿Número incorrecto?
                            <button type="button" onclick="document.getElementById('pf-change-phone').style.display='block';this.parentElement.style.display='none';" class="pf-link-inline">Cambiar número</button>
                        </p>
                        <div id="pf-change-phone" style="display:none;margin-top:.5rem;">
                            <form method="POST" action="<?= u('/perfil') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="enviar_codigo_tel">
                                <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                    <input type="tel" name="phone_nuevo"
                                           placeholder="+52 786 228 6246"
                                           class="pf-phone-input">
                                    <button type="submit" class="pf-btn-verify" style="white-space:nowrap;">
                                        Enviar código
                                    </button>
                                </div>
                                <p style="font-size:var(--tbt-text-2xs);color:var(--tbt-txt-dim);margin-top:.3rem;">Incluye código de país. Ej: +527861234567</p>
                            </form>
                        </div>

                    <?php else: ?>
                     
                        <form method="POST" action="<?= u('/perfil') ?>" style="margin-top:.75rem;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="enviar_codigo_tel">
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                <input type="tel" name="phone_nuevo"
                                       placeholder="+52 786 228 6246"
                                       class="pf-phone-input">
                                <button type="submit" class="pf-btn-verify" style="white-space:nowrap;">
                                    📲 Verificar
                                </button>
                            </div>
                            <p style="font-size:var(--tbt-text-2xs);color:var(--tbt-txt-dim);margin-top:.3rem;">Incluye código de país. Ej: +527861234567</p>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            
            <div class="pf-2fa-row">
                <div class="pf-2fa-info">
                    <span class="pf-2fa-label">Autenticación en 2 pasos</span>
                    <span class="pf-2fa-desc">
                        <?php if ($user_data['two_fa_enabled']): ?>
                            <span style="color:#4ade80;">🔐 Activa — código por email al iniciar sesión</span>
                        <?php else: ?>
                            <span style="color:var(--tbt-txt-muted);">Inactiva — actívala para más seguridad</span>
                        <?php endif; ?>
                    </span>
                </div>

                <form method="POST" action="<?= u('/perfil') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="toggle_2fa">
                    <input type="hidden" name="two_fa_value" value="<?= $user_data['two_fa_enabled'] ? '0' : '1' ?>">
                    <button type="submit"
                            class="pf-toggle <?= $user_data['two_fa_enabled'] ? 'pf-toggle--on' : '' ?>"
                            onclick="return confirm('<?= $user_data['two_fa_enabled'] ? '¿Desactivar el 2FA? Tu cuenta será menos segura.' : '¿Activar autenticación en 2 pasos? Se enviará un código a tu email al iniciar sesión.' ?>')">
                        <span class="pf-toggle__track">
                            <span class="pf-toggle__thumb"></span>
                        </span>
                        <span class="pf-toggle__label"><?= $user_data['two_fa_enabled'] ? 'Activo' : 'Inactivo' ?></span>
                    </button>
                </form>
            </div>

           
            <div style="margin-top:var(--tbt-s2);padding-top:var(--tbt-s2);border-top:1px solid var(--tbt-bg-3);">
                <a href="<?= u('/recuperar-password') ?>" class="pf-link-small">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    Cambiar contraseña
                </a>
            </div>
        </div>

    </div>

    <div class="pf-main">

        <div class="pf-card">
            <div class="pf-section-header" style="margin-bottom:var(--tbt-s3);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--tbt-jade-light);flex-shrink:0;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4l6 2.67V11c0 3.5-2.33 6.79-6 7.93C8.33 17.79 6 14.5 6 11V7.67L12 5z"/></svg>
                <h3 class="pf-section-title">Sesiones activas</h3>
                <?php if (count($sesiones_activas) > 1): ?>
                    <form method="POST" action="<?= u('/perfil') ?>" style="margin-left:auto;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="revocar_todas">
                        <button type="submit" class="pf-btn-sm pf-btn-sm--red"
                                onclick="return confirm('¿Cerrar todas las sesiones? Tendrás que volver a iniciar sesión.')">
                            Cerrar todas
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($sesiones_activas)): ?>
                <div class="pf-empty">No hay sesiones activas registradas.</div>
            <?php else: ?>
                <div class="pf-sessions">
                    <?php foreach ($sesiones_activas as $s):
                        $ua     = $s['user_agent'] ?? '';
                        $device = 'Dispositivo desconocido';
                        $icon   = '💻';
                        if (stripos($ua,'Mobile')!==false||stripos($ua,'Android')!==false) { $device='Móvil'; $icon='📱'; }
                        elseif (stripos($ua,'iPad')!==false||stripos($ua,'Tablet')!==false) { $device='Tablet'; $icon='📱'; }
                        elseif (stripos($ua,'Windows')!==false) { $device='Windows'; $icon='🖥'; }
                        elseif (stripos($ua,'Mac')!==false)     { $device='Mac'; $icon='💻'; }
                        elseif (stripos($ua,'Linux')!==false)   { $device='Linux'; $icon='🖥'; }
                        $browser = '';
                        if (stripos($ua,'Chrome')!==false&&stripos($ua,'Edg')===false) $browser='Chrome';
                        elseif (stripos($ua,'Firefox')!==false)                        $browser='Firefox';
                        elseif (stripos($ua,'Safari')!==false&&stripos($ua,'Chrome')===false) $browser='Safari';
                        elseif (stripos($ua,'Edg')!==false)                            $browser='Edge';
                        if ($browser) $device .= ' · '.$browser;
                    ?>
                        <div class="pf-session-item">
                            <div class="pf-session-icon"><?= $icon ?></div>
                            <div class="pf-session-info">
                                <span class="pf-session-device"><?= htmlspecialchars($device,ENT_QUOTES,'UTF-8') ?></span>
                                <span class="pf-session-meta">
                                    <span class="pf-session-ip"><?= htmlspecialchars($s['ip_address'],ENT_QUOTES,'UTF-8') ?></span>
                                    · Iniciada <?= Comments::timeAgo($s['created_at']) ?>
                                    · Expira <?= date('d/m/Y H:i',strtotime($s['expires_at'])) ?>
                                </span>
                            </div>
                            <form method="POST" action="<?= u('/perfil') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="revocar_sesion">
                                <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="pf-btn-sm pf-btn-sm--ghost"
                                        onclick="return confirm('¿Cerrar esta sesión?')">Cerrar</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

       
        <div class="pf-card" style="margin-top:var(--tbt-s3);">
            <div class="pf-section-header" style="margin-bottom:var(--tbt-s3);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="color:var(--tbt-jade-light);flex-shrink:0;"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                <h3 class="pf-section-title">Mis comentarios</h3>
                <span class="pf-badge pf-badge--jade" style="margin-left:auto;"><?= $total_comentarios ?></span>
            </div>

            <?php if (empty($mis_comentarios)): ?>
                <div class="pf-empty">Aún no has enviado ningún comentario.</div>
            <?php else: ?>
                <div class="pf-comments">
                    <?php foreach ($mis_comentarios as $c):
                        $si=['approved'=>['label'=>'Aprobado','class'=>'badge--green'],'pending'=>['label'=>'Pendiente','class'=>'badge--amber'],'rejected'=>['label'=>'Rechazado','class'=>'badge--red']];
                        $s=$si[$c['status']]??['label'=>$c['status'],'class'=>''];
                    ?>
                        <div class="pf-comment pf-comment--<?= $c['status'] ?>">
                            <div class="pf-comment__meta">
                                <span class="pf-badge pf-badge--outline"><?= htmlspecialchars(Comments::slugToLabel($c['page_slug']),ENT_QUOTES,'UTF-8') ?></span>
                                <span class="pf-badge pf-<?= $s['class'] ?>"><?= $s['label'] ?></span>
                                <span class="pf-comment__fecha"><?= Comments::timeAgo($c['created_at']) ?></span>
                            </div>
                            <p class="pf-comment__texto"><?= nl2br(htmlspecialchars($c['content'],ENT_QUOTES,'UTF-8')) ?></p>
                            <?php if (!empty($c['admin_note'])): ?>
                                <div class="pf-comment__nota">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
                                    <?= htmlspecialchars($c['admin_note'],ENT_QUOTES,'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($c['status']==='approved'): ?>
                                <a href="<?= u('/'.$c['page_slug']) ?>#comentarios" class="pf-link-small">Ver en la página →</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>
</div>
</section>

<style>
.pf-grid{display:grid;grid-template-columns:300px 1fr;gap:var(--tbt-s3);align-items:start;max-width:900px;margin:0 auto;}
.pf-card{background:var(--tbt-bg-1);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);padding:var(--tbt-s3);}
.pf-sidebar{display:flex;flex-direction:column;gap:var(--tbt-s2);}
.pf-alert{display:flex;align-items:center;gap:var(--tbt-s1);padding:var(--tbt-s2) var(--tbt-s3);border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:500;}
.pf-alert--ok{background:rgba(34,197,94,.08);color:#4ade80;border:1px solid rgba(34,197,94,.2);}
.pf-alert--err{background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.2);}
.pf-avatar-wrap{display:flex;justify-content:center;margin-bottom:var(--tbt-s3);}
.pf-avatar-container{position:relative;width:88px;height:88px;cursor:pointer;}
.pf-avatar{width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--tbt-jade-30);display:block;}
.pf-avatar--default{width:88px;height:88px;border-radius:50%;background:var(--tbt-jade-15);border:3px solid var(--tbt-jade-30);display:flex;align-items:center;justify-content:center;font-size:var(--tbt-text-xl);font-weight:800;color:var(--tbt-jade-light);font-family:var(--tbt-font-mono);}
.pf-avatar-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,.65);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;opacity:0;transition:opacity var(--tbt-t1);cursor:pointer;color:#fff;font-size:var(--tbt-text-xs);font-weight:600;}
.pf-avatar-container:hover .pf-avatar-overlay{opacity:1;}
.pf-info{text-align:center;margin-bottom:var(--tbt-s3);}
.pf-username{font-size:var(--tbt-text-xl);font-weight:700;color:var(--tbt-txt-white);margin-bottom:.3rem;}
.pf-email{font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);word-break:break-all;margin-bottom:var(--tbt-s1);}
.pf-rol-desc{font-size:var(--tbt-text-xs);color:var(--tbt-txt-sub);margin-top:.5rem;line-height:1.5;}
.pf-datos{display:flex;flex-direction:column;border-top:1px solid var(--tbt-bg-4);margin-top:var(--tbt-s2);padding-top:var(--tbt-s1);}
.pf-dato{display:flex;justify-content:space-between;align-items:center;padding:.45rem 0;border-bottom:1px solid var(--tbt-bg-3);font-size:var(--tbt-text-xs);}
.pf-dato:last-child{border-bottom:none;}
.pf-dato span:first-child{color:var(--tbt-txt-muted);}
.pf-dato span:last-child{color:var(--tbt-txt-white);font-weight:600;}
.pf-btn{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:.6rem;border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity var(--tbt-t1);}
.pf-btn:hover{opacity:.82;}
.pf-btn--jade{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30);}
.pf-btn--red{background:transparent;color:#f87171;border:1px solid rgba(239,68,68,.25);}
.pf-btn--red:hover{background:rgba(239,68,68,.08);}
.pf-badge{display:inline-block;font-size:var(--tbt-text-2xs);font-weight:700;font-family:var(--tbt-font-mono);padding:2px 8px;border-radius:var(--tbt-r-full);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
.pf-badge--purple,.pf-badge--jade{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30);}
.pf-badge--blue{background:rgba(59,130,246,.1);color:#60a5fa;border:1px solid rgba(59,130,246,.25);}
.pf-badge--green{background:rgba(34,197,94,.1);color:#4ade80;border:1px solid rgba(34,197,94,.25);}
.pf-badge--amber{background:var(--tbt-amber-15);color:var(--tbt-amber);border:1px solid var(--tbt-amber-30);}
.pf-badge--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25);}
.pf-badge--outline{background:var(--tbt-bg-3);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5);}
.pf-section-header{display:flex;align-items:center;gap:.5rem;}
.pf-section-title{font-size:var(--tbt-text-md);font-weight:700;color:var(--tbt-txt-white);}


.pf-verify-phone-box{background:var(--tbt-jade-08);border:1px solid var(--tbt-jade-15);border-radius:var(--tbt-r-md);padding:var(--tbt-s2);margin-bottom:var(--tbt-s2);}
.pf-verify-phone-header{display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.6rem;}
.pf-verify-phone-icon{font-size:1.4rem;flex-shrink:0;}
.pf-verify-phone-title{font-size:var(--tbt-text-sm);font-weight:700;color:var(--tbt-txt-white);margin-bottom:.15rem;}
.pf-verify-phone-desc{font-size:var(--tbt-text-xs);color:var(--tbt-txt-sub);line-height:1.5;}
.pf-verify-phone-num{font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);margin-bottom:.4rem;}
.pf-verify-phone-num strong{color:var(--tbt-txt-light);}
.pf-verify-phone-change{font-size:var(--tbt-text-xs);color:var(--tbt-txt-dim);margin-top:.4rem;}
.pf-link-inline{background:none;border:none;color:var(--tbt-jade-light);font-size:inherit;cursor:pointer;padding:0;text-decoration:underline;}
.pf-phone-input{flex:1;background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-5);border-radius:var(--tbt-r-md);color:var(--tbt-txt-white);font-size:var(--tbt-text-xs);padding:.45rem .75rem;outline:none;min-width:140px;transition:border-color var(--tbt-t1);}
.pf-phone-input:focus{border-color:var(--tbt-jade-40);}
.pf-phone-input::placeholder{color:var(--tbt-txt-dim);}
.pf-btn-verify{background:var(--tbt-jade);color:#fff;border:none;border-radius:var(--tbt-r-md);font-size:var(--tbt-text-xs);font-weight:700;padding:.45rem .9rem;cursor:pointer;transition:opacity var(--tbt-t1);}
.pf-btn-verify:hover{opacity:.85;}


.pf-2fa-row{display:flex;align-items:center;justify-content:space-between;gap:var(--tbt-s2);flex-wrap:wrap;padding:var(--tbt-s2) 0;border-top:1px solid var(--tbt-bg-3);}
.pf-2fa-row--disabled{opacity:.6;}
.pf-2fa-info{display:flex;flex-direction:column;gap:.2rem;}
.pf-2fa-label{font-size:var(--tbt-text-sm);font-weight:600;color:var(--tbt-txt-white);}
.pf-2fa-desc{font-size:var(--tbt-text-xs);}
.pf-toggle{display:flex;align-items:center;gap:.5rem;background:none;border:none;cursor:pointer;padding:0;}
.pf-toggle__track{position:relative;width:40px;height:22px;background:var(--tbt-bg-4);border-radius:var(--tbt-r-sm);border:1px solid var(--tbt-bg-5);transition:background .2s,border-color .2s;display:block;flex-shrink:0;}
.pf-toggle__thumb{position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;display:block;}
.pf-toggle--on .pf-toggle__track{background:var(--tbt-jade);border-color:var(--tbt-jade);}
.pf-toggle--on .pf-toggle__thumb{transform:translateX(18px);}
.pf-toggle__label{font-size:var(--tbt-text-xs);font-weight:600;color:var(--tbt-txt-sub);}
.pf-toggle--on .pf-toggle__label{color:#4ade80;}
.pf-link-small{display:inline-flex;align-items:center;gap:.3rem;font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);text-decoration:none;transition:color var(--tbt-t1);}
.pf-link-small:hover{color:var(--tbt-jade-light);}


.pf-sessions{display:flex;flex-direction:column;gap:.5rem;}
.pf-session-item{display:flex;align-items:center;gap:var(--tbt-s2);padding:var(--tbt-s2);background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);transition:border-color var(--tbt-t1);}
.pf-session-item:hover{border-color:var(--tbt-bg-5);}
.pf-session-icon{font-size:1.4rem;flex-shrink:0;}
.pf-session-info{flex:1;min-width:0;}
.pf-session-device{display:block;font-size:var(--tbt-text-sm);font-weight:600;color:var(--tbt-txt-white);}
.pf-session-meta{display:block;font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);font-family:var(--tbt-font-mono);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.pf-session-ip{color:var(--tbt-jade-light);}
.pf-btn-sm{display:inline-flex;align-items:center;justify-content:center;border:none;border-radius:var(--tbt-r-md);font-family:var(--tbt-font-display);font-size:var(--tbt-text-xs);font-weight:600;cursor:pointer;padding:.3rem .75rem;transition:opacity var(--tbt-t1);white-space:nowrap;}
.pf-btn-sm--ghost{background:var(--tbt-bg-3);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5);}
.pf-btn-sm--ghost:hover{color:var(--tbt-txt-white);}
.pf-btn-sm--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25);}
.pf-btn-sm--red:hover{opacity:.8;}

.pf-comments{display:flex;flex-direction:column;gap:var(--tbt-s2);}
.pf-comment{background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);padding:var(--tbt-s2) var(--tbt-s3);border-left:3px solid var(--tbt-bg-5);}
.pf-comment--approved{border-left-color:rgba(34,197,94,.5);}
.pf-comment--pending{border-left-color:var(--tbt-amber-30);}
.pf-comment--rejected{border-left-color:rgba(239,68,68,.4);}
.pf-comment__meta{display:flex;align-items:center;flex-wrap:wrap;gap:.4rem;margin-bottom:.5rem;}
.pf-comment__fecha{font-size:var(--tbt-text-xs);font-family:var(--tbt-font-mono);color:var(--tbt-txt-muted);margin-left:auto;}
.pf-comment__texto{font-size:var(--tbt-text-sm);color:var(--tbt-txt-base);line-height:1.65;word-break:break-word;margin-bottom:.4rem;}
.pf-comment__nota{display:flex;align-items:center;gap:5px;font-size:var(--tbt-text-xs);color:var(--tbt-jade-light);background:var(--tbt-jade-08);border:1px solid var(--tbt-jade-15);border-radius:6px;padding:.3rem var(--tbt-s1);margin-bottom:.4rem;}
.pf-empty{text-align:center;padding:var(--tbt-s3);color:var(--tbt-txt-muted);font-size:var(--tbt-text-sm);background:var(--tbt-bg-2);border:1px dashed var(--tbt-bg-4);border-radius:var(--tbt-r-md);}

@media(max-width:768px){
    .pf-grid{grid-template-columns:1fr;}
    .pf-main{order:-1;}
}
</style>

<script <?= csp_nonce() ?>>
document.getElementById('avatar-input').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2*1024*1024) { alert('La imagen no puede superar 2MB.'); this.value=''; return; }
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatar-container').innerHTML = `
            <img src="${e.target.result}" class="pf-avatar" style="opacity:.6;">
            <label for="avatar-input" class="pf-avatar-overlay" style="opacity:1;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>
                Subiendo...
            </label>`;
        document.getElementById('avatar-form').submit();
    };
    reader.readAsDataURL(file);
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>