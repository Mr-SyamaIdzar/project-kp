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
            // Delete all related LembarKerjaEvaluasi first
            \App\Models\LembarKerjaEvaluasi::where('domain_id', $domain->id)->delete();
            // Then delete all related Kriterias
            $domain->kriterias()->delete();
        });
    }

}
