@extends('layouts.opd')

@php
  $title = 'Profile';
  $header = 'Profile Saya';
  $subheader = 'Kelola informasi profil dan keamanan akun Anda.';
@endphp

@section('content')
<div class="max-w-3xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <div>
      <h2 class="text-xl md:text-2xl font-bold text-(--text) m-0">{{ $header }}</h2>
      <p class="text-(--muted) mt-1 mb-0">{{ $subheader }}</p>
    </div>
  </div>

  @if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 text-red-500 rounded-2xl p-4 mb-6">
      <strong class="font-semibold">Ada kesalahan:</strong>
      <ul class="list-disc pl-5 mb-0 mt-2 text-xs md:text-sm text-red-400">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('success'))
    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-500 rounded-2xl p-4 mb-6 flex items-center gap-2">
      <i class="bi bi-check-circle text-base md:text-lg"></i> {{ session('success') }}
    </div>
  @endif

  <!-- Profil Section -->
  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6 shadow-sm">
    <div class="flex items-center gap-2 font-bold text-base md:text-lg text-(--text) mb-6 pb-4 border-b border-(--border-strong)">
      <i class="bi bi-person-circle text-(--brand)"></i>
      Informasi Profil
    </div>

    <form method="POST" action="{{ route('opd.profile.update-profile') }}" enctype="multipart/form-data">
      @csrf

      <!-- Avatar Upload -->
      <div class="flex items-end gap-6 mb-8">
        <div class="relative w-32 h-32 rounded-2xl overflow-hidden border-2 border-(--brand)/30 flex items-center justify-center bg-(--brand)/5 group shadow-inner" id="avatarPreview">
          @if ($user->foto)
            <img src="{{ asset('storage/' . $user->foto) }}" alt="Avatar" class="w-full h-full object-cover shadow-md" />
          @else
            <span class="text-5xl text-(--text) opacity-70"><i class="bi bi-person"></i></span>
          @endif
        </div>

        <div class="flex-1">
          <div class="relative inline-block overflow-hidden">
            <!-- Accept only jpeg/png to disallow GIF uploads -->
            <input type="file" id="fotoInput" name="foto" accept="image/png,image/jpeg" class="absolute -left-2499.75" />
            <button type="button" class="px-3 md:px-4 py-1.5 md:py-2 bg-transparent border border-(--border-strong) text-(--text) rounded-xl hover:bg-white/5 transition-colors inline-flex items-center gap-2 text-xs md:text-sm font-medium whitespace-nowrap" onclick="document.getElementById('fotoInput').click()">
              <i class="bi bi-upload"></i> Pilih Foto
            </button>
          </div>
          <small class="block mt-2 text-[10px] md:text-xs font-medium text-(--muted)">JPG, PNG (Max 2MB)</small>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Nama -->
        <div>
          <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Nama Lengkap</label>
          <input type="text" class="w-full bg-(--sidebar-bg) border {{ $errors->has('nama') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 transition-all" name="nama" value="{{ old('nama', $user->nama) }}" required />
          @error('nama')
            <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div>
          @enderror
        </div>

        <!-- Username (readonly) -->
        <div>
          <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Username</label>
          <input type="text" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2.5 opacity-60 cursor-not-allowed" value="{{ $user->username }}" disabled />
          <small class="block mt-2 text-[10px] md:text-xs text-(--muted)">Username tidak dapat diubah.</small>
        </div>

        <!-- Role (readonly) -->
        <div>
          <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Role</label>
          <input type="text" class="w-full bg-(--sidebar-bg) border border-(--border-strong) text-(--text) rounded-xl px-4 py-2.5 opacity-60 cursor-not-allowed" value="{{ strtoupper($user->role) }}" disabled />
        </div>
      </div>

      <div class="mt-8 flex justify-end">
        <button type="submit" class="px-4 md:px-5 py-2 md:py-2.5 bg-(--brand) text-white rounded-xl hover:opacity-90 inline-flex items-center justify-center gap-2 transition-opacity font-medium">
          <i class="bi bi-check2 text-base md:text-lg"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>

  <!-- Change Password Section -->
  <div class="bg-(--panel) border border-(--border-strong) rounded-2xl p-6 mb-6 shadow-sm">
    <div class="flex items-center gap-2 font-bold text-base md:text-lg text-(--text) mb-6 pb-4 border-b border-(--border-strong)">
      <i class="bi bi-shield-lock text-(--brand)"></i>
      Keamanan Akun
    </div>

    <form method="POST" action="{{ route('opd.profile.update-password') }}" id="passwordForm">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-6">
          <!-- Password Lama -->
          <div>
            <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Password Lama</label>
            <div class="relative flex items-center">
              <input type="password" id="password_lama" class="w-full bg-(--sidebar-bg) border {{ $errors->has('password_lama') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl pr-12 pl-4 py-2.5 focus:outline-none focus:ring-2 transition-all" name="password_lama" required />
              <button type="button" class="absolute right-2 px-2 py-1 bg-transparent text-(--muted) hover:text-(--text) transition-colors" onclick="togglePassword('password_lama', this)" aria-label="Tampilkan password">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            @error('password_lama')
              <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div>
            @enderror
          </div>

          <!-- Password Baru -->
          <div>
            <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Password Baru</label>
            <div class="relative flex items-center">
              <input type="password" id="passwordBaru" class="w-full bg-(--sidebar-bg) border {{ $errors->has('password_baru') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl pr-12 pl-4 py-2.5 focus:outline-none focus:ring-2 transition-all" name="password_baru" required />
              <button type="button" class="absolute right-2 px-2 py-1 bg-transparent text-(--muted) hover:text-(--text) transition-colors" onclick="togglePassword('passwordBaru', this)" aria-label="Tampilkan password baru">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            @error('password_baru')
              <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div>
            @enderror
          </div>

          <!-- Konfirmasi Password -->
          <div>
            <label class="block text-xs md:text-sm font-semibold text-(--text) mb-2">Konfirmasi Password Baru</label>
            <div class="relative flex items-center">
              <input type="password" id="passwordBaruConfirmation" class="w-full bg-(--sidebar-bg) border {{ $errors->has('password_baru_confirmation') ? 'border-red-500 focus:ring-red-500' : 'border-(--border-strong) focus:ring-(--brand)' }} text-(--text) rounded-xl pr-12 pl-4 py-2.5 focus:outline-none focus:ring-2 transition-all" name="password_baru_confirmation" required />
              <button type="button" class="absolute right-2 px-2 py-1 bg-transparent text-(--muted) hover:text-(--text) transition-colors" onclick="togglePassword('passwordBaruConfirmation', this)" aria-label="Tampilkan konfirmasi password">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            @error('password_baru_confirmation')
               <div class="text-red-500 text-[10px] md:text-xs mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div>
          <!-- Password Requirements -->
          <div class="bg-(--brand)/10 border-l-4 border-(--brand) rounded-tr-xl rounded-br-xl mt-8 md:mt-0 p-4 h-full flex flex-col justify-center">
            <h4 class="text-xs md:text-sm font-semibold text-(--text) mb-3">Persyaratan Password:</h4>
            <div class="space-y-2 text-xs md:text-sm text-(--text)">
              <div class="flex items-center gap-2 requirement-item text-(--muted)" id="req-length">
                <i class="bi bi-check-circle transition-colors"></i> <span>Minimal 8 karakter</span>
              </div>
              <div class="flex items-center gap-2 requirement-item text-(--muted)" id="req-uppercase">
                <i class="bi bi-check-circle transition-colors"></i> <span>Mengandung huruf besar (A-Z)</span>
              </div>
              <div class="flex items-center gap-2 requirement-item text-(--muted)" id="req-lowercase">
                <i class="bi bi-check-circle transition-colors"></i> <span>Mengandung huruf kecil (a-z)</span>
              </div>
              <div class="flex items-center gap-2 requirement-item text-(--muted)" id="req-number">
                <i class="bi bi-check-circle transition-colors"></i> <span>Mengandung angka (0-9)</span>
              </div>
              <div class="flex items-center gap-2 requirement-item text-(--muted)" id="req-symbol">
                <i class="bi bi-check-circle transition-colors"></i> <span>Mengandung simbol (!@#$%^&*)</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 flex justify-end">
        <button type="submit" class="px-4 md:px-5 py-2 md:py-2.5 bg-transparent border border-amber-500/50 text-amber-500 rounded-xl hover:bg-amber-500 hover:text-white inline-flex items-center justify-center gap-2 transition-colors font-medium">
          <i class="bi bi-shield-check text-base md:text-lg"></i> Ubah Password
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Avatar Preview
  const fotoInput = document.getElementById('fotoInput');
  const avatarPreview = document.getElementById('avatarPreview');

  fotoInput.addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (event) {
        avatarPreview.innerHTML = `<img src="${event.target.result}" alt="Preview" class="w-full h-full object-cover shadow-md" />`;
      };
      reader.readAsDataURL(file);
    }
  });

  // Password Requirements Validator
  const passwordInput = document.getElementById('passwordBaru');
  const requirements = {
    length: (pwd) => pwd.length >= 8,
    uppercase: (pwd) => /[A-Z]/.test(pwd),
    lowercase: (pwd) => /[a-z]/.test(pwd),
    number: (pwd) => /[0-9]/.test(pwd),
    symbol: (pwd) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
  };

  passwordInput.addEventListener('input', function () {
    const pwd = this.value;

    Object.keys(requirements).forEach((key) => {
      const element = document.getElementById(`req-${key === 'length' ? 'length' : key === 'uppercase' ? 'uppercase' : key === 'lowercase' ? 'lowercase' : key === 'number' ? 'number' : 'symbol'}`);
      const isMet = requirements[key](pwd);
      const icon = element.querySelector('i');

      if (isMet) {
        element.classList.remove('text-(--muted)');
        element.classList.add('text-(--text)');
        icon.classList.remove('text-(--muted)');
        icon.classList.add('text-emerald-500');
      } else {
        element.classList.remove('text-(--text)');
        element.classList.add('text-(--muted)');
        icon.classList.remove('text-emerald-500');
        icon.classList.add('text-(--muted)');
      }

    });
  });

  // Trigger on page load if there are errors
  if (passwordInput.value) {
    passwordInput.dispatchEvent(new Event('input'));
  }

  // Toggle show/hide password for inputs
  function togglePassword(inputId, btn){
    const input = document.getElementById(inputId);
    if(!input) return;
    const icon = btn.querySelector('i');
    if(input.type === 'password'){
      input.type = 'text';
      if(icon) icon.className = 'bi bi-eye-slash';
      btn.setAttribute('aria-label', 'Sembunyikan password');
    } else {
      input.type = 'password';
      if(icon) icon.className = 'bi bi-eye';
      btn.setAttribute('aria-label', 'Tampilkan password');
    }
  }
</script>
@endsection
