/* Makerade — médiathèque : gestion des dossiers/fichiers et sélecteur utilisé par la fiche produit. */
(() => {
  'use strict';
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const UI = () => window.MKUI;
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const bytes = n => n >= 1048576 ? (n / 1048576).toFixed(1).replace('.', ',') + ' Mo' : n >= 1024 ? Math.round(n / 1024) + ' Ko' : n + ' o';
  const headers = () => ({ Accept: 'application/json', 'X-CSRF-TOKEN': UI().csrf() });

  async function send(url, method, body) {
    const res = await fetch(url, { method, headers: { ...headers(), ...(body && !(body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}) }, body: body instanceof FormData ? body : (body ? JSON.stringify(body) : undefined) });
    const data = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data };
  }

  /* ---------- sélecteur (fiche produit) ---------- */
  async function pick({ multiple = false, type = 'image', title = 'Médiathèque', selected = [] } = {}) {
    if (!window.Swal) return null;
    const browseUrl = window.MK_MEDIA?.browse;
    if (!browseUrl) return null;
    const chosen = new Map();
    const state = { folder: 'all', q: '', page: 1, folders: null, files: [], next: null };

    const html = `
      <div class="mm-pick-bar"><input type="search" placeholder="Rechercher un fichier" data-pick-q aria-label="Rechercher"></div>
      <div class="mm-pick"><nav class="mm-pick-folders" data-pick-folders></nav><ul class="mm-pick-grid" data-pick-grid></ul></div>
      <p class="muted" style="margin:10px 0 0;font-size:13px" data-pick-hint></p>
      <div style="margin-top:8px"><button type="button" class="mk-btn" data-pick-more hidden>Charger plus</button></div>`;

    const load = async (reset = true) => {
      if (reset) { state.page = 1; state.files = []; }
      const url = new URL(browseUrl, location.origin);
      url.searchParams.set('folder', state.folder); url.searchParams.set('page', state.page); url.searchParams.set('type', type);
      if (state.q) url.searchParams.set('q', state.q);
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      const data = await res.json().catch(() => ({ files: [], folders: null }));
      state.folders = data.folders; state.files = state.files.concat(data.files || []); state.next = data.next;
      draw();
    };

    const draw = () => {
      const el = Swal.getPopup(); if (!el) return;
      const f = state.folders;
      if (f) {
        $('[data-pick-folders]', el).innerHTML =
          `<button type="button" data-f="all" class="${state.folder === 'all' ? 'is-active' : ''}">Tous <em>${f.all}</em></button>` +
          `<button type="button" data-f="none" class="${state.folder === 'none' ? 'is-active' : ''}">Sans dossier <em>${f.none}</em></button>` +
          f.folders.map(x => `<button type="button" data-f="${x.id}" class="${String(state.folder) === String(x.id) ? 'is-active' : ''}"><span>${esc(x.name)}</span><em>${x.count}</em></button>`).join('');
      }
      $('[data-pick-grid]', el).innerHTML = state.files.map(file =>
        `<li data-id="${file.id}" class="${chosen.has(file.id) ? 'is-picked' : ''}" title="${esc(file.name)}">${file.image ? `<img src="${esc(file.url)}" alt="" loading="lazy">` : `<b style="display:grid;place-items:center;height:100%">${esc(file.name.split('.').pop().toUpperCase())}</b>`}<span>${esc(file.name)}</span></li>`).join('')
        || '<li style="grid-column:1/-1;aspect-ratio:auto;border:0;background:none;cursor:default;padding:20px;text-align:center" class="muted">Aucun fichier dans ce dossier.</li>';
      $('[data-pick-more]', el).hidden = !state.next;
      $('[data-pick-hint]', el).textContent = chosen.size ? `${chosen.size} sélectionné(s)` : (multiple ? 'Cliquez sur plusieurs images pour les sélectionner.' : 'Cliquez sur une image pour la choisir.');
    };

    const result = await Swal.fire({
      title, html, width: 820, showCancelButton: true, confirmButtonText: 'Utiliser la sélection', cancelButtonText: 'Annuler', showCloseButton: true,
      customClass: { popup: 'mk-swal', confirmButton: 'mk-btn mk-btn-primary', cancelButton: 'mk-btn' },
      didOpen: el => {
        load();
        let timer;
        $('[data-pick-q]', el).addEventListener('input', e => { clearTimeout(timer); timer = setTimeout(() => { state.q = e.target.value.trim(); load(); }, 250); });
        el.addEventListener('click', e => {
          const fb = e.target.closest('[data-f]');
          if (fb) { state.folder = fb.dataset.f; load(); return; }
          if (e.target.closest('[data-pick-more]')) { state.page = state.next; load(false); return; }
          const li = e.target.closest('[data-pick-grid] li[data-id]'); if (!li) return;
          const file = state.files.find(x => String(x.id) === li.dataset.id); if (!file) return;
          if (chosen.has(file.id)) chosen.delete(file.id);
          else { if (!multiple) chosen.clear(); chosen.set(file.id, file); }
          draw();
        });
      },
      preConfirm: () => {
        if (!chosen.size) { Swal.showValidationMessage('Sélectionnez au moins un fichier.'); return false; }
        return [...chosen.values()];
      },
    });
    return result.isConfirmed ? result.value : null;
  }
  window.MKMedia = { pick, bytes };

  /* ---------- fiche produit : choisir dans la médiathèque ---------- */
  const mainBtn = $('[data-media-pick-main]');
  if (mainBtn) {
    const idInput = $('[data-media-main-id]'), preview = $('#imgPreview');
    mainBtn.addEventListener('click', async () => {
      const files = await pick({ multiple: false, title: 'Photo principale' });
      if (!files) return;
      idInput.value = files[0].id;
      preview.innerHTML = `<img src="${esc(files[0].url)}" alt="">`;
      const upload = $('input[name="image"]'); if (upload) upload.value = '';
      UI().toast('Photo choisie : elle sera appliquée à l’enregistrement.');
    });
    $('input[name="image"]')?.addEventListener('change', e => { if (e.target.files.length) idInput.value = ''; });
  }
  const galleryBtn = $('[data-media-pick-gallery]');
  if (galleryBtn) {
    const box = $('[data-media-gallery-picked]'), picked = new Map();
    const render = () => {
      box.innerHTML = [...picked.values()].map(f => `<li><img src="${esc(f.url)}" alt=""><input type="hidden" name="gallery_media_ids[]" value="${f.id}"><button type="button" data-unpick="${f.id}" aria-label="Retirer">×</button></li>`).join('');
    };
    galleryBtn.addEventListener('click', async () => {
      const files = await pick({ multiple: true, title: 'Photos secondaires' });
      if (!files) return;
      files.forEach(f => picked.set(f.id, f));
      render();
    });
    box.addEventListener('click', e => { const b = e.target.closest('[data-unpick]'); if (b) { picked.delete(Number(b.dataset.unpick)); render(); } });
  }

  /* ---------- page Médiathèque ---------- */
  const root = $('[data-media]');
  if (!root) return;
  const urls = JSON.parse(root.dataset.urls);
  const maxMb = Number(root.dataset.maxMb) || 8;
  const compression = JSON.parse(root.dataset.compression || '{}');
  const folderId = root.dataset.folder || '';
  const fill = (tpl, id) => tpl.replace('__id__', id);
  const go = (message, type = 'success') => { UI().flashNext(message, type); location.reload(); };

  /* envoi de fichiers */
  const input = $('[data-media-input]', root), drop = $('[data-media-drop]', root);
  /* Réduit la photo dans le navigateur avant l'envoi : une photo de téléphone de 6 Mo passe ainsi sous la limite du serveur. */
  async function shrink(file) {
    if (!compression.enabled || !/^image\/(jpeg|png|webp)$/.test(file.type) || !window.createImageBitmap) return file;
    try {
      const bmp = await createImageBitmap(file);
      const ratio = Math.min(1, compression.dimension / Math.max(bmp.width, bmp.height));
      if (ratio === 1 && file.size < 400 * 1024) { bmp.close?.(); return file; }
      const canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(bmp.width * ratio)); canvas.height = Math.max(1, Math.round(bmp.height * ratio));
      canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
      bmp.close?.();
      const type = compression.webp ? 'image/webp' : (file.type === 'image/png' ? 'image/png' : 'image/jpeg');
      const blob = await new Promise(resolve => canvas.toBlob(resolve, type, compression.quality / 100));
      if (!blob || blob.size >= file.size) return file;
      const ext = { 'image/webp': 'webp', 'image/png': 'png', 'image/jpeg': 'jpg' }[blob.type] || 'jpg';
      return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.' + ext, { type: blob.type });
    } catch (e) { return file; }
  }

  async function upload(list) {
    const picked = [...list]; if (!picked.length) return;
    Swal.fire({ title: 'Préparation et compression…', text: `${picked.length} fichier(s)`, allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    const files = [];
    for (const f of picked) files.push(await shrink(f));
    Swal.close();
    const big = files.find(f => f.size > maxMb * 1024 * 1024);
    if (big) { UI().errors(`« ${big.name} » pèse ${(big.size / 1048576).toFixed(1).replace('.', ',')} Mo : la limite est de ${maxMb} Mo.`); return; }
    const form = new FormData();
    files.forEach(f => form.append('files[]', f));
    if (folderId) form.append('folder_id', folderId);
    Swal.fire({ title: 'Envoi et compression…', text: `${files.length} fichier(s)`, allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    let r;
    try { r = await send(urls.upload, 'POST', form); } catch (e) { Swal.close(); UI().errors('Connexion impossible. Vérifiez votre réseau.'); return; }
    Swal.close();
    const problems = r.data.errors || [];
    if (!r.ok && !r.data.files?.length) { UI().errors(problems.length ? problems : (r.data.message || (r.data.errors && Object.values(r.data.errors)[0]?.[0]) || 'Envoi impossible (fichier trop lourd pour le serveur ?).')); return; }
    if (problems.length) await UI().errors(problems, 'Certains fichiers n’ont pas été ajoutés');
    go(r.data.message);
  }
  $$('[data-media-upload-btn]').forEach(b => b.addEventListener('click', () => input.click()));
  drop?.addEventListener('click', () => input.click());
  drop?.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
  input?.addEventListener('change', () => { upload(input.files); input.value = ''; });
  ['dragenter', 'dragover'].forEach(ev => drop?.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'drop'].forEach(ev => drop?.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('is-over'); }));
  drop?.addEventListener('drop', e => upload(e.dataTransfer.files));

  /* dossiers */
  const nameField = [{ name: 'name', label: 'Nom du dossier', type: 'text', required: true, placeholder: 'Ex. Lessives, Promotions été…' }];
  $$('[data-media-new-folder]').forEach(b => b.addEventListener('click', async () => {
    const r = await UI().formModal({ title: 'Nouveau dossier', fields: nameField, url: urls.folders, submitText: 'Créer', width: 480 });
    if (r) { UI().flashNext(r.message); location.href = urls.index + '?folder=' + r.id; }
  }));
  $('[data-media-rename-folder]')?.addEventListener('click', async e => {
    const { id, name } = e.currentTarget.dataset;
    const r = await UI().formModal({ title: 'Renommer le dossier', fields: nameField, values: { name }, url: fill(urls.folder, id), method: 'PUT', submitText: 'Renommer', width: 480 });
    if (r) go(r.message);
  });
  $('[data-media-delete-folder]')?.addEventListener('click', async e => {
    const { id, name, count } = e.currentTarget.dataset;
    const ok = await UI().confirm({ title: `Supprimer « ${name} » ?`, text: Number(count) ? `Les ${count} fichier(s) ne sont pas supprimés : ils restent dans la médiathèque, hors dossier.` : 'Le dossier est vide.', confirmText: 'Supprimer le dossier', danger: true });
    if (!ok) return;
    const r = await send(fill(urls.folder, id), 'DELETE');
    if (!r.ok) return UI().errors(r.data.message || 'Suppression impossible.');
    UI().flashNext(r.data.message); location.href = urls.index;
  });

  /* optimisation en lot */
  $('[data-media-optimize]', root)?.addEventListener('click', async e => {
    const ok = await UI().confirm({ title: 'Optimiser les images ?', text: 'Les images de cette vue sont recompressées avec vos réglages actuels (taille maximale et qualité). Leur format ne change pas.', confirmText: 'Optimiser', icon: 'question' });
    if (!ok) return;
    Swal.fire({ title: 'Optimisation…', allowOutsideClick: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
    const r = await send(urls.optimize, 'POST', { folder: e.currentTarget.dataset.folder || 'all' });
    Swal.close();
    if (!r.ok) return UI().errors(r.data.message || 'Optimisation impossible.');
    go(r.data.message);
  });

  /* fichiers */
  root.addEventListener('click', async e => {
    const card = e.target.closest('.mm-card'); if (!card) return;
    const file = JSON.parse(card.dataset.file);
    if (e.target.closest('[data-media-copy]')) {
      if (await UI().copy(file.url)) UI().toast('Lien copié dans le presse-papiers.');
    } else if (e.target.closest('[data-media-rename]')) {
      const r = await UI().formModal({ title: 'Renommer le fichier', fields: [{ name: 'name', label: 'Nom', type: 'text', required: true }], values: { name: file.name }, url: fill(urls.file, file.id), method: 'PUT', submitText: 'Renommer', width: 480 });
      if (r) go(r.message);
    } else if (e.target.closest('[data-media-move]')) {
      const list = await (await fetch(urls.index + '/browse?folder=none&page=99999', { headers: { Accept: 'application/json' } })).json().catch(() => ({ folders: { folders: [] } }));
      const options = { '': 'Sans dossier', ...Object.fromEntries((list.folders?.folders || []).map(f => [f.id, f.name])) };
      const r = await UI().formModal({ title: 'Déplacer vers un dossier', fields: [{ name: 'media_folder_id', label: 'Dossier', type: 'select', options }], values: { media_folder_id: file.folder ?? '' }, url: fill(urls.file, file.id), method: 'PUT', submitText: 'Déplacer', width: 480 });
      if (r) go(r.message);
    } else if (e.target.closest('[data-media-delete]')) {
      const ok = await UI().confirm({ title: `Supprimer « ${file.name} » ?`, text: file.used ? `Ce fichier est utilisé par ${file.used} produit(s) : la suppression sera refusée.` : 'Le fichier est supprimé définitivement.', confirmText: 'Supprimer', danger: true });
      if (!ok) return;
      const r = await send(fill(urls.file, file.id), 'DELETE');
      if (!r.ok) return UI().errors(r.data.message || 'Suppression impossible.');
      go(r.data.message);
    }
  });
})();
