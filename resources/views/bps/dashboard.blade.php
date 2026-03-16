@extends('layouts.bps')

@php
  $title = 'Dashboard BPS';
  $header = 'Dashboard BPS';
  $subheader = 'Ringkasan LKE OPD untuk proses penilaian.';
@endphp

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Total OPD</div>
      <i class="bi bi-building text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1">{{ $totalOpd }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Akun OPD terdaftar</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Draft</div>
      <i class="bi bi-hourglass-split text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1">{{ $totalDraft }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Belum final submit</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Final</div>
      <i class="bi bi-check2-circle text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1">{{ $totalFinal }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Sudah dikumpulkan</div>
  </div>

  <div class="bg-(--panel) shadow-sm rounded-2xl p-6 border border-(--border-strong) transition-all hover:-translate-y-1 hover:shadow-md">
    <div class="flex items-center justify-between mb-4">
      <div class="text-(--muted) text-xs md:text-sm font-medium">Masuk Penilaian</div>
      <i class="bi bi-clipboard-check text-xl md:text-2xl text-(--muted) opacity-50"></i>
    </div>
    <div class="font-bold text-2xl md:text-3xl text-(--text) mb-1">{{ $masukPenilaian }}</div>
    <div class="text-(--muted) text-[10px] md:text-xs">Siap dinilai</div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 md:p-6 mb-6">
  <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
      <div class="font-semibold text-base md:text-lg text-(--text)">Menu BPS</div>
      <div class="text-(--muted) text-xs md:text-sm mt-1">Akses cepat ke halaman penilaian.</div>
    </div>

    <div class="flex flex-wrap gap-3">
      <a href="{{ route('bps.penilaian.index') }}" class="px-4 md:px-5 py-2 md:py-2.5 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors flex items-center gap-2 font-medium">
        <i class="bi bi-clipboard-check text-base md:text-lg"></i>
        Penilaian OPD
      </a>
    </div>
  </div>
</div>

<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-5 md:p-6">
  <div class="font-semibold text-base md:text-lg text-(--text) mb-2">{{ $informasi->judul }}</div>
  <div class="text-(--muted) text-xs md:text-sm leading-relaxed">{{ $informasi->isi }}</div>

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

@endsection
