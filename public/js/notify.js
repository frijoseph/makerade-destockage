/* Notifications, confirmations et modales (SweetAlert2) — partagé par la boutique et l'administration */
(() => {
  'use strict';
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || window.MK_SHOP?.token || '';
  const hasSwal = () => typeof Swal !== 'undefined';
  const base = { buttonsStyling: false, reverseButtons: true, customClass: { popup: 'mk-swal', title: 'mk-swal-title', confirmButton: 'mk-btn mk-btn-primary', cancelButton: 'mk-btn mk-btn-ghost', denyButton: 'mk-btn mk-btn-danger', actions: 'mk-swal-actions', closeButton: 'mk-swal-close' } };
  const cls = (...extra) => ({ ...base.customClass, popup: ['mk-swal', ...extra].join(' ') });

  /* ---------- toast ---------- */
  function toast(message, type = 'success', opts = {}) {
    if (!hasSwal()) { console.log(`[${type}]`, message); return; }
    Swal.fire({
      toast: true, position: opts.position || 'top-end', icon: type, html: opts.html ? message : esc(message),
      showConfirmButton: false, showCloseButton: true, timer: opts.timer || (type === 'error' ? 6000 : 3800), timerProgressBar: true,
      buttonsStyling: false, customClass: cls('mk-toast'),
      didOpen: el => { el.addEventListener('mouseenter', Swal.stopTimer); el.addEventListener('mouseleave', Swal.resumeTimer); },
    });
  }

  /* ---------- confirmation ---------- */
  async function confirm({ title = 'Confirmer ?', text = '', html = '', confirmText = 'Confirmer', cancelText = 'Annuler', danger = false, icon = 'question' } = {}) {
    if (!hasSwal()) return window.confirm(text || title);
    const r = await Swal.fire({ ...base, icon: danger ? 'warning' : icon, title, text: html ? undefined : text, html: html || undefined, showCancelButton: true, confirmButtonText: confirmText, cancelButtonText: cancelText, focusCancel: danger, customClass: { ...cls(), confirmButton: danger ? 'mk-btn mk-btn-danger' : 'mk-btn mk-btn-primary' } });
    return r.isConfirmed;
  }

  /* ---------- erreurs de validation ---------- */
  function errors(list, title = 'Merci de corriger les points suivants') {
    const items = Array.isArray(list) ? list : [list];
    if (!hasSwal()) { alert(items.join('\n')); return; }
    if (items.length === 1) return Swal.fire({ ...base, icon: 'error', title: 'Oups…', text: items[0], confirmButtonText: 'Compris', customClass: cls() });
    return Swal.fire({ ...base, icon: 'error', title, html: `<ul class="mk-errors">${items.map(i => `<li>${esc(i)}</li>`).join('')}</ul>`, confirmButtonText: 'Compris', customClass: cls() });
  }

  /* ---------- toast après rechargement ---------- */
  const flashNext = (message, type = 'success') => { try { sessionStorage.setItem('mk_flash', JSON.stringify({ message, type })); } catch (e) {} };

  /* ---------- modale de formulaire (ajout / modification via JSON) ---------- */
  const fieldHtml = (f, v) => {
    const val = v?.[f.name] ?? f.default ?? '';
    const id = `mkf_${f.name}`;
    const help = f.help ? `<small>${esc(f.help)}</small>` : '';
    const req = f.required ? ' required' : '';
    if (f.type === 'checkbox') return `<label class="mk-check ${f.wide ? 'mk-span' : ''}"><input type="checkbox" name="${f.name}" ${val ? 'checked' : ''}><span class="mk-switch"></span><span>${esc(f.label)}${help ? `<br>${help}` : ''}</span></label>`;
    let control;
    if (f.type === 'select') control = `<select id="${id}" name="${f.name}">${Object.entries(f.options || {}).map(([k, l]) => `<option value="${esc(k)}" ${String(val) === String(k) ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>`;
    else if (f.type === 'textarea') control = `<textarea id="${id}" name="${f.name}" rows="3"${req}>${esc(val)}</textarea>`;
    else {
      const type = { datetime: 'datetime-local', number: 'number' }[f.type] || f.type || 'text';
      const v2 = f.type === 'datetime' && val ? String(val).replace(' ', 'T').slice(0, 16) : val;
      control = `<input id="${id}" name="${f.name}" type="${type}" value="${esc(v2)}"${req}${f.step ? ` step="${f.step}"` : ''}${f.min !== undefined ? ` min="${f.min}"` : ''}${f.placeholder ? ` placeholder="${esc(f.placeholder)}"` : ''}${f.list ? ` list="${id}_l"` : ''}>${f.list ? `<datalist id="${id}_l">${f.list.map(o => `<option value="${esc(o)}">`).join('')}</datalist>` : ''}`;
    }
    return `<label class="mk-field ${f.wide ? 'mk-span' : ''}"><span>${esc(f.label)}${f.required ? ' <b>*</b>' : ''}</span>${control}${help}</label>`;
  };

  async function formModal({ title, fields, values = {}, submitText = 'Enregistrer', url, method = 'POST', width = 620, icon }) {
    if (!hasSwal()) return null;
    const r = await Swal.fire({
      ...base, title, width, showCancelButton: true, confirmButtonText: submitText, cancelButtonText: 'Annuler', focusConfirm: false, showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
      customClass: cls('mk-swal-form'),
      html: `<form class="mk-form" novalidate>${(fields || []).map(f => fieldHtml(f, values)).join('')}</form>`,
      didOpen: el => {
        window.MKSelects?.enhance(el);
        el.querySelector('input:not([type=checkbox]), select, textarea')?.focus();
        el.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); Swal.clickConfirm(); } });
      },
      preConfirm: async () => {
        const form = Swal.getPopup().querySelector('form'), body = {};
        form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
        for (const f of fields) {
          const el = form.elements[f.name];
          body[f.name] = f.type === 'checkbox' ? el.checked : (el.value === '' ? null : el.value);
        }
        let res;
        try {
          res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf() }, body: JSON.stringify(body) });
        } catch (e) { Swal.showValidationMessage('Connexion impossible. Vérifiez votre réseau.'); return false; }
        const data = await res.json().catch(() => ({}));
        if (res.status === 422 && data.errors) {
          Object.keys(data.errors).forEach(k => form.elements[k]?.classList.add('is-invalid'));
          Swal.showValidationMessage(Object.values(data.errors)[0][0]);
          return false;
        }
        if (!res.ok) { Swal.showValidationMessage(data.message || 'Une erreur est survenue.'); return false; }
        return data;
      },
    });
    return r.isConfirmed ? r.value : null;
  }

  async function sendDelete(url) {
    const res = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Suppression impossible.');
    return data;
  }

  /* ---------- confettis ---------- */
  function confetti({ count = 140, duration = 2800 } = {}) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const cv = document.createElement('canvas'); cv.className = 'mk-confetti';
    cv.width = innerWidth; cv.height = innerHeight; document.body.appendChild(cv);
    const ctx = cv.getContext('2d'), colors = [(window.MK_SHOP && window.MK_SHOP.primary) || '#237a32','#f2a516', '#e0364b', '#2e8bd6', '#9b59d0', '#ffffff'];
    const parts = Array.from({ length: count }, () => ({ x: innerWidth / 2 + (Math.random() - .5) * 200, y: innerHeight * .55, vx: (Math.random() - .5) * 14, vy: -Math.random() * 15 - 5, s: Math.random() * 7 + 4, r: Math.random() * 6, vr: (Math.random() - .5) * .4, c: colors[(Math.random() * colors.length) | 0] }));
    const t0 = performance.now();
    (function tick(t) {
      const p = (t - t0) / duration; ctx.clearRect(0, 0, cv.width, cv.height);
      parts.forEach(q => { q.vy += .35; q.x += q.vx; q.y += q.vy; q.r += q.vr; ctx.save(); ctx.globalAlpha = Math.max(0, 1 - p); ctx.translate(q.x, q.y); ctx.rotate(q.r); ctx.fillStyle = q.c; ctx.fillRect(-q.s / 2, -q.s / 3, q.s, q.s * .6); ctx.restore(); });
      p < 1 ? requestAnimationFrame(tick) : cv.remove();
    })(t0);
  }

  const copy = async text => { try { await navigator.clipboard.writeText(text); return true; } catch (e) { return false; } };

  /* ---------- modale de consultation (contenu HTML déjà échappé côté serveur) ---------- */
  function view({ title = '', html = '', width = 720, confirmText = 'Fermer' } = {}) {
    if (!hasSwal()) return Promise.resolve(null);
    return Swal.fire({ ...base, title, html, width, showConfirmButton: true, confirmButtonText: confirmText, showCloseButton: true, customClass: cls('mk-swal-form', 'mk-swal-view') });
  }

  window.MKUI = { esc, toast, confirm, errors, view, formModal, sendDelete, confetti, copy, flashNext, csrf };

  /* ---------- messages de session (flash) + toast différé ---------- */
  const run = () => {
    try { const f = JSON.parse(sessionStorage.getItem('mk_flash') || 'null'); if (f) { sessionStorage.removeItem('mk_flash'); toast(f.message, f.type); } } catch (e) {}
    const f = window.MK_FLASH || {};
    if (f.modal && hasSwal()) {
      Swal.fire({ ...base, icon: f.modal.icon || 'success', title: f.modal.title, html: f.modal.html, confirmButtonText: f.modal.button || 'Super !', customClass: cls() });
      if (f.modal.confetti && window.MK_SHOP?.confetti !== false) confetti();
    } else if (f.status) toast(f.status, 'success');
    if (f.errors?.length) errors(f.errors);
    if (f.error) toast(f.error, 'error');
  };
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', run) : setTimeout(run, 0);
})();
