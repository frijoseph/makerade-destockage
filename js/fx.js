/* Makerade Destockage — animations « soin de la maison » */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const reduce = matchMedia('(prefers-reduced-motion:reduce)').matches;
  const rnd = (a, b) => a + Math.random() * (b - a);

  /* ---------- bulles de savon ---------- */
  function bubble(box) {
    const h = box.clientHeight || 400, s = rnd(16, 62);
    const b = document.createElement('i'); b.className = 'bubble';
    b.style.cssText = `width:${s}px;height:${s}px;left:${rnd(2, 96)}%;--dur:${rnd(8, 15)}s;--dx:${rnd(-45, 45)}px;--rise:${h + 90}px`;
    const pop = () => { if (b.classList.contains('pop')) return; b.classList.add('pop'); setTimeout(() => b.remove(), 360); };
    b.addEventListener('pointerenter', pop); b.addEventListener('click', pop);
    b.addEventListener('animationend', e => { if (e.animationName === 'rise') b.remove(); });
    box.appendChild(b);
  }
  if (!reduce) $$('[data-bubbles]').forEach(box => {
    const max = +box.dataset.bubbles || 10; let visible = true;
    if ('IntersectionObserver' in window) new IntersectionObserver(e => visible = e[0].isIntersecting).observe(box);
    for (let i = 0; i < Math.ceil(max / 2); i++) setTimeout(() => bubble(box), i * 350);
    setInterval(() => { if (visible && !document.hidden && box.children.length < max) bubble(box); }, 900);
  });

  /* ---------- étincelles ---------- */
  $$('[data-sparkles]').forEach(box => {
    for (let i = 0; i < +box.dataset.sparkles; i++) {
      const s = document.createElement('i'); s.className = 'sp';
      s.style.cssText = `left:${rnd(6, 92)}%;top:${rnd(8, 88)}%;--s:${rnd(9, 22)}px;--t:${rnd(2.2, 3.8)}s;--dl:${rnd(0, 3)}s`;
      box.appendChild(s);
    }
  });

  const trail = (x, y, big) => {
    const t = document.createElement('i'), s = big ? rnd(7, 16) : rnd(6, 14); t.className = 'trail';
    t.style.cssText = `left:${x - s / 2}px;top:${y - s / 2}px;width:${s}px;height:${s}px;--tx:${rnd(-24, 24)}px`;
    document.body.appendChild(t); setTimeout(() => t.remove(), 1100);
  };

  /* ---------- traînée de bulles sous la souris (hero) ---------- */
  const hero = $('#hero');
  if (hero && !reduce && matchMedia('(pointer:fine)').matches) {
    let last = 0;
    hero.addEventListener('pointermove', e => { const now = performance.now(); if (now - last < 70) return; last = now; trail(e.clientX, e.clientY, true); });
  }

  /* ---------- barre de progression du diaporama + parallaxe ---------- */
  if (hero) {
    const bar = $('#heroBar'), restart = () => { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = 'hbar 6s linear forwards'; };
    new MutationObserver(restart).observe($('#heroTrack'), { subtree: true, attributes: true, attributeFilter: ['class'] });
    restart();
    if (!reduce) addEventListener('scroll', () => {
      const y = Math.min(scrollY, 600);
      $$('.fhero-img').forEach(el => el.style.translate = `0 ${y * .12}px`);
    }, { passive: true });
  }

  /* ---------- compteurs ---------- */
  const fmt = n => n.toLocaleString('fr-FR');
  $$('[data-countto]').forEach(el => {
    const to = +el.dataset.countto, suf = el.dataset.suffix || '';
    const run = () => {
      const t0 = performance.now(), d = 1900;
      const step = now => { const p = Math.min(1, (now - t0) / d), e = 1 - Math.pow(1 - p, 3); el.textContent = fmt(Math.round(to * e)) + suf; if (p < 1) requestAnimationFrame(step); };
      requestAnimationFrame(step);
    };
    if ('IntersectionObserver' in window) { const io = new IntersectionObserver(e => { if (e[0].isIntersecting) { run(); io.disconnect(); } }, { threshold: .4 }); io.observe(el); } else run();
  });

  /* ---------- légère parallaxe des images au survol ---------- */
  if (!reduce && matchMedia('(pointer:fine)').matches) {
    $$('.pcard, .fb').forEach(card => {
      const img = $('.pimg .art, .fb-img', card); if (!img) return;
      card.addEventListener('pointermove', e => {
        const r = card.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
        img.style.translate = `${x * -10}px ${y * -8}px`;
      });
      card.addEventListener('pointerleave', () => img.style.translate = '0 0');
    });
  }

  /* ---------- éclaboussure de bulles à l'ajout au panier ---------- */
  document.addEventListener('click', e => {
    const add = e.target.closest('[data-add]'); if (!add || reduce) return;
    const r = add.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
    for (let i = 0; i < 10; i++) {
      const b = document.createElement('i'), s = rnd(8, 20); b.className = 'burst';
      b.style.cssText = `left:${cx - s / 2}px;top:${cy - s / 2}px;width:${s}px;height:${s}px;--bx:${rnd(-70, 70)}px;--by:${rnd(-90, -20)}px`;
      document.body.appendChild(b); setTimeout(() => b.remove(), 950);
    }
  }, true);

  /* ---------- test de propreté ---------- */
  const stage = $('#ctStage'), cv = $('#ctCanvas');
  if (stage && cv) {
    const ctx = cv.getContext('2d', { willReadFrequently: true }); let W, H, done = false, moved = 0;
    const paint = () => {
      const r = stage.getBoundingClientRect(), dpr = Math.min(devicePixelRatio || 1, 2);
      W = cv.width = Math.round(r.width * dpr); H = cv.height = Math.round(r.height * dpr);
      ctx.globalCompositeOperation = 'source-over';
      const g = ctx.createLinearGradient(0, 0, W, H); g.addColorStop(0, 'rgba(116,106,92,.93)'); g.addColorStop(1, 'rgba(92,84,72,.95)');
      ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);
      for (let i = 0; i < 90; i++) { ctx.fillStyle = `rgba(${rnd(40, 90) | 0},${rnd(36, 80) | 0},${rnd(30, 70) | 0},${rnd(.06, .18)})`; ctx.beginPath(); ctx.arc(rnd(0, W), rnd(0, H), rnd(10, 60) * dpr, 0, 7); ctx.fill(); }
      ctx.strokeStyle = 'rgba(255,255,255,.07)'; ctx.lineWidth = 2 * dpr;
      for (let i = 0; i < 26; i++) { ctx.beginPath(); const x = rnd(0, W); ctx.moveTo(x, rnd(0, H / 2)); ctx.lineTo(x + rnd(-14, 14), rnd(H / 2, H)); ctx.stroke(); }
    };
    paint();
    const erase = e => {
      if (done) return; const r = cv.getBoundingClientRect(), k = W / r.width;
      const x = (e.clientX - r.left) * k, y = (e.clientY - r.top) * k;
      ctx.globalCompositeOperation = 'destination-out';
      const rad = 34 * k, g = ctx.createRadialGradient(x, y, rad * .3, x, y, rad); g.addColorStop(0, 'rgba(0,0,0,1)'); g.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = g; ctx.beginPath(); ctx.arc(x, y, rad, 0, 7); ctx.fill();
      stage.classList.add('touched'); moved++;
      if (!reduce && moved % 6 === 0) trail(e.clientX, e.clientY);
    };
    cv.addEventListener('pointermove', erase);
    cv.addEventListener('pointerdown', e => { cv.setPointerCapture && cv.setPointerCapture(e.pointerId); erase(e); });
    const bar = $('#ctBar'), pct = $('#ctPct');
    setInterval(() => {
      if (done || !moved) return;
      const d = ctx.getImageData(0, 0, W, H).data; let clear = 0, n = 0;
      for (let i = 3; i < d.length; i += 4 * 97) { n++; if (d[i] < 40) clear++; }
      const ratio = clear / n, p = Math.min(100, Math.round(ratio / .75 * 100));
      bar.style.width = p + '%'; pct.textContent = p + ' % nettoyé';
      if (ratio > .75) {
        done = true; bar.style.width = '100%'; pct.textContent = '100 % nettoyé ✨ Bravo !';
        stage.classList.add('clean'); stage.closest('.cleantest').classList.add('done');
        const box = document.createElement('div'); box.className = 'sparkles'; stage.appendChild(box);
        for (let i = 0; i < 14; i++) { const s = document.createElement('i'); s.className = 'sp'; s.style.cssText = `left:${rnd(5, 92)}%;top:${rnd(5, 90)}%;--s:${rnd(12, 26)}px;--t:${rnd(1.2, 2)}s;--dl:${rnd(0, 1)}s`; box.appendChild(s); }
      }
    }, 350);
    addEventListener('resize', () => { if (!done) paint(); });
  }

  /* ---------- écran d'accueil ---------- */
  const sp = $('#splash');
  if (sp && document.documentElement.classList.contains('splash-on')) {
    setTimeout(() => { sp.classList.add('hide'); document.documentElement.classList.remove('splash-on'); try { sessionStorage.setItem('mk_splash', '1'); } catch (e) {} }, reduce ? 200 : 1900);
  }
})();
