<?php

namespace App\Http\Controllers\BPS;

use App\Http\Controllers\Controller;
use App\Models\LembarKerjaEvaluasi;
use App\Models\RoleInformasi;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalOpd = User::where('role', 'opd')->count();

        $totalDraft = LembarKerjaEvaluasi::where('status', 'draft')->count();
        $totalFinal = LembarKerjaEvaluasi::where('status', 'final')->count();

        $masukPenilaian = $totalFinal;

        $informasi = RoleInformasi::forRole('bps');

        return view('bps.dashboard', compact(
            'totalOpd',
            'totalDraft',
            'totalFinal',
            'masukPenilaian',
            'informasi'
        ));
    }
}
