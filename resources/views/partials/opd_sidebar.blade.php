@php
   $user = Auth::user();
   $foto = $user?->foto ? asset('storage/'.$user->foto) : 'https://ui-avatars.com/api/?name='.urlencode($user?->nama ?? 'OPD').'&background=3b82f6&color=fff';
@endphp


<div class="profile flex items-center gap-3 p-3 rounded-xl mb-6">
  <img class="avatar w-12 h-12 rounded-full object-cover shadow-sm bg-white" src="{{ $foto }}" alt="Foto Profil">
  <div class="grow min-w-0">
    <div class="font-semibold wrap-break-word whitespace-normal leading-tight">{{ $user?->nama ?? $user?->username ?? 'OPD' }}</div>
    <div class="mt-1">
      @php($role = 'opd')
      <style>html[data-theme="light"] .badge-txt-opd { color: #1e3a8a !important; }</style>
      <span class="role-badge capitalize inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] md:text-[11px] font-semibold bg-blue-100 dark:bg-blue-500/20 badge-txt-opd dark:text-blue-200 border-[0.5px] border-blue-300 dark:border-blue-500/50">
        {{ $role }}
      </span>
    </div>
  </div>
</div>

<nav class="flex flex-col gap-2 relative grow">
  <a href="{{ route('opd.profile.edit') }}"
     class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ request()->routeIs('opd.profile.*') ? 'active' : '' }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-person-circle"></i></span>
    <span>Profile</span>
  </a>

  <hr class="border-(--border-strong) my-2">

  <a href="{{ route('opd.dashboard') }}"
     class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ request()->routeIs('opd.dashboard') ? 'active' : '' }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-speedometer2"></i></span>
    <span>Dashboard</span>
  </a>

  <a href="{{ route('opd.lke.create') }}"
     class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ request()->routeIs('opd.lke.create') ? 'active' : '' }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-pencil-square"></i></span>
    <span>Isi LKE</span>
  </a>

  <a href="{{ route('opd.lke.riwayat.index') }}"
     class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ request()->routeIs('opd.lke.riwayat.*') ? 'active' : '' }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-clock-history"></i></span>
    <span>Riwayat LKE</span>
  </a>
</nav>

{{-- Sidebar footer: theme toggle + logout --}}
<div class="mt-auto pt-4 border-t border-(--border-strong) flex flex-col gap-2">
  <button type="button" id="themeToggleBtn"
    class="theme-toggle nav-link-custom w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-sm font-medium">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg">
      <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
    </span>
    <span id="themeToggleText">Dark Mode</span>
  </button>

  <form action="{{ route('logout') }}" method="POST" class="m-0">
    @csrf
    <button type="submit"
      class="btn-logout w-full flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-sm font-medium">
      <span class="w-8 h-8 flex items-center justify-center rounded-lg">
        <i class="bi bi-box-arrow-right"></i>
      </span>
      <span>Logout</span>
    </button>
  </form>
</div>
