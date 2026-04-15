@extends('layouts.admin')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Data Kriteria</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Kelola kriteria per indikator</div>
  </div>
  <a href="{{ route('kriterias.create') }}" class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 font-medium transition-opacity text-xs md:text-sm shrink-0">
    <i class="bi bi-plus-circle"></i> Tambah Kriteria
  </a>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('kriterias.index') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
    <div class="flex-1 w-full">
      <div class="relative flex items-center w-full">
        <span class="absolute left-4 text-(--muted)"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="{{ $q ?? request('q') }}"
               class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) placeholder-(--muted) rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all text-xs md:text-sm"
               placeholder="Cari domain / indikator / tingkat / kriteria...">
      </div>
      <p class="text-[10px] text-(--muted) mt-1 ml-1">Contoh: <b>indikator</b>, <b>tingkat 2</b>, atau kata di kolom kriteria.</p>
    </div>
    <div class="flex gap-2 shrink-0">
      <button class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm" type="submit">
        <i class="bi bi-search"></i> Cari
      </button>
      @if(!empty($q))
        <a href="{{ route('kriterias.index') }}" class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse min-w-175">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold text-(--muted)">
        <th class="p-4 w-16">No</th>
        <th class="p-4 min-w-45">Domain</th>
        <th class="p-4 min-w-55">Indikator</th>
        <th class="p-4 w-24">Tingkat</th>
        <th class="p-4 min-w-[320px]">Kriteria</th>
        <th class="p-4 w-52 text-center">Aksi</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($kriterias as $k)
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($kriterias->currentPage()-1)*$kriterias->perPage() + $loop->iteration }}</td>
          <td class="p-4 font-semibold text-(--text)">{{ $k->domain?->nama_domain ?? '-' }}</td>
          <td class="p-4">{{ $k->domain?->nama_indikator ?? '-' }}</td>
          <td class="p-4">
            <span class="inline-flex items-center justify-center bg-gray-800 text-white text-[10px] md:text-xs font-semibold px-2.5 py-1 rounded-lg border border-gray-600">
              T{{ $k->tingkat }}
            </span>
          </td>
          <td class="p-4">{{ $k->kriteria }}</td>

          <td class="p-4 text-center">
            <div class="flex items-center justify-center gap-2">
              <a href="{{ route('kriterias.edit', $k->id) }}"
                 class="px-3 py-1.5 bg-transparent border border-cyan-500/50 text-cyan-500 hover:bg-cyan-500 hover:text-white rounded-xl transition-colors flex items-center gap-2 text-xs md:text-sm">
                <i class="bi bi-pencil-square"></i> Edit
              </a>

              <form action="{{ route('kriterias.destroy', $k->id) }}"
                    method="POST"
                    class="inline-block form-delete"
                    data-message="Yakin hapus kriteria ini?">
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
          <td colspan="6" class="p-8 text-center text-(--muted)">
            @if(!empty($q))
              Tidak ada hasil untuk pencarian: <b>{{ $q }}</b>
            @else
              Belum ada data kriteria.
            @endif
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
  <div class="text-xs md:text-sm text-(--muted)">
    @if($kriterias->total() > 0)
      Menampilkan {{ $kriterias->firstItem() }}–{{ $kriterias->lastItem() }} dari {{ $kriterias->total() }} data
      @if(!empty($q))
        <span class="ml-1">(filter: <b>{{ $q }}</b>)</span>
      @endif
    @else
      Menampilkan 0 data
    @endif
  </div>

  <div class="pagination-wrap">
    {{ $kriterias->onEachSide(1)->links() }}
  </div>
</div>
<script>
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
