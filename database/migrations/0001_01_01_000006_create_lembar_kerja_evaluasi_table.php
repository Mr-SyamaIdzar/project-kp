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
        Schema::create('lembar_kerja_evaluasi', function (Blueprint $table) {
            $table->integer('id')->unsigned()->autoIncrement()->primary();

            $table->integer('user_id')->unsigned();
            $table->integer('tahun_id')->unsigned();
            $table->integer('domain_id')->unsigned();
            $table->integer('kriteria_id')->unsigned();

            $table->string('nama_kegiatan', 250);
            $table->string('nomor_rekomendasi', 255);
            $table->tinyInteger('nilai');
            $table->longText('penjelasan');
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('tahun_id')
                ->references('id')->on('tahun')
                ->restrictOnDelete();

            $table->foreign('domain_id')
                ->references('id')->on('domains')
                ->restrictOnDelete();

            $table->foreign('kriteria_id')
                ->references('id')->on('kriterias')
                ->restrictOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembar_kerja_evaluasi');
    }
};
