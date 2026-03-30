@extends('layouts.admin')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Detail LKE</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Tampilan indikator read-only untuk monitoring admin.</div>
  </div>
  <a href="{{ route('lke.index') }}" class="inline-flex items-center gap-2 px-3 md:px-4 py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 transition-colors text-xs md:text-sm shrink-0">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6">
  <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
    <div class="md:col-span-4 lg:col-span-4">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">OPD</div>
      <div class="font-semibold text-(--text) text-base md:text-lg">{{ $user->nama ?? $user->username }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs mt-1">username: {{ $user->username }}</div>
    </div>
    <div class="md:col-span-4 lg:col-span-4">
      <div class="text-[10px] md:text-xs font-semibold text-(--muted) uppercase tracking-wider mb-1">Tahun</div>
      <div class="font-semibold text-(--text) text-base md:text-lg">{{ $tahun->tahun }}</div>
    </div>
    <div class="md:col-span-4 lg:col-span-4">
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
      $accId = 'acc-lke-adm-' . $d->id;
      $lke = $items[$d->id] ?? null;

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

    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden transition-all duration-300">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 md:p-5 bg-black/5 hover:bg-black/10 cursor-pointer border-b border-transparent transition-colors" onclick="toggleAccordion('{{ $accId }}')">
        <div>
          <div class="font-semibold text-(--text) text-[1.05rem]">{{ $d->nama_indikator }}</div>
          <div class="text-(--muted) text-xs md:text-sm mt-1">
            <b class="text-(--text)">{{ $d->kode }}</b> - {{ $d->nama_domain }} - {{ $d->nama_aspek }}
          </div>
        </div>
        <div class="flex items-center gap-3">
          @if($status === 'done')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 whitespace-nowrap">
              Lengkap
            </span>
          @elseif($status === 'progress')
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-amber-500/10 border border-amber-500/30 text-amber-500 whitespace-nowrap">
              Progres
            </span>
          @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-slate-500/10 border border-slate-500/30 text-(--muted) whitespace-nowrap">
              Kosong
            </span>
          @endif

          <button class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-(--border-strong) text-(--text) text-[10px] md:text-xs rounded-xl hover:bg-white/5 transition-colors" type="button">
            Buka
          </button>
        </div>
      </div>

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
                          <input type="radio" disabled {{ $active ? 'checked' : '' }} class="w-4 h-4 text-(--brand) bg-(--sidebar-bg) border-(--border-strong) focus:ring-(--brand) focus:ring-2 disabled:opacity-50">
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
            @php
              $revisedReq = $revisedRequestMap[$d->id] ?? null;
              $revTime = $revisedReq?->created_at;

              $allFiles = ($domainRecordsMap[$d->id] ?? collect())
                ->pluck('buktiDukung')->collapse()->unique('id');

              if ($revTime) {
                  $filesBefore = $allFiles->filter(fn($f) => $f->created_at < $revTime);
                  $filesAfter  = $allFiles->filter(fn($f) => $f->created_at >= $revTime);
              } else {
                  $filesBefore = $allFiles;
                  $filesAfter  = collect();
              }
            @endphp

            @if($filesAfter->count() > 0)
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- File Sebelum Revisi -->
                <div>
                  <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-2 uppercase tracking-wide">File Sebelum Revisi</div>
                  @if($filesBefore->count() > 0)
                    <div class="space-y-2">
                      @foreach($filesBefore as $f)
                        <div class="flex items-start gap-3 p-3 bg-black/5 dark:bg-white/5 border border-(--border-strong) rounded-xl">
                          <div class="w-8 h-8 rounded-lg bg-(--brand)/10 flex items-center justify-center text-(--brand) shrink-0">
                            <i class="bi bi-file-earmark-text text-base"></i>
                          </div>
                          <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-(--text) truncate mb-0.5" title="{{ $f->original_name ?: basename($f->file) }}">
                              {{ $f->original_name ?: basename($f->file) }}
                            </div>
                            <a href="{{ asset('storage/' . $f->file) }}" target="_blank" rel="noopener" class="text-[10px] text-(--brand) hover:underline inline-flex items-center gap-1 font-medium">
                              <i class="bi bi-box-arrow-up-right"></i> Lihat File
                            </a>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  @else
                    <div class="bg-black/5 dark:bg-white/5 border border-dashed border-(--border-strong) rounded-xl p-4 text-(--text) text-xs italic">
                      <span class="text-(--muted)">Belum ada file sebelumnya.</span>
                    </div>
                  @endif
                </div>

                <!-- File Revisi Saat Ini -->
                <div>
                  <div class="text-[10px] md:text-xs font-semibold text-(--muted) mb-2 uppercase tracking-wide text-emerald-600 dark:text-emerald-500">File Revisi Saat Ini</div>
                  <div class="space-y-2">
                    @foreach($filesAfter as $f)
                      <div class="flex items-start gap-3 p-3 bg-emerald-500/5 border border-emerald-500/20 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 shrink-0">
                          <i class="bi bi-file-earmark-check text-base"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                          <div class="text-xs font-semibold text-(--text) truncate mb-0.5" title="{{ $f->original_name ?: basename($f->file) }}">
                            {{ $f->original_name ?: basename($f->file) }}
                          </div>
                          <a href="{{ asset('storage/' . $f->file) }}" target="_blank" rel="noopener" class="text-[10px] text-emerald-600 hover:text-emerald-700 hover:underline inline-flex items-center gap-1 font-medium">
                            <i class="bi bi-box-arrow-up-right"></i> Lihat File
                          </a>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @else
              <div class="text-xs md:text-sm font-semibold text-(--muted) mb-3">Bukti Dukung</div>
              @if(!$lke)
                <div class="text-(--muted) text-xs md:text-sm italic">Belum ada data LKE untuk indikator ini.</div>
              @elseif($allFiles->count() === 0)
                <div class="text-(--muted) text-xs md:text-sm italic">Belum ada file.</div>
              @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                  @foreach($allFiles as $f)
                    @php
                      $url = asset('storage/' . $f->file);
                      $displayName = trim((string)($f->original_name ?? '')) !== '' ? $f->original_name : basename($f->file);
                    @endphp
                    <div class="bg-(--sidebar-bg) border border-(--border-strong) rounded-xl p-4 flex flex-col h-full">
                      <div class="font-semibold text-xs md:text-sm text-(--text) truncate" title="{{ $displayName }}">{{ $displayName }}</div>
                      <div class="mt-4 flex gap-2">
                         <a class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-(--border-strong) text-(--text) text-[10px] md:text-xs rounded-xl hover:bg-white/5 transition-colors inline-flex items-center gap-2" href="{{ $url }}" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-box-arrow-up-right"></i> Buka
                         </a>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<script>
  function closeAccordion(id) {
    const el = document.getElementById(id);
    if (!el || el.classList.contains('hidden')) return;
    el.classList.add('hidden');
    const header = el.previousElementSibling;
    if (header) {
        const btn = header.querySelector('button');
        if (btn) btn.textContent = 'Buka';
    }
  }

  function toggleAccordion(id) {
    const el = document.getElementById(id);
    const isHidden = el.classList.contains('hidden');

    // Close all other accordions
    document.querySelectorAll('.group-expanded').forEach(panel => {
      if (panel.id && panel.id !== id) {
        closeAccordion(panel.id);
      }
    });

    if (isHidden) {
      el.classList.remove('hidden');
      const header = el.previousElementSibling;
      if (header) {
        const btn = header.querySelector('button');
        if (btn) btn.textContent = 'Tutup';
        setTimeout(() => {
          const card = el.parentElement;
          if (card) {
            card.classList.add('scroll-mt-header');
            const rect = card.getBoundingClientRect();
            const top = window.scrollY + rect.top - 110; // offset header
            window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
          }
        }, 500);
      }
    } else {
      closeAccordion(id);
    }
  }
</script>
@endsection
