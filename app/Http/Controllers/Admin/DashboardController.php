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
         * Definisi kategori:
         * - Sudah/Belum submit: per OPD, ada minimal 1 baris LKE status=final pada tahun created_at tersebut.
         * - Penjelasan "Lengkap": semua indikator yang disubmit memiliki penjelasan terisi (trim != '').
         * - Bukti dukung "Lengkap": untuk setiap indikator yang disubmit dengan nilai >= 2 harus ada minimal 1 file.
         *
         * Penting: pie chart tidak menerima filter OPD (agregat semua OPD).
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

        $rows = LembarKerjaEvaluasi::query()
            ->whereIn('lembar_kerja_evaluasi.user_id', $opdIds)
            ->where('lembar_kerja_evaluasi.status', 'final')
            ->whereYear('lembar_kerja_evaluasi.created_at', $year)
            ->leftJoin('bukti_dukung as bd', 'bd.lembar_kerja_id', '=', 'lembar_kerja_evaluasi.id')
            ->selectRaw("
                lembar_kerja_evaluasi.id as lke_id,
                lembar_kerja_evaluasi.user_id,
                lembar_kerja_evaluasi.nilai,
                lembar_kerja_evaluasi.penjelasan,
                COUNT(bd.id) as file_cnt
            ")
            ->groupBy('lembar_kerja_evaluasi.id', 'lembar_kerja_evaluasi.user_id', 'lembar_kerja_evaluasi.nilai', 'lembar_kerja_evaluasi.penjelasan')
            ->get();

        $byUser = [];
        foreach ($opdIds as $uid) {
            $byUser[(int) $uid] = [
                'has_submit' => false,
                'penjelasan_ok' => true,
                'bukti_ok' => true,
            ];
        }

        foreach ($rows as $r) {
            $uid = (int) $r->user_id;
            if (!isset($byUser[$uid])) continue;

            $byUser[$uid]['has_submit'] = true;

            $p = trim((string) ($r->penjelasan ?? ''));
            if ($p === '') $byUser[$uid]['penjelasan_ok'] = false;

            $nilai = (int) ($r->nilai ?? 0);
            $fileCnt = (int) ($r->file_cnt ?? 0);
            if ($nilai >= 2 && $fileCnt <= 0) $byUser[$uid]['bukti_ok'] = false;
        }

        $cntSudah = 0;
        $cntPenjelasanLengkap = 0;
        $cntBuktiLengkap = 0;
        foreach ($byUser as $st) {
            if ($st['has_submit']) {
                $cntSudah++;
                if ($st['penjelasan_ok']) $cntPenjelasanLengkap++;
                if ($st['bukti_ok']) $cntBuktiLengkap++;
            }
        }

        $cntBelum = max($totalOpd - $cntSudah, 0);
        $cntPenjelasanSebagian = max($cntSudah - $cntPenjelasanLengkap, 0);
        $cntBuktiSebagian = max($cntSudah - $cntBuktiLengkap, 0);

        return response()->json([
            'ok' => true,
            'year' => $year,
            'years' => $years,
            'charts' => [
                'submit' => [
                    'labels' => ['Sudah', 'Belum'],
                    'data' => [$cntSudah, $cntBelum],
                ],
                'penjelasan' => [
                    'labels' => ['Belum Submit LKE', 'Sebagian Tidak Ada Penjelasan', 'Lengkap'],
                    'data' => [$cntBelum, $cntPenjelasanSebagian, $cntPenjelasanLengkap],
                ],
                'bukti' => [
                    'labels' => ['Belum Submit LKE', 'Upload Sebagian', 'Lengkap'],
                    'data' => [$cntBelum, $cntBuktiSebagian, $cntBuktiLengkap],
                ],
            ],
        ]);
    }
}
