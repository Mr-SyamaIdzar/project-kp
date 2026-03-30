@extends('layouts.opd')

@php
  $title = 'Detail Riwayat LKE';
  $header = 'Detail Riwayat LKE';
  $subheader = 'Revisi indikator hanya untuk yang ditandai oleh BPS.';
@endphp

@section('content')

{{-- 
  Dokumentasi (OPD Riwayat LKE - Detail Paket):
  - Halaman ini bersifat read-only kecuali untuk indikator yang sedang diminta revisi oleh BPS (`canReviseDomainIds`).
  - Revisi OPD dibatasi: hanya `penjelasan` dan `bukti dukung` yang boleh berubah; tingkat/kriteria tetap mengikuti baseline.
  - Panel histori menampilkan: Sebelum / Revisi 1 / Revisi 2 (jika ada) agar OPD & BPS punya konteks perubahan.
  - Jika BPS sudah finalisasi paket (`isLockedBps=1`), semua aksi revisi dinonaktifkan (UI + server-side di controller).
--}}
<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl text-(--text)">Detail Riwayat LKE</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Revisi hanya pada indikator yang ditandai BPS.</div>
  </div>
  <a href="{{ route('opd.lke.riwayat.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm font-medium">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<div class="bg-black/5 border border-(--border-strong) rounded-2xl p-4 mb-4 dark:bg-(--panel)">
  <div class="grid grid-cols-2 md:grid-cols-12 gap-4">
    <div class="md:col-span-3">
      <div class="text-[10px] md:text-xs uppercase tracking-wide text-(--muted) font-semibold">Tahun</div>
      <div class="text-(--text) font-bold mt-1 wrap-break-word">{{ $tahun->tahun }}</div>
    </div>
    <div class="md:col-span-3">
      <div class="text-[10px] md:text-xs uppercase tracking-wide text-(--muted) font-semibold">Nomor Rekomendasi</div>
      <div class="text-(--text) font-bold mt-1 wrap-break-word font-mono text-xs md:text-sm">{{ $nomorRek }}</div>
    </div>
    <div class="md:col-span-6 col-span-2">
      <div class="text-[10px] md:text-xs uppercase tracking-wide text-(--muted) font-semibold">Nama Kegiatan</div>
      <div class="text-(--text) font-bold mt-1 wrap-break-word">{{ $namaKegiatan }}</div>
    </div>
  </div>
</div>

@if(count($canReviseDomainIds) > 0)
  <div class="bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 rounded-xl p-4 mb-6">
    BPS meminta revisi pada <b class="font-bold">{{ count($canReviseDomainIds) }}</b> indikator.
  </div>
@else
  @if(($isLockedBps ?? false))
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-xl p-4 mb-6 flex items-start gap-3">
      <i class="bi bi-lock-fill text-base md:text-lg mt-0.5 shrink-0"></i>
      <div>
        <div class="font-semibold mb-0.5">Penilaian sudah difinalisasi & dikunci oleh BPS</div>
        OPD tidak dapat mengirim revisi lagi untuk paket ini, walaupun masih ada permintaan revisi yang sebelumnya belum diselesaikan.
      </div>
    </div>
  @else
    <div class="bg-cyan-500/10 border border-cyan-500/30 text-cyan-600 dark:text-cyan-400 rounded-xl p-4 mb-6 flex items-center gap-2">
      <i class="bi bi-info-circle text-base md:text-lg"></i>
      Belum ada indikator yang diminta revisi oleh BPS untuk paket ini.
    </div>
  @endif
@endif

