<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
if ($auth->isLoggedIn()) safe_redirect(u('/'));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit('registro_form', 5, 300)) {
        $error = 'Demasiados intentos. Espera unos minutos.';
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } elseif (ENV === 'production' && !verify_recaptcha($_POST['g-recaptcha-response'] ?? '')) {
        $error = 'Por favor verifica que no eres un robot.';
    } else {
        $username         = sanitize_string($_POST['username'] ?? '', 50);
        $email            = sanitize_email($_POST['email'] ?? '');
        $phone            = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? '');
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if (!$email) {
            $error = 'El email no es válido.';
        } elseif (empty($phone) || strlen($phone) < 10) {
            $error = 'Ingresa un número de teléfono válido con código de país. Ej: +527861234567';
        } else {
            $phoneNorm = preg_replace('/[^0-9+]/', '', $phone);
            if (!str_starts_with($phoneNorm, '+')) $phoneNorm = '+52' . $phoneNorm;
            $dbCheck    = Database::getInstance();
            $phoneEnUso = $dbCheck->fetch(
                "SELECT id FROM users WHERE phone = ? AND phone_verified = 1 LIMIT 1",
                [$phoneNorm]
            );
            if ($phoneEnUso) {
                $error = 'Ese número de teléfono ya está asociado a otra cuenta.';
            } else {
            $resultado = $auth->register($username, $email, $password, $password_confirm);
            if ($resultado['success']) {
                $user_id = $resultado['user_id'];
                Mailer::sendWelcome($email, $username);
                $sms = $auth->sendPhoneVerification($user_id, $phone);
                if ($sms['success']) {
                    $_SESSION['verify_user_id'] = $user_id;
                    $_SESSION['verify_phone']   = $phone;
                    safe_redirect(u('/verificar-telefono'));
                } else {
                    $auth->setFlash('success', '¡Cuenta creada! No pudimos enviar el SMS. Podrás verificar tu teléfono desde tu perfil.');
                    safe_redirect(u('/login'));
                }
            } else {
                $error = $resultado['error'];
            }
            } 
        }
    }
}

