<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model `lembar_kerja_evaluasi`.
 *
 * Konsep penting:
 * - Satu "paket" LKE diidentifikasi oleh (user_id, tahun_id, nama_kegiatan, nomor_rekomendasi).
 * - Satu indikator di dalam paket diidentifikasi oleh `domain_id` (mengarah ke `indikators`).
 * - Riwayat revisi OPD disimpan dengan cara CREATE record baru `status='revisi'`
 *   (bukan overwrite), dengan `revisi_round` (1/2) sebagai penanda ronde.
 * - Saat BPS finalisasi penilaian paket, `is_locked_bps=1` sehingga OPD tidak bisa
 *   mengubah apa pun (autosave/upload/finalize/revisi) di paket tersebut.
 *
 * Kolom penting yang sering dipakai:
 * - `nilai`: tingkat/kriteria yang dipilih OPD (juga jadi patokan wajib bukti dukung untuk 2–5)
 * - `penilaian_bps`: nilai akhir BPS untuk indikator
 * - `catatan_bps`: catatan evaluasi BPS atau alasan revisi (disatukan dalam UI BPS)
 */
class LembarKerjaEvaluasi extends Model
{
    protected $table = 'lembar_kerja_evaluasi';

    protected $fillable = [
        'user_id','tahun_id','domain_id','kriteria_id',
        'nama_kegiatan','nomor_rekomendasi','nilai','penjelasan','status',
        'revisi_round',
        'penilaian_bps', 'catatan_bps', 'is_revisi_bps', 'is_locked_bps'
    ];

    /**
     * Bukti dukung (file) untuk satu baris LKE indikator.
     */
    public function buktiDukung()
    {
        return $this->hasMany(\App\Models\BuktiDukung::class, 'lembar_kerja_id');
    }

    /**
     * OPD pemilik paket.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Master tahun.
     */
    public function tahun()
    {
        return $this->belongsTo(\App\Models\Tahun::class);
    }

    /**
     * Indikator/domain yang dinilai.
     */
    public function domain()
    {
        return $this->belongsTo(\App\Models\Indikator::class, 'domain_id');
    }

    /**
     * Tingkat/kriteria yang dipilih OPD.
     */
    public function kriteria()
    {
        return $this->belongsTo(\App\Models\Kriteria::class);
    }
}
