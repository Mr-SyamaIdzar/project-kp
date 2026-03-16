<?php

namespace App\Http\Controllers\BPS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
            'layout' => 'layouts.bps',
            'routePrefix' => 'bps',
            'title' => 'Profile',
            'header' => 'Profile Saya',
            'subheader' => 'Kelola informasi profil dan keamanan akun Anda.',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'foto' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,png,jpg'],
        ]);

        $user->nama = $validated['nama'];

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }

            $user->foto = $request->file('foto')->store('avatars/users', 'public');
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password_baru' => [
                'required',
                'confirmed',
                'different:password_lama',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_lama.current_password' => 'Password lama tidak sesuai.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak sesuai.',
            'password_baru.different' => 'Password baru tidak boleh sama dengan password lama.',
            'password_baru' => 'Password baru harus minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan simbol.',
        ]);

        $user->password = Hash::make($validated['password_baru']);
        $user->save();

        return back()->with('success', 'Password berhasil diubah.');
    }
}
