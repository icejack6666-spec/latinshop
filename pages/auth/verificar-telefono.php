<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();

if (empty($_SESSION['verify_user_id']) || empty($_SESSION['verify_phone'])) {
    safe_redirect(u('/registrar'));
}

$user_id      = (int)$_SESSION['verify_user_id'];
$phone        = $_SESSION['verify_phone'];
$phone_masked = substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 7)) . substr($phone, -4);

$error   = null;
$success = null;

if (isset($_POST['accion']) && $_POST['accion'] === 'reenviar') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $res = $auth->resendPhoneCode($user_id);
        $success = $res['success'] ? 'Código reenviado a ' . $phone_masked : null;
        $error   = !$res['success'] ? $res['error'] : null;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $code = sanitize_string($_POST['code'] ?? '', 6);
        $res  = $auth->verifyPhone($user_id, $code);
        if ($res['success']) {
            // Email de confirmación
            $db = Database::getInstance();
            $u  = $db->fetch("SELECT email, username FROM users WHERE id=? LIMIT 1", [$user_id]);
            if ($u) Mailer::sendPhoneVerified($u['email'], $u['username']);
            unset($_SESSION['verify_user_id'], $_SESSION['verify_phone']);
            $auth->setFlash('success', '¡Teléfono verificado! Tu cuenta está pendiente de aprobación.');
            safe_redirect(u('/login'));
        } else {
            $error = $res['error'];
        }
    }
}

$page_title     = 'Verificar Teléfono | Latin Shop';
$page_canonical = u('/verificar-telefono');
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
    <span class="auth-rune auth-rune-1">📱</span>
    <span class="auth-rune auth-rune-2">🔒</span>

    <div class="auth-epic-brand">
      <div class="vt-phone-epic" id="phone-icon-wrap">
        <div class="vt-rings">
          <div class="vt-ring vt-ring-1"></div>
          <div class="vt-ring vt-ring-2"></div>
          <div class="vt-ring vt-ring-3"></div>
        </div>
        <div class="vt-phone-core">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
            <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
          </svg>
        </div>
      </div>

      <h1 class="auth-epic-title" style="font-size:2rem;">Verifica tu<br>Teléfono</h1>
      <p class="auth-epic-subtitle">Paso 2 de 3 — Verificación SMS</p>

      <div class="vt-info-box">
        <p style="color:var(--tbt-txt-sub);font-size:.85rem;line-height:1.7;text-align:center;">
          Enviamos un código de <strong style="color:var(--tbt-jade-light);">6 dígitos</strong> a<br>
          <span style="color:var(--tbt-txt-white);font-family:var(--tbt-font-mono);font-size:1rem;letter-spacing:.1em;">
            <?= htmlspecialchars($phone_masked, ENT_QUOTES, 'UTF-8') ?>
          </span>
        </p>
      </div>

      <div class="vt-steps">
        <div class="vt-step vt-step--done">
          <div class="vt-step__dot">✓</div>
          <span>Crear cuenta</span>
        </div>
        <div class="vt-step-line"></div>
        <div class="vt-step vt-step--active">
          <div class="vt-step__dot">2</div>
          <span>Verificar SMS</span>
        </div>
        <div class="vt-step-line"></div>
        <div class="vt-step">
          <div class="vt-step__dot">3</div>
          <span>Aprobación admin</span>
        </div>
      </div>
    </div>
  </div>

  <div class="auth-epic-right">
    <div class="auth-glass-card">

      <div class="auth-glass-logo">
        <div class="auth-glass-logo-icon" style="background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);box-shadow:0 0 24px rgba(34,197,94,.15);">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path d="M17 1H7C5.9 1 5 1.9 5 3v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm-5 20c-.83 0-1.5-.67-1.5-1.5S11.17 18 12 18s1.5.67 1.5 1.5S12.83 21 12 21zm5-4H7V4h10v13z"
                  fill="rgba(34,197,94,.3)" stroke="#4ade80" stroke-width=".5"/>
          </svg>
        </div>
        <span class="auth-glass-logo-title">Código de verificación</span>
        <span class="auth-glass-logo-sub">Ingresa los 6 dígitos recibidos</span>
      </div>

      <?php if ($error): ?>
        <div class="auth-glass-alert auth-glass-alert--error">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-glass-alert auth-glass-alert--success">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= u('/verificar-telefono') ?>" id="form-codigo">
        <?= csrf_field() ?>

        <!-- Inputs de dígitos épicos -->
        <div class="vt-digits-wrap">
          <div class="vt-digits" id="code-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" class="vt-digit-epic"
                     maxlength="1" inputmode="numeric" pattern="[0-9]"
                     autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                     data-index="<?= $i ?>">
            <?php endfor; ?>
          </div>
          <input type="hidden" name="code" id="code-hidden">
        </div>

        <div class="vt-timer-epic" id="vt-timer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="color:var(--tbt-jade-light);"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
          El código expira en <span id="timer-count" style="color:var(--tbt-jade-light);font-weight:700;font-family:var(--tbt-font-mono);">10:00</span>
        </div>

        <button type="submit" class="auth-glass-submit" id="btn-verify" disabled
                style="background:linear-gradient(135deg,#4ade80 0%,#16a34a 50%,#4ade80 100%);color:var(--tbt-bg-base);">
          Verificar teléfono
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </button>
      </form>

      <div style="margin-top:1.25rem;padding-top:1.25rem;border-top:1px solid rgba(232,96,44,.08);text-align:center;">
        <p style="font-size:.8rem;color:var(--tbt-txt-muted);margin-bottom:.5rem;">¿No recibiste el SMS?</p>
        <form method="POST" action="<?= u('/verificar-telefono') ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="accion" value="reenviar">
          <button type="submit" id="btn-resend"
                  style="background:none;border:1px solid rgba(232,96,44,.2);color:var(--tbt-jade-light);
                         font-size:.8rem;font-weight:600;padding:.4rem 1rem;border-radius:8px;
                         cursor:pointer;transition:all .2s;font-family:var(--tbt-font-body);">
            Reenviar código
          </button>
        </form>
      </div>

      <div class="auth-glass-footer">
        <a href="<?= u('/registrar') ?>" class="auth-glass-link">← Volver al registro</a>
      </div>
    </div>
  </div>

