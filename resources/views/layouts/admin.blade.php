<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Admin Dashboard' }}</title>

  <!-- Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- App Styles and Scripts (Includes Tailwind + Extracted Layout assets) -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <!-- Tiny inline script for theme to prevent Flash Of Unstyled Content (FOUC) -->
  <script>
    (function(){
      const key = 'kp_theme';
      let theme = 'light';
      try{
        const saved = localStorage.getItem(key);
        if(saved === 'dark' || saved === 'light') theme = saved;
      }catch(e){}
      document.documentElement.setAttribute('data-theme', theme);
      if(theme === 'dark'){
          document.documentElement.classList.add('dark');
      }
    })();
  </script>
</head>

<body class="antialiased min-h-screen overflow-x-hidden">
{{-- TOAST (Global Admin) --}}
<!-- Toast Container (Custom Vanilla) -->
<div id="appToast" class="hidden fixed bottom-4 right-4 z-50 p-4 rounded-xl shadow-lg text-white transition-opacity duration-300 opacity-0" role="alert">
  <div class="flex items-center justify-between gap-4">
    <div id="appToastBody">...</div>
    <button type="button" class="text-white opacity-75 hover:opacity-100 focus:outline-none" onclick="document.getElementById('appToast').style.display='none'">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
</div>

@include('partials._custom-alert')

<!-- OFFCANVAS (Mobile/Tablet) Vanilla implementation since BS is removed -->
<!-- Add AlpineJS or simple toggle logic for mobile sidebar in the future if needed,
     For now, we'll hide it and let user implement JS toggle or we extract more later. -->
<div id="mobileSidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden transition-opacity" onclick="toggleMobileSidebar()"></div>
<div id="mobileSidebar" class="fixed inset-y-0 left-0 w-72 bg-(--offcanvas-bg) text-(--offcanvas-text) z-50 transform -translate-x-full transition-transform duration-300 shadow-xl overflow-y-auto lg:hidden">
  <div class="flex items-center justify-between p-4 border-b border-(--border-strong)">
    <h5 class="font-semibold text-base md:text-lg">Menu</h5>
    <button type="button" class="text-xl md:text-2xl opacity-70 hover:opacity-100 transition-opacity" onclick="toggleMobileSidebar()" aria-label="Close">
         <i class="bi bi-x"></i>
    </button>
  </div>
  <div class="flex flex-col flex-1 p-4">
    @include('partials.admin_sidebar')
  </div>
</div>

<!-- Layout Wrapper -->
<div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] min-h-screen">
  <!-- Sidebar Desktop -->
  <aside class="sidebar hidden lg:block sticky top-0 h-screen overflow-y-auto">
    <div class="flex flex-col h-full p-5">
      @include('partials.admin_sidebar')
    </div>
  </aside>

  <!-- Content -->
  <main class="content p-4 lg:p-6 bg-(--content-bg) flex flex-col">
    <!-- Mobile hamburger bar -->
    <div class="flex items-center gap-3 mb-4 lg:hidden">
      <button class="btn-outline-light p-2 rounded-xl border flex items-center justify-center transition-colors" type="button" onclick="toggleMobileSidebar()">
        <i class="bi bi-list text-lg"></i>
      </button>
      <div>
        <div class="font-semibold text-sm text-(--text)">{{ $header ?? 'Dashboard' }}</div>
        <div class="text-[10px] text-(--muted)">{{ $subheader ?? '' }}</div>
      </div>
    </div>

    <!-- Page Content -->
    <div class="page-card p-4 lg:p-6 rounded-2xl md:rounded-[18px] grow">
      @yield('content')
    </div>
  </main>
</div>

@stack('scripts')

{{-- auto toast dari session --}}
@if(session('success'))
  <script>document.addEventListener('DOMContentLoaded', ()=> showToast(@json(session('success')), 'success'));</script>
@endif
@if(session('failed'))
  <script>document.addEventListener('DOMContentLoaded', ()=> showToast(@json(session('failed')), 'danger'));</script>
@endif
@if($errors->any())
  <script>document.addEventListener('DOMContentLoaded', ()=> showToast(@json($errors->first()), 'warning'));</script>
@endif

</body>
</html>
