
function togglePass() {
  const i   = document.getElementById('password');
  const btn = document.querySelector('.auth-glass-toggle-pass');
  const h   = i.type === 'password';
  i.type    = h ? 'text' : 'password';
  btn.innerHTML = h
    ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>'
    : '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
}

(function () {
  const c = document.getElementById('auth-particles');
  if (!c) return;
  const ctx = c.getContext('2d');
  function resize() { c.width = c.offsetWidth; c.height = c.offsetHeight; }
  resize();
  window.addEventListener('resize', resize);
  const pts = Array.from({ length: 55 }, () => ({
    x: Math.random() * c.width, y: Math.random() * c.height,
    r: Math.random() * 1.4 + .3,
    vx: (Math.random() - .5) * .25, vy: (Math.random() - .5) * .25,
    a: Math.random() * .45 + .1,
    col: Math.random() > .55 ? 'var(--tbt-jade)' : Math.random() > .5 ? '#f5a623' : 'var(--tbt-jade-light)'
  }));
  function draw() {
    ctx.clearRect(0, 0, c.width, c.height);
    pts.forEach((p, i) => {
      pts.slice(i + 1).forEach(q => {
        const d = Math.hypot(p.x - q.x, p.y - q.y);
        if (d < 90) {
          ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y);
          ctx.strokeStyle = `rgba(232,96,44,${(1 - d / 90) * .07})`; ctx.lineWidth = .4; ctx.stroke();
        }
      });
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.col; ctx.globalAlpha = p.a; ctx.fill(); ctx.globalAlpha = 1;
      p.x += p.vx; p.y += p.vy;
      if (p.x < 0 || p.x > c.width) p.vx *= -1;
      if (p.y < 0 || p.y > c.height) p.vy *= -1;
    });
    requestAnimationFrame(draw);
  }
  draw();
})();
