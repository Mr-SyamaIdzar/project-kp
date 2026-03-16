<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lke_revisi_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('bps_user_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('tahun_id');
            $table->unsignedInteger('domain_id');
            $table->string('nama_kegiatan', 250);
            $table->string('nomor_rekomendasi', 255);
            $table->enum('status', ['requested', 'revised'])->default('requested');
            $table->unsignedInteger('revised_lke_id')->nullable();
            $table->timestamp('revised_at')->nullable();
            $table->timestamps();

            $table->foreign('bps_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tahun_id')->references('id')->on('tahun')->restrictOnDelete();
            $table->foreign('domain_id')->references('id')->on('domains')->restrictOnDelete();
            $table->foreign('revised_lke_id')->references('id')->on('lembar_kerja_evaluasi')->nullOnDelete();

            $table->unique(
                ['user_id', 'tahun_id', 'nama_kegiatan', 'nomor_rekomendasi', 'domain_id', 'status'],
                'lke_revisi_req_unique_package_domain_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lke_revisi_requests');
    }
};

