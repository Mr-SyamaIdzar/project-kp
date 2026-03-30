<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\OPD\DashboardController as OpdDashboard;
use App\Http\Controllers\BPS\DashboardController as BpsDashboard;

use App\Http\Controllers\Admin\LembarKerjaEvaluasiController as AdminLke;
use App\Http\Controllers\Admin\MasterMenuController;

use App\Http\Controllers\BPS\DashboardController as BpsDashboardController;
use App\Http\Controllers\BPS\PenilaianController as BpsPenilaianController;

Route::get('/', function () {
    return redirect()->route('login.form');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth','role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/pie-stats', [AdminDashboard::class, 'pieStats'])->name('admin.dashboard.pie-stats');
    Route::get('/dashboard/stats', [AdminDashboard::class, 'stats'])->name('admin.dashboard.stats');
    Route::get('/profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::post('/profile/update-profile', [\App\Http\Controllers\Admin\ProfileController::class, 'updateProfile'])->name('admin.profile.update-profile');
    Route::post('/profile/update-password', [\App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('admin.profile.update-password');

    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('tahuns', \App\Http\Controllers\Admin\TahunController::class)->names('tahun');
    
    Route::resource('domains', \App\Http\Controllers\Admin\DomainController::class);
    Route::resource('kriterias', \App\Http\Controllers\Admin\KriteriaController::class);

    Route::get('/lke', [AdminLke::class, 'index'])->name('lke.index');
    Route::get('/lke/export', [AdminLke::class, 'exportExcel'])->name('lke.export');
    Route::get('/lke/show', [AdminLke::class, 'show'])->name('lke.show');
    Route::delete('/lke/delete', [AdminLke::class, 'destroy'])->name('lke.destroy');

    Route::get('/master-menu', [MasterMenuController::class, 'index'])->name('master-menu.index');
    Route::put('/master-menu', [MasterMenuController::class, 'update'])->name('master-menu.update');
    Route::put('/master-menu/informasi', [MasterMenuController::class, 'updateInformasi'])->name('master-menu.updateInformasi');

});

Route::middleware(['auth','role:opd'])->prefix('opd')->name('opd.')->group(function () {
    Route::get('/dashboard', [OpdDashboard::class, 'index'])->name('dashboard');

    Route::get('lke/create', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'create'])->name('lke.create');

    // AJAX endpoints — wrapped in ReadOnlySession to prevent MySQL session row lock
    // which would serialize all parallel requests even when using Promise.all()
    Route::middleware(\App\Http\Middleware\ReadOnlySession::class)->group(function () {
        Route::post('lke/autosave', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'autosave'])->name('lke.autosave');
        Route::post('lke/upload', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'uploadBukti'])->name('lke.upload');
        Route::post('lke/finalize', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'finalize'])->name('lke.finalize');
        Route::get('lke/files/{lke}', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'files'])->name('lke.files');
        Route::post('lke/finalize-all', [\App\Http\Controllers\OPD\LembarKerjaEvaluasiController::class, 'finalizeAll'])->name('lke.finalizeAll');
    });
    Route::get('lke/riwayat', [\App\Http\Controllers\OPD\RiwayatLkeController::class, 'index'])->name('lke.riwayat.index');
    Route::get('lke/riwayat/show', [\App\Http\Controllers\OPD\RiwayatLkeController::class, 'show'])->name('lke.riwayat.show');
    Route::post('lke/riwayat/revisi', [\App\Http\Controllers\OPD\RiwayatLkeController::class, 'storeRevisi'])->name('lke.riwayat.revisi.store');

    // Profile routes
    Route::get('/profile', [\App\Http\Controllers\OPD\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update-profile', [\App\Http\Controllers\OPD\ProfileController::class, 'updateProfile'])->name('profile.update-profile');
    Route::post('/profile/update-password', [\App\Http\Controllers\OPD\ProfileController::class, 'updatePassword'])->name('profile.update-password');
});


Route::middleware(['auth', 'role:bps'])->prefix('bps')->name('bps.')->group(function () {
    Route::get('/dashboard', [BpsDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/pie-stats', [BpsDashboardController::class, 'pieStats'])->name('dashboard.pie-stats');
    Route::get('/dashboard/stats', [BpsDashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/profile', [\App\Http\Controllers\BPS\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update-profile', [\App\Http\Controllers\BPS\ProfileController::class, 'updateProfile'])->name('profile.update-profile');
    Route::post('/profile/update-password', [\App\Http\Controllers\BPS\ProfileController::class, 'updatePassword'])->name('profile.update-password');

    // halaman list LKE untuk dinilai
    Route::get('/penilaian', [BpsPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/export', [AdminLke::class, 'exportExcel'])->name('penilaian.export');
    Route::get('/penilaian/show', [BpsPenilaianController::class, 'show'])->name('penilaian.show');
    Route::post('/penilaian/revisi-targets', [BpsPenilaianController::class, 'updateRevisiTargets'])->name('penilaian.revisi-targets');
    Route::post('/penilaian/evaluasi', [BpsPenilaianController::class, 'evaluasiLke'])->name('penilaian.evaluasi');
    Route::post('/penilaian/finalize', [BpsPenilaianController::class, 'finalize'])->name('penilaian.finalize');
});
