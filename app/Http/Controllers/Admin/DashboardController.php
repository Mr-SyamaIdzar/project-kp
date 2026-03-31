<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\LembarKerjaEvaluasi;
use App\Models\RoleInformasi;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin dashboard: ringkasan cepat + monitoring agregat.
 *
 * Catatan desain:
 * - Filter OPD/Tahun di dashboard hanya untuk navigasi + card stat (lihat endpoint stats()).
 * - Pie chart sengaja tidak bisa difilter per-OPD (sesuai requirement), hanya by Tahun Submit (created_at).
 * - "Submit" yang dihitung untuk pie chart memakai status='final' (paket sudah dikumpulkan).
 */
class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers  = User::count();
        $totalTahun  = Tahun::count();
        $totalIndikator = Indikator::count();
        $totalLke    = LembarKerjaEvaluasi::count();
        $informasi   = RoleInformasi::forRole('admin');

        // Jumlah domain: hitung unik nama_domain di table domains
        $totalDomains = Indikator::distinct('nama_domain')->count('nama_domain');

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

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalTahun',
            'totalIndikator',
            'totalDomains',
            'totalLke',
            'informasi',
            'opds',
            'years'
        ));
    }

    public function stats(Request $request): JsonResponse
    {
        // Endpoint JSON untuk update live kartu statistik dashboard (tanpa reload).
        // Ini tidak dipakai oleh pie chart (pie chart punya filter tahun sendiri).
        $userId = (int) $request->get('user_id', 0);
        $year = (int) $request->get('year', 0);

        $q = LembarKerjaEvaluasi::query();
        if ($userId > 0) $q->where('user_id', $userId);
        if ($year > 0) $q->whereYear('created_at', $year);

        return response()->json([
            'ok' => true,
            'filters' => [
                'user_id' => $userId,
                'year' => $year,
            ],
            'cards' => [
                // Fokus dashboard admin: monitoring LKE
                'total_lke' => (int) $q->count(),
            ],
        ]);
    }

    public function pieStats(Request $request): JsonResponse
    {
        /**
         * Endpoint JSON untuk pie chart dashboard.
         *
         * Pie 1 – Status Submit LKE:
         *   - Sudah  = OPD yang punya minimal 1 LKE status=final pada tahun tersebut
         *   - Draft  = OPD yang punya LKE status=draft di tahun tersebut tapi belum pernah final
         *   - Belum  = OPD yang tidak punya LKE sama sekali di tahun tersebut
         *
         * Pie 2 – Pengisian Indikator:
         *   - Lengkap = OPD yang sudah final dan tidak ada indikator sedang direvisi BPS
         *   - Revisi  = OPD yang ada indikatornya sedang diminta revisi oleh BPS
         *   - Kosong  = OPD yang belum ada LKE di tahun tersebut
         *
         * Pie 3 – Status Bukti Dukung:
         *   - Lengkap = OPD sudah final dan semua indikator tingkat ≥ 2 punya bukti dukung
         *   - Kosong  = OPD belum submit atau ada indikator yang kurang bukti
         */
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
        // OPD yang punya LKE final tahun ini
        $opdWithFinal = LembarKerjaEvaluasi::query()
            ->whereIn('user_id', $opdIds)
            ->where('status', 'final')
            ->whereYear('created_at', $year)
            ->distinct('user_id')
            ->pluck('user_id')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->unique();

        // OPD yang punya LKE draft di tahun ini tapi tidak punya final
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
        $totalMasterIndicators = Indikator::count();
        $totalExpectedIndicators = $totalOpdFiltered * $totalMasterIndicators;

        // --- PIE 2 & 3: Dihitung Per-Indikator untuk OPD yang difilter ---

        // Ambil semua LKE untuk OPD yang difilter (terlepas dari status paket final/draft)
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

        // Ambil data indikator yang sedang direvisi BPS
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

            // Definisi Indikator Lengkap (Pie 3 & Pie 2):
            // Tingkat 1 perlu penjelasan. Tingkat >= 2 perlu penjelasan + bukti dukung.
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

            // Pie 2 (Pengisian Indikator)
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
