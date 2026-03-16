@extends('layouts.bps')

@php
  $title = 'Detail Penilaian LKE';
  $header = 'Detail Penilaian LKE';
  $subheader = 'Tampilan indikator untuk penilaian BPS.';
@endphp

@section('content')

<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
  <div>
    <div class="font-semibold text-lg md:text-xl">Detail Penilaian LKE</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Tampilan indikator untuk penilaian BPS.</div>
  </div>
  <a href="{{ route('bps.penilaian.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors text-xs md:text-sm">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-4">
  <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    <div class="md:col-span-4">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">OPD</div>
      <div class="font-semibold text-(--text) text-base md:text-lg">{{ $user->nama ?? $user->username }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs mt-1">username: {{ $user->username }}</div>
    </div>
    <div class="md:col-span-4">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">Tahun</div>
      <div class="font-semibold text-(--text) text-base md:text-lg">{{ $tahun->tahun }}</div>
    </div>
    <div class="md:col-span-4">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">Nomor Rekomendasi</div>
      <div class="font-mono text-xs md:text-sm text-(--text) mt-1 wrap-break-word">{{ $nomorRek }}</div>
    </div>
    <div class="md:col-span-12">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">Nama Kegiatan</div>
      <div class="font-semibold text-(--text) wrap-break-word">{{ $namaKegiatan }}</div>
    </div>
  </div>
</div>

<div class="flex flex-col gap-3">
  @foreach($domains as $d)
    @php
      $accId = 'acc-lke-bps-' . $d->id;
      $lke = $items[$d->id] ?? null;

      $hasK = (bool)($lke?->kriteria_id);
      $hasP = strlen(trim((string)($lke?->penjelasan ?? ''))) > 0;
      $hasF = $lke ? $lke->buktiDukung->count() > 0 : false;
      $isRequested = in_array((int)$d->id, $requestedDomainIds ?? [], true);
      $hasBpsEval  = $lke && $lke->penilaian_bps;

      $tingkat = (int)($lke?->nilai ?? 0);
      $status = 'empty';
      if ($hasK || $hasP || $hasF) $status = 'progress';
      if ($hasK && $hasP) {
        if ($tingkat === 1) $status = 'done';
        else $status = $hasF ? 'done' : 'progress';
      }
    @endphp

    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden transition-all duration-300 shadow-sm">

      {{-- Accordion Header --}}
      <div class="bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10 border-b border-(--border-strong) p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer transition-colors lke-head-toggle"
           data-target="{{ $accId }}" tabindex="0">
        <div class="flex-1 pointer-events-none">
          <div class="font-semibold text-(--text) text-sm md:text-base mb-1">{{ $d->nama_indikator }}</div>
          <div class="text-[10px] md:text-xs text-(--muted)">
            <b class="text-(--text) font-semibold">{{ $d->kode }}</b> — {{ $d->nama_domain }} — {{ $d->nama_aspek }}
          </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap md:flex-nowrap shrink-0 pointer-events-none">

          @if($hasBpsEval)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-(--brand)/10 border border-(--brand)/30 text-(--brand) whitespace-nowrap">
              <i class="bi bi-shield-check"></i> Dinilai
            </span>
          @endif

          @if($isRequested)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-500 whitespace-nowrap">
              <i class="bi bi-arrow-return-left"></i> Perlu Revisi
            </span>
          @endif

          @if($status === 'done')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 whitespace-nowrap badge-stat" id="badge{{ $d->id }}">Lengkap</span>
          @elseif($status === 'progress')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-500 whitespace-nowrap badge-stat" id="badge{{ $d->id }}">Progres</span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-slate-500/10 border border-slate-500/30 text-(--muted) whitespace-nowrap badge-stat" id="badge{{ $d->id }}">Kosong</span>
          @endif

          <button class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-(--border-strong) text-(--text) rounded-lg text-[10px] md:text-xs font-medium pointer-events-auto hover:bg-white/5 transition-all duration-500 btn-toggle-acc min-w-17.5" data-target="{{ $accId }}" type="button">
            <i class="bi bi-chevron-down transition-transform duration-500 inline-block"></i> Buka
          </button>
        </div>
      </div>

      {{-- Accordion Body --}}
      <div id="{{ $accId }}" class="hidden group-expanded transition-all duration-300 border-t border-(--border-strong)">
        <div class="p-5 md:p-6 space-y-6 bg-black/5">

          {{-- Kriteria --}}
          <div>
            <div class="text-xs md:text-sm font-semibold text-(--muted) mb-3">Pilihan Kriteria</div>
            <div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-xl">
              <table class="w-full text-(--text) border-collapse text-xs md:text-sm">
                <thead>
                  <tr class="border-b border-(--border-strong) bg-black/5 text-left font-semibold text-(--muted)">
                    <th class="p-3 w-28">Tingkat</th>
                    <th class="p-3">Kriteria</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-(--border-strong)">
                  @foreach($d->kriterias as $k)
                    @php $active = ($lke && (int)$lke->kriteria_id === (int)$k->id); @endphp
                    <tr class="{{ $active ? 'bg-(--brand)/10' : 'hover:bg-black/5' }} transition-colors">
                      <td class="p-3">
                        <div class="flex items-center gap-3">
                          <input type="radio" disabled {{ $active ? 'checked' : '' }}
                            class="w-4 h-4 text-(--brand) bg-(--sidebar-bg) border-(--border-strong) focus:ring-(--brand) focus:ring-2 disabled:opacity-50">
                          <span class="font-bold">T{{ $k->tingkat }}</span>
                        </div>
                      </td>
                      <td class="p-3">{{ $k->kriteria }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          {{-- Penjelasan --}}
          <div>
            <div class="text-xs md:text-sm font-semibold text-(--muted) mb-3">Penjelasan</div>
            <div class="bg-black/10 border border-(--border-strong) rounded-xl p-4 text-xs md:text-sm leading-relaxed text-(--text) wrap-break-word w-full overflow-hidden">
              @if($lke && trim((string)$lke->penjelasan) !== '')
                {{ $lke->penjelasan }}
              @else
                <span class="text-(--muted) italic">Belum ada penjelasan.</span>
              @endif
            </div>
          </div>

          {{-- Bukti Dukung --}}
          <div>
            <div class="text-xs md:text-sm font-semibold text-(--muted) mb-3">Bukti Dukung</div>
            @if(!$lke)
              <div class="text-(--muted) text-xs md:text-sm italic">Belum ada data LKE untuk indikator ini.</div>
            @elseif($lke->buktiDukung->count() === 0)
              <div class="text-(--muted) text-xs md:text-sm italic">Belum ada file.</div>
            @else
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($lke->buktiDukung as $f)
                  @php
                    $url = asset('storage/' . $f->file);
                    $displayName = trim((string)($f->original_name ?? '')) !== '' ? $f->original_name : basename($f->file);
                    $pathName = basename($f->file);
                  @endphp
                  <div class="bg-(--sidebar-bg) border border-(--border-strong) rounded-xl p-4 flex flex-col h-full">
                    <div class="font-semibold text-xs md:text-sm text-(--text) truncate" title="{{ $displayName }}">{{ $displayName }}</div>
                    @if($displayName !== $pathName)
                      <div class="text-[10px] md:text-xs text-(--muted) truncate mt-1" title="{{ $pathName }}">{{ $pathName }}</div>
                    @endif
                    <div class="mt-auto pt-4 flex gap-2">
                      <a class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-(--border-strong) text-(--text) text-[10px] md:text-xs rounded-xl hover:bg-white/5 transition-colors inline-flex items-center gap-2"
                         href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-box-arrow-up-right"></i> Buka
                      </a>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>

          {{-- BPS Evaluation Section --}}
          <div class="border-t border-(--border-strong) pt-6">

            <div class="flex items-center justify-between mb-4">
              <div class="font-semibold text-sm md:text-base text-(--text) flex items-center gap-2">
                <i class="bi bi-shield-check text-(--brand)"></i> Penilaian BPS
              </div>
              @if($lke)
                <span id="save-indicator-{{ $lke->id }}" class="text-[10px] md:text-xs font-normal flex items-center gap-1.5 opacity-0 transition-all duration-300 whitespace-nowrap text-(--muted)">
                  <i class="bi bi-check-circle-fill"></i> Tersimpan
                </span>
              @endif
            </div>

            @if($lke)
              <form id="bps-eval-form-{{ $lke->id }}" action="{{ route('bps.penilaian.evaluasi') }}" method="POST"
                    class="bg-(--panel) border border-(--border-strong) rounded-xl p-4 md:p-5">
                @csrf
                <input type="hidden" name="lke_id" value="{{ $lke->id }}">
                <input type="hidden" name="action" id="action-{{ $lke->id }}" value="{{ $lke->is_revisi_bps ? 'revisi' : 'simpan' }}">

                {{-- Score --}}
                <div class="mb-5">
                  <label class="block text-xs md:text-sm font-semibold text-(--text) mb-3">
                    Nilai Indikator (1–5) <span class="text-red-500">*</span>
                  </label>
                  <div class="flex flex-wrap gap-2 md:gap-3">
                    @for($i = 1; $i <= 5; $i++)
                      @php $isChecked = (int)($lke->penilaian_bps ?? 0) === $i; @endphp
                      <label class="eval-radio-label flex items-center justify-center cursor-pointer w-12 h-12 md:w-14 md:h-14 border rounded-xl transition-all text-center font-bold text-base md:text-lg
                        {{ $isChecked
                          ? 'border-(--brand) bg-(--brand)/10 text-(--brand)'
                          : 'border-(--border-strong) bg-(--sidebar-bg) text-(--muted) hover:border-(--brand)/40 hover:bg-black/5' }}">
                        <input type="radio" name="penilaian_bps" value="{{ $i }}"
                          {{ $isChecked ? 'checked' : '' }}
                          class="sr-only"
                          onchange="autoSaveBps({{ $lke->id }})" required>
                        {{ $i }}
                      </label>
                    @endfor
                  </div>
                </div>

                {{-- Note --}}
                <div class="mb-5">
                  <label id="label-catatan-{{ $lke->id }}" class="block text-xs md:text-sm font-semibold text-(--text) mb-2">
                    Catatan Evaluasi / Alasan Revisi
                    <span class="text-[10px] font-normal text-(--muted) ml-1" id="label-hint-{{ $lke->id }}">(opsional kecuali minta revisi)</span>
                    <span class="text-red-500 hidden" id="label-required-{{ $lke->id }}">* <span class="text-[10px] font-normal text-red-400">Wajib diisi saat minta revisi</span></span>
                  </label>
                  <textarea name="catatan_bps" id="catatan-{{ $lke->id }}" rows="3"
                    class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-(--brand) focus:border-transparent transition-all text-xs md:text-sm leading-relaxed resize-none placeholder:text-(--muted)"
                    placeholder="Tulis catatan atau saran perbaikan..."
                    onkeyup="scheduleAutoSave({{ $lke->id }})"
                    onchange="scheduleAutoSave({{ $lke->id }})">{{ $lke->catatan_bps ?? '' }}</textarea>
                  <div id="catatan-error-{{ $lke->id }}" class="hidden mt-1.5 text-[11px] text-red-500 items-center gap-1">
                    <i class="bi bi-exclamation-circle-fill"></i> Catatan wajib diisi saat meminta revisi.
                  </div>
                </div>

                {{-- Revisi Button --}}
                <div class="pt-4 border-t border-(--border-strong)">
                  <input type="hidden" id="toggle-revisi-{{ $lke->id }}" value="{{ $lke->is_revisi_bps ? '1' : '0' }}">
                  <button type="button"
                    id="revisi-btn-{{ $lke->id }}"
                    onclick="toggleRevisi({{ $lke->id }})"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold text-xs md:text-sm transition-all
                      {{ $lke->is_revisi_bps
                         ? 'bg-amber-500 border-amber-500 text-white hover:bg-amber-600'
                         : 'bg-transparent border-(--border-strong) text-(--text) hover:border-amber-500/50 hover:text-amber-500' }}">
                    <i class="bi bi-arrow-return-left"></i>
                    {{ $lke->is_revisi_bps ? 'Batalkan Permintaan Revisi' : 'Minta Revisi OPD' }}
                  </button>
                  <p class="text-[10px] text-(--muted) mt-2">Catatan akan ditampilkan ke OPD saat revisi diaktifkan.</p>
                </div>

              </form>
            @else
              <div class="bg-blue-500/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 rounded-xl p-4 text-xs md:text-sm flex items-start gap-3">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0"></i>
                <div>
                  <div class="font-semibold mb-1">Menunggu OPD</div>
                  OPD belum mengisi draf apa pun untuk indikator ini, sehingga belum dapat diberikan penilaian.
                </div>
              </div>
            @endif
          </div>

        </div>
      </div>
    </div>
  @endforeach
</div>

<script>
  const saveTimers = {};

  function closeAccordion(id) {
    const el  = document.getElementById(id);
    const btn = document.querySelector(`[data-target="${id}"].btn-toggle-acc`);
    if (!el || el.classList.contains('hidden')) return;
    el.classList.add('hidden');
    if (btn) {
      const ico = btn.querySelector('i');
      if (ico) ico.style.transform = '';
      btn.innerHTML = btn.innerHTML.replace(/Tutup/, 'Buka');
    }
  }

  function openAccordion(id) {
    const el  = document.getElementById(id);
    const btn = document.querySelector(`[data-target="${id}"].btn-toggle-acc`);
    if (!el) return;
    el.classList.remove('hidden');
    if (btn) {
      const ico = btn.querySelector('i');
      if (ico) ico.style.transform = 'rotate(180deg)';
      btn.innerHTML = btn.innerHTML.replace(/Buka/, 'Tutup');
    }
    // Scroll ke header accordion yang baru dibuka
    const header = document.querySelector(`.lke-head-toggle[data-target="${id}"]`);
    if (header) {
      setTimeout(() => {
        header.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 50);
    }
  }

  function toggleAccordion(id, btn) {
    const el = document.getElementById(id);
    const isHidden = el ? el.classList.contains('hidden') : true;

    // Tutup semua accordion lain terlebih dahulu
    document.querySelectorAll('.group-expanded').forEach(panel => {
      if (panel.id && panel.id !== id) {
        closeAccordion(panel.id);
      }
    });

    if (isHidden) {
      openAccordion(id);
    } else {
      closeAccordion(id);
    }
  }

  // Pasang event listener ke semua header dan tombol accordion
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.lke-head-toggle').forEach(header => {
      header.addEventListener('click', function (e) {
        if (e.target.closest('.btn-toggle-acc')) return;
        const id  = header.getAttribute('data-target');
        const btn = header.querySelector('.btn-toggle-acc');
        toggleAccordion(id, btn);
      });
      header.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const id  = header.getAttribute('data-target');
          const btn = header.querySelector('.btn-toggle-acc');
          toggleAccordion(id, btn);
        }
      });
    });

    document.querySelectorAll('.btn-toggle-acc').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const id = btn.getAttribute('data-target');
        toggleAccordion(id, btn);
      });
    });
  });

  function toggleRevisi(id) {
    const hidden = document.getElementById(`toggle-revisi-${id}`);
    const btn    = document.getElementById(`revisi-btn-${id}`);
    const textarea    = document.getElementById(`catatan-${id}`);
    const labelHint   = document.getElementById(`label-hint-${id}`);
    const labelReq    = document.getElementById(`label-required-${id}`);
    const catatanErr  = document.getElementById(`catatan-error-${id}`);

    const isNowRevisi = hidden.value !== '1';
    hidden.value = isNowRevisi ? '1' : '0';

    document.getElementById(`action-${id}`).value = isNowRevisi ? 'revisi' : 'simpan';

    if (isNowRevisi) {
      btn.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold text-xs md:text-sm transition-all bg-amber-500 border-amber-500 text-white hover:bg-amber-600';
      btn.innerHTML = '<i class="bi bi-arrow-return-left"></i> Batalkan Permintaan Revisi';
      // Aktifkan required & highlight
      if (textarea) {
        textarea.setAttribute('required', 'required');
        textarea.classList.add('border-amber-500', 'focus:ring-amber-400');
        textarea.classList.remove('border-(--border-strong)', 'focus:ring-(--brand)');
      }
      if (labelHint) labelHint.classList.add('hidden');
      if (labelReq)  labelReq.classList.remove('hidden');
    } else {
      btn.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold text-xs md:text-sm transition-all bg-transparent border-(--border-strong) text-(--text) hover:border-amber-500/50 hover:text-amber-500';
      btn.innerHTML = '<i class="bi bi-arrow-return-left"></i> Minta Revisi OPD';
      // Nonaktifkan required & kembalikan style
      if (textarea) {
        textarea.removeAttribute('required');
        textarea.classList.remove('border-amber-500', 'focus:ring-amber-400', 'border-red-500', 'focus:ring-red-400');
        textarea.classList.add('border-(--border-strong)', 'focus:ring-(--brand)');
      }
      if (labelHint) labelHint.classList.remove('hidden');
      if (labelReq)  labelReq.classList.add('hidden');
      if (catatanErr) catatanErr.classList.add('hidden');
    }
    doSave(id);
  }

  function scheduleAutoSave(lkeId) {
    clearTimeout(saveTimers[lkeId]);
    setIndicator(lkeId, 'saving');
    saveTimers[lkeId] = setTimeout(() => doSave(lkeId), 900);
  }

  function autoSaveBps(lkeId) {
    const form = document.getElementById(`bps-eval-form-${lkeId}`);
    form.querySelectorAll('input[type="radio"]').forEach(radio => {
      const lbl = radio.closest('label');
      if (radio.checked) {
        lbl.classList.add('border-(--brand)', 'bg-(--brand)/10', 'text-(--brand)');
        lbl.classList.remove('border-(--border-strong)', 'bg-(--sidebar-bg)', 'text-(--muted)', 'hover:border-(--brand)/40', 'hover:bg-black/5');
      } else {
        lbl.classList.remove('border-(--brand)', 'bg-(--brand)/10', 'text-(--brand)');
        lbl.classList.add('border-(--border-strong)', 'bg-(--sidebar-bg)', 'text-(--muted)', 'hover:border-(--brand)/40', 'hover:bg-black/5');
      }
    });
    clearTimeout(saveTimers[lkeId]);
    setIndicator(lkeId, 'saving');
    saveTimers[lkeId] = setTimeout(() => doSave(lkeId), 400);
  }

  function doSave(lkeId) {
    const form     = document.getElementById(`bps-eval-form-${lkeId}`);
    const formData = new FormData(form);
    const action   = formData.get('action');
    const nilai    = formData.get('penilaian_bps');
    const catatan  = (formData.get('catatan_bps') ?? '').trim();
    const textarea    = document.getElementById(`catatan-${lkeId}`);
    const catatanErr  = document.getElementById(`catatan-error-${lkeId}`);

    if (!nilai) {
      setIndicator(lkeId, 'error', 'Pilih nilai 1–5 terlebih dahulu');
      return;
    }
    if (action === 'revisi' && catatan === '') {
      // Tampilkan error visual pada textarea catatan
      if (textarea) {
        textarea.classList.add('border-red-500', 'focus:ring-red-400');
        textarea.classList.remove('border-amber-500', 'border-(--border-strong)');
        textarea.focus();
        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      if (catatanErr) catatanErr.classList.remove('hidden');
      setIndicator(lkeId, 'error', 'Catatan wajib saat minta revisi');
      return;
    } else if (textarea && catatanErr) {
      // Bersihkan error jika ada isi
      if (catatan !== '' && action === 'revisi') {
        textarea.classList.remove('border-red-500', 'focus:ring-red-400');
        textarea.classList.add('border-amber-500');
      }
      catatanErr.classList.add('hidden');
    }

    fetch(form.getAttribute('action'), {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        setIndicator(lkeId, 'saved');
        setTimeout(() => setIndicator(lkeId, 'hide'), 3000);
      } else {
        setIndicator(lkeId, 'error', 'Gagal disimpan');
      }
    })
    .catch(() => setIndicator(lkeId, 'error', 'Koneksi gagal'));
  }

  function setIndicator(lkeId, state, msg) {
    const el = document.getElementById(`save-indicator-${lkeId}`);
    if (!el) return;
    el.className = 'text-[10px] md:text-xs font-normal flex items-center gap-1.5 transition-all duration-300 whitespace-nowrap';
    if (state === 'hide')   { el.classList.add('opacity-0'); return; }
    el.classList.add('opacity-100');
    if (state === 'saving') { el.classList.add('text-(--muted)');  el.innerHTML = '<i class="bi bi-arrow-repeat"></i> Menyimpan...'; }
    if (state === 'saved')  { el.classList.add('text-emerald-500');     el.innerHTML = '<i class="bi bi-check-circle-fill"></i> Tersimpan'; }
    if (state === 'error')  { el.classList.add('text-red-500');         el.innerHTML = `<i class="bi bi-exclamation-circle-fill"></i> ${msg ?? 'Error'}`; }
  }
</script>
@endsection
