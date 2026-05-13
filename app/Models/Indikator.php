<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indikator extends Model
{
    protected $table = 'domains';

    protected $fillable = [
        'kode',
        'nama_domain',
        'nama_aspek',
        'nama_indikator',
    ];

    public function kriterias(){
        return $this->hasMany(\App\Models\Kriteria::class, 'domain_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($domain) {
            // Catatan: LembarKerjaEvaluasi TIDAK di-cascade delete di sini.
            // Jika masih ada LKE yang mereferensi domain ini, FK constraint database
            // akan mencegah penghapusan domain (QueryException kode 23000),
            // sehingga data LKE tetap aman.
            // Hanya Kriteria yang di-cascade delete karena kriteria adalah bagian
            // dari definisi domain itu sendiri.
            $domain->kriterias()->delete();
        });
    }

}
