<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\LembarKerjaEvaluasi;
use App\Models\RoleInformasi;
use App\Models\Tahun;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers  = User::count();
        $totalTahun  = Tahun::count();
        $totalDomain = Domain::count();
        $totalLke    = LembarKerjaEvaluasi::count();
        $informasi   = RoleInformasi::forRole('admin');

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalTahun',
            'totalDomain',
            'totalLke',
            'informasi'
        ));
    }
}
