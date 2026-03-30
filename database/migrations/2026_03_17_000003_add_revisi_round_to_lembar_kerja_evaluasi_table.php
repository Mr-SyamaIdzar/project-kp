<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->unsignedTinyInteger('revisi_round')->nullable()->after('status'); // 1 atau 2 untuk status=revisi
        });
    }

    public function down(): void
    {
        Schema::table('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->dropColumn('revisi_round');
        });
    }
};

