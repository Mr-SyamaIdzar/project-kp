<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lke_revisi_requests', 'round')) {
            Schema::table('lke_revisi_requests', function (Blueprint $table) {
                $table->unsignedTinyInteger('round')->default(1)->after('nomor_rekomendasi');
            });
        }
        if (!Schema::hasColumn('lke_revisi_requests', 'catatan')) {
            Schema::table('lke_revisi_requests', function (Blueprint $table) {
                $table->text('catatan')->nullable()->after('round'); // alasan revisi per round
            });
        }

        // Update unique key: allow 2 rounds
        Schema::table('lke_revisi_requests', function (Blueprint $table) {
            // Pastikan index FK tetap ada sebelum drop unique lama (MySQL bisa memakai unique ini untuk FK).
            $table->index('bps_user_id', 'lke_rr_idx_bps_user_id');
            $table->index('user_id', 'lke_rr_idx_user_id');
            $table->index('tahun_id', 'lke_rr_idx_tahun_id');
            $table->index('domain_id', 'lke_rr_idx_domain_id');
            $table->index('revised_lke_id', 'lke_rr_idx_revised_lke_id');

            $table->dropUnique('lke_revisi_req_unique_package_domain_status');
            $table->unique(
                ['user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi', 'domain_id', 'round', 'status'],
                'lke_revisi_req_unique_package_domain_round_status'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lke_revisi_requests', function (Blueprint $table) {
            $table->dropUnique('lke_revisi_req_unique_package_domain_round_status');
            $table->unique(
                ['user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi', 'domain_id', 'status'],
                'lke_revisi_req_unique_package_domain_status'
            );
            $table->dropIndex('lke_rr_idx_bps_user_id');
            $table->dropIndex('lke_rr_idx_user_id');
            $table->dropIndex('lke_rr_idx_tahun_id');
            $table->dropIndex('lke_rr_idx_domain_id');
            $table->dropIndex('lke_rr_idx_revised_lke_id');
            $table->dropColumn(['round', 'catatan']);
        });
    }
};

