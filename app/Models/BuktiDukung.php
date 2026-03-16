<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuktiDukung extends Model
{
    protected $table = 'bukti_dukung';

    protected $fillable = ['lembar_kerja_id','file','original_name'];
    
    public function lembarKerja()
    {
        return $this->belongsTo(\App\Models\LembarKerjaEvaluasi::class, 'lembar_kerja_id');
    }

}
