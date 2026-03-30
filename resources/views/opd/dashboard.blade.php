@extends('layouts.opd')

@php
  $title = 'OPD Dashboard';
  $header = 'Dashboard OPD';
  $subheader = 'Isi dan kelola Lembar Kerja Evaluasi (LKE) berdasarkan indikator dan kriteria.';
@endphp

@section('content')

  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
      <div class="font-bold text-lg md:text-xl text-(--text)">
        Selamat datang, {{ Auth::user()->nama ?? Auth::user()->username }}
      </div>
      <div class="text-(--muted) text-xs md:text-sm mt-0.5 mb-1">
        Gunakan menu di sidebar untuk mengisi LKE, upload bukti dukung, dan finalisasi.
      </div>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-(--brand)/40 bg-(--brand)/10 text-(--brand) text-xs md:text-sm font-semibold">
        <i class="bi bi-calendar3"></i> Tahun {{ $selectedYear }}
      </span>
      <a href="{{ route('opd.lke.create') }}" class="inline-flex items-center gap-2 px-3 md:px-4 py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 transition-opacity text-xs md:text-sm font-medium">
        <i class="bi bi-pencil-square"></i> Isi LKE
      </a>
    </div>
  </div>

  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('opd.dashboard') }}" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end">
      <div class="flex-1 w-full sm:max-w-xs">
        <label class="block text-[10px] font-semibold text-(--muted) mb-1.5 uppercase tracking-wide">Filter Tahun</label>
        <select name="year" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-3 py-2.5 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-(--brand) transition-all">
          @forelse($years as $y)
            <option value="{{ $y }}" {{ (int)$selectedYear === (int)$y ? 'selected' : '' }}>{{ $y }}</option>
          @empty
            <option value="{{ $selectedYear }}">{{ $selectedYear }}</option>
          @endforelse
        </select>
      </div>
      <button type="submit" class="px-3 py-2.5 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 text-xs md:text-sm shrink-0">
        <i class="bi bi-funnel"></i> Terapkan
      </button>
    </form>
  </div>

  <hr class="border-t border-(--border-strong) my-6">

  {{-- Stats --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between mb-4">
        <div class="text-(--muted) text-xs md:text-sm font-medium">Total Draft</div>
        <i class="bi bi-hourglass-split text-xl md:text-2xl text-(--muted) opacity-50"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text) mb-2">{{ $totalDraft ?? '—' }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs">LKE yang masih proses ({{ $selectedYear }})</div>
    </div>

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between mb-4">
        <div class="text-(--muted) text-xs md:text-sm font-medium">Total Final</div>
        <i class="bi bi-check2-circle text-xl md:text-2xl text-(--muted) opacity-50"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text) mb-2">{{ $totalFinal ?? '—' }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs">LKE yang sudah final ({{ $selectedYear }})</div>
    </div>

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between mb-4">
        <div class="text-(--muted) text-xs md:text-sm font-medium">Indikator Tersedia</div>
        <i class="bi bi-diagram-3 text-xl md:text-2xl text-(--muted) opacity-50"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text) mb-2">{{ $totalIndikator ?? '—' }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs">Dari master Domain ({{ $selectedYear }})</div>
    </div>

    <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
      <div class="flex items-center justify-between mb-4">
        <div class="text-(--muted) text-xs md:text-sm font-medium">Bukti Dukung</div>
        <i class="bi bi-paperclip text-xl md:text-2xl text-(--muted) opacity-50"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text) mb-2">{{ $totalFiles ?? '—' }}</div>
      <div class="text-(--muted) text-[10px] md:text-xs">File yang sudah diupload ({{ $selectedYear }})</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
    <div class="lg:col-span-7">
      <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) h-full">
        <div class="font-semibold text-base md:text-lg text-(--text) mb-1">Aksi Cepat</div>
        <div class="text-(--muted) text-xs md:text-sm mb-6">Shortcut untuk pengisian LKE.</div>

        <div class="flex flex-wrap gap-2">
          <a class="px-4 md:px-5 py-2 md:py-2.5 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2" href="{{ route('opd.lke.create', ['year' => $selectedYear]) }}">
            <i class="bi bi-pencil-square"></i> Isi LKE Baru
          </a>
        </div>
      </div>
    </div>

    <div class="lg:col-span-5">
      @php
        $w = $informasi->warna ?? 'neutral';
        $infoCardClass = match ($w) {
          'red' => 'border-red-500/30 bg-red-500/5',
          'blue' => 'border-blue-500/30 bg-blue-500/5',
          'amber' => 'border-amber-500/30 bg-amber-500/5',
          'emerald' => 'border-emerald-500/30 bg-emerald-500/5',
          default => 'border-(--border-strong) bg-(--panel)',
        };
        $infoTitleClass = match ($w) {
          'red' => 'text-red-700 dark:text-red-200',
          'blue' => 'text-blue-700 dark:text-blue-200',
          'amber' => 'text-amber-700 dark:text-amber-200',
          'emerald' => 'text-emerald-700 dark:text-emerald-200',
          default => 'text-(--text)',
        };
        $infoBodyClass = match ($w) {
          'red' => 'text-red-700/90 dark:text-red-200/90',
          'blue' => 'text-blue-700/90 dark:text-blue-200/90',
          'amber' => 'text-amber-700/90 dark:text-amber-200/90',
          'emerald' => 'text-emerald-700/90 dark:text-emerald-200/90',
          default => 'text-(--muted)',
        };
      @endphp
      <div class="shadow-sm rounded-2xl p-6 border h-full {{ $infoCardClass }}">
        <div class="font-semibold text-base md:text-lg mb-2 break-words {{ $infoTitleClass }}">{{ $informasi->judul }}</div>
        <div class="text-xs md:text-sm leading-relaxed mb-6 break-words {{ $infoBodyClass }}">
          {{ $informasi->isi }}
        </div>

        <hr class="border-t border-(--border-strong) my-4">

        <div class="space-y-3 text-xs md:text-sm">
          <div class="flex items-center justify-between text-(--muted)">
            <span>Session timeout</span>
            <span class="font-semibold text-(--text)">15 menit idle</span>
          </div>
          <div class="flex items-center justify-between text-(--muted)">
            <span>Role</span>
            <span class="font-semibold text-(--text) capitalize">{{ Auth::user()->role }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