<div class="space-y-3" id="indikatorAccordion">
  @foreach($domains as $d)
    @php
      $accId = 'acc' . $d->id;
      $lke = $items[$d->id] ?? null;
      $beforeLke = $beforeRevisiItems[$d->id] ?? null;
      $revisedReq = $revisedRequestMap[$d->id] ?? null;
      $revisedLke = $revisedReq?->revisedLke;
      $isTargetRevisi = in_array((int)$d->id, $canReviseDomainIds, true);
      $activeRound = (int) (($activeRevisiRoundMap[$d->id] ?? 0) ?: 0);

      $hasK = (bool)($lke?->kriteria_id);
      $hasP = strlen(trim((string)($lke?->penjelasan ?? ''))) > 0;
      $hasF = $lke ? $lke->buktiDukung->count() > 0 : false;
      $tingkat = (int)($lke?->nilai ?? 0);
      $status = 'empty';
      if ($hasK || $hasP || $hasF) $status = 'progress';
      if ($hasK && $hasP) {
        if ($tingkat === 1) $status = 'done';
        else $status = $hasF ? 'done' : 'progress';
      }
    @endphp

    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden shadow-sm transition-all duration-200">
      <div class="bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10 border-b border-(--border-strong) p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 cursor-pointer transition-colors"
           onclick="toggleAccordion('{{ $accId }}')">
        <div class="flex-1">
          <div class="font-semibold text-(--text) text-sm md:text-base mb-1">{{ $d->nama_indikator }}</div>
          <div class="text-[10px] md:text-xs text-(--muted)">
            <b class="text-(--text) font-semibold">{{ $d->kode }}</b> - {{ $d->nama_domain }} - {{ $d->nama_aspek }}
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap md:flex-nowrap">
          @if($isTargetRevisi)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-red-500/10 text-red-600 border border-red-500/30 whitespace-nowrap">
              Perlu Revisi{{ $activeRound ? ' ('.$activeRound.')' : '' }}
            </span>
          @endif
          @if($status === 'done')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-emerald-500/10 text-emerald-600 border border-emerald-500/30 whitespace-nowrap">
              Lengkap
            </span>
          @elseif($status === 'progress')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-amber-500/10 text-amber-600 border border-amber-500/30 whitespace-nowrap">
              Progres
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-slate-500/10 text-slate-600 border border-slate-500/30 whitespace-nowrap">
              Kosong
            </span>
          @endif
          <i class="bi bi-chevron-down text-(--muted) text-xs md:text-sm transition-transform duration-200 accordion-icon" id="icon-{{ $accId }}"></i>
        </div>
      </div>

      <div id="{{ $accId }}" class="hidden group-expanded">
        <div class="p-5 border-t border-(--border-strong)">
          <!-- Data Terakhir -->
          <div class="mb-5">
            <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-2 uppercase tracking-wide">Data Terakhir</div>
            <div class="overflow-hidden rounded-xl border border-(--border-strong) w-full block">
              <table class="w-full text-xs md:text-sm text-left border-collapse">
                <thead class="bg-black/5 dark:bg-white/5 text-(--muted) font-semibold border-b border-(--border-strong)">
                  <tr>
                    <th class="p-3 w-32 border-r border-(--border-strong) text-center">Tingkat</th>
                    <th class="p-3">Kriteria</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-(--border-strong)">
                  @foreach($d->kriterias as $k)
                    @php $active = ($lke && (int)$lke->kriteria_id === (int)$k->id); @endphp
                    <tr class="transition-colors {{ $active ? 'bg-(--brand)/10' : 'bg-(--panel)' }} text-(--text)">
                      <td class="p-3 border-r border-(--border-strong) text-center align-middle">
                        <label class="inline-flex items-center justify-center gap-2 cursor-pointer w-full h-full">
                          <input type="radio" disabled {{ $active ? 'checked' : '' }} class="text-(--brand) focus:ring-(--brand) opacity-70">
                          <span class="font-bold text-base md:text-lg {{ $active ? 'text-(--brand)' : '' }}">{{ $k->tingkat }}</span>
                        </label>
                      </td>
                      <td class="p-3 align-middle {{ $active ? 'font-medium' : '' }}">{{ $k->kriteria }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <!-- Penjelasan Terakhir -->
          <div class="mb-5">
            <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-2 uppercase tracking-wide">Penjelasan Terakhir</div>
            <div class="bg-black/5 dark:bg-white/5 border border-(--border-strong) rounded-xl p-4 text-(--text) text-xs md:text-sm leading-relaxed whitespace-pre-wrap">@if($lke && trim((string)$lke->penjelasan) !== ''){{ $lke->penjelasan }}@else<span class="text-(--muted) italic">Belum ada penjelasan.</span>@endif</div>
          </div>

          <!-- Alasan Revisi dari BPS (per round) -->
          @php
            $alasanR1 = trim((string)($revisiCatatanMap[$d->id][1] ?? ''));
            $alasanR2 = trim((string)($revisiCatatanMap[$d->id][2] ?? ''));
          @endphp
          @if($alasanR1 !== '' || $alasanR2 !== '')
            <div class="mb-5">
              <div class="text-[10px] md:text-xs font-semibold text-amber-600 dark:text-amber-500 mb-2 uppercase tracking-wide flex items-center gap-1.5"><i class="bi bi-chat-square-text"></i> Alasan Revisi dari BPS</div>
              <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 text-amber-800 dark:text-amber-200 text-xs md:text-sm leading-relaxed whitespace-pre-wrap space-y-2">
                @if($alasanR1 !== '')
                  <div><b>Revisi 1:</b> {{ $alasanR1 }}</div>
                @endif
                @if($alasanR2 !== '')
                  <div><b>Revisi 2:</b> {{ $alasanR2 }}</div>
                @endif
              </div>
            </div>
          @endif

          <!-- Histori Penjelasan & Bukti Dukung (Sebelum/Revisi 1/Revisi 2) -->
          @php
            $hist = ($domainRecordsMap[$d->id] ?? collect());
            $base = $hist->filter(fn($r) => (string)$r->status !== 'revisi')->sortByDesc('id')->first();
            $rev1 = $hist->filter(fn($r) => (string)$r->status === 'revisi' && (int)($r->revisi_round ?? 0) === 1)->sortByDesc('id')->first();
            $rev2 = $hist->filter(fn($r) => (string)$r->status === 'revisi' && (int)($r->revisi_round ?? 0) === 2)->sortByDesc('id')->first();

            $filesBase = $base ? $base->buktiDukung : collect();
            $filesR1   = $rev1 ? $rev1->buktiDukung : collect();
            $filesR2   = $rev2 ? $rev2->buktiDukung : collect();
          @endphp

          @if($base || $rev1 || $rev2)
            <div class="mb-6">
              <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-3 uppercase tracking-wide">Histori Revisi</div>
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Sebelum --}}
                <div class="bg-black/5 dark:bg-white/5 border border-(--border-strong) rounded-xl p-4 text-left">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-clock-history text-(--muted)"></i> Sebelum Revisi
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left!">
                    {{ trim((string)($base?->penjelasan ?? '')) !== '' ? $base->penjelasan : '-' }}
                  </div>
                  <div class="mt-4 text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Bukti Dukung</div>
                  @if($filesBase && $filesBase->count() > 0)
                    <div class="space-y-2">
                      @foreach($filesBase as $f)
                        <a class="block text-[10px] md:text-xs text-(--brand) hover:underline truncate"
                           href="{{ asset('storage/' . $f->file) }}" target="_blank" rel="noopener">
                          <i class="bi bi-box-arrow-up-right me-1"></i>{{ $f->original_name ?: basename($f->file) }}
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="text-[11px] text-(--muted) italic">-</div>
                  @endif
                </div>

                {{-- Revisi 1 --}}
                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-left">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-arrow-return-left text-emerald-600"></i> Revisi 1
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left!">
                    {{ trim((string)($rev1?->penjelasan ?? '')) !== '' ? $rev1->penjelasan : '-' }}
                  </div>
                  <div class="mt-4 text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Bukti Dukung</div>
                  @if($filesR1 && $filesR1->count() > 0)
                    <div class="space-y-2">
                      @foreach($filesR1 as $f)
                        <a class="block text-[10px] md:text-xs text-emerald-600 hover:text-emerald-700 hover:underline truncate"
                           href="{{ asset('storage/' . $f->file) }}" target="_blank" rel="noopener">
                          <i class="bi bi-box-arrow-up-right me-1"></i>{{ $f->original_name ?: basename($f->file) }}
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="text-[11px] text-(--muted) italic">-</div>
                  @endif
                </div>

                {{-- Revisi 2 --}}
                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-left">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-arrow-return-left text-emerald-600"></i> Revisi 2
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left!">
                    {{ trim((string)($rev2?->penjelasan ?? '')) !== '' ? $rev2->penjelasan : '-' }}
                  </div>
                  <div class="mt-4 text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Bukti Dukung</div>
                  @if($filesR2 && $filesR2->count() > 0)
                    <div class="space-y-2">
                      @foreach($filesR2 as $f)
                        <a class="block text-[10px] md:text-xs text-emerald-600 hover:text-emerald-700 hover:underline truncate"
                           href="{{ asset('storage/' . $f->file) }}" target="_blank" rel="noopener">
                          <i class="bi bi-box-arrow-up-right me-1"></i>{{ $f->original_name ?: basename($f->file) }}
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="text-[11px] text-(--muted) italic">-</div>
                  @endif
                </div>
              </div>
            </div>
          @endif

          <!-- Form Revisi -->
          @if($isTargetRevisi && !($lke?->is_locked_bps ?? false))
            @php
              $oldForThisDomain = (int) old('domain_id', 0) === (int) $d->id;
              $selectedKriteriaId = $oldForThisDomain
                ? (int) old('kriteria_id', 0)
                : (int) ($lke->kriteria_id ?? 0);
              $oldPenjelasan = $oldForThisDomain
                ? (string) old('penjelasan', '')
                : (string) ($lke->penjelasan ?? '');
            @endphp

            <div class="mt-6 pt-6 border-t border-(--border-strong)">
              <form method="POST" action="{{ route('opd.lke.riwayat.revisi.store') }}" enctype="multipart/form-data" class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-5">
                @csrf
                <input type="hidden" name="tahun_id" value="{{ $tahun->id }}">
                <input type="hidden" name="nama_kegiatan" value="{{ $namaKegiatan }}">
                <input type="hidden" name="nomor_rekomendasi" value="{{ $nomorRek }}">
                <input type="hidden" name="domain_id" value="{{ $d->id }}">

                <div class="flex items-center gap-2 font-bold justify-start text-base md:text-lg text-amber-600 mb-4 pb-3 border-b border-amber-500/20">
                    <i class="bi bi-pencil-square"></i> Form Revisi Indikator
                </div>

                <!-- Kriteria (tidak dapat diubah saat revisi) -->
                <div class="mb-5">
                  <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Kriteria (Terkunci)</label>
                  <input type="hidden" name="kriteria_id" value="{{ $selectedKriteriaId }}">
                  <div class="bg-(--panel) border border-(--border-strong) rounded-xl p-4 text-xs md:text-sm text-(--text)">
                    @php
                      $selectedK = $d->kriterias->firstWhere('id', $selectedKriteriaId);
                    @endphp
                    @if($selectedK)
                      <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-600">
                          Tingkat {{ $selectedK->tingkat }}
                        </span>
                        <span class="font-medium">{{ $selectedK->kriteria }}</span>
                      </div>
                    @else
                      <span class="text-(--muted)">Kriteria belum dipilih.</span>
                    @endif
                  </div>
                  <div class="text-[10px] text-(--muted) mt-2"><i class="bi bi-info-circle"></i> Saat revisi, OPD hanya dapat mengubah penjelasan dan menambah bukti dukung.</div>
                </div>

                <!-- Penjelasan Revisi -->
                <div class="mb-5">
                  <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Penjelasan Revisi <span class="text-red-500">*</span></label>
                  <textarea class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all text-xs md:text-sm leading-relaxed" name="penjelasan" rows="4" required placeholder="Tuliskan penjelasan perbaikan di sini...">{{ $oldPenjelasan }}</textarea>
                </div>

                <!-- Upload File Bukti Revisi -->
                <div class="mb-5">
                  <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Tambah File Bukti Revisi</label>

                  <div class="relative inline-block w-full">
                    <input type="file" class="revisi-file-input absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" name="files[]" multiple data-domain-id="{{ $d->id }}" title="Pilih file bukti revisi">
                    <div class="w-full bg-(--sidebar-bg) border-2 border-dashed border-(--border-strong) hover:border-amber-400 text-(--text) rounded-xl px-4 py-6 flex flex-col items-center justify-center gap-2 transition-all group">
                      <i class="bi bi-cloud-arrow-up text-2xl md:text-3xl text-(--muted) group-hover:text-amber-500 transition-colors"></i>
                      <span class="font-medium text-xs md:text-sm text-(--text)">Klik atau seret file ke sini untuk upload</span>
                      <span class="text-[10px] md:text-xs text-(--muted)">Bisa upload banyak file sekaligus</span>
                    </div>
                  </div>

                  <div class="text-(--muted) text-[10px] md:text-xs space-y-1 mt-2">
                    <p><i class="bi bi-info-circle"></i> Wajib jika memilih tingkat 2-5, opsional jika tingkat 1. Maksimal <b class="text-(--text)">10MB per file</b>.</p>
                    <p><i class="bi bi-info-circle"></i> Upload baru akan <b class="text-(--text)">ditambahkan</b> ke file revisi yang sudah ada, tanpa menghapus file sebelumnya.</p>
                  </div>
                  @if($errors->has('files'))
                     <div class="text-red-500 text-[10px] md:text-xs mt-2 p-2 bg-red-500/10 rounded-lg">{{ $errors->first('files') }}</div>
                  @endif

                  <!-- Preview Box -->
                  <div class="mt-4" id="revisiPreview{{ $d->id }}"></div>
                </div>

                <div class="flex justify-end pt-2 border-t border-amber-500/20">
                  <button type="submit" class="px-4 md:px-5 py-2 md:py-2.5 bg-amber-500 text-white rounded-xl hover:bg-amber-600 transition-colors font-semibold flex items-center gap-2 shadow-sm shadow-amber-500/20">
                    <i class="bi bi-save2"></i> Simpan Revisi
                  </button>
                </div>
              </form>
            </div>
          @elseif(isset($isTargetRevisi) && $isTargetRevisi && ($lke?->is_locked_bps ?? false))
            <div class="mt-6 pt-6 border-t border-(--border-strong)">
              <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-xl p-4 flex items-center gap-3">
                <i class="bi bi-shield-lock-fill text-xl"></i>
                <div>
                  <div class="font-bold">Penilaian Telah Difinalisasi</div>
                  BPS telah mengunci penilaian ini. Anda tidak dapat melakukan revisi lagi untuk indikator ini.
                </div>
              </div>
            </div>
          @endif

        </div>
      </div>
    </div>
  @endforeach
