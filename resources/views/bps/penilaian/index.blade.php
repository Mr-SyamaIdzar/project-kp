@extends('layouts.bps')

@php
  $title = 'LKE yang sudah dinilai';
  $header = 'LKE yang sudah dinilai';
  $subheader = 'Filter OPD, pilih tahun, lalu export ke Excel.';
@endphp

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <div class="font-bold text-lg md:text-xl">LKE yang sudah dinilai</div>
    <div class="text-(--muted) text-xs md:text-sm mt-0.5">Filter OPD, pilih tahun, lalu export ke Excel.</div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-4 mb-6">
  <form method="GET" action="{{ route('bps.penilaian.index') }}" class="flex flex-col sm:flex-row flex-wrap gap-3 items-start sm:items-end">
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="user_id" onchange="this.form.submit()" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="0">Semua OPD</option>
        @foreach($opds as $u)
          <option value="{{ $u->id }}" {{ (int)$userId === (int)$u->id ? 'selected' : '' }}>
            {{ $u->nama ?? $u->username }} ({{ $u->username }})
          </option>
        @endforeach
      </select>
    </div>
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="export_year" onchange="this.form.submit()" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="0">Semua Tahun</option>
        @foreach(($exportYears ?? collect()) as $y)
          <option value="{{ $y }}" {{ (int)($exportYear ?? 0) === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
        @endforeach
      </select>
    </div>
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="sort_opd" onchange="this.form.submit()" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="asc" {{ ($sortOpd ?? 'asc') === 'asc' ? 'selected' : '' }}>Urut OPD: A → Z</option>
        <option value="desc" {{ ($sortOpd ?? 'asc') === 'desc' ? 'selected' : '' }}>Urut OPD: Z → A</option>
      </select>
    </div>
    <div class="w-full sm:w-auto sm:flex-1 min-w-0">
      <select name="export_status" onchange="this.form.submit()" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
        <option value="all" {{ ($exportStatus ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
        <option value="done" {{ ($exportStatus ?? 'all') === 'done' ? 'selected' : '' }}>Sudah Dinilai Semua (Done)</option>
      </select>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <button type="submit" formaction="{{ route('bps.penilaian.export') }}" formmethod="GET" class="px-3 py-2 bg-green-600 border border-green-500 text-white rounded-xl hover:bg-green-700 transition-colors flex items-center gap-2 text-xs md:text-sm">
        <i class="bi bi-file-earmark-excel"></i> Export Excel
      </button>
      @if((int)$userId > 0 || (int)($exportYear ?? 0) > 0)
        <a class="px-3 py-2 bg-transparent border border-orange-500/50 text-orange-500 rounded-xl hover:bg-orange-500/10 transition-colors flex items-center gap-2 text-xs md:text-sm" href="{{ route('bps.penilaian.index') }}">
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
        <th class="p-4 w-44">Status</th>
        <th class="p-4 w-64">Ringkasan</th>
        <th class="p-4 w-32 text-center">Aksi</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-(--border-strong) text-xs md:text-sm">
      @forelse($rows as $row)
        @php
          $u = $userMap[$row->user_id] ?? null;
          $createdYear = $row->package_created_at
            ? \Illuminate\Support\Carbon::parse($row->package_created_at)->format('Y')
            : '-';

          $scored = (int)($row->cnt_scored ?? 0);
          $domainsTotal = (int)($totalDomains ?? 0);
          $status = 'none';
          if ($scored <= 0) $status = 'none';
          elseif ($domainsTotal > 0 && $scored >= $domainsTotal) $status = 'done';
          else $status = 'progress';
        @endphp
        <tr class="hover:bg-black/5 transition-colors">
          <td class="p-4">{{ ($rows->currentPage()-1)*$rows->perPage() + $loop->iteration }}</td>

          <td class="p-4">
            <div class="font-semibold text-(--text)">{{ $u->nama ?? $u->username ?? '-' }}</div>
            <div class="text-(--muted) text-[10px] md:text-xs mt-1">
              username: {{ $u->username ?? '-' }} | tahun: {{ $createdYear }}
            </div>
          </td>

          <td class="p-4 font-semibold whitespace-normal max-w-85 wrap-break-word">{{ $row->nama_kegiatan }}</td>

          <td class="p-4 font-mono text-xs md:text-sm whitespace-normal max-w-85 wrap-break-word">{{ $row->nomor_rekomendasi }}</td>

          <td class="p-4">
            @if($status === 'done')
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-bold bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 whitespace-nowrap">
                <i class="bi bi-check-circle-fill"></i> Done
              </span>
              <div class="text-[10px] text-(--muted) mt-1">Dinilai {{ $scored }}/{{ $domainsTotal }}</div>
            @elseif($status === 'progress')
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-bold bg-amber-500/10 border border-amber-500/30 text-amber-600 whitespace-nowrap">
                <i class="bi bi-hourglass-split"></i> Onprogress
              </span>
              <div class="text-[10px] text-(--muted) mt-1">Dinilai {{ $scored }}/{{ $domainsTotal }}</div>
            @else
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] md:text-xs font-bold bg-slate-500/10 border border-slate-500/30 text-slate-600 whitespace-nowrap">
                <i class="bi bi-dot"></i> Belum dinilai
              </span>
              <div class="text-[10px] text-(--muted) mt-1">Dinilai 0/{{ $domainsTotal }}</div>
            @endif
          </td>

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
               href="{{ route('bps.penilaian.show', [
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
          <td colspan="7" class="p-8 text-center text-(--muted)">Belum ada data.</td>
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
