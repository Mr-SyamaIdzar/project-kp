<?php

namespace App\Http\Controllers\BPS;

use App\Http\Controllers\Controller;
use App\Models\LembarKerjaEvaluasi;
use App\Models\RoleInformasi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard BPS: ringkasan paket LKE yang masuk penilaian.
 *
 * Catatan desain:
 * - Filter OPD/Tahun di dashboard hanya untuk navigasi + card stat (endpoint stats()).
 * - Pie chart sengaja agregat semua OPD (tidak menerima filter OPD), hanya by Tahun Submit (created_at).
 * - Tahun submit diambil dari YEAR(created_at) record LKE status='final'.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $totalDraft = LembarKerjaEvaluasi::query()
            ->where('status', 'draft')
            ->select('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->distinct()
            ->get()
            ->count();

        $lkes = LembarKerjaEvaluasi::query()
            ->where('status', 'final')
            ->select('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->selectRaw('COUNT(penilaian_bps) as cnt_scored')
            ->groupBy('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->get();

        $totalMasterIndicators = \App\Models\Indikator::count();
        $totalFinalAll = $lkes->count();
        $masukPenilaian = $lkes->where('cnt_scored', '>=', $totalMasterIndicators)->count();
        $totalFinal = $totalFinalAll - $masukPenilaian;

        $informasi = RoleInformasi::forRole('bps');

        $opds = User::query()
            ->where('role', 'opd')
            ->orderByRaw('COALESCE(nama, username) asc')
            ->get(['id', 'nama', 'username']);

        $years = LembarKerjaEvaluasi::query()
            ->where('status', 'final')
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $totalOpd = $opds->count();

        return view('bps.dashboard', compact(
            'totalOpd',
            'totalDraft',
            'totalFinal',
            'masukPenilaian',
            'informasi',
            'opds',
            'years'
        ));
    }

    public function stats(Request $request): JsonResponse
    {
        // Endpoint JSON untuk update live kartu statistik dashboard (tanpa reload).
        $userId = (int) $request->get('user_id', 0);
        $year = (int) $request->get('year', 0);

        $totalOpd = $userId > 0 ? 1 : (int) User::where('role', 'opd')->count();

        $base = LembarKerjaEvaluasi::query();
        $baseDraft = LembarKerjaEvaluasi::query()->where('status', 'draft');
        if ($userId > 0) $baseDraft->where('user_id', $userId);
        if ($year > 0) $baseDraft->whereYear('created_at', $year);

        $totalDraft = $baseDraft->select('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->distinct()
            ->get()
            ->count();

        $lkes = LembarKerjaEvaluasi::query()
            ->where('status', 'final');
            
        if ($userId > 0) $lkes->where('user_id', $userId);
        if ($year > 0) $lkes->whereYear('created_at', $year);

        $lkes = $lkes->select('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->selectRaw('COUNT(penilaian_bps) as cnt_scored')
            ->groupBy('user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi')
            ->get();

        $totalMasterIndicators = \App\Models\Indikator::count();
        $totalFinalAll = $lkes->count();
        $masukPenilaian = $lkes->where('cnt_scored', '>=', $totalMasterIndicators)->count();
        $totalFinal = $totalFinalAll - $masukPenilaian;

        return response()->json([
            'ok' => true,
            'filters' => [
                'user_id' => $userId,
                'year' => $year,
            ],
            'cards' => [
                'total_opd' => $totalOpd,
                'total_draft' => $totalDraft,
                'total_final' => $totalFinal,
                'masuk_penilaian' => $masukPenilaian,
            ],
        ]);
    }

    public function pieStats(Request $request): JsonResponse
    {
        $year = (int) $request->get('year', 0);
        $years = LembarKerjaEvaluasi::query()
            ->where('status', 'final')
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        if ($year <= 0) {
            $year = (int) ($years->first() ?? now()->year);
        }

        $opdIds = User::query()->where('role', 'opd')->pluck('id')->map(fn ($v) => (int) $v)->values();
        $totalOpd = (int) $opdIds->count();

        // --- PIE 1: Status Submit (Sudah / Draft / Belum) ---
        $opdWithFinal = LembarKerjaEvaluasi::query()
            ->whereIn('user_id', $opdIds)
            ->where('status', 'final')
            ->whereYear('created_at', $year)
            ->distinct('user_id')
            ->pluck('user_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->unique();

        $opdWithDraft = LembarKerjaEvaluasi::query()
            ->whereIn('user_id', $opdIds)
            ->where('status', 'draft')
            ->whereYear('created_at', $year)
            ->whereNotIn('user_id', $opdWithFinal)
            ->distinct('user_id')
            ->pluck('user_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->unique();

        $cntSudah = $opdWithFinal->count();
        $cntDraft  = $opdWithDraft->count();
        $cntBelum  = max($totalOpd - $cntSudah - $cntDraft, 0);

        $userId = (int) $request->get('user_id', 0);
        $filteredOpdIds = ($userId > 0 && $opdIds->contains($userId)) ? collect([$userId]) : $opdIds;
        $totalOpdFiltered = $filteredOpdIds->count();
        $totalMasterIndicators = \App\Models\Indikator::count();
        $totalExpectedIndicators = $totalOpdFiltered * $totalMasterIndicators;

        // --- PIE 2 & 3: Dihitung Per-Indikator untuk OPD yang difilter ---
        $lkeRows = LembarKerjaEvaluasi::query()
            ->whereIn('lembar_kerja_evaluasi.user_id', $filteredOpdIds)
            ->whereYear('lembar_kerja_evaluasi.created_at', $year)
            ->leftJoin('bukti_dukung as bd', 'bd.lembar_kerja_id', '=', 'lembar_kerja_evaluasi.id')
            ->selectRaw("
                lembar_kerja_evaluasi.user_id,
                lembar_kerja_evaluasi.domain_id,
                lembar_kerja_evaluasi.nilai,
                lembar_kerja_evaluasi.penjelasan,
                COUNT(bd.id) as file_cnt
            ")
            ->groupBy(
                'lembar_kerja_evaluasi.id', 
                'lembar_kerja_evaluasi.user_id', 
                'lembar_kerja_evaluasi.domain_id', 
                'lembar_kerja_evaluasi.nilai', 
                'lembar_kerja_evaluasi.penjelasan'
            )
            ->get();

        $revisiMap = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('lke_revisi_requests')) {
            $revisiRows = \App\Models\LkeRevisiRequest::query()
                ->whereIn('user_id', $filteredOpdIds)
                ->where('status', 'requested')
                ->whereYear('created_at', $year)
                ->get(['user_id', 'domain_id']);
            foreach ($revisiRows as $r) {
                $revisiMap[$r->user_id . '_' . $r->domain_id] = true;
            }
        }

        $cntIndikatorRevisi = 0;
        $cntIndikatorLengkapPengisian = 0;
        $cntIndikatorLengkapBukti = 0;

        foreach ($lkeRows as $r) {
            $key = $r->user_id . '_' . $r->domain_id;
            $isRevisi = isset($revisiMap[$key]);

            $nilai   = (int) ($r->nilai ?? 0);
            $fileCnt = (int) ($r->file_cnt ?? 0);
            $hasP    = strlen(trim((string) ($r->penjelasan ?? ''))) > 0;
            
            $isLengkapBukti = false;
            if ($nilai === 1 && $hasP) {
                $isLengkapBukti = true;
            } elseif ($nilai >= 2 && $hasP && $fileCnt > 0) {
                $isLengkapBukti = true;
            }

            if ($isLengkapBukti) {
                $cntIndikatorLengkapBukti++;
            }

            if ($isRevisi) {
                $cntIndikatorRevisi++;
            } elseif ($isLengkapBukti) {
                $cntIndikatorLengkapPengisian++;
            }
        }

        $cntIndikatorKosongPengisian = max($totalExpectedIndicators - $cntIndikatorLengkapPengisian - $cntIndikatorRevisi, 0);
        $cntIndikatorKosongBukti = max($totalExpectedIndicators - $cntIndikatorLengkapBukti, 0);

        return response()->json([
            'ok'    => true,
            'year'  => $year,
            'years' => $years,
            'charts' => [
                'submit' => [
                    'labels' => ['Sudah', 'Draft', 'Belum'],
                    'data'   => [$cntSudah, $cntDraft, $cntBelum],
                ],
                'penjelasan' => [
                    'labels' => ['Lengkap', 'Revisi', 'Kosong'],
                    'data'   => [$cntIndikatorLengkapPengisian, $cntIndikatorRevisi, $cntIndikatorKosongPengisian],
                ],
                'bukti' => [
                    'labels' => ['Lengkap', 'Kosong'],
                    'data'   => [$cntIndikatorLengkapBukti, $cntIndikatorKosongBukti],
                ],
            ],
        ]);
    }
}
