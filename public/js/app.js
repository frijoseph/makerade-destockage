/* Makerade Destockage — interactions */
(() => {
  'use strict';
  const B = window.MK_BASE || '';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const SHOP = window.MK_SHOP || {};
  const money = window.mkMoney || (n => Number(n).toFixed(2));
  const FREE_SHIP = SHOP.shipping?.freeFrom ?? 0, SHIP_FEE = SHOP.shipping?.fee ?? 0;
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

  /* ---------- toast (SweetAlert2) ---------- */
  const esc = x => window.MKUI ? MKUI.esc(x) : String(x ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  function toast(msg, link, type = 'success') {
    const html = esc(msg) + (link ? `<a href="${esc(link[1])}">${esc(link[0])}</a>` : '');
    if (window.MKUI) MKUI.toast(html, type, { html: true }); else console.log(msg);
  }

  /* ---------- actions ---------- */
  document.addEventListener('click', e => {
    const add = e.target.closest('[data-add]');
    if (add) {
      const id = add.dataset.add;
      if (CATALOG[id]?.variants?.length) { location.href = B + '/product/' + id; return; }
      let qty = add.dataset.qtyFrom ? +$(add.dataset.qtyFrom).value || 1 : 1;
      const have = store.cart[id] || 0, stock = CATALOG[id]?.stock;
      if (stock != null && have + qty > stock) {
        toast(stock > have ? `Il ne reste que ${stock} en stock.` : (stock ? `Vous avez déjà les ${stock} disponibles dans votre panier.` : 'Ce produit est en rupture de stock.'), null, 'error');
        if (have >= stock) return;
        qty = stock - have;
      }
      store.cart[id] = have + qty; store.sync();
      add.classList.add('added');
      const old = add.innerHTML; add.dataset.old = add.dataset.old || old;
      add.innerHTML = '✓ Ajouté';
      setTimeout(() => { add.classList.remove('added'); add.innerHTML = add.dataset.old; }, 1300);
      flyToCart(add);
      if (window.mkAdded) window.mkAdded(id); else toast(`${CATALOG[id]?.name || 'Article'} ajouté au panier.`, ['Voir le panier', B + '/cart']);
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

  /* ---------- sous-menus (en-tête et tiroir mobile) ---------- */
  $$('.has-sub .sub-toggle').forEach(btn => btn.addEventListener('click', e => {
    e.stopPropagation();
    const li = btn.closest('.has-sub'), open = !li.classList.contains('open');
    $$('.has-sub.open').forEach(x => x.classList.remove('open'));
    li.classList.toggle('open', open);
    btn.setAttribute('aria-expanded', open);
  }));
  document.addEventListener('click', e => { if (!e.target.closest('.has-sub')) $$('.has-sub.open').forEach(x => x.classList.remove('open')); });
  $$('.d-sub-toggle').forEach(btn => btn.addEventListener('click', () => {
    const li = btn.closest('.d-sub'), open = li.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
  }));

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
    const res = Object.values(CATALOG).filter(p => !p.virtual && (p.name + ' ' + p.brand + ' ' + p.variant).toLowerCase().includes(q)).slice(0, 5);
    sg.innerHTML = res.length ? res.map(p => `<a href="${B}/product/${p.slug}/">${p.image ? `<img class="art photo" src="${p.image}" alt="">` : `<svg class="art" style="width:34px;height:40px" viewBox="0 0 120 140"><rect x="30" y="20" width="60" height="100" rx="12" fill="${p.c1}"/></svg>`}<span><b>${esc(p.name)}</b><small>${esc(p.variant)} · ${money(p.price)}</small></span></a>`).join('') : '<div class="none">Aucun produit trouvé</div>';
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
    i.value = Math.max(1, Math.min(+i.max || 99, +i.value + +b.dataset.q));
  }));
  const tilt = $('#tilt');
  tilt?.parentElement.addEventListener('mousemove', e => {
    const r = tilt.parentElement.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
    tilt.style.transform = `perspective(700px) rotateY(${x * 18}deg) rotateX(${-y * 14}deg) scale(1.04)`;
  });
  tilt?.parentElement.addEventListener('mouseleave', () => tilt.style.transform = '');

  /* ---------- envoi JSON au serveur ---------- */
  async function postJson(endpoint, payload) {
    if (!SHOP.endpoints?.[endpoint]) throw new Error('Ce service n’est pas disponible sur cette version du site.');
    let res;
    try {
      res = await fetch(SHOP.endpoints[endpoint], {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': SHOP.token },
        body: JSON.stringify(payload),
      });
    } catch (e) { throw new Error('Connexion impossible. Vérifiez votre réseau et réessayez.'); }
    const data = await res.json().catch(() => ({}));
    if (res.status === 419) throw new Error('Votre session a expiré : rechargez la page puis réessayez.');
    if (!res.ok) throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Une erreur est survenue. Réessayez.'));
    return data;
  }
  const formData = f => Object.fromEntries(new FormData(f));
  const showError = (box, msg) => { if (box) { box.textContent = msg; box.hidden = false; } else toast(msg, null, 'error'); };
  window.MK_postJson = postJson;

  /* ---------- cart page ---------- */
  const list = $('#cartList');
  const cartItems = () => Object.fromEntries(Object.entries(store.cart).filter(([k, q]) => CATALOG[k] && q > 0));
  const zones = SHOP.zones || [];
  const cityField = () => $('#checkoutForm [name=city]');
  const zoneFor = c => zones.find(z => z.name.toLowerCase() === String(c || '').trim().toLowerCase());
  const savedCode = () => load('mk_coupon', '') || '';
  let preview = null, previewTimer = 0;

  function totals() {
    const sub = store.sub(), zone = zoneFor(cityField()?.value);
    const free = zone ? zone.free_threshold : FREE_SHIP, fee = zone ? zone.fee : SHIP_FEE;
    if (preview && Math.abs(preview.subtotal - sub) < .005) return { sub, discount: preview.discount, ship: preview.shipping, grand: preview.total, free, fee, coupon: preview.coupon, zone };
    const ship = sub === 0 || (free > 0 && sub >= free) ? 0 : fee;
    return { sub, discount: 0, ship, grand: sub + ship, free, fee, coupon: null, zone };
  }

  function renderTotals() {
    if (!$('[data-sub]')) return;
    const t = totals();
    $('[data-sub]').textContent = money(t.sub);
    $('[data-ship]').textContent = t.sub === 0 ? money(0) : (t.ship ? money(t.ship) : 'Gratuite');
    $('[data-grand]').textContent = money(t.grand);
    const row = $('[data-discount-row]');
    if (row) {
      row.hidden = !t.coupon;
      if (t.coupon) { $('[data-discount-label]').textContent = `${t.coupon.auto ? 'Promotion' : 'Code'} ${t.coupon.code}`; $('[data-discount]').textContent = t.discount ? '−' + money(t.discount) : t.coupon.benefit; }
    }
    const left = t.free - t.sub;
    $('#shipBar').style.width = (t.free ? Math.min(100, t.sub / t.free * 100) : 0) + '%';
    $('#shipNote').textContent = t.sub === 0 || !t.free ? '' : left <= 0 ? '🎉 Livraison gratuite débloquée !' : `Ajoutez ${money(left)} pour la livraison gratuite.`;
    const info = $('#zoneInfo');
    if (info) info.textContent = t.zone ? `Livraison à ${t.zone.name}${t.zone.delay ? ' · ' + t.zone.delay : ''}` : '';
  }

  function renderPromo() {
    const box = $('#promoBox'); if (!box) return;
    const t = totals(), applied = $('#promoApplied'), form = $('#promoForm'), toggle = $('#promoToggle');
    const manual = !!(t.coupon && !t.coupon.auto);
    applied.hidden = !t.coupon; form.hidden = manual || !form.dataset.open; toggle.hidden = manual || !!form.dataset.open;
    if (t.coupon) {
      $('[data-promo-code]', applied).textContent = t.coupon.code;
      $('[data-promo-benefit]', applied).textContent = t.coupon.auto ? 'promotion automatique · ' + t.coupon.benefit : t.coupon.benefit;
      $('#promoRemove').hidden = t.coupon.auto;
    }
  }

  async function refreshPreview() {
    const items = cartItems();
    if (!SHOP.endpoints?.coupon || !Object.keys(items).length) { preview = null; renderTotals(); renderPromo(); return; }
    const code = savedCode();
    try {
      preview = await postJson('coupon', { items, code: code || null, city: cityField()?.value || null, phone: $('#checkoutForm [name=phone]')?.value || null });
    } catch (e) {
      preview = null;
      if (code) { save('mk_coupon', ''); toast(e.message, null, 'error'); return refreshPreview(); }
    }
    renderTotals(); renderPromo();
  }
  const queuePreview = () => { clearTimeout(previewTimer); previewTimer = setTimeout(refreshPreview, 280); };

  function renderCart() {
    if (!list) return;
    const ids = Object.keys(store.cart).filter(k => CATALOG[k]);
    if (!ids.length) {
      list.innerHTML = `<div class="empty"><svg class="ic" width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2.5l2.2 10.5h10.5L20.500 7H6.200"/><circle cx="9" cy="19" r="1.500"/><circle cx="17" cy="19" r="1.500"/></svg><h3>Votre panier est vide</h3><p>Vous n'avez encore rien ajouté.</p><a class="btn btn-primary" href="${B}/shop/">Commencer mes achats</a></div>`;
    } else {
      list.innerHTML = ids.map(id => {
        const p = CATALOG[id], q = store.cart[id], art = $(`[data-art="${p.base || id}"]`)?.innerHTML || '';
        return `<div class="citem" data-id="${esc(id)}">${art}<div><h4>${esc(p.name)}</h4><p>${esc(p.variant)}</p><b>${money(p.price)}</b></div>
          <div class="citem-r"><div class="qty"><button data-c="-1" aria-label="Moins">−</button><input value="${q}" readonly><button data-c="1" aria-label="Plus">+</button></div>
          <button class="rm" data-rm="${esc(id)}">Retirer</button></div></div>`;
      }).join('');
    }
    renderTotals(); renderPromo(); queuePreview();
  }
  list?.addEventListener('click', e => {
    const row = e.target.closest('.citem'); if (!row) return; const id = row.dataset.id;
    const c = e.target.closest('[data-c]'), rm = e.target.closest('[data-rm]');
    if (c) {
      const stock = CATALOG[id]?.stock, next = Math.max(1, store.cart[id] + +c.dataset.c);
      if (stock != null && next > stock) return toast(`Il ne reste que ${stock} en stock.`, null, 'error');
      store.cart[id] = next; store.sync(); renderCart();
    }
    if (rm) { row.classList.add('out'); setTimeout(() => { delete store.cart[id]; store.sync(); renderCart(); }, 330); }
  });

  /* code promo */
  $('#promoToggle')?.addEventListener('click', () => { $('#promoForm').dataset.open = '1'; renderPromo(); $('#promoInput').focus(); });
  $('#promoForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const code = $('#promoInput').value.trim().toUpperCase(); if (!code) return;
    save('mk_coupon', code);
    await refreshPreview();
    if (savedCode() && preview?.coupon) { toast(`Code ${preview.coupon.code} appliqué : ${preview.coupon.benefit}`); if (SHOP.confetti) MKUI.confetti({ count: 70, duration: 2000 }); $('#promoInput').value = ''; }
  });
  $('#promoRemove')?.addEventListener('click', () => { save('mk_coupon', ''); refreshPreview(); toast('Code promo retiré.'); });
  ['input', 'change'].forEach(ev => document.addEventListener(ev, e => { if (e.target.matches?.('#checkoutForm [name=city], #checkoutForm [name=phone]')) { renderTotals(); queuePreview(); } }));

  /* ---------- checkout : envoie le panier au serveur (prix, promotions et livraison recalculés côté serveur) ---------- */
  const coForm = $('#checkoutForm'), coBtn = $('#checkout'), coErr = $('#checkoutErr');
  if (coForm && SHOP.user) { ['name', 'phone', 'email'].forEach(k => { if (coForm[k] && !coForm[k].value && SHOP.user[k]) coForm[k].value = SHOP.user[k]; }); }
  coBtn?.addEventListener('click', () => {
    if (!store.count()) return toast('Votre panier est vide.', null, 'error');
    if (!SHOP.endpoints?.checkout || !coForm) return toast('La commande en ligne n’est pas disponible sur cette version du site.', null, 'error');
    coBtn.hidden = true; coForm.hidden = false; renderTotals(); queuePreview();
    coForm.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => $('input', coForm).focus({ preventScroll: true }), 350);
  });
  coForm?.addEventListener('submit', async e => {
    e.preventDefault();
    coErr.hidden = true;
    if (!valid(coForm)) return toast('Merci de compléter les champs en rouge.', null, 'error');
    const items = cartItems();
    if (!Object.keys(items).length) return toast('Votre panier est vide.', null, 'error');
    const t = totals(), pay = coForm.querySelector('[name=payment_method]:checked')?.closest('label')?.textContent.trim() || '';
    const ok = await MKUI.confirm({
      title: 'Confirmer votre commande ?', icon: 'question', confirmText: 'Oui, je commande', cancelText: 'Vérifier',
      html: `<div class="co-sum"><div><span>Articles</span><b>${store.count()}</b></div><div><span>Livraison · ${esc(coForm.city.value)}</span><b>${t.ship ? money(t.ship) : 'Gratuite'}</b></div>${t.coupon ? `<div class="disc"><span>${esc(t.coupon.code)}</span><b>${t.discount ? '−' + money(t.discount) : esc(t.coupon.benefit)}</b></div>` : ''}<div class="tot"><span>Total à payer</span><b>${money(t.grand)}</b></div><p>${esc(pay)}${t.zone?.delay ? ' · ' + esc(t.zone.delay) : ''}</p></div>`,
    });
    if (!ok) return;
    const submit = $('#checkoutSubmit', coForm), label = submit.textContent;
    submit.disabled = true; submit.textContent = 'Envoi en cours…';
    try {
      const data = await postJson('checkout', { ...formData(coForm), items, coupon_code: savedCode() || null });
      store.cart = {}; save('mk_coupon', ''); store.sync();
      location.href = data.redirect;
    } catch (err) {
      showError(coErr, err.message); toast(err.message, null, 'error'); submit.disabled = false; submit.textContent = label;
    }
  });

  /* ---------- wishlist page ---------- */
  function renderWishlist() {
    const g = $('#wishGrid'); if (!g) return;
    $$('[data-wish-item]', g).forEach(el => el.hidden = !store.wish.includes(el.dataset.wishItem));
    $('#wishEmpty').hidden = store.wish.length > 0;
    $$('.reveal', g).forEach(r => r.classList.add('in'));
  }

  /* ---------- forms ---------- */
  const valid = f => { let ok = true; $$('[required]', f).forEach(i => { const bad = i.type === 'checkbox' ? !i.checked : (!i.value.trim() || (i.type === 'email' && !/^\S+@\S+\.\S+$/.test(i.value))); i.classList.toggle('invalid', bad); if (bad) ok = false; }); return ok; };
  $$('form[data-validate]').forEach(f => f.addEventListener('submit', e => { if (!valid(f)) { e.preventDefault(); toast('Merci de compléter les champs en rouge.', null, 'error'); } }));
  $$('[data-toggle-pass]').forEach(b => b.addEventListener('click', () => { const i = b.parentElement.querySelector('input'); i.type = i.type === 'password' ? 'text' : 'password'; b.classList.toggle('on', i.type === 'text'); }));
  $$('[data-confirm-logout]').forEach(f => f.addEventListener('submit', async e => { if (f.dataset.ok) return; e.preventDefault(); if (await MKUI.confirm({ title: 'Se déconnecter ?', text: 'Vous pourrez vous reconnecter à tout moment.', confirmText: 'Me déconnecter' })) { f.dataset.ok = '1'; f.submit(); } }));
  $('#newsForm')?.addEventListener('submit', async e => {
    e.preventDefault(); const f = e.target;
    if (!valid(f)) { f.classList.remove('err'); void f.offsetWidth; f.classList.add('err'); return; }
    try { const data = await postJson('newsletter', formData(f)); f.reset(); toast(data.message); } catch (err) { toast(err.message, null, 'error'); }
  });
  $('#contactForm')?.addEventListener('submit', async e => {
    e.preventDefault(); const f = e.target, err = $('[data-form-error]', f), btn = $('button', f);
    if (err) err.hidden = true;
    if (!valid(f)) return;
    btn.disabled = true;
    try { const data = await postJson('contact', formData(f)); f.reset(); toast(data.message); } catch (x) { showError(err, x.message); }
    btn.disabled = false;
  });
  $$('.tabs button').forEach(b => b.addEventListener('click', () => {
    $$('.tabs button').forEach(x => x.classList.toggle('on', x === b));
    $$('[data-pane]').forEach(p => { p.hidden = p.dataset.pane !== b.dataset.tab; });
  }));
  $('#trackForm')?.addEventListener('submit', async e => {
    e.preventDefault(); const f = e.target, err = $('[data-form-error]', f), box = $('#trackResult');
    if (err) err.hidden = true;
    box.hidden = true;
    if (!valid(f)) return;
    try {
      const o = await postJson('track', formData(f));
      const head = $('.track-head', box);
      head.textContent = `Commande ${o.number} du ${o.placed_at} · ${o.total} · ${o.status}`;
      const s = $('#steps', box);
      const icons = ['<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', '<path d="m5 12.5 4.5 4.5L19 7.5"/>', '<path d="m12 3 8 4v10l-8 4-8-4V7z"/><path d="m4 7 8 4 8-4M12 11v10"/>', '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7.5" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/>', '<path d="M12 21s-6.500-5.500-6.500-11a6.500 6.500 0 0 1 13 0c0 5.500-6.500 11-6.500 11z"/><circle cx="12" cy="10" r="2.300"/>'];
      const steps = o.cancelled ? [{ label: 'Commande annulée', state: 'cur' }] : o.steps;
      s.style.setProperty('--n', steps.length);
      s.replaceChildren(...steps.map((st, i) => {
        const li = document.createElement('li'), b = document.createElement('b');
        if (st.state !== 'todo') li.className = st.state;
        li.innerHTML = `<svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${o.cancelled ? '<path d="M6 6l12 12M18 6 6 18"/>' : icons[i]}</svg>`;
        b.textContent = st.label; li.appendChild(b);
        return li;
      }));
      $$('li', s).forEach((li, i) => { li.style.animation = `up .5s ${i * .18}s var(--ease) both`; });
      box.hidden = false;
    } catch (x) { showError(err, x.message); }
  });

  /* ---------- page transitions ---------- */
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href]'); if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || a.target || a.hasAttribute('data-scroll')) return;
    const u = new URL(a.href, location.href);
    if (u.origin !== location.origin || (u.pathname === location.pathname && u.search === location.search) || u.hash && u.pathname === location.pathname) return;
    e.preventDefault(); $('#main').classList.add('leaving'); setTimeout(() => location.href = u.href, 220);
  });
  addEventListener('pageshow', e => { if (e.persisted) $('#main').classList.remove('leaving'); });

  window.MK = { store, toast, money, refreshPreview };
  render(); renderCart(); renderWishlist();
})();
