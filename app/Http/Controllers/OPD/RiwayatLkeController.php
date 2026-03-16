<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Kriteria;
use App\Models\LembarKerjaEvaluasi;
use App\Models\LkeRevisiRequest;
use App\Models\Tahun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RiwayatLkeController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) auth()->id();
        $tahunId = (int) $request->get('tahun_id', 0);
        $totalIndikator = (int) Domain::count();

        $tahuns = Tahun::orderByDesc('tahun')->get();

        $rows = LembarKerjaEvaluasi::query()
            ->selectRaw("
                tahun_id,
                nama_kegiatan,
                nomor_rekomendasi,
                MAX(updated_at) as last_update,
                COUNT(DISTINCT domain_id) as cnt_total
            ")
            ->where('user_id', $userId)
            ->whereIn('status', ['final', 'revisi'])
            ->when($tahunId > 0, fn ($q) => $q->where('tahun_id', $tahunId))
            ->groupBy('tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->orderByDesc('last_update')
            ->paginate(10)
            ->withQueryString();

        $tahunMap = $rows->pluck('tahun_id')->unique()->filter()->isNotEmpty()
            ? Tahun::whereIn('id', $rows->pluck('tahun_id')->unique())->get()->keyBy('id')
            : collect();

        $revisiMap = collect();
        if (Schema::hasTable('lke_revisi_requests')) {
            $requested = LkeRevisiRequest::query()
                ->select(['tahun_id', 'nama_kegiatan', 'nomor_rekomendasi', 'domain_id'])
                ->where('user_id', $userId)
                ->where('status', 'requested')
                ->when($tahunId > 0, fn ($q) => $q->where('tahun_id', $tahunId))
                ->get();

            $revisiMap = $requested
                ->groupBy(function ($row) {
                    return $row->tahun_id . '|' . $row->nama_kegiatan . '|' . $row->nomor_rekomendasi;
                })
                ->map(fn ($group) => (int) $group->pluck('domain_id')->unique()->count());
        }

        $rows->setCollection(
            $rows->getCollection()->map(function ($row) use ($revisiMap, $totalIndikator) {
                $key = $row->tahun_id . '|' . $row->nama_kegiatan . '|' . $row->nomor_rekomendasi;
                $cntRevisi = (int) ($revisiMap[$key] ?? 0);
                $cntTerisi = max($totalIndikator - $cntRevisi, 0);

                $row->cnt_revisi = $cntRevisi;
                $row->cnt_terisi = $cntTerisi;
                $row->status_paket = $cntRevisi > 0 ? 'revisi' : 'final';
                return $row;
            })
        );

        return view('opd.lke.riwayat.index', compact('rows', 'tahuns', 'tahunId', 'tahunMap', 'totalIndikator'));
    }

    public function show(Request $request)
    {
        $userId = (int) auth()->id();
        $tahunId = (int) $request->get('tahun_id', 0);
        $namaKegiatan = (string) $request->get('nama_kegiatan', '');
        $nomorRek = (string) $request->get('nomor_rekomendasi', '');

        abort_if($tahunId <= 0 || $namaKegiatan === '' || $nomorRek === '', 404);

        $tahun = Tahun::findOrFail($tahunId);

        $rawItems = LembarKerjaEvaluasi::query()
            ->with(['domain.kriterias', 'kriteria', 'buktiDukung'])
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRek)
            ->orderByDesc('id')
            ->get();

        abort_if($rawItems->isEmpty(), 404);

        $items = $rawItems
            ->unique('domain_id')
            ->keyBy('domain_id');

        $beforeRevisiItems = $rawItems
            ->filter(fn ($row) => (string) $row->status !== 'revisi')
            ->unique('domain_id')
            ->keyBy('domain_id');

        $domains = Domain::with(['kriterias' => function ($q) {
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        $activeRevisiRequests = collect();
        $revisedRequestMap = collect();
        if (Schema::hasTable('lke_revisi_requests')) {
            $activeRevisiRequests = LkeRevisiRequest::query()
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek)
                ->where('status', 'requested')
                ->get()
                ->keyBy('domain_id');

            $revisedRequestMap = LkeRevisiRequest::query()
                ->with(['revisedLke.buktiDukung'])
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek)
                ->where('status', 'revised')
                ->get()
                ->keyBy('domain_id');
        }

        $canReviseDomainIds = $activeRevisiRequests->keys()->map(fn ($id) => (int) $id)->all();

        return view('opd.lke.riwayat.show', compact(
            'tahun',
            'domains',
            'items',
            'beforeRevisiItems',
            'namaKegiatan',
            'nomorRek',
            'activeRevisiRequests',
            'revisedRequestMap',
            'canReviseDomainIds'
        ));
    }

    public function storeRevisi(Request $request)
    {
        if (!Schema::hasTable('lke_revisi_requests')) {
            return back()->with('failed', 'Tabel revisi belum tersedia. Jalankan php artisan migrate terlebih dahulu.');
        }

        $userId = (int) auth()->id();

        $validated = $request->validate([
            'tahun_id' => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan' => ['required', 'string', 'max:250'],
            'nomor_rekomendasi' => ['required', 'string', 'max:255'],
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'kriteria_id' => ['required', 'integer', 'exists:kriterias,id'],
            'penjelasan' => ['required', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'],
        ], [
            'files.*.max' => 'Ukuran maksimal setiap file adalah 10MB.',
        ]);

        $domainId = (int) $validated['domain_id'];
        $kriteriaId = (int) $validated['kriteria_id'];

        $kriteria = Kriteria::query()
            ->where('id', $kriteriaId)
            ->where('domain_id', $domainId)
            ->first();

        if (!$kriteria) {
            return back()->with('failed', 'Kriteria tidak sesuai dengan indikator yang dipilih.');
        }

        $tingkatDipilih = (int) $kriteria->tingkat;

        $revisiRequest = LkeRevisiRequest::query()
            ->where('user_id', $userId)
            ->where('tahun_id', (int) $validated['tahun_id'])
            ->where('nama_kegiatan', (string) $validated['nama_kegiatan'])
            ->where('nomor_rekomendasi', (string) $validated['nomor_rekomendasi'])
            ->where('domain_id', $domainId)
            ->where('status', 'requested')
            ->first();

        if (!$revisiRequest) {
            return back()->with('failed', 'Indikator ini belum diizinkan untuk revisi oleh BPS.');
        }

        $baseWhere = [
            'user_id' => $userId,
            'tahun_id' => (int) $validated['tahun_id'],
            'nama_kegiatan' => (string) $validated['nama_kegiatan'],
            'nomor_rekomendasi' => (string) $validated['nomor_rekomendasi'],
            'domain_id' => (int) $validated['domain_id'],
        ];

        $existingRevisedRequest = LkeRevisiRequest::query()
            ->where($baseWhere)
            ->where('status', 'revised')
            ->where('id', '!=', (int) $revisiRequest->id)
            ->first();

        // Selalu append ke revisi terakhir dalam paket yang sama agar file lama tidak "hilang".
        $existingRevisedLke = LembarKerjaEvaluasi::query()
            ->where($baseWhere)
            ->where('status', 'revisi')
            ->orderByDesc('id')
            ->first();

        // Data pembanding terakhir: jika sudah pernah revisi pakai revisi terakhir,
        // kalau belum, pakai data non-revisi terakhir.
        $latestNonRevisiLke = LembarKerjaEvaluasi::query()
            ->where($baseWhere)
            ->where('status', '!=', 'revisi')
            ->orderByDesc('id')
            ->first();
        $baselineLke = $existingRevisedLke ?: $latestNonRevisiLke;

        $hasIncomingFiles = $request->hasFile('files');
        $existingFileCount = (int) ($existingRevisedLke?->buktiDukung()->count() ?? 0);

        // Revisi wajib punya perubahan baru (kriteria/penjelasan) atau file baru.
        $kriteriaSama = (int) ($baselineLke?->kriteria_id ?? 0) === (int) $validated['kriteria_id'];
        $penjelasanLama = trim((string) ($baselineLke?->penjelasan ?? ''));
        $penjelasanBaru = trim((string) $validated['penjelasan']);
        $penjelasanSama = $penjelasanLama === $penjelasanBaru;
        if ($baselineLke && $kriteriaSama && $penjelasanSama && !$hasIncomingFiles) {
            return back()
                ->with('failed', 'Revisi tidak boleh sama dengan data sebelumnya. Ubah kriteria/penjelasan atau tambahkan file bukti baru.')
                ->withInput();
        }

        if ($tingkatDipilih !== 1 && !$hasIncomingFiles && $existingFileCount === 0) {
            return back()
                ->withErrors([
                    'files' => 'File bukti revisi wajib diupload jika memilih kriteria tingkat 2-5.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $request, $userId, $kriteria, $revisiRequest, $existingRevisedRequest, $existingRevisedLke, $latestNonRevisiLke) {
            $targetLke = $existingRevisedLke ?: $latestNonRevisiLke;

            if ($targetLke) {
                $targetLke->kriteria_id = (int) $validated['kriteria_id'];
                $targetLke->nilai = (int) $kriteria->tingkat;
                $targetLke->penjelasan = (string) $validated['penjelasan'];
                $targetLke->status = 'revisi';
                $targetLke->save();
            } else {
                // Should not happen if history is correct, but just in case
                $targetLke = LembarKerjaEvaluasi::create([
                    'user_id' => $userId,
                    'tahun_id' => (int) $validated['tahun_id'],
                    'domain_id' => (int) $validated['domain_id'],
                    'kriteria_id' => (int) $validated['kriteria_id'],
                    'nama_kegiatan' => (string) $validated['nama_kegiatan'],
                    'nomor_rekomendasi' => (string) $validated['nomor_rekomendasi'],
                    'nilai' => (int) $kriteria->tingkat,
                    'penjelasan' => (string) $validated['penjelasan'],
                    'status' => 'revisi',
                ]);
            }

            foreach ($request->file('files', []) as $file) {
                $original = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $base = pathinfo($original, PATHINFO_FILENAME);

                $safeBase = Str::slug($base);
                if ($safeBase === '') {
                    $safeBase = 'file';
                }

                $unique = now()->format('YmdHis') . '-' . Str::random(6);
                $filename = "{$safeBase}-{$unique}." . strtolower($ext);

                $folder = "bukti-dukung/user-{$userId}/tahun-{$targetLke->tahun_id}/lke-{$targetLke->id}";
                $path = $file->storeAs($folder, $filename, 'public');

                \App\Models\BuktiDukung::create([
                    'lembar_kerja_id' => $targetLke->id,
                    'file' => $path,
                    'original_name' => $original,
                ]);
            }

            if ($existingRevisedRequest) {
                $existingRevisedRequest->delete();
            }

            $revisiRequest->status = 'revised';
            $revisiRequest->revised_lke_id = (int) $targetLke->id;
            $revisiRequest->revised_at = now();
            $revisiRequest->save();
        });

        return back()->with('success', 'Revisi indikator berhasil disimpan.');
    }
}
