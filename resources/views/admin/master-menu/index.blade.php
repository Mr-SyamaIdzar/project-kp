@extends('layouts.admin')

@php
  $title = 'Master Menu';
  $header = 'Master Menu';
  $subheader = 'Kontrol akses menu LKE, konten Informasi, dan nilai akhir per OPD.';
@endphp

@section('content')

<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
  <div>
    <div class="font-semibold text-lg md:text-xl">Master Menu per Role</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Atur akses menu Isi LKE, konten Informasi, dan nilai akhir OPD.</div>
  </div>
</div>

{{-- Flash messages --}}
@if(session('success'))
  <div id="flash-success" class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-2xl px-5 py-3 mb-6 text-xs md:text-sm flex items-center gap-3">
    <i class="bi bi-check-circle-fill shrink-0"></i>
    <span>{{ session('success') }}</span>
  </div>
@endif
@if(session('failed'))
  <div id="flash-failed" class="bg-red-500/10 border border-red-500/30 text-red-500 rounded-2xl px-5 py-3 mb-6 text-xs md:text-sm flex items-center gap-3">
    <i class="bi bi-exclamation-circle-fill shrink-0"></i>
    <span>{{ session('failed') }}</span>
  </div>
@endif

{{-- ===== SECTION 1: MENU LKE TOGGLE ===== --}}
<div class="font-semibold text-sm md:text-base text-(--text) mb-3 flex items-center gap-2">
  <i class="bi bi-toggles text-(--brand)"></i> Kontrol Akses Menu Isi LKE
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-4">
  <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div>
      <div class="text-[10px] md:text-xs text-(--muted) mb-1">Role (hanya OPD yang bisa dikontrol)</div>
      <div class="font-semibold uppercase text-base md:text-lg text-(--text)">OPD</div>
    </div>
    <div>
      <div class="text-[10px] md:text-xs text-(--muted) mb-1">Total User</div>
      <div class="font-semibold text-base md:text-lg text-(--text)">{{ $totalUsers }}</div>
    </div>
    <div>
      <div class="text-[10px] md:text-xs text-(--muted) mb-1">User Nonaktif</div>
      <div class="font-semibold text-base md:text-lg text-(--text)">{{ $disabledUsers }}</div>
    </div>
  </div>
</div>

@if($totalUsers === 0)
  <div class="bg-yellow-500/10 border border-yellow-500/50 text-yellow-500 text-xs md:text-sm rounded-2xl p-4 mb-6">
    Belum ada user pada role OPD yang bisa diatur.
  </div>
