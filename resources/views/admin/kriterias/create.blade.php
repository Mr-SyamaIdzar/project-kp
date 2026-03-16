@extends('layouts.admin')

@section('content')
<div class="flex justify-center">
  <div class="w-full lg:w-4/5">

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong)">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h5 class="font-semibold text-base md:text-lg mb-0 text-(--text)">Tambah Kriteria</h5>
          <div class="text-(--muted) text-xs md:text-sm">Pilih indikator (dari Domain), tingkat, lalu isi kriteria.</div>
        </div>
        <i class="bi bi-list-check text-2xl md:text-3xl text-(--muted) opacity-50"></i>
      </div>

      {{-- Tombol Tambah Kriteria (Sekarang di atas form) --}}
      <div class="mb-6 flex justify-end">
        <button type="button" id="addRowBtn" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--brand) text-(--brand) bg-transparent rounded-xl hover:bg-(--brand) hover:text-white flex items-center gap-2 transition-colors font-semibold">
          <i class="bi bi-plus-circle"></i> Tambah Kriteria
        </button>
      </div>

      <form method="POST" action="{{ route('kriterias.store') }}">
        @csrf

        <div id="kriteriaContainer" class="flex flex-col gap-4">
          {{-- Baris pertama --}}
          <div class="kriteria-row p-4 border border-(--border-strong) rounded-2xl bg-black/5">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
              <div class="md:col-span-4">
                <label class="block text-(--muted) text-xs md:text-sm mb-1">Pilih Indikator</label>
                <select name="items[0][domain_id]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all domain-select" required>
                  <option value="">-- Pilih Indikator --</option>
                  @foreach($domains as $d)
                    <option value="{{ $d->id }}">
                      ({{ $d->kode }}) {{ $d->nama_indikator }}
                    </option>
                  @endforeach
                </select>
                <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Sumber dari data Domain.</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-(--muted) text-xs md:text-sm mb-1">Tingkat</label>
                <input type="number" name="items[0][tingkat]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all tingkat-input" min="1" max="100" required>
                <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Angka (contoh: 1-5).</small>
              </div>

              <div class="md:col-span-6">
                <label class="block text-(--muted) text-xs md:text-sm mb-1">Kriteria</label>
                <div class="flex gap-2">
                  <input type="text" name="items[0][kriteria]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all" maxlength="500" required>
                  <button type="button" class="remove-row shrink-0 w-10 h-10 flex items-center justify-center border border-red-500/50 text-red-500 rounded-xl hover:bg-red-500/10 transition-colors" style="display:none;">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
                <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Maks 500 karakter.</small>
              </div>
            </div>
          </div>
        </div>

        {{-- tombol submit dan kembali --}}
        <div class="mt-6 flex flex-col md:flex-row justify-between items-center gap-4 border-t border-(--border-strong) pt-6">
          <div></div> {{-- Spacer --}}

          <div class="flex gap-3">
            <a href="{{ route('kriterias.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-transparent text-(--text) rounded-xl hover:bg-white/5 flex items-center gap-2 transition-colors">
              <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 transition-opacity">
              <i class="bi bi-save"></i> Simpan Semua
            </button>
          </div>
        </div>

        @if ($errors->any())
          <div class="bg-red-500/10 border border-red-500/50 rounded-xl p-4 mt-6 text-xs md:text-sm text-(--text)">
            <div class="font-semibold mb-2 text-red-500">Ada error:</div>
            <ul class="list-disc pl-5 m-0 space-y-1">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

      </form>
    </div>

  </div>
</div>

<script>
  const container = document.getElementById('kriteriaContainer');
  const addBtn = document.getElementById('addRowBtn');

  let idx = container.querySelectorAll('.kriteria-row').length;

  const indikatorOptions = `{!! collect($domains)->map(function($d){
      return '<option value="'.$d->id.'">('.$d->kode.') '.e($d->nama_indikator).'</option>';
  })->implode('') !!}`;

  function refreshRemoveButtons(){
    const rows = container.querySelectorAll('.kriteria-row');
    rows.forEach((row) => {
      const btn = row.querySelector('.remove-row');
      btn.style.display = rows.length > 1 ? 'flex' : 'none';
    });
  }

  function reIndexNames(){
    const rows = container.querySelectorAll('.kriteria-row');
    rows.forEach((row, i) => {
      row.querySelector('.domain-select').name = `items[${i}][domain_id]`;
      row.querySelector('.tingkat-input').name = `items[${i}][tingkat]`;
      row.querySelector('input[type="text"]').name = `items[${i}][kriteria]`;
    });
    idx = rows.length;
  }

  function getLastRowData() {
    const rows = container.querySelectorAll('.kriteria-row');
    if (!rows.length) return { domainId: '', tingkat: '' };
    const lastRow = rows[rows.length - 1];
    return {
      domainId: lastRow.querySelector('.domain-select')?.value || '',
      tingkat: lastRow.querySelector('.tingkat-input')?.value || ''
    };
  }

  // Calculate the next Tingkat value
  function getNextTingkat(lastTingkat) {
    if (!lastTingkat) return '';
    let next = parseInt(lastTingkat, 10) + 1;
    if (next > 5) {
      next = 1;
    }
    return next;
  }

  addBtn.addEventListener('click', () => {
    const { domainId, tingkat: lastTingkat } = getLastRowData();
    const nextTingkat = getNextTingkat(lastTingkat);

    const row = document.createElement('div');
    row.className = 'kriteria-row p-4 border border-(--border-strong) rounded-2xl bg-black/5';

    row.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
        <div class="md:col-span-4">
          <label class="block text-(--muted) text-xs md:text-sm mb-1">Pilih Indikator</label>
          <select name="items[${idx}][domain_id]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all domain-select" required>
            <option value="">-- Pilih Indikator --</option>
            ${indikatorOptions}
          </select>
          <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Sumber dari data Domain.</small>
        </div>

        <div class="md:col-span-2">
          <label class="block text-(--muted) text-xs md:text-sm mb-1">Tingkat</label>
          <input type="number" name="items[${idx}][tingkat]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all tingkat-input" min="1" max="100" required value="${nextTingkat}">
          <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Angka (contoh: 1-5).</small>
        </div>

        <div class="md:col-span-6">
          <label class="block text-(--muted) text-xs md:text-sm mb-1">Kriteria</label>
          <div class="flex gap-2">
            <input type="text" name="items[${idx}][kriteria]" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all text-input-kriteria" maxlength="500" required>
            <button type="button" class="remove-row shrink-0 w-10 h-10 flex items-center justify-center border border-red-500/50 text-red-500 rounded-xl hover:bg-red-500/10 transition-colors">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          <small class="text-(--muted) text-[10px] md:text-xs mt-1 block">Maks 500 karakter.</small>
        </div>
      </div>
    `;

    container.appendChild(row);

    const newSelect = row.querySelector('.domain-select');
    if (domainId) newSelect.value = domainId;

    idx++;
    refreshRemoveButtons();
    
    // Focus on the logic required field
    if (nextTingkat) {
        row.querySelector('.text-input-kriteria').focus();
    } else {
        row.querySelector('.tingkat-input').focus();
    }
  });

  container.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-row');
    if(!btn) return;
    btn.closest('.kriteria-row')?.remove();
    reIndexNames();
    refreshRemoveButtons();
  });

  reIndexNames();
  refreshRemoveButtons();
</script>

@endsection
