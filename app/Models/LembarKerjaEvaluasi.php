<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LembarKerjaEvaluasi extends Model
{
    protected $table = 'lembar_kerja_evaluasi';

    protected $fillable = [
        'user_id','tahun_id','domain_id','kriteria_id',
        'nama_kegiatan','nomor_rekomendasi','nilai','penjelasan','status',
        'penilaian_bps', 'catatan_bps', 'is_revisi_bps'
    ];
    public function buktiDukung()
    {
        return $this->hasMany(\App\Models\BuktiDukung::class, 'lembar_kerja_id');
    }

    public function user(){
    return $this->belongsTo(\App\Models\User::class);
    }

        public function tahun(){
        return $this->belongsTo(\App\Models\Tahun::class);
    }

    public function domain(){
        return $this->belongsTo(\App\Models\Domain::class);
    }

    public function kriteria(){
        return $this->belongsTo(\App\Models\Kriteria::class);
    }
}
