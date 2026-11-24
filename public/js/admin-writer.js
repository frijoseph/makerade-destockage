/* Rédaction automatique : bouton « Rédiger » et rédaction dès la saisie du nom (descriptions de produits, articles).
   Configuration dans l'attribut data-writer du bloc qui contient l'éditeur. Ne remplace jamais un texte écrit à la main. */
(() => {
  'use strict';
  const UI = () => window.MKUI;

  document.querySelectorAll('[data-writer]').forEach(box => {
    let cfg; try { cfg = JSON.parse(box.dataset.writer); } catch (e) { return; }
    const form = box.closest('form'); if (!form) return;
    const field = sel => (sel ? form.querySelector(sel) : null);
    const textarea = box.querySelector('textarea[data-rich]'); if (!textarea) return;
    const rte = textarea._rte || window.MKRich?.build(textarea); if (!rte) return;
    const button = box.querySelector('[data-writer-run]');
    const excerpt = field(cfg.excerpt);
    let variation = 0, autoFilled = false, excerptAuto = false, timer, busy = false;

    const payload = () => ({
      type: cfg.type, name: (field(cfg.name)?.value || '').trim(), variant: field(cfg.variant)?.value || null,
      category_id: field(cfg.category)?.value || null, brand_id: field(cfg.brand)?.value || null, tag: field(cfg.tag)?.value || null,
      variation, ignore_id: cfg.ignoreId || null,
    });

    async function run(auto) {
      const body = payload();
      if (body.name.length < 2) { if (!auto) UI().toast('Saisissez d’abord le nom.', 'error'); return; }
      if (busy) return;
      busy = true; button?.classList.add('is-loading'); if (button) button.disabled = true;
      try {
        const res = await fetch(cfg.url, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': UI().csrf() }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { if (!auto) UI().toast(data.message || Object.values(data.errors || {})[0]?.[0] || 'Rédaction impossible.', 'error'); return; }
        rte.fill(data.html); autoFilled = true;
        if (excerpt && data.excerpt && (!excerpt.value.trim() || excerptAuto)) { excerpt.value = data.excerpt; excerptAuto = true; }
        UI().toast(auto ? 'Texte rédigé automatiquement : relisez-le et ajustez-le.' : (data.source === 'ai' ? 'Texte rédigé par l’IA.' : 'Nouveau texte rédigé.'), 'success');
      } catch (e) {
        if (!auto) UI().toast('Rédaction impossible pour le moment.', 'error');
      } finally { busy = false; button?.classList.remove('is-loading'); if (button) button.disabled = false; }
    }

    button?.addEventListener('click', async () => {
      if (!rte.isEmpty() && !autoFilled && !await UI().confirm({ title: 'Remplacer le texte actuel ?', text: 'Le texte écrit sera remplacé par un texte rédigé automatiquement.', confirmText: 'Oui, rédiger', danger: false })) return;
      if (autoFilled || !rte.isEmpty()) variation++;
      run(false);
    });

    if (cfg.auto) {
      [cfg.name, cfg.variant, cfg.category, cfg.brand, cfg.tag].filter(Boolean).forEach(sel => field(sel)?.addEventListener('change', () => {
        clearTimeout(timer);
        timer = setTimeout(() => { if (rte.isEmpty() || (autoFilled && !rte.state.dirty)) run(true); }, 500);
      }));
    }
    excerpt?.addEventListener('input', () => { excerptAuto = false; });
  });
})();
