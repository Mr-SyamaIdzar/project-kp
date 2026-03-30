@extends('layouts.opd')

@php
  $title = 'Isi Lembar Kerja Evaluasi';
  $header = 'Isi Lembar Kerja Evaluasi (LKE)';
  $subheader = 'Isi data umum sekali, lalu lengkapi setiap indikator sesuai jumlah indikator yang tersedia.';
  $canFillDataUmum = (bool)($canFillDataUmum ?? true);
  $canFillIndikator = (bool)($canFillIndikator ?? true);
  $accessBlocked = (bool)($accessBlocked ?? false);
  $accessBlockReason = $accessBlockReason ?? null;
  $readOnlyAll = !$canFillIndikator;
  $isActivityLocked = (bool)($isActivityLocked ?? false);
  $dataUmumLocked = !$canFillDataUmum || $readOnlyAll || $isActivityLocked;
  $indikatorLocked = $readOnlyAll || $accessBlocked;
  $initialUmumComplete = $canFillDataUmum
    ? (trim((string)($prefillUmum['nama_kegiatan'] ?? '')) !== ''
      && trim((string)($prefillUmum['nomor_rekomendasi'] ?? '')) !== ''
      && trim((string)($prefillUmum['tahun_id'] ?? '')) !== '')
    : (trim((string)($prefillUmum['tahun_id'] ?? '')) !== '');
  $indikatorInputLocked = $indikatorLocked || !$initialUmumComplete;

  /*
    Dokumentasi (OPD Isi LKE):
    - Halaman ini merender UI, sedangkan seluruh interaksi (accordion, autosave, upload, finalisasi)
      ada di `resources/js/opd/lke-create.js`.
    - Blade mengirim "kontrak config" melalui `window.LKE_CREATE_CONFIG` agar JS tidak perlu hardcode URL/CSRF.
    - `indikatorInputLocked` menjaga agar user wajib isi Data Umum (tahun/nama kegiatan/no rekomendasi) sebelum input indikator.
    - Jika BPS sudah finalisasi paket (`is_locked_bps=1`), controller akan memblok semua endpoint write, dan UI menampilkan alasan blok.
  */

  // Config JSON untuk `resources/js/opd/lke-create.js` (disimpan sebagai HTML attribute; JS hanya parse).
  $lkeCreateConfigJson = json_encode([
    'autosaveUrl' => route('opd.lke.autosave'),
    'uploadUrl' => route('opd.lke.upload'),
    'filesUrl' => url('opd/lke/files'),
    'finalizeUrl' => route('opd.lke.finalize'),
    'finalizeAllUrl' => route('opd.lke.finalizeAll'),
    'csrfToken' => csrf_token(),
    'serverDrafts' => $draftMap ?? [],
    'selectedTahun' => $tahunId ?? null,
    'canFillDataUmum' => $canFillDataUmum,
    'canFillIndikator' => $canFillIndikator,
    'accessBlocked' => $accessBlocked,
    'accessBlockReason' => $accessBlockReason,
    'initialUmum' => $prefillUmum ?? [],
    'initialUmumComplete' => $initialUmumComplete,
    'authUserId' => auth()->id(),
    'isActivityLocked' => $isActivityLocked,
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@section('content')

<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl text-(--text)">Isi Lembar Kerja Evaluasi (LKE)</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Isi data umum sekali, lalu lengkapi setiap indikator sesuai jumlah indikator yang tersedia.</div>
  </div>
</div>

{{-- DATA UMUM --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden shadow-sm mb-6">
  <div class="bg-black/5 dark:bg-white/5 border-b border-(--border-strong) p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="font-bold text-base md:text-lg text-(--text)">Data Umum</div>
      <div class="text-(--muted) text-[10px] md:text-xs mt-1">Wajib diisi sebelum mengisi indikator.</div>
    </div>
    <div class="flex items-center gap-2">
      @if($isActivityLocked)
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-blue-500/10 text-blue-600 border border-blue-500/30 whitespace-nowrap">
          <i class="bi bi-info-circle"></i> Paket Tahun {{ $prefillUmum['tahun_id'] ? $tahuns->find($prefillUmum['tahun_id'])->tahun : '' }}
        </span>
      @endif
      <span id="umumStatusBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold whitespace-nowrap {{ $indikatorInputLocked ? 'bg-slate-500/10 text-slate-600 border border-slate-500/30' : 'bg-amber-500/10 text-amber-600 border-amber-500/30' }}">
        {{ $indikatorLocked ? 'Terkunci Admin' : ($indikatorInputLocked ? 'Isi Data Umum Dulu' : 'Auto Save Aktif') }}
      </span>
    </div>
  </div>

  <div class="p-5">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
      <div class="md:col-span-3">
        <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Nama Perangkat Daerah</label>
        <input type="text" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm opacity-60 cursor-not-allowed" value="{{ Auth::user()->nama ?? Auth::user()->username }}" readonly>
        <div class="text-[10px] md:text-xs text-(--muted) mt-1">Diambil otomatis.</div>
      </div>

      <div class="md:col-span-4">
        <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Nama Kegiatan <span class="text-red-500">*</span></label>
        <input type="text" id="nama_kegiatan" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all {{ $dataUmumLocked ? 'opacity-60 cursor-not-allowed' : '' }}"
               value="{{ $prefillUmum['nama_kegiatan'] ?? '' }}"
               placeholder="Contoh: Penyusunan SOP ..." maxlength="250"
               {{ $dataUmumLocked ? 'readonly' : '' }}>
        @if($dataUmumLocked)
          <div class="text-[10px] md:text-xs text-(--muted) mt-1">
            @if($isActivityLocked)
              Terkunci karena sudah ada data di tahun ini.
            @else
              Data Umum dikunci oleh admin.
            @endif
          </div>
        @endif
      </div>

      <div class="md:col-span-2">
        <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Tahun Kegiatan <span class="text-red-500">*</span></label>
        <select id="tahun_id" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all {{ ($readOnlyAll || $isActivityLocked) ? 'opacity-60 cursor-not-allowed' : '' }}" {{ ($readOnlyAll || $isActivityLocked) ? 'disabled' : '' }}>
          <option value="">-- pilih --</option>
          @foreach($tahuns as $t)
            <option value="{{ $t->id }}" {{ (string)($prefillUmum['tahun_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>
              {{ $t->tahun }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-3">
        <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Nomor Rekomendasi <span class="text-red-500">*</span></label>
        <input type="text" id="nomor_rekomendasi" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all font-mono {{ $dataUmumLocked ? 'opacity-60 cursor-not-allowed' : '' }}"
               value="{{ $prefillUmum['nomor_rekomendasi'] ?? '' }}"
               placeholder="ex: 001/REK/2026" maxlength="255"
               {{ $dataUmumLocked ? 'readonly' : '' }}>
      </div>
    </div>

    <div class="mt-4 text-[10px] md:text-xs text-(--muted)">
      @if($isActivityLocked)
        <i class="bi bi-exclamation-triangle-fill text-blue-500"></i> Setiap OPD hanya diperbolehkan menginput <b class="text-(--text)">1 Nama Kegiatan</b> per tahun.
      @else
        <i class="bi bi-info-circle"></i> Warna status indikator: <b class="text-(--text)">abu</b> (kosong), <b class="text-amber-500">oranye</b> (progres), <b class="text-emerald-500">hijau</b> (lengkap).
      @endif
    </div>
  </div>
</div>

@if($accessBlocked && !empty($accessBlockReason))
  <div class="bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 rounded-xl p-4 mb-6">
    {{ $accessBlockReason }}
  </div>
@elseif(!$canFillDataUmum && $canFillIndikator)
  <div class="bg-cyan-500/10 border border-cyan-500/30 text-cyan-600 dark:text-cyan-400 rounded-xl p-4 mb-6">
    <i class="bi bi-info-circle me-1"></i> Data Umum dikunci oleh admin. Anda hanya dapat melanjutkan indikator dengan Data Umum dari tahun terpilih.
  </div>
@elseif($isActivityLocked)
  <div class="bg-blue-500/10 border border-blue-500/30 text-blue-600 dark:text-blue-400 rounded-xl p-4 mb-6">
    <i class="bi bi-info-circle me-1"></i> <strong>Pembatasan Tahunan:</strong> Anda sudah mengisi LKE di tahun kalender ini. Data Umum (Nama Kegiatan, Nomor Rekomendasi, dan Tahun LKE) telah dikunci agar konsisten.
  </div>
@endif

{{-- LIST INDIKATOR --}}

<div class="flex items-center justify-between mb-4">
  <div>
    <div class="font-bold text-base md:text-lg text-(--text)">Indikator yang harus diisi</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Jumlah indikator: <b class="text-(--text) font-semibold">{{ $domains->count() }}</b></div>
  </div>
</div>

<div class="relative" id="indikatorLockWrap">
  <div class="space-y-3" id="indikatorAccordion">
  @foreach($domains as $d)
    @php $accId = 'acc'.$d->id; @endphp

    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden shadow-sm transition-all duration-200 indicator-card scroll-mt-header" id="card{{ $d->id }}">
      {{-- HEADER CLICKABLE: toggle manual --}}
      <div class="bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10 border-b border-(--border-strong) p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer transition-colors lke-head-toggle"
           data-target="{{ $accId }}" tabindex="0">
        <div class="flex-1 pointer-events-none">
          <div class="font-semibold text-(--text) text-sm md:text-base mb-1">{{ $d->nama_indikator }}</div>
          <div class="text-[10px] md:text-xs text-(--muted)">
            <b class="text-(--text) font-semibold">{{ $d->kode }}</b> — {{ $d->nama_domain }} — {{ $d->nama_aspek }}
          </div>
        </div>

        <div class="flex items-center gap-2 pointer-events-none">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold whitespace-nowrap bg-slate-500/10 text-slate-600 border border-slate-500/30 badge-stat" id="badge{{ $d->id }}">Kosong</span>
          <button class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-(--border-strong) text-(--text) rounded-lg text-[10px] md:text-xs font-medium pointer-events-auto hover:bg-white/5 transition-all duration-500 btn-toggle-acc min-w-17.5" data-target="{{ $accId }}" type="button">
            <i class="bi bi-chevron-down transition-transform duration-500 inline-block"></i> Buka
          </button>
        </div>
      </div>

      <div id="{{ $accId }}" class="hidden group-expanded overflow-hidden">
        <div class="p-5 border-t border-(--border-strong) bg-(--sidebar-bg)/30" id="body{{ $d->id }}">

          {{-- PILIH KRITERIA --}}
          <div class="mb-5">
            <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-2 uppercase tracking-wide">Pilihan Kriteria <span class="text-red-500">*</span></div>

            <div class="overflow-hidden rounded-xl border border-(--border-strong) w-full block bg-(--panel) shadow-sm">
              <table class="w-full text-xs md:text-sm text-left border-collapse m-0">
                <thead class="bg-black/5 dark:bg-white/5 text-(--muted) font-semibold border-b border-(--border-strong)">
                  <tr>
                    <th class="p-3 w-32 border-r border-(--border-strong) text-center">Tingkat</th>
                    <th class="p-3">Kriteria</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-(--border-strong)">
                  @foreach($d->kriterias as $k)
                    <tr
                        class="kriteria-row transition-colors {{ $indikatorInputLocked ? 'opacity-60 cursor-not-allowed' : 'hover:bg-black/5 dark:hover:bg-white/5 cursor-pointer' }}"
                        data-kp-select-row
                        data-domain-id="{{ (int) $d->id }}"
                        data-kriteria-id="{{ (int) $k->id }}"
                        data-tingkat="{{ (int) $k->tingkat }}"
                        id="row{{ $d->id }}_{{ $k->id }}">
                      <td class="p-3 border-r border-(--border-strong) text-center align-middle">
                        <label class="inline-flex items-center justify-center gap-2 m-0 w-full h-full cursor-pointer pointer-events-none">
                          <input type="radio"
                                 class="indikator-input w-4 h-4 text-(--brand) border-(--border-strong) focus:ring-(--brand) bg-transparent pointer-events-auto"
                                 name="tingkat_domain_{{ $d->id }}"
                                 value="{{ $k->id }}"
                                 id="radio{{ $d->id }}_{{ $k->id }}"
                                 {{ $indikatorInputLocked ? 'disabled' : '' }}
                                 data-kp-tingkat-radio
                                 data-domain-id="{{ (int) $d->id }}"
                                 data-kriteria-id="{{ (int) $k->id }}"
                                 data-tingkat="{{ (int) $k->tingkat }}">
                          <span class="font-bold text-base md:text-lg text-(--text) span-num">{{ $k->tingkat }}</span>
                        </label>
                      </td>
                      <td class="p-3 align-middle text-(--text) td-kriteria">{{ $k->kriteria }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="text-[10px] md:text-xs text-(--muted) mt-2">
              <i class="bi bi-info-circle"></i> Jika memilih tingkat <b class="text-(--text)">1</b>, upload file menjadi <b class="text-(--text)">opsional</b>. Untuk tingkat <b class="text-(--text)">2–5</b>, file <b class="text-red-500">wajib</b>.
            </div>
          </div>

          {{-- PENJELASAN --}}
          <div class="mb-5">
            <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2 uppercase tracking-wide">Penjelasan <span class="text-red-500">*</span></label>
            <textarea class="indikator-input w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all text-xs md:text-sm leading-relaxed"
                      id="penjelasan{{ $d->id }}"
                      data-kp-penjelasan
                      data-domain-id="{{ (int) $d->id }}"
                      rows="3"
                      placeholder="Jelaskan kondisi indikator ini..."
                      {{ $indikatorInputLocked ? 'disabled' : '' }}></textarea>
            <div class="text-[10px] md:text-xs text-(--muted) mt-1"><i class="bi bi-info-circle"></i> Penjelasan wajib diisi untuk dianggap lengkap.</div>
          </div>

          {{-- UPLOAD FILE --}}
          <div class="mb-4 bg-black/5 dark:bg-white/5 border border-(--border-strong) rounded-xl p-4">
            <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2 uppercase tracking-wide">Upload Bukti Dukung</label>

            <div class="relative inline-block w-full">
                <input type="file"
                       class="indikator-input absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       id="file{{ $d->id }}"
                       multiple
                       {{ $indikatorInputLocked ? 'disabled' : '' }}
                       data-kp-files
                       data-domain-id="{{ (int) $d->id }}"
                       title="Pilih file bukti dukung">
                <div class="w-full bg-(--sidebar-bg) border-2 border-dashed border-(--border-strong) hover:border-(--brand) text-(--text) rounded-xl px-4 py-6 flex flex-col items-center justify-center gap-2 transition-all group">
                  <i class="bi bi-cloud-arrow-up text-2xl md:text-3xl text-(--muted) group-hover:text-(--brand) transition-colors"></i>
                  <span class="font-medium text-xs md:text-sm text-(--text)">Klik atau seret file ke sini untuk dipersiapkan</span>
                </div>
            </div>

            <div class="text-[10px] md:text-xs text-(--muted) mt-2">Maksimal ukuran upload adalah <b class="text-(--text)">10MB per file</b>.</div>

            {{-- PREVIEW --}}
            <div class="mt-4" id="preview{{ $d->id }}"></div>

            {{-- INFO --}}
            <div class="mt-2 text-[10px] md:text-xs font-semibold autosave-info" id="fileInfo{{ $d->id }}"></div>
          </div>

          {{-- SAVE INFO --}}
          <div class="mt-4 text-xs md:text-sm autosave-info flex items-center gap-2 bg-black/5 dark:bg-white/5 p-3 rounded-xl border border-(--border-strong)" id="saveInfo{{ $d->id }}">
            <span class="text-(--muted)">Status:</span> <span class="font-bold text-(--text)">Belum tersimpan</span>
          </div>

        </div>
      </div>
    </div>
  @endforeach
  </div>

  @if($domains->count() > 0)
  <div class="absolute inset-0 z-20 rounded-2xl bg-slate-500/20 backdrop-blur-sm flex items-center justify-center p-6 {{ $indikatorInputLocked ? 'flex' : 'hidden' }}" id="indikatorOverlay">
    <div class="bg-slate-900 border border-slate-700 text-white rounded-xl px-6 py-4 shadow-xl font-semibold text-base md:text-lg flex items-center justify-center gap-3" id="indikatorOverlayText">
        <i class="bi bi-lock-fill text-lg md:text-xl"></i> <span>Isi data umum dulu</span>
    </div>
  </div>
  @endif
</div>

{{-- ACTION BAR BAWAH --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mt-6 shadow-sm relative">
  <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div class="text-xs md:text-sm">
      <div class="font-bold text-(--text) text-sm md:text-base">Aksi</div>
      <div class="text-(--muted) mt-1">Data tersimpan otomatis (Auto Save). Klik Final/Kumpulkan untuk memfinalkan seluruh LKE.</div>
    </div>

    <div class="flex flex-wrap gap-2 w-full md:w-auto">
      <button type="button" id="btnFinalizeAll" onclick="finalizeAll()" class="px-4 md:px-5 py-2 md:py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-colors flex items-center justify-center gap-2 font-bold disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-emerald-500/20 flex-1 md:flex-auto" {{ $indikatorInputLocked ? 'disabled' : '' }}>
        <i class="bi bi-check2-circle text-base md:text-lg"></i> Final / Kumpulkan
      </button>
    </div>
  </div>
</div>

{{-- Config holder untuk JS (diletakkan di HTML, bukan di @push scripts, agar linter tidak menganggap JSX) --}}
<div id="kpLkeCreateConfig" class="hidden" data-config="{{ $lkeCreateConfigJson }}"></div>

@push('scripts')
  <script>
    /**
     * Kontrak config untuk `resources/js/opd/lke-create.js`.
     * Dibaca dari JSON script tag agar linter tidak menganggap token Blade sebagai syntax JS.
     */
    (function () {
      const el = document.getElementById('kpLkeCreateConfig');
      try { window.LKE_CREATE_CONFIG = JSON.parse(el ? (el.getAttribute('data-config') || '{}') : '{}'); }
      catch (e) { window.LKE_CREATE_CONFIG = {}; }
    })();
  </script>
@vite(['resources/js/opd/lke-create.js'])
@endpush
@endsection
