@extends('layouts.admin')

@php
  $title   = 'Nilai Akhir OPD';
  $header  = 'Kelola Nilai Akhir OPD';
  $subheader = 'Input nilai akhir (skala 1–5) dan file ketentuan per OPD untuk tahun berjalan.';
@endphp

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl text-(--text)">Kelola Nilai Akhir OPD</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">
      Tahun berjalan: <span class="font-semibold text-(--brand)">{{ now()->year }}</span>
      &nbsp;·&nbsp; Format file: PDF, DOC/DOCX, JPG, PNG, WEBP, BMP (max 10MB)
    </div>
  </div>
</div>

{{-- Flash --}}
@if(session('success'))
  <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 rounded-2xl px-5 py-3 mb-5 text-xs md:text-sm flex items-center gap-3">
    <i class="bi bi-check-circle-fill shrink-0"></i> {{ session('success') }}
  </div>
@endif
@if(session('failed'))
  <div class="bg-red-500/10 border border-red-500/30 text-red-500 rounded-2xl px-5 py-3 mb-5 text-xs md:text-sm flex items-center gap-3">
    <i class="bi bi-exclamation-circle-fill shrink-0"></i> {{ session('failed') }}
  </div>
@endif

{{-- Search + stats bar --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl mb-5 px-5 pt-5 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
  <div class="text-(--muted) text-xs">
    Menampilkan <b class="text-(--text)">{{ $opds->firstItem() }}–{{ $opds->lastItem() }}</b>
    dari <b class="text-(--text)">{{ $opds->total() }}</b> OPD
  </div>
  <form method="GET" action="{{ route('penilaian-akhir.index') }}" class="flex gap-2 w-full sm:w-auto">
    <div class="relative flex-1 sm:w-64">
      <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama OPD..."
        class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl pl-8 pr-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
      <i class="bi bi-search absolute left-2.5 top-1/2 -translate-y-1/2 text-(--muted) text-xs pointer-events-none"></i>
    </div>
    <button type="submit"
      class="px-4 py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 transition-opacity text-xs font-semibold shrink-0">
      Cari
    </button>
    @if($search)
      <a href="{{ route('penilaian-akhir.index') }}"
        class="px-3 py-2 border border-(--border-strong) text-(--muted) hover:text-(--text) rounded-xl text-xs transition-colors shrink-0">
        Reset
      </a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl overflow-hidden mb-5">
  @if($opds->isEmpty())
    <div class="py-16 text-center text-(--muted) text-sm">
      <i class="bi bi-building text-4xl opacity-30 block mb-3"></i>
      @if($search)
        Tidak ada OPD yang cocok dengan "<b>{{ $search }}</b>"
      @else
        Belum ada akun OPD yang terdaftar.
      @endif
    </div>
  @else
    {{-- Encode data ke JSON untuk modal JS --}}
    @php
      $opdsJson = $opds->map(function ($o) use ($penilaianMap) {
        $ps = $penilaianMap->get($o->id, collect());
        return [
          'id'       => $o->id,
          'nama'     => $o->nama ?? $o->username,
          'username' => $o->username,
          'riwayat'  => $ps->map(fn ($p) => [
            'tahun'         => $p->tahun,
            'nilai_akhir'   => $p->nilai_akhir,
            'catatan'       => $p->catatan,
            'file_url'      => $p->file ? asset('storage/' . $p->file) : null,
            'original_name' => $p->original_name ?? ($p->file ? basename($p->file) : null),
          ])->values()->all(),
        ];
      })->values();
    @endphp

    <div class="overflow-x-auto">
      <table class="w-full text-xs md:text-sm">
        <thead>
          <tr class="border-b border-(--border-strong) bg-(--sidebar-bg)">
            <th class="px-4 py-3 text-left text-(--muted) font-semibold uppercase tracking-wider text-[10px] w-10">#</th>
            <th class="px-4 py-3 text-left text-(--muted) font-semibold uppercase tracking-wider text-[10px] min-w-[200px]">Nama OPD</th>
            <th class="px-4 py-3 text-center text-(--muted) font-semibold uppercase tracking-wider text-[10px]">
              Nilai {{ now()->year }}
            </th>
            <th class="px-4 py-3 text-center text-(--muted) font-semibold uppercase tracking-wider text-[10px]">Total Entri</th>
            <th class="px-4 py-3 text-center text-(--muted) font-semibold uppercase tracking-wider text-[10px]">File</th>
            <th class="px-4 py-3 text-right text-(--muted) font-semibold uppercase tracking-wider text-[10px] min-w-[120px]">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($opds as $i => $opd)
            @php
              $entries  = $penilaianMap->get($opd->id, collect());
              $thisYear = $entries->firstWhere('tahun', now()->year);
              $hasFile  = $entries->whereNotNull('file')->isNotEmpty();
              $rowNo    = ($opds->currentPage() - 1) * $opds->perPage() + $i + 1;
            @endphp
            <tr class="border-b border-(--border-strong) hover:bg-white/5 transition-colors">
              <td class="px-4 py-3 text-(--muted) text-xs align-top">{{ $rowNo }}</td>
              <td class="px-4 py-3 align-top break-words whitespace-normal">
                <div class="font-semibold text-(--text) leading-tight">{{ $opd->nama ?? $opd->username }}</div>
                <div class="text-[10px] text-(--muted) mt-1">{{ $opd->username }}</div>
              </td>
              <td class="px-4 py-3 text-center align-top">
                @if($thisYear)
                  <span class="inline-flex items-center px-2.5 py-1 bg-(--brand)/10 text-(--brand) rounded-full text-xs font-bold">
                    {{ number_format($thisYear->nilai_akhir, 2) }}
                  </span>
                @else
                  <span class="text-[10px] text-(--muted) italic">—</span>
                @endif
              </td>
              <td class="px-4 py-3 text-center align-top">
                <span class="font-semibold text-(--text)">{{ $entries->count() }}</span>
              </td>
              <td class="px-4 py-3 text-center align-top">
                @if($hasFile)
                  <i class="bi bi-paperclip text-(--brand)"></i>
                @else
                  <span class="text-(--muted) text-[10px]">—</span>
                @endif
              </td>
              <td class="px-4 py-3 text-right align-top">
                <button type="button" onclick="openModal({{ $opd->id }})"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-(--brand)/10 text-(--brand) hover:bg-(--brand) hover:text-white rounded-lg transition-colors text-xs font-semibold">
                  <i class="bi bi-pencil-square text-xs"></i>
                  <span class="hidden sm:inline">Input / Edit</span>
                </button>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Pagination --}}
    @if($opds->hasPages())
      <div class="px-5 py-4 border-t border-(--border-strong) flex items-center justify-between gap-3 flex-wrap">
        <div class="text-xs text-(--muted)">
          Halaman {{ $opds->currentPage() }} dari {{ $opds->lastPage() }}
        </div>
        <div class="flex items-center gap-1.5 flex-wrap">
          {{-- Prev --}}
          @if($opds->onFirstPage())
            <span class="px-3 py-1.5 rounded-lg text-xs text-(--muted) border border-(--border-strong) opacity-40 cursor-not-allowed">
              <i class="bi bi-chevron-left"></i>
            </span>
          @else
            <a href="{{ $opds->previousPageUrl() }}"
              class="px-3 py-1.5 rounded-lg text-xs border border-(--border-strong) text-(--text) hover:bg-white/5 transition-colors">
              <i class="bi bi-chevron-left"></i>
            </a>
          @endif

          {{-- page numbers --}}
          @foreach($opds->getUrlRange(max(1, $opds->currentPage()-2), min($opds->lastPage(), $opds->currentPage()+2)) as $page => $url)
            @if($page == $opds->currentPage())
              <span class="px-3 py-1.5 rounded-lg text-xs bg-(--brand) text-white font-semibold min-w-[32px] text-center">{{ $page }}</span>
            @else
              <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-xs border border-(--border-strong) text-(--text) hover:bg-white/5 transition-colors min-w-[32px] text-center">{{ $page }}</a>
            @endif
          @endforeach

          {{-- Next --}}
          @if($opds->hasMorePages())
            <a href="{{ $opds->nextPageUrl() }}"
              class="px-3 py-1.5 rounded-lg text-xs border border-(--border-strong) text-(--text) hover:bg-white/5 transition-colors">
              <i class="bi bi-chevron-right"></i>
            </a>
          @else
            <span class="px-3 py-1.5 rounded-lg text-xs text-(--muted) border border-(--border-strong) opacity-40 cursor-not-allowed">
              <i class="bi bi-chevron-right"></i>
            </span>
          @endif
        </div>
      </div>
    @endif
  @endif
