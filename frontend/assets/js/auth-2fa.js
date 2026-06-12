(function () {
  const inputs  = document.querySelectorAll('.vt-digit-epic');
  const hidden  = document.getElementById('code-hidden');
  const btn     = document.getElementById('btn-verify');
  const resend  = document.getElementById('btn-resend');
  const script  = document.currentScript || document.querySelector('script[data-method]');
  const method  = script ? script.dataset.method : 'email';
  const timerSec = parseInt(script ? script.dataset.timer : '600') || 0;

  inputs.forEach((inp, idx) => {
    inp.addEventListener('keydown', function (e) {
      if (!/^[0-9]$/.test(e.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(e.key))
        e.preventDefault();
    });
    inp.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '').slice(-1);
      if (this.value) { this.classList.add('filled'); if (idx < inputs.length - 1) inputs[idx + 1].focus(); }
      else this.classList.remove('filled');
      update();
    });
    inp.addEventListener('keyup', function (e) {
      if (e.key === 'Backspace' && !this.value && idx > 0) {
        inputs[idx - 1].focus(); inputs[idx - 1].value = ''; inputs[idx - 1].classList.remove('filled'); update();
      }
    });
    inp.addEventListener('paste', function (e) {
      e.preventDefault();
      const p = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
      p.split('').forEach((c, i) => { if (inputs[i]) { inputs[i].value = c; inputs[i].classList.add('filled'); } });
      if (inputs[p.length - 1]) inputs[p.length - 1].focus();
      update();
    });
  });

  function update() {
    const code = Array.from(inputs).map(i => i.value).join('');
    hidden.value = code;
    btn.disabled = code.length < 6;
    if (code.length === 6) {
      setTimeout(() => {
        if (Array.from(inputs).map(i => i.value).join('').length === 6)
          document.getElementById('form-2fa').submit();
      }, 300);
    }
  }

  if (timerSec > 0) {
    let s = timerSec;
    const tc = document.getElementById('timer-count');
    const tw = document.getElementById('vt-timer');
    if (tc && tw) {
      const iv = setInterval(() => {
        s--;
        const m   = Math.floor(s / 60).toString().padStart(2, '0');
        const sec = (s % 60).toString().padStart(2, '0');
        tc.textContent = m + ':' + sec;
        if (s <= 0) { clearInterval(iv); tw.classList.add('expired'); tw.innerHTML = '⚠ El código expiró. Solicita uno nuevo.'; btn.disabled = true; }
        if (s <= 60) tc.style.color = '#f87171';
      }, 1000);
    }
  }

  if (resend) {
    resend.addEventListener('click', function () {
      let cd = 60;
      const ri = setInterval(() => {
        cd--; resend.textContent = `Reenviar (${cd}s)`; resend.disabled = true;
        if (cd <= 0) { clearInterval(ri); resend.textContent = 'Reenviar código'; resend.disabled = false; }
      }, 1000);
    });
  }

  if (inputs[0]) inputs[0].focus();
})();

(function () {
  const c = document.getElementById('auth-particles');
  if (!c) return;
  const ctx = c.getContext('2d');
  function resize() { c.width = c.offsetWidth; c.height = c.offsetHeight; }
  resize(); window.addEventListener('resize', resize);
  const pts = Array.from({ length: 45 }, () => ({
    x: Math.random() * c.width, y: Math.random() * c.height,
    r: Math.random() * 1.2 + .3, vx: (Math.random() - .5) * .2, vy: (Math.random() - .5) * .2,
    a: Math.random() * .4 + .1, col: Math.random() > .4 ? '#f5a623' : '#fbbf24'
  }));
  function draw() {
    ctx.clearRect(0, 0, c.width, c.height);
    pts.forEach((p, i) => {
      pts.slice(i + 1).forEach(q => {
        const d = Math.hypot(p.x - q.x, p.y - q.y);
        if (d < 80) {
          ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = `rgba(245,166,35,${(1 - d / 80) * .07})`; ctx.lineWidth = .4; ctx.stroke();
        }
      });
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.col; ctx.globalAlpha = p.a; ctx.fill(); ctx.globalAlpha = 1;
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0 || p.x > c.width) p.vx *= -1; if (p.y < 0 || p.y > c.height) p.vy *= -1;
    });
    requestAnimationFrame(draw);
  }
  draw();
})();
