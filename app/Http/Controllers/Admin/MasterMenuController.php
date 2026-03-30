<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpdMenuSetting;
use App\Models\RoleInformasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterMenuController extends Controller
{
    public function index(Request $request)
    {
        $role = (string) $request->get('role', 'opd');
        $allowedRoles = ['opd', 'admin', 'bps'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'opd';
        }

        $userIds = User::query()
            ->where('role', $role)
            ->pluck('id');

        $totalUsers = $userIds->count();
        $disabledUsers = 0;
        if ($totalUsers > 0 && $role === 'opd') {
            $disabledUsers = OpdMenuSetting::query()
                ->whereIn('user_id', $userIds)
                ->where(function ($q) {
                    $q->where('can_fill_data_umum', false)
                        ->orWhere('can_fill_indikator', false);
                })
                ->count();
        }

        // Row setting yang tidak ada dianggap "aktif" (default true).
        $menuIsiLkeAvailable = $disabledUsers === 0;

        // Load informasi per role
        $informasiAdmin = RoleInformasi::forRole('admin');
        $informasiOpd   = RoleInformasi::forRole('opd');
        $informasiBps   = RoleInformasi::forRole('bps');

        return view('admin.master-menu.index', compact(
            'role',
            'totalUsers',
            'disabledUsers',
            'menuIsiLkeAvailable',
            'informasiAdmin',
            'informasiOpd',
            'informasiBps'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(['opd'])],
            'menu_isi_lke_available' => ['required', 'boolean'],
        ]);

        $role = (string) $validated['role'];
        $isAvailable = (bool) $validated['menu_isi_lke_available'];
        $userIds = User::query()
            ->where('role', $role)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return redirect()
                ->route('master-menu.index', ['role' => $role])
                ->with('failed', 'Belum ada user pada role yang dipilih.');
        }

        if ($isAvailable) {
            // Aktifkan semua: hapus override supaya kembali ke default true.
            OpdMenuSetting::query()->whereIn('user_id', $userIds)->delete();
        } else {
            foreach ($userIds as $userId) {
                OpdMenuSetting::updateOrCreate(
                    ['user_id' => (int) $userId],
                    [
                        'can_fill_data_umum' => false,
                        'can_fill_indikator' => false,
                    ]
                );
            }
        }

        return redirect()
            ->route('master-menu.index', ['role' => $role])
            ->with('success', 'Master Menu berhasil disimpan untuk semua user pada role ' . strtoupper($role) . '.');
    }

    public function updateInformasi(Request $request)
    {
        $validated = $request->validate([
            'role'  => ['required', 'string', Rule::in(['admin', 'opd', 'bps'])],
            'judul' => ['required', 'string', 'max:200'],
            'isi'   => ['required', 'string', function ($attribute, $value, $fail) {
                $wordCount = $value !== '' ? count(preg_split('/\s+/', trim($value))) : 0;
                if ($wordCount > 300) {
                    $fail("Isi informasi maksimal 300 kata (saat ini {$wordCount} kata).");
                }
            }],
            'warna' => ['nullable', 'string', Rule::in(['neutral', 'blue', 'red', 'amber', 'emerald'])],
        ]);

        RoleInformasi::updateOrCreate(
            ['role' => $validated['role']],
            [
                'judul' => $validated['judul'],
                'isi'   => $validated['isi'],
                'warna' => $validated['warna'] ?? 'neutral',
            ]
        );

        return redirect()
            ->route('master-menu.index', ['role' => $validated['role']])
            ->with('success', 'Informasi untuk role ' . strtoupper($validated['role']) . ' berhasil diperbarui.');
    }
}
