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
  <div class="flex items-center gap-3">
    @if($isLocked)
      <span class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 rounded-xl text-xs md:text-sm font-bold">
        <i class="bi bi-lock-fill"></i> TERKUNCI / FINAL
      </span>
    @else
      <form action="{{ route('bps.penilaian.finalize') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memfinalisasi penilaian ini? Setelah difinalisasi, OPD tidak dapat melakukan revisi lagi.')">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="tahun_id" value="{{ $tahun->id }}">
        <input type="hidden" name="nama_kegiatan" value="{{ $namaKegiatan }}">
        <input type="hidden" name="nomor_rekomendasi" value="{{ $nomorRek }}">
        <button type="submit" 
          @if(!$allScored) disabled title="Semua indikator harus dinilai terlebih dahulu" @endif
          class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:bg-(--brand)/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 transition-colors text-xs md:text-sm font-semibold shadow-sm">
          <i class="bi bi-check-all text-lg"></i> Finalisasikan Penilaian
        </button>
      </form>
    @endif
    <a href="{{ route('bps.penilaian.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors text-xs md:text-sm">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
  </div>
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
      $reqRound = 0;
      if ($isRequested) {
        $r1 = (string) (($revisiStatus[$d->id][1] ?? '') ?: '');
        $r2 = (string) (($revisiStatus[$d->id][2] ?? '') ?: '');
        if ($r2 === 'requested') $reqRound = 2;
        elseif ($r1 === 'requested') $reqRound = 1;
      }
      $lastBps = $bpsLastMap[$d->id] ?? null;
      $hasBpsEval  = (bool)($lastBps?->penilaian_bps);

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

          <span
            data-bps-scored-badge
            data-domain-id="{{ (int) $d->id }}"
            class="{{ $hasBpsEval ? 'inline-flex' : 'hidden' }} items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-(--brand)/10 border border-(--brand)/30 text-(--brand) whitespace-nowrap">
            <i class="bi bi-shield-check"></i> Dinilai
          </span>

          <span
            data-bps-requested-badge
            data-domain-id="{{ (int) $d->id }}"
            class="{{ $isRequested ? 'inline-flex' : 'hidden' }} items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-500 whitespace-nowrap">
            <i class="bi bi-arrow-return-left"></i>
            <span data-bps-requested-badge-text>Perlu Revisi{{ $reqRound ? ' ('.$reqRound.')' : '' }}</span>
          </span>

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

          {{-- Histori Penjelasan & Bukti Dukung (Sebelum/Revisi 1/Revisi 2) --}}
          <div>
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
              <div class="text-xs md:text-sm font-semibold text-(--muted) mb-3">Bukti Dukung & Penjelasan (Histori)</div>
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Sebelum --}}
                <div class="bg-black/5 dark:bg-white/5 border border-(--border-strong) rounded-xl p-4">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-clock-history text-(--muted)"></i> Sebelum Revisi
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left">{{ trim((string)($base?->penjelasan ?? '')) !== '' ? $base->penjelasan : '-' }}</div>
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
                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-arrow-return-left text-emerald-600"></i> Revisi 1
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left">{{ trim((string)($rev1?->penjelasan ?? '')) !== '' ? $rev1->penjelasan : '-' }}</div>
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
                <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
                  <div class="font-semibold text-(--text) text-xs md:text-sm mb-2 flex items-center gap-2">
                    <i class="bi bi-arrow-return-left text-emerald-600"></i> Revisi 2
                  </div>
                  <div class="text-[10px] md:text-xs text-(--muted) mb-2 uppercase tracking-wide">Penjelasan</div>
                  <div class="text-xs md:text-sm text-(--text) whitespace-pre-wrap text-left">{{ trim((string)($rev2?->penjelasan ?? '')) !== '' ? $rev2->penjelasan : '-' }}</div>
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
            @else
              <div class="text-(--muted) text-xs md:text-sm italic">Belum ada data.</div>
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
                    class="bg-(--panel) border border-(--border-strong) rounded-xl p-4 md:p-5"
                    data-domain-id="{{ (int) $d->id }}">
                @csrf
                <input type="hidden" name="lke_id" value="{{ $lke->id }}">
                <input type="hidden" name="action" id="action-{{ $lke->id }}" value="simpan">
                <input type="hidden" name="round" id="round-{{ $lke->id }}" value="">

                {{-- Score --}}
                <div class="mb-5">
                  <label class="block text-xs md:text-sm font-semibold text-(--text) mb-3">
                    Nilai Indikator (1–5) <span class="text-red-500">*</span>
                  </label>
                  @if($lastBps && !is_null($lastBps->penilaian_bps))
                    <div class="text-[10px] md:text-xs text-(--muted) mb-2">
                      Nilai terakhir dari BPS: <b class="text-(--text)">{{ (int) $lastBps->penilaian_bps }}</b>
                      <span class="hidden sm:inline">•</span>
                      <span class="block sm:inline">update: {{ \Illuminate\Support\Carbon::parse($lastBps->updated_at)->format('d/m/Y H:i') }}</span>
                    </div>
                  @endif
                  <div class="flex flex-wrap gap-2 md:gap-3">
                    @for($i = 1; $i <= 5; $i++)
                      @php $isChecked = (int)($lastBps?->penilaian_bps ?? 0) === $i; @endphp
                      <label class="eval-radio-label flex items-center justify-center cursor-pointer w-12 h-12 md:w-14 md:h-14 border rounded-xl transition-all text-center font-bold text-base md:text-lg
                        {{ $isChecked
                          ? 'border-(--brand) bg-(--brand)/10 text-(--brand)'
                          : 'border-(--border-strong) bg-(--sidebar-bg) text-(--muted) hover:border-(--brand)/40 hover:bg-black/5' }}
                        {{ $isLocked ? 'pointer-events-none opacity-80' : '' }}">
                        <input type="radio" name="penilaian_bps" value="{{ $i }}"
                          {{ $isChecked ? 'checked' : '' }}
                          class="sr-only"
                          {{ $isLocked ? 'disabled' : '' }}
                          data-lke-id="{{ $lke->id }}"
                          data-bps-score-radio
                          required>
                        {{ $i }}
                      </label>
                    @endfor
                  </div>
                </div>

                {{-- Note --}}
                <div class="mb-5">
                  <label id="label-catatan-{{ $lke->id }}" class="block text-xs md:text-sm font-semibold text-(--text) mb-2">
                    Catatan Evaluasi / Alasan Revisi
                    <span class="text-[10px] font-normal text-(--muted) ml-1">(dipakai saat simpan penilaian atau saat minta revisi)</span>
                  </label>
                  <textarea name="catatan_bps" id="catatan-{{ $lke->id }}" rows="3"
                    class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-(--brand) focus:border-transparent transition-all text-xs md:text-sm leading-relaxed resize-none placeholder:text-(--muted) disabled:opacity-70"
                    placeholder="Tulis catatan evaluasi atau alasan revisi di sini..."
                    {{ $isLocked ? 'disabled' : '' }}
                    data-lke-id="{{ $lke->id }}"
                    data-bps-catatan-eval>{{ $lastBps?->catatan_bps ?? '' }}</textarea>
                </div>

                @php
                  $r1 = (string) (($revisiStatus[$d->id][1] ?? '') ?: '');
                  $r2 = (string) (($revisiStatus[$d->id][2] ?? '') ?: '');
                  $r1Done = $r1 === 'revised';
                  $r2Done = $r2 === 'revised';
                  $r1Req  = $r1 === 'requested';
                  $r2Req  = $r2 === 'requested';
                  $canOpenR2 = $r1Done;
                @endphp

                {{-- Revisi Accordion --}}
                <div class="pt-4 border-t border-(--border-strong) space-y-3">
                  <div class="text-xs md:text-sm font-semibold text-(--text) flex items-center gap-2">
                    <i class="bi bi-arrow-return-left text-amber-500"></i> Permintaan Revisi (maks. 2x)
                  </div>

                  {{-- Revisi 1 --}}
                  <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl overflow-hidden">
                    <div class="p-4 flex items-start justify-between gap-3">
                      <div>
                        <div class="font-bold text-(--text) text-sm">Revisi 1</div>
                        <div class="text-[10px] text-(--muted) mt-0.5" data-bps-rev-status data-round="1">
                          @if($r1Done) Selesai (OPD sudah revisi) @elseif($r1Req) Menunggu OPD @else Belum diminta @endif
                        </div>
                      </div>
                      <button type="button"
                        {{ ($isLocked || $r1Done) ? 'disabled' : '' }}
                        data-lke-id="{{ $lke->id }}"
                        data-round="1"
                        data-bps-btn-revisi
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold text-xs md:text-sm transition-all
                          {{ $r1Req ? 'bg-amber-500 border-amber-500 text-white hover:bg-amber-600' : 'bg-transparent border-amber-500/40 text-amber-600 hover:bg-amber-500/10' }}
                          {{ ($isLocked || $r1Done) ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <i class="bi bi-send"></i> {{ $r1Req ? 'Kirim Ulang' : 'Minta Revisi 1' }}
                      </button>
                    </div>
                    <div class="px-4 pb-4 text-[10px] text-(--muted)" data-bps-rev-saved data-round="1">
                      @php $saved1 = trim((string)($revisiCatatan[$d->id][1] ?? '')); @endphp
                      <span data-bps-rev-saved-prefix>
                        {{ $saved1 !== '' ? 'Alasan tersimpan:' : 'Isi alasan revisi di textarea di atas, lalu klik tombol “Minta Revisi 1”.' }}
                      </span>
                      <span class="text-(--text) font-semibold" data-bps-rev-saved-text>{{ $saved1 }}</span>
                      @if($r1Done)
                        <div class="mt-2">Revisi 1 sudah selesai, tidak dapat diubah lagi.</div>
                      @endif
                    </div>
                  </div>

                  {{-- Revisi 2 --}}
                  <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl overflow-hidden {{ $canOpenR2 ? '' : 'opacity-60' }}" data-bps-rev-wrap data-round="2">
                    <div class="p-4 flex items-start justify-between gap-3">
                      <div>
                        <div class="font-bold text-(--text) text-sm">Revisi 2</div>
                        <div class="text-[10px] text-(--muted) mt-0.5" data-bps-rev-status data-round="2">
                          @if(!$canOpenR2) Kunci: selesaikan revisi 1 dulu
                          @elseif($r2Done) Selesai (OPD sudah revisi)
                          @elseif($r2Req) Menunggu OPD
                          @else Belum diminta @endif
                        </div>
                      </div>
                      <button type="button"
                        {{ ($isLocked || !$canOpenR2 || $r2Done) ? 'disabled' : '' }}
                        data-lke-id="{{ $lke->id }}"
                        data-round="2"
                        data-bps-btn-revisi
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-semibold text-xs md:text-sm transition-all
                          {{ $r2Req ? 'bg-amber-500 border-amber-500 text-white hover:bg-amber-600' : 'bg-transparent border-amber-500/40 text-amber-600 hover:bg-amber-500/10' }}
                          {{ ($isLocked || !$canOpenR2 || $r2Done) ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <i class="bi bi-send"></i> {{ $r2Req ? 'Kirim Ulang' : 'Minta Revisi 2' }}
                      </button>
                    </div>
                    <div class="px-4 pb-4 text-[10px] text-(--muted)" data-bps-rev-saved data-round="2">
                      @php $saved2 = trim((string)($revisiCatatan[$d->id][2] ?? '')); @endphp
                      <span data-bps-rev-saved-prefix>
                        {{ $saved2 !== '' ? 'Alasan tersimpan:' : 'Isi alasan revisi di textarea di atas, lalu klik tombol “Minta Revisi 2”.' }}
                      </span>
                      <span class="text-(--text) font-semibold" data-bps-rev-saved-text>{{ $saved2 }}</span>
                      @if($r2Done)
                        <div class="mt-2">Revisi 2 sudah selesai, tidak dapat diubah lagi.</div>
                      @endif
                    </div>
                  </div>
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
  /**
   * BPS Penilaian (client-side):
   * - Accordion memakai scroll offset agar POV selalu ke header (hindari jatuh ke bawah panel).
   * - Autosave penilaian menggunakan timer per LKE (debounce) untuk mengurangi request.
   * - Revisi 2 dibatasi: hanya bisa diminta setelah Revisi 1 berstatus `revised`.
   * - Saat `isLocked` (finalisasi), tombol/textarea seharusnya disabled oleh Blade.
   *
   * Catatan: update UI dilakukan live (tanpa refresh) setelah AJAX sukses:
   * - badge "Dinilai"
   * - status "Menunggu OPD" / "Selesai"
   * - label tombol "Minta Revisi" → "Kirim Ulang"
   */
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
    // Scroll ke header card parent agar posisi POV stabil (hindari jatuh ke bawah accordion)
    const card = el.closest('.indicator-card') || el.closest('.lke-card') || el.parentElement;
    if (card) {
      setTimeout(() => {
        card.classList.add('scroll-mt-header');
        const rect = card.getBoundingClientRect();
        const top = window.scrollY + rect.top - 110; // offset header
        window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
      }, 500);
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

    // Bind input handlers (hindari inline handler di Blade)
    document.querySelectorAll('[data-bps-score-radio]').forEach((radio) => {
      radio.addEventListener('change', () => {
        const lkeId = parseInt(radio.getAttribute('data-lke-id') || '0', 10);
        if (Number.isFinite(lkeId) && lkeId > 0) autoSaveBps(lkeId);
      });
    });
    document.querySelectorAll('[data-bps-catatan-eval]').forEach((ta) => {
      const on = () => {
        const lkeId = parseInt(ta.getAttribute('data-lke-id') || '0', 10);
        if (Number.isFinite(lkeId) && lkeId > 0) scheduleAutoSave(lkeId);
      };
      ta.addEventListener('keyup', on);
      ta.addEventListener('change', on);
    });
  });

  // Event delegation: robust untuk tombol revisi (termasuk jika DOM berubah)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-bps-btn-revisi]');
    if (!btn) return;
    if (btn.hasAttribute('disabled')) return;
    const lkeId = parseInt(btn.getAttribute('data-lke-id') || '0', 10);
    const round = parseInt(btn.getAttribute('data-round') || '0', 10);
    if (!Number.isFinite(lkeId) || lkeId <= 0) return;
    if (![1, 2].includes(round)) return;
    requestRevisi(lkeId, round);
  }, true);

  function requestRevisi(lkeId, round) {
    const roundEl = document.getElementById(`round-${lkeId}`);
    const actionEl = document.getElementById(`action-${lkeId}`);

    const catatanEl = document.getElementById(`catatan-${lkeId}`);
    const alasan = (catatanEl?.value ?? '').trim();

    if (!alasan) {
      if (catatanEl) {
        catatanEl.classList.add('border-red-500', 'focus:ring-red-400');
        catatanEl.classList.remove('border-(--border-strong)');
        catatanEl.focus();
        catatanEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      setIndicator(lkeId, 'error', 'Alasan revisi wajib diisi');
      return;
    }

    if (roundEl) roundEl.value = String(round);
    if (actionEl) actionEl.value = 'revisi';
    doSave(lkeId);
  }

  function scheduleAutoSave(lkeId) {
    clearTimeout(saveTimers[lkeId]);
    const actionEl = document.getElementById(`action-${lkeId}`);
    const roundEl = document.getElementById(`round-${lkeId}`);
    if (actionEl) actionEl.value = 'simpan';
    if (roundEl) roundEl.value = '';
    setIndicator(lkeId, 'saving');
    saveTimers[lkeId] = setTimeout(() => doSave(lkeId), 900);
  }

  function autoSaveBps(lkeId) {
    const form = document.getElementById(`bps-eval-form-${lkeId}`);
    const actionEl = document.getElementById(`action-${lkeId}`);
    const roundEl = document.getElementById(`round-${lkeId}`);
    if (actionEl) actionEl.value = 'simpan';
    if (roundEl) roundEl.value = '';
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
    const round    = parseInt(formData.get('round') || '0', 10);
    const alasan   = (formData.get('catatan_bps') ?? '').trim();

    if (!nilai) {
      setIndicator(lkeId, 'error', 'Pilih nilai 1–5 terlebih dahulu');
      return;
    }
    if (action === 'revisi') {
      if (![1,2].includes(round)) {
        setIndicator(lkeId, 'error', 'Round revisi tidak valid');
        return;
      }
      if (alasan === '') {
        setIndicator(lkeId, 'error', 'Alasan revisi wajib diisi');
        return;
      }
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
        // Live UI updates (tanpa refresh)
        // 1) Badge "Dinilai" muncul saat nilai tersimpan
        const domainIdForScore = parseInt(form.getAttribute('data-domain-id') || '0', 10);
        if (Number.isFinite(domainIdForScore) && domainIdForScore > 0) {
          const scoredBadge = document.querySelector(`[data-bps-scored-badge][data-domain-id="${domainIdForScore}"]`);
          if (scoredBadge) {
              scoredBadge.classList.remove('hidden');
              scoredBadge.classList.add('inline-flex');
          }
        }

        if (action === 'revisi' && [1,2].includes(round)) {
          const domainId = parseInt(form.getAttribute('data-domain-id') || '0', 10);
          const badge = document.querySelector(`[data-bps-requested-badge][data-domain-id="${domainId}"]`);
          const badgeText = badge ? badge.querySelector('[data-bps-requested-badge-text]') : null;
          if (badge) {
              badge.classList.remove('hidden');
              badge.classList.add('inline-flex');
          }
          if (badgeText) badgeText.textContent = `Perlu Revisi (${round})`;

          // status line & saved reason text
          const st = form.querySelector(`[data-bps-rev-status][data-round="${round}"]`);
          if (st) st.textContent = 'Menunggu OPD';
          const saved = form.querySelector(`[data-bps-rev-saved][data-round="${round}"]`);
          if (saved) {
            const prefix = saved.querySelector('[data-bps-rev-saved-prefix]');
            const txt = saved.querySelector('[data-bps-rev-saved-text]');
            if (prefix) prefix.textContent = 'Alasan tersimpan:';
            if (txt) txt.textContent = alasan;
          }

          // Button label -> Kirim Ulang
          const btn = form.querySelector(`[data-bps-btn-revisi][data-round="${round}"]`);
          if (btn) {
            const label = btn.lastChild;
            // safest: rewrite innerHTML keeping icon
            btn.innerHTML = '<i class="bi bi-send"></i> Kirim Ulang';
            btn.classList.add('bg-amber-500', 'border-amber-500', 'text-white');
            btn.classList.remove('bg-transparent');
          }
        }
        setTimeout(() => setIndicator(lkeId, 'hide'), 3000);
      } else {
        setIndicator(lkeId, 'error', data.message || 'Gagal disimpan');
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
