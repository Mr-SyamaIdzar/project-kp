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
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function kriterias()
    {
        return $this->hasMany(Kriteria::class, 'domain_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($kriteria) {
            // Delete all related LembarKerjaEvaluasi
            \App\Models\LembarKerjaEvaluasi::where('kriteria_id', $kriteria->id)->delete();
        });
    }

}