$page_title     = 'Crear Cuenta | Latin Shop';
$page_canonical = u('/registrar');
$extra_css      = ['auth-epic.css'];
include INCLUDES_PATH . '/header.php';
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
        <svg viewBox="0 0 120 140" fill="none">
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
      <h1 class="auth-epic-title">Únete al<br>Imperio</h1>
      <p class="auth-epic-subtitle">Lords Mobile · Premium Services</p>

      <div class="auth-benefits">
        <div class="auth-benefit">
          <span class="auth-benefit__icon">💬</span>
          <span class="auth-benefit__text">Comenta en todas las páginas</span>
        </div>
        <div class="auth-benefit">
          <span class="auth-benefit__icon">⭐</span>
          <span class="auth-benefit__text">Valora servicios con estrellas</span>
        </div>
        <div class="auth-benefit">
          <span class="auth-benefit__icon">🔐</span>
          <span class="auth-benefit__text">Autenticación en 2 pasos</span>
        </div>
        <div class="auth-benefit">
          <span class="auth-benefit__icon">✓</span>
          <span class="auth-benefit__text">Insignia de verificado</span>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-epic-right">
    <div class="auth-glass-card" style="max-width:480px;">

      <div class="auth-glass-logo">
        <div class="auth-glass-logo-icon" style="background:rgba(245,166,35,.1);border-color:rgba(245,166,35,.3);box-shadow:0 0 24px rgba(245,166,35,.15);">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
                  fill="rgba(245,166,35,.4)" stroke="#f5a623" stroke-width=".5"/>
          </svg>
        </div>
        <span class="auth-glass-logo-title">Crear cuenta</span>
        <span class="auth-glass-logo-sub">Latin Shop · Lords Mobile</span>
      </div>

      <h2 class="auth-glass-heading">Comienza tu aventura</h2>
      <p class="auth-glass-subheading">Regístrate gratis — aprobación por admin requerida</p>

      <?php if ($error): ?>
        <div class="auth-glass-alert auth-glass-alert--error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form class="auth-glass-form" method="POST" action="<?= u('/registrar') ?>" novalidate id="form-reg">
        <?= csrf_field() ?>

        <!-- Username -->
        <div class="auth-glass-field">
          <label class="auth-glass-label" for="username">Nombre de usuario</label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            <input type="text" id="username" name="username" class="auth-glass-input"
              placeholder="minombre123"
              value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="username" minlength="3" maxlength="50" required>
          </div>
          <span class="auth-glass-hint" id="hint-user">Mínimo 3 caracteres. Letras, números, guiones y puntos.</span>
        </div>

        <div class="auth-glass-field">
          <label class="auth-glass-label" for="email">Correo electrónico</label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            <input type="email" id="email" name="email" class="auth-glass-input"
              placeholder="tu@email.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="email" required>
          </div>
        </div>

        <div class="auth-glass-field">
          <label class="auth-glass-label" for="phone">
            Teléfono
            <span class="auth-glass-label-badge">SMS</span>
          </label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
            <input type="tel" id="phone" name="phone" class="auth-glass-input"
              placeholder="+52 786 228 6246"
              value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
              autocomplete="tel" required>
          </div>
          <span class="auth-glass-hint">Incluye código de país (+52). Recibirás un código SMS.</span>
        </div>

        <div class="auth-glass-field">
          <label class="auth-glass-label" for="password">Contraseña</label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            <input type="password" id="password" name="password" class="auth-glass-input"
              placeholder="Mínimo 8 caracteres"
              autocomplete="new-password" minlength="8" required>
            <button type="button" class="auth-glass-toggle-pass" onclick="toggleP('password','ep')">
              <svg id="ep" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            </button>
          </div>
          <div class="auth-glass-strength-wrap">
            <div class="auth-glass-strength-bar"><div class="auth-glass-strength-fill" id="sf"></div></div>
            <span class="auth-glass-strength-label" id="sl"></span>
          </div>
        </div>

        <div class="auth-glass-field">
          <label class="auth-glass-label" for="password_confirm">Confirmar contraseña</label>
          <div class="auth-glass-input-wrap">
            <svg class="auth-glass-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            <input type="password" id="password_confirm" name="password_confirm" class="auth-glass-input"
              placeholder="Repite tu contraseña"
              autocomplete="new-password" required>
            <button type="button" class="auth-glass-toggle-pass" onclick="toggleP('password_confirm','ec')">
              <svg id="ec" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
            </button>
          </div>
          <span class="auth-glass-hint" id="hint-confirm" style="display:none;"></span>
        </div>

        <?php if (ENV === 'production'): ?>
          <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
        <?php endif; ?>

        <div class="auth-glass-info">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="flex-shrink:0;color:var(--tbt-jade-light);margin-top:1px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          <span>Se enviará un <strong>código SMS</strong> para verificar tu teléfono. Tu cuenta quedará como <strong>pendiente</strong> hasta aprobación del admin.</span>
        </div>

        <button type="submit" class="auth-glass-submit auth-glass-submit--amber" id="btn-reg">
          Unirse al imperio
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </button>
      </form>

      <div class="auth-glass-footer">
        ¿Ya tienes cuenta? <a href="<?= u('/login') ?>" class="auth-glass-link">Iniciar sesión</a>
      </div>
    </div>
  </div>

</div>

<?php if (ENV === 'production'): ?>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>

<style>
.auth-benefits{display:flex;flex-direction:column;gap:.75rem;margin-top:1.5rem;}
.auth-benefit{display:flex;align-items:center;gap:.75rem;background:rgba(255,255,255,.03);border:1px solid rgba(232,96,44,.1);border-radius:10px;padding:.6rem 1rem;backdrop-filter:blur(8px);transition:border-color .3s,transform .3s;}
.auth-benefit:hover{border-color:rgba(232,96,44,.3);transform:translateX(4px);}
.auth-benefit__icon{font-size:1.1rem;flex-shrink:0;}
.auth-benefit__text{font-size:.8rem;color:var(--tbt-txt-sub);font-family:var(--tbt-font-body);}

