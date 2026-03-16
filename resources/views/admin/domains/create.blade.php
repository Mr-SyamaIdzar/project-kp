@extends('layouts.admin')

@section('content')
<div class="flex justify-center">
  <div class="w-full lg:w-2/3">

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong)">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h5 class="font-semibold text-base md:text-lg mb-0 text-(--text)">Tambah</h5>
          <div class="text-(--muted) text-xs md:text-sm">Isi kode dan deskripsi domain/aspek/indikator.</div>
        </div>
        <i class="bi bi-diagram-3 text-2xl md:text-3xl text-(--muted) opacity-50"></i>
      </div>

      <form method="POST" action="{{ route('domains.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-3">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Kode</label>
            <input type="text" name="kode"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('kode') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('kode') }}"
                   inputmode="numeric" pattern="[0-9]*" required>
            @error('kode') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
            <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Hanya angka.</small>
          </div>

          <div class="md:col-span-9">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Nama Domain</label>
            <input type="text" name="nama_domain"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('nama_domain') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('nama_domain') }}" required>
            @error('nama_domain') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-6">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Nama Aspek</label>
            <input type="text" name="nama_aspek"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('nama_aspek') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('nama_aspek') }}" required>
            @error('nama_aspek') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-6">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Nama Indikator</label>
            <input type="text" name="nama_indikator"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('nama_indikator') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('nama_indikator') }}" required>
            @error('nama_indikator') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="flex justify-between mt-8">
          <a href="{{ route('domains.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>

          <button type="submit" class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity">
            <i class="bi bi-save"></i> Simpan
          </button>
        </div>
      </form>
    </div>

  </div>
</div>

<script>
  // Biar kode bener2 angka
  document.addEventListener('input', function(e){
    if(e.target && e.target.name === 'kode'){
      e.target.value = e.target.value.replace(/\D/g,'');
    }
  });
</script>
@endsection
