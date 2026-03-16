@extends('layouts.admin')

@section('content')
<div class="flex justify-center">
  <div class="w-full lg:w-1/2">
    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong)">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h5 class="font-semibold text-base md:text-lg mb-0 text-(--text)">Edit Tahun</h5>
          <div class="text-(--muted) text-xs md:text-sm">Ubah tahun 4 digit (contoh: 2026)</div>
        </div>
        <i class="bi bi-pencil-square text-2xl md:text-3xl text-(--muted) opacity-50"></i>
      </div>

      <form method="POST" action="{{ route('tahun.update', $tahun->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-4">
          <label class="block text-(--muted) text-xs md:text-sm mb-1">Tahun</label>
          <input type="text" name="tahun"
                class="w-full bg-(--sidebar-bg) border {{ $errors->has('tahun') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 focus:outline-none focus:ring-2 transition-all"
                value="{{ old('tahun', $tahun->tahun) }}" inputmode="numeric" pattern="[0-9]*" maxlength="4" required>
          @error('tahun') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Hanya angka, 4 digit (1900–2100).</small>
        </div>

        <div class="flex justify-between mt-8">
          <a href="{{ route('tahun.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
          <button type="submit" class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity">
            <i class="bi bi-save"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener('input', function(e) {
    if (e.target && e.target.name === 'tahun') {
      e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    }
  });
</script>
@endsection
