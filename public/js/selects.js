/* Champs de sélection avec recherche (Tom Select) — boutique et administration */
(() => {
  'use strict';
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function build(sel) {
    const many = sel.options.length >= (+sel.dataset.searchFrom || 6);
    const searchable = sel.multiple || many;
    const iconOf = d => (d.$option?.dataset?.icon ? `<span class="ts-ic">${d.$option.dataset.icon}</span>` : '');
    const hintOf = d => (d.$option?.dataset?.hint ? `<small>${esc(d.$option.dataset.hint)}</small>` : '');
    return new TomSelect(sel, {
      allowEmptyOption: true,
      maxOptions: null,
      create: false,
      closeAfterSelect: !sel.multiple,
      hideSelected: false,
      dropdownParent: 'body',
      plugins: sel.multiple ? ['remove_button', 'clear_button'] : (searchable ? ['dropdown_input'] : []),
      controlInput: searchable ? undefined : null,
      placeholder: sel.dataset.placeholder || '',
      render: {
        option: (d, e) => `<div class="ts-opt">${iconOf(d)}<span class="ts-txt">${e(d.text)}${hintOf(d)}</span></div>`,
        item: (d, e) => `<div class="ts-itm">${iconOf(d)}<span>${e(d.text)}</span></div>`,
        no_results: () => '<div class="no-results">Aucun résultat</div>',
      },
    });
  }

  function enhance(root = document) {
    if (typeof TomSelect === 'undefined') return;
    root.querySelectorAll('select:not([data-no-ts]):not(.swal2-select)').forEach(sel => {
      if (sel.tomselect || sel.closest('template')) return;
      try { build(sel); } catch (e) { console.warn('Tom Select', e); }
    });
  }

  window.MKSelects = { enhance };
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => enhance()) : enhance();
})();
