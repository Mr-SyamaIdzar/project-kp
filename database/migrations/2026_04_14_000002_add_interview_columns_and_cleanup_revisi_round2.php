<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom interview ke lembar_kerja_evaluasi
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->text('catatan_interview')->nullable()->after('catatan_bps')->comment('Catatan hasil interview dari BPS');
            $table->tinyInteger('nilai_interview')->unsigned()->nullable()->after('catatan_interview')->comment('Nilai hasil interview dari BPS (1-5)');
        });

        // 2. Bersihkan data revisi_round=2 dari lke_revisi_requests
        if (Schema::hasTable('lke_revisi_requests')) {
            DB::table('lke_revisi_requests')->where('round', 2)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->dropColumn(['catatan_interview', 'nilai_interview']);
        });
    }
};
