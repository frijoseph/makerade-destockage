/* Fiche produit : choix de la variante (puces par option), prix / stock / photo mis à jour, ajout au panier « slug~id ». */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const root = $('[data-pd-variants]');
  if (!root) return;

  const variants = JSON.parse(root.dataset.variants || '[]'), slug = root.dataset.slug;
  const detail = $('.pdetail'), low = +detail?.dataset.lowStock || 0;
  const axes = $$('[data-axis]', root);
  const money = window.mkMoney || (n => Number(n).toFixed(2));
  const priceBox = $('#pdPrice'), priceSticky = $('#pdPriceSticky'), stockBox = $('#pdStock'), qty = $('#qtyVal');
  const buttons = [$('#pdAdd'), $('#pdAddSticky')].filter(Boolean);
  const main = $('#pdMain'), thumbs = $$('.pd-thumbs [data-thumb]');
  const mainDefault = main && main.tagName === 'IMG' ? main.getAttribute('src') : null;
  const sel = [];

  const matches = (v, skip = -1) => sel.every((value, i) => i === skip || !value || v.options[i] === value);
  const current = () => variants.find(v => sel.every((value, i) => v.options[i] === value));

  function setMain(url) {
    if (main && main.tagName === 'IMG' && url) {
      main.src = url;
      thumbs.forEach(t => t.classList.toggle('on', t.dataset.thumb === url));
    }
  }

  function apply(v) {
    const saving = v.old_price && v.old_price > v.price ? `<s>${money(v.old_price)}</s><em>Économisez ${money(v.old_price - v.price)}</em>` : '';
    priceBox.innerHTML = `<b>${money(v.price)}</b>${saving}`;
    if (priceSticky) priceSticky.textContent = money(v.price);
    buttons.forEach(b => { b.dataset.add = `${slug}~${v.id}`; b.disabled = v.stock <= 0; });
    if (stockBox) {
      stockBox.hidden = !(v.stock <= 0 || (low > 0 && v.stock <= low));
      stockBox.textContent = v.stock <= 0 ? 'Rupture de stock — revenez bientôt.' : `Plus que ${v.stock} en stock`;
    }
    if (qty) { qty.max = v.stock || 99; if (+qty.value > v.stock && v.stock > 0) qty.value = v.stock; }
    if (v.image) setMain(v.image); else if (mainDefault) setMain(mainDefault);
  }

  function refresh() {
    axes.forEach((axis, i) => {
      $('[data-axis-value]', axis).textContent = sel[i] || '';
      $$('.pv-chip', axis).forEach(chip => {
        const options = variants.filter(v => v.options[i] === chip.dataset.value && matches(v, i));
        chip.classList.toggle('on', sel[i] === chip.dataset.value);
        chip.classList.toggle('na', options.length === 0);
        chip.classList.toggle('oos', options.length > 0 && options.every(v => v.stock <= 0));
        chip.setAttribute('aria-pressed', String(sel[i] === chip.dataset.value));
      });
    });
    const v = current();
    if (v) apply(v);
  }

  root.addEventListener('click', e => {
    const chip = e.target.closest('.pv-chip');
    if (!chip) return;
    const i = +chip.closest('[data-axis]').dataset.axis;
    sel[i] = chip.dataset.value;
    if (!current()) {
      // la combinaison n'existe pas : on bascule sur la première variante qui porte cette valeur
      const fallback = variants.find(v => v.options[i] === sel[i] && v.stock > 0) || variants.find(v => v.options[i] === sel[i]);
      if (fallback) fallback.options.forEach((value, k) => { sel[k] = value; });
    }
    refresh();
  });

  $$('.pd-thumbs [data-thumb]').forEach(t => t.addEventListener('click', () => {
    if (main && main.tagName === 'IMG') { main.src = t.dataset.thumb; thumbs.forEach(x => x.classList.toggle('on', x === t)); }
  }));

  const first = variants.find(v => v.stock > 0) || variants[0];
  if (first) { first.options.forEach((value, i) => { sel[i] = value; }); refresh(); }
})();
