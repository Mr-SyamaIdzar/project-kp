<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPenilaianAkhir extends Model
{
    protected $table = 'admin_penilaian_akhir';

    protected $fillable = [
        'user_id',
        'tahun',
        'nilai_akhir',
        'catatan',
        'file',
        'original_name',
    ];

    protected $casts = [
        'nilai_akhir' => 'float',
        'tahun'       => 'integer',
    ];

    public function opd()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