</div>

<script>
  // Custom Accordion Logic — auto-close lainnya + smooth scroll ke yang dibuka
  function toggleAccordion(id) {
    const activeContent = document.getElementById(id);
    const icon = document.getElementById(`icon-${id}`);
    const isHidden = activeContent.classList.contains('hidden');

    // Tutup semua accordion lain + reset semua icon
    document.querySelectorAll('.group-expanded').forEach(el => {
      el.classList.add('hidden');
    });
    document.querySelectorAll('.accordion-icon').forEach(el => {
      el.classList.remove('rotate-180');
    });

    if (isHidden) {
      activeContent.classList.remove('hidden');
      if (icon) icon.classList.add('rotate-180');

      // Scroll ke wrapper parent agar posisi POV stabil
      const card = activeContent.parentElement;
      if (card) {
        setTimeout(() => {
          card.classList.add('scroll-mt-header');
          const rect = card.getBoundingClientRect();
          const top = window.scrollY + rect.top - 110; // offset header
          window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
        }, 500);
      }
    }
  }

  // Row Selection Logic for Revisi
  function selectRevisiKriteriaFromRow(rowEl) {
    if (!rowEl) return;
    const domainId = parseInt(rowEl.getAttribute('data-domain-id') || '', 10);
    const kriteriaId = parseInt(rowEl.getAttribute('data-kriteria-id') || '', 10);
    if (!Number.isFinite(domainId) || !Number.isFinite(kriteriaId)) return;
    selectRevisiKriteria(domainId, kriteriaId);
  }

  function selectRevisiKriteria(domainId, kriteriaId) {
    document.querySelectorAll(`[id^="revisi_row_${domainId}_"]`).forEach((row) => {
      row.classList.remove('bg-amber-500/10', 'hover:bg-amber-500/20');
      
      const numSpan = row.querySelector('span');
      if(numSpan) {
          numSpan.classList.remove('text-amber-600');
          numSpan.classList.add('text-(--text)');
      }
      
      const textTd = row.querySelectorAll('td')[1];
      if(textTd) textTd.classList.remove('font-medium');
    });

    const row = document.getElementById(`revisi_row_${domainId}_${kriteriaId}`);
    if (row) {
      row.classList.add('bg-amber-500/10', 'hover:bg-amber-500/20');
      
      const numSpan = row.querySelector('span');
      if(numSpan) {
           numSpan.classList.remove('text-(--text)');
           numSpan.classList.add('text-amber-600');
      }
      
      const textTd = row.querySelectorAll('td')[1];
      if(textTd) textTd.classList.add('font-medium');
    }

    const radio = document.getElementById(`revisi_radio_${domainId}_${kriteriaId}`);
    if (radio) {
      radio.checked = true;
    }
  }

  // File Preview Logic
  const MAX_REVISI_FILE_SIZE = 10 * 1024 * 1024;
  const revisiSelectedFiles = {};

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function applyFilesToInput(domainId) {
    const input = document.querySelector(`.revisi-file-input[data-domain-id="${domainId}"]`);
    if (!input) return;

    const dt = new DataTransfer();
    (revisiSelectedFiles[domainId] || []).forEach((file) => dt.items.add(file));
    input.files = dt.files;
  }

  function renderRevisiPreview(domainId) {
    const wrap = document.getElementById(`revisiPreview${domainId}`);
    if (!wrap) return;

    const files = revisiSelectedFiles[domainId] || [];
    if (!files.length) {
      wrap.innerHTML = '';
      return;
    }

    let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">';
    files.forEach((file, index) => {
      html += `
        <div class="flex items-center gap-3 p-3 bg-white/5 border border-(--border-strong) rounded-xl relative group pr-10">
          <div class="w-10 h-10 rounded-lg bg-(--brand)/10 flex items-center justify-center text-(--brand) shrink-0">
             <i class="bi bi-file-earmark-plus text-lg md:text-xl"></i>
          </div>
          <div class="flex-1 min-w-0">
              <div class="text-xs md:text-sm font-semibold text-(--text) truncate" title="${escapeHtml(file.name)}">
                ${escapeHtml(file.name)}
              </div>
              <div class="text-[10px] md:text-xs text-(--muted) mt-0.5">
                  ${Math.round(file.size / 1024)} KB
              </div>
          </div>
          <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-500/10 transition-colors tooltip" aria-label="Hapus" onclick="removeRevisiFile(${domainId}, ${index})">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      `;
    });
    html += '</div>';

    wrap.innerHTML = html;
  }

  function removeRevisiFile(domainId, index) {
    const current = revisiSelectedFiles[domainId] || [];
    current.splice(index, 1);
    revisiSelectedFiles[domainId] = current;
    applyFilesToInput(domainId);
    renderRevisiPreview(domainId);
  }

  document.querySelectorAll('.revisi-file-input').forEach((input) => {
    input.addEventListener('change', (event) => {
      const domainId = parseInt(event.target.getAttribute('data-domain-id'), 10);
      if (!Number.isFinite(domainId)) return;

      const incoming = Array.from(event.target.files || []);
      const current = revisiSelectedFiles[domainId] || [];
      const validIncoming = [];

      incoming.forEach((file) => {
        if (file.size <= MAX_REVISI_FILE_SIZE) {
          validIncoming.push(file);
        } else {
             ui.showToast(`File ${file.name} terlalu besar. Maksimal 10MB.`, 'error');
        }
      });

      revisiSelectedFiles[domainId] = current.concat(validIncoming);
      applyFilesToInput(domainId);
      renderRevisiPreview(domainId);
    });
  });
</script>
@endsection
