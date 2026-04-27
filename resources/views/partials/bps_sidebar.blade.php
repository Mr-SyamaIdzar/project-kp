@php
  $r = request()->route()?->getName() ?? '';
  $u = auth()->user();
  $foto = $u?->foto ? asset('storage/'.$u->foto) : 'https://ui-avatars.com/api/?name='.urlencode($u?->nama ?? 'BPS').'&background=10b981&color=fff';
@endphp


<div class="profile flex items-center gap-3 p-3 rounded-xl mb-6">
  <img class="avatar w-12 h-12 rounded-full object-cover shadow-sm bg-white" src="{{ $foto }}" alt="Foto Profil">
  <div class="grow min-w-0">
    <div class="font-semibold wrap-break-word whitespace-normal leading-tight">{{ $u?->nama ?? $u?->username ?? 'BPS' }}</div>
    <div class="mt-1">
      @php($role = 'BPS')
      <style>html[data-theme="light"] .badge-txt-bps { color: #064e3b !important; }</style>
      <span class="role-badge capitalize inline-flex items-center justify-center px-2 py-0.5 rounded text-[10px] md:text-[11px] font-semibold bg-emerald-100 dark:bg-emerald-500/20 badge-txt-bps dark:text-emerald-200 border-[0.5px] border-emerald-300 dark:border-emerald-500/50">
        {{ $role }}
      </span>
    </div>
  </div>
</div>

<div class="flex flex-col gap-2 grow">
  <a class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ str_starts_with($r,'bps.profile') ? 'active' : '' }}"
     href="{{ route('bps.profile.edit') }}">
    <div class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-person-circle"></i></div>
    <span>Profile</span>
  </a>

  <a class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ $r==='bps.dashboard' ? 'active' : '' }}"
     href="{{ route('bps.dashboard') }}">
    <div class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-speedometer2"></i></div>
    <span>Dashboard</span>
  </a>

  <a class="nav-link-custom flex items-center gap-3 p-2.5 rounded-xl {{ str_starts_with($r,'bps.penilaian') ? 'active' : '' }}"
     href="{{ route('bps.penilaian.index') }}">
    <div class="nav-ico w-8 h-8 flex items-center justify-center rounded-lg"><i class="bi bi-clipboard-check"></i></div>
    <span>Penilaian</span>
  </a>
</div>

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