</div>

<style>
body{background:var(--tbt-bg-base);}.tbt-site-content{padding:0;}

.vt-phone-epic{position:relative;width:100px;height:100px;margin:0 auto 2rem;display:flex;align-items:center;justify-content:center;}
.vt-rings{position:absolute;inset:0;}
.vt-ring{position:absolute;border-radius:50%;border:1px solid rgba(232,96,44,.2);animation:ringPulse 3s ease-out infinite;}
.vt-ring-1{inset:0;animation-delay:0s;}
.vt-ring-2{inset:-15px;animation-delay:.8s;}
.vt-ring-3{inset:-30px;animation-delay:1.6s;}
@keyframes ringPulse{0%{opacity:0;transform:scale(.8);}50%{opacity:1;}100%{opacity:0;transform:scale(1.2);}}
.vt-phone-core{width:70px;height:70px;border-radius:50%;background:rgba(232,96,44,.1);border:2px solid rgba(232,96,44,.3);display:flex;align-items:center;justify-content:center;color:var(--tbt-jade-light);position:relative;z-index:2;box-shadow:0 0 30px rgba(232,96,44,.2);}
.vt-info-box{background:rgba(255,255,255,.03);border:1px solid rgba(232,96,44,.12);border-radius:12px;padding:1rem 1.25rem;margin:1.5rem 0;}

.vt-steps{display:flex;align-items:center;gap:.5rem;margin-top:1.5rem;}
.vt-step{display:flex;flex-direction:column;align-items:center;gap:.3rem;flex:1;}
.vt-step__dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;background:rgba(255,255,255,.05);border:1px solid rgba(232,96,44,.15);color:var(--tbt-txt-muted);}
.vt-step span{font-size:.6rem;color:var(--tbt-txt-dim);text-align:center;font-family:var(--tbt-font-mono);letter-spacing:.05em;}
.vt-step--done .vt-step__dot{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.4);color:#4ade80;}
.vt-step--active .vt-step__dot{background:var(--tbt-jade-15);border-color:var(--tbt-jade-40);color:var(--tbt-jade-light);box-shadow:0 0 12px rgba(232,96,44,.3);}
.vt-step--active span{color:var(--tbt-jade-light);}
.vt-step-line{flex:none;width:24px;height:1px;background:rgba(232,96,44,.15);}

