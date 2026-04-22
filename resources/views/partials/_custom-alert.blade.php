{{--
  _custom-alert.blade.php
  ========================
  Custom pop-up alert/confirm system — menggantikan window.alert() & window.confirm().

  Cara pakai di JS:
    showAlert('Pesan success!');
    showAlert('Ada kesalahan.', 'error');
    showConfirm('Yakin ingin menghapus?', function() { /* OK callback */ });
--}}

{{-- Modal backdrop + container --}}
<div id="custom-modal-overlay"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm hidden"
     role="dialog" aria-modal="true" aria-labelledby="custom-modal-title">
  <div id="custom-modal-box"
       class="bg-(--panel) border border-(--border-strong) rounded-2xl shadow-2xl w-full max-w-md p-6 flex flex-col gap-4 transform transition-all duration-200 scale-95 opacity-0"
       style="max-width:420px;">

    {{-- Icon + title --}}
    <div class="flex items-start gap-4">
      <div id="custom-modal-icon-wrap"
           class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 text-xl">
        {{-- icon injected by JS --}}
      </div>
      <div class="flex-1 min-w-0">
        <div id="custom-modal-title" class="font-bold text-base text-(--text) mb-1"></div>
        <div id="custom-modal-msg"   class="text-xs md:text-sm text-(--muted) leading-relaxed"></div>
      </div>
    </div>

    {{-- Actions --}}
    <div id="custom-modal-actions" class="flex gap-3 justify-end pt-1">
      {{-- Injected by JS --}}
    </div>
  </div>
</div>

<script>
  /**
   * Custom modal alert / confirm system.
   * Replaces window.alert() and window.confirm() across the app.
   */
  (function () {
    const overlay = document.getElementById('custom-modal-overlay');
    const box     = document.getElementById('custom-modal-box');
    const iconW   = document.getElementById('custom-modal-icon-wrap');
    const titleEl = document.getElementById('custom-modal-title');
    const msgEl   = document.getElementById('custom-modal-msg');
    const actions = document.getElementById('custom-modal-actions');

    function openModal() {
      overlay.classList.remove('hidden');
      requestAnimationFrame(() => {
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
      });
    }

    function closeModal() {
      box.classList.remove('scale-100', 'opacity-100');
      box.classList.add('scale-95', 'opacity-0');
      setTimeout(() => overlay.classList.add('hidden'), 180);
    }

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
    });

    const styles = {
      success: {
        iconClass: 'bg-emerald-500/10 text-emerald-500',
        icon: '<i class="bi bi-check-circle-fill"></i>',
        title: 'Berhasil',
      },
      error: {
        iconClass: 'bg-red-500/10 text-red-500',
        icon: '<i class="bi bi-exclamation-circle-fill"></i>',
        title: 'Perhatian',
      },
      warning: {
        iconClass: 'bg-amber-500/10 text-amber-500',
        icon: '<i class="bi bi-exclamation-triangle-fill"></i>',
        title: 'Konfirmasi',
      },
      info: {
        iconClass: 'bg-blue-500/10 text-blue-500',
        icon: '<i class="bi bi-info-circle-fill"></i>',
        title: 'Informasi',
      },
    };

    /**
     * showAlert(message, type='info', title=null)
     * Displays an informational modal (single OK button).
     */
    window.showAlert = function (message, type, title) {
      type = type || 'info';
      const s = styles[type] || styles.info;
      iconW.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 text-xl ' + s.iconClass;
      iconW.innerHTML = s.icon;
      titleEl.textContent = title || s.title;
      msgEl.textContent = message;
      actions.innerHTML = `
        <button onclick="document.getElementById('custom-modal-overlay').classList.add('hidden')"
          class="px-5 py-2 bg-(--brand) text-white rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
          OK
        </button>`;
      openModal();
    };

    /**
     * showConfirm(message, onConfirm, title='Konfirmasi', type='warning')
     * Displays a confirmation modal with OK + Cancel buttons.
     * onConfirm is called only when user clicks the confirm button.
     */
    window.showConfirm = function (message, onConfirm, title, type, yesText, noText) {
      type = type || 'warning';
      title = title || 'Konfirmasi';
      const s = styles[type] || styles.warning;
      iconW.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 text-xl ' + s.iconClass;
      iconW.innerHTML = s.icon;
      titleEl.textContent = title;
      msgEl.innerHTML = message;
      actions.innerHTML = `
        <button id="cm-cancel-btn"
          class="px-5 py-2 border border-(--border-strong) text-(--text) rounded-xl text-sm font-semibold hover:bg-white/5 transition-colors">
          ${noText || 'Batal'}
        </button>
        <button id="cm-confirm-btn"
          class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition-colors">
          ${yesText || 'Ya, Lanjutkan'}
        </button>`;

      document.getElementById('cm-cancel-btn').onclick = closeModal;
      document.getElementById('cm-confirm-btn').onclick = function () {
        closeModal();
        if (typeof onConfirm === 'function') onConfirm();
      };
      openModal();
    };
  })();
</script>
