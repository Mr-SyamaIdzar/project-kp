@extends('layouts.admin')

@php
  $title = 'Deadline Submit';
  $header = 'Deadline Submit';
  $subheader = 'Atur batas waktu final submit LKE per tahun.';
@endphp

@section('content')
<style>
  /* Keep datetime picker accessible on dark themes without too many custom rules */
  .deadline-input { color-scheme: light; }
  html[data-theme="dark"] .deadline-input { color-scheme: dark; }
</style>

<div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
  <div>
    <div class="font-semibold text-lg md:text-xl">Atur Deadline per Tahun</div>
    <div class="text-(--muted) text-xs md:text-sm mt-1">Kosongkan deadline jika ingin tidak dibatasi.</div>
  </div>
</div>

{{-- Search --}}
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('deadlines.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
    <div class="md:col-span-8 lg:col-span-6">
      <input type="text" name="q" value="{{ $q ?? '' }}" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) placeholder-(--muted) rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all"
             inputmode="numeric" pattern="[0-9]*" maxlength="4"
             placeholder="Cari tahun (contoh: 2026)">
      <p class="text-[10px] md:text-xs text-(--muted) mt-1 ml-1">Hanya angka, 4 digit.</p>
    </div>
    <div class="md:col-span-4 lg:col-span-6 flex gap-2">
      <button class="px-3 md:px-4 py-2 md:py-2.5 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2" type="submit">
        <i class="bi bi-search"></i> Cari
      </button>
      @if(!empty($q))
        <a href="{{ route('deadlines.index') }}" class="px-3 md:px-4 py-2 md:py-2.5 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold">
        <th class="p-4 w-20">No</th>
        <th class="p-4 w-40">Tahun</th>
        <th class="p-4 min-w-90">Deadline Submit</th>
        <th class="p-4 w-48">Status</th>
        <th class="p-4 w-56 text-center">Aksi</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-(--border-strong)">
      @forelse($items as $t)
        @php
          $deadline = $t->deadline_submit;
          $isExpired = $deadline ? now()->greaterThan($deadline) : false;

          $val = $deadline ? \Carbon\Carbon::parse($deadline)->format('Y-m-d\TH:i') : '';
          $formatted = $deadline
            ? \Carbon\Carbon::parse($deadline)->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
            : null;
        @endphp

        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($items->currentPage()-1)*$items->perPage() + $loop->iteration }}</td>
          <td class="p-4 font-semibold">{{ $t->tahun }}</td>

          <td class="p-4">
            <form method="POST"
                  action="{{ route('deadlines.update', $t->id) }}"
                  class="flex flex-wrap items-center gap-3">
              @csrf
              @method('PUT')

              <input type="datetime-local"
                     name="deadline_submit"
                     class="deadline-input bg-white text-gray-900 border border-gray-300 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-purple-500 outline-none"
                     style="height:36px;"
                     value="{{ old('deadline_submit', $val) }}">

              <button class="px-2 md:px-3 py-1 md:py-1.5 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 text-xs md:text-sm transition-opacity" type="submit">
                <i class="bi bi-save"></i> Simpan
              </button>
            </form>

            @error('deadline_submit')
              <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div>
            @enderror
          </td>

          <td class="p-4">
            @if(!$deadline)
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-yellow-100 text-yellow-800 border border-yellow-400">Belum diatur</span>
            @elseif($isExpired)
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-red-100 text-red-800 border border-red-400">Lewat deadline</span>
            @else
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-400">Aktif</span>
            @endif
          </td>

          <td class="p-4 text-center">
            <div class="flex justify-center">
              <form method="POST"
                    action="{{ route('deadlines.destroy', $t->id) }}"
                    class="form-delete"
                    data-message="Yakin hapus deadline tahun {{ $t->tahun }}?">
                @csrf
                @method('DELETE')
                <button class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-red-500/50 text-red-500 hover:bg-red-500 hover:text-white rounded-xl transition-colors flex items-center gap-2 text-xs md:text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-red-500" type="submit" {{ !$deadline ? 'disabled' : '' }}>
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="p-8 text-center text-(--muted)">
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
  document.addEventListener('input', function(e){
    if(e.target && e.target.name === 'q'){
      e.target.value = e.target.value.replace(/\D/g,'').slice(0,4);
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
