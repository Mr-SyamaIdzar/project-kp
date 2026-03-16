@extends($layout ?? 'layouts.opd')

@php
  $title = $title ?? 'Profile';
  $header = $header ?? 'Profile Saya';
  $subheader = $subheader ?? 'Kelola informasi profil dan keamanan akun Anda.';
  $routePrefix = $routePrefix ?? 'opd';
@endphp

@section('content')
<div class="profile-container">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">{{ $header }}</h2>
      <p class="profile-subheader mb-0">{{ $subheader }}</p>
    </div>
  </div>

  @if ($errors->any())
    <div class="profile-alert alert-danger">
      <strong>Ada kesalahan:</strong>
      <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if (session('success'))
    <div class="profile-alert alert-success">
      <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
  @endif

  <div class="profile-card">
    <div class="card-title">
      <i class="bi bi-person-circle"></i>
      Informasi Profil
    </div>

    <form method="POST" action="{{ route($routePrefix . '.profile.update-profile') }}" enctype="multipart/form-data">
      @csrf

      <div class="avatar-section">
        <div class="avatar-preview" id="avatarPreview">
          @if ($user->foto)
            <img src="{{ asset('storage/' . $user->foto) }}" alt="Avatar" />
          @else
            <span class="avatar-placeholder"><i class="bi bi-person"></i></span>
          @endif
        </div>

        <div class="upload-info">
          <div class="file-input-wrapper">
            <input type="file" id="fotoInput" name="foto" accept="image/png,image/jpeg" />
            <button type="button" class="btn btn-outline-light w-auto px-4 py-2 d-flex align-items-center justify-content-center" style="white-space: nowrap;" onclick="document.getElementById('fotoInput').click()">
              <i class="bi bi-upload me-2"></i> Pilih Foto
            </button>
          </div>
          <small class="upload-hint">JPG, PNG (Max 2MB)</small>
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-40 shrink-0 font-semibold">Nama Lengkap</label>
        <div class="grow max-w-md w-full ml-4">
          <input type="text" class="form-control w-full @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama', $user->nama) }}" required />
          @error('nama')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-40 shrink-0 font-semibold">Username</label>
        <div class="grow flex items-center gap-2 max-w-lg w-full ml-4">
          <input type="text" class="form-control w-full sm:w-auto min-w-60" value="{{ $user->username }}" disabled />
          <small class="text-muted ml-2">Username tidak dapat diubah.</small>
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-40 shrink-0 font-semibold">Role</label>
        <div class="grow max-w-md w-full ml-4">
          <input type="text" class="form-control w-full sm:w-auto min-w-60" value="{{ strtoupper($user->role) }}" disabled />
        </div>
      </div>

      <button type="submit" class="btn btn-primary d-flex align-items-center mt-4 px-4 py-2">
        <i class="bi bi-check2 me-2"></i> Simpan Perubahan
      </button>
    </form>
  </div>

  <div class="profile-card">
    <div class="card-title">
      <i class="bi bi-shield-lock"></i>
      Keamanan Akun
    </div>

    <form method="POST" action="{{ route($routePrefix . '.profile.update-password') }}" id="passwordForm">
      @csrf

      <div class="mb-4 flex items-start mt-2">
        <label class="form-label m-0 md:mt-3 md:min-w-40 shrink-0 font-semibold">Password Lama</label>
        <div class="grow max-w-md w-full ml-4 mt-1">
          <div class="flex">
            <input type="password" id="password_lama" class="form-control w-full rounded-r-none border-r-0 focus:z-10 @error('password_lama') is-invalid @enderror" name="password_lama" required />
            <button type="button" class="btn btn-outline-light px-4 rounded-l-none" onclick="togglePassword('password_lama', this)" aria-label="Tampilkan password">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
          @error('password_lama')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-4 flex items-start">
        <label class="form-label m-0 md:mt-3 md:min-w-40 shrink-0 font-semibold">Password Baru</label>
        <div class="grow max-w-md w-full ml-4 mt-1">
          <div class="flex">
            <input type="password" id="passwordBaru" class="form-control w-full rounded-r-none border-r-0 focus:z-10 @error('password_baru') is-invalid @enderror" name="password_baru" required />
            <button type="button" class="btn btn-outline-light px-4 rounded-l-none" onclick="togglePassword('passwordBaru', this)" aria-label="Tampilkan password baru">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
          @error('password_baru')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror

          <div class="password-requirement mt-3">
            <div class="requirement-item" id="req-length">
              <i class="bi bi-check-circle"></i>
              <span>Minimal 8 karakter</span>
            </div>
            <div class="requirement-item" id="req-uppercase">
              <i class="bi bi-check-circle"></i>
              <span>Mengandung huruf besar (A-Z)</span>
            </div>
            <div class="requirement-item" id="req-lowercase">
              <i class="bi bi-check-circle"></i>
              <span>Mengandung huruf kecil (a-z)</span>
            </div>
            <div class="requirement-item" id="req-number">
              <i class="bi bi-check-circle"></i>
              <span>Mengandung angka (0-9)</span>
            </div>
            <div class="requirement-item" id="req-symbol">
              <i class="bi bi-check-circle"></i>
              <span>Mengandung simbol (!@#$%^&*)</span>
            </div>
          </div>
        </div>
      </div>

      <div class="mb-4 flex items-start">
        <label class="form-label m-0 md:mt-3 md:min-w-40 shrink-0 font-semibold">Konfirmasi Password Baru</label>
        <div class="grow max-w-md w-full ml-4 mt-1">
          <div class="flex">
            <input type="password" id="passwordBaruConfirmation" class="form-control w-full rounded-r-none border-r-0 focus:z-10 @error('password_baru_confirmation') is-invalid @enderror" name="password_baru_confirmation" required />
            <button type="button" class="btn btn-outline-light px-4 rounded-l-none" onclick="togglePassword('passwordBaruConfirmation', this)" aria-label="Tampilkan konfirmasi password">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
          @error('password_baru_confirmation')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <button type="submit" class="btn btn-primary d-flex align-items-center mt-4 px-4 py-2">
        <i class="bi bi-shield-check me-2"></i> Ubah Password
      </button>
    </form>
  </div>
</div>

<script>
  const fotoInput = document.getElementById('fotoInput');
  const avatarPreview = document.getElementById('avatarPreview');

  if (fotoInput && avatarPreview) {
    fotoInput.addEventListener('change', function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          avatarPreview.innerHTML = `<img src="${event.target.result}" alt="Preview" />`;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  const passwordInput = document.getElementById('passwordBaru');
  const requirements = {
    length: (pwd) => pwd.length >= 8,
    uppercase: (pwd) => /[A-Z]/.test(pwd),
    lowercase: (pwd) => /[a-z]/.test(pwd),
    number: (pwd) => /[0-9]/.test(pwd),
    symbol: (pwd) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd),
  };

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      const pwd = this.value;

      Object.keys(requirements).forEach((key) => {
        const element = document.getElementById(`req-${key === 'length' ? 'length' : key === 'uppercase' ? 'uppercase' : key === 'lowercase' ? 'lowercase' : key === 'number' ? 'number' : 'symbol'}`);
        if (!element) return;
        const isMet = requirements[key](pwd);

        element.classList.toggle('met', isMet);
        element.classList.toggle('unmet', !isMet);
      });
    });

    if (passwordInput.value) {
      passwordInput.dispatchEvent(new Event('input'));
    }
  }

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
