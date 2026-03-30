<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\Kriteria;
use App\Models\LembarKerjaEvaluasi;
use App\Models\OpdMenuSetting;
use App\Models\Tahun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LembarKerjaEvaluasiController extends Controller
{
    /**
     * Cek apakah paket LKE sudah dikunci oleh BPS (finalisasi penilaian).
     *
     * Paket diidentifikasi oleh (user_id, tahun_id, nama_kegiatan, nomor_rekomendasi).
     * Jika locked, OPD tidak boleh melakukan perubahan apa pun (autosave/upload/finalize).
     */
    private function isBpsLockedPacket(int $userId, int $tahunId, string $namaKegiatan, string $nomorRekomendasi): bool
    {
        return LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRekomendasi)
            ->where('is_locked_bps', true)
            ->exists();
    }

    public function create(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);

        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return view('opd.lke.unavailable');
        }

        // Kebijakan bisnis: OPD hanya boleh membuat 1 aktivitas final per tahun kalender.
        // (Draft masih boleh dibuat/diubah sampai difinalkan.)
        $currentYear = now()->year;
        $existingAnnualActivity = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->whereYear('created_at', $currentYear)
            ->where('status', 'final') // Only block if already finalized
            ->exists();

        if ($existingAnnualActivity) {
            // Jika sudah pernah buat di tahun ini, block akses ke menu isi
            return view('opd.lke.unavailable', [
                'reason' => 'Hanya bisa diisi 1 kali per tahun'
            ]);
        }
        // -------------------------------------------------------

        $tahuns = Tahun::orderBy('tahun', 'desc')->get();
        $tahunId = (int) $request->get('tahun_id', 0);
        $namaKegiatan = trim((string) $request->get('nama_kegiatan', ''));
        $nomorRekomendasi = trim((string) $request->get('nomor_rekomendasi', ''));

        $hasExplicitTahun = $request->query->has('tahun_id');
        $hasExplicitNama = $request->query->has('nama_kegiatan');
        $hasExplicitNomor = $request->query->has('nomor_rekomendasi');

        $latestDraftBaseQuery = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->where('status', 'draft');

        $latestDraft = (clone $latestDraftBaseQuery)
            ->latest('updated_at')
            ->first();

        if ($tahunId <= 0 && $latestDraft) {
            $tahunId = (int) $latestDraft->tahun_id;
        }

        $latestDraftInSelectedYear = null;
        if ($tahunId > 0) {
            $latestDraftInSelectedYear = (clone $latestDraftBaseQuery)
                ->where('tahun_id', $tahunId)
                ->latest('updated_at')
                ->first();
        }

        // Hindari "kebawa" paket lain saat user sengaja memilih tahun baru tanpa konteks paket.
        $allowAutoPrefillUmum = !$hasExplicitTahun || $hasExplicitNama || $hasExplicitNomor;
        $defaultDraftForUmum = $allowAutoPrefillUmum
            ? ($latestDraftInSelectedYear ?: $latestDraft)
            : null;

        if ($namaKegiatan === '' && $defaultDraftForUmum) {
            $namaKegiatan = (string) $defaultDraftForUmum->nama_kegiatan;
        }

        if ($nomorRekomendasi === '' && $defaultDraftForUmum) {
            $nomorRekomendasi = (string) $defaultDraftForUmum->nomor_rekomendasi;
        }

        if ($tahunId <= 0) {
            $lastDraftTahun = LembarKerjaEvaluasi::query()
                ->where('user_id', $userId)
                ->where('status', 'draft')
                ->latest('updated_at')
                ->value('tahun_id');

            $tahunId = (int) ($lastDraftTahun ?: 0);
        }

        $domains = Indikator::with(['kriterias' => function ($q) {
            $q->orderBy('tingkat');
        }])->orderBy('kode')->get();

        $drafts = collect();
        if ($tahunId > 0 && $namaKegiatan !== '' && $nomorRekomendasi !== '') {
            $drafts = LembarKerjaEvaluasi::withCount('buktiDukung')
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRekomendasi)
                ->where('status', 'draft')
                ->get();
        }

        $prefillUmum = [
            'nama_kegiatan' => $namaKegiatan,
            'tahun_id' => $tahunId > 0 ? $tahunId : '',
            'nomor_rekomendasi' => $nomorRekomendasi,
        ];

        $draftMap = $drafts->keyBy('domain_id')->map(function ($lke) {
            return [
                'lke_id' => $lke->id,
                'kriteria_id' => $lke->kriteria_id,
                'penjelasan' => $lke->penjelasan ?? '',
                'tingkat' => $lke->nilai,
                'has_files' => ($lke->bukti_dukung_count ?? 0) > 0,
            ];
        });

        return view('opd.lke.create', [
            'tahuns' => $tahuns,
            'domains' => $domains,
            'tahunId' => $tahunId,
            'prefillUmum' => $prefillUmum,
            'draftMap' => $draftMap,
            'isActivityLocked' => (bool)$existingAnnualActivity,
            'canFillDataUmum' => true,
            'canFillIndikator' => true,
            'accessBlocked' => false,
            'accessBlockReason' => null,
            'initialUmumComplete' => ($tahunId > 0 && $namaKegiatan !== '' && $nomorRekomendasi !== ''),
        ]);
    }

    public function autosave(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $request->validate([
            'nama_kegiatan'    => ['required', 'string', 'max:250'],
            'tahun_id'         => ['required', 'integer', 'exists:tahun,id'],
            'nomor_rekomendasi'=> ['required', 'string', 'max:255'],
            'domain_id'        => ['required', 'integer', 'exists:domains,id'],
            'kriteria_id'      => ['nullable', 'integer', 'exists:kriterias,id'],
            'penjelasan'       => ['nullable', 'string'],
        ]);

        if ($this->isBpsLockedPacket(
            $userId,
            (int) $request->tahun_id,
            (string) $request->nama_kegiatan,
            (string) $request->nomor_rekomendasi
        )) {
            return response()->json([
                'ok' => false,
                'message' => 'Penilaian telah difinalisasi oleh BPS. Paket ini terkunci dan tidak dapat diubah lagi.',
            ], 403);
        }

        // RESTRICTION: One finalized activity per year
        $finalizedActivity = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->whereYear('created_at', now()->year)
            ->where('status', 'final')
            ->exists();

        if ($finalizedActivity) {
            return response()->json([
                'ok' => false,
                'message' => 'LKE tahun ini sudah difinalisasi. Anda tidak dapat mengubah data lagi.',
            ], 403);
        }

        // RESTRICTION: One activity at a time (prevent multiple drafts)
        $existingOtherActivity = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->whereYear('created_at', now()->year)
            ->where(function ($q) use ($request) {
                $q->where('nama_kegiatan', '!=', (string) $request->nama_kegiatan)
                  ->orWhere('nomor_rekomendasi', '!=', (string) $request->nomor_rekomendasi);
            })
            ->first();

        if ($existingOtherActivity) {
            return response()->json([
                'ok' => false,
                'message' => 'Anda sudah memiliki kegiatan lain (' . $existingOtherActivity->nama_kegiatan . '). Setiap OPD hanya diperbolehkan input 1 kegiatan per tahun.',
            ], 422);
        }

        // Fetch kriteria ONCE (replaces 2 separate queries: exists + value)
        $kriteria = null;
        $nilai = null;
        if ($request->filled('kriteria_id')) {
            $kriteria = Kriteria::select('id', 'domain_id', 'tingkat')
                ->where('id', $request->kriteria_id)
                ->first();

            if (!$kriteria || (int) $kriteria->domain_id !== (int) $request->domain_id) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Kriteria tidak sesuai indikator yang dipilih.',
                ], 422);
            }

            $nilai = $kriteria->tingkat;
        }

        $lke = LembarKerjaEvaluasi::updateOrCreate(
            [
                'user_id'          => $userId,
                'tahun_id'         => (int) $request->tahun_id,
                'domain_id'        => (int) $request->domain_id,
                'nama_kegiatan'    => (string) $request->nama_kegiatan,
                'nomor_rekomendasi'=> (string) $request->nomor_rekomendasi,
                'status'           => 'draft',
            ],
            [
                'kriteria_id' => $request->kriteria_id,
                'nilai'       => $nilai,
                'penjelasan'  => $request->penjelasan ?? '',
            ]
        );

        // Use withCount instead of a separate exists() query
        $lke->loadCount('buktiDukung');
        $hasFiles = ($lke->bukti_dukung_count ?? 0) > 0;

        $hasK = (bool) $lke->kriteria_id;
        $hasP = strlen(trim((string) $lke->penjelasan)) > 0;
        $tingkat = $lke->nilai ?? null;

        $isFilled = $hasK || $hasP || $hasFiles;
        $isDone   = $hasK && $hasP;
        if ($tingkat !== null && (int) $tingkat !== 1) {
            $isDone = $isDone && $hasFiles;
        }

        return response()->json([
            'ok'       => true,
            'lke_id'   => $lke->id,
            'progress' => $isDone ? 'done' : ($isFilled ? 'progress' : 'empty'),
            'tingkat'  => $tingkat,
            'has_files'=> $hasFiles,
        ]);
    }

    public function uploadBukti(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $lke = LembarKerjaEvaluasi::where('id', $request->lke_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ((bool) $lke->is_locked_bps) {
            return response()->json([
                'ok' => false,
                'message' => 'Penilaian telah difinalisasi oleh BPS. Paket ini terkunci dan tidak dapat diubah lagi.',
            ], 403);
        }

        $request->validate([
            'lke_id' => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240'],
        ], [
            'files.*.max' => 'Ukuran maksimal setiap file adalah 10MB.',
        ]);

        foreach ($request->file('files') as $file) {
            $original = $file->getClientOriginalName();
            $ext = $file->getClientOriginalExtension();
            $base = pathinfo($original, PATHINFO_FILENAME);

            $safeBase = Str::slug($base);
            if ($safeBase === '') {
                $safeBase = 'file';
            }

            $unique = now()->format('YmdHis') . '-' . Str::random(6);
            $filename = "{$safeBase}-{$unique}." . strtolower($ext);

            $folder = "bukti-dukung/user-{$userId}/tahun-{$lke->tahun_id}/lke-{$lke->id}";
            $path = $file->storeAs($folder, $filename, 'public');

            \App\Models\BuktiDukung::create([
                'lembar_kerja_id' => $lke->id,
                'file' => $path,
                'original_name' => $original,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function finalize(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $request->validate([
            'lke_id' => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
        ]);

        $lke = LembarKerjaEvaluasi::where('id', $request->lke_id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ((bool) $lke->is_locked_bps) {
            return response()->json([
                'ok' => false,
                'message' => 'Penilaian telah difinalisasi oleh BPS. Paket ini terkunci dan tidak dapat diubah lagi.',
            ], 403);
        }

        if ($lke->status === 'final') {
            return response()->json(['ok' => true, 'message' => 'Sudah final']);
        }

        $lke->status = 'final';
        $lke->save();

        return response()->json(['ok' => true]);
    }

    private function isComplete(LembarKerjaEvaluasi $lke): bool
    {
        $hasK = (bool) $lke->kriteria_id;
        $hasP = strlen(trim((string) $lke->penjelasan)) > 0;

        if (!$hasK || !$hasP) {
            return false;
        }

        $tingkat = (int) ($lke->nilai ?? 0);
        if ($tingkat === 1) {
            return true;
        }

        // Optimized for eager-loaded count instead of ->exists() to prevent N+1 queries.
        return ($lke->bukti_dukung_count ?? 0) > 0;
    }

    public function files(LembarKerjaEvaluasi $lke)
    {
        // Allow access if owner, OR if role is admin or bps
        $user = Auth::user();
        abort_if(
            $lke->user_id !== $user->id && !in_array($user->role, ['admin', 'bps']), 
            403
        );

        $files = $lke->buktiDukung()
            ->latest()
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'file' => $f->file,
                    'url' => asset('storage/' . $f->file),
                    'name' => $f->original_name ?: basename($f->file),
                ];
            });

        return response()->json(['ok' => true, 'files' => $files]);
    }

    public function finalizeAll(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $request->validate([
            'tahun_id'          => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan'     => ['nullable', 'string', 'max:250'],
            'nomor_rekomendasi' => ['nullable', 'string', 'max:255'],
        ]);

        // RESTRICTION: One finalized activity per year
        $finalizedActivityExists = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->whereYear('created_at', now()->year)
            ->where('status', 'final')
            ->exists();

        if ($finalizedActivityExists) {
             return response()->json([
                'ok' => false,
                'message' => 'Anda sudah memfinalisasi LKE untuk tahun ini. Tidak dapat memproses finalisasi lagi.',
            ], 403);
        }

        // Query hanya untuk paket tertentu milik user ini.
        $query = LembarKerjaEvaluasi::where('user_id', $userId)
            ->where('tahun_id', (int) $request->tahun_id);

        // Filter by package ketika ada (menghindari paket lain ikut ter-final).
        if ($request->filled('nama_kegiatan')) {
            $query->where('nama_kegiatan', (string) $request->nama_kegiatan);
        }
        if ($request->filled('nomor_rekomendasi')) {
            $query->where('nomor_rekomendasi', (string) $request->nomor_rekomendasi);
        }

        if ((clone $query)->where('is_locked_bps', true)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Penilaian telah difinalisasi oleh BPS. Paket ini terkunci dan tidak dapat diubah lagi.',
            ], 403);
        }

        // Hanya ubah yang masih draft atau revisi → final dalam satu UPDATE (tanpa loop PHP).
        $updated = (clone $query)
            ->whereIn('status', ['draft', 'revisi'])
            ->update(['status' => 'final']);

        // Hitung total final setelah update (untuk informasi saja).
        $totalFinal = (clone $query)
            ->where('status', 'final')
            ->count();

        return response()->json([
            'ok'      => true,
            'message' => "Final submit selesai. Indikator yang diubah ke final: {$updated}. Total final di paket ini: {$totalFinal}.",
        ]);
    }

    private function isMenuIsiLkeAvailable(int $userId): bool
    {
        $setting = OpdMenuSetting::where('user_id', $userId)->first();
        if (!$setting) {
            return true;
        }

        return (bool) $setting->can_fill_data_umum && (bool) $setting->can_fill_indikator;
    }

    private function accessDenied(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ], 403);
    }
}
