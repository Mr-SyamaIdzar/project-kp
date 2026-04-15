<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPenilaianAkhir;
use App\Models\GlobalSetting;
use App\Models\OpdMenuSetting;
use App\Models\RoleInformasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        // Load feature toggles
        $revisiDokumenEnabled  = GlobalSetting::isEnabled('revisi_dokumen_enabled');
        $interviewInputEnabled = GlobalSetting::isEnabled('interview_input_enabled');

        return view('admin.master-menu.index', compact(
            'role',
            'totalUsers',
            'disabledUsers',
            'menuIsiLkeAvailable',
            'informasiAdmin',
            'informasiOpd',
            'informasiBps',
            'revisiDokumenEnabled',
            'interviewInputEnabled',
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

    /**
     * Simpan toggle fitur global (Revisi Dokumen & Input Hasil Interview).
     */
    public function updateFeatureToggles(Request $request)
    {
        $revisiDokumenEnabled  = $request->has('revisi_dokumen_enabled');
        $interviewInputEnabled = $request->has('interview_input_enabled');

        GlobalSetting::set('revisi_dokumen_enabled',  $revisiDokumenEnabled  ? '1' : '0');
        GlobalSetting::set('interview_input_enabled', $interviewInputEnabled ? '1' : '0');
        GlobalSetting::flushCache();

        return redirect()
            ->route('master-menu.index')
            ->with('success', 'Pengaturan fitur berhasil disimpan.');
    }

    /**
     * Simpan atau update nilai akhir OPD dari admin (1 per user per tahun).
     */
    public function storePenilaianAkhir(Request $request)
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'integer', 'exists:users,id'],
            'tahun'       => ['required', 'integer', 'in:' . now()->year],  // hanya tahun berjalan
            'nilai_akhir' => ['required', 'numeric', 'min:1', 'max:5'],
            'catatan'     => ['nullable', 'string', 'max:1000'],
            'file'        => ['nullable', 'file', 'max:10240',
                              'mimes:pdf,doc,docx,jpg,jpeg,png,webp,bmp'],
        ], [
            'tahun.in'       => 'Nilai akhir hanya dapat diinput untuk tahun berjalan (' . now()->year . ').',
            'file.mimes'     => 'Format file harus PDF, DOC, DOCX, atau gambar (JPG/PNG/WEBP/BMP).',
            'file.max'       => 'Ukuran file maksimal 10MB.',
        ]);

        $userId = (int) $validated['user_id'];
        $tahun  = (int) $validated['tahun'];

        // Ambil record lama jika sudah ada (untuk hapus file lama jika ada file baru)
        $existing = AdminPenilaianAkhir::where('user_id', $userId)
            ->where('tahun', $tahun)
            ->first();

        $filePath     = $existing?->file;
        $originalName = $existing?->original_name;

        if ($request->hasFile('file')) {
            // Hapus file lama jika ada
            if ($existing && $existing->file && Storage::disk('public')->exists($existing->file)) {
                Storage::disk('public')->delete($existing->file);
            }

            $file     = $request->file('file');
            $original = $file->getClientOriginalName();
            $ext      = strtolower($file->getClientOriginalExtension());
            $base     = pathinfo($original, PATHINFO_FILENAME);
            $safeBase = Str::slug($base) ?: 'file';
            $unique   = now()->format('YmdHis') . '-' . Str::random(6);
            $filename = "{$safeBase}-{$unique}.{$ext}";
            $folder   = "penilaian-akhir/user-{$userId}/tahun-{$tahun}";
            $filePath     = $file->storeAs($folder, $filename, 'public');
            $originalName = $original;
        }

        AdminPenilaianAkhir::updateOrCreate(
            ['user_id' => $userId, 'tahun' => $tahun],
            [
                'nilai_akhir'  => round((float) $validated['nilai_akhir'], 2),
                'catatan'      => $validated['catatan'] ?? null,
                'file'         => $filePath,
                'original_name'=> $originalName,
            ]
        );

        return redirect()
            ->route('master-menu.index', ['role' => 'opd'])
            ->with('success', 'Nilai akhir OPD berhasil disimpan.');
    }

    /**
     * Hapus penilaian akhir beserta file-nya.
     */
    public function destroyPenilaianAkhir(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tahun'   => ['required', 'integer'],
        ]);

        $record = AdminPenilaianAkhir::where('user_id', (int) $validated['user_id'])
            ->where('tahun', (int) $validated['tahun'])
            ->first();

        if ($record) {
            if ($record->file && Storage::disk('public')->exists($record->file)) {
                Storage::disk('public')->delete($record->file);
            }
            $record->delete();
        }

        return redirect()
            ->route('master-menu.index', ['role' => 'opd'])
            ->with('success', 'Penilaian akhir berhasil dihapus.');
    }
}
