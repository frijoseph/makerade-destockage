/* Makerade Destockage — interactions */
(() => {
  'use strict';
  const B = window.MK_BASE || '';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const money = n => '$' + Number(n).toFixed(2);
  const FREE_SHIP = 49, SHIP_FEE = 4.99;
  const safe = (fn, d) => { try { return fn(); } catch (e) { return d; } };
  const load = (k, d) => safe(() => JSON.parse(localStorage.getItem(k)) ?? d, d);
  const save = (k, v) => safe(() => localStorage.setItem(k, JSON.stringify(v)));
  let CATALOG = {};
  try { CATALOG = JSON.parse($('#catalog').textContent); } catch (e) {}

  /* ---------- store ---------- */
  const store = {
    cart: load('dn_cart', {}),      // {slug: qty}
    wish: load('dn_wish', []),
    sync() { save('dn_cart', this.cart); save('dn_wish', this.wish); render(); },
    count() { return Object.values(this.cart).reduce((a, b) => a + b, 0); },
    sub() { return Object.entries(this.cart).reduce((a, [k, q]) => a + (CATALOG[k]?.price || 0) * q, 0); },
  };

  function render() {
    setTimeout(() => dispatchEvent(new Event('mk:cart')), 0);
    const c = store.count(), w = store.wish.length;
    $$('[data-count="cart"]').forEach(b => setBadge(b, c));
    $$('[data-count="wish"]').forEach(b => setBadge(b, w));
    $$('[data-total]').forEach(t => t.textContent = money(store.sub()));
    $$('[data-wish]').forEach(b => b.classList.toggle('on', store.wish.includes(b.dataset.wish)));
  }
  function setBadge(b, n) {
    const changed = b.textContent != n;
    b.textContent = n; b.dataset.zero = n ? '0' : '1';
    if (changed) { b.classList.remove('bump'); void b.offsetWidth; b.classList.add('bump'); }
  }

  /* ---------- toast ---------- */
  function toast(msg, link) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = `<i><svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></i><span>${msg}${link ? `<a href="${link[1]}">${link[0]}</a>` : ''}</span>`;
    $('#toasts').appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 350); }, 3200);
  }

  /* ---------- actions ---------- */
  document.addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) {
      const id = add.dataset.add;
      const qty = add.dataset.qtyFrom ? +$(add.dataset.qtyFrom).value || 1 : 1;
      store.cart[id] = (store.cart[id] || 0) + qty; store.sync();
      add.classList.add('added');
      const old = add.innerHTML; add.dataset.old = add.dataset.old || old;
      add.innerHTML = '✓ Ajouté';
      setTimeout(() => { add.classList.remove('added'); add.innerHTML = add.dataset.old; }, 1300);
      flyToCart(add);
      if (window.mkAdded) window.mkAdded(id); else toast(`<b>${CATALOG[id]?.name || 'Article'}</b> ajouté au panier.`, ['Voir le panier', B + '/cart']);
      return;
    }
    const wish = e.target.closest('[data-wish]');
    if (wish) {
      e.preventDefault();
      const id = wish.dataset.wish, i = store.wish.indexOf(id);
      i > -1 ? store.wish.splice(i, 1) : store.wish.push(id);
      store.sync();
      toast(i > -1 ? 'Retiré des favoris.' : 'Ajouté à vos favoris.');
      if (document.body.dataset.page === 'wishlist') renderWishlist();
    }
  });

  function flyToCart(from) {
    const target = $('.act-cart .ic-wrap'); if (!target || matchMedia('(prefers-reduced-motion:reduce)').matches) return;
    const a = from.getBoundingClientRect(), b = target.getBoundingClientRect();
    const dot = document.createElement('div');
    Object.assign(dot.style, { position: 'fixed', zIndex: 400, left: a.left + a.width / 2 + 'px', top: a.top + a.height / 2 + 'px', width: '16px', height: '16px', borderRadius: '50%', background: '#237a32', pointerEvents: 'none', transition: 'transform .8s cubic-bezier(.5,-.2,.6,1), opacity .8s' });
    document.body.appendChild(dot);
    requestAnimationFrame(() => { dot.style.transform = `translate(${b.left - a.left - a.width / 2 + 10}px, ${b.top - a.top - a.height / 2 + 10}px) scale(.4)`; dot.style.opacity = '.2'; });
    setTimeout(() => dot.remove(), 850);
  }

  /* ---------- header / scroll ---------- */
  const header = $('#header'), prog = $('#progress'), top = $('#toTop');
  const onScroll = () => {
    const y = scrollY, h = document.documentElement.scrollHeight - innerHeight;
    header.classList.toggle('stuck', y > 20);
    prog.style.width = (h > 0 ? y / h * 100 : 0) + '%';
    top.classList.toggle('show', y > 600);
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();
  top.addEventListener('click', () => scrollTo({ top: 0, behavior: 'smooth' }));
  $$('[data-scroll]').forEach(a => a.addEventListener('click', e => { e.preventDefault(); $(a.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }));

  /* ---------- reveal ---------- */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }), { threshold: .12, rootMargin: '0px 0px -40px' });
    $$('.reveal').forEach((el, i) => { if (!el.style.getPropertyValue('--d') && el.closest('.car-item,.grid-item')) el.style.setProperty('--d', (i % 6) * 70 + 'ms'); io.observe(el); });
  } else $$('.reveal').forEach(el => el.classList.add('in'));

  /* ---------- category menu + drawer ---------- */
  const cm = $('#catmenu');
  $('#catbtn')?.addEventListener('click', e => { e.stopPropagation(); const o = cm.classList.toggle('open'); e.currentTarget.setAttribute('aria-expanded', o); });
  document.addEventListener('click', e => { if (!e.target.closest('#catmenu')) cm?.classList.remove('open'); if (!e.target.closest('#searchForm')) $('#suggest').hidden = true; });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { cm?.classList.remove('open'); document.body.classList.remove('menu-open'); $('#suggest').hidden = true; } });
  $('#burger')?.addEventListener('click', () => document.body.classList.add('menu-open'));
  ['#scrim', '#drawerClose'].forEach(s => $(s)?.addEventListener('click', () => document.body.classList.remove('menu-open')));

  /* ---------- hero slider ---------- */
  const hero = $('#hero');
  if (hero) {
    const slides = $$('.slide', hero), dots = $$('#heroDots button'); let cur = 0, timer;
    const go = n => {
      cur = (n + slides.length) % slides.length;
      slides.forEach((s, i) => s.classList.toggle('is-active', i === cur));
      dots.forEach((d, i) => d.classList.toggle('on', i === cur));
    };
    const play = () => { clearInterval(timer); timer = setInterval(() => go(cur + 1), 6000); };
    $('#heroNext').onclick = () => { go(cur + 1); play(); };
    $('#heroPrev').onclick = () => { go(cur - 1); play(); };
    dots.forEach((d, i) => d.onclick = () => { go(i); play(); });
    hero.addEventListener('mouseenter', () => clearInterval(timer)); hero.addEventListener('mouseleave', play);
    let sx = 0; hero.addEventListener('touchstart', e => sx = e.touches[0].clientX, { passive: true });
    hero.addEventListener('touchend', e => { const d = e.changedTouches[0].clientX - sx; if (Math.abs(d) > 50) { go(cur + (d < 0 ? 1 : -1)); play(); } });
    // parallax on pointer
    hero.addEventListener('mousemove', e => {
      const r = hero.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      $$('.slide.is-active .scene, .slide.is-active .lineup', hero).forEach(el => el.style.transform = `translate(${x * -14}px, ${y * -10}px)`);
    });
    document.addEventListener('visibilitychange', () => document.hidden ? clearInterval(timer) : play());
    play();
  }

  /* ---------- carousels ---------- */
  $$('[data-carousel]').forEach(c => {
    const tr = $('.car-track', c), prev = $('.prev', c), next = $('.next', c);
    const step = () => tr.clientWidth * .85;
    const upd = () => { prev.disabled = tr.scrollLeft < 8; next.disabled = tr.scrollLeft + tr.clientWidth >= tr.scrollWidth - 8; };
    prev.onclick = () => tr.scrollBy({ left: -step() });
    next.onclick = () => tr.scrollBy({ left: step() });
    tr.addEventListener('scroll', upd, { passive: true }); addEventListener('resize', upd); upd();
  });

  /* ---------- live search ---------- */
  const si = $('#searchInput'), sg = $('#suggest');
  si?.addEventListener('input', () => {
    const q = si.value.trim().toLowerCase(); if (q.length < 2) { sg.hidden = true; return; }
    const res = Object.values(CATALOG).filter(p => (p.name + ' ' + p.brand + ' ' + p.variant).toLowerCase().includes(q)).slice(0, 5);
    sg.innerHTML = res.length ? res.map(p => `<a href="${B}/product/${p.slug}/">${p.image ? `<img class="art photo" src="${p.image}" alt="">` : `<svg class="art" style="width:34px;height:40px" viewBox="0 0 120 140"><rect x="30" y="20" width="60" height="100" rx="12" fill="${p.c1}"/></svg>`}<span><b>${p.name}</b><small>${p.variant} · ${money(p.price)}</small></span></a>`).join('') : '<div class="none">Aucun produit trouvé</div>';
    sg.hidden = false;
  });

  /* ---------- listing sort ---------- */
  const sort = $('#sortSel');
  sort?.addEventListener('change', () => {
    const g = $('#pgrid'), items = $$('.grid-item', g), v = sort.value;
    items.sort((a, b) => v === 'low' ? a.dataset.price - b.dataset.price : v === 'high' ? b.dataset.price - a.dataset.price : v === 'off' ? b.dataset.off - a.dataset.off : a.dataset.idx - b.dataset.idx);
    g.animate([{ opacity: .3 }, { opacity: 1 }], { duration: 400 });
    items.forEach(i => g.appendChild(i));
  });

  /* ---------- product detail ---------- */
  $$('[data-qty]').forEach(q => q.addEventListener('click', e => {
    const b = e.target.closest('[data-q]'); if (!b) return; const i = $('input', q);
    i.value = Math.max(1, Math.min(20, +i.value + +b.dataset.q));
  }));
  const tilt = $('#tilt');
  tilt?.parentElement.addEventListener('mousemove', e => {
    const r = tilt.parentElement.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
    tilt.style.transform = `perspective(700px) rotateY(${x * 18}deg) rotateX(${-y * 14}deg) scale(1.04)`;
  });
  tilt?.parentElement.addEventListener('mouseleave', () => tilt.style.transform = '');

  /* ---------- cart page ---------- */
  const list = $('#cartList');
  function renderCart() {
    if (!list) return;
    const ids = Object.keys(store.cart).filter(k => CATALOG[k]);
    if (!ids.length) {
      list.innerHTML = `<div class="empty"><svg class="ic" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2.5l2.2 10.5h10.5L20.500 7H6.200"/><circle cx="9" cy="19" r="1.500"/><circle cx="17" cy="19" r="1.500"/></svg><h3>Votre panier est vide</h3><p>Vous n'avez encore rien ajouté.</p><a class="btn btn-primary" href="${B}/shop/">Commencer mes achats</a></div>`;
    } else {
      list.innerHTML = ids.map(id => {
        const p = CATALOG[id], q = store.cart[id], art = $(`[data-art="${id}"]`)?.innerHTML || '';
        return `<div class="citem" data-id="${id}">${art}<div><h4>${p.name}</h4><p>${p.variant}</p><b>${money(p.price)}</b></div>
          <div class="citem-r"><div class="qty"><button data-c="-1" aria-label="Moins">−</button><input value="${q}" readonly><button data-c="1" aria-label="Plus">+</button></div>
          <button class="rm" data-rm="${id}">Retirer</button></div></div>`;
      }).join('');
    }
    const sub = store.sub(), ship = sub === 0 || sub >= FREE_SHIP ? 0 : SHIP_FEE;
    $('[data-sub]').textContent = money(sub); $('[data-ship]').textContent = ship ? money(ship) : 'Gratuite';
    $('[data-grand]').textContent = money(sub + ship);
    $('#shipBar').style.width = Math.min(100, sub / FREE_SHIP * 100) + '%';
    $('#shipNote').textContent = sub === 0 ? '' : sub >= FREE_SHIP ? '🎉 Livraison gratuite débloquée !' : `Ajoutez ${money(FREE_SHIP - sub)} pour la livraison gratuite.`;
  }
  list?.addEventListener('click', e => {
    const row = e.target.closest('.citem'); if (!row) return; const id = row.dataset.id;
    const c = e.target.closest('[data-c]'), rm = e.target.closest('[data-rm]');
    if (c) { store.cart[id] = Math.max(1, store.cart[id] + +c.dataset.c); store.sync(); renderCart(); }
    if (rm) { row.classList.add('out'); setTimeout(() => { delete store.cart[id]; store.sync(); renderCart(); }, 330); }
  });
  $('#checkout')?.addEventListener('click', () => store.count() ? toast('Paiement en démonstration — bientôt disponible.') : toast('Votre panier est vide.'));

  /* ---------- wishlist page ---------- */
  function renderWishlist() {
    const g = $('#wishGrid'); if (!g) return;
    $$('[data-wish-item]', g).forEach(el => el.hidden = !store.wish.includes(el.dataset.wishItem));
    $('#wishEmpty').hidden = store.wish.length > 0;
    $$('.reveal', g).forEach(r => r.classList.add('in'));
  }

  /* ---------- forms ---------- */
  const valid = f => { let ok = true; $$('[required]', f).forEach(i => { const bad = !i.value.trim() || (i.type === 'email' && !/^\S+@\S+\.\S+$/.test(i.value)); i.classList.toggle('invalid', bad); if (bad) ok = false; }); return ok; };
  $('#newsForm')?.addEventListener('submit', e => { e.preventDefault(); const f = e.target; if (!valid(f)) { f.classList.remove('err'); void f.offsetWidth; f.classList.add('err'); return; } f.reset(); toast('Merci pour votre inscription ! 🎉'); });
  $('#contactForm')?.addEventListener('submit', e => { e.preventDefault(); if (valid(e.target)) { e.target.reset(); toast('Message envoyé. Nous répondons sous 24 h.'); } });
  $$('.authform').forEach(f => f.addEventListener('submit', e => { e.preventDefault(); if (valid(f)) toast("Démonstration — l'authentification n'est pas encore connectée."); }));
  $$('.tabs button').forEach(b => b.addEventListener('click', () => {
    $$('.tabs button').forEach(x => x.classList.toggle('on', x === b));
    $$('[data-pane]').forEach(p => { p.hidden = p.dataset.pane !== b.dataset.tab; });
  }));
  $('#trackForm')?.addEventListener('submit', e => {
    e.preventDefault(); if (!valid(e.target)) return;
    const s = $('#steps'); s.hidden = false; $$('li', s).forEach((li, i) => { li.style.animation = `up .5s ${i * .18}s var(--ease) both`; });
  });

  /* ---------- page transitions ---------- */
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]'); if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target || a.hasAttribute('data-scroll')) return;
    const u = new URL(a.href, location.href);
    if (u.origin !== location.origin || (u.pathname === location.pathname && u.search === location.search) || u.hash && u.pathname === location.pathname) return;
    e.preventDefault(); $('#main').classList.add('leaving'); setTimeout(() => location.href = u.href, 220);
  });
  addEventListener('pageshow', e => { if (e.persisted) $('#main').classList.remove('leaving'); });

  window.MK = { store, toast, money };
  render(); renderCart(); renderWishlist();
})();