.auth-glass-submit--amber{
    background:linear-gradient(135deg,#f5a623 0%,#e08800 50%,#f5a623 100%) !important;
    background-size:200% 100% !important;
    color:var(--tbt-bg-base) !important;
}
.auth-glass-submit--amber:hover{
    box-shadow:0 8px 32px rgba(245,166,35,.4),0 0 0 1px rgba(245,166,35,.3) !important;
}
</style>

<script <?= csp_nonce() ?>>
function toggleP(f,i){
  const inp=document.getElementById(f);
  const ic=document.getElementById(i);
  const h=inp.type==='password';
  inp.type=h?'text':'password';
  ic.innerHTML=h?'<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>':'<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
}

document.getElementById('password').addEventListener('input',function(){
  const v=this.value,f=document.getElementById('sf'),l=document.getElementById('sl');
  let s=0;
  if(v.length>=8)s++;if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const n=[{w:'0%',c:'rgba(255,255,255,.06)',t:''},{w:'25%',c:'#f87171',t:'Débil'},{w:'50%',c:'#f5a623',t:'Regular'},{w:'75%',c:'var(--tbt-jade-light)',t:'Buena'},{w:'100%',c:'#4ade80',t:'Fuerte'}];
  const i=Math.min(s,4);
  f.style.width=v.length===0?'0%':n[i].w;f.style.background=n[i].c;
  l.textContent=v.length===0?'':n[i].t;l.style.color=n[i].c;
});

document.getElementById('password_confirm').addEventListener('input',function(){
  const p=document.getElementById('password').value;
  const h=document.getElementById('hint-confirm');
  const b=document.getElementById('btn-reg');
  if(!this.value){h.style.display='none';this.classList.remove('auth-glass-input--ok','auth-glass-input--err');return;}
  h.style.display='block';
  if(p===this.value){
    h.textContent='✓ Las contraseñas coinciden';h.className='auth-glass-hint auth-glass-hint--ok';
    this.classList.add('auth-glass-input--ok');this.classList.remove('auth-glass-input--err');
    if(b)b.disabled=false;
  }else{
    h.textContent='✕ Las contraseñas no coinciden';h.className='auth-glass-hint auth-glass-hint--err';
    this.classList.add('auth-glass-input--err');this.classList.remove('auth-glass-input--ok');
    if(b)b.disabled=true;
  }
});

document.getElementById('username').addEventListener('input',function(){
  const v=this.value,h=document.getElementById('hint-user'),r=/^[a-zA-Z0-9_.-]+$/;
  if(!v){h.className='auth-glass-hint';h.textContent='Mínimo 3 caracteres. Letras, números, guiones y puntos.';this.classList.remove('auth-glass-input--ok','auth-glass-input--err');}
  else if(v.length<3){h.className='auth-glass-hint auth-glass-hint--err';h.textContent='✕ Mínimo 3 caracteres';this.classList.add('auth-glass-input--err');this.classList.remove('auth-glass-input--ok');}
  else if(!r.test(v)){h.className='auth-glass-hint auth-glass-hint--err';h.textContent='✕ Caracteres no permitidos';this.classList.add('auth-glass-input--err');this.classList.remove('auth-glass-input--ok');}
  else{h.className='auth-glass-hint auth-glass-hint--ok';h.textContent='✓ Nombre válido';this.classList.add('auth-glass-input--ok');this.classList.remove('auth-glass-input--err');}
});

(function(){
  const c=document.getElementById('auth-particles');
  if(!c)return;
  const ctx=c.getContext('2d');
  function resize(){c.width=c.offsetWidth;c.height=c.offsetHeight;}
  resize();window.addEventListener('resize',resize);
  const pts=Array.from({length:55},()=>({
    x:Math.random()*c.width,y:Math.random()*c.height,
    r:Math.random()*1.4+.3,vx:(Math.random()-.5)*.25,vy:(Math.random()-.5)*.25,
    a:Math.random()*.45+.1,
    col:Math.random()>.55?'var(--tbt-jade)':Math.random()>.5?'#f5a623':'var(--tbt-jade-light)'
  }));
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    pts.forEach((p,i)=>{
      pts.slice(i+1).forEach(q=>{
        const d=Math.hypot(p.x-q.x,p.y-q.y);
        if(d<90){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);
          ctx.strokeStyle=`rgba(232,96,44,${(1-d/90)*.07})`;ctx.lineWidth=.4;ctx.stroke();}
      });
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=p.col;ctx.globalAlpha=p.a;ctx.fill();ctx.globalAlpha=1;
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0||p.x>c.width)p.vx*=-1;
      if(p.y<0||p.y>c.height)p.vy*=-1;
    });
    requestAnimationFrame(draw);
  }
  draw();
})();
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
