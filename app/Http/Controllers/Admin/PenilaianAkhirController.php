<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPenilaianAkhir;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PenilaianAkhirController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $opds = User::where('role', 'opd')
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('nama', 'like', "%{$search}%")
                   ->orWhere('username', 'like', "%{$search}%");
            }))
            ->orderBy('nama')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Ambil nilai akhir untuk semua OPD di halaman ini
        $userIds = $opds->pluck('id');
        $penilaianMap = AdminPenilaianAkhir::whereIn('user_id', $userIds)
            ->orderByDesc('tahun')
            ->get()
            ->groupBy('user_id');

        return view('admin.penilaian-akhir.index', compact('opds', 'penilaianMap', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'     => ['required', 'integer', 'exists:users,id'],
            'tahun'       => ['required', 'integer', 'in:' . now()->year],
            'nilai_akhir' => ['required', 'numeric', 'min:1', 'max:5'],
            'catatan'     => ['nullable', 'string', 'max:1000'],
            'file'        => ['nullable', 'file', 'max:10240',
                              'mimes:pdf,doc,docx,jpg,jpeg,png,webp,bmp'],
        ], [
            'tahun.in'   => 'Nilai akhir hanya dapat diinput untuk tahun berjalan (' . now()->year . ').',
            'file.mimes' => 'Format file harus PDF, DOC, DOCX, atau gambar (JPG/PNG/WEBP/BMP).',
            'file.max'   => 'Ukuran file maksimal 10MB.',
        ]);

        $userId = (int) $validated['user_id'];
        $tahun  = (int) $validated['tahun'];

        $existing     = AdminPenilaianAkhir::where('user_id', $userId)->where('tahun', $tahun)->first();
        $filePath     = $existing?->file;
        $originalName = $existing?->original_name;

        if ($request->hasFile('file')) {
            if ($existing?->file && Storage::disk('public')->exists($existing->file)) {
                Storage::disk('public')->delete($existing->file);
            }
            $file     = $request->file('file');
            $original = $file->getClientOriginalName();
            $ext      = strtolower($file->getClientOriginalExtension());
            $base     = pathinfo($original, PATHINFO_FILENAME);
            $safe     = Str::slug($base) ?: 'file';
            $unique   = now()->format('YmdHis') . '-' . Str::random(6);
            $filePath     = $file->storeAs("penilaian-akhir/user-{$userId}/tahun-{$tahun}", "{$safe}-{$unique}.{$ext}", 'public');
            $originalName = $original;
        }

        AdminPenilaianAkhir::updateOrCreate(
            ['user_id' => $userId, 'tahun' => $tahun],
            [
                'nilai_akhir'   => round((float) $validated['nilai_akhir'], 2),
                'catatan'       => $validated['catatan'] ?? null,
                'file'          => $filePath,
                'original_name' => $originalName,
            ]
        );

        return redirect()
            ->route('penilaian-akhir.index', ['search' => $request->get('search'), 'page' => $request->get('page')])
            ->with('success', 'Nilai akhir OPD berhasil disimpan.');
    }

    public function destroy(Request $request)
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
            ->route('penilaian-akhir.index', ['search' => $request->get('search'), 'page' => $request->get('page')])
            ->with('success', 'Penilaian akhir berhasil dihapus.');
    }
}
