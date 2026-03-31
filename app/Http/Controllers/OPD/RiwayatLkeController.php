<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\Kriteria;
use App\Models\LembarKerjaEvaluasi;
use App\Models\LkeRevisiRequest;
use App\Models\Tahun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RiwayatLkeController extends Controller
{
    /**
     * Daftar paket LKE milik OPD (final/revisi).
     *
     * Catatan:
     * - `status_paket` di view adalah status agregat per paket (bukan per-indikator).
     * - Jika BPS sudah finalisasi paket (`is_locked_bps=1`), status paket di-override menjadi "locked"
     *   walaupun masih ada revisi yang sebelumnya belum diselesaikan.
     */
    public function index(Request $request)
    {
        $userId = (int) (Auth::id() ?? 0);
        $tahunId = (int) $request->get('tahun_id', 0);
        $totalIndikator = (int) Indikator::count();

        $tahuns = Tahun::orderByDesc('tahun')->get();

        $rows = LembarKerjaEvaluasi::query()
            ->selectRaw("
                tahun_id,
                nama_kegiatan,
                nomor_rekomendasi,
                MAX(updated_at) as last_update,
                MAX(is_locked_bps) as is_locked_bps,
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
                $row->is_locked_bps = (bool) ($row->is_locked_bps ?? false);
                $row->status_paket = $row->is_locked_bps ? 'locked' : ($cntRevisi > 0 ? 'revisi' : 'final');
                return $row;
            })
        );

        return view('opd.lke.riwayat.index', compact('rows', 'tahuns', 'tahunId', 'tahunMap', 'totalIndikator'));
    }

    public function show(Request $request)
    {
        /**
         * Detail paket LKE (final/revisi) untuk OPD, termasuk histori revisi.
         *
         * Output view:
         * - `items`: record terbaru per domain (termasuk revisi terbaru jika ada)
         * - `beforeRevisiItems`: baseline sebelum revisi (status != 'revisi')
         * - `canReviseDomainIds`: domain yang sedang diminta revisi (status ticket = requested)
         * - `isLockedBps`: jika TRUE, OPD tidak boleh submit revisi apa pun (bahkan jika ada request pending)
         *
         * Catatan:
         * - Active round per domain ditentukan dari request revisi tertinggi yang berstatus requested.
         */
        $userId = (int) (Auth::id() ?? 0);
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

        $isLockedBps = (bool) ($rawItems->first()?->is_locked_bps ?? false);

        $items = $rawItems
            ->unique('domain_id')
            ->keyBy('domain_id');

        $beforeRevisiItems = $rawItems
            ->filter(fn ($row) => (string) $row->status !== 'revisi')
            ->unique('domain_id')
            ->keyBy('domain_id');

        $domains = Indikator::with(['kriterias' => function ($q) {
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        $activeRevisiRequests = collect();
        $revisedRequestMap = collect();
        $activeRevisiRoundMap = collect(); // domain_id => round aktif (1/2)
        $revisiCatatanMap = collect(); // domain_id => [1=>...,2=>...]
        if (Schema::hasTable('lke_revisi_requests')) {
            $baseReq = LkeRevisiRequest::query()
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek);

            $requested = (clone $baseReq)
                ->where('status', 'requested')
                ->get();

            // Ambil request aktif per domain: round terbesar yang masih requested
            $activeRevisiRequests = $requested
                ->sortByDesc('round')
                ->groupBy('domain_id')
                ->map(fn ($g) => $g->first())
                ->filter()
                ->keyBy('domain_id');

            $activeRevisiRoundMap = $activeRevisiRequests
                ->mapWithKeys(fn ($r, $domainId) => [(int) $domainId => (int) ($r->round ?? 1)]);

            $revisedRequestMap = (clone $baseReq)
                ->with(['revisedLke.buktiDukung'])
                ->where('status', 'revised')
                ->get()
                ->keyBy('domain_id');

            $revisiCatatanMap = (clone $baseReq)
                ->get()
                ->groupBy('domain_id')
                ->map(function ($g) {
                    return $g->groupBy('round')->map(fn ($gg) => (string) ($gg->sortByDesc('id')->first()->catatan ?? ''));
                });
        }

        $canReviseDomainIds = $isLockedBps
            ? []
            : $activeRevisiRequests->keys()->map(fn ($id) => (int) $id)->all();

        $domainRecordsMap = $rawItems->groupBy('domain_id');

        return view('opd.lke.riwayat.show', compact(
            'tahun',
            'domains',
            'items',
            'beforeRevisiItems',
            'domainRecordsMap',
            'namaKegiatan',
            'nomorRek',
            'activeRevisiRequests',
            'revisedRequestMap',
            'canReviseDomainIds',
            'isLockedBps',
            'activeRevisiRoundMap',
            'revisiCatatanMap'
        ));
    }

    public function storeRevisi(Request $request)
    {
        if (!Schema::hasTable('lke_revisi_requests')) {
            return back()->with('failed', 'Tabel revisi belum tersedia. Jalankan php artisan migrate terlebih dahulu.');
        }

        $userId = (int) (Auth::id() ?? 0);

        $validated = $request->validate([
            'tahun_id' => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan' => ['required', 'string', 'max:250'],
            'nomor_rekomendasi' => ['required', 'string', 'max:255'],
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'penjelasan' => ['required', 'string', 'min:10'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240'],
        ], [
            'penjelasan.min' => 'Penjelasan harus diisi minimal 10 karakter.',
            'files.*.max' => 'Ukuran maksimal setiap file adalah 10MB.',
        ]);

        $domainId = (int) $validated['domain_id'];
        $hasIncomingFiles = $request->hasFile('files');

        $baseWhere = [
            'user_id' => $userId,
            'tahun_id' => (int) $validated['tahun_id'],
            'nama_kegiatan' => (string) $validated['nama_kegiatan'],
            'nomor_rekomendasi' => (string) $validated['nomor_rekomendasi'],
            'domain_id' => $domainId,
        ];

        // Cek paket sudah dikunci BPS
        $anyLke = LembarKerjaEvaluasi::query()
            ->where($baseWhere)
            ->orderByDesc('id')
            ->first();
        if ($anyLke && (bool) $anyLke->is_locked_bps) {
            return back()->with('failed', 'Penilaian ini telah dikunci oleh BPS dan tidak dapat direvisi lagi.');
        }

        // Tentukan round revisi yang sedang aktif (max requested)
        $revisiRequest = LkeRevisiRequest::query()
            ->where('user_id', $userId)
            ->where('tahun_id', (int) $validated['tahun_id'])
            ->where('nama_kegiatan', (string) $validated['nama_kegiatan'])
            ->where('nomor_rekomendasi', (string) $validated['nomor_rekomendasi'])
            ->where('domain_id', $domainId)
            ->where('status', 'requested')
            ->orderByDesc('round')
            ->first();

        if (!$revisiRequest) {
            return back()->with('failed', 'Indikator ini belum diizinkan untuk revisi oleh BPS.');
        }

        $round = (int) ($revisiRequest->round ?? 1);
        if ($round < 1 || $round > 2) {
            return back()->with('failed', 'Round revisi tidak valid.');
        }

        // Baseline: sebelum revisi (round 1) atau hasil revisi pertama (round 2)
        $baselineLke = null;
        if ($round === 2) {
            $rev1 = LkeRevisiRequest::query()
                ->where($baseWhere)
                ->where('round', 1)
                ->where('status', 'revised')
                ->orderByDesc('id')
                ->first();
            if ($rev1 && $rev1->revised_lke_id) {
                $baselineLke = LembarKerjaEvaluasi::find($rev1->revised_lke_id);
            }
        }
        if (!$baselineLke) {
            $baselineLke = LembarKerjaEvaluasi::query()
                ->where($baseWhere)
                ->where('status', '!=', 'revisi')
                ->orderByDesc('id')
                ->first();
        }

        if (!$baselineLke) {
            return back()->with('failed', 'Data baseline tidak ditemukan.');
        }

        // Kriteria tidak boleh diubah saat revisi
        $kriteriaId = (int) ($baselineLke->kriteria_id ?? 0);
        if ($kriteriaId <= 0) {
            return back()->with('failed', 'Kriteria baseline belum dipilih, revisi tidak dapat dilakukan.');
        }
        $kriteria = Kriteria::query()
            ->select(['id', 'domain_id', 'tingkat'])
            ->where('id', $kriteriaId)
            ->where('domain_id', $domainId)
            ->first();
        if (!$kriteria) {
            return back()->with('failed', 'Kriteria baseline tidak valid.');
        }
        $tingkatDipilih = (int) $kriteria->tingkat;

        // Validasi perubahan: minimal penjelasan berubah atau ada file baru
        $penjelasanLama = trim((string) ($baselineLke->penjelasan ?? ''));
        $penjelasanBaru = trim((string) $validated['penjelasan']);
        $penjelasanSama = $penjelasanLama === $penjelasanBaru;
        if ($penjelasanSama && !$hasIncomingFiles) {
            return back()
                ->with('failed', 'Revisi tidak boleh sama dengan data sebelumnya. Ubah penjelasan atau tambahkan file bukti baru.')
                ->withInput();
        }

        // Untuk tingkat 2-5 wajib ada file baru di setiap round revisi
        if ($tingkatDipilih !== 1 && !$hasIncomingFiles) {
            return back()
                ->withErrors([
                    'files' => 'File bukti revisi wajib diupload jika memilih kriteria tingkat 2-5.',
                ])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $request, $userId, $kriteriaId, $tingkatDipilih, $revisiRequest, $round, $baseWhere, $baselineLke) {
            // Selalu create record revisi baru agar histori tidak hilang
            $targetLke = LembarKerjaEvaluasi::create([
                'user_id' => $userId,
                'tahun_id' => (int) $validated['tahun_id'],
                'domain_id' => (int) $validated['domain_id'],
                'kriteria_id' => $kriteriaId,
                'nama_kegiatan' => (string) $validated['nama_kegiatan'],
                'nomor_rekomendasi' => (string) $validated['nomor_rekomendasi'],
                'nilai' => $tingkatDipilih,
                'penjelasan' => (string) $validated['penjelasan'],
                'status' => 'revisi',
                'revisi_round' => $round,
                // Bawa nilai BPS terakhir supaya tidak "hilang" di tampilan BPS
                'penilaian_bps' => $baselineLke?->penilaian_bps,
                'catatan_bps' => $baselineLke?->catatan_bps,
                'is_revisi_bps' => (bool) ($baselineLke?->is_revisi_bps),
                'is_locked_bps' => (bool) ($baselineLke?->is_locked_bps),
            ]);

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

            // Pastikan request yang direvisi ini masih requested untuk round yang sama
            $freshReq = LkeRevisiRequest::query()
                ->where($baseWhere)
                ->where('round', $round)
                ->where('status', 'requested')
                ->lockForUpdate()
                ->first();
            if (!$freshReq) {
                throw new \RuntimeException('Request revisi sudah tidak aktif.');
            }

            $freshReq->status = 'revised';
            $freshReq->revised_lke_id = (int) $targetLke->id;
            $freshReq->revised_at = now();
            $freshReq->save();
        });

        return back()->with('success', 'Revisi indikator berhasil disimpan.');
    }
}
