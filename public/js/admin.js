/* Makerade — back office interactions */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const root = document.documentElement;
  const UI = () => window.MKUI;
  const norm = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

  /* ---------- light / dark ---------- */
  const toggle = $('[data-theme-toggle]');
  const syncSwitch = () => toggle?.setAttribute('aria-checked', root.dataset.theme === 'dark' ? 'true' : 'false');
  toggle?.addEventListener('click', () => {
    root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try { localStorage.setItem('mk_admin_theme', root.dataset.theme); } catch (e) {}
    syncSwitch();
  });
  syncSwitch();

  /* ---------- mobile sidebar ---------- */
  $('[data-side-open]')?.addEventListener('click', () => document.body.classList.add('side-open'));
  $('[data-side-close]')?.addEventListener('click', () => document.body.classList.remove('side-open'));

  /* ---------- sidebar repliable (icônes toujours visibles) ---------- */
  const collapse = $('[data-side-collapse]');
  const syncCollapse = () => {
    const mini = root.classList.contains('side-mini');
    collapse?.setAttribute('aria-expanded', mini ? 'false' : 'true');
    collapse?.setAttribute('aria-label', mini ? 'Déplier le menu' : 'Replier le menu');
    collapse?.setAttribute('title', mini ? 'Déplier le menu' : 'Replier le menu');
  };
  collapse?.addEventListener('click', () => {
    root.classList.toggle('side-mini');
    try { localStorage.setItem('mk_side_collapsed', root.classList.contains('side-mini') ? '1' : '0'); } catch (e) {}
    syncCollapse();
  });
  syncCollapse();

  /* ---------- champs affichés selon un autre réglage (data-show-when="reglage=valeur") ---------- */
  $$('[data-show-when]').forEach(wrap => {
    const [key, expected] = wrap.dataset.showWhen.split('=');
    const source = $('[name="settings[' + key + ']"]');
    if (!source) return;
    const sync = () => { wrap.style.display = source.value === expected ? 'contents' : 'none'; };
    source.addEventListener('change', sync);
    sync();
  });

  /* ---------- notifications (menu déroulant) ---------- */
  const notif = $('[data-notif]');
  if (notif) {
    const btn = $('[data-notif-toggle]', notif), panel = $('.notif-panel', notif);
    const set = open => { panel.hidden = !open; btn.setAttribute('aria-expanded', open); };
    btn.addEventListener('click', e => { e.stopPropagation(); set(panel.hidden); });
    document.addEventListener('click', e => { if (!notif.contains(e.target)) set(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') set(false); });
  }

  /* ---------- palette de commandes (Ctrl+K) ---------- */
  const cmdk = $('#cmdk');
  if (cmdk) {
    const input = $('#cmdkInput'), list = $('#cmdkList');
    let quick = []; try { quick = JSON.parse($('#cmdkQuick').textContent); } catch (e) {}
    const items = [
      ...$$('[data-nav]').map(a => ({ label: a.dataset.nav, url: a.href, hint: 'Aller à' })),
      ...quick,
    ];
    let shown = [], active = 0, remote = [], seq = 0, timer = 0;
    const render = () => {
      const words = norm(input.value.trim()).split(/\s+/).filter(Boolean);
      shown = [...items.filter(i => words.every(w => norm(i.label).includes(w))).slice(0, 8), ...remote].slice(0, 16);
      active = 0;
      list.innerHTML = shown.length
        ? shown.map((i, n) => `<li role="option" data-n="${n}" class="${n === 0 ? 'is-on' : ''}"><span>${UI().esc(i.label)}</span><small>${UI().esc(i.hint)}</small></li>`).join('')
        : '<li class="cmdk-none">Aucun résultat</li>';
    };
    const open = () => { cmdk.hidden = false; input.value = ''; remote = []; render(); setTimeout(() => input.focus(), 10); };
    const close = () => { cmdk.hidden = true; };
    const go = n => { if (shown[n]) location.href = shown[n].url; };
    const mark = () => $$('li', list).forEach((li, n) => { li.classList.toggle('is-on', n === active); if (n === active) li.scrollIntoView({ block: 'nearest' }); });
    $$('[data-cmdk-open]').forEach(b => b.addEventListener('click', open));
    const search = () => {
      const q = input.value.trim(), url = cmdk.dataset.searchUrl, id = ++seq;
      if (q.length < 2 || !url) { remote = []; return render(); }
      fetch(`${url}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } })
        .then(r => r.ok ? r.json() : { results: [] })
        .then(d => { if (id === seq) { remote = d.results || []; render(); } })
        .catch(() => {});
    };
    input.addEventListener('input', () => { render(); clearTimeout(timer); timer = setTimeout(search, 180); });
    list.addEventListener('click', e => { const li = e.target.closest('[data-n]'); if (li) go(+li.dataset.n); });
    list.addEventListener('mousemove', e => { const li = e.target.closest('[data-n]'); if (li && +li.dataset.n !== active) { active = +li.dataset.n; mark(); } });
    cmdk.addEventListener('click', e => { if (e.target === cmdk) close(); });
    document.addEventListener('keydown', e => {
      const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName);
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); cmdk.hidden ? open() : close(); return; }
      if (e.key === '/' && !typing && cmdk.hidden && !(typeof Swal !== 'undefined' && Swal.isVisible())) { e.preventDefault(); open(); return; }
      if (cmdk.hidden) { if (e.key === 'Escape') document.body.classList.remove('side-open'); return; }
      if (e.key === 'Escape') close();
      else if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(shown.length - 1, active + 1); mark(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(0, active - 1); mark(); }
      else if (e.key === 'Enter') { e.preventDefault(); go(active); }
    });
  }

  /* ---------- auto-submit filters ---------- */
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.requestSubmit()));

  /* ---------- clickable rows ---------- */
  document.addEventListener('click', e => {
    const row = e.target.closest('tr[data-href]');
    if (!row || e.target.closest('a, button, input, select, form, [data-copy]')) return;
    location.href = row.dataset.href;
  });

  /* ---------- aperçu d'une commande dans une modale ---------- */
  document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-order-preview]');
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    try {
      const res = await fetch(btn.dataset.orderPreview, { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' } });
      if (!res.ok) throw new Error();
      UI().view({ title: btn.dataset.title || 'Commande', html: await res.text() });
    } catch (err) { UI().toast('Impossible de charger la commande.', 'error'); }
  });

  /* ---------- confirmations (SweetAlert2) + verrouillage des doubles envois ---------- */
  document.addEventListener('submit', async e => {
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      e.preventDefault();
      const custom = !!f.dataset.confirmButton;
      const ok = await UI().confirm({ title: f.dataset.confirmTitle || 'Supprimer ?', text: f.dataset.confirm, confirmText: f.dataset.confirmButton || 'Oui, supprimer', danger: !custom });
      if (ok) { f.dataset.confirmed = '1'; f.submit(); }
      return;
    }
    const btn = f.querySelector('button:not([type=button]):not([data-no-lock])');
    if (btn && !f.hasAttribute('data-no-lock')) setTimeout(() => { btn.disabled = true; btn.classList.add('is-loading'); }, 0);
  });
  addEventListener('pageshow', e => { if (e.persisted) $$('button.is-loading').forEach(b => { b.disabled = false; b.classList.remove('is-loading'); }); });

  /* ---------- tableaux avec modale d'ajout / modification ---------- */
  $$('[data-crud]').forEach(wrap => {
    let cfg; try { cfg = JSON.parse($('script[data-crud-config]', wrap).textContent); } catch (e) { return; }
    const save = async (record) => {
      const editing = !!record?.id;
      const data = await UI().formModal({
        title: editing ? cfg.editTitle.replace(':name', record[cfg.nameKey] ?? '') : cfg.addTitle,
        fields: cfg.fields, values: editing ? record : (cfg.defaults || {}), width: cfg.width || 620,
        submitText: editing ? 'Enregistrer' : 'Ajouter',
        url: editing ? cfg.updateUrl.replace('__id__', record.id) : cfg.storeUrl, method: editing ? 'PUT' : 'POST',
      });
      if (data) { UI().flashNext(data.message); location.reload(); }
    };
    const remove = async record => {
      const name = record[cfg.nameKey] ?? '';
      if (!await UI().confirm({ title: `Supprimer « ${name} » ?`, text: cfg.deleteText || 'Cette action est définitive.', confirmText: 'Oui, supprimer', danger: true })) return;
      try { const data = await UI().sendDelete(cfg.deleteUrl.replace('__id__', record.id)); UI().flashNext(data.message); location.reload(); }
      catch (err) { UI().toast(err.message, 'error'); }
    };
    wrap._crudSave = save;
    wrap.addEventListener('click', e => {
      if (e.target.closest('[data-crud-add]')) return save(null);
      const del = e.target.closest('[data-crud-delete]'); if (del) return remove(JSON.parse(del.dataset.record));
      const edit = e.target.closest('[data-crud-edit]'); if (edit) return save(JSON.parse(edit.dataset.record));
      const row = e.target.closest('tr[data-record]');
      if (row && !e.target.closest('a, button, [data-copy], input')) save(JSON.parse(row.dataset.record));
    });
  });

  // Bouton « Ajouter » placé dans l'en-tête de page, hors du tableau.
  $$('[data-crud-add]').filter(b => !b.closest('[data-crud]')).forEach(b => b.addEventListener('click', () => $('[data-crud]')?._crudSave(null)));

  /* ---------- copier un code ---------- */
  document.addEventListener('click', async e => {
    const el = e.target.closest('[data-copy]'); if (!el) return;
    e.preventDefault();
    if (await UI().copy(el.dataset.copy)) UI().toast(`« ${el.dataset.copy} » copié dans le presse-papiers.`);
  });

  /* ---------- line chart hover ---------- */
  $$('[data-line-chart]').forEach(chart => {
    const tip = $('[data-tip]', chart), svg = $('svg', chart), pts = $$('.pt', chart);
    if (!tip || !pts.length) return;
    const vb = svg.viewBox.baseVal;
    const show = pt => {
      pts.forEach(p => p.classList.toggle('is-hover', p === pt));
      tip.style.left = (pt.cx.baseVal.value / vb.width * 100) + '%';
      tip.style.top = (pt.cy.baseVal.value / vb.height * 100) + '%';
      tip.innerHTML = `<b>${pt.dataset.value}</b><small>${pt.dataset.label}</small>`;
    };
    svg.addEventListener('mousemove', e => {
      const r = svg.getBoundingClientRect(), x = (e.clientX - r.left) / r.width * vb.width;
      let best = pts[0];
      pts.forEach(p => { if (Math.abs(p.cx.baseVal.value - x) < Math.abs(best.cx.baseVal.value - x)) best = p; });
      show(best);
    });
    svg.addEventListener('mouseleave', () => show(pts[pts.length - 1]));
  });

  /* ---------- colour inputs mirrored in a text field ---------- */
  $$('[data-color]').forEach(wrap => {
    const picker = $('input[type=color]', wrap), text = $('input[type=text]', wrap);
    picker.addEventListener('input', () => { text.value = picker.value; text.dispatchEvent(new Event('input', { bubbles: true })); });
    text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
  });

  /* ---------- image preview (delegated: also works in repeater rows added later) ---------- */
  document.addEventListener('change', e => {
    const input = e.target.closest('[data-image-input]');
    if (!input) return;
    const file = input.files[0], box = $(input.dataset.imageInput);
    if (!file || !box) return;
    box.innerHTML = '';
    const img = document.createElement('img');
    img.alt = '';
    img.src = URL.createObjectURL(file);
    box.appendChild(img);
  });

  /* ---------- répéteurs (réglages, secteurs de la roue) ---------- */
  $$('[data-repeater]').forEach(rep => {
    const rows = $('[data-rows]', rep), tpl = $('[data-row-template]', rep);
    const renumber = () => $$(':scope > [data-row]', rows).forEach((row, i) => {
      $$('[name], [id], [for], [data-image-input]', row).forEach(el => {
        ['name', 'id', 'for'].forEach(attr => { if (el.hasAttribute(attr)) el.setAttribute(attr, el.getAttribute(attr).replace(/(\[|_)(\d+|__i__)(\]|_)/, `$1${i}$3`)); });
        if (el.dataset.imageInput) el.dataset.imageInput = el.dataset.imageInput.replace(/(_)(\d+|__i__)(_)/, `$1${i}$3`);
      });
    });
    $('[data-add-row]', rep).addEventListener('click', () => {
      rows.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', rows.children.length));
      renumber();
      window.MKSelects?.enhance(rows.lastElementChild);
      $('input, textarea, select', rows.lastElementChild)?.focus();
    });
    rows.addEventListener('click', e => {
      const row = e.target.closest('[data-row]');
      if (!row || row.parentElement !== rows) return;
      if (e.target.closest('[data-row-remove]')) row.remove();
      else if (e.target.closest('[data-row-up]') && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
      else if (e.target.closest('[data-row-down]') && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
      else return;
      renumber();
    });
  });

  /* ---------- formulaire popup : sections selon le type + aperçu ---------- */
  const popupForm = $('[data-popup-form]');
  if (popupForm) {
    const kind = $('[name=kind]', popupForm), pv = $('[data-pv]', popupForm);
    const sync = () => {
      $$('[data-kind-only]', popupForm).forEach(el => { el.hidden = !el.dataset.kindOnly.split(' ').includes(kind.value); });
      const icon = { promo: '🎁', newsletter: '💌', wheel: '🎡' }[kind.value];
      $('[data-pv-icon]', pv).textContent = icon;
      $('[data-pv-title]', pv).textContent = $('[name=title]', popupForm).value || 'Votre titre ici';
      $('[data-pv-body]', pv).textContent = $('[name=body]', popupForm).value;
      const btn = $('[name=button_label]', popupForm).value || { promo: 'J’en profite', newsletter: 'Je m’abonne', wheel: 'Tourner la roue !' }[kind.value];
      $('[data-pv-btn]', pv).textContent = btn;
      const code = $('[name=coupon_code]', popupForm).value.trim().toUpperCase();
      const codeBox = $('[data-pv-code]', pv); codeBox.hidden = !(kind.value === 'promo' && code); codeBox.textContent = code;
      $('[data-pv-email]', pv).hidden = kind.value === 'promo';
      pv.style.setProperty('--ac', $('[name=accent]', popupForm).value);
    };
    popupForm.addEventListener('input', sync); popupForm.addEventListener('change', sync); sync();
  }

  /* ---------- saisie manuelle de commande ---------- */
  const builder = $('[data-order-builder]');
  if (builder) {
    const list = $('[data-lines]', builder), tpl = $('#line-template');
    const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: +builder.dataset.decimals, maximumFractionDigits: +builder.dataset.decimals });
    const money = n => builder.dataset.position === 'after' ? `${fmt.format(n)} ${builder.dataset.symbol}` : `${builder.dataset.symbol}${fmt.format(n)}`;
    let zones = []; try { zones = JSON.parse(builder.dataset.zones || '[]'); } catch (e) {}
    const city = $('[name="customer[city]"]', builder);
    let index = list.children.length;

    const recalc = () => {
      let sub = 0;
      $$('.line-row', list).forEach(row => {
        const opt = $('select', row).selectedOptions[0], qtyInput = $('input[type=number]', row);
        const price = opt ? +opt.dataset.price || 0 : 0, stock = opt ? +opt.dataset.stock : 0;
        qtyInput.max = stock || 999;
        const total = price * (+qtyInput.value || 0);
        sub += total;
        $('.line-total', row).textContent = money(total);
      });
      const zone = zones.find(z => z.name.toLowerCase() === String(city?.value || '').trim().toLowerCase());
      const fee = zone ? zone.fee : +builder.dataset.shipping, freeFrom = zone ? zone.free_threshold : +builder.dataset.freeFrom;
      const discount = Math.min(+($('[name=discount]', builder)?.value || 0), sub);
      const ship = sub <= 0 ? 0 : (freeFrom > 0 && sub >= freeFrom ? 0 : fee);
      $('[data-sub]', builder).textContent = money(sub);
      $('[data-ship]', builder).textContent = ship ? money(ship) : 'Offerte';
      $('[data-discount]', builder).textContent = '− ' + money(discount);
      $('[data-grand]', builder).textContent = money(sub + ship - discount);
    };
    const addLine = () => {
      const html = tpl.innerHTML.replaceAll('__i__', index++);
      list.insertAdjacentHTML('beforeend', html);
      window.MKSelects?.enhance(list.lastElementChild);
      recalc();
    };
    $('[data-add-line]', builder).addEventListener('click', addLine);
    list.addEventListener('click', e => {
      if (!e.target.closest('[data-remove-line]')) return;
      e.target.closest('.line-row').remove();
      if (!list.children.length) addLine();
      recalc();
    });
    builder.addEventListener('input', recalc);
    builder.addEventListener('change', recalc);
    if (!list.children.length) addLine(); else recalc();
  }

  /* ---------- barres de filtres des listes : recherche en direct, pastilles, réinitialisation ---------- */
  $$('form.toolbar[data-filters]').forEach(form => {
    const send = () => { const q = $('input[type=search]', form); if (q) { try { sessionStorage.setItem('mk_refocus', q.name); } catch (e) {} } form.requestSubmit(); };
    const search = $('input[type=search]', form);
    if (search) {
      let t; search.addEventListener('input', () => { clearTimeout(t); t = setTimeout(send, 450); });
      try { if (sessionStorage.getItem('mk_refocus') === search.name) { sessionStorage.removeItem('mk_refocus'); search.focus(); search.setSelectionRange(search.value.length, search.value.length); } } catch (e) {}
    }
    $$('select', form).forEach(sel => sel.addEventListener('change', send));
    $$('input[type=date]', form).forEach(i => i.addEventListener('change', send));
    const submitBtn = $('button:not([type=button])', form); if (submitBtn) submitBtn.hidden = true;

    const labelOf = el => el.dataset.label || el.getAttribute('aria-label') || el.placeholder || el.name;
    const fields = $$('input[name]:not([type=hidden]), select[name]', form).filter(el => el.name !== 'page');
    const active = fields.filter(el => String(el.value).trim() !== '');
    const base = form.getAttribute('action') || location.pathname;
    const keep = new URLSearchParams(); $$('input[type=hidden]', form).forEach(h => { if (h.value) keep.set(h.name, h.value); });
    if (active.length) {
      const chips = document.createElement('div'); chips.className = 'filter-chips';
      chips.innerHTML = '<span>Filtres actifs :</span>';
      active.forEach(el => {
        const text = el.tagName === 'SELECT' ? el.selectedOptions[0]?.textContent.trim() : el.value;
        const chip = document.createElement('button'); chip.type = 'button'; chip.className = 'chip-x';
        chip.innerHTML = `<b>${UI().esc(labelOf(el))}</b> ${UI().esc(text)} <i aria-hidden="true">×</i>`; chip.setAttribute('aria-label', `Retirer le filtre ${labelOf(el)}`);
        chip.addEventListener('click', () => { el.value = ''; el.tomselect?.setValue('', true); send(); });
        chips.appendChild(chip);
      });
      const reset = document.createElement('a'); reset.className = 'chip-reset'; reset.href = keep.toString() ? `${base}?${keep}` : base; reset.textContent = 'Tout effacer';
      chips.appendChild(reset);
      form.after(chips);
    }
  });

  /* ---------- tableaux intelligents : recherche multi-mots, filtres par colonne, tri, compteur ---------- */
  $$('table[data-smart]').forEach(table => {
    const wrap = table.closest('.table-wrap') || table;
    const body = $('tbody', table);
    const rows = $$('tr', body).filter(tr => !tr.querySelector('.empty-state, td.empty'));
    if (rows.length < 2) return;
    const heads = $$('thead th', table);
    const cell = (tr, i) => tr.children[i];
    const valueOf = (tr, i) => { const c = cell(tr, i); return c ? (c.dataset.filter ?? c.innerText.trim().split('\n')[0].trim()) : ''; };

    const bar = document.createElement('div'); bar.className = 'smart-bar';
    bar.innerHTML = `<label class="search smart-search"><svg class="ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><span class="sr-only">Filtrer le tableau</span><input type="search" placeholder="Filtrer… (plusieurs mots, sans accents)" autocomplete="off"></label>`;
    const selects = [];
    heads.forEach((th, i) => {
      if (th.dataset.filter === undefined) return;
      const counts = new Map(); rows.forEach(tr => { const v = valueOf(tr, i); if (v) counts.set(v, (counts.get(v) || 0) + 1); });
      if (counts.size < 2) return;
      const sel = document.createElement('select'); sel.multiple = true; sel.dataset.placeholder = th.textContent.trim() || 'Filtrer'; sel.setAttribute('aria-label', `Filtrer par ${th.textContent.trim()}`);
      [...counts.entries()].sort((a, b) => a[0].localeCompare(b[0], 'fr')).forEach(([v, n]) => sel.add(new Option(`${v} (${n})`, v)));
      bar.appendChild(sel); selects.push({ sel, col: i });
    });
    const count = document.createElement('span'); count.className = 'smart-count'; bar.appendChild(count);
    const reset = document.createElement('button'); reset.type = 'button'; reset.className = 'btn btn-sm'; reset.textContent = 'Réinitialiser'; reset.hidden = true; bar.appendChild(reset);
    wrap.parentElement.insertBefore(bar, wrap);
    window.MKSelects?.enhance(bar);

    const empty = document.createElement('tr'); empty.className = 'smart-empty'; empty.hidden = true;
    empty.innerHTML = `<td colspan="${heads.length}"><div class="empty-state"><span>🔎</span><b>Aucun résultat</b><p>Aucune ligne ne correspond à vos filtres.</p></div></td>`; body.appendChild(empty);

    const input = $('input', bar);
    const apply = () => {
      const words = norm(input.value).split(/\s+/).filter(Boolean);
      const picks = selects.map(({ sel, col }) => ({ col, values: [...sel.selectedOptions].map(o => o.value) })).filter(p => p.values.length);
      let n = 0;
      rows.forEach(tr => {
        const text = norm(tr.textContent);
        const ok = words.every(w => text.includes(w)) && picks.every(p => p.values.includes(valueOf(tr, p.col)));
        tr.hidden = !ok; if (ok) n++;
      });
      empty.hidden = n > 0;
      count.textContent = n === rows.length ? `${rows.length} ligne(s)` : `${n} / ${rows.length} ligne(s)`;
      reset.hidden = !(words.length || picks.length);
    };
    input.addEventListener('input', apply);
    selects.forEach(({ sel }) => sel.addEventListener('change', apply));
    reset.addEventListener('click', () => { input.value = ''; selects.forEach(({ sel }) => sel.tomselect?.clear(true)); apply(); });

    const numeric = t => { const m = String(t).replace(/\s|\u00a0|\u202f/g, '').replace(',', '.').match(/-?\d+(\.\d+)?/); return m ? parseFloat(m[0]) : NaN; };
    heads.forEach((th, i) => {
      if (th.dataset.sort === undefined) return;
      th.classList.add('is-sortable'); th.tabIndex = 0; th.setAttribute('role', 'button');
      const sort = () => {
        const dir = th.classList.contains('sort-asc') ? -1 : 1;
        heads.forEach(h => h.classList.remove('sort-asc', 'sort-desc')); th.classList.add(dir === 1 ? 'sort-asc' : 'sort-desc');
        const key = tr => { const c = cell(tr, i); return c ? (c.dataset.sort ?? c.innerText.trim()) : ''; };
        rows.sort((a, b) => { const x = key(a), y = key(b), nx = numeric(x), ny = numeric(y); return dir * ((!isNaN(nx) && !isNaN(ny)) ? nx - ny : String(x).localeCompare(String(y), 'fr', { numeric: true })); });
        rows.forEach(tr => body.insertBefore(tr, empty));
      };
      th.addEventListener('click', sort); th.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sort(); } });
    });
    apply();
  });
})();
