<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model `lke_revisi_requests`.
 *
 * Berfungsi sebagai "ticket" revisi dari BPS → OPD per indikator.
 *
 * Aturan bisnis yang di-enforce di controller:
 * - Maksimal 2 ronde revisi (`round` 1 dan 2).
 * - Ronde 2 hanya boleh diminta setelah ronde 1 berstatus `revised`.
 * - Saat OPD submit revisi, status ticket berubah dari `requested` → `revised`
 *   dan `revised_lke_id` mengarah ke record LKE revisi yang baru dibuat.
 *
 * Kolom penting:
 * - `catatan`: alasan revisi per ronde (UI BPS memakai 1 textarea `catatan_bps`)
 * - `status`: 'requested' | 'revised' (dan kemungkinan status lain sesuai kebutuhan)
 */
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
        'round',
        'catatan',
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
        return $this->belongsTo(Indikator::class, 'domain_id');
    }

    public function revisedLke()
    {
        return $this->belongsTo(LembarKerjaEvaluasi::class, 'revised_lke_id');
    }
}

