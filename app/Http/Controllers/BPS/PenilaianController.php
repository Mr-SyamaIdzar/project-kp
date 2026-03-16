<?php

namespace App\Http\Controllers\BPS;

use App\Http\Controllers\Controller;
use App\Models\LembarKerjaEvaluasi;
use App\Models\LkeRevisiRequest;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PenilaianController extends Controller
{
    public function index(Request $request)
    {
        $userId  = (int) $request->get('user_id', 0);
        $exportYear = (int) $request->get('export_year', 0);

        $opds   = User::where('role', 'opd')->orderBy('nama')->get();
        $exportYears = LembarKerjaEvaluasi::query()
            ->whereIn('status', ['final', 'revisi'])
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $rows = LembarKerjaEvaluasi::query()
            ->selectRaw("
                user_id,
                tahun_id,
                nama_kegiatan,
                nomor_rekomendasi,
                MAX(updated_at) as last_update,
                MIN(created_at) as package_created_at,
                COUNT(DISTINCT CASE WHEN status IN ('final', 'revisi') THEN domain_id END) as cnt_final,
                COUNT(DISTINCT CASE WHEN status='draft' THEN domain_id END) as cnt_draft,
                COUNT(DISTINCT domain_id) as cnt_total
            ")
            ->when($userId > 0, fn ($q) => $q->where('user_id', $userId))
            ->when($exportYear > 0, fn ($q) => $q->whereYear('created_at', $exportYear))
            ->groupBy('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
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
            'userMap',
            'tahunMap'
        ));
    }

    public function show(Request $request)
    {
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

        $domains = \App\Models\Domain::with(['kriterias' => function ($q) {
                $q->orderBy('tingkat');
            }])
            ->orderBy('kode')
            ->get();

        $requestedDomainIds = [];
        if (Schema::hasTable('lke_revisi_requests')) {
            $requestedDomainIds = LkeRevisiRequest::query()
                ->where('user_id', $userId)
                ->where('tahun_id', $tahunId)
                ->where('nama_kegiatan', $namaKegiatan)
                ->where('nomor_rekomendasi', $nomorRek)
                ->where('status', 'requested')
                ->pluck('domain_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return view('bps.penilaian.show', compact('user', 'tahun', 'domains', 'items', 'namaKegiatan', 'nomorRek', 'requestedDomainIds'));
    }

    public function updateRevisiTargets(Request $request)
    {
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
                        'bps_user_id' => (int) auth()->id(),
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
        $validated = $request->validate([
            'lke_id' => ['required', 'integer', 'exists:lembar_kerja_evaluasi,id'],
            'penilaian_bps' => ['required', 'integer', 'min:1', 'max:5'],
            'catatan_bps' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', 'string', 'in:simpan,revisi'],
        ]);

        $lke = LembarKerjaEvaluasi::findOrFail($validated['lke_id']);

        $isRevisi = $validated['action'] === 'revisi';

        DB::transaction(function () use ($validated, $lke, $isRevisi) {
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
                    LkeRevisiRequest::updateOrCreate(
                        $baseWhere + ['status' => 'requested'],
                        [
                            'bps_user_id' => (int) auth()->id(),
                            'revised_lke_id' => null,
                            'revised_at' => null,
                        ]
                    );
                }
            } else {
                // If it was marked as revisi but now BPS just saves normally (e.g., rescinding revisi)
                if (Schema::hasTable('lke_revisi_requests')) {
                    LkeRevisiRequest::where($baseWhere)
                        ->where('status', 'requested')
                        ->delete();
                }
            }
        });

        $msg = $isRevisi ? 'Penilaian disimpan dan diminta revisi ke OPD.' : 'Penilaian berhasil disimpan.';
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $msg,
                'is_revisi' => $isRevisi
            ]);
        }

        return back()->with('success', $msg);
    }
}
