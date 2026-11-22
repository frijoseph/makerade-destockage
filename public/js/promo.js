/* Promotions et surprises : barre d'annonces, popups, roue de la chance, récemment consultés */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const SHOP = window.MK_SHOP || {};
  const UI = () => window.MKUI;
  const esc = s => UI().esc(s);
  const page = document.body.dataset.page;
  const B = window.MK_BASE || '';
  const isMobile = () => matchMedia('(max-width: 820px)').matches;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const ls = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };
  const ss = { get: k => { try { return sessionStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { sessionStorage.setItem(k, v); } catch (e) {} } };
  const post = async (url, payload) => {
    let res;
    try { res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': SHOP.token }, body: JSON.stringify(payload) }); }
    catch (e) { throw new Error('Connexion impossible. Vérifiez votre réseau.'); }
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Une erreur est survenue.'));
    return data;
  };
  const track = (p, type) => { if (SHOP.endpoints?.popupEvent) post(`${SHOP.endpoints.popupEvent}/${p.id}/event`, { type }).catch(() => {}); };
  const useCode = code => { try { localStorage.setItem('mk_coupon', JSON.stringify(code)); } catch (e) {} };

  /* ---------- barre d'annonces : rotation + fermeture ---------- */
  const ann = $('#annbar');
  if (ann) {
    const sig = [...$$('.ann-item', ann)].map(i => i.textContent.trim()).join('|');
    if (ss.get('mk_ann_closed') === sig) ann.remove();
    else {
      const items = $$('.ann-item', ann); let cur = 0;
      if (items.length > 1 && !reduce) setInterval(() => { items[cur].classList.remove('is-active'); cur = (cur + 1) % items.length; items[cur].classList.add('is-active'); }, 4500);
      $('[data-ann-close]', ann).addEventListener('click', () => { ss.set('mk_ann_closed', sig); ann.classList.add('out'); setTimeout(() => ann.remove(), 300); });
    }
  }

  /* ---------- copier un code ---------- */
  document.addEventListener('click', async e => {
    const el = e.target.closest('[data-copy]'); if (!el) return;
    if (await UI().copy(el.dataset.copy)) { el.classList.add('copied'); setTimeout(() => el.classList.remove('copied'), 1800); }
  });

  /* ---------- popups ---------- */
  const popups = (SHOP.popups || []).filter(p => !(p.devices === 'mobile' && !isMobile()) && !(p.devices === 'desktop' && isMobile()));
  const allowed = p => {
    if (p.kind === 'wheel' && ls.get(`mk_spun_${p.id}`)) return false;
    const key = `mk_pop_${p.id}`;
    switch (p.frequency) {
      case 'once': return !ls.get(key);
      case 'session': return !ss.get(key);
      case 'daily': return Date.now() - (+ls.get(key) || 0) > 864e5;
      default: return true;
    }
  };
  const mark = p => { const key = `mk_pop_${p.id}`; ls.set(key, String(Date.now())); ss.set(key, '1'); };
  const busy = () => typeof Swal !== 'undefined' && Swal.isVisible();

  const head = p => `${p.image ? `<div class="pp-img" style="background-image:url('${esc(p.image)}')"></div>` : `<div class="pp-ic">${p.kind === 'newsletter' ? '💌' : p.kind === 'wheel' ? '🎡' : '🎁'}</div>`}<h3 class="pp-title">${esc(p.title)}</h3>${p.body ? `<p class="pp-body">${esc(p.body)}</p>` : ''}`;
  const accent = p => el => el.style.setProperty('--ac', p.accent);

  /* champ téléphone (indicatif recherchable + numéro) pour les popups */
  const phoneHtml = () => {
    const ph = SHOP.phone || { default: 'CI', countries: [] };
    const opts = ph.countries.map(c => `<option value="${esc(c.iso)}" data-short="${esc(c.flag)} +${esc(c.dial)}" ${c.iso === ph.default ? 'selected' : ''}>${esc(c.flag)} ${esc(c.name)} (+${esc(c.dial)})</option>`).join('');
    return `<div class="phone-field" data-phone><select name="phone_country" aria-label="Indicatif du pays" data-search-from="1">${opts}</select><input type="tel" name="phone" inputmode="tel" autocomplete="tel-national" placeholder="07 00 00 00 00" aria-label="Numéro de téléphone"></div>`;
  };
  const readPhone = () => {
    const pop = Swal.getPopup(), phone = $('[name=phone]', pop).value.trim();
    if (!phone) { Swal.showValidationMessage('Saisissez votre numéro de téléphone.'); return null; }
    return { phone, phone_country: $('[name=phone_country]', pop).value };
  };

  function showPromo(p) {
    Swal.fire({
      buttonsStyling: false, reverseButtons: true, showCloseButton: true, showCancelButton: true,
      customClass: { popup: 'mk-swal mk-promo', confirmButton: 'mk-btn mk-btn-primary mk-cta', cancelButton: 'mk-btn mk-btn-ghost', actions: 'mk-swal-actions', closeButton: 'mk-swal-close' },
      html: `${head(p)}${p.code ? `<div class="mk-code" data-copy="${esc(p.code)}"><b>${esc(p.code)}</b></div>` : ''}`,
      confirmButtonText: p.button_label || (p.code ? 'Utiliser ce code' : 'J’en profite'), cancelButtonText: 'Plus tard',
      didOpen: el => accent(p)(el),
    }).then(r => {
      if (!r.isConfirmed) return;
      track(p, 'click');
      if (p.code) { useCode(p.code); UI().toast(`Code ${p.code} ajouté. Il s’applique dans votre panier.`); }
      if (p.button_url) location.href = p.button_url; else if (p.code) location.href = `${B}/cart`;
    });
  }

  function showNewsletter(p) {
    Swal.fire({
      buttonsStyling: false, reverseButtons: true, showCloseButton: true, showCancelButton: true, showLoaderOnConfirm: true,
      customClass: { popup: 'mk-swal mk-promo', confirmButton: 'mk-btn mk-btn-primary mk-cta', cancelButton: 'mk-btn mk-btn-ghost', actions: 'mk-swal-actions', closeButton: 'mk-swal-close' },
      html: head(p) + phoneHtml(),
      confirmButtonText: p.button_label || 'Je m’abonne', cancelButtonText: 'Non merci',
      didOpen: el => { accent(p)(el); window.MKSelects?.enhance(el); },
      preConfirm: async () => {
        const who = readPhone(); if (!who) return false;
        try { return await post(SHOP.endpoints.newsletter, { ...who, popup_id: p.id }); } catch (e) { Swal.showValidationMessage(e.message); return false; }
      },
    }).then(r => {
      if (!r.isConfirmed) return;
      const data = r.value || {};
      if (data.code) {
        useCode(data.code);
        Swal.fire({ buttonsStyling: false, icon: 'success', title: 'Merci ! Voici votre cadeau', html: `<div class="mk-code" data-copy="${esc(data.code)}"><b>${esc(data.code)}</b></div><p class="mk-code-note">Votre code est déjà enregistré : il s’applique dans le panier.</p>`, confirmButtonText: 'Voir la boutique', customClass: { popup: 'mk-swal mk-promo', confirmButton: 'mk-btn mk-btn-primary' }, didOpen: el => accent(p)(el) }).then(x => { if (x.isConfirmed) location.href = `${B}/shop`; });
        if (SHOP.confetti) UI().confetti();
      } else UI().toast(data.message || 'Merci pour votre inscription !');
    });
  }

  /* ---------- roue de la chance ---------- */
  function drawWheel(cv, segs) {
    const size = 300, dpr = Math.min(devicePixelRatio || 1, 2); cv.width = cv.height = size * dpr; cv.style.width = cv.style.height = '100%';
    const c = cv.getContext('2d'); c.scale(dpr, dpr);
    const r = size / 2, step = (Math.PI * 2) / segs.length;
    segs.forEach((s, i) => {
      c.beginPath(); c.moveTo(r, r); c.arc(r, r, r - 4, -Math.PI / 2 + i * step, -Math.PI / 2 + (i + 1) * step); c.closePath();
      c.fillStyle = s.color || '#237a32'; c.fill(); c.strokeStyle = 'rgba(255,255,255,.85)'; c.lineWidth = 2; c.stroke();
      c.save(); c.translate(r, r); c.rotate(-Math.PI / 2 + (i + .5) * step); c.textAlign = 'right'; c.fillStyle = '#fff'; c.shadowColor = 'rgba(0,0,0,.35)'; c.shadowBlur = 3;
      const maxW = r - 22 - 38; let px = 16, label = s.label;
      c.font = `700 ${px}px Inter, system-ui, sans-serif`;
      while (c.measureText(label).width > maxW && px > 10) { px--; c.font = `700 ${px}px Inter, system-ui, sans-serif`; }
      while (c.measureText(label).width > maxW && label.length > 4) label = label.slice(0, -2) + '…';
      c.fillText(label, r - 22, px * .33); c.restore();
    });
    c.beginPath(); c.arc(r, r, 22, 0, Math.PI * 2); c.fillStyle = '#fff'; c.fill(); c.strokeStyle = 'rgba(0,0,0,.12)'; c.lineWidth = 2; c.stroke();
  }

  function showWheel(p) {
    mark(p);
    Swal.fire({
      buttonsStyling: false, reverseButtons: true, showCloseButton: true, showCancelButton: true, showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
      customClass: { popup: 'mk-swal mk-promo mk-wheelpop', confirmButton: 'mk-btn mk-btn-primary mk-cta', cancelButton: 'mk-btn mk-btn-ghost', actions: 'mk-swal-actions', closeButton: 'mk-swal-close', input: 'mk-input' },
      html: `<h3 class="pp-title">${esc(p.title)}</h3>${p.body ? `<p class="pp-body">${esc(p.body)}</p>` : ''}<div class="wheel"><span class="wheel-pin" aria-hidden="true"></span><div class="wheel-disc" id="wheelDisc"><canvas id="wheelCv" aria-label="Roue de la chance"></canvas></div></div>${phoneHtml()}`,
      confirmButtonText: p.button_label || 'Tourner la roue !', cancelButtonText: 'Plus tard',
      didOpen: el => { accent(p)(el); drawWheel($('#wheelCv', el), p.segments); window.MKSelects?.enhance(el); },
      preConfirm: async () => {
        const who = readPhone(); if (!who) return false;
        let res;
        try { res = await post(SHOP.endpoints.spin, { popup_id: p.id, ...who }); } catch (e) { Swal.showValidationMessage(e.message); return false; }
        const seg = 360 / p.segments.length, turn = 360 * 5 - (res.index + .5) * seg + (Math.random() - .5) * seg * .6;
        const disc = $('#wheelDisc'); disc.style.transition = reduce ? 'transform .4s' : 'transform 4.6s cubic-bezier(.12,.67,.12,1)'; disc.style.transform = `rotate(${turn}deg)`;
        await new Promise(r => setTimeout(r, reduce ? 500 : 4800));
        return res;
      },
    }).then(r => {
      if (!r.isConfirmed) return;
      ls.set(`mk_spun_${p.id}`, '1'); $('.spin-fab')?.remove();
      const res = r.value;
      if (res.won) {
        useCode(res.code); if (SHOP.confetti) UI().confetti({ count: 200, duration: 3400 });
        Swal.fire({ buttonsStyling: false, icon: 'success', title: `Bravo ! ${res.label}`, html: `<div class="mk-code" data-copy="${esc(res.code)}"><b>${esc(res.code)}</b></div><p class="mk-code-note">Votre code est enregistré : il s’applique automatiquement dans votre panier.</p>`, confirmButtonText: 'J’en profite', customClass: { popup: 'mk-swal mk-promo', confirmButton: 'mk-btn mk-btn-primary' }, didOpen: el => accent(p)(el) }).then(x => { if (x.isConfirmed) location.href = `${B}/shop`; });
      } else {
        Swal.fire({ buttonsStyling: false, icon: 'info', title: res.label || 'Pas cette fois…', text: 'Merci d’avoir joué ! Gardez un œil sur nos offres.', confirmButtonText: 'Continuer', customClass: { popup: 'mk-swal mk-promo', confirmButton: 'mk-btn mk-btn-primary' } });
      }
    });
  }

  const render = p => (p.kind === 'wheel' ? showWheel : p.kind === 'newsletter' ? showNewsletter : showPromo)(p);
  let shown = false;
  const fire = p => {
    if (shown || busy() || !allowed(p)) return;
    shown = true; mark(p); track(p, 'impression'); render(p);
  };

  const first = popups.find(allowed);
  if (first && typeof Swal !== 'undefined') {
    const v = first.trigger_value;
    if (first.trigger === 'scroll') {
      const onScroll = () => { const max = document.documentElement.scrollHeight - innerHeight; if (max > 0 && scrollY / max * 100 >= v) { removeEventListener('scroll', onScroll); fire(first); } };
      addEventListener('scroll', onScroll, { passive: true });
    } else if (first.trigger === 'exit' && !isMobile()) {
      setTimeout(() => document.addEventListener('mouseout', function onOut(e) { if (!e.relatedTarget && e.clientY <= 0) { document.removeEventListener('mouseout', onOut); fire(first); } }), 4000);
    } else {
      setTimeout(() => fire(first), (first.trigger === 'exit' ? 25 : v) * 1000);
    }
  }

  /* bouton flottant « Tentez votre chance » tant que la roue n'a pas été jouée */
  const wheel = popups.find(p => p.kind === 'wheel' && !ls.get(`mk_spun_${p.id}`));
  if (wheel && typeof Swal !== 'undefined') {
    const fab = document.createElement('button'); fab.type = 'button'; fab.className = 'spin-fab'; fab.setAttribute('aria-label', 'Jouer à la roue de la chance');
    fab.innerHTML = '<span>🎡</span><b>Tentez votre chance</b>';
    fab.addEventListener('click', () => { if (!busy()) render(wheel); });
    document.body.appendChild(fab);
  }

  /* ---------- récemment consultés ---------- */
  if (SHOP.recentlyViewed) {
    let CAT = {}; try { CAT = JSON.parse($('#catalog').textContent); } catch (e) {}
    const key = 'mk_recent', read = () => { try { return JSON.parse(localStorage.getItem(key)) || []; } catch (e) { return []; } };
    const current = $('.pdetail')?.dataset.id;
    if (current && CAT[current]) { try { localStorage.setItem(key, JSON.stringify([current, ...read().filter(s => s !== current)].slice(0, 12))); } catch (e) {} }
    const box = $('#recentGrid'), sec = $('#recentSec');
    if (box && sec) {
      const slugs = read().filter(s => s !== current && CAT[s]).slice(0, 6);
      if (slugs.length) {
        box.innerHTML = slugs.map(s => { const p = CAT[s]; return `<a class="rc" href="${B}/product/${esc(p.slug)}/">${p.image ? `<img src="${esc(p.image)}" alt="" loading="lazy">` : '<span class="rc-ph"></span>'}<b>${esc(p.name)}</b><small>${esc(window.mkMoney(p.price))}</small></a>`; }).join('');
        sec.hidden = false;
      }
    }
  }

  /* ---------- barre d'achat fixe (fiche produit, mobile) ---------- */
  const sticky = $('#stickyBuy'), anchor = $('.pd-info .buy');
  if (sticky && anchor && 'IntersectionObserver' in window) {
    new IntersectionObserver(([en]) => sticky.classList.toggle('show', !en.isIntersecting && en.boundingClientRect.top < 0), { threshold: 0 }).observe(anchor);
  }

  /* ---------- page de confirmation : confettis ---------- */
  if ($('[data-celebrate]') && SHOP.confetti) setTimeout(() => UI().confetti({ count: 180, duration: 3200 }), 350);
})();
