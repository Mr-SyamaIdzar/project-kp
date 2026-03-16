# Dokumentasi Fitur Profile OPD

## Ringkasan
Fitur Profile untuk role OPD memungkinkan user untuk:
1. **Update Profil** - Mengubah nama lengkap dan upload foto profil (max 2MB)
2. **Ubah Password** - Mengganti password dengan validasi password lama dan requirement password baru

---

## Routes

```php
// Di routes/web.php (OPD middleware group)
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile/update-profile', [ProfileController::class, 'updateProfile'])->name('profile.update-profile');
Route::post('/profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update-password');
```

**Route Names untuk referensi:**
- `opd.profile.edit` - Akses halaman profile
- `opd.profile.update-profile` - Submit form update nama & foto
- `opd.profile.update-password` - Submit form ubah password

---

## Controller: OPD/ProfileController

### Method 1: `edit()`
```php
public function edit()
{
    $user = Auth::user();
    return view('opd.profile.edit', compact('user'));
}
```
**Fungsi:** Menampilkan halaman profile dengan form update profil dan password

---

### Method 2: `updateProfile(Request $request)`
**Validasi:**
- `nama` - Required, string, max 60 karakterakter
- `foto` - Optional, image, max 2048KB (2MB), format: jpeg/png/jpg/gif

**Proses:**
1. Validasi input
2. Update nama user
3. Jika ada upload foto baru:
   - Hapus foto lama (jika ada)
   - Store foto ke `storage/app/public/avatars/users/`
   - Update path foto di database
4. Return redirect dengan pesan sukses

---

### Method 3: `updatePassword(Request $request)`
**Validasi:**
- `password_lama` - Required, harus sama dengan password user saat ini (current_password)
- `password_baru` - Required:
  - Min 8 karakter
  - Harus mengandung huruf besar (A-Z)
  - Harus mengandung huruf kecil (a-z)
  - Harus mengandung angka (0-9)
  - Harus mengandung simbol (!@#$%^&*)
  - Harus dikonfirmasi (password_baru_confirmation)
  - Tidak boleh sama dengan password lama

**Proses:**
1. Validasi input
2. Hash password baru
3. Update password di database
4. Return redirect dengan pesan sukses

---

## View: resources/views/opd/profile/edit.blade.php

### Fitur UI/UX:
1. **Avatar Preview**
   - Preview foto current atau icon placeholder
   - Real-time preview saat user pilih foto baru
   - Button untuk open file picker

2. **Form Update Profil**
   - Input Nama Lengkap (editable)
   - Input Username (disabled/readonly)
   - Input Role (disabled/readonly)
   - Button Simpan Perubahan

3. **Form Ubah Password**
   - Input Password Lama (type: password)
   - Input Password Baru (type: password)
   - Real-time password requirement checker
   - Input Konfirmasi Password Baru (type: password)
   - Button Ubah Password

4. **Password Requirements Checker**
   - Visual indicator (icon + text) untuk setiap requirement
   - Green checkmark jika requirement terpenuhi
   - Gray checkmark jika belum terpenuhi
   - Update real-time saat user mengetik

5. **Error & Success Messages**
   - Alert box untuk error validation
   - Alert box untuk success message
   - Inline error feedback per field

### Styling:
- Selaras dengan dashboard OPD (warna, border radius, spacing)
- Dark mode support
- Responsive design (mobile, tablet, desktop)
- Brand color: `#7c3aed` (purple)
- Consistent dengan komponen lain (form control, button, alert)

---

## File Storage

**Path penyimpanan foto:**
```
storage/app/public/avatars/users/{filename}
```

**URL akses (setelah symbolic link dibuat):**
```
/storage/avatars/users/{filename}
```

**Setup Symbolic Link:**
```bash
php artisan storage:link
```
(Sudah dilakukan di project ini)

---

## Integrasi Sidebar

Menu Profile telah ditambahkan ke sidebar OPD dengan:
- Icon: `bi-person-circle`
- Separator (horizontal rule) sebelumnya untuk visual separation
- Active state indicator jika user ada di halaman profile

**File:** `resources/views/partials/opd_sidebar.blade.php`

---

## Validasi Password Detail

Menggunakan Laravel `Password` rule dari `Illuminate\Validation\Rules\Password`:

```php
Password::min(8)          // Minimal 8 karakter
    ->mixedCase()         // Harus ada uppercase dan lowercase
    ->numbers()           // Harus ada angka
    ->symbols()           // Harus ada simbol
```

**Error Messages yang Custom:**
- `password_lama.required` → "Password lama wajib diisi."
- `password_lama.current_password` → "Password lama tidak sesuai."
- `password_baru.required` → "Password baru wajib diisi."
- `password_baru.confirmed` → "Konfirmasi password baru tidak sesuai."
- `password_baru.different` → "Password baru tidak boleh sama dengan password lama."

---

## Testing Checklist

- [ ] Akses halaman profile via `/opd/profile`
- [ ] Update nama lengkap dan simpan
- [ ] Upload foto baru (test dengan file < 2MB)
- [ ] Reject foto > 2MB
- [ ] Reject format file bukan image
- [ ] Avatar preview update real-time
- [ ] Password lama validation (error jika salah)
- [ ] Password baru requirement checker real-time
- [ ] Submit password dengan requirement tidak terpenuhi (error)
- [ ] Sukses ubah password
- [ ] Dark mode styling
- [ ] Responsive design
- [ ] Sidebar menu active state

---

## Files Changed/Created

### Created:
1. `app/Http/Controllers/OPD/ProfileController.php`
2. `resources/views/opd/profile/edit.blade.php`

### Modified:
1. `routes/web.php` - Add profile routes
2. `resources/views/partials/opd_sidebar.blade.php` - Add profile menu link

### No changes needed:
- `app/Models/User.php` - Already has 'foto' in fillable
- `config/filesystems.php` - Already configured
- Storage symbolic link - Sudah ada

---

## Catatan Penting

1. **Password Validation:** Menggunakan `current_password` rule yang memerlukan user sedang login
2. **File Storage:** Foto disimpan di public disk untuk bisa diakses via web
3. **Dark Mode:** Semua styling mendukung light dan dark theme
4. **Responsive:** Mobile-first design dengan breakpoints untuk tablet dan desktop
5. **Error Handling:** Comprehensive validation dengan user-friendly error messages