</div>

{{-- ===== MODAL ===== --}}
<div id="modalNilai" class="fixed inset-0 z-50 flex items-center justify-center hidden" aria-modal="true">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>

  <div class="relative w-full max-w-lg mx-4 bg-(--panel) border border-(--border-strong) rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

    {{-- Modal header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-(--border-strong) shrink-0">
      <div>
        <div class="font-semibold text-sm md:text-base text-(--text)" id="modalOpdName">Input Nilai</div>
        <div class="text-[10px] text-(--muted)" id="modalOpdUser"></div>
      </div>
      <button onclick="closeModal()" class="p-1.5 text-(--muted) hover:text-(--text) rounded-lg hover:bg-white/10 transition-colors">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    {{-- Modal body --}}
    <div class="overflow-y-auto flex-1 p-5">
      <form id="modalForm" method="POST" action="{{ route('penilaian-akhir.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="user_id" id="modalUserId">
        {{-- preserve pagination state --}}
        <input type="hidden" name="page" value="{{ request('page', 1) }}">
        <input type="hidden" name="search" value="{{ $search }}">

        <div class="text-[10px] font-semibold text-(--muted) uppercase tracking-wider mb-3">Tambah / Update Nilai</div>

        <div class="grid grid-cols-2 gap-3 mb-3">
          <div>
            <label class="block text-(--muted) text-xs mb-1.5">Tahun</label>
            <input type="hidden" name="tahun" value="{{ now()->year }}">
            <div class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs font-semibold select-none cursor-not-allowed opacity-70">
              {{ now()->year }} <span class="text-(--muted) font-normal">(tahun berjalan)</span>
            </div>
          </div>
          <div>
            <label class="block text-(--muted) text-xs mb-1.5">Nilai Akhir (1–5) <span class="text-red-500">*</span></label>
            <input type="number" name="nilai_akhir" id="modalNilaiInput" min="1" max="5" step="0.01"
              placeholder="cth: 3.50"
              class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all"
              required>
          </div>
        </div>

        <div class="mb-3">
          <label class="block text-(--muted) text-xs mb-1.5">Catatan (opsional)</label>
          <textarea name="catatan" id="modalCatatan" maxlength="1000" rows="3"
            placeholder="Catatan tambahan..."
            class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all resize-y"></textarea>
        </div>

        <div class="mb-4">
          <label class="block text-(--muted) text-xs mb-1.5">File Ketentuan <span class="text-[10px]">(PDF/DOC/Gambar, max 10MB)</span></label>
          <input type="file" name="file" id="modalFile"
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.bmp"
            class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-(--brand)/10 file:text-(--brand) hover:file:bg-(--brand)/20 transition-all cursor-pointer">
        </div>

        <button type="submit"
          class="w-full py-2.5 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center justify-center gap-2 transition-opacity font-semibold text-sm">
          <i class="bi bi-save2"></i> Simpan Nilai
        </button>
      </form>

      {{-- Riwayat --}}
      <div id="modalRiwayat" class="mt-5 hidden">
        <div class="border-t border-(--border-strong) pt-4">
          <div class="text-[10px] font-semibold text-(--muted) uppercase tracking-wider mb-2">Riwayat Tersimpan</div>
          <div id="modalRiwayatList" class="space-y-2"></div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Hidden delete form --}}
