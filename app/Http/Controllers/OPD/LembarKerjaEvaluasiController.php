<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Kriteria;
use App\Models\LembarKerjaEvaluasi;
use App\Models\OpdMenuSetting;
use App\Models\Tahun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LembarKerjaEvaluasiController extends Controller
{
    public function create(Request $request)
    {
        $userId = auth()->id();

        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return view('opd.lke.unavailable');
        }

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

        $domains = Domain::with(['kriterias' => function ($q) {
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
        ]);
    }

    public function autosave(Request $request)
    {
        $userId = auth()->id();
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
        $userId = auth()->id();
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $lke = LembarKerjaEvaluasi::where('id', $request->lke_id)
            ->where('user_id', $userId)
            ->firstOrFail();

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
        $userId = auth()->id();
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $request->validate([
            'lke_id' => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
        ]);

        $lke = LembarKerjaEvaluasi::where('id', $request->lke_id)
            ->where('user_id', $userId)
            ->firstOrFail();

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
        abort_if($lke->user_id !== auth()->id(), 403);

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
        $userId = auth()->id();
        if (!$this->isMenuIsiLkeAvailable($userId)) {
            return $this->accessDenied('Menu Isi Lembar Kerja Evaluasi Tidak ada');
        }

        $request->validate([
            'tahun_id'          => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan'     => ['nullable', 'string', 'max:250'],
            'nomor_rekomendasi' => ['nullable', 'string', 'max:255'],
        ]);

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
