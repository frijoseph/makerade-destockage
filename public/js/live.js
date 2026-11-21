/* Makerade Destockage — interactions « e-commerce vivant » */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const rnd = (a, b) => a + Math.random() * (b - a);
  const money = n => '$' + Number(n).toFixed(2);
  document.documentElement.classList.add('js');
  let CAT = {}; try { CAT = JSON.parse($('#catalog').textContent); } catch (e) {}
  const page = document.body.dataset.page;
  const B = window.MK_BASE || '';

  /* ---------- compte à rebours des ventes flash (jusqu'à minuit) ---------- */
  $$('[data-countdown]').forEach(box => {
    const h = $('[data-h]', box), m = $('[data-m]', box), s = $('[data-s]', box);
    const set = (el, v) => { const t = String(v).padStart(2, '0'); if (el.textContent !== t) { el.textContent = t; el.classList.remove('flip'); void el.offsetWidth; el.classList.add('flip'); } };
    const tick = () => { const end = new Date(); end.setHours(23, 59, 59, 999); const d = Math.max(0, Math.floor((end - Date.now()) / 1000)); set(h, Math.floor(d / 3600)); set(m, Math.floor(d % 3600 / 60)); set(s, d % 60); };
    tick(); setInterval(tick, 1000);
  });

  /* ---------- images : fondu après chargement ---------- */
  $$('.pimg img.art.photo, .fcat-c img').forEach(img => {
    const box = img.parentElement, done = () => { img.classList.add('loaded'); box.classList.remove('skel'); };
    if (img.complete && img.naturalWidth) done(); else { box.classList.add('skel'); img.addEventListener('load', done, { once: true }); img.addEventListener('error', done, { once: true }); }
  });

  /* ---------- quantité directement sur la carte ---------- */
  const MK = () => window.MK;
  const syncCards = () => {
    if (!MK()) return;
    $$('.pcard').forEach(card => {
      const q = MK().store.cart[card.dataset.id] || 0, b = $('.stepper b', card);
      card.classList.toggle('has-qty', q > 0); if (b) b.textContent = q;
    });
  };
  document.addEventListener('click', e => {
    const sb = e.target.closest('.stepper [data-s]'); if (!sb || !MK()) return;
    const id = sb.closest('.pcard').dataset.id, st = MK().store;
    st.cart[id] = (st.cart[id] || 0) + +sb.dataset.s; if (st.cart[id] <= 0) delete st.cart[id]; st.sync();
  });

  /* ---------- panier latéral ---------- */
  const el = document.createElement('aside'); el.className = 'mini'; el.setAttribute('aria-label', 'Panier');
  el.innerHTML = `<div class="mini-h"><h3>Mon panier <span id="miniCount"></span></h3><button class="icon-btn" data-mclose aria-label="Fermer">✕</button></div>
    <div class="mini-body" id="miniBody"></div>
    <div class="mini-f"><div class="ship-bar"><i id="miniBar"></i></div><p class="ship-note" id="miniNote"></p>
    <div class="srow total"><span>Sous-total</span><b id="miniTotal">$0.00</b></div>
    <a href="${B}/cart/" class="btn btn-ghost block">Voir le panier</a><a href="${B}/cart/" class="btn btn-primary block">Commander</a></div>`;
  const scrim = document.createElement('div'); scrim.className = 'mini-scrim';
  document.body.append(scrim, el);
  const open = () => document.body.classList.add('mini-open'), close = () => document.body.classList.remove('mini-open');
  scrim.addEventListener('click', close);
  addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
  const renderMini = () => {
    if (!MK()) return;
    const st = MK().store, ids = Object.keys(st.cart).filter(k => CAT[k]);
    $('#miniCount').textContent = ids.length ? `(${st.count()})` : '';
    $('#miniBody').innerHTML = ids.length ? ids.map(id => {
      const p = CAT[id], q = st.cart[id];
      return `<div class="mi" data-id="${id}">${p.image ? `<img src="${p.image}" alt="">` : '<div class="ph"></div>'}
        <div><b>${p.name}</b><small>${p.variant}</small><span class="mp">${money(p.price * q)}</span></div>
        <div class="mi-r"><div class="mi-q"><button data-ms="-1" aria-label="Moins">−</button><span>${q}</span><button data-ms="1" aria-label="Plus">+</button></div><button class="mi-x" data-mrm>Retirer</button></div></div>`;
    }).join('') : '<div class="mini-empty"><span class="big">🧴</span>Votre panier est vide.<br>Ajoutez des produits pour commencer.</div>';
    const sub = st.sub(); $('#miniTotal').textContent = money(sub);
    const FREE = window.MK_SHIPPING?.freeFrom ?? 49;
    $('#miniBar').style.width = (FREE ? Math.min(100, sub / FREE * 100) : 0) + '%';
    $('#miniNote').textContent = !sub || !FREE ? '' : sub >= FREE ? '🎉 Livraison gratuite débloquée !' : `Ajoutez ${money(FREE - sub)} pour la livraison gratuite.`;
  };
  el.addEventListener('click', e => {
    if (e.target.closest('[data-mclose]')) return close();
    const row = e.target.closest('.mi'); if (!row || !MK()) return; const id = row.dataset.id, st = MK().store;
    const b = e.target.closest('[data-ms]'), rm = e.target.closest('[data-mrm]');
    if (b) { st.cart[id] = Math.max(0, (st.cart[id] || 0) + +b.dataset.ms); if (!st.cart[id]) delete st.cart[id]; st.sync(); }
    if (rm) { delete st.cart[id]; st.sync(); }
  });
  addEventListener('mk:cart', () => { syncCards(); renderMini(); });
  window.mkAdded = id => { if (page === 'cart') MK().toast(`<b>${CAT[id] ? CAT[id].name : 'Article'}</b> ajouté au panier.`); else setTimeout(open, 350); };
  // un clic sur l'icône du panier ouvre le panier latéral
  document.addEventListener('click', e => {
    const c = e.target.closest('.act-cart'); if (!c || page === 'cart') return;
    e.preventDefault(); renderMini(); open();
  }, true);
  setTimeout(() => { syncCards(); renderMini(); }, 60);

  /* ---------- notifications « vient d'acheter » ---------- */
  if (['home', 'shop', 'deals', 'arrivals', 'product'].includes(page)) {
    const names = ['Awa', 'Koffi', 'Fatou', 'Yao', 'Mariam', 'Kouadio', 'Aminata', 'Serge', 'Adjoua', 'Ibrahim', 'Nadège', 'Moussa', 'Aya', 'Désiré'];
    const cities = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'San-Pédro', 'Daloa', 'Korhogo', 'Man', 'Abengourou', 'Grand-Bassam'];
    const show = () => {
      const ps = Object.values(CAT); if (!ps.length || document.hidden) return schedule();
      const p = ps[(Math.random() * ps.length) | 0], n = $('.sale'); if (n) n.remove();
      const box = document.createElement('div'); box.className = 'sale';
      box.innerHTML = `${p.image ? `<img src="${p.image}" alt="">` : ''}<div><b>${names[(Math.random() * names.length) | 0]}</b> de ${cities[(Math.random() * cities.length) | 0]} vient d'acheter<br><a href="${B}/product/${p.slug}/">${p.name}</a><small><span class="ok">✔ Achat vérifié</span> · il y a ${1 + ((Math.random() * 9) | 0)} min</small></div><button aria-label="Fermer">✕</button>`;
      document.body.appendChild(box);
      requestAnimationFrame(() => requestAnimationFrame(() => box.classList.add('show')));
      const hide = () => { box.classList.remove('show'); setTimeout(() => box.remove(), 700); };
      $('button', box).addEventListener('click', hide); setTimeout(hide, 5500); schedule();
    };
    const schedule = () => setTimeout(show, rnd(14000, 24000));
    setTimeout(show, 9000);
  }

  /* ---------- placeholder de recherche « machine à écrire » ---------- */
  const inp = $('#searchInput');
  if (inp) {
    const words = ['une lessive Surf Excel…', 'un savon Dove…', 'du liquide vaisselle…', 'un shampooing…', 'un désinfectant Dettol…', 'un nettoyant WC…'];
    let w = 0, i = 0, dir = 1;
    const tick = () => {
      if (document.activeElement === inp || inp.value) return setTimeout(tick, 700);
      const full = words[w]; i += dir; inp.placeholder = 'Rechercher ' + full.slice(0, i);
      if (dir > 0 && i >= full.length) { dir = -1; return setTimeout(tick, 1500); }
      if (dir < 0 && i <= 0) { dir = 1; w = (w + 1) % words.length; }
      setTimeout(tick, dir > 0 ? 70 : 32);
    };
    setTimeout(tick, 1200);
  }
})();

/* recherche (?q=) côté navigateur : utile pour la version statique */
(() => {
  const q = new URLSearchParams(location.search).get('q'), grid = document.querySelector('#pgrid');
  if (!q || !grid) return;
  const t = q.toLowerCase(); let n = 0;
  grid.querySelectorAll('.grid-item').forEach(g => { const ok = g.textContent.toLowerCase().includes(t); g.hidden = !ok; if (ok) n++; });
  const c = document.querySelector('.count b'); if (c) c.textContent = n;
})();
