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
            $table->boolean('is_locked_bps')->default(false)->after('is_revisi_bps')->comment('Apakah penilaian sudah dikunci oleh BPS');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->dropColumn('is_locked_bps');
        });
    }
};
