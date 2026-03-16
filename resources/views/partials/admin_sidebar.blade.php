@php
  $u = Auth::user();
  $foto = $u?->foto ? asset('storage/'.$u->foto) : 'https://ui-avatars.com/api/?name='.urlencode($u?->nama ?? 'Admin').'&background=2563eb&color=fff';
@endphp


<div class="profile flex items-center gap-3 p-3 rounded-xl mb-6">
  <img class="avatar w-12 h-12 rounded-full object-cover shadow-sm bg-white" src="{{ $foto }}" alt="Foto Profil">
  <div class="grow min-w-0">
    <div class="font-semibold wrap-break-word whitespace-normal leading-tight">{{ $u?->nama ?? $u?->username ?? 'Admin' }}</div>
    <div class="mt-1">
      @php($role = $u?->role ?? 'admin')
      <style>
        html[data-theme="light"] .badge-txt-admin { color: #4c1d95 !important; }
        html[data-theme="light"] .badge-txt-opd { color: #1e3a8a !important; }
        html[data-theme="light"] .badge-txt-bps { color: #064e3b !important; }
      </style>
      <span class="role-badge capitalize inline-flex items-center px-2 py-0.5 rounded text-[10px] md:text-[11px] font-semibold
        {{ $role === 'admin' ? 'bg-purple-100 dark:bg-purple-500/20 badge-txt-admin dark:text-purple-200 border-[0.5px] border-purple-300 dark:border-purple-500/50' : '' }}
        {{ $role === 'opd' ? 'bg-blue-100 dark:bg-blue-500/20 badge-txt-opd dark:text-blue-200 border-[0.5px] border-blue-300 dark:border-blue-500/50' : '' }}
        {{ $role === 'bps' ? 'bg-emerald-100 dark:bg-emerald-500/20 badge-txt-bps dark:text-emerald-200 border-[0.5px] border-emerald-300 dark:border-emerald-500/50' : '' }}
      ">
        {{ $role }}
      </span>
    </div>
  </div>
</div>

<nav class="flex flex-col gap-2 grow">
  <a class="nav-link-custom {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}"
     href="{{ route('admin.profile.edit') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-person-circle"></i></span>
    <span>Profile</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
     href="{{ route('admin.dashboard') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-speedometer2"></i></span>
    <span>Dashboard</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}"
     href="{{ route('users.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-people"></i></span>
    <span>User</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('tahun.*') ? 'active' : '' }}"
     href="{{ route('tahun.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-calendar3"></i></span>
    <span>Tahun</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('domains.*') ? 'active' : '' }}"
     href="{{ route('domains.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-diagram-3"></i></span>
    <span>Indikator</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('kriterias.*') ? 'active' : '' }}"
     href="{{ route('kriterias.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-list-check"></i></span>
    <span>Kriteria</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('lke.*') ? 'active' : '' }}"
     href="{{ route('lke.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-file-earmark-text"></i></span>
    <span>Lembar Kerja Evaluasi</span>
  </a>

  <a class="nav-link-custom {{ request()->routeIs('master-menu.*') ? 'active' : '' }}"
     href="{{ route('master-menu.index') }}">
    <span class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-sliders2 me-1"></i></span>
    <span>Master Menu</span>
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

  <form method="POST" action="{{ route('logout') }}" class="m-0">
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
