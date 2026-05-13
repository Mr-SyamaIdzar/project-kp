<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kriteria extends Model
{
    protected $table = 'kriterias';

    protected $fillable = [
        'domain_id',
        'tingkat',
        'kriteria',
    ];

    public function domain()
    {
        return $this->belongsTo(Indikator::class, 'domain_id');
    }

    public function kriterias()
    {
        return $this->hasMany(Kriteria::class, 'domain_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Catatan: LembarKerjaEvaluasi TIDAK di-cascade delete di sini.
        // Jika masih ada LKE yang mereferensi kriteria ini, FK constraint database
        // akan mencegah penghapusan kriteria (QueryException kode 23000),
        // sehingga data LKE tetap aman.
    }

}
