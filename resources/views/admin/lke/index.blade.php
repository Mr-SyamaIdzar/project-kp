@extends('layouts.admin')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Lembar Kerja Evaluasi</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Filter OPD, pilih tahun export, lalu export ke Excel.</div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('lke.index') }}" class="flex flex-col sm:flex-row flex-wrap gap-3 items-start sm:items-end">
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="user_id" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="0">Semua OPD Terdaftar</option>
        @foreach($opds as $u)
          <option value="{{ $u->id }}" {{ (int)$userId === (int)$u->id ? 'selected' : '' }}>
            {{ $u->nama ?? $u->username }} ({{ $u->username }})
          </option>
        @endforeach
      </select>
    </div>
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="export_year" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="0">Semua Tahun Created LKE</option>
        @foreach(($exportYears ?? collect()) as $y)
          <option value="{{ $y }}" {{ (int)($exportYear ?? 0) === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <button class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm" type="submit">
        <i class="bi bi-funnel"></i> Terapkan
      </button>
      <a class="px-3 py-2 bg-green-600 border border-green-500 text-white rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 text-xs md:text-sm" href="{{ route('lke.export', [
        'user_id' => $userId, 'nama_kegiatan' => $namaKegiatan, 'nomor_rekomendasi' => $nomorRekomendasi, 'export_year' => $exportYear ?? 0,
      ]) }}">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
      </a>
      @if((int)$userId > 0 || (int)($exportYear ?? 0) > 0)
        <a class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm" href="{{ route('lke.index') }}">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse min-w-200">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold text-(--muted)">
        <th class="p-4 w-16">No</th>
        <th class="p-4">User</th>
        <th class="p-4 min-w-55">Nama Kegiatan</th>
        <th class="p-4 w-56">Nomor Rekomendasi</th>
        <th class="p-4 w-64">Ringkasan</th>
        <th class="p-4 w-32 text-center">Aksi</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($rows as $row)
        @php
          $u = $userMap[$row->user_id] ?? null;
          $t = $tahunMap[$row->tahun_id] ?? null;
        @endphp
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($rows->currentPage()-1)*$rows->perPage() + $loop->iteration }}</td>

          <td class="p-4">
            <div class="font-semibold text-(--text)">{{ $u->nama ?? $u->username ?? '-' }}</div>
            <div class="text-(--muted) text-[10px] md:text-xs mt-1">
              username: {{ $u->username ?? '-' }} | tahun: {{ $t->tahun ?? $row->tahun_id }}
            </div>
          </td>

          <td class="p-4 font-semibold whitespace-normal max-w-85 wrap-break-word">{{ $row->nama_kegiatan }}</td>

          <td class="p-4 font-mono text-xs md:text-sm whitespace-normal max-w-85 wrap-break-word">{{ $row->nomor_rekomendasi }}</td>

          <td class="p-4">
            <div class="flex flex-wrap gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                <i class="bi bi-check2-circle opacity-70"></i> Final: <b class="font-bold">{{ (int)$row->cnt_final }}</b>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                <i class="bi bi-hourglass-split opacity-70"></i> Draft: <b class="font-bold">{{ (int)$row->cnt_draft }}</b>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                <i class="bi bi-diagram-3 opacity-70"></i> Total: <b class="font-bold">{{ (int)$row->cnt_total }}</b>
              </span>
            </div>
          </td>

          <td class="p-4 text-center">
            <a class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-cyan-500/50 text-cyan-500 hover:bg-cyan-500 hover:text-white rounded-xl transition-colors inline-flex items-center gap-2 text-xs md:text-sm"
               href="{{ route('lke.show', [
                  'user_id' => $row->user_id,
                  'tahun_id' => $row->tahun_id,
                  'nama_kegiatan' => $row->nama_kegiatan,
                  'nomor_rekomendasi' => $row->nomor_rekomendasi,
               ]) }}">
              <i class="bi bi-eye"></i> Detail
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="p-8 text-center text-(--muted)">Belum ada data.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
  <div class="text-(--muted) text-xs md:text-sm">
    @if($rows->total() > 0)
      Menampilkan {{ $rows->firstItem() }}-{{ $rows->lastItem() }} dari {{ $rows->total() }} data
    @else
      Menampilkan 0 data
    @endif
  </div>

  <div class="pagination-wrap">
    {{ $rows->onEachSide(1)->links() }}
  </div>
</div>
@endsection
