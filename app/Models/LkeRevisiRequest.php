<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LkeRevisiRequest extends Model
{
    protected $table = 'lke_revisi_requests';

    protected $fillable = [
        'bps_user_id',
        'user_id',
        'tahun_id',
        'domain_id',
        'nama_kegiatan',
        'nomor_rekomendasi',
        'status',
        'revised_lke_id',
        'revised_at',
    ];

    protected $casts = [
        'revised_at' => 'datetime',
    ];

    public function bpsUser()
    {
        return $this->belongsTo(User::class, 'bps_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tahun()
    {
        return $this->belongsTo(Tahun::class, 'tahun_id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function revisedLke()
    {
        return $this->belongsTo(LembarKerjaEvaluasi::class, 'revised_lke_id');
    }
}

