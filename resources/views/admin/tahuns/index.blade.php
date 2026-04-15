@extends('layouts.admin')
@php
  $title = 'Kelola Tahun';
  $header = 'Tahun';
  $subheader = 'Tambah, edit, dan hapus master tahun.';
@endphp

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Data Tahun</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Master tahun untuk evaluasi</div>
  </div>
  <a href="{{ route('tahun.create') }}" class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 font-medium transition-opacity text-xs md:text-sm shrink-0">
    <i class="bi bi-plus-circle"></i> Tambah Tahun
  </a>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('tahun.index') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
    <div class="flex-1 w-full">
      <div class="relative flex items-center w-full">
        <span class="absolute left-4 text-(--muted)"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="{{ $q ?? request('q') }}"
               class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) placeholder-(--muted) rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all text-xs md:text-sm"
               inputmode="numeric" pattern="[0-9]*" placeholder="Cari tahun (contoh: 2024, 2025, 2026)...">
      </div>
      <p class="text-(--muted) text-[10px] mt-1 ml-1">Contoh: <b>2024</b>, <b>2025</b>, <b>2026</b>.</p>
    </div>
    <div class="flex gap-2 shrink-0">
      <button class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm" type="submit">
        <i class="bi bi-search"></i> Cari
      </button>
      @if(!empty($q))
        <a href="{{ route('tahun.index') }}" class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse min-w-100">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold text-(--muted)">
        <th class="p-4 w-16">No</th>
        <th class="p-4">Tahun</th>
        <th class="p-4 w-52 text-center">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($items as $t)
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($items->currentPage()-1)*$items->perPage() + $loop->iteration }}</td>
          <td class="p-4 font-semibold text-(--text)">{{ $t->tahun }}</td>
          <td class="p-4 text-center">
            <div class="flex items-center justify-center gap-2">
              <a href="{{ route('tahun.edit', $t->id) }}" class="px-3 py-1.5 bg-transparent border border-cyan-500/50 text-cyan-500 hover:bg-cyan-500 hover:text-white rounded-xl transition-colors flex items-center gap-2 text-xs md:text-sm">
                <i class="bi bi-pencil-square"></i> Edit
              </a>
              <form action="{{ route('tahun.destroy', $t->id) }}" method="POST" class="inline-block form-delete" data-message="Yakin hapus tahun {{ $t->tahun }}?">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-red-500/50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-colors flex items-center gap-2 text-xs md:text-sm">
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="p-8 text-center text-(--muted)">
            @if(!empty($q))
              Tidak ada hasil untuk pencarian: <b>{{ $q }}</b>
            @else
              Belum ada data tahun.
            @endif
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
  <div class="text-xs md:text-sm text-(--muted)">
    @if($items->total() > 0)
      Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ $items->total() }} data
      @if(!empty($q)) <span class="ml-1">(filter: <b>{{ $q }}</b>)</span> @endif
    @else
      Menampilkan 0 data
    @endif
  </div>

  <div class="pagination-wrap">
    {{ $items->onEachSide(1)->links() }}
  </div>
</div>

<script>
  document.addEventListener('input', function(e) {
    if (e.target && e.target.name === 'q') {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    }
  });
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.form-delete').forEach(form => {
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const msg = form.getAttribute('data-message');
        if (typeof window.showConfirm === 'function') {
           window.showConfirm(msg, function() { form.submit(); }, 'Konfirmasi', 'warning', 'Ya, Hapus');
        } else {
           if (confirm(msg)) form.submit();
        }
      });
    });
  });
</script>
@endsection
