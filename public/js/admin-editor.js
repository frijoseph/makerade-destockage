/* Éditeur de texte riche de l'administration : mise en forme façon traitement de texte, sans dépendance.
   Active sur chaque <textarea data-rich>. Le HTML produit est nettoyé une seconde fois côté serveur. */
(() => {
  'use strict';

  const ICONS = {
    bold: '<path d="M7 5h6a3.5 3.5 0 0 1 0 7H7zM7 12h7a3.5 3.5 0 0 1 0 7H7z"/>',
    italic: '<path d="M19 4h-9M14 20H5M15 4 9 20"/>',
    underline: '<path d="M6 4v7a6 6 0 0 0 12 0V4M4 21h16"/>',
    strike: '<path d="M16 6.5C15.3 5.2 13.9 4.5 12 4.5c-2.4 0-4 1.2-4 3 0 4 8 2.5 8 7 0 1.9-1.8 3-4 3-2.1 0-3.7-.9-4.4-2.5M4 12h16"/>',
    ul: '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1" fill="currentColor"/><circle cx="4.5" cy="12" r="1" fill="currentColor"/><circle cx="4.5" cy="18" r="1" fill="currentColor"/>',
    ol: '<path d="M10 6h10M10 12h10M10 18h10M4 5l1.5-1v4M3.5 13.5c.3-.9 2.5-1 2.5.3 0 1-2.5 2-2.5 3h2.6"/>',
    left: '<path d="M4 6h16M4 10h10M4 14h16M4 18h10"/>',
    center: '<path d="M4 6h16M7 10h10M4 14h16M7 18h10"/>',
    right: '<path d="M4 6h16M10 10h10M4 14h16M10 18h10"/>',
    justify: '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
    link: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 18.7l1-1"/>',
    unlink: '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 18.7l1-1M4 4l16 16"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/>',
    table: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/>',
    quote: '<path d="M6 17c-1.5 0-3-1-3-3.5C3 10 5 7.5 8 6.5M15 17c-1.5 0-3-1-3-3.5 0-3.5 2-6 5-7"/>',
    hr: '<path d="M4 12h16"/>',
    clear: '<path d="m7 21 3-3M4 20h8M9 4h9M13 4 8 16M17 14l4 6M21 14l-4 6"/>',
    undo: '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
    redo: '<path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/>',
    code: '<path d="m8 8-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
    expand: '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
    magic: '<path d="M5 19 19 5M14 4l.8 2.2L17 7l-2.2.8L14 10l-.8-2.2L11 7l2.2-.8zM18 13l.6 1.6 1.6.6-1.6.6L18 17.4l-.6-1.6-1.6-.6 1.6-.6z"/>',
  };
  const svg = name => `<svg class="ic" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICONS[name]}</svg>`;
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const ALLOWED = new Set(['P', 'BR', 'H2', 'H3', 'H4', 'STRONG', 'EM', 'U', 'S', 'SUB', 'SUP', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'A', 'IMG', 'HR', 'TABLE', 'THEAD', 'TBODY', 'TR', 'TH', 'TD', 'MARK']);
  const RENAME = { B: 'STRONG', I: 'EM', STRIKE: 'S', DEL: 'S', H1: 'H2', H5: 'H4', H6: 'H4' };
  const DROP = new Set(['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED', 'FORM', 'INPUT', 'BUTTON', 'TEXTAREA', 'SELECT', 'SVG', 'META', 'LINK', 'TITLE', 'HEAD', 'XML']);
  const safeUrl = (u, imageOnly) => {
    u = String(u || '').replace(/[\u0000- ]+/g, '');
    if (!u) return '';
    const m = u.match(/^([a-z][a-z0-9+.-]*):/i);
    if (m) return (imageOnly ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel']).includes(m[1].toLowerCase()) ? u : '';
    return /^(\/(?!\/)|#)/.test(u) ? u : '';
  };

  /* Nettoie du HTML collé (Word, sites web…) : on ne garde que la structure utile. */
  function clean(html) {
    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, 'text/html');
    const walk = parent => {
      [...parent.childNodes].forEach(node => {
        if (node.nodeType === 3) return;
        if (node.nodeType !== 1) return node.remove();
        let tag = RENAME[node.tagName] || node.tagName;
        if (DROP.has(node.tagName) || node.tagName.includes(':')) return node.remove();
        if (tag === 'DIV' && ![...node.children].some(c => /^(P|H[1-6]|UL|OL|BLOCKQUOTE|TABLE|DIV)$/.test(c.tagName))) tag = 'P';
        if (!ALLOWED.has(tag)) { walk(node); node.replaceWith(...node.childNodes); return; }
        const keep = {};
        if (tag === 'A') { const h = safeUrl(node.getAttribute('href')); if (!h) { walk(node); node.replaceWith(...node.childNodes); return; } keep.href = h; }
        if (tag === 'IMG') { const s = safeUrl(node.getAttribute('src'), true); if (!s) return node.remove(); keep.src = s; keep.alt = node.getAttribute('alt') || ''; }
        if (/^(P|H2|H3|H4|LI|BLOCKQUOTE|TD|TH)$/.test(tag)) {
          const a = (node.style?.textAlign || node.getAttribute('align') || '').toLowerCase();
          if (['center', 'right', 'justify'].includes(a)) keep.class = 'ta-' + a;
        }
        const out = tag === node.tagName ? node : doc.createElement(tag);
        if (out !== node) { while (node.firstChild) out.appendChild(node.firstChild); node.replaceWith(out); }
        [...out.attributes].forEach(at => out.removeAttribute(at.name));
        Object.entries(keep).forEach(([k, v]) => out.setAttribute(k, v));
        walk(out);
      });
    };
    walk(doc.body);
    return doc.body.innerHTML;
  }

  const BLOCK_LABELS = [['p', 'Paragraphe'], ['h2', 'Titre 1'], ['h3', 'Titre 2'], ['h4', 'Titre 3'], ['blockquote', 'Citation']];

  const BUTTONS = [
    ['block'],
    ['sep'],
    ['bold', 'bold', 'Gras (Ctrl+B)'], ['italic', 'italic', 'Italique (Ctrl+I)'], ['underline', 'underline', 'Souligné (Ctrl+U)'], ['strikeThrough', 'strike', 'Barré'],
    ['sep'],
    ['insertUnorderedList', 'ul', 'Liste à puces'], ['insertOrderedList', 'ol', 'Liste numérotée'],
    ['sep'],
    ['justifyLeft', 'left', 'Aligner à gauche'], ['justifyCenter', 'center', 'Centrer'], ['justifyRight', 'right', 'Aligner à droite'], ['justifyFull', 'justify', 'Justifier'],
    ['sep'],
    ['link', 'link', 'Insérer un lien (Ctrl+K)'], ['unlink', 'unlink', 'Retirer le lien'], ['image', 'image', 'Insérer une image'], ['table', 'table', 'Insérer un tableau'], ['insertHorizontalRule', 'hr', 'Ligne de séparation'],
    ['sep'],
    ['removeFormat', 'clear', 'Effacer la mise en forme'], ['undo', 'undo', 'Annuler (Ctrl+Z)'], ['redo', 'redo', 'Rétablir (Ctrl+Y)'],
    ['spacer'],
    ['source', 'code', 'Code HTML'], ['fullscreen', 'expand', 'Plein écran'],
  ];

  function build(textarea) {
    if (textarea._rte) return textarea._rte;
    const wrap = document.createElement('div');
    wrap.className = 'rte';
    const blocks = BLOCK_LABELS.map(([v, l]) => `<option value="${v}">${l}</option>`).join('');
    const bar = BUTTONS.map(b => {
      if (b[0] === 'block') return `<select class="rte-block" aria-label="Style du paragraphe" title="Style du paragraphe">${blocks}</select>`;
      if (b[0] === 'sep') return '<i class="rte-sep" aria-hidden="true"></i>';
      if (b[0] === 'spacer') return '<i class="rte-spacer" aria-hidden="true"></i>';
      return `<button type="button" class="rte-btn" data-cmd="${b[0]}" title="${esc(b[2])}" aria-label="${esc(b[2])}">${svg(b[1])}</button>`;
    }).join('');
    wrap.innerHTML = `<div class="rte-bar" role="toolbar" aria-label="Mise en forme">${bar}</div>
      <div class="rte-area rich" contenteditable="true" role="textbox" aria-multiline="true" data-placeholder="${esc(textarea.getAttribute('placeholder') || 'Écrivez votre texte…')}"></div>
      <textarea class="rte-source" hidden spellcheck="false" aria-label="Code HTML"></textarea>
      <div class="rte-foot"><span data-rte-count></span><span class="muted">Collez depuis Word ou le web : la mise en forme est nettoyée automatiquement.</span></div>`;
    textarea.hidden = true;
    textarea.after(wrap);

    const area = wrap.querySelector('.rte-area'), source = wrap.querySelector('.rte-source'), count = wrap.querySelector('[data-rte-count]'), blockSel = wrap.querySelector('.rte-block');
    const state = { dirty: false, saved: null };
    let sourceMode = false;

    const isEmpty = () => !area.textContent.trim() && !area.querySelector('img,table,hr');
    const getHTML = () => (sourceMode ? clean(source.value) : (isEmpty() ? '' : clean(area.innerHTML)));
    const sync = () => {
      textarea.value = getHTML();
      const words = (area.textContent.trim().match(/\S+/g) || []).length;
      count.textContent = `${words} mot${words > 1 ? 's' : ''}`;
      textarea.dispatchEvent(new CustomEvent('rte:change', { bubbles: true }));
    };
    const setHTML = (html, { silent = false } = {}) => {
      area.innerHTML = clean(html || '');
      if (sourceMode) source.value = area.innerHTML;
      sync();
      if (!silent) state.dirty = true;
    };

    const saveRange = () => { const s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) state.saved = s.getRangeAt(0).cloneRange(); };
    const restoreRange = () => { area.focus(); if (state.saved) { const s = getSelection(); s.removeAllRanges(); s.addRange(state.saved); } };
    const exec = (cmd, value = null) => {
      area.focus();
      document.execCommand('styleWithCSS', false, cmd.startsWith('justify'));
      document.execCommand(cmd, false, value);
      afterChange();
    };
    const insert = html => { restoreRange(); document.execCommand('insertHTML', false, html); afterChange(); };

    const refreshState = () => {
      wrap.querySelectorAll('.rte-btn[data-cmd]').forEach(b => {
        const c = b.dataset.cmd;
        if (/^(bold|italic|underline|strikeThrough|insertUnorderedList|insertOrderedList|justifyLeft|justifyCenter|justifyRight|justifyFull)$/.test(c)) {
          let on = false; try { on = document.queryCommandState(c); } catch (e) {}
          b.classList.toggle('is-on', on);
        }
      });
      let v = 'p'; try { v = (document.queryCommandValue('formatBlock') || 'p').toLowerCase().replace(/[<>]/g, ''); } catch (e) {}
      blockSel.value = BLOCK_LABELS.some(([k]) => k === v) ? v : 'p';
    };
    const afterChange = () => { state.dirty = true; sync(); refreshState(); };

    area.addEventListener('input', afterChange);
    area.addEventListener('keyup', () => { saveRange(); refreshState(); });
    area.addEventListener('mouseup', () => { saveRange(); refreshState(); });
    area.addEventListener('blur', saveRange);
    document.addEventListener('selectionchange', () => { if (area.contains(getSelection().anchorNode)) refreshState(); });
    area.addEventListener('paste', e => {
      e.preventDefault();
      const html = e.clipboardData.getData('text/html'), text = e.clipboardData.getData('text/plain');
      const out = html ? clean(html) : text.split(/\n{2,}/).map(p => `<p>${esc(p).replace(/\n/g, '<br>')}</p>`).join('');
      document.execCommand('insertHTML', false, out);
      afterChange();
    });
    area.addEventListener('keydown', e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); wrap.querySelector('[data-cmd=link]').click(); } });
    area.addEventListener('click', e => { const a = e.target.closest('a'); if (a) e.preventDefault(); });

    blockSel.addEventListener('change', () => { restoreRange(); exec('formatBlock', `<${blockSel.value}>`); });

    const askLink = async () => {
      saveRange();
      const current = getSelection().anchorNode?.parentElement?.closest('a')?.getAttribute('href') || '';
      const { value, isConfirmed } = await Swal.fire({
        title: 'Insérer un lien', input: 'url', inputValue: current, inputPlaceholder: 'https://…', showCancelButton: true,
        confirmButtonText: 'Insérer', cancelButtonText: 'Annuler', inputValidator: v => (v && !safeUrl(v) ? 'Adresse non valide (http, https, mailto ou tel).' : undefined),
      });
      if (!isConfirmed || !value) return;
      restoreRange();
      if (getSelection().isCollapsed) insert(`<a href="${esc(safeUrl(value))}">${esc(value)}</a>`);
      else exec('createLink', safeUrl(value));
    };
    const askImage = async () => {
      saveRange();
      let files = [];
      if (window.MKMedia?.pick) files = (await window.MKMedia.pick({ multiple: true, title: 'Insérer une image' })) || [];
      else { const { value } = await Swal.fire({ title: 'Adresse de l’image', input: 'url', showCancelButton: true }); if (value && safeUrl(value, true)) files = [{ url: value, name: '' }]; }
      if (files.length) insert(files.map(f => `<p><img src="${esc(f.url)}" alt="${esc((f.name || '').replace(/\.[^.]+$/, ''))}"></p>`).join(''));
    };

    wrap.querySelector('.rte-bar').addEventListener('mousedown', e => { if (e.target.closest('.rte-btn')) e.preventDefault(); });
    wrap.querySelector('.rte-bar').addEventListener('click', e => {
      const btn = e.target.closest('.rte-btn'); if (!btn) return;
      const cmd = btn.dataset.cmd;
      if (cmd === 'link') return askLink();
      if (cmd === 'image') return askImage();
      if (cmd === 'table') { saveRange(); return insert('<table><thead><tr><th>Titre</th><th>Titre</th><th>Titre</th></tr></thead><tbody><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table><p><br></p>'); }
      if (cmd === 'removeFormat') { exec('removeFormat'); return exec('formatBlock', '<p>'); }
      if (cmd === 'fullscreen') { wrap.classList.toggle('is-full'); document.body.classList.toggle('rte-lock', wrap.classList.contains('is-full')); btn.classList.toggle('is-on', wrap.classList.contains('is-full')); return; }
      if (cmd === 'source') {
        sourceMode = !sourceMode; btn.classList.toggle('is-on', sourceMode);
        if (sourceMode) { source.value = clean(area.innerHTML).replace(/(<\/(p|h[2-4]|ul|ol|li|blockquote|table|tr)>)/g, '$1\n'); }
        else { area.innerHTML = clean(source.value); sync(); }
        source.hidden = !sourceMode; area.hidden = sourceMode; return;
      }
      exec(cmd);
    });
    source.addEventListener('input', () => { state.dirty = true; textarea.value = clean(source.value); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && wrap.classList.contains('is-full')) { wrap.querySelector('[data-cmd=fullscreen]').click(); } });
    textarea.form?.addEventListener('submit', sync);

    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}
    area.innerHTML = clean(textarea.value || '');
    sync();

    const api = {
      el: wrap, area, state, getHTML, setHTML, isEmpty,
      focus: () => area.focus(),
      /** Insère un texte généré à la place du contenu : sans le marquer comme saisi à la main. */
      fill: html => setHTML(html, { silent: true }),
    };
    textarea._rte = api;
    return api;
  }

  window.MKRich = { build, clean };
  const init = () => document.querySelectorAll('textarea[data-rich]').forEach(build);
  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
