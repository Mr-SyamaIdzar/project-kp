@extends('layouts.opd')

@php
  $title = 'Isi Lembar Kerja Evaluasi';
  $header = 'Isi Lembar Kerja Evaluasi (LKE)';
  $subheader = 'Menu ini dinonaktifkan oleh admin.';
@endphp

@section('content')
<div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 shadow-sm min-h-80 flex flex-col items-center justify-center text-center">
  <div class="mb-4 text-5xl text-(--muted) opacity-50">
    <i class="bi bi-slash-circle"></i>
  </div>
  <div class="font-semibold text-lg md:text-xl text-(--text) mb-2">Menu Isi Lembar Kerja Evaluasi Tidak Tersedia</div>
  <div class="text-(--muted) text-xs md:text-sm max-w-lg mx-auto leading-relaxed">
    @if(isset($reason))
      {{ $reason }}
    @else
      Akses menu ini saat ini dinonaktifkan. Silakan hubungi admin jika membutuhkan akses kembali.
    @endif
  </div>
</div>
@endsection
