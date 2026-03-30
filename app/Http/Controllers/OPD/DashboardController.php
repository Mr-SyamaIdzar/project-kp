<?php

namespace App\Http\Controllers\OPD;

use App\Http\Controllers\Controller;
use App\Models\BuktiDukung;
use App\Models\Indikator;
use App\Models\LembarKerjaEvaluasi;
use App\Models\RoleInformasi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $years = collect()
            ->merge(
                LembarKerjaEvaluasi::query()
                    ->where('user_id', $userId)
                    ->selectRaw('DISTINCT YEAR(created_at) as y')
                    ->pluck('y')
            )
            ->merge(
                BuktiDukung::query()
                    ->whereHas('lembarKerja', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })
                    ->selectRaw('DISTINCT YEAR(created_at) as y')
                    ->pluck('y')
            )
            ->merge(
                Indikator::query()
                    ->selectRaw('DISTINCT YEAR(created_at) as y')
                    ->pluck('y')
            )
            ->map(fn ($y) => (int) $y)
            ->filter(fn ($y) => $y > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $selectedYear = (int) $request->get('year', 0);
        if ($selectedYear <= 0) {
            $selectedYear = (int) ($years->first() ?? now()->year);
        }

        $totalDraft = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->where('status', 'draft')
            ->whereYear('created_at', $selectedYear)
            ->count();

        $totalFinal = LembarKerjaEvaluasi::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['final', 'revisi'])
            ->whereYear('created_at', $selectedYear)
            ->count();

        $totalIndikator = Indikator::query()
            ->whereYear('created_at', $selectedYear)
            ->count();

        $totalFiles = BuktiDukung::query()
            ->whereYear('created_at', $selectedYear)
            ->whereHas('lembarKerja', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->count();

        $informasi = RoleInformasi::forRole('opd');

        return view('opd.dashboard', compact(
            'totalDraft',
            'totalFinal',
            'totalIndikator',
            'totalFiles',
            'years',
            'selectedYear',
            'informasi'
        ));
    }
}
