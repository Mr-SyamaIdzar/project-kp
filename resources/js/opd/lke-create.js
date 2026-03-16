/**
 * LKE Create (OPD Isi Lembar Kerja Evaluasi) — logic dipisah dari Blade.
 * Config harus sudah di-set di window.LKE_CREATE_CONFIG sebelum script ini dimuat.
 */
(function () {
  const C = window.LKE_CREATE_CONFIG || {};
  const AUTOSAVE_URL = C.autosaveUrl || '';
  const UPLOAD_URL = C.uploadUrl || '';
  const FILES_URL = C.filesUrl || '';
  const FINALIZE_URL = C.finalizeUrl || '';
  const FINALIZE_ALL_URL = C.finalizeAllUrl || '';
  const CSRF_TOKEN = C.csrfToken || '';
  const SERVER_DRAFTS = C.serverDrafts || {};
  const SELECTED_TAHUN = C.selectedTahun ?? null;
  const CAN_FILL_DATA_UMUM = !!C.canFillDataUmum;
  const CAN_FILL_INDIKATOR = !!C.canFillIndikator;
  const ACCESS_BLOCKED = !!C.accessBlocked;
  const ACCESS_BLOCK_REASON = C.accessBlockReason || null;
  const INITIAL_UMUM = C.initialUmum || {};
  const INITIAL_UMUM_COMPLETE = !!C.initialUmumComplete;
  const AUTH_USER_ID = String(C.authUserId ?? '');

  function slugifyKey(value) {
    return (value || '')
      .toString()
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '') || 'none';
  }

  const state = {};
  const timers = {};
  const selectedFiles = {};
  const MAX_UPLOAD_FILE_SIZE = 10 * 1024 * 1024; // 10MB

  function buildLocalStorageKey() {
    const umum = getUmum();
    return `lke_draft_user_${AUTH_USER_ID}_tahun_${umum.tahun_id || SELECTED_TAHUN || 'none'}_kegiatan_${slugifyKey(umum.nama_kegiatan)}_rek_${slugifyKey(umum.nomor_rekomendasi)}`;
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  const toast = (typeof window.showToast === 'function')
    ? window.showToast
    : (message) => alert(message);

  function getUmum() {
    const nama = document.getElementById('nama_kegiatan');
    const tahun = document.getElementById('tahun_id');
    const nomor = document.getElementById('nomor_rekomendasi');
    return {
      nama_kegiatan: nama ? nama.value.trim() : '',
      tahun_id: tahun ? tahun.value : '',
      nomor_rekomendasi: nomor ? nomor.value.trim() : '',
    };
  }

  function isDataUmumComplete() {
    const umum = getUmum();
    if (CAN_FILL_DATA_UMUM) {
      return !!(umum.nama_kegiatan && umum.tahun_id && umum.nomor_rekomendasi);
    }
    return !!umum.tahun_id;
  }

  function guardIndicatorAccess(show = true) {
    if (!CAN_FILL_INDIKATOR || ACCESS_BLOCKED) {
      if (show) toast(ACCESS_BLOCK_REASON || 'Pengisian indikator dinonaktifkan oleh admin.', 'error');
      return false;
    }
    if (!isDataUmumComplete()) {
      if (show) toast('Isi Data Umum terlebih dahulu sampai lengkap.', 'error');
      return false;
    }
    return true;
  }

  function setIndicatorDisabled(disabled) {
    document.querySelectorAll('.indikator-input').forEach((el) => {
      el.disabled = !!disabled;
    });
  }

  function setAutosaveInfoVisible(visible) {
    document.querySelectorAll('.autosave-info').forEach((el) => {
      el.style.display = visible ? 'flex' : 'none';
    });
  }

  function syncIndicatorAccessUi(showLockToast = false) {
    const blockedByAdmin = !CAN_FILL_INDIKATOR || ACCESS_BLOCKED;
    const blockedByUmum = !isDataUmumComplete();
    const locked = blockedByAdmin || blockedByUmum;

    setIndicatorDisabled(locked);

    const btnSave = document.getElementById('btnSaveAll');
    const btnFinal = document.getElementById('btnFinalizeAll');
    if (btnSave) btnSave.disabled = locked;
    if (btnFinal) btnFinal.disabled = locked;

    const badge = document.getElementById('umumStatusBadge');
    if (badge) {
      badge.className = `inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${locked ? 'bg-slate-500/10 text-slate-600 border border-slate-500/30' : 'bg-amber-500/10 text-amber-600 border border-amber-500/30'}`;
      if (blockedByAdmin) {
        badge.textContent = 'Terkunci Admin';
      } else if (blockedByUmum) {
        badge.textContent = 'Isi Data Umum Dulu';
      } else {
        badge.textContent = 'Auto Save Aktif';
      }
    }

    setAutosaveInfoVisible(!locked);

    const overlay = document.getElementById('indikatorOverlay');
    const overlayText = document.getElementById('indikatorOverlayText');
    if (overlay) {
      if (locked) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
      } else {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
      }
    }
    if (overlayText) {
      overlayText.innerHTML = blockedByAdmin
        ? '<i class="bi bi-lock-fill text-lg md:text-xl"></i> <span>' + (escapeHtml(ACCESS_BLOCK_REASON) || 'Pengisian indikator dinonaktifkan admin') + '</span>'
        : '<i class="bi bi-lock-fill text-lg md:text-xl"></i> <span>Isi data umum dulu</span>';
    }

    if (showLockToast && locked && blockedByUmum && !blockedByAdmin) {
      toast('Isi Data Umum dulu agar indikator aktif.', 'error');
    }
  }

  function clearAllLocalDraftKeys() {
    try {
      const prefix = `lke_draft_user_${AUTH_USER_ID}_`;
      const keys = [];
      for (let i = 0; i < localStorage.length; i++) {
        const k = localStorage.key(i);
        if (k && k.startsWith(prefix)) keys.push(k);
      }
      keys.forEach((k) => localStorage.removeItem(k));
    } catch (e) {}
  }

  function allDomainIds() {
    return Array.from(document.querySelectorAll('.indicator-card'))
      .map((el) => el.id.replace('card', ''))
      .map((x) => parseInt(x, 10))
      .filter(Number.isFinite);
  }

  function clearIndicatorsFromOtherPackage() {
    Object.keys(state).forEach((domainIdStr) => delete state[domainIdStr]);

    allDomainIds().forEach((domainId) => {
      const ta = document.getElementById('penjelasan' + domainId);
      if (ta) ta.value = '';

      document.querySelectorAll(`input[name="tingkat_domain_${domainId}"]`).forEach((radio) => {
        radio.checked = false;
      });

      document.querySelectorAll(`#body${domainId} .kriteria-row`).forEach((row) => {
        row.classList.remove('bg-[var(--brand)]/10', 'dark:bg-[var(--brand)]/20');
        const spanNum = row.querySelector('.span-num');
        if (spanNum) spanNum.classList.remove('text-[var(--brand)]');
        const tdKrit = row.querySelector('.td-kriteria');
        if (tdKrit) tdKrit.classList.remove('font-medium');
      });

      const preview = document.getElementById('preview' + domainId);
      if (preview) preview.innerHTML = '';

      const fileInfo = document.getElementById('fileInfo' + domainId);
      if (fileInfo) fileInfo.innerHTML = '';

      const saveInfo = document.getElementById('saveInfo' + domainId);
      if (saveInfo) saveInfo.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="font-bold text-[var(--text)]">Belum tersimpan</span>';

      setBadge(domainId, 'empty');
    });
  }

  function setBadge(domainId, type) {
    const b = document.getElementById('badge' + domainId);
    if (!b) return;
    b.className = `inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap badge-stat`;

    const card = document.getElementById('card' + domainId);

    if (type === 'done') {
      b.classList.add('bg-emerald-500/10', 'text-emerald-600', 'border', 'border-emerald-500/30');
      b.textContent = 'Lengkap';
      if (card) card.style.borderColor = 'rgba(16,185,129,.35)';
    } else if (type === 'progress') {
      b.classList.add('bg-amber-500/10', 'text-amber-600', 'border', 'border-amber-500/30');
      b.textContent = 'Progres';
      if (card) card.style.borderColor = 'rgba(245,158,11,.35)';
    } else {
      b.classList.add('bg-slate-500/10', 'text-slate-600', 'border', 'border-slate-500/30');
      b.textContent = 'Kosong';
      if (card) card.style.borderColor = '';
    }
  }

  function highlightSelectedRow(domainId, kriteriaId) {
    document.querySelectorAll(`#body${domainId} .kriteria-row`).forEach((row) => {
      row.classList.remove('bg-[var(--brand)]/10', 'dark:bg-[var(--brand)]/20');
      const spanNum = row.querySelector('.span-num');
      if (spanNum) spanNum.classList.remove('text-[var(--brand)]');
      const tdKrit = row.querySelector('.td-kriteria');
      if (tdKrit) tdKrit.classList.remove('font-medium');
    });

    const row = document.getElementById(`row${domainId}_${kriteriaId}`);
    if (row) {
      row.classList.add('bg-[var(--brand)]/10', 'dark:bg-[var(--brand)]/20');
      const spanNum = row.querySelector('.span-num');
      if (spanNum) spanNum.classList.add('text-[var(--brand)]');
      const tdKrit = row.querySelector('.td-kriteria');
      if (tdKrit) tdKrit.classList.add('font-medium');
    }
  }

  function saveToLocal() {
    const payload = { umum: getUmum(), state, ts: Date.now() };
    try {
      localStorage.setItem(buildLocalStorageKey(), JSON.stringify(payload));
    } catch (e) {}
  }

  function loadFromLocal() {
    try {
      const raw = localStorage.getItem(buildLocalStorageKey());
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function resetFormAfterFinalize() {
    clearAllLocalDraftKeys();

    if (CAN_FILL_DATA_UMUM) {
      const nama = document.getElementById('nama_kegiatan');
      const tahun = document.getElementById('tahun_id');
      const nomor = document.getElementById('nomor_rekomendasi');
      if (nama) nama.value = '';
      if (tahun) tahun.value = '';
      if (nomor) nomor.value = '';
    }

    Object.keys(timers).forEach((k) => clearTimeout(timers[k]));
    Object.keys(state).forEach((k) => delete state[k]);
    Object.keys(selectedFiles).forEach((k) => delete selectedFiles[k]);

    clearIndicatorsFromOtherPackage();
    saveToLocal();
    syncIndicatorAccessUi(false);
  }

  function debouncedAutosave(domainId, immediate = false) {
    clearTimeout(timers[domainId]);
    timers[domainId] = setTimeout(() => autosave(domainId), immediate ? 50 : 600);
  }

  async function autosave(domainId) {
    if (!guardIndicatorAccess(false)) return false;

    const umum = getUmum();
    const st = state[domainId] || {};

    if (CAN_FILL_DATA_UMUM) {
      if (!umum.nama_kegiatan || !umum.tahun_id || !umum.nomor_rekomendasi) {
        const info = document.getElementById('saveInfo' + domainId);
        if (info) info.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-amber-500 font-bold">Isi data umum dulu</span>';
        return false;
      }
    } else if (!umum.tahun_id) {
      const info = document.getElementById('saveInfo' + domainId);
      if (info) info.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-amber-500 font-bold">Pilih tahun dulu</span>';
      return false;
    }

    const payload = {
      nama_kegiatan: umum.nama_kegiatan,
      tahun_id: umum.tahun_id,
      nomor_rekomendasi: umum.nomor_rekomendasi,
      domain_id: domainId,
      kriteria_id: st.kriteria_id || null,
      penjelasan: (document.getElementById('penjelasan' + domainId) || {}).value || '',
    };

    try {
      const saveInfo = document.getElementById('saveInfo' + domainId);
      if (saveInfo) saveInfo.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-cyan-500 font-bold"><i class="bi bi-arrow-repeat animate-spin inline-block"></i> Menyimpan...</span>';

      const res = await fetch(AUTOSAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(payload),
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.ok) {
        if (saveInfo) saveInfo.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-red-500 font-bold">Gagal tersimpan</span>';
        return false;
      }

      state[domainId] = state[domainId] || {};
      state[domainId].lke_id = data.lke_id;
      state[domainId].hasFiles = data.has_files;
      if (data.tingkat !== undefined && data.tingkat !== null) state[domainId].tingkat = data.tingkat;

      setBadge(domainId, data.progress);

      if (saveInfo) saveInfo.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-emerald-500 font-bold"><i class="bi bi-check-lg"></i> Tersimpan (Draft)</span>';

      saveToLocal();
      return true;
    } catch (e) {
      console.error(e);
      const saveInfo = document.getElementById('saveInfo' + domainId);
      if (saveInfo) saveInfo.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-red-500 font-bold">Error jaringan</span>';
      return false;
    }
  }

  function onSelectTingkat(domainId, kriteriaId, tingkat) {
    if (!guardIndicatorAccess()) return;
    state[domainId] = state[domainId] || {};
    state[domainId].kriteria_id = kriteriaId;
    state[domainId].tingkat = tingkat;

    highlightSelectedRow(domainId, kriteriaId);
    setBadge(domainId, 'progress');

    saveToLocal();
    debouncedAutosave(domainId, true);
  }

  function onPenjelasanInput(domainId) {
    if (!guardIndicatorAccess()) return;
    state[domainId] = state[domainId] || {};
    state[domainId].penjelasan = (document.getElementById('penjelasan' + domainId) || {}).value;

    const hasK = !!state[domainId].kriteria_id;
    const hasP = ((state[domainId].penjelasan || '').trim().length) > 0;
    const hasF = !!state[domainId].hasFiles;

    if (hasK || hasP || hasF) setBadge(domainId, 'progress');
    else setBadge(domainId, 'empty');

    saveToLocal();
    debouncedAutosave(domainId);
  }

  function toggleAccordion(accId) {
    const activeContent = document.getElementById(accId);
    if (!activeContent) return;

    const isHidden = activeContent.classList.contains('hidden');

    document.querySelectorAll('.group-expanded').forEach((el) => {
      if (!el.classList.contains('hidden')) {
        el.style.transform = 'scaleY(0.95)';
        el.style.opacity = '0';
        setTimeout(() => el.classList.add('hidden'), 400);
      }
    });

    document.querySelectorAll('.btn-toggle-acc').forEach((btn) => {
      btn.textContent = 'Buka';
      btn.classList.remove('bg-[var(--brand)]', 'text-white');
      btn.classList.add('text-[var(--text)]', 'bg-transparent');
      const icon = btn.querySelector('i');
      if (icon) icon.style.transform = 'rotate(0deg)';
    });

    if (isHidden) {
      activeContent.classList.remove('hidden');
      activeContent.style.transformOrigin = 'top center';
      activeContent.style.transform = 'scaleY(0.95)';
      activeContent.style.opacity = '0';
      activeContent.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';

      setTimeout(() => {
        activeContent.style.transform = 'scaleY(1)';
        activeContent.style.opacity = '1';

        // Scroll ke bagian paling atas accordion (card indikator) sebagai patokan
        setTimeout(() => {
          const card = activeContent.closest('.indicator-card');
          if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }, 300); // Wait for the expand animation to settle
      }, 10);

      const btn = document.querySelector(`.btn-toggle-acc[data-target="${accId}"]`);
      if (btn) {
        btn.innerHTML = '<i class="bi bi-chevron-up transition-transform duration-500 inline-block"></i> Tutup';
        btn.classList.remove('text-[var(--text)]', 'bg-transparent');
        btn.classList.add('bg-[var(--brand)]', 'text-white', 'border-[var(--brand)]');
      }
    }
  }

  function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  }

  function fileCardHtml(f) {
    const ext = (f.name.split('.').pop() || '').toLowerCase();
    const isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
    const isPdf = ext === 'pdf';

    let thumb = '';
    if (isImg) {
      thumb = `<img src="${f.url}" class="w-12 h-12 object-cover rounded-lg border border-black/10 dark:border-white/10">`;
    } else if (isPdf) {
      thumb = '<div class="w-12 h-12 rounded-lg bg-orange-500/10 flex items-center justify-center font-bold text-orange-600 text-[10px]">PDF</div>';
    } else {
      thumb = '<div class="w-12 h-12 rounded-lg bg-slate-500/10 flex items-center justify-center font-bold text-slate-600 text-[10px]">FILE</div>';
    }

    return `
      <div class="flex items-center gap-3 p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl mb-3">
        ${thumb}
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-[var(--text)] text-xs md:text-sm leading-tight truncate" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
          <a href="${f.url}" target="_blank" class="text-[10px] md:text-xs text-emerald-600 hover:text-emerald-700 hover:underline mt-1 inline-block font-medium"><i class="bi bi-box-arrow-up-right me-1"></i> Buka File</a>
        </div>
      </div>
    `;
  }

  async function refreshFiles(domainId) {
    const lkeId = state[domainId]?.lke_id;
    if (!lkeId) return;

    const res = await fetch(`${FILES_URL}/${lkeId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) return;

    const wrap = document.getElementById('preview' + domainId);
    if (!wrap) return;

    if (!data.files || data.files.length === 0) {
      wrap.innerHTML = '<div class="text-[var(--muted)] text-xs md:text-sm italic">Belum ada file.</div>';
      state[domainId].hasFiles = false;
      return;
    }

    let html = '<div class="font-semibold text-xs md:text-sm mb-2 text-[var(--text)]">File Tersimpan di Server:</div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">';
    html += data.files.map(fileCardHtml).join('');
    html += '</div>';
    wrap.innerHTML = html;

    state[domainId].hasFiles = true;
  }

  function renderPreview(domainId) {
    const wrap = document.getElementById('preview' + domainId);
    const files = selectedFiles[domainId] || [];

    if (!wrap) return;
    if (!files.length) {
      wrap.innerHTML = '';
      return;
    }

    let html = '<div class="font-semibold text-xs md:text-sm mb-2 text-[var(--text)]">File Siap Upload:</div><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mb-4">';

    files.forEach((f, idx) => {
      const isImg = f.type && f.type.startsWith('image/');
      html += `
        <div class="flex items-center gap-3 p-3 bg-white/5 border border-[var(--border-strong)] rounded-xl relative group pr-10">
          <div class="w-12 h-12 rounded-lg bg-[var(--brand)]/10 flex items-center justify-center text-[var(--brand)] shrink-0 overflow-hidden" id="thumb_${domainId}_${idx}">
             ${isImg ? '<img alt="preview" class="w-full h-full object-cover"/>' : '<i class="bi bi-file-earmark-plus text-xl md:text-2xl"></i>'}
          </div>
          <div class="flex-1 min-w-0">
              <div class="text-xs md:text-sm font-semibold text-[var(--text)] truncate" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
              <div class="text-[10px] md:text-xs text-[var(--muted)] mt-0.5">${formatBytes(f.size)} • ${escapeHtml(f.type || 'unknown')}</div>
          </div>
          <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-500/10 transition-colors" aria-label="Hapus" onclick="window.removeSelectedFile(${domainId}, ${idx})">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      `;
    });

    html += `
      <div class="flex gap-2 flex-wrap">
        <button type="button" class="px-3 md:px-4 py-1.5 md:py-2 bg-[var(--brand)] text-white rounded-xl hover:opacity-90 transition-opacity flex items-center gap-2 text-xs md:text-sm font-medium" onclick="window.uploadSelectedFiles(${domainId})">
          <i class="bi bi-upload"></i> Upload File
        </button>
        <button type="button" class="px-3 md:px-4 py-1.5 md:py-2 bg-transparent border border-red-500/50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-colors flex items-center gap-2 text-xs md:text-sm font-medium" onclick="window.clearSelectedFiles(${domainId})">
          <i class="bi bi-trash3"></i> Batalkan Semua
        </button>
      </div>
    `;

    wrap.innerHTML = html;

    files.forEach((f, idx) => {
      if (f.type && f.type.startsWith('image/')) {
        const thumb = document.querySelector(`#thumb_${domainId}_${idx} img`);
        if (thumb) {
          const url = URL.createObjectURL(f);
          thumb.src = url;
          thumb.onload = () => URL.revokeObjectURL(url);
        }
      }
    });
  }

  function onFilesSelected(domainId) {
    if (!guardIndicatorAccess()) return;
    const input = document.getElementById('file' + domainId);
    if (!input || !input.files || input.files.length === 0) return;

    const incoming = Array.from(input.files);
    const validFiles = incoming.filter((f) => f.size <= MAX_UPLOAD_FILE_SIZE);
    const oversized = incoming.filter((f) => f.size > MAX_UPLOAD_FILE_SIZE);

    if (oversized.length) {
      const names = oversized.slice(0, 2).map((f) => f.name).join(', ');
      const suffix = oversized.length > 2 ? ' dan lainnya' : '';
      toast(`File melebihi 10MB: ${names}${suffix}.`, 'error');
      const info = document.getElementById('fileInfo' + domainId);
      if (info) info.innerHTML = '<span class="text-red-500">Maksimal 10MB per file.</span>';
    }

    if (!validFiles.length) {
      input.value = '';
      return;
    }

    const current = selectedFiles[domainId] || [];
    selectedFiles[domainId] = current.concat(validFiles);

    renderPreview(domainId);
    input.value = '';
  }

  function removeSelectedFile(domainId, index) {
    if (!guardIndicatorAccess()) return;
    const files = selectedFiles[domainId] || [];
    files.splice(index, 1);
    selectedFiles[domainId] = files;
    renderPreview(domainId);
  }

  function clearSelectedFiles(domainId) {
    if (!guardIndicatorAccess()) return;
    selectedFiles[domainId] = [];
    renderPreview(domainId);
  }

  async function uploadSelectedFiles(domainId) {
    if (!guardIndicatorAccess()) return;
    const files = selectedFiles[domainId] || [];
    if (!files.length) {
      toast('Pilih file dulu.', 'error');
      return;
    }
    if (files.some((f) => f.size > MAX_UPLOAD_FILE_SIZE)) {
      toast('Ada file yang melebihi 10MB. Hapus dulu file tersebut.', 'error');
      const info = document.getElementById('fileInfo' + domainId);
      if (info) info.innerHTML = '<span class="text-red-500">Maksimal 10MB per file.</span>';
      return;
    }

    const ok = await autosave(domainId);
    if (!ok || !state[domainId]?.lke_id) {
      toast('Lengkapi Data Umum dulu.', 'error');
      return;
    }
    if (!state[domainId]?.kriteria_id) {
      toast('Pilih tingkat minimal sebelum upload.', 'error');
      return;
    }

    const form = new FormData();
    form.append('lke_id', state[domainId].lke_id);
    files.forEach((f) => form.append('files[]', f));

    try {
      const fileInfo = document.getElementById('fileInfo' + domainId);
      if (fileInfo) fileInfo.innerHTML = '<span class="text-cyan-500"><i class="bi bi-arrow-repeat animate-spin inline-block"></i> Uploading...</span>';

      const res = await fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }, body: form });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.ok) {
        if (fileInfo) fileInfo.innerHTML = '<span class="text-red-500">Upload gagal</span>';
        toast(data.message || 'Upload gagal.', 'error');
        return;
      }

      if (fileInfo) fileInfo.innerHTML = '<span class="text-emerald-500"><i class="bi bi-check-circle"></i> Upload berhasil</span>';

      selectedFiles[domainId] = [];
      await refreshFiles(domainId);
      await autosave(domainId);

      toast('File ditambahkan.', 'success');
    } catch (e) {
      console.error(e);
      toast('Upload error jaringan.', 'error');
      const fileInfo = document.getElementById('fileInfo' + domainId);
      if (fileInfo) fileInfo.innerHTML = '<span class="text-red-500">Error jaringan</span>';
    }
  }

  async function saveAll(isSilent = false) {
    if (!guardIndicatorAccess()) return false;
    const ids = allDomainIds();

    const btnSave = document.getElementById('btnSaveAll');
    const originalText = btnSave ? btnSave.innerHTML : '';
    if (btnSave) {
      btnSave.innerHTML = '<i class="bi bi-arrow-repeat animate-spin inline-block"></i> Menyimpan...';
      btnSave.disabled = true;
    }

    if (!isSilent) showLoadingOverlay(`Menyimpan ${ids.length} indikator secara paralel...`);

    const results = await Promise.all(ids.map((id) => autosave(id)));
    const okCount = results.filter(Boolean).length;

    if (btnSave) {
      btnSave.innerHTML = originalText;
      btnSave.disabled = false;
    }

    if (!isSilent) {
      hideLoadingOverlay();
      toast(`Simpan selesai. ${okCount}/${ids.length} indikator tersimpan.`, 'success');
    }
    return true;
  }

  function isDomainComplete(domainId) {
    const st = state[domainId] || {};
    const kriteriaId = st.kriteria_id || null;
    const tingkat = st.tingkat || null;
    const penjelasan = ((document.getElementById('penjelasan' + domainId) || {}).value || '').trim();
    const hasFiles = !!st.hasFiles;

    if (!kriteriaId) return false;
    if (!penjelasan) return false;
    if (!tingkat) return false;

    if (parseInt(tingkat, 10) === 1) return true;
    return hasFiles;
  }

  async function finalizeAll() {
    if (!guardIndicatorAccess()) return;
    const umum = getUmum();
    if (CAN_FILL_DATA_UMUM && (!umum.nama_kegiatan || !umum.tahun_id || !umum.nomor_rekomendasi)) {
      toast('Data Umum wajib diisi dulu sebelum Final.', 'error');
      return;
    }
    if (!umum.tahun_id) {
      toast('Pilih tahun dulu sebelum Final.', 'error');
      return;
    }

    const btnFinal = document.getElementById('btnFinalizeAll');
    const originalText = btnFinal ? btnFinal.innerHTML : '';
    if (btnFinal) {
      btnFinal.innerHTML = '<i class="bi bi-arrow-repeat animate-spin inline-block"></i> Memproses...';
      btnFinal.disabled = true;
    }

    const ids = allDomainIds();
    const notComplete = ids.filter((id) => !isDomainComplete(id));
    if (notComplete.length > 0) {
      toast(`Masih ada ${notComplete.length} indikator belum lengkap.`, 'error');
      if (btnFinal) {
        btnFinal.innerHTML = originalText;
        btnFinal.disabled = false;
      }
      return;
    }

    showLoadingOverlay('Memfinalisasi semua indikator...');

    try {
      const res = await fetch(FINALIZE_ALL_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({
          tahun_id: umum.tahun_id,
          nama_kegiatan: umum.nama_kegiatan,
          nomor_rekomendasi: umum.nomor_rekomendasi,
        }),
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        toast(data.message || 'Gagal memfinalkan. Coba lagi.', 'error');
        if (btnFinal) {
          btnFinal.innerHTML = originalText;
          btnFinal.disabled = false;
        }
        hideLoadingOverlay();
        return;
      }

      updateLoadingText('Selesai!');
      toast('Berhasil Final/Kumpulkan. Siap untuk pengisian berikutnya.', 'success');
    } catch (e) {
      console.error(e);
      toast('Error jaringan saat finalisasi.', 'error');
      if (btnFinal) {
        btnFinal.innerHTML = originalText;
        btnFinal.disabled = false;
      }
      hideLoadingOverlay();
      return;
    }

    if (btnFinal) {
      btnFinal.innerHTML = originalText;
      btnFinal.disabled = false;
    }
    setTimeout(() => {
      hideLoadingOverlay();
      resetFormAfterFinalize();
    }, 1500);
  }

  let loadingOverlayEl = null;
  let loadingTextEl = null;

  function ensureLoadingOverlay() {
    if (loadingOverlayEl) return;
    loadingOverlayEl = document.createElement('div');
    loadingOverlayEl.id = 'globalLoadingOverlay';
    loadingOverlayEl.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm';

    const box = document.createElement('div');
    box.className = 'bg-slate-900/90 text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3';

    const spinner = document.createElement('div');
    spinner.className = 'w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin';

    loadingTextEl = document.createElement('div');
    loadingTextEl.className = 'text-sm font-medium';
    loadingTextEl.textContent = 'Memproses...';

    box.appendChild(spinner);
    box.appendChild(loadingTextEl);
    loadingOverlayEl.appendChild(box);
  }

  function showLoadingOverlay(message = 'Memproses...') {
    ensureLoadingOverlay();
    if (loadingTextEl) loadingTextEl.textContent = message;
    if (!document.body.contains(loadingOverlayEl)) {
      document.body.appendChild(loadingOverlayEl);
    }
  }

  function updateLoadingText(message) {
    if (loadingTextEl) loadingTextEl.textContent = message;
  }

  function hideLoadingOverlay() {
    if (loadingOverlayEl && document.body.contains(loadingOverlayEl)) {
      document.body.removeChild(loadingOverlayEl);
    }
  }

  async function hydrateFromDrafts() {
    if (SERVER_DRAFTS && Object.keys(SERVER_DRAFTS).length) {
      const refreshPromises = [];

      for (const domainIdStr of Object.keys(SERVER_DRAFTS)) {
        const domainId = parseInt(domainIdStr, 10);
        const d = SERVER_DRAFTS[domainIdStr];

        state[domainId] = state[domainId] || {};
        state[domainId].lke_id = d.lke_id;
        state[domainId].kriteria_id = d.kriteria_id;
        state[domainId].tingkat = d.tingkat;
        state[domainId].penjelasan = d.penjelasan;
        state[domainId].hasFiles = d.has_files;

        const ta = document.getElementById('penjelasan' + domainId);
        if (ta) ta.value = d.penjelasan || '';

        if (d.kriteria_id) {
          const radio = document.getElementById(`radio${domainId}_${d.kriteria_id}`);
          if (radio) radio.checked = true;
          highlightSelectedRow(domainId, d.kriteria_id);
        }

        let status = 'empty';
        const hasK = !!d.kriteria_id;
        const hasP = (d.penjelasan || '').trim().length > 0;
        const hasF = !!d.has_files;

        if (hasK || hasP || hasF) status = 'progress';
        if (hasK && hasP) {
          if (parseInt(d.tingkat, 10) === 1) status = 'done';
          else status = hasF ? 'done' : 'progress';
        }
        setBadge(domainId, status);

        const info = document.getElementById('saveInfo' + domainId);
        if (info) info.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-cyan-500 font-bold">Draft dimuat</span>';

        if (d.lke_id) refreshPromises.push(refreshFiles(domainId));
      }

      await Promise.all(refreshPromises);

      saveToLocal();
      syncIndicatorAccessUi(false);
      return;
    }

    const local = loadFromLocal();
    if (!local) return;

    if (local.umum && CAN_FILL_DATA_UMUM) {
      const nama = document.getElementById('nama_kegiatan');
      const nomor = document.getElementById('nomor_rekomendasi');
      if (nama) nama.value = local.umum.nama_kegiatan || '';
      if (nomor) nomor.value = local.umum.nomor_rekomendasi || '';
    }

    if (local.state) {
      for (const domainIdStr of Object.keys(local.state)) {
        const domainId = parseInt(domainIdStr, 10);
        state[domainId] = local.state[domainId];

        const st = state[domainId] || {};
        const ta = document.getElementById('penjelasan' + domainId);
        if (ta) ta.value = st.penjelasan || '';

        if (st.kriteria_id) {
          const radio = document.getElementById(`radio${domainId}_${st.kriteria_id}`);
          if (radio) radio.checked = true;
          highlightSelectedRow(domainId, st.kriteria_id);
        }

        let status = 'empty';
        const hasK = !!st.kriteria_id;
        const hasP = (st.penjelasan || '').trim().length > 0;
        const hasF = !!st.hasFiles;

        if (hasK || hasP || hasF) status = 'progress';
        if (hasK && hasP) {
          if (parseInt(st.tingkat, 10) === 1) status = 'done';
          else status = hasF ? 'done' : 'progress';
        }
        setBadge(domainId, status);

        const info = document.getElementById('saveInfo' + domainId);
        if (info) info.innerHTML = '<span class="text-[var(--muted)]">Status:</span> <span class="text-amber-500 font-bold">Draft lokal dimuat</span>';
      }
    }

    syncIndicatorAccessUi(false);
  }

  function selectRow(domainId, kriteriaId, tingkat) {
    if (!guardIndicatorAccess()) return;
    const radio = document.getElementById(`radio${domainId}_${kriteriaId}`);
    if (radio) {
      radio.checked = true;
      onSelectTingkat(domainId, kriteriaId, tingkat);
    }
  }

  // ——— Event bindings (run when DOM ready) ———
  function init() {
    const tahunEl = document.getElementById('tahun_id');
    if (tahunEl) {
      tahunEl.addEventListener('change', (e) => {
        const val = e.target.value || '';
        const umum = getUmum();
        const url = new URL(window.location.href);
        if (val) url.searchParams.set('tahun_id', val);
        else url.searchParams.delete('tahun_id');
        if (umum.nama_kegiatan) url.searchParams.set('nama_kegiatan', umum.nama_kegiatan);
        else url.searchParams.delete('nama_kegiatan');
        if (umum.nomor_rekomendasi) url.searchParams.set('nomor_rekomendasi', umum.nomor_rekomendasi);
        else url.searchParams.delete('nomor_rekomendasi');
        window.location.href = url.toString();
      });
    }

    ['nama_kegiatan', 'nomor_rekomendasi'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', () => {
          saveToLocal();
          syncIndicatorAccessUi(false);
        });
        el.addEventListener('change', () => {
          syncIndicatorAccessUi(false);
          const umumNow = getUmum();
          const initialNama = (INITIAL_UMUM.nama_kegiatan || '').trim();
          const initialNomor = (INITIAL_UMUM.nomor_rekomendasi || '').trim();
          const packageChanged =
            umumNow.nama_kegiatan !== initialNama || umumNow.nomor_rekomendasi !== initialNomor;
          if (packageChanged) clearIndicatorsFromOtherPackage();
        });
      }
    });

    document.querySelectorAll('.lke-head-toggle').forEach((head) => {
      head.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-toggle-acc');
        if (btn) {
          e.preventDefault();
          toggleAccordion(btn.getAttribute('data-target'));
          return;
        }
        const blocked = e.target.closest('a, input, label, textarea, select');
        if (blocked) return;
        toggleAccordion(head.getAttribute('data-target'));
      });
      head.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleAccordion(head.getAttribute('data-target'));
        }
      });
    });

    window.addEventListener('online', async () => {
      const local = loadFromLocal();
      if (!local || !local.state) return;
      for (const domainIdStr of Object.keys(local.state)) {
        const domainId = parseInt(domainIdStr, 10);
        if (Number.isFinite(domainId)) await autosave(domainId);
      }
      toast('Koneksi kembali online. Draft disinkronkan.', 'success');
    });

    syncIndicatorAccessUi(false);
    if (!CAN_FILL_INDIKATOR || ACCESS_BLOCKED) {
      toast(ACCESS_BLOCK_REASON || 'Pengisian indikator dinonaktifkan oleh admin.', 'error');
    }
    hydrateFromDrafts();
  }

  // Expose for onclick/oninput in Blade
  window.finalizeAll = finalizeAll;
  window.removeSelectedFile = removeSelectedFile;
  window.uploadSelectedFiles = uploadSelectedFiles;
  window.clearSelectedFiles = clearSelectedFiles;
  window.selectRow = selectRow;
  window.onFilesSelected = onFilesSelected;
  window.onPenjelasanInput = onPenjelasanInput;
  window.toggleAccordion = toggleAccordion;
  window.saveAll = saveAll;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
