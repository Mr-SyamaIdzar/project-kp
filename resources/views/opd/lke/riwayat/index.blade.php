@extends('layouts.opd')

@php
  $title = 'Riwayat LKE';
  $header = 'Riwayat LKE';
  $subheader = 'Lihat riwayat final/revisi dan buka detail indikator.';
@endphp

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">Riwayat LKE</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Lihat riwayat final/revisi dan buka detail indikator.</div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('opd.lke.riwayat.index') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
    <div class="w-full sm:w-64">
      <select name="tahun_id" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="0">Semua Tahun</option>
        @foreach($tahuns as $t)
          <option value="{{ $t->id }}" {{ (int)$tahunId === (int)$t->id ? 'selected' : '' }}>{{ $t->tahun }}</option>
        @endforeach
      </select>
    </div>
    <div class="flex gap-2 shrink-0">
      <button class="px-3 py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm" type="submit">
        <i class="bi bi-funnel"></i> Terapkan
      </button>
      @if((int)$tahunId > 0)
        <a class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm" href="{{ route('opd.lke.riwayat.index') }}">
          <i class="bi bi-x-circle"></i> Reset
        </a>
      @endif
    </div>
  </form>
</div>

<div class="overflow-x-auto bg-(--panel) border border-(--border-strong) rounded-2xl">
  <table class="w-full text-(--text) border-collapse whitespace-nowrap min-w-[760px]">
    <thead>
      <tr class="border-b border-(--border-strong) bg-black/5 text-left text-xs md:text-sm font-semibold text-(--muted)">
        <th class="p-4 w-16">No</th>
        <th class="p-4 w-24">Tahun</th>
        <th class="p-4">Nama Kegiatan</th>
        <th class="p-4 w-48">Nomor Rekomendasi</th>
        <th class="p-4 w-72">Ringkasan</th>
        <th class="p-4 w-32 text-center">Aksi</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($rows as $row)
        @php $t = $tahunMap[$row->tahun_id] ?? null; @endphp
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($rows->currentPage()-1)*$rows->perPage() + $loop->iteration }}</td>
          <td class="p-4">{{ $t->tahun ?? $row->tahun_id }}</td>
          <td class="p-4 font-semibold whitespace-normal">{{ $row->nama_kegiatan }}</td>
          <td class="p-4 font-mono text-xs md:text-sm whitespace-normal">{{ $row->nomor_rekomendasi }}</td>
          <td class="p-4">
            <div class="flex flex-wrap gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                <i class="bi bi-check2-circle opacity-70"></i> Terisi: <b class="font-bold">{{ (int)($row->cnt_terisi ?? 0) }}</b>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                <i class="bi bi-pencil-square opacity-70"></i> Revisi: <b class="font-bold">{{ (int)$row->cnt_revisi }}</b>
              </span>
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold bg-white/5 border border-(--border-strong) text-(--text)">
                 <i class="bi bi-diagram-3 opacity-70"></i> Total: <b class="font-bold">{{ (int)($totalIndikator ?? $row->cnt_total) }}</b>
              </span>
              @php $sp = (string)($row->status_paket ?? 'final'); @endphp
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-semibold border-none text-white
                {{ $sp === 'locked' ? 'bg-blue-600' : ($sp === 'revisi' ? 'bg-red-500' : 'bg-emerald-500') }}">
                <i class="bi {{ $sp === 'locked' ? 'bi-shield-lock' : ($sp === 'revisi' ? 'bi-exclamation-triangle' : 'bi-check-circle') }}"></i>
                Status:
                <b class="font-bold">
                  {{ $sp === 'locked' ? 'Final BPS (Dikunci)' : ($sp === 'revisi' ? 'Revisi' : 'Final') }}
                </b>
              </span>
            </div>
          </td>
          <td class="p-4 text-center">
            <a class="px-2 md:px-3 py-1 md:py-1.5 bg-transparent border border-cyan-500/50 text-cyan-500 hover:bg-cyan-500 hover:text-white rounded-xl transition-colors inline-flex items-center gap-2 text-xs md:text-sm"
               href="{{ route('opd.lke.riwayat.show', [
                 'tahun_id' => $row->tahun_id,
                 'nama_kegiatan' => $row->nama_kegiatan,
                 'nomor_rekomendasi' => $row->nomor_rekomendasi,
               ]) }}">
              <i class="bi bi-eye"></i> Detail
            </a>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="p-8 text-center text-(--muted)">Belum ada riwayat LKE.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-6 flex justify-end pagination-wrap">
  {{ $rows->onEachSide(1)->links() }}
</div>
@endsection
