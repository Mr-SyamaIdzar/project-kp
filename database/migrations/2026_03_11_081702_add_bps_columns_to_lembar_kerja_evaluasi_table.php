<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->tinyInteger('penilaian_bps')->nullable()->comment('Nilai dari BPS (1-5)');
            $table->text('catatan_bps')->nullable()->comment('Catatan dari BPS');
            $table->boolean('is_revisi_bps')->default(false)->comment('Apakah BPS meminta revisi untuk indikator ini');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->dropColumn(['penilaian_bps', 'catatan_bps', 'is_revisi_bps']);
        });
    }
};
