<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdMenuSetting extends Model
{
    protected $table = 'opd_menu_settings';

    protected $fillable = [
        'user_id',
        'can_fill_data_umum',
        'can_fill_indikator',
    ];

    protected $casts = [
        'can_fill_data_umum' => 'boolean',
        'can_fill_indikator' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
