@extends('layouts.admin')

@php
  $title = 'Admin Dashboard';
  $header = 'Dashboard Admin';
  $subheader = 'Kelola user dan master data, serta monitoring lembar kerja evaluasi.';
@endphp

@section('content')
  <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
    <div>
      <div class="font-semibold text-lg md:text-xl">Selamat datang, {{ Auth::user()->nama ?? Auth::user()->username }}</div>
      <div class="text-(--muted) text-xs md:text-sm">Gunakan menu di sidebar untuk mengelola data.</div>
    </div>

    <div class="flex gap-3">
      <a href="{{ route('users.create') }}" class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) bg-white text-gray-800 rounded-xl hover:bg-gray-50 flex items-center gap-2 font-medium transition-colors">
        <i class="bi bi-person-plus text-base md:text-lg"></i> Tambah User
      </a>

      <a href="{{ route('lke.index') }}" class="px-3 md:px-4 py-1.5 md:py-2 bg-(--brand) text-white rounded-xl hover:opacity-90 flex items-center gap-2 font-medium transition-opacity">
        <i class="bi bi-file-earmark-text text-base md:text-lg"></i> Lihat Lembar Kerja
      </a>
    </div>
  </div>

  <hr class="border-t border-(--border-strong) my-6">

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
    <!-- Stat 1 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Total User</div>
        <i class="bi bi-people text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalUsers ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Admin / OPD / BPS</div>
    </div>

    <!-- Stat 2 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Tahun</div>
        <i class="bi bi-calendar3 text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalTahun ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Master data tahun</div>
    </div>

    <!-- Stat 3 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Domain</div>
        <i class="bi bi-diagram-3 text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalDomain ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Master domain</div>
    </div>

    <!-- Stat 4 -->
    <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-2">
        <div class="text-(--muted) text-xs md:text-sm">Lembar Kerja</div>
        <i class="bi bi-file-earmark-text text-(--muted) opacity-70"></i>
      </div>
      <div class="font-bold text-2xl md:text-3xl text-(--text)">{{ $totalLke ?? '—' }}</div>
      <div class="text-[10px] md:text-xs text-(--muted) mt-1">Isian OPD (monitoring)</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mt-4">
    <div class="lg:col-span-7">
      <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm h-full">
        <div class="font-semibold text-base md:text-lg mb-1">Aksi Cepat</div>
        <div class="text-(--muted) text-xs md:text-sm mb-4">Shortcut untuk kerja harian admin.</div>

        <div class="flex flex-wrap gap-3">
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('users.index') }}">
            <i class="bi bi-people"></i> Kelola User
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('tahun.index') }}">
            <i class="bi bi-calendar3"></i> Kelola Tahun
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('domains.index') }}">
            <i class="bi bi-diagram-3"></i> Kelola Domain
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('kriterias.index') }}">
            <i class="bi bi-list-check"></i> Kelola Kriteria
          </a>
          <a class="px-3 md:px-4 py-1.5 md:py-2 border border-(--border-strong) rounded-xl hover:bg-[rgba(255,255,255,0.1)] flex items-center gap-2 transition-colors text-xs md:text-sm text-(--text)" href="{{ route('lke.index') }}">
            <i class="bi bi-file-earmark-text"></i> Monitoring LKE
          </a>
        </div>
      </div>
    </div>

    <div class="lg:col-span-5">
      <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 shadow-sm h-full">
        <div class="font-semibold text-base md:text-lg mb-1">{{ $informasi->judul }}</div>
        <div class="text-(--muted) text-xs md:text-sm mb-4">{{ $informasi->isi }}</div>

        <hr class="border-t border-(--border-strong) my-4">

        <div class="text-xs md:text-sm text-(--muted) space-y-3">
          <div class="flex items-center justify-between">
            <span>Session timeout</span>
            <span class="font-semibold text-(--text)">15 menit idle</span>
          </div>
          <div class="flex items-center justify-between">
            <span>Role</span>
            <span class="font-semibold capitalize text-(--text)">{{ Auth::user()->role }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
