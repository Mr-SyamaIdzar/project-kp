@extends('layouts.admin')

@section('content')
<div class="flex justify-center">
  <div class="w-full lg:w-2/3">

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong)">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h5 class="font-semibold text-base md:text-lg mb-0 text-(--text)">Edit Kriteria</h5>
          <div class="text-(--muted) text-xs md:text-sm">Ubah indikator, tingkat, dan kriteria.</div>
        </div>
        <i class="bi bi-pencil-square text-2xl md:text-3xl text-(--muted) opacity-50"></i>
      </div>

      <form method="POST" action="{{ route('kriterias.update', $kriteria->id) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div class="md:col-span-4">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Pilih Indikator</label>
            <select name="domain_id" class="w-full bg-(--sidebar-bg) border {{ $errors->has('domain_id') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-all" required>
              <option value="">-- Pilih Indikator --</option>
              @foreach($domains as $d)
                <option value="{{ $d->id }}" {{ old('domain_id', $kriteria->domain_id)==$d->id ? 'selected' : '' }}>
                  ({{ $d->kode }}) {{ $d->nama_indikator }}
                </option>
              @endforeach
            </select>
            @error('domain_id') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-2">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Tingkat</label>
            <input type="number" name="tingkat"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('tingkat') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('tingkat', $kriteria->tingkat) }}"
                   min="1" max="100" required>
            @error('tingkat') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-6">
            <label class="block text-(--muted) text-xs md:text-sm mb-1">Kriteria</label>
            <input type="text" name="kriteria"
                   class="w-full bg-(--sidebar-bg) border {{ $errors->has('kriteria') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 transition-all"
                   value="{{ old('kriteria', $kriteria->kriteria) }}"
                   maxlength="500" required>
            @error('kriteria') <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="flex justify-between mt-8">
          <a href="{{ route('kriterias.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors">
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
@endsection