<form id="deleteForm" method="POST" action="{{ route('penilaian-akhir.destroy') }}" class="hidden">
  @csrf
  @method('DELETE')
  <input type="hidden" name="user_id" id="deleteUserId">
  <input type="hidden" name="tahun" id="deleteTahun">
  <input type="hidden" name="page" value="{{ request('page', 1) }}">
  <input type="hidden" name="search" value="{{ $search }}">
</form>

<script>
  const opdsData   = {!! json_encode($opdsJson ?? collect()) !!};
  const currentYear = {{ now()->year }};
  let activeOpdId  = null;

  function openModal(opdId) {
    const opd = opdsData.find(o => o.id === opdId);
    if (!opd) return;
    activeOpdId = opdId;

    document.getElementById('modalOpdName').textContent  = opd.nama;
    document.getElementById('modalOpdUser').textContent   = opd.username;
    document.getElementById('modalUserId').value          = opdId;
    document.getElementById('modalNilaiInput').value      = '';
    document.getElementById('modalCatatan').value         = '';
    document.getElementById('modalFile').value            = '';

    // Pre-fill jika sudah ada nilai tahun ini
    const existing = opd.riwayat.find(r => r.tahun === currentYear);
    if (existing) {
      document.getElementById('modalNilaiInput').value = existing.nilai_akhir;
      document.getElementById('modalCatatan').value    = existing.catatan || '';
    }

    renderRiwayat(opd.riwayat);
    document.getElementById('modalNilai').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('modalNilaiInput').focus(), 80);
  }

  function closeModal() {
    document.getElementById('modalNilai').classList.add('hidden');
    document.body.style.overflow = '';
    activeOpdId = null;
  }

  function renderRiwayat(riwayat) {
    const list    = document.getElementById('modalRiwayatList');
    const wrapper = document.getElementById('modalRiwayat');
    if (!riwayat?.length) { wrapper.classList.add('hidden'); return; }
    wrapper.classList.remove('hidden');
    list.innerHTML = riwayat.map(r => `
      <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 bg-(--sidebar-bg) rounded-xl px-4 py-3 min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 w-full min-w-0">
          <div class="sm:col-span-2">
            <div class="text-[9px] text-(--muted) uppercase tracking-wider">Tahun</div>
            <div class="font-semibold text-sm text-(--text)">${r.tahun}</div>
          </div>
          <div class="sm:col-span-2">
            <div class="text-[9px] text-(--muted) uppercase tracking-wider">Nilai</div>
            <div class="font-bold text-base text-(--brand)">${parseFloat(r.nilai_akhir).toFixed(2)}</div>
          </div>
          ${r.catatan || r.file_url ? `
          <div class="sm:col-span-8 flex flex-col gap-2 min-w-0">
            ${r.catatan ? `
              <div class="min-w-0">
                <div class="text-[9px] text-(--muted) uppercase tracking-wider">Catatan</div>
                <div class="text-xs text-(--text) break-words whitespace-normal leading-relaxed mt-0.5">${esc(r.catatan)}</div>
              </div>
            ` : ''}
            ${r.file_url ? `
              <div class="min-w-0">
                <div class="text-[9px] text-(--muted) uppercase tracking-wider">File</div>
                <a href="${r.file_url}" target="_blank" class="inline-flex items-center gap-1.5 text-xs text-(--brand) hover:underline mt-0.5 max-w-full break-all">
                  <i class="bi bi-file-earmark-arrow-down shrink-0"></i> 
                  <span class="break-words">${esc(r.original_name || 'Lihat File')}</span>
                </a>
              </div>
            ` : ''}
          </div>
          ` : ''}
        </div>
        <button type="button" onclick="confirmDelete(${activeOpdId}, ${r.tahun})" title="Hapus Entri ${r.tahun}"
          class="p-2 text-red-400 hover:text-red-600 hover:bg-red-500/10 rounded-lg transition-colors shrink-0 self-end sm:self-start">
          <i class="bi bi-trash3 text-sm"></i>
        </button>
      </div>
    `).join('');
  }

  function confirmDelete(userId, tahun) {
    const opd = opdsData.find(o => o.id === userId);
    if (!confirm(`Hapus nilai akhir tahun ${tahun} untuk ${opd?.nama ?? 'OPD ini'}?`)) return;
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteTahun').value  = tahun;
    document.getElementById('deleteForm').submit();
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

@endsection
