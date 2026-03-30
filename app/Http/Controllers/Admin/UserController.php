<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Validation\Rules\Password;


class UserController extends Controller
{
    public function index(Request $request)
{
    $q = trim((string) $request->get('q', ''));

    $users = User::query()
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('username', 'like', "%{$q}%")
                    ->orWhere('nama', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%");
            });
        })
        ->orderBy('role')
        ->latest()
        ->paginate(10)
        ->appends(['q' => $q]); // biar search keikut pagination

    return view('admin.users.index', compact('users', 'q'));
}


    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required','string','max:60','unique:users,username'],
            'nama'     => ['required','string','max:60'],
            'role'     => ['required','in:admin,opd,bps'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ]);


        User::create([
            'username' => $validated['username'],
            'nama'     => $validated['nama'],
            'role'     => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required','string','max:60','unique:users,username,'.$user->id],
            'nama'     => ['required','string','max:60'],
            'role'     => ['required','in:admin,opd,bps'],
            // password opsional saat edit
            'password' => ['nullable','string','min:8','confirmed'],
        ]);

        $user->username = $validated['username'];
        $user->nama     = $validated['nama'];
        $user->role     = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User berhasil diupdate.');
    }
    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('failed', 'Tidak bisa menghapus akun yang sedang login.');
        }

        try {
            $user->delete();
            return back()->with('success', 'User berhasil dihapus.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == '23000') {
                return back()->with('failed', 'User ini tidak dapat dihapus karena sedang digunakan (berelasi) dengan data lainnya.');
            }
            return back()->with('failed', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }


}
