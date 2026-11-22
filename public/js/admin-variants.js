/* Éditeur de variantes de la fiche produit : axes d'options, génération des combinaisons, lignes éditables. */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const root = $('[data-variants]');
  if (!root) return;

  const rowsBox = $('[data-var-rows]', root), tpl = $('#variantRowTemplate'), table = $('[data-var-table]', root);
  const empty = $('[data-var-empty]', root), counter = $('[data-var-count]', root);
  const axisNames = () => $$('[data-axis-name]', root).map(i => i.value.trim());
  const rows = () => $$('[data-var-row]', rowsBox);

  /* Colonnes visibles = axes nommés (au moins 1), plus toute colonne déjà remplie dans une ligne. */
  function layout() {
    const names = axisNames();
    let visible = names.reduce((n, v, i) => (v ? i + 1 : n), 1);
    rows().forEach(r => $$('[data-opt]', r).forEach((input, i) => { if (input.value.trim() && i + 1 > visible) visible = i + 1; }));
    [0, 1, 2].forEach(i => {
      $$(`[data-col="o${i}"]`, table).forEach(cell => { cell.hidden = i >= visible; });
      const th = $(`thead [data-col="o${i}"]`, table);
      if (th) th.textContent = names[i] || `Option ${i + 1}`;
    });
    counter.textContent = rows().length ? `${rows().length} variante(s)` : '';
    empty.hidden = rows().length > 0;
  }

  function renumber() {
    rows().forEach((row, i) => $$('[name]', row).forEach(el => {
      el.name = el.name.replace(/variants\[(\d+|__i__)\]/, `variants[${i}]`);
    }));
  }

  function addRow(values = {}) {
    rowsBox.insertAdjacentHTML('beforeend', tpl.innerHTML.replaceAll('__i__', rows().length));
    const row = rowsBox.lastElementChild;
    $$('[data-opt]', row).forEach((input, i) => { input.value = values.options?.[i] || ''; });
    if (values.sku) $('[data-sku]', row).value = values.sku;
    renumber(); layout();
    return row;
  }

  const existing = () => new Set(rows().map(r => $$('[data-opt]', r).map(i => i.value.trim().toLowerCase()).join('|')));
  const nextSku = () => {
    const base = root.dataset.baseSku || 'VAR', used = new Set($$('[data-sku]', rowsBox).map(i => i.value));
    let n = rows().length + 1, sku;
    do { sku = `${base}-${String(n++).padStart(2, '0')}`; } while (used.has(sku));
    return sku;
  };

  /* Produit cartésien des valeurs saisies */
  $('[data-generate]', root).addEventListener('click', () => {
    const axes = $$('[data-axis]', root).map(a => ({
      name: $('[data-axis-name]', a).value.trim(),
      values: $('[data-axis-values]', a).value.split(',').map(v => v.trim()).filter(Boolean),
    })).filter(a => a.values.length);
    if (!axes.length) {
      return window.MKUI?.toast('Saisissez les valeurs d’au moins une option (ex. 500 ml, 1 L).', 'info');
    }
    let combos = [[]];
    axes.forEach(a => { combos = combos.flatMap(c => a.values.map(v => [...c, v])); });
    if (combos.length > 100) return window.MKUI?.toast('Trop de combinaisons (100 maximum).', 'error');
    const have = existing();
    let added = 0;
    // Les valeurs sont placées dans l'ordre des axes utilisés : on aligne les noms d'options en conséquence.
    const nameInputs = $$('[data-axis-name]', root);
    nameInputs.forEach((input, i) => { input.value = axes[i]?.name || ''; });
    combos.forEach(combo => {
      if (have.has(combo.join('|').toLowerCase())) return;
      addRow({ options: combo, sku: nextSku() });
      added++;
    });
    $$('[data-axis-values]', root).forEach(i => { i.value = ''; });
    window.MKUI?.toast(added ? `${added} variante(s) ajoutée(s).` : 'Ces combinaisons existent déjà.', added ? 'success' : 'info');
  });

  $('[data-add-variant]', root).addEventListener('click', () => {
    const row = addRow({ sku: nextSku() });
    $('[data-opt]', row)?.focus();
  });

  rowsBox.addEventListener('click', async e => {
    const btn = e.target.closest('[data-remove-variant]');
    if (!btn) return;
    const row = btn.closest('[data-var-row]');
    const isSaved = $('input[name$="[id]"]', row)?.value;
    const label = $$('[data-opt]', row).map(i => i.value.trim()).filter(Boolean).join(' / ') || 'cette variante';
    if (isSaved && window.MKUI) {
      const ok = await MKUI.confirm({ title: 'Retirer la variante ?', text: `« ${label} » sera supprimée à l’enregistrement (son stock restant est retiré du stock total). Si elle a déjà été commandée, elle sera seulement désactivée.`, confirmText: 'Retirer', danger: true });
      if (!ok) return;
    }
    row.remove(); renumber(); layout();
  });

  root.addEventListener('input', e => { if (e.target.matches('[data-axis-name], [data-opt]')) layout(); });
  layout();
})();
