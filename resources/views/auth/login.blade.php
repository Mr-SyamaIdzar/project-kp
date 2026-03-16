<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>IPKSS SLEMAN</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <!-- Load Tailwind CSS -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Turnstile script dari package --}}
  <x-turnstile.scripts />

  <style>
    /* Biar captcha nggak ketutup CSS template */
    .turnstile-wrap{
      display:flex;
      justify-content:center;
      margin: 14px 0;
      position: relative;
      z-index: 10;
    }
    body { font-family: 'Inter', sans-serif; }
  </style>
</head>

<body class="auth-shell relative">
  <img
    src="{{ asset('images/bg fhd.jpg') }}"
    alt=""
    class="fixed inset-0 w-full h-full object-cover -z-10"
  >
  <div class="auth-overlay z-0"></div>

  <div class="relative z-10 container mx-auto px-4">
    <div class="flex justify-center mb-8">
      <h2 class="text-white text-3xl md:text-4xl font-bold tracking-wide drop-shadow-lg text-center">IPKS SLEMAN</h2>
    </div>

    <div class="flex justify-center">
      <div class="auth-card transform transition-all hover:scale-[1.01]">
        <h3 class="text-xl md:text-2xl font-semibold text-center text-white mb-6 tracking-tight">LOGIN</h3>

        @if (session('failed'))
          <div class="bg-red-500/80 border border-red-400 text-white px-4 py-3 rounded-xl mb-4 text-xs md:text-sm shadow-sm">
            {{ session('failed') }}
          </div>
        @endif

        @if ($timeout ?? false)
          <div class="bg-amber-500/80 border border-amber-400 text-white px-4 py-3 rounded-xl mb-4 text-xs md:text-sm shadow-sm flex items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i> Sesi Anda telah berakhir karena tidak ada aktivitas selama 15 menit. Silakan login kembali.
          </div>
        @endif

        {{-- error validasi --}}
        @if ($errors->any())
          <div class="bg-red-500/80 border border-red-400 text-white px-4 py-3 rounded-xl mb-4 text-xs md:text-sm shadow-sm">
            <ul class="list-disc pl-5 m-0 space-y-1">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @php
          $isLocked = $isLocked ?? session()->has('lockout_seconds');
          $seconds = $seconds ?? session('lockout_seconds', 0);
        @endphp

        <form action="{{ route('login') }}" method="POST" class="space-y-5" id="loginForm">
          @csrf

          <div>
            <div class="relative">
              <input type="text" name="username" id="username" class="auth-input {{ $isLocked ? 'opacity-50 cursor-not-allowed' : '' }}"
                     placeholder="Username" value="{{ old('username') }}" required autocomplete="username" {{ $isLocked ? 'disabled' : '' }}>
              <i class="bi bi-person auth-input-icon"></i>
            </div>
          </div>

          <div>
            <div class="relative">
              <input type="password" name="password" id="password-field" class="auth-input pr-12 {{ $isLocked ? 'opacity-50 cursor-not-allowed' : '' }}"
                     placeholder="Password" required autocomplete="current-password" {{ $isLocked ? 'disabled' : '' }}>
              <button type="button" class="absolute right-4 top-3.5 text-white/70 hover:text-white transition-colors focus:outline-none" onclick="togglePassword()" {{ $isLocked ? 'disabled' : '' }}>
                <i class="bi bi-eye" id="togglePasswordIcon"></i>
              </button>
            </div>
          </div>

          <div class="bg-black/20 p-4 rounded-xl border border-white/10 {{ $isLocked ? 'opacity-50' : '' }}">
            <label class="block text-white/80 text-xs md:text-sm mb-2">
              Captcha: <strong class="text-white">{{ session('captcha_question') }}</strong>
            </label>
            <input type="text"
                   name="captcha"
                   id="captcha-field"
                   inputmode="numeric"
                   pattern="[0-9]*"
                   class="w-full bg-white/10 text-white placeholder-white/50 border {{ $errors->has('captcha') ? 'border-red-400 focus:ring-red-400' : 'border-white/20 focus:ring-purple-400' }} rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:border-transparent transition-all {{ $isLocked ? 'cursor-not-allowed' : '' }}"
                   placeholder="Jawaban captcha..."
                   required
                   {{ $isLocked ? 'disabled' : '' }}>

            @error('captcha')
              <div class="text-red-300 text-[10px] md:text-xs mt-1">{{ $message }}</div>
            @enderror

            <p class="text-white/60 text-[10px] md:text-xs mt-2 mb-0">
              Isi hasil penjumlahan di atas.
            </p>
          </div>

          <button type="submit" class="auth-btn-primary {{ $isLocked ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isLocked ? 'disabled' : '' }}>
            {{ $isLocked ? 'Silakan Tunggu...' : 'Sign In' }}
          </button>

        </form>

      </div>
    </div>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById('password-field');
      const icon = document.getElementById('togglePasswordIcon');

      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    }

    @if($isLocked && $seconds > 0)
    (function() {
      let timeLeft = {{ $seconds }};
      const btn = document.querySelector('button[type="submit"]');
      const inputs = document.querySelectorAll('#loginForm input, #loginForm button');
      
      const timer = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
          clearInterval(timer);
          inputs.forEach(el => {
            el.disabled = false;
            el.classList.remove('opacity-50', 'cursor-not-allowed');
          });
          btn.innerText = 'Sign In';
          // Optional: refresh page to get new captcha
          window.location.reload();
        }
      }, 1000);
    })();
    @endif
  </script>
</body>
</html>