.vt-digits-wrap{margin:1.25rem 0;}
.vt-digits{display:flex;gap:.5rem;justify-content:center;}
.vt-digit-epic{
  width:50px;height:58px;
  text-align:center;font-size:1.5rem;font-weight:700;font-family:var(--tbt-font-mono);
  background:rgba(255,255,255,.03);
  border:1px solid rgba(232,96,44,.15);
  border-radius:12px;color:var(--tbt-txt-white);
  outline:none;caret-color:var(--tbt-jade);
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.vt-digit-epic:focus{border-color:rgba(232,96,44,.5);box-shadow:0 0 0 3px rgba(232,96,44,.1),0 0 20px rgba(232,96,44,.08);background:rgba(232,96,44,.04);}
.vt-digit-epic.filled{border-color:rgba(232,96,44,.4);background:rgba(232,96,44,.06);}
.vt-digit-epic.error{border-color:rgba(239,68,68,.5);animation:shake .3s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}

.vt-timer-epic{display:flex;align-items:center;justify-content:center;gap:.4rem;font-size:.78rem;color:var(--tbt-txt-muted);text-align:center;margin:.75rem 0 1rem;font-family:var(--tbt-font-mono);}
.vt-timer-epic.expired{color:#f87171;}

@media(max-width:480px){.vt-digit-epic{width:42px;height:50px;font-size:1.2rem;}}
</style>

<script <?= csp_nonce() ?>>
(function(){
  const inputs=document.querySelectorAll('.vt-digit-epic');
  const hidden=document.getElementById('code-hidden');
  const btn=document.getElementById('btn-verify');
  const resend=document.getElementById('btn-resend');

  inputs.forEach((inp,idx)=>{
    inp.addEventListener('keydown',function(e){
      if(!/^[0-9]$/.test(e.key)&&!['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key))e.preventDefault();
    });
    inp.addEventListener('input',function(){
      this.value=this.value.replace(/[^0-9]/g,'').slice(-1);
      if(this.value){this.classList.add('filled');if(idx<inputs.length-1)inputs[idx+1].focus();}
      else this.classList.remove('filled');
      update();
    });
    inp.addEventListener('keyup',function(e){
      if(e.key==='Backspace'&&!this.value&&idx>0){
        inputs[idx-1].focus();inputs[idx-1].value='';inputs[idx-1].classList.remove('filled');update();
      }
    });
    inp.addEventListener('paste',function(e){
      e.preventDefault();
      const p=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
      p.split('').forEach((c,i)=>{if(inputs[i]){inputs[i].value=c;inputs[i].classList.add('filled');}});
      if(inputs[p.length-1])inputs[p.length-1].focus();
      update();
    });
  });

  function update(){
    const code=Array.from(inputs).map(i=>i.value).join('');
    hidden.value=code;
    btn.disabled=code.length<6;
  }

  let s=600;
  const tc=document.getElementById('timer-count');
  const tw=document.getElementById('vt-timer');
  const iv=setInterval(()=>{
    s--;
    const m=Math.floor(s/60).toString().padStart(2,'0');
    const sec=(s%60).toString().padStart(2,'0');
    tc.textContent=m+':'+sec;
    if(s<=0){clearInterval(iv);tw.classList.add('expired');tw.innerHTML='⚠ El código expiró. Solicita uno nuevo.';btn.disabled=true;}
    if(s<=60)tc.style.color='#f87171';
  },1000);

  if(resend){
    resend.addEventListener('click',function(){
      let cd=60;
      const ri=setInterval(()=>{
        cd--;resend.textContent=`Reenviar (${cd}s)`;resend.disabled=true;
        if(cd<=0){clearInterval(ri);resend.textContent='Reenviar código';resend.disabled=false;}
      },1000);
    });
  }

  if(inputs[0])inputs[0].focus();
})();

// Partículas
(function(){
  const c=document.getElementById('auth-particles');
  if(!c)return;
  const ctx=c.getContext('2d');
  function resize(){c.width=c.offsetWidth;c.height=c.offsetHeight;}
  resize();window.addEventListener('resize',resize);
  const pts=Array.from({length:45},()=>({
    x:Math.random()*c.width,y:Math.random()*c.height,
    r:Math.random()*1.2+.3,vx:(Math.random()-.5)*.2,vy:(Math.random()-.5)*.2,
    a:Math.random()*.4+.1,col:Math.random()>.5?'var(--tbt-jade)':'#4ade80'
  }));
  function draw(){
    ctx.clearRect(0,0,c.width,c.height);
    pts.forEach((p,i)=>{
      pts.slice(i+1).forEach(q=>{
        const d=Math.hypot(p.x-q.x,p.y-q.y);
        if(d<80){ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(q.x,q.y);
          ctx.strokeStyle=`rgba(232,96,44,${(1-d/80)*.06})`;ctx.lineWidth=.4;ctx.stroke();}
      });
      ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
      ctx.fillStyle=p.col;ctx.globalAlpha=p.a;ctx.fill();ctx.globalAlpha=1;
      p.x+=p.vx;p.y+=p.vy;
      if(p.x<0||p.x>c.width)p.vx*=-1;if(p.y<0||p.y>c.height)p.vy*=-1;
    });
    requestAnimationFrame(draw);
  }
  draw();
})();
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
