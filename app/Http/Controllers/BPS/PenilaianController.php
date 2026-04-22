<?php

namespace App\Http\Controllers\BPS;

use App\Http\Controllers\Controller;
use App\Models\GlobalSetting;
use App\Models\LembarKerjaEvaluasi;
use App\Models\LkeRevisiRequest;
use App\Models\Indikator;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PenilaianController extends Controller
{
    /**
     * List paket LKE untuk dinilai oleh BPS.
     *
     * Ringkasan:
     * - `user_id` / `export_year` adalah filter list.
     * - `sort_opd` mengurutkan berdasarkan nama OPD (A→Z atau Z→A).
     * - Status badge (Done/Onprogress/Belum dinilai) dihitung dari jumlah domain yang sudah punya nilai BPS.
     */
    public function index(Request $request)
    {
        $userId      = (int) $request->get('user_id', 0);
        $exportYear  = (int) $request->get('export_year', 0);
        $exportStatus = (string) $request->get('export_status', 'all');
        if (!in_array($exportStatus, ['all', 'done'], true)) {
            $exportStatus = 'all';
        }
        $sortOpd = (string) $request->get('sort_opd', 'asc');
        if (!in_array($sortOpd, ['asc', 'desc'], true)) {
            $sortOpd = 'asc';
        }

        $opds         = User::where('role', 'opd')->orderBy('nama')->get();
        $totalDomains = (int) Indikator::count();
        $exportYears  = LembarKerjaEvaluasi::query()
            ->whereIn('status', ['final', 'revisi'])
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $rows = LembarKerjaEvaluasi::query()
            ->join('users as u', 'u.id', '=', 'lembar_kerja_evaluasi.user_id')
            ->selectRaw("
                lembar_kerja_evaluasi.user_id,
                lembar_kerja_evaluasi.tahun_id,
                lembar_kerja_evaluasi.nama_kegiatan,
                lembar_kerja_evaluasi.nomor_rekomendasi,
                COALESCE(NULLIF(TRIM(u.nama), ''), u.username) as user_name,
                MAX(lembar_kerja_evaluasi.updated_at) as last_update,
                MIN(lembar_kerja_evaluasi.created_at) as package_created_at,
                COUNT(DISTINCT CASE WHEN lembar_kerja_evaluasi.status IN ('final', 'revisi') THEN lembar_kerja_evaluasi.domain_id END) as cnt_final,
                COUNT(DISTINCT CASE WHEN lembar_kerja_evaluasi.status='draft' THEN lembar_kerja_evaluasi.domain_id END) as cnt_draft,
                COUNT(DISTINCT lembar_kerja_evaluasi.domain_id) as cnt_total,
                COUNT(DISTINCT CASE WHEN lembar_kerja_evaluasi.penilaian_bps IS NOT NULL THEN lembar_kerja_evaluasi.domain_id END) as cnt_scored,
                MAX(CASE WHEN lembar_kerja_evaluasi.is_locked_bps = 1 THEN 1 ELSE 0 END) as is_locked_bps
            ")
            ->when($userId > 0, fn ($q) => $q->where('lembar_kerja_evaluasi.user_id', $userId))
            ->when($exportYear > 0, fn ($q) => $q->whereYear('lembar_kerja_evaluasi.created_at', $exportYear))
            ->groupBy('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi', 'user_name')
            ->when($exportStatus === 'done' && $totalDomains > 0, fn ($q) =>
                $q->havingRaw('COUNT(DISTINCT CASE WHEN lembar_kerja_evaluasi.penilaian_bps IS NOT NULL THEN lembar_kerja_evaluasi.domain_id END) >= ?', [$totalDomains])
            )
            ->orderBy('user_name', $sortOpd)
            ->orderByDesc('last_update')
            ->paginate(10)
            ->withQueryString();

        $userMap = $rows->pluck('user_id')->unique()->filter()->isNotEmpty()
            ? User::whereIn('id', $rows->pluck('user_id')->unique())->get()->keyBy('id')
            : collect();

        $tahunMap = $rows->pluck('tahun_id')->unique()->filter()->isNotEmpty()
            ? Tahun::whereIn('id', $rows->pluck('tahun_id')->unique())->get()->keyBy('id')
            : collect();

        return view('bps.penilaian.index', compact(
            'rows',
            'opds',
            'userId',
            'exportYear',
            'exportYears',
            'exportStatus',
            'userMap',
            'tahunMap',
            'sortOpd',
            'totalDomains'
        ));
    }

    public function show(Request $request)
    {
        /**
         * Detail paket LKE untuk penilaian BPS.
         *
         * Catatan:
         * - Histori revisi ditampilkan sebagai panel "Sebelum / Revisi 1 / Revisi 2".
         * - `bpsLastMap` menjaga nilai BPS terakhir per domain agar tidak "hilang" saat OPD membuat record revisi baru.
         * - `isLocked` = paket sudah difinalisasi BPS → semua aksi simpan/revisi harus nonaktif.
         */
        $userId = (int) $request->get('user_id', 0);
        $tahunId = (int) $request->get('tahun_id', 0);
        $namaKegiatan = (string) $request->get('nama_kegiatan', '');
        $nomorRek = (string) $request->get('nomor_rekomendasi', '');

        abort_if($userId <= 0 || $tahunId <= 0 || $namaKegiatan === '' || $nomorRek === '', 404);

        $user  = User::findOrFail($userId);
        $tahun = Tahun::findOrFail($tahunId);

        $rawItems = LembarKerjaEvaluasi::query()
            ->with([
                'domain.kriterias',
                'kriteria',
                'buktiDukung',
            ])
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRek)
            ->orderByDesc('id')
            ->get();

        $items = $rawItems
            ->unique('domain_id')
            ->keyBy('domain_id');

        // Nilai BPS terakhir per domain (agar tidak "hilang" saat OPD membuat record revisi baru)
        $bpsLastMap = $rawItems
            ->filter(fn ($row) => !is_null($row->penilaian_bps))
            ->groupBy('domain_id')
            ->map(fn ($g) => $g->sortByDesc('updated_at')->first());

        $beforeRevisiItems = $rawItems
            ->filter(fn ($row) => (string) $row->status !== 'revisi')
            ->unique('domain_id')
            ->keyBy('domain_id');

        $domains = Indikator::with(['kriterias' => function ($q) {
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        $requestedDomainIds = [];
        $revisedRequestMap = collect();
        $revisiStatus = collect(); // [domain_id][round] => 'requested'|'revised'|null
        $revisiCatatan = collect(); // [domain_id][round] => catatan
        if (Schema::hasTable('lke_revisi_requests')) {
            $baseReq = LkeRevisiRequest::query()
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek);

            $requestedDomainIds = (clone $baseReq)
                ->where('status', 'requested')
                ->pluck('domain_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $revisedRequestMap = (clone $baseReq)
                ->with(['revisedLke.buktiDukung'])
                ->where('status', 'revised')
                ->get()
                ->keyBy('domain_id');

            $allReq = (clone $baseReq)->get();
            $revisiStatus = $allReq
                ->groupBy('domain_id')
                ->map(function ($g) {
                    return $g->groupBy('round')->map(fn ($gg) => (string) ($gg->sortByDesc('id')->first()->status ?? ''));
                });
            $revisiCatatan = $allReq
                ->groupBy('domain_id')
                ->map(function ($g) {
                    return $g->groupBy('round')->map(fn ($gg) => (string) ($gg->sortByDesc('id')->first()->catatan ?? ''));
                });
        }

        $allScored = $domains->every(function($d) use ($items) {
           return isset($items[$d->id]) && !is_null($items[$d->id]->penilaian_bps);
        });

        $isLocked = $rawItems->first()?->is_locked_bps ?? false;

        $domainRecordsMap = $rawItems->groupBy('domain_id');

        // Feature toggles
        $revisiDokumenEnabled  = GlobalSetting::isEnabled('revisi_dokumen_enabled');
        $interviewInputEnabled = GlobalSetting::isEnabled('interview_input_enabled');

        return view('bps.penilaian.show', compact(
            'user', 'tahun', 'domains', 'items', 'beforeRevisiItems', 'domainRecordsMap', 'namaKegiatan',
            'nomorRek', 'requestedDomainIds', 'revisedRequestMap', 'allScored', 'isLocked',
            'revisiStatus', 'revisiCatatan', 'bpsLastMap',
            'revisiDokumenEnabled', 'interviewInputEnabled',
        ));
    }

    public function finalize(Request $request)
    {
        /**
         * Finalisasi penilaian BPS untuk satu paket.
         *
         * Efek: set `is_locked_bps=1` untuk seluruh record LKE dalam paket.
         * Dampak ke OPD: semua endpoint perubahan (autosave/upload/finalize/revisi) ditolak.
         *
         * Prasyarat: semua domain harus sudah punya `penilaian_bps` (dinilai).
         */
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tahun_id' => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan' => ['required', 'string'],
            'nomor_rekomendasi' => ['required', 'string'],
        ]);

        $userId = (int) $validated['user_id'];
        $tahunId = (int) $validated['tahun_id'];
        $namaKegiatan = (string) $validated['nama_kegiatan'];
        $nomorRek = (string) $validated['nomor_rekomendasi'];

        $domains = Indikator::all();
        $items = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRek)
            ->get()
            ->keyBy('domain_id');

        $allScored = $domains->every(function($d) use ($items) {
           return isset($items[$d->id]) && !is_null($items[$d->id]->penilaian_bps);
        });

        if (!$allScored) {
            return back()->with('failed', 'Semua indikator harus dinilai sebelum finalisasi.');
        }

        LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->where('tahun_id', $tahunId)
            ->where('nama_kegiatan', $namaKegiatan)
            ->where('nomor_rekomendasi', $nomorRek)
            ->update(['is_locked_bps' => true]);

        return back()->with('success', 'Penilaian telah berhasil difinalisasi dan dikunci.');
    }

    public function updateRevisiTargets(Request $request)
    {
        /**
         * Update daftar domain yang ditandai "Perlu Revisi" oleh BPS (bulk).
         *
         * Catatan: ini bukan mekanisme ronde revisi 1/2, melainkan penandaan target domain
         * agar OPD tahu indikator mana yang harus direvisi pada paket tersebut.
         */
        if (!Schema::hasTable('lke_revisi_requests')) {
            return back()->with('failed', 'Tabel revisi belum tersedia. Jalankan php artisan migrate terlebih dahulu.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'tahun_id' => ['required', 'integer', 'exists:tahun,id'],
            'nama_kegiatan' => ['required', 'string', 'max:250'],
            'nomor_rekomendasi' => ['required', 'string', 'max:255'],
            'domain_ids' => ['nullable', 'array'],
            'domain_ids.*' => ['integer', 'exists:domains,id'],
        ]);

        $opd = User::where('id', (int) $validated['user_id'])
            ->where('role', 'opd')
            ->firstOrFail();

        $domainIds = collect($validated['domain_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $baseWhere = [
            'user_id' => $opd->id,
            'tahun_id' => (int) $validated['tahun_id'],
            'nama_kegiatan' => (string) $validated['nama_kegiatan'],
            'nomor_rekomendasi' => (string) $validated['nomor_rekomendasi'],
        ];

        DB::transaction(function () use ($baseWhere, $domainIds) {
            LkeRevisiRequest::query()
                ->where($baseWhere)
                ->where('status', 'requested')
                ->whereNotIn('domain_id', $domainIds->isEmpty() ? [0] : $domainIds->all())
                ->delete();

            foreach ($domainIds as $domainId) {
                LkeRevisiRequest::updateOrCreate(
                    $baseWhere + [
                        'domain_id' => $domainId,
                        'status' => 'requested',
                    ],
                    [
                        'bps_user_id' => (int) (Auth::id() ?? 0),
                        'revised_lke_id' => null,
                        'revised_at' => null,
                    ]
                );
            }
        });

        return back()->with('success', 'Indikator revisi untuk OPD berhasil diperbarui.');
    }

    public function evaluasiLke(Request $request)
    {
        /**
         * Simpan penilaian per indikator (domain) oleh BPS.
         *
         * Input:
         * - `penilaian_bps` (1-5) wajib
         * - `catatan_bps` opsional (dipakai sebagai catatan evaluasi atau alasan revisi)
         * - `action`: 'simpan' atau 'revisi'
         * - `round`: 1/2 hanya saat action='revisi'
         *
         * Aturan revisi:
         * - Maksimal 2 ronde.
         * - Ronde 2 hanya bisa diminta setelah ronde 1 berstatus `revised`.
         * - Saat action='revisi', `catatan_bps` wajib (jadi alasan revisi).
         */
        $validated = $request->validate([
            'lke_id' => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
            'penilaian_bps' => ['required', 'integer', 'min:1', 'max:5'],
            'catatan_bps' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', 'string', 'in:simpan,revisi'],
            'round' => ['nullable', 'integer', 'min:1', 'max:2'],
        ]);

        $lke = LembarKerjaEvaluasi::findOrFail($validated['lke_id']);

        $isRevisi = $validated['action'] === 'revisi';
        $roundFromClient = (int) ($validated['round'] ?? 0);

        DB::transaction(function () use ($validated, $lke, $isRevisi, $roundFromClient) {
            $lke->update([
                'penilaian_bps' => $validated['penilaian_bps'],
                'catatan_bps' => $validated['catatan_bps'],
                'is_revisi_bps' => $isRevisi,
            ]);

            $baseWhere = [
                'user_id' => $lke->user_id,
                'tahun_id' => $lke->tahun_id,
                'nama_kegiatan' => $lke->nama_kegiatan,
                'nomor_rekomendasi' => $lke->nomor_rekomendasi,
                'domain_id' => $lke->domain_id,
            ];

            if ($isRevisi) {
                if (Schema::hasTable('lke_revisi_requests')) {
                    // Cek toggle revisi dokumen
                    if (!GlobalSetting::isEnabled('revisi_dokumen_enabled')) {
                        throw new \RuntimeException('Fitur revisi dokumen sedang dinonaktifkan.');
                    }

                    $alasan = trim((string) ($validated['catatan_bps'] ?? ''));
                    if ($alasan === '') {
                        throw new \RuntimeException('Alasan revisi wajib diisi.');
                    }

                    // Hanya 1 ronde revisi (max 1x)
                    $existing = LkeRevisiRequest::query()
                        ->where($baseWhere)
                        ->lockForUpdate()
                        ->get();

                    $hasReq1  = $existing->where('round', 1)->where('status', 'requested')->isNotEmpty();
                    $hasRev1  = $existing->where('round', 1)->where('status', 'revised')->isNotEmpty();

                    if ($hasRev1) {
                        throw new \RuntimeException('Revisi dokumen sudah mencapai batas maksimal (1 kali). OPD telah menyelesaikan revisi.');
                    }

                    // Hanya round 1
                    LkeRevisiRequest::updateOrCreate(
                        $baseWhere + ['round' => 1, 'status' => 'requested'],
                        [
                            'bps_user_id'    => (int) (Auth::id() ?? 0),
                            'catatan'        => $alasan,
                            'revised_lke_id' => null,
                            'revised_at'     => null,
                        ]
                    );
                }
            } else {
                // If it was marked as revisi but now BPS just saves normally (e.g., rescinding revisi)
                if (Schema::hasTable('lke_revisi_requests')) {
                    // BPS boleh membatalkan request hanya jika round tersebut belum direvisi.
                    LkeRevisiRequest::where($baseWhere)
                        ->where('status', 'requested')
                        ->delete();
                }
            }
        });

        $msg = $isRevisi ? 'Penilaian disimpan dan permintaan revisi dokumen dikirim ke OPD.' : 'Penilaian berhasil disimpan.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => $msg,
                'is_revisi'=> $isRevisi
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Simpan data hasil interview per indikator oleh BPS.
     *
     * Hanya aktif jika toggle `interview_input_enabled` = true.
     * Menyimpan catatan_interview + nilai_interview ke record LKE indikator.
     */
    public function saveInterview(Request $request)
    {
        if (!GlobalSetting::isEnabled('interview_input_enabled')) {
            return response()->json(['ok' => false, 'message' => 'Fitur input hasil interview tidak aktif.'], 403);
        }

        $validated = $request->validate([
            'lke_id'            => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
            'catatan_interview' => ['nullable', 'string', 'max:2000'],
            'nilai_interview'   => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $lke = LembarKerjaEvaluasi::findOrFail($validated['lke_id']);

        if ($lke->is_locked_bps) {
            return response()->json(['ok' => false, 'message' => 'Paket LKE sudah dikunci, tidak bisa diubah.'], 403);
        }

        $lke->update([
            'catatan_interview' => $validated['catatan_interview'] ?? null,
            'nilai_interview'   => isset($validated['nilai_interview']) ? (int) $validated['nilai_interview'] : null,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Data interview tersimpan.']);
        }

        return back()->with('success', 'Data interview tersimpan.');
    }
}
