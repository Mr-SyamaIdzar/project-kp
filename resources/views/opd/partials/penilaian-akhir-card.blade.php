{{--
  Partial: Nilai Akhir dari Admin
  Dipanggil dari opd/dashboard.blade.php dan juga dikembalikan via AJAX (HTML string).
  Variables: $penilaianAkhir (AdminPenilaianAkhir|null), $selectedYear (int)
--}}

@if($penilaianAkhir)
  @php
    $nilai = (float) $penilaianAkhir->nilai_akhir;
    // warna berdasarkan range nilai
    if ($nilai >= 4.5) {
      $nilaiColor = 'text-emerald-500';
      $nilaiLabel = 'Sangat Baik';
      $barColor = 'bg-emerald-500';
    } elseif ($nilai >= 3.5) {
      $nilaiColor = 'text-blue-500';
      $nilaiLabel = 'Baik';
      $barColor = 'bg-blue-500';
    } elseif ($nilai >= 2.5) {
      $nilaiColor = 'text-amber-500';
      $nilaiLabel = 'Cukup';
      $barColor = 'bg-amber-500';
    } elseif ($nilai >= 1.5) {
      $nilaiColor = 'text-orange-500';
      $nilaiLabel = 'Kurang';
      $barColor = 'bg-orange-500';
    } else {
      $nilaiColor = 'text-red-500';
      $nilaiLabel = 'Sangat Kurang';
      $barColor = 'bg-red-500';
    }
    $persen = (($nilai - 1) / 4) * 100; // mapping 1-5 ke 0-100%
  @endphp

  <div class="bg-(--panel) shadow-sm rounded-2xl border border-(--border-strong) p-6">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-(--brand)/10 flex items-center justify-center shrink-0">
          <i class="bi bi-award-fill text-(--brand) text-lg"></i>
        </div>
        <div>
          <div class="font-semibold text-sm md:text-base text-(--text)">Nilai Akhir dari Admin</div>
          <div class="text-[10px] text-(--muted)">Tahun {{ $penilaianAkhir->tahun }}</div>
        </div>
      </div>
      <div class="text-right shrink-0">
        <div class="font-bold text-3xl md:text-4xl {{ $nilaiColor }}">{{ number_format($nilai, 2) }}</div>
        <div class="text-xs font-semibold {{ $nilaiColor }} mt-0.5">{{ $nilaiLabel }}</div>
        <div class="text-[10px] text-(--muted)">dari skala 5</div>
      </div>
    </div>

    {{-- Progress bar --}}
    <div class="w-full bg-(--sidebar-bg) rounded-full h-2 mb-4">
      <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500"
           style="width: {{ number_format($persen, 1) }}%"></div>
    </div>

    <div class="flex flex-wrap gap-4">
      @if($penilaianAkhir->catatan)
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-2">
            <i class="bi bi-chat-left-text text-(--brand) text-sm"></i>
            <span class="text-xs font-semibold text-(--muted) uppercase tracking-wide">Catatan</span>
          </div>
          <div class="bg-(--sidebar-bg) border-l-4 border-(--brand) rounded-r-xl px-4 py-3">
            <p class="text-sm text-(--text) leading-relaxed break-words">{{ $penilaianAkhir->catatan }}</p>
          </div>
        </div>
      @endif

      @if($penilaianAkhir->file)
        <div class="shrink-0 flex items-end">
          <a href="{{ asset('storage/' . $penilaianAkhir->file) }}" target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2 bg-(--brand)/10 text-(--brand) rounded-xl hover:bg-(--brand)/20 transition-colors font-medium text-xs">
            <i class="bi bi-file-earmark-arrow-down"></i>
            {{ $penilaianAkhir->original_name ?? basename($penilaianAkhir->file) }}
          </a>
        </div>
      @endif
    </div>
  </div>

@else
  {{-- Jika belum ada nilai untuk tahun ini, tampilkan placeholder tipis --}}
  <div class="bg-(--panel) border border-dashed border-(--border-strong) rounded-2xl p-5 flex items-center gap-3 text-(--muted)">
    <i class="bi bi-award text-xl opacity-40"></i>
    <div>
      <div class="text-xs font-semibold">Nilai Akhir dari Admin</div>
      <div class="text-[10px] mt-0.5">Belum ada nilai akhir untuk tahun {{ $selectedYear }}.</div>
    </div>
  </div>
@endif
