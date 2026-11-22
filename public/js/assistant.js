/* Assistant IA : fenêtre de discussion avec les visiteurs (configurée depuis l'administration). */
(() => {
  'use strict';
  const cfg = window.MK_SHOP && window.MK_SHOP.assistant;
  if (!cfg) return;

  const B = window.MK_BASE || '';
  const KEY = 'mk_ai';
  const $ = (s, c = document) => c.querySelector(s);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const load = () => { try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; } };
  const save = () => { try { localStorage.setItem(KEY, JSON.stringify({ token: state.token, messages: state.messages.slice(-40) })); } catch (e) {} };
  const state = Object.assign({ token: null, messages: [] }, load());

  /* ---------- texte du modèle → HTML sûr : tout est échappé, puis gras, listes et liens internes ---------- */
  function format(text) {
    let html = esc(text)
      .replace(/\*\*(.+?)\*\*/g, '<b>$1</b>')
      .replace(/(^|\n)\s*[-•]\s+/g, '$1• ')
      .replace(/(^|[\s(])(\/(?:product|page|shop|cart|account|track-order|contact|brands|best-deals|new-arrivals|blogs?)[A-Za-z0-9\-_/?=&%.;]*)/g, (m, pre, path) => `${pre}<a href="${B}${path.replace(/[.,;:!?]+$/, '')}">${path.replace(/[.,;:!?]+$/, '')}</a>${/[.,;:!?]+$/.test(path) ? path.match(/[.,;:!?]+$/)[0] : ''}`);
    return html.replace(/\n/g, '<br>');
  }

  const avatar = cfg.avatar
    ? `<img src="${esc(cfg.avatar)}" alt="">`
    : '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.8 4.6L18 9l-4.2 1.4L12 15l-1.8-4.6L6 9l4.2-1.4z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/></svg>';

  /* ---------- structure ---------- */
  const root = document.createElement('div');
  root.className = 'ai-root';
  root.dataset.side = cfg.position;
  root.innerHTML = `
    <button type="button" class="ai-launch" aria-haspopup="dialog" aria-expanded="false">
      <span class="ai-av">${avatar}</span>${cfg.label ? `<span class="ai-launch-t">${esc(cfg.label)}</span>` : ''}
    </button>
    <section class="ai-panel" role="dialog" aria-label="${esc(cfg.name)}" hidden>
      <header class="ai-head">
        <span class="ai-av">${avatar}</span>
        <span class="ai-who"><b>${esc(cfg.name)}</b><small>${esc(cfg.title)}</small></span>
        <button type="button" class="ai-ic" data-ai-reset title="Nouvelle conversation" aria-label="Nouvelle conversation"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg></button>
        <button type="button" class="ai-ic" data-ai-close aria-label="Fermer"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button>
      </header>
      <div class="ai-body" aria-live="polite"></div>
      <div class="ai-chips"></div>
      <form class="ai-form" autocomplete="off">
        <textarea rows="1" maxlength="1000" placeholder="Écrivez votre message…" aria-label="Votre message"></textarea>
        <button class="ai-send" aria-label="Envoyer"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l14-7-5 14-2-6z"/></svg></button>
      </form>
      ${cfg.handoff ? `<a class="ai-human" href="${esc(cfg.handoff.url)}" target="_blank" rel="noopener">${esc(cfg.handoff.label)}</a>` : ''}
    </section>`;
  document.body.appendChild(root);

  const launch = $('.ai-launch', root), panel = $('.ai-panel', root), body = $('.ai-body', root), chips = $('.ai-chips', root);
  const form = $('.ai-form', root), input = $('textarea', form), sendBtn = $('.ai-send', form);
  let busy = false;

  const scroll = () => { body.scrollTop = body.scrollHeight; };

  function cardHtml(p) {
    const action = p.has_variants
      ? `<a class="ai-card-btn" href="${esc(p.url)}">Choisir</a>`
      : `<button type="button" class="ai-card-btn" data-add="${esc(p.slug)}" ${p.in_stock ? '' : 'disabled'}>${p.in_stock ? 'Ajouter' : 'Rupture'}</button>`;
    return `<div class="ai-card">${p.image ? `<img src="${esc(p.image)}" alt="" loading="lazy">` : '<span class="ai-card-ph"></span>'}
      <div><a href="${esc(p.url)}"><b>${esc(p.name)}</b></a><small>${esc(p.variant)}</small><span class="ai-price">${esc(p.price)}${p.old_price ? ` <s>${esc(p.old_price)}</s>` : ''}</span></div>${action}</div>`;
  }

  function bubble(role, text, products = []) {
    const el = document.createElement('div');
    el.className = `ai-msg ${role === 'u' ? 'is-user' : 'is-bot'}`;
    el.innerHTML = `<div class="ai-bub">${role === 'u' ? esc(text).replace(/\n/g, '<br>') : format(text)}</div>${products.length ? `<div class="ai-cards">${products.map(cardHtml).join('')}</div>` : ''}`;
    body.appendChild(el);
    scroll();
    return el;
  }

  function render() {
    body.innerHTML = '';
    bubble('a', cfg.welcome);
    state.messages.forEach(m => bubble(m.r, m.t, m.p || []));
    chips.innerHTML = '';
    if (!state.messages.length) {
      (cfg.suggestions || []).forEach(text => {
        const b = document.createElement('button'); b.type = 'button'; b.className = 'ai-chip'; b.textContent = text; chips.appendChild(b);
      });
    }
    chips.hidden = !chips.children.length;
  }

  function setOpen(open) {
    panel.hidden = !open;
    root.classList.toggle('is-open', open);
    launch.setAttribute('aria-expanded', String(open));
    document.documentElement.classList.toggle('ai-lock', open && matchMedia('(max-width: 640px)').matches);
    if (open) { render(); setTimeout(() => input.focus(), 60); }
  }

  async function send(text) {
    text = text.trim();
    if (!text || busy) return;
    busy = true; sendBtn.disabled = true; chips.hidden = true;
    state.messages.push({ r: 'u', t: text }); save();
    bubble('u', text);
    const typing = document.createElement('div');
    typing.className = 'ai-msg is-bot'; typing.innerHTML = '<div class="ai-bub ai-typing"><i></i><i></i><i></i></div>';
    body.appendChild(typing); scroll();

    let reply = '', products = [];
    try {
      const res = await fetch(cfg.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': window.MK_SHOP.token },
        body: JSON.stringify({ message: text, conversation: state.token }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.status === 429) reply = 'Vous écrivez très vite : patientez quelques secondes avant de poser votre prochaine question.';
      else if (res.status === 422) reply = (data.errors && Object.values(data.errors)[0][0]) || 'Votre message n’a pas pu être envoyé.';
      else if (!res.ok) reply = 'Je rencontre un souci technique. Réessayez dans un instant.';
      else { reply = data.reply; products = data.products || []; if (data.conversation) state.token = data.conversation; }
    } catch (e) {
      reply = 'Connexion impossible. Vérifiez votre réseau puis réessayez.';
    }

    typing.remove();
    state.messages.push({ r: 'a', t: reply, p: products }); save();
    bubble('a', reply, products);
    busy = false; sendBtn.disabled = false; input.focus();
  }

  /* ---------- événements ---------- */
  launch.addEventListener('click', () => setOpen(panel.hidden));
  $('[data-ai-close]', root).addEventListener('click', () => { setOpen(false); launch.focus(); });
  $('[data-ai-reset]', root).addEventListener('click', () => { state.token = null; state.messages = []; save(); render(); input.focus(); });
  chips.addEventListener('click', e => { const c = e.target.closest('.ai-chip'); if (c) send(c.textContent); });
  form.addEventListener('submit', e => { e.preventDefault(); const t = input.value; input.value = ''; input.style.height = ''; send(t); });
  input.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
  input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 120) + 'px'; });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !panel.hidden) { setOpen(false); launch.focus(); } });
  window.MKAssistant = { open: () => setOpen(true), ask: t => { setOpen(true); send(t); } };
})();
