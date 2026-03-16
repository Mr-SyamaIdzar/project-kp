@extends('layouts.admin')

@section('content')

<div class="profile-container mx-auto">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h2 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Tambah User</h2>
      <p class="profile-subheader mb-0">Tambahkan user baru dengan role ADMIN, OPD, atau BPS.</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-light px-4 py-2 flex items-center gap-2">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
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

  <div class="profile-card">
    <div class="card-title">
      <i class="bi bi-person-plus"></i>
      Informasi Akun User
    </div>

    <form method="POST" action="{{ route('users.store') }}">
      @csrf

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-[160px] flex-shrink-0 font-semibold">Nama Lengkap</label>
        <div class="flex-grow max-w-md w-full ml-4">
          <input type="text" class="form-control w-full @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama') }}" required />
          @error('nama')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-[160px] flex-shrink-0 font-semibold">Username</label>
        <div class="flex-grow max-w-md w-full ml-4">
          <input type="text" class="form-control w-full @error('username') is-invalid @enderror" name="username" value="{{ old('username') }}" required />
          @error('username')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <label class="form-label m-0 md:min-w-[160px] flex-shrink-0 font-semibold">Role</label>
        <div class="flex-grow max-w-md w-full ml-4">
          <select name="role" class="form-select w-full @error('role') is-invalid @enderror" required>
            <option value="">-- Pilih Role --</option>
            <option value="admin" {{ old('role')=='admin'?'selected':'' }}>Admin</option>
            <option value="opd" {{ old('role')=='opd'?'selected':'' }}>OPD</option>
            <option value="bps" {{ old('role')=='bps'?'selected':'' }}>BPS</option>
          </select>
          @error('role')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <hr class="form-divider" />

      <div class="card-title mt-6">
        <i class="bi bi-shield-lock"></i>
        Pengaturan Password
      </div>

      <div class="mb-4 flex flex-col md:flex-row md:items-start gap-2 md:gap-4 mt-4">
        <label class="form-label m-0 md:mt-3 md:min-w-[160px] flex-shrink-0 font-semibold">Password Baru</label>
        <div class="flex-grow max-w-md w-full md:ml-4 mt-1">
          <div class="flex">
            <input type="password" id="passwordBaru" class="form-control w-full rounded-r-none border-r-0 focus:z-10 @error('password') is-invalid @enderror" name="password" required />
            <button type="button" class="btn btn-outline-light px-4 rounded-l-none" onclick="togglePassword('passwordBaru', this)" aria-label="Tampilkan password baru">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
          @error('password')
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

      <div class="mb-4 flex flex-col md:flex-row md:items-start gap-2 md:gap-4">
        <label class="form-label m-0 md:mt-3 md:min-w-[160px] flex-shrink-0 font-semibold">Konfirmasi Password</label>
        <div class="flex-grow max-w-md w-full md:ml-4 mt-1">
          <div class="flex">
            <input type="password" id="passwordBaruConfirmation" class="form-control w-full rounded-r-none border-r-0 focus:z-10 @error('password_confirmation') is-invalid @enderror" name="password_confirmation" required />
            <button type="button" class="btn btn-outline-light px-4 rounded-l-none" onclick="togglePassword('passwordBaruConfirmation', this)" aria-label="Tampilkan konfirmasi password">
              <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
          </div>
          @error('password_confirmation')
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <div class="mt-6 flex">
        <label class="form-label m-0 md:min-w-[160px] flex-shrink-0 font-semibold md:block hidden"></label>
        <button type="submit" class="btn btn-primary flex items-center px-4 py-2 w-full md:w-auto md:ml-4 justify-center">
          <i class="bi bi-person-plus-fill me-2"></i> Simpan User Baru
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
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

  window.togglePassword = function(inputId, btn){
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
@endpush
@endsection