@else
  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6">
    <div class="mb-6">
      <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs md:text-sm font-medium border {{ $menuIsiLkeAvailable ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-500' : 'bg-red-500/10 border-red-500/30 text-red-500' }}">
        <i class="bi {{ $menuIsiLkeAvailable ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
        Menu Isi LKE untuk role OPD: {{ $menuIsiLkeAvailable ? 'Tersedia' : 'Tidak Ada' }}
      </span>
    </div>

    <form method="POST" action="{{ route('master-menu.update') }}" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
      @csrf
      @method('PUT')
      <input type="hidden" name="role" value="opd">

      <div class="md:col-span-6 lg:col-span-4">
        <label class="block text-(--muted) text-xs md:text-sm mb-2">Status Menu Isi LKE</label>
        <select name="menu_isi_lke_available" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 md:px-4 py-2 md:py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
          <option value="1" {{ $menuIsiLkeAvailable ? 'selected' : '' }}>Tersedia</option>
          <option value="0" {{ !$menuIsiLkeAvailable ? 'selected' : '' }}>Tidak Ada</option>
        </select>
      </div>

      <div class="md:col-span-12">
        <button type="submit" class="px-5 md:px-6 py-2 md:py-2.5 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity font-medium">
          <i class="bi bi-save2"></i> Simpan Pengaturan
        </button>
      </div>
    </form>
  </div>
@endif

{{-- ===== SECTION 1.5: FEATURE TOGGLES ===== --}}
<div class="font-semibold text-sm md:text-base text-(--text) mb-3 mt-4 flex items-center gap-2">
  <i class="bi bi-toggles2 text-(--brand)"></i> Toggle Fitur
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6">
  <div class="text-(--muted) text-xs md:text-sm mb-5">
    Aktifkan atau nonaktifkan fitur secara global. Perubahan langsung berlaku untuk role BPS.
  </div>

  <form method="POST" action="{{ route('master-menu.updateFeatureToggles') }}">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">

      {{-- Toggle: Revisi Dokumen --}}
      <div class="bg-(--sidebar-bg) border border-(--border-strong) rounded-xl p-5">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div>
            <div class="font-semibold text-sm text-(--text) flex items-center gap-2">
              <i class="bi bi-file-earmark-diff text-amber-500"></i> Revisi Dokumen
            </div>
            <div class="text-[10px] md:text-xs text-(--muted) mt-1">
              BPS dapat meminta revisi dokumen ke OPD (maks. 1x).
            </div>
          </div>
          {{-- Toggle switch --}}
          <label class="relative inline-flex items-center cursor-pointer shrink-0" for="toggle-revisi-dokumen">
            <input type="checkbox" id="toggle-revisi-dokumen" name="revisi_dokumen_enabled" value="1"
              class="sr-only peer" {{ $revisiDokumenEnabled ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-400 peer-focus:ring-2 peer-focus:ring-(--brand)/30 rounded-full peer
              peer-checked:after:translate-x-full peer-checked:bg-(--brand)
              after:content-[''] after:absolute after:top-[2px] after:left-[2px]
              after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
          </label>
        </div>
        <div class="text-[10px] md:text-xs font-semibold px-2 py-1 rounded-lg inline-flex items-center gap-1
          {{ $revisiDokumenEnabled ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-500/10 text-(--muted)' }}">
          <i class="bi {{ $revisiDokumenEnabled ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
          {{ $revisiDokumenEnabled ? 'Aktif' : 'Nonaktif' }}
        </div>
      </div>

      {{-- Toggle: Input Hasil Interview --}}
      <div class="bg-(--sidebar-bg) border border-(--border-strong) rounded-xl p-5">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div>
            <div class="font-semibold text-sm text-(--text) flex items-center gap-2">
              <i class="bi bi-mic text-blue-500"></i> Input Hasil Interview
            </div>
            <div class="text-[10px] md:text-xs text-(--muted) mt-1">
              BPS dapat mengisi catatan dan nilai hasil interview per indikator.
            </div>
          </div>
          {{-- Toggle switch --}}
          <label class="relative inline-flex items-center cursor-pointer shrink-0" for="toggle-interview">
            <input type="checkbox" id="toggle-interview" name="interview_input_enabled" value="1"
              class="sr-only peer" {{ $interviewInputEnabled ? 'checked' : '' }}>
            <div class="w-11 h-6 bg-gray-400 peer-focus:ring-2 peer-focus:ring-(--brand)/30 rounded-full peer
              peer-checked:after:translate-x-full peer-checked:bg-(--brand)
              after:content-[''] after:absolute after:top-[2px] after:left-[2px]
              after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
          </label>
        </div>
        <div class="text-[10px] md:text-xs font-semibold px-2 py-1 rounded-lg inline-flex items-center gap-1
          {{ $interviewInputEnabled ? 'bg-emerald-500/10 text-emerald-600' : 'bg-slate-500/10 text-(--muted)' }}">
          <i class="bi {{ $interviewInputEnabled ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
          {{ $interviewInputEnabled ? 'Aktif' : 'Nonaktif' }}
        </div>
      </div>
    </div>

    <button type="submit"
      class="px-5 md:px-6 py-2 md:py-2.5 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity font-medium text-xs md:text-sm">
      <i class="bi bi-save2"></i> Simpan Toggle Fitur
    </button>
  </form>
</div>

{{-- ===== SECTION 2: EDIT KOLOM INFORMASI PER ROLE ===== --}}
<div class="font-semibold text-sm md:text-base text-(--text) mb-3 mt-2 flex items-center gap-2">
  <i class="bi bi-pencil-square text-(--brand)"></i> Kelola Konten Kolom Informasi
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6">
  <div class="text-(--muted) text-xs md:text-sm mb-5">
    Isi kolom "Informasi" yang ditampilkan di dashboard masing-masing role. Klik tab role lalu edit dan simpan.
  </div>

  {{-- Tab Navigasi --}}
  {{-- 
    FIX BUG: Tab ini HANYA mengontrol panel informasi.
    Tab yang dipilih disimpan di JS (activeInfoTab), bukan di PHP $role.
    Kontrol akses LKE di atas terpisah dan diatur via form POST sendiri.
  --}}
  <div class="flex gap-1 mb-6 bg-black/5 rounded-xl p-1 w-fit">
    @foreach(['admin' => 'Admin', 'opd' => 'OPD', 'bps' => 'BPS'] as $r => $label)
      <button type="button"
        onclick="switchInfoTab('{{ $r }}')"
        id="info-tab-{{ $r }}"
        class="px-4 py-1.5 rounded-lg text-xs md:text-sm font-semibold transition-all
          {{ $r === 'opd' ? 'bg-(--brand) text-white shadow-sm' : 'text-(--muted) hover:text-(--text)' }}">
        {{ $label }}
      </button>
    @endforeach
  </div>

  {{-- Panel per role — default aktif: OPD --}}
  @foreach([
    'admin' => $informasiAdmin,
    'opd'   => $informasiOpd,
    'bps'   => $informasiBps,
  ] as $r => $inf)
    <div id="info-panel-{{ $r }}" class="{{ $r !== 'opd' ? 'hidden' : '' }}">
      <form method="POST" action="{{ route('master-menu.updateInformasi') }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="role" value="{{ $r }}">

        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-(--muted) text-xs md:text-sm mb-2 font-semibold">
              Judul <span class="text-red-500">*</span>
            </label>
            <input type="text" name="judul" required maxlength="200"
              value="{{ $inf->judul }}"
              class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
          </div>

          <div>
            <label class="block text-(--muted) text-xs md:text-sm mb-2 font-semibold">
              Warna Card
            </label>
            <select name="warna"
              class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
              @php $w = $inf->warna ?? 'neutral'; @endphp
              <option value="neutral" {{ $w === 'neutral' ? 'selected' : '' }}>Netral</option>
              <option value="blue" {{ $w === 'blue' ? 'selected' : '' }}>Biru</option>
              <option value="red" {{ $w === 'red' ? 'selected' : '' }}>Merah</option>
              <option value="amber" {{ $w === 'amber' ? 'selected' : '' }}>Kuning</option>
              <option value="emerald" {{ $w === 'emerald' ? 'selected' : '' }}>Hijau</option>
            </select>
            <div class="text-[10px] text-(--muted) mt-1">Mengubah warna tampilan card Informasi di dashboard role {{ strtoupper($r) }}.</div>
          </div>

          <div>
            <label class="block text-(--muted) text-xs md:text-sm mb-2 font-semibold">
              Isi Informasi <span class="text-red-500">*</span>
            </label>
            <textarea name="isi" id="isi-{{ $r }}" required rows="5"
              oninput="updateWordCount('{{ $r }}')"
              class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-3 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all leading-relaxed resize-none"
              placeholder="Tuliskan teks informasi yang akan tampil di dashboard role {{ strtoupper($r) }}...">{{ $inf->isi }}</textarea>
            <div class="flex items-center justify-between mt-1">
              <div id="word-count-{{ $r }}" class="text-[10px] text-(--muted) transition-colors">0 kata</div>
              <div class="text-[10px] text-(--muted)">Maks. <b>300 kata</b></div>
            </div>
          </div>

          <div>
            <button type="submit"
              class="px-5 py-2 md:py-2.5 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity font-medium text-xs md:text-sm">
              <i class="bi bi-save2"></i> Simpan Informasi {{ strtoupper($r) }}
            </button>
          </div>
        </div>
      </form>

      {{-- Preview --}}
      <div class="mt-5 border-t border-(--border-strong) pt-5">
        <div class="text-[10px] text-(--muted) uppercase tracking-wider font-semibold mb-3">Preview tampilan di dashboard {{ strtoupper($r) }}</div>
        <div class="bg-(--sidebar-bg) border border-(--border-strong) rounded-xl p-4 text-xs md:text-sm text-(--text) leading-relaxed">
          {{ $inf->isi }}
        </div>
      </div>
    </div>
  @endforeach
</div>


{{-- Info cards --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 h-full">
    <div class="font-semibold text-(--text) mb-3 flex items-center gap-2">
      <i class="bi bi-lock text-(--brand)"></i> Saat Menu = Tidak Ada
    </div>
    <div class="text-(--muted) text-xs md:text-sm leading-relaxed">
      Semua user pada role terkait tetap melihat menu <b class="text-(--text)">Isi LKE</b>, tetapi saat dibuka halaman akan menampilkan pemberitahuan bahwa menu tidak tersedia.
    </div>
  </div>

  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 h-full">
    <div class="font-semibold text-(--text) mb-3 flex items-center gap-2">
      <i class="bi bi-shield-lock text-(--brand)"></i> Enforcement
    </div>
    <div class="text-(--muted) text-xs md:text-sm leading-relaxed">
      Semua aksi mutasi LKE dari OPD (autosave, upload file, finalize) juga ditolak oleh server ketika menu dinonaktifkan.
    </div>
  </div>
</div>

<script>
  const MAX_WORDS = 300;

  function countWords(text) {
    return text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
  }

  function updateWordCount(role) {
    const textarea = document.getElementById('isi-' + role);
    const counter  = document.getElementById('word-count-' + role);
    if (!textarea || !counter) return;
    const words = countWords(textarea.value);
    counter.textContent = words + ' kata';
    if (words >= MAX_WORDS) {
      counter.classList.add('text-red-500', 'font-semibold');
      counter.classList.remove('text-(--muted)', 'text-amber-500');
      if (words > MAX_WORDS) {
        textarea.value = textarea.value.trim().split(/\s+/).slice(0, MAX_WORDS).join(' ');
        counter.textContent = MAX_WORDS + ' kata (batas maksimum)';
      }
    } else if (words >= MAX_WORDS * 0.85) {
      counter.classList.add('text-amber-500', 'font-semibold');
      counter.classList.remove('text-red-500', 'text-(--muted)');
    } else {
      counter.classList.remove('text-red-500', 'text-amber-500', 'font-semibold');
      counter.classList.add('text-(--muted)');
    }
  }

  function switchInfoTab(role) {
    ['admin', 'opd', 'bps'].forEach(r => {
      const panel = document.getElementById('info-panel-' + r);
      const tab   = document.getElementById('info-tab-' + r);
      if (panel) panel.classList.add('hidden');
      if (tab) { tab.classList.remove('bg-(--brand)', 'text-white', 'shadow-sm'); tab.classList.add('text-(--muted)'); }
    });
    const ap = document.getElementById('info-panel-' + role);
    const at = document.getElementById('info-tab-' + role);
    if (ap) ap.classList.remove('hidden');
    if (at) { at.classList.add('bg-(--brand)', 'text-white', 'shadow-sm'); at.classList.remove('text-(--muted)'); }
    updateWordCount(role);
  }

  document.addEventListener('DOMContentLoaded', () => {
    ['admin', 'opd', 'bps'].forEach(r => updateWordCount(r));
  });
</script>

@endsection

