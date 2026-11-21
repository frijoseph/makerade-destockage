/* Makerade — back office interactions */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const root = document.documentElement;

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
  document.addEventListener('keydown', e => { if (e.key === 'Escape') document.body.classList.remove('side-open'); });

  /* ---------- auto-submit filters ---------- */
  $$('[data-autosubmit]').forEach(el => el.addEventListener('change', () => el.form.requestSubmit()));

  /* ---------- clickable rows ---------- */
  document.addEventListener('click', e => {
    const row = e.target.closest('tr[data-href]');
    if (!row || e.target.closest('a, button, input, select, form')) return;
    location.href = row.dataset.href;
  });

  /* ---------- confirm destructive actions ---------- */
  document.addEventListener('submit', e => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) e.preventDefault();
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
    picker.addEventListener('input', () => { text.value = picker.value; text.dispatchEvent(new Event('input')); });
    text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
  });

  /* ---------- product image preview ---------- */
  $$('[data-image-input]').forEach(input => input.addEventListener('change', () => {
    const file = input.files[0], box = $(input.dataset.imageInput);
    if (!file || !box) return;
    box.innerHTML = '';
    const img = document.createElement('img');
    img.alt = '';
    img.src = URL.createObjectURL(file);
    box.appendChild(img);
  }));

  /* ---------- manual order builder ---------- */
  const builder = $('[data-order-builder]');
  if (builder) {
    const list = $('[data-lines]', builder), tpl = $('#line-template');
    const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: +builder.dataset.decimals, maximumFractionDigits: +builder.dataset.decimals });
    const money = n => builder.dataset.position === 'after' ? `${fmt.format(n)} ${builder.dataset.symbol}` : `${builder.dataset.symbol}${fmt.format(n)}`;
    const shipFee = +builder.dataset.shipping, freeFrom = +builder.dataset.freeFrom;
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
      const discount = Math.min(+($('[name=discount]', builder)?.value || 0), sub);
      const ship = sub <= 0 ? 0 : (freeFrom > 0 && sub >= freeFrom ? 0 : shipFee);
      $('[data-sub]', builder).textContent = money(sub);
      $('[data-ship]', builder).textContent = ship ? money(ship) : 'Offerte';
      $('[data-discount]', builder).textContent = '− ' + money(discount);
      $('[data-grand]', builder).textContent = money(sub + ship - discount);
    };
    const addLine = () => {
      const html = tpl.innerHTML.replaceAll('__i__', index++);
      list.insertAdjacentHTML('beforeend', html);
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
})();
